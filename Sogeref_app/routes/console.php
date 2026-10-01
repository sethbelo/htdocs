<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Services\Communication\CampaignService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('sogeref:mark-overdue', function () {
    $count = Document::query()->where('type', 'invoice')->whereDate('due_date', '<', today())
        ->whereColumn('amount_paid', '<', 'total')->whereNotIn('status', ['paid', 'cancelled'])
        ->update(['status' => DocumentStatus::Overdue]);
    $this->info("{$count} facture(s) marquée(s) en retard.");
})->purpose('Marquer les factures échues et impayées');

Artisan::command('sogeref:schedule-reminders', function () {
    $documents = Document::query()->where('type', 'invoice')->where('status', 'overdue')->whereColumn('amount_paid', '<', 'total')->get();
    foreach ($documents as $document) {
        DB::table('reminders')->updateOrInsert(
            ['company_id' => $document->company_id, 'document_id' => $document->id, 'channel' => 'email', 'scheduled_at' => today()->setTime(9, 0)],
            ['status' => 'pending', 'payload' => json_encode(['document' => $document->number, 'balance_due' => $document->balance_due]), 'created_at' => now(), 'updated_at' => now()]
        );
    }
    $this->info($documents->count().' relance(s) planifiée(s).');
})->purpose('Planifier les relances automatiques des factures échues');

Schedule::command('sogeref:mark-overdue')->dailyAt('00:10')->withoutOverlapping();
Schedule::command('sogeref:schedule-reminders')->weekdays()->at('08:00')->withoutOverlapping();

Artisan::command('sogeref:dispatch-campaigns', function (CampaignService $campaigns) {
    $this->info($campaigns->dispatchDue().' campagne(s) programmée(s) lancée(s).');
})->purpose('Lancer les campagnes de communication arrivées à échéance');

Schedule::command('sogeref:dispatch-campaigns')->everyMinute()->withoutOverlapping();
