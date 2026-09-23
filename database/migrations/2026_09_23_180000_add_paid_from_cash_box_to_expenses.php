<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_expenses', function (Blueprint $table) {
            $table->boolean('paid_from_cash_box')->default(false)->after('expense_fund_id');
        });

        Schema::table('project_expenses', function (Blueprint $table) {
            $table->boolean('paid_from_cash_box')->default(false)->after('expense_fund_id');
        });

        DB::table('general_expenses')
            ->whereNotNull('expense_fund_id')
            ->update([
                'paid_from_cash_box' => true,
                'expense_fund_id' => null,
            ]);

        DB::table('project_expenses')
            ->whereNotNull('expense_fund_id')
            ->update([
                'paid_from_cash_box' => true,
                'expense_fund_id' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('general_expenses', function (Blueprint $table) {
            $table->dropColumn('paid_from_cash_box');
        });

        Schema::table('project_expenses', function (Blueprint $table) {
            $table->dropColumn('paid_from_cash_box');
        });
    }
};
