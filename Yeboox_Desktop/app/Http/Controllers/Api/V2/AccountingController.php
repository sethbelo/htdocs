<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\AccountingRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'journal_code' => ['nullable', 'string', 'max:20'],
            'syscohada_code' => ['nullable', 'string', 'max:30'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $query = AccountingRecord::query()
            ->with('user:id,name')
            ->when($data['journal_code'] ?? null, fn ($builder, string $code) => $builder->where('journal_code', $code))
            ->when($data['syscohada_code'] ?? null, fn ($builder, string $code) => $builder->where('syscohada_code', $code))
            ->when($data['from'] ?? null, fn ($builder, string $date) => $builder->whereDate('record_date', '>=', $date))
            ->when($data['to'] ?? null, fn ($builder, string $date) => $builder->whereDate('record_date', '<=', $date));

        $summary = (clone $query)->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')->first();
        $records = $query->latest('record_date')->latest('id')->paginate($data['per_page'] ?? 50);

        return response()->json([
            'data' => $records->items(),
            'summary' => [
                'debit' => (float) $summary->debit,
                'credit' => (float) $summary->credit,
                'balance' => round((float) $summary->debit - (float) $summary->credit, 2),
            ],
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }
}
