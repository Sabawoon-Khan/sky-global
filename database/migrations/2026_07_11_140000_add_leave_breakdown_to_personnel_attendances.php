<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_attendances', function (Blueprint $table) {
            $table->unsignedTinyInteger('days_sick_leave')->default(0)->after('days_leave');
            $table->unsignedTinyInteger('days_annual_leave')->default(0)->after('days_sick_leave');
            $table->unsignedTinyInteger('days_casual_leave')->default(0)->after('days_annual_leave');
            $table->unsignedTinyInteger('days_other')->default(0)->after('days_casual_leave');
        });
    }

    public function down(): void
    {
        Schema::table('personnel_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'days_sick_leave',
                'days_annual_leave',
                'days_casual_leave',
                'days_other',
            ]);
        });
    }
};
