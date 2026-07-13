<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_deployments', function (Blueprint $table) {
            $table->string('status')->default('active')->after('shift_pattern');
        });

        DB::table('project_deployments')->update(['currency' => 'AFN']);
    }

    public function down(): void
    {
        Schema::table('project_deployments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
