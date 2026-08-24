<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('general_incomes')->update(['currency' => 'AFN']);
        DB::table('general_expenses')->update(['currency' => 'AFN']);

        Schema::table('general_incomes', function (Blueprint $table) {
            $table->string('currency', 3)->default('AFN')->change();
        });

        Schema::table('general_expenses', function (Blueprint $table) {
            $table->string('currency', 3)->default('AFN')->change();
        });
    }

    public function down(): void
    {
        // No rollback — AFN is the operational currency for general finance.
    }
};
