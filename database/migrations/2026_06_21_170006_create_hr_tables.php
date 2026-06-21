<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('father_name');
            $table->text('original_address')->nullable();
            $table->text('current_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tazkira_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('disabled_by')->constrained()->nullOnDelete();
        });

        Schema::create('employee_job_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('designation')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('salary_grade')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('father_name');
            $table->text('original_address')->nullable();
            $table->text('current_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tazkira_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contractor_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->string('agreement_number')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('contractor_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('daily_rate', 15, 2)->nullable();
            $table->decimal('monthly_rate', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('personnel_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('personnel_type');
            $table->unsignedBigInteger('personnel_id');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('days_present')->default(0);
            $table->unsignedTinyInteger('days_absent')->default(0);
            $table->unsignedTinyInteger('days_leave')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_type', 'personnel_id']);
            $table->unique(['personnel_type', 'personnel_id', 'project_id', 'year', 'month'], 'personnel_attendance_unique');
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('status')->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period_year', 'period_month']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->string('personnel_type');
            $table->unsignedBigInteger('personnel_id');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('base_amount', 15, 2);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_type', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('personnel_attendances');
        Schema::dropIfExists('contractor_rates');
        Schema::dropIfExists('contractor_agreements');
        Schema::dropIfExists('contractors');
        Schema::dropIfExists('employee_contracts');
        Schema::dropIfExists('employee_salaries');
        Schema::dropIfExists('employee_job_details');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
        Schema::dropIfExists('employees');
    }
};
