<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Communication & marketing : conversations multicanales (WhatsApp, SMS,
| e-mail), messages, campagnes de masse, destinataires et modèles.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comm_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('channel', 20);
            $table->string('address');
            $table->string('sender')->nullable();
            $table->string('subject')->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamps();
            $table->index(['company_id', 'channel', 'last_message_at']);
            $table->index(['company_id', 'channel', 'address']);
        });

        Schema::create('comm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel', 20);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('audience')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('stats')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('comm_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('comm_conversations')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('comm_campaigns')->nullOnDelete();
            $table->string('direction', 3);
            $table->string('channel', 20);
            $table->text('body');
            $table->string('subject')->nullable();
            $table->json('media')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('provider')->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->text('error')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'channel', 'created_at']);
        });

        Schema::create('comm_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('comm_campaigns')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('address');
            $table->string('status', 20)->default('pending');
            $table->foreignId('message_id')->nullable()->constrained('comm_messages')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'address']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('comm_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();
            $table->index(['company_id', 'channel']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'whatsapp')) {
                $table->string('whatsapp', 40)->nullable();
            }
            if (! Schema::hasColumn('contacts', 'marketing_opt_in')) {
                $table->boolean('marketing_opt_in')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'marketing_opt_in']);
        });
        Schema::dropIfExists('comm_templates');
        Schema::dropIfExists('comm_campaign_recipients');
        Schema::dropIfExists('comm_messages');
        Schema::dropIfExists('comm_campaigns');
        Schema::dropIfExists('comm_conversations');
    }
};
