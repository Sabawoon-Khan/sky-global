<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const LABEL_TO_SLUG = [
        'static guarding' => 'static',
        'static guards' => 'static',
        'perimeter security' => 'static',
        'mobile patrol' => 'mobile',
        'vip' => 'vip',
        'event' => 'event',
    ];

    public function up(): void
    {
        DB::table('projects')
            ->whereNotNull('security_scope')
            ->orderBy('id')
            ->each(function ($project): void {
                $decoded = json_decode((string) $project->security_scope, true);

                if (is_array($decoded)) {
                    return;
                }

                $scope = trim((string) $project->security_scope);

                if ($scope === '') {
                    DB::table('projects')->where('id', $project->id)->update(['security_scope' => null]);

                    return;
                }

                $slug = self::LABEL_TO_SLUG[strtolower($scope)] ?? $scope;
                $values = in_array($slug, ['static', 'mobile', 'vip', 'event'], true)
                    ? [$slug]
                    : [$scope];

                DB::table('projects')->where('id', $project->id)->update([
                    'security_scope' => json_encode(array_values(array_unique($values))),
                ]);
            });

        Schema::table('projects', function (Blueprint $table): void {
            $table->json('security_scope')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('projects')
            ->whereNotNull('security_scope')
            ->orderBy('id')
            ->each(function ($project): void {
                $decoded = json_decode((string) $project->security_scope, true);

                if (! is_array($decoded)) {
                    return;
                }

                DB::table('projects')->where('id', $project->id)->update([
                    'security_scope' => $decoded[0] ?? null,
                ]);
            });

        Schema::table('projects', function (Blueprint $table): void {
            $table->string('security_scope')->nullable()->change();
        });
    }
};
