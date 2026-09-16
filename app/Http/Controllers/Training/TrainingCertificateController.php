<?php

namespace App\Http\Controllers\Training;

use App\Enums\TrainingStatus;
use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Training\TrainingGuard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingCertificateController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'training.view');

        $filters = $this->listFilters(
            $request,
            [TrainingStatus::Completed->value, TrainingStatus::Certified->value],
            ['batch_number'],
        );

        $query = TrainingGuard::query()
            ->whereIn('status', [TrainingStatus::Completed->value, TrainingStatus::Certified->value]);

        $this->applyListFilters($query, $filters, [
            'date_column' => 'certificate_issued_at',
            'search_columns' => [
                'name',
                'father_name',
                'tazkira_number',
                'id_card_number',
                'batch_number',
                'certificate_number',
            ],
        ]);
        $query->when($filters['batch_number'] ?? null, fn ($q, string $batch) => $q->where('batch_number', $batch));

        $ready = TrainingGuard::query()
            ->whereIn('status', TrainingStatus::certifiable())
            ->count();
        $certified = TrainingGuard::query()
            ->where('status', TrainingStatus::Certified->value)
            ->count();

        return Inertia::render('mis/training/Certificates/Index', [
            'guards' => (clone $query)
                ->orderByRaw("case when status = 'certified' then 0 else 1 end")
                ->latest('certificate_issued_at')
                ->paginate(15)
                ->withQueryString(),
            'stats' => [
                'certified' => $certified,
                'ready' => $ready,
                'completed' => TrainingGuard::query()->where('status', TrainingStatus::Completed->value)->count(),
            ],
            'filters' => $filters,
        ]);
    }
}
