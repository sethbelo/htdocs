<?php

namespace App\Console\Commands;

use App\Actions\Accounting\SynchronizeAccounting;
use App\Enums\DocumentType;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Console\Command;

class SynchronizeAccountingCommand extends Command
{
    protected $signature = 'accounting:sync {--company= : Identifiant de l’entreprise à synchroniser}';

    protected $description = 'Synchronise factures, paiements, dépenses et flux bancaires avec la comptabilité OHADA';

    public function handle(SynchronizeAccounting $accounting): int
    {
        $companies = Company::query()
            ->when($this->option('company'), fn ($query, $id) => $query->whereKey($id))
            ->get();

        if ($companies->isEmpty()) {
            $this->components->warn('Aucune entreprise à synchroniser.');

            return self::SUCCESS;
        }

        foreach ($companies as $company) {
            $this->components->task("Synchronisation de {$company->name}", function () use ($accounting, $company): void {
                // L'ordre est important : les créances clients précèdent leurs règlements.
                Document::where('company_id', $company->id)->where('type', DocumentType::Invoice->value)->orderBy('id')->each(fn (Document $document) => $accounting->invoice($document));
                Expense::where('company_id', $company->id)->orderBy('id')->each(fn (Expense $expense) => $accounting->expense($expense));
                Payment::where('company_id', $company->id)->orderBy('id')->each(fn (Payment $payment) => $accounting->payment($payment));
                BankTransaction::whereHas('account', fn ($query) => $query->where('company_id', $company->id))
                    ->whereNull('reconcilable_type')
                    ->orderBy('id')
                    ->each(fn (BankTransaction $transaction) => $accounting->bankTransaction($transaction, (float) data_get($transaction->raw_data, 'exchange_rate', 1)));
            });
        }

        $this->components->info('Comptabilité et trésorerie synchronisées sans doublon.');

        return self::SUCCESS;
    }
}
