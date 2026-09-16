<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_ministry_payments', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('reference_number');
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::table('training_ministry_payments', function (Blueprint $table) {
            $table->dropIndex(['batch_number']);
            $table->dropColumn('batch_number');
        });
    }
};
