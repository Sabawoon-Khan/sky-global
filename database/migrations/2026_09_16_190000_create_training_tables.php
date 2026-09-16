<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_ministry_payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->date('payment_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('AFN');
            $table->string('receipt_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_guards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('father_name');
            $table->string('grandfather_name')->nullable();
            $table->string('tazkira_number')->nullable();
            $table->string('id_card_number')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('batch_number');
            $table->string('status', 32)->default('registered');
            $table->string('training_path', 32)->nullable();
            $table->date('ministry_period_start')->nullable();
            $table->date('ministry_period_end')->nullable();
            $table->string('fee_number')->nullable();
            $table->decimal('fee_amount', 12, 2)->nullable();
            $table->foreignId('ministry_payment_id')->nullable()->constrained('training_ministry_payments')->nullOnDelete();
            $table->string('company_trainer')->nullable();
            $table->string('company_location')->nullable();
            $table->string('certificate_number')->nullable()->unique();
            $table->date('certificate_issued_at')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('certificate_original_filename')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('batch_number');
            $table->index('tazkira_number');
            $table->index('id_card_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_guards');
        Schema::dropIfExists('training_ministry_payments');
    }
};
