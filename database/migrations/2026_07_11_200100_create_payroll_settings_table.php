<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('working_days_per_month')->default(30);
            $table->unsignedTinyInteger('absent_deduction_percent')->default(100);
            $table->unsignedTinyInteger('sick_leave_pay_percent')->default(100);
            $table->unsignedTinyInteger('annual_leave_pay_percent')->default(100);
            $table->unsignedTinyInteger('casual_leave_pay_percent')->default(100);
            $table->unsignedTinyInteger('other_leave_pay_percent')->default(0);
            $table->timestamps();
        });

        DB::table('payroll_settings')->insert([
            'working_days_per_month' => 30,
            'absent_deduction_percent' => 100,
            'sick_leave_pay_percent' => 100,
            'annual_leave_pay_percent' => 100,
            'casual_leave_pay_percent' => 100,
            'other_leave_pay_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
