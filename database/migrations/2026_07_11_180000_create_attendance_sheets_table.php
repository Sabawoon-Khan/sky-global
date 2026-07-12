<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('attendance_type')->default('general');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date_from', 'date_to']);
            $table->index(['attendance_type', 'project_id']);
        });

        Schema::table('personnel_attendances', function (Blueprint $table) {
            $table->foreignId('attendance_sheet_id')
                ->nullable()
                ->after('id')
                ->constrained('attendance_sheets')
                ->nullOnDelete();
        });

        $groups = DB::table('personnel_attendances')
            ->select('year', 'month', 'project_id')
            ->distinct()
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        foreach ($groups as $group) {
            $periodStart = Carbon::create((int) $group->year, (int) $group->month, 1);
            $periodEnd = $periodStart->copy()->endOfMonth();
            $isProject = $group->project_id !== null;

            $sheetId = DB::table('attendance_sheets')->insertGetId([
                'title' => $isProject
                    ? "Project Attendance - {$periodStart->format('F Y')}"
                    : "General Attendance - {$periodStart->format('F Y')}",
                'attendance_type' => $isProject ? 'project' : 'general',
                'project_id' => $group->project_id,
                'date_from' => $periodStart->toDateString(),
                'date_to' => $periodEnd->toDateString(),
                'year' => (int) $group->year,
                'month' => (int) $group->month,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('personnel_attendances')
                ->where('year', $group->year)
                ->where('month', $group->month)
                ->when(
                    $group->project_id === null,
                    fn ($query) => $query->whereNull('project_id'),
                    fn ($query) => $query->where('project_id', $group->project_id),
                )
                ->update(['attendance_sheet_id' => $sheetId]);
        }

        Schema::table('personnel_attendances', function (Blueprint $table) {
            $table->dropUnique('personnel_attendance_unique');
            $table->unique(
                ['attendance_sheet_id', 'personnel_type', 'personnel_id'],
                'personnel_attendance_sheet_personnel_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('personnel_attendances', function (Blueprint $table) {
            $table->dropUnique('personnel_attendance_sheet_personnel_unique');
            $table->unique(
                ['personnel_type', 'personnel_id', 'project_id', 'year', 'month'],
                'personnel_attendance_unique',
            );
            $table->dropConstrainedForeignId('attendance_sheet_id');
        });

        Schema::dropIfExists('attendance_sheets');
    }
};
