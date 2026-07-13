<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')->update(['currency' => 'AFN']);
        DB::table('competitor_bids')->update(['currency' => 'AFN']);
        DB::table('project_incomes')->update(['currency' => 'AFN']);
        DB::table('project_expenses')->update(['currency' => 'AFN']);
        DB::table('project_deployments')->update(['currency' => 'AFN']);
    }

    public function down(): void
    {
        // No rollback — AFN is the operational currency for projects.
    }
};
