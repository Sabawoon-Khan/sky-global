<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contract_number')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->text('scope_summary')->nullable();
            $table->decimal('total_contract_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('planning');
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('bids', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        Schema::create('project_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('client_requirements')->nullable();
            $table->longText('risk_notes')->nullable();
            $table->longText('special_instructions')->nullable();
            $table->unsignedInteger('guards_required')->nullable();
            $table->unsignedInteger('supervisors_required')->nullable();
            $table->text('shift_details')->nullable();
            $table->text('equipment_requirements')->nullable();
            $table->text('training_requirements')->nullable();
            $table->string('client_contact_on_site')->nullable();
            $table->string('reporting_frequency')->nullable();
            $table->longText('internal_notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });

        Schema::create('project_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('province')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id', 'role']);
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('category')->nullable();
            $table->string('direction')->default('internal');
            $table->string('title');
            $table->string('file_path');
            $table->date('document_date')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('activity_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->nullableMorphs('causer');
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });

        Schema::create('project_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->string('category')->default('other');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('project_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('personnel_type');
            $table->unsignedBigInteger('personnel_id');
            $table->string('role')->default('guard');
            $table->string('shift_pattern')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('monthly_rate', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->index(['personnel_type', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_deployments');
        Schema::dropIfExists('project_status_histories');
        Schema::dropIfExists('project_issues');
        Schema::dropIfExists('project_activities');
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_sites');
        Schema::dropIfExists('project_details');
        Schema::table('bids', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
        Schema::dropIfExists('projects');
    }
};
