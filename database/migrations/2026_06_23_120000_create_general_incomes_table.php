<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->date('transaction_date');
            $table->string('reference_number')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('recorded');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('general_expenses', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('general_expenses', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::dropIfExists('general_incomes');
    }
};
