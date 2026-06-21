<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('archived_documents', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('direction');
            $table->foreignId('document_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bid_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->date('document_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('replaces_id')->nullable()->constrained('archived_documents')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('archived_document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archived_document_id')->constrained()->cascadeOnDelete();
            $table->morphs('linkable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_document_links');
        Schema::dropIfExists('archived_documents');
        Schema::dropIfExists('document_categories');
    }
};
