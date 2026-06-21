<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('equipment_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_catalog_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->timestamps();
        });

        Schema::create('personnel_equipment_issues', function (Blueprint $table) {
            $table->id();
            $table->string('personnel_type');
            $table->unsignedBigInteger('personnel_id');
            $table->foreignId('equipment_catalog_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_type', 'personnel_id']);
        });

        Schema::create('personnel_equipment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_equipment_issue_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('returned_at');
            $table->string('condition')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('training_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('validity_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_catalog_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->date('session_date');
            $table->string('location')->nullable();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('personnel_trainings', function (Blueprint $table) {
            $table->id();
            $table->string('personnel_type');
            $table->unsignedBigInteger('personnel_id');
            $table->foreignId('training_catalog_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_session_id')->nullable()->constrained()->nullOnDelete();
            $table->date('completed_at');
            $table->date('expires_at')->nullable();
            $table->string('certificate_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_type', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_trainings');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_catalog');
        Schema::dropIfExists('personnel_equipment_returns');
        Schema::dropIfExists('personnel_equipment_issues');
        Schema::dropIfExists('equipment_stock');
        Schema::dropIfExists('equipment_catalog');
    }
};
