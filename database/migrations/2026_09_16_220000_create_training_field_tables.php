<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_field_guards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('father_name');
            $table->string('grandfather_name')->nullable();
            $table->string('tazkira_number')->nullable();
            $table->string('id_card_number')->nullable();
            $table->string('site')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('tazkira_number');
            $table->index('id_card_number');
        });

        Schema::create('training_field_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->date('report_date');
            $table->text('description');
            $table->string('trainer_name')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('report_date');
        });

        Schema::create('training_field_report_guard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_field_report_id')
                ->constrained('training_field_reports')
                ->cascadeOnDelete();
            $table->foreignId('training_field_guard_id')
                ->constrained('training_field_guards')
                ->cascadeOnDelete();

            $table->unique(
                ['training_field_report_id', 'training_field_guard_id'],
                'training_field_report_guard_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_field_report_guard');
        Schema::dropIfExists('training_field_reports');
        Schema::dropIfExists('training_field_guards');
    }
};
