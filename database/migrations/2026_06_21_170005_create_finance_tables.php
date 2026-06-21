<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3)->default('USD');
            $table->decimal('rate', 15, 6);
            $table->date('effective_date');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_date']);
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->decimal('budgeted_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('period')->default('total');
            $table->timestamps();
        });

        Schema::create('project_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('description')->nullable();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('recorded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('description')->nullable();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('recorded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('general_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('description')->nullable();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('recorded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expense_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('general_expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('allocated_amount', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('direction');
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('expense_allocations');
        Schema::dropIfExists('general_expenses');
        Schema::dropIfExists('project_expenses');
        Schema::dropIfExists('project_incomes');
        Schema::dropIfExists('project_budgets');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
