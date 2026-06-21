<?php

namespace App\Services;

use App\Enums\ProjectActivityType;
use App\Models\Project\Project;
use App\Models\Project\ProjectActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(
        Project $project,
        ProjectActivityType $type,
        string $title,
        ?string $description = null,
        ?array $metadata = null,
        ?Model $causer = null,
    ): ProjectActivity {
        $user = $causer instanceof User ? $causer : auth()->user();

        return $project->activities()->create([
            'activity_type' => $type->value,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'causer_type' => $user ? $user::class : null,
            'causer_id' => $user?->id,
        ]);
    }
}
