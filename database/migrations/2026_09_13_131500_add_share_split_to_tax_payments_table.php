<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_payments', function (Blueprint $table) {
            $table->decimal('their_amount', 15, 2)->default(0)->after('amount');
            $table->decimal('company_amount', 15, 2)->default(0)->after('their_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tax_payments', function (Blueprint $table) {
            $table->dropColumn(['their_amount', 'company_amount']);
        });
    }
};
