<?php

namespace App\Services;

use App\Models\AccountingRecord;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function postInvoice(Invoice $invoice): Invoice
    {
        if ($invoice->posted_at) {
            return $invoice;
        }

        if (! $invoice->customer_id) {
            throw ValidationException::withMessages(['invoice' => 'Un client est requis avant de valider la facture.']);
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->loadMissing('items.product');
            $reference = $invoice->number;
            $netRevenue = max(0, (float) $invoice->subtotal - (float) $invoice->discount_amount);
            $serviceGross = (float) $invoice->items->filter(fn ($item) => $item->product?->type === 'service')->sum('total_price');
            $goodsGross = max(0, (float) $invoice->subtotal - $serviceGross);
            $gross = $goodsGross + $serviceGross;
            $serviceRevenue = $gross > 0 ? round($netRevenue * ($serviceGross / $gross), 2) : 0;
            $goodsRevenue = round($netRevenue - $serviceRevenue, 2);
            $lines = [['account' => '4111', 'debit' => (float) $invoice->total_amount, 'credit' => 0]];
            if ($goodsRevenue > 0) {
                $lines[] = ['account' => '701', 'debit' => 0, 'credit' => $goodsRevenue];
            }
            if ($serviceRevenue > 0) {
                $lines[] = ['account' => '706', 'debit' => 0, 'credit' => $serviceRevenue];
            }

            if ((float) $invoice->tax_amount > 0) {
                $lines[] = ['account' => '4431', 'debit' => 0, 'credit' => (float) $invoice->tax_amount];
            }

            $this->postBalanced($reference, $invoice->invoice_date, 'VE', 'invoice', "Facture {$invoice->number}", $lines, $invoice);
            $invoice->update(['posted_at' => now(), 'status' => 'unpaid']);

            return $invoice->refresh();
        });
    }

    public function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        $this->postInvoice($invoice);
        $balance = $invoice->fresh()->balance_due;

        if ((float) $data['amount'] > $balance + 0.001) {
            throw ValidationException::withMessages(['paymentAmount' => 'Le montant dépasse le solde restant de la facture.']);
        }

        return DB::transaction(function () use ($invoice, $data) {
            $payment = InvoicePayment::create([
                ...$data,
                'invoice_id' => $invoice->id,
                'number' => $this->nextNumber(InvoicePayment::class, 'REG'),
                'user_id' => auth()->id(),
            ]);

            $this->postBalanced(
                $payment->number,
                $payment->payment_date,
                'TR',
                'payment',
                "Règlement {$invoice->number}",
                [
                    ['account' => $payment->treasury_account, 'debit' => (float) $payment->amount, 'credit' => 0],
                    ['account' => '4111', 'debit' => 0, 'credit' => (float) $payment->amount],
                ],
                $payment,
            );

            $paid = (float) $invoice->payments()->sum('amount');
            $credited = (float) $invoice->creditNotes()->sum('total_amount');
            $invoice->update([
                'paid_amount' => $paid,
                'status' => ((float) $invoice->total_amount - $paid - $credited) <= 0.001 ? 'paid' : 'unpaid',
            ]);

            return $payment;
        });
    }

    public function createCreditNote(Invoice $invoice, array $data): CreditNote
    {
        $this->postInvoice($invoice);
        $available = $invoice->fresh()->balance_due;
        $total = (float) $data['subtotal'] + (float) $data['tax_amount'];

        if ($total > $available + 0.001) {
            throw ValidationException::withMessages(['creditSubtotal' => 'L’avoir dépasse le solde disponible de la facture.']);
        }

        return DB::transaction(function () use ($invoice, $data, $total) {
            $invoice->loadMissing('items.product');
            $credit = CreditNote::create([
                ...$data,
                'number' => $this->nextNumber(CreditNote::class, 'AV'),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'total_amount' => $total,
                'status' => 'posted',
                'posted_at' => now(),
                'user_id' => auth()->id(),
            ]);

            $serviceGross = (float) $invoice->items->filter(fn ($item) => $item->product?->type === 'service')->sum('total_price');
            $goodsGross = max(0, (float) $invoice->subtotal - $serviceGross);
            $gross = $goodsGross + $serviceGross;
            $serviceCredit = $gross > 0 ? round((float) $credit->subtotal * ($serviceGross / $gross), 2) : 0;
            $goodsCredit = round((float) $credit->subtotal - $serviceCredit, 2);
            $lines = [['account' => '4111', 'debit' => 0, 'credit' => (float) $credit->total_amount]];
            if ($goodsCredit > 0) {
                $lines[] = ['account' => '701', 'debit' => $goodsCredit, 'credit' => 0];
            }
            if ($serviceCredit > 0) {
                $lines[] = ['account' => '706', 'debit' => $serviceCredit, 'credit' => 0];
            }
            if ((float) $credit->tax_amount > 0) {
                $lines[] = ['account' => '4431', 'debit' => (float) $credit->tax_amount, 'credit' => 0];
            }

            $this->postBalanced($credit->number, $credit->credit_date, 'AV', 'credit_note', "Avoir sur {$invoice->number}", $lines, $credit);

            if ($invoice->fresh()->balance_due <= 0.001) {
                $invoice->update(['status' => 'paid']);
            }

            return $credit;
        });
    }

    public function createExpense(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $expense = Expense::create([
                ...$data,
                'number' => $this->nextNumber(Expense::class, 'DEP'),
                'total_amount' => (float) $data['subtotal'] + (float) $data['tax_amount'],
                'status' => 'posted',
                'posted_at' => now(),
                'user_id' => auth()->id(),
            ]);

            $lines = [
                ['account' => $expense->expense_account, 'debit' => (float) $expense->subtotal, 'credit' => 0],
                ['account' => $expense->credit_account, 'debit' => 0, 'credit' => (float) $expense->total_amount],
            ];
            if ((float) $expense->tax_amount > 0) {
                $lines[] = ['account' => '4452', 'debit' => (float) $expense->tax_amount, 'credit' => 0];
            }

            $this->postBalanced($expense->number, $expense->expense_date, 'AC', 'expense', $expense->description, $lines, $expense);

            return $expense;
        });
    }

    public function postManual(array $data): string
    {
        $reference = 'OD-'.now()->format('YmdHis').'-'.random_int(10, 99);
        $this->postBalanced($reference, $data['record_date'], 'OD', 'manual', $data['description'], [
            ['account' => $data['debit_code'], 'debit' => (float) $data['amount'], 'credit' => 0],
            ['account' => $data['credit_code'], 'debit' => 0, 'credit' => (float) $data['amount']],
        ]);

        return $reference;
    }

    public function postOperation(Model $source, string $reference, $date, string $journal, string $type, string $description, array $lines): void
    {
        $this->postBalanced($reference, $date, $journal, $type, $description, $lines, $source);
    }

    private function postBalanced(string $reference, $date, string $journal, string $type, string $description, array $lines, ?Model $source = null): void
    {
        $debit = round(collect($lines)->sum('debit'), 2);
        $credit = round(collect($lines)->sum('credit'), 2);

        if ($debit <= 0 || abs($debit - $credit) > 0.001) {
            throw ValidationException::withMessages(['accounting' => 'L’écriture comptable doit être équilibrée.']);
        }

        foreach ($lines as $line) {
            AccountingRecord::create([
                'reference' => $reference,
                'journal_code' => $journal,
                'entry_type' => $type,
                'record_date' => $date,
                'syscohada_code' => $line['account'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'description' => $description,
                'user_id' => auth()->id(),
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
            ]);
        }
    }

    private function nextNumber(string $model, string $prefix): string
    {
        $year = now()->format('Y');
        $last = $model::query()->where('number', 'like', "{$prefix}-{$year}-%")->latest('id')->value('number');
        $sequence = $last ? ((int) str($last)->afterLast('-')->toString()) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}
