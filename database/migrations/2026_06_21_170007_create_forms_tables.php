<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('context');
            $table->boolean('is_required_on_registration')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('name');
            $table->string('field_type');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_template_id', 'name']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->restrictOnDelete();
            $table->morphs('submittable');
            $table->string('status')->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained()->cascadeOnDelete();
            $table->text('value_text')->nullable();
            $table->string('value_file_path')->nullable();
            $table->timestamps();

            $table->unique(['form_submission_id', 'form_field_id']);
        });

        Schema::create('attachment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('requires_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('personnel_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('personnel_type');
            $table->unsignedBigInteger('personnel_id');
            $table->foreignId('attachment_type_id')->constrained()->restrictOnDelete();
            $table->string('file_path');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['personnel_type', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_attachments');
        Schema::dropIfExists('attachment_types');
        Schema::dropIfExists('form_submission_values');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('form_templates');
    }
};
