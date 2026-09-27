<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the existing users table to add Yeboox-specific columns
        Schema::table('users', function (Blueprint $table) {
            // Add UUID support — we'll handle this with a trait
            $table->uuid('establishment_id')->nullable()->after('id');
            $table->uuid('branch_id')->nullable()->after('establishment_id');
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('pin_code')->nullable()->after('password');
            $table->string('rfid_badge')->unique()->nullable()->after('pin_code');
            $table->string('phone')->nullable()->after('rfid_badge');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('status')->default('active')->after('avatar_path'); // active, suspended
            $table->boolean('must_change_password')->default(false)->after('status');
            $table->string('locale', 5)->default('fr')->after('must_change_password');
            $table->softDeletes();

            $table->foreign('establishment_id')->references('id')->on('establishments')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->index(['establishment_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['establishment_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'establishment_id', 'branch_id', 'username', 'pin_code',
                'rfid_badge', 'phone', 'avatar_path', 'status',
                'must_change_password', 'locale',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
