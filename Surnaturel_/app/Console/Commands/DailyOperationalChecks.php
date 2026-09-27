<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\OperationalDigest;
use App\Services\Support\Money;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('erp:daily-checks')]
#[Description('Maintenance machines, devis expirés et factures échues : notifications quotidiennes')]
class DailyOperationalChecks extends Command
{
    public function handle(): int
    {
        $expired = Quote::query()->whereIn('status', [QuoteStatus::Draft, QuoteStatus::Sent])->whereDate('valid_until', '<', today())->update(['status' => QuoteStatus::Expired]);

        $maintenance = Machine::query()->whereDate('next_maintenance_at', '<=', today()->addDays(7))->where('status', 'operational')->get();
        if ($maintenance->isNotEmpty()) {
            Notification::send(User::permission('machines.update')->where('is_active', true)->get(), new OperationalDigest(
                'Maintenance préventive à planifier',
                $maintenance->map(fn (Machine $m) => "{$m->code} {$m->name} ({$m->next_maintenance_at->format('d/m')})")->implode(', '),
                route('machines.index'),
            ));
        }

        $overdue = Invoice::query()->whereIn('status', ['issued', 'partially_paid'])->whereDate('due_at', '<', today())->get();
        if ($overdue->isNotEmpty()) {
            Notification::send(User::permission('invoices.approve')->where('is_active', true)->get(), new OperationalDigest(
                'Factures échues',
                $overdue->count().' facture(s) impayée(s) pour '.Money::format($overdue->sum(fn (Invoice $i) => $i->balanceDue())),
                route('invoices.index', ['impayees' => 1]),
            ));
        }

        $this->info("Devis expirés : {$expired} · machines à entretenir : {$maintenance->count()} · factures échues : {$overdue->count()}");

        return self::SUCCESS;
    }
}
