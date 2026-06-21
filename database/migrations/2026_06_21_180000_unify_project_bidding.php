<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('name');
            $table->string('source')->nullable()->after('scope_summary');
            $table->string('location')->nullable()->after('source');
            $table->string('security_scope')->nullable()->after('location');
            $table->date('published_at')->nullable()->after('security_scope');
            $table->date('submission_deadline')->nullable()->after('published_at');
            $table->timestamp('bid_submitted_at')->nullable()->after('submission_deadline');
            $table->decimal('our_bid_amount', 15, 2)->nullable()->after('total_contract_value');
            $table->text('loss_reason')->nullable()->after('our_bid_amount');
            $table->string('winning_competitor_name')->nullable()->after('loss_reason');
            $table->decimal('winning_amount', 15, 2)->nullable()->after('winning_competitor_name');
        });

        Schema::table('competitor_bids', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::create('project_bid_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('projects')
            ->where('status', 'planning')
            ->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::dropIfExists('project_bid_line_items');

        Schema::table('competitor_bids', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'reference_number',
                'source',
                'location',
                'security_scope',
                'published_at',
                'submission_deadline',
                'bid_submitted_at',
                'our_bid_amount',
                'loss_reason',
                'winning_competitor_name',
                'winning_amount',
            ]);
        });
    }
};
