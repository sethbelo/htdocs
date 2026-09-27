<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_teacher_child')->default(false)->after('parent_phone');
            $table->boolean('has_derogation')->default(false)->after('is_teacher_child');
            $table->text('derogation_notes')->nullable()->after('has_derogation');
            $table->decimal('advance_balance', 10, 2)->default(0.00)->after('derogation_notes');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->default('Cash')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['is_teacher_child', 'has_derogation', 'derogation_notes', 'advance_balance']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
