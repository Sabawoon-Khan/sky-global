<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_payments', function (Blueprint $table) {
            $table->id();
            $table->string('period_type');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('rate', 8, 4);
            $table->unsignedTinyInteger('rate_percent');
            $table->decimal('tax_due', 15, 2)->default(0);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('AFN');
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('paid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_type', 'year', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_payments');
    }
};
