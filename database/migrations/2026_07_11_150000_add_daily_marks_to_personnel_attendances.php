<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_attendances', function (Blueprint $table) {
            $table->json('daily_marks')->nullable()->after('days_other');
        });
    }

    public function down(): void
    {
        Schema::table('personnel_attendances', function (Blueprint $table) {
            $table->dropColumn('daily_marks');
        });
    }
};
