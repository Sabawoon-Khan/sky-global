<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assignments')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('assignments', 'assigned_on')) {
                $table->date('assigned_on')->nullable()->after('description');
            }

            if (! Schema::hasColumn('assignments', 'reply_by')) {
                $table->date('reply_by')->nullable()->after('assigned_on');
            }
        });

        if (Schema::hasColumn('assignments', 'deadline')) {
            DB::table('assignments')
                ->whereNull('reply_by')
                ->whereNotNull('deadline')
                ->update(['reply_by' => DB::raw('deadline')]);

            Schema::table('assignments', function (Blueprint $table) {
                $table->dropColumn('deadline');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('assignments')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('assignments', 'deadline')) {
                $table->date('deadline')->nullable()->after('description');
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if (Schema::hasColumn('assignments', 'assigned_on')) {
                $table->dropColumn('assigned_on');
            }

            if (Schema::hasColumn('assignments', 'reply_by')) {
                $table->dropColumn('reply_by');
            }
        });
    }
};
