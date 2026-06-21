<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source')->nullable();
            $table->date('published_at')->nullable();
            $table->date('submission_deadline')->nullable();
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('security_scope')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('duration_months')->nullable();
            $table->string('status')->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('bid_number')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('our_total_amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('loss_reason')->nullable();
            $table->string('winning_competitor_name')->nullable();
            $table->decimal('winning_amount', 15, 2)->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bid_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('cost_basis', 15, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('bid_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('file_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('competitor_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('competitor_name');
            $table->decimal('bid_amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_winner')->default(false);
            $table->boolean('is_estimated')->default(false);
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bid_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid_status_histories');
        Schema::dropIfExists('competitor_bids');
        Schema::dropIfExists('bid_documents');
        Schema::dropIfExists('bid_line_items');
        Schema::dropIfExists('bids');
        Schema::dropIfExists('procurement_opportunities');
    }
};
