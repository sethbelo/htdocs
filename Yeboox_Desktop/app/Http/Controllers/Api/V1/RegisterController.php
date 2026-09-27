<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RegisterSessionResource;
use App\Models\RegisterSession;
use App\Models\SalePayment;
use App\Support\DisplayCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function current(Request $request)
    {
        $session = $this->openSession($request);

        return $session
            ? new RegisterSessionResource($session->load('user'))
            : response()->json(['data' => null]);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'required_without_all:opening_balance_usd,opening_balance_cdf'],
            'opening_balance_usd' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_cdf' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $session = DB::transaction(function () use ($request, $data) {
            if ($this->openSession($request, true)) {
                throw ValidationException::withMessages(['register' => 'Une caisse est déjà ouverte pour cet utilisateur.']);
            }
            $base = DisplayCurrency::baseCode();
            $opening = (float) ($data['opening_balance'] ?? ($base === 'USD' ? ($data['opening_balance_usd'] ?? 0) : ($data['opening_balance_cdf'] ?? 0)));

            return RegisterSession::create([
                'user_id' => $request->user()->id,
                'opened_at' => now(),
                'opening_balance' => $opening,
                'opening_balance_usd' => $base === 'USD' ? $opening : 0,
                'opening_balance_cdf' => $base === 'CDF' ? $opening : 0,
                'closing_balance' => 0,
                'closing_balance_usd' => 0,
                'closing_balance_cdf' => 0,
                'exchange_rate' => DisplayCurrency::rate(),
                'base_currency' => $base,
                'secondary_currency' => DisplayCurrency::secondaryCode(),
                'base_to_secondary_rate' => DisplayCurrency::rate(),
                'status' => 'open',
                'note' => $data['note'] ?? null,
            ]);
        }, 3);

        return (new RegisterSessionResource($session->load('user')))->response()->setStatusCode(201);
    }

    public function close(Request $request)
    {
        $data = $request->validate([
            'closing_balance' => ['nullable', 'numeric', 'min:0', 'required_without_all:closing_balance_usd,closing_balance_cdf'],
            'closing_balance_usd' => ['nullable', 'numeric', 'min:0'],
            'closing_balance_cdf' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $session = DB::transaction(function () use ($request, $data) {
            $session = RegisterSession::query()->where('user_id', $request->user()->id)->where('status', 'open')->lockForUpdate()->latest('opened_at')->first();
            if (! $session) {
                throw ValidationException::withMessages(['register' => 'Aucune caisse ouverte à clôturer.']);
            }

            $base = $session->base_currency ?: DisplayCurrency::baseCode();
            $cash = SalePayment::query()->where('method', 'cash')->whereHas('sale', fn ($query) => $query->where('register_session_id', $session->id))->sum('amount');
            $expected = (float) $session->opening_balance + (float) $cash;
            $closing = (float) ($data['closing_balance'] ?? ($base === 'USD' ? ($data['closing_balance_usd'] ?? 0) : ($data['closing_balance_cdf'] ?? 0)));
            $tolerance = DisplayCurrency::decimals($base) === 0 ? 1 : 0.01;
            $hasDifference = abs($closing - $expected) >= $tolerance;
            if ($hasDifference && blank($data['note'] ?? null)) {
                throw ValidationException::withMessages(['note' => 'Expliquez l’écart constaté avant de clôturer la caisse.']);
            }

            $session->update([
                'closed_at' => now(),
                'closing_balance' => $closing,
                'closing_balance_usd' => $base === 'USD' ? $closing : 0,
                'closing_balance_cdf' => $base === 'CDF' ? $closing : 0,
                'status' => 'closed',
                'note' => $data['note'] ?? $session->note,
            ]);

            return $session;
        }, 3);

        return new RegisterSessionResource($session->load('user'));
    }

    private function openSession(Request $request, bool $lock = false): ?RegisterSession
    {
        $query = RegisterSession::query()->where('user_id', $request->user()->id)->where('status', 'open')->latest('opened_at');

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }
}
