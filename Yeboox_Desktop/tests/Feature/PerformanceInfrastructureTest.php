<?php

namespace Tests\Feature;

use App\Jobs\WriteActivityLog;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PerformanceInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_main_sqlite_query_indexes_are_installed(): void
    {
        $indexes = collect(\Illuminate\Support\Facades\Schema::getIndexes('sales'))->pluck('name');

        $this->assertContains('sales_created_idx', $indexes);
        $this->assertContains('sales_session_created_idx', $indexes);
        $this->assertContains('sales_user_created_idx', $indexes);
    }

    public function test_activity_audit_is_sent_to_the_local_queue(): void
    {
        Queue::fake();

        ActivityLog::log('TEST', 'Journal différé');

        Queue::assertPushed(WriteActivityLog::class, fn (WriteActivityLog $job) => $job->queue === 'audit');
        $this->assertDatabaseMissing('activity_logs', ['action' => 'TEST']);
    }
}
