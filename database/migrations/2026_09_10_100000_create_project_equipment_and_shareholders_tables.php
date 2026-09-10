<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_catalog', function (Blueprint $table) {
            $table->string('category')->nullable()->after('sku');
            $table->string('unit')->default('pcs')->after('category');
        });

        Schema::create('project_equipment_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_catalog_id')->constrained('equipment_catalog')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('quantity_returned')->default(0);
            $table->date('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('project_shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('share_percent', 5, 2)->default(0);
            $table->decimal('invested_amount', 15, 2)->default(0);
            $table->decimal('returned_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('AFN');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('project_shareholder_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_shareholder_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('AFN');
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_shareholder_transactions');
        Schema::dropIfExists('project_shareholders');
        Schema::dropIfExists('project_equipment_issues');

        Schema::table('equipment_catalog', function (Blueprint $table) {
            $table->dropColumn(['category', 'unit']);
        });
    }
};
