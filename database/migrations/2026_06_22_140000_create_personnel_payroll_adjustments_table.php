<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('personnel_type');
            $table->unsignedBigInteger('personnel_id');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('payroll_item_id')->nullable()->constrained('payroll_items')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(
                ['personnel_type', 'personnel_id', 'period_year', 'period_month'],
                'ppa_personnel_period_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_payroll_adjustments');
    }
};
