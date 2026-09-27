<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establishment_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->string('group')->default('general'); // general, pos, kitchen, invoice, loyalty, sync
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->timestamps();

            $table->foreign('establishment_id')->references('id')->on('establishments')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['establishment_id', 'branch_id', 'group', 'key'], 'settings_unique_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
