<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->can('settings.view_login_logs')
                || $request->user()?->can('settings.manage_users'),
            403,
        );

        $search = $request->string('search')->trim()->toString();
        $event = $request->string('event')->trim()->toString();
        $subjectType = $request->string('subject_type')->trim()->toString();
        $dateFrom = $request->filled('date_from') ? $request->date('date_from')?->toDateString() : null;
        $dateTo = $request->filled('date_to') ? $request->date('date_to')?->toDateString() : null;

        $logs = Activity::query()
            ->with(['causer:id,name,email', 'subject'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('subject_type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('subject_id', $search)
                        ->orWhereHas('causer', function ($causerQuery) use ($search) {
                            $causerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($subjectType !== '', fn ($query) => $query->where('subject_type', $subjectType))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Activity $activity) => $this->present($activity));

        $subjectTypes = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => $this->subjectTypeLabel($type),
            ])
            ->values();

        return Inertia::render('settings/ActivityLogs/Index', [
            'logs' => $logs,
            'events' => ['created', 'updated', 'deleted', 'restored'],
            'subjectTypes' => $subjectTypes,
            'filters' => [
                'search' => $search ?: null,
                'event' => $event ?: null,
                'subject_type' => $subjectType ?: null,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function present(Activity $activity): array
    {
        $subject = $activity->subject;

        return [
            'id' => $activity->id,
            'event' => $activity->event,
            'subject_type' => $this->subjectTypeLabel($activity->subject_type),
            'subject_id' => $activity->subject_id,
            'subject_label' => $this->subjectLabel($subject, $activity->subject_type, $activity->subject_id),
            'causer' => $activity->causer
                ? [
                    'id' => $activity->causer->getKey(),
                    'name' => $activity->causer->getAttribute('name'),
                    'email' => $activity->causer->getAttribute('email'),
                ]
                : null,
            'changes' => $this->presentChanges($activity->attribute_changes),
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    private function subjectLabel(mixed $subject, ?string $type, mixed $id): string
    {
        if ($subject instanceof Model && method_exists($subject, 'activitySubjectLabel')) {
            return $subject->activitySubjectLabel();
        }

        $base = $this->subjectTypeLabel($type);

        return $id ? "{$base} #{$id}" : $base;
    }

    private function subjectTypeLabel(?string $type): string
    {
        if (! $type) {
            return 'Record';
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        return class_basename($class) ?: 'Record';
    }

    /** @return list<array{field: string, old: string|null, new: string|null}> */
    private function presentChanges(mixed $changes): array
    {
        $data = $changes instanceof Collection ? $changes->toArray() : (array) $changes;
        $attributes = is_array($data['attributes'] ?? null) ? $data['attributes'] : [];
        $old = is_array($data['old'] ?? null) ? $data['old'] : [];
        $keys = array_values(array_unique([...array_keys($attributes), ...array_keys($old)]));
        $rows = [];

        foreach ($keys as $field) {
            if (! is_string($field) || $field === '') {
                continue;
            }

            $rows[] = [
                'field' => $field,
                'old' => $this->stringify($old[$field] ?? null),
                'new' => $this->stringify($attributes[$field] ?? null),
            ];
        }

        return $rows;
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: null;
    }
}
