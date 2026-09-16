<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_guards', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('created_by')->constrained('employees')->nullOnDelete();
            $table->foreignId('contractor_id')->nullable()->after('employee_id')->constrained('contractors')->nullOnDelete();

            $table->unique(['employee_id'], 'training_guards_employee_unique');
            $table->unique(['contractor_id'], 'training_guards_contractor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('training_guards', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['contractor_id']);
            $table->dropColumn(['employee_id', 'contractor_id']);
        });
    }
};
