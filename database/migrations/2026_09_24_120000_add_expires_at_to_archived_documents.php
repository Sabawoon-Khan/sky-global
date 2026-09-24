<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archived_documents', function (Blueprint $table) {
            $table->date('expires_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('archived_documents', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
