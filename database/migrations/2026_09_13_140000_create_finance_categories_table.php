<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('applies_to')->default('both');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['name', 'applies_to']);
        });

        Schema::table('project_incomes', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
        });

        Schema::table('project_expenses', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
        });

        $incomeNames = DB::table('general_incomes')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $rows = [];
        $sort = 1;

        foreach (['Grant', 'Investment', 'Client payment'] as $name) {
            $rows[] = [
                'name' => $name,
                'applies_to' => 'income',
                'is_active' => true,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (['Salary', 'Equipment', 'Transport', 'Utilities'] as $name) {
            $rows[] = [
                'name' => $name,
                'applies_to' => 'expense',
                'is_active' => true,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $existing = collect($rows)->pluck('name')->map(fn (string $name) => mb_strtolower($name));

        foreach ($incomeNames as $name) {
            if ($existing->contains(mb_strtolower((string) $name))) {
                continue;
            }

            $rows[] = [
                'name' => (string) $name,
                'applies_to' => 'income',
                'is_active' => true,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $existing->push(mb_strtolower((string) $name));
        }

        if ($rows !== []) {
            DB::table('finance_categories')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::table('project_expenses', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('project_incomes', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::dropIfExists('finance_categories');
    }
};
