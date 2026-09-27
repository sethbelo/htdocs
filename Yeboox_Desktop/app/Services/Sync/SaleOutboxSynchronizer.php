<?php

namespace App\Services\Sync;

use App\Models\Sale;
use App\Models\SyncOutbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

final class SaleOutboxSynchronizer
{
    /** @return array{processed:int,synced:int,failed:int,pending:int} */
    public function syncPending(?int $limit = null): array
    {
        $limit ??= (int) config('offline-sync.batch_size', 50);
        $stats = ['processed' => 0, 'synced' => 0, 'failed' => 0, 'pending' => 0];

        if (! config('offline-sync.enabled') || config('offline-sync.runtime') !== 'desktop') {
            return $stats;
        }

        $events = SyncOutbox::query()
            ->whereIn('status', ['pending', 'retry'])
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('id')
            ->limit(max(1, min($limit, 500)))
            ->get();

        foreach ($events as $event) {
            $stats['processed']++;
            try {
                $response = $this->send($event);
                if (! $response->successful()) {
                    $detail = $response->json('message') ?: $response->body();
                    throw new \RuntimeException(
                        'Synchronisation refusée (HTTP '.$response->status().') : '.mb_substr((string) $detail, 0, 800)
                    );
                }

                DB::transaction(function () use ($event): void {
                    $event->update([
                        'status' => 'synced',
                        'attempts' => $event->attempts + 1,
                        'last_attempt_at' => now(),
                        'synced_at' => now(),
                        'last_error' => null,
                    ]);
                    Sale::query()->where('sync_uuid', $event->aggregate_uuid)->update([
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                    ]);
                });
                $stats['synced']++;
            } catch (Throwable $exception) {
                $attempts = $event->attempts + 1;
                $delays = config('offline-sync.backoff_seconds', [15, 30, 60, 300]);
                $delay = $delays[min($attempts - 1, count($delays) - 1)] ?? 1800;
                $event->update([
                    'status' => 'retry',
                    'attempts' => $attempts,
                    'last_attempt_at' => now(),
                    'available_at' => now()->addSeconds((int) $delay),
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);
                $stats['failed']++;
            }
        }

        $stats['pending'] = SyncOutbox::query()->whereIn('status', ['pending', 'retry'])->count();

        return $stats;
    }

    private function send(SyncOutbox $event)
    {
        $url = (string) config('offline-sync.remote_url');
        $token = (string) config('offline-sync.token');
        if ($url === '' || $token === '') {
            throw new \RuntimeException('La connexion au serveur central n’est pas configurée.');
        }

        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout((int) config('offline-sync.timeout', 12))
            ->withHeaders(['X-Yeboox-Sync-Token' => $token])
            ->post($url.'/api/v2/sync/sales', [
                'event_uuid' => $event->event_uuid,
                'event_type' => $event->event_type,
                'payload' => $event->payload,
            ]);
    }
}
