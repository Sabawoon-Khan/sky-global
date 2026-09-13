<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('services')->nullable()->after('due_date');
            $table->date('period_start')->nullable()->after('services');
            $table->date('period_end')->nullable()->after('period_start');
            $table->text('notes')->nullable()->after('status');
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->unsignedInteger('days')->default(1)->after('unit_price');
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quote_number')->unique();
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->text('description_of_work')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_line_items');
        Schema::dropIfExists('quotations');

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropColumn('days');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['services', 'period_start', 'period_end', 'notes']);
        });
    }
};
