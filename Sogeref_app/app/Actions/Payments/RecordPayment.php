<?php

namespace App\Actions\Payments;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Enums\DocumentStatus;
use App\Events\PaymentRecorded;
use App\Models\Document;
use App\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

class RecordPayment
{
    public function __construct(private readonly SynchronizeAccounting $accounting) {}

    public function handle(array $data, array $allocations): Payment
    {
        return DB::transaction(function () use ($data, $allocations): Payment {
            $allocated = round(array_sum(array_column($allocations, 'amount')), 2);
            if ($allocated > round((float) $data['amount'], 2)) {
                throw new DomainException('Le montant ventilé dépasse le paiement.');
            }
            $payment = Payment::create($data);

            foreach ($allocations as $allocation) {
                $document = Document::where('company_id', $data['company_id'])->lockForUpdate()->findOrFail($allocation['document_id']);
                $amount = round((float) $allocation['amount'], 2);
                if ($amount <= 0 || $amount > $document->balance_due) {
                    throw new DomainException("Ventilation invalide pour {$document->number}.");
                }
                $payment->allocations()->create(['document_id' => $document->id, 'amount' => $amount]);
                $paid = round((float) $document->amount_paid + $amount, 2);
                $document->update(['amount_paid' => $paid, 'status' => $paid >= (float) $document->total ? DocumentStatus::Paid : DocumentStatus::PartiallyPaid]);
            }

            $payment->load('allocations.document');
            // ZONE DE SYNCHRONISATION COMPTABLE ET TRÉSORERIE.
            $this->accounting->payment($payment);
            DB::afterCommit(fn () => PaymentRecorded::dispatch($payment));

            return $payment;
        });
    }
}
