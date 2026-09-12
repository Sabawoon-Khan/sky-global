<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_equipment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_equipment_issue_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('returned_at');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_equipment_returns');
    }
};
