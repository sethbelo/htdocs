<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conception graphique, BAT, parc machines, production et livraisons.
     */
    public function up(): void
    {
        Schema::create('design_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('designer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('to_design')->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('blocked_reason')->nullable();
            $table->text('report')->nullable();
            $table->timestamps();
        });

        Schema::create('design_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_job_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->text('comment')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['design_job_id', 'version']);
        });

        Schema::create('proofs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_version_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('validator_type', 20)->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('validator_name')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('comment')->nullable();
            $table->string('locked_path')->nullable();
            $table->string('locked_checksum', 64)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->string('signature_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 30);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('print_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('operational')->index();
            $table->unsignedBigInteger('counter')->default(0);
            $table->unsignedSmallInteger('maintenance_interval_days')->default(90);
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->decimal('purchase_cost', 15, 2)->default(0);
            $table->date('acquired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('machine_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('planned');
            $table->date('scheduled_on')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('downtime_hours', 8, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->unsignedBigInteger('counter_reading')->nullable();
            $table->string('technician')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('proof_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('print_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedInteger('planned_quantity')->default(0);
            $table->unsignedInteger('produced_quantity')->default(0);
            $table->unsignedInteger('waste_quantity')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('quality_passed')->nullable();
            $table->foreignId('quality_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('print_job_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('waste_quantity', 15, 3)->default(0);
            $table->foreignId('stock_movement_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('fee', 15, 2)->default(0);
            $table->string('proof_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('failure_reason')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['deliveries', 'print_job_consumptions', 'print_jobs', 'machine_maintenances', 'machines', 'proofs', 'design_versions', 'design_jobs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
