<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_funds', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount_received', 15, 2);
            $table->string('currency', 3)->default('AFN');
            $table->string('received_from')->nullable();
            $table->string('description')->nullable();
            $table->date('received_date');
            $table->string('reference_number')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('general_expenses', function (Blueprint $table) {
            $table->foreignId('expense_fund_id')
                ->nullable()
                ->after('account_id')
                ->constrained('expense_funds')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('general_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_fund_id');
        });

        Schema::dropIfExists('expense_funds');
    }
};
