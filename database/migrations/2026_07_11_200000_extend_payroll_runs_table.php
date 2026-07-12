<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropUnique(['period_year', 'period_month']);
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->string('title')->nullable()->after('id');
            $table->string('payroll_type')->default('general')->after('title');
            $table->foreignId('project_id')->nullable()->after('payroll_type')->constrained()->nullOnDelete();
            $table->date('date_from')->nullable()->after('project_id');
            $table->date('date_to')->nullable()->after('date_from');
            $table->foreignId('attendance_sheet_id')->nullable()->after('date_to')->constrained('attendance_sheets')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('processed_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_sheet_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['title', 'payroll_type', 'date_from', 'date_to']);
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->unique(['period_year', 'period_month']);
        });
    }
};
