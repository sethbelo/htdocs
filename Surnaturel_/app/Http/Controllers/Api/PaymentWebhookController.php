<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhooks de paiement (Mobile Money, TPE, agrégateurs) : signature HMAC-SHA256, traitement idempotent.
 * Le prestataire confirme une transaction dont la référence a été saisie en caisse.
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $secret = config("services.payment_webhooks.{$provider}.secret");
        abort_unless($secret, 404);

        $signature = (string) $request->header('X-Signature');
        abort_unless(hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature), 401, 'Signature invalide.');

        $data = $request->validate([
            'event_id' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'status' => ['required', 'in:success,failed,pending'],
        ]);

        $event = WebhookEvent::query()->firstOrCreate(
            ['provider' => $provider, 'event_id' => $data['event_id']],
            ['type' => $data['type'], 'payload' => $request->all()],
        );

        if ($event->processed_at) {
            return response()->json(['status' => 'already_processed']);
        }

        $payment = Payment::query()->where('reference', $data['reference'])->latest()->first();

        if (! $payment) {
            $event->update(['status' => 'unmatched', 'processed_at' => now()]);

            return response()->json(['status' => 'unmatched'], 202);
        }

        $amountMatches = abs((float) $payment->amount - (float) $data['amount']) < 0.01;

        $payment->update(['meta' => array_merge($payment->meta ?? [], [
            'provider' => $provider,
            'provider_status' => $data['status'],
            'provider_event' => $data['event_id'],
            'amount_matches' => $amountMatches,
            'confirmed_at' => now()->toIso8601String(),
        ])]);

        $event->update(['status' => $amountMatches && $data['status'] === 'success' ? 'confirmed' : 'anomaly', 'processed_at' => now()]);

        return response()->json(['status' => $event->status]);
    }
}
