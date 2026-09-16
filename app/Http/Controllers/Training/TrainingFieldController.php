<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Concerns\AppliesListFilters;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Training\TrainingFieldGuard;
use App\Models\Training\TrainingFieldReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingFieldController extends Controller
{
    use AppliesListFilters, AuthorizesMisPermissions;

    public function roster(Request $request): Response
    {
        $this->authorizePermission($request, 'training.view');

        $filters = $this->listFilters($request);

        $query = TrainingFieldGuard::query();
        $this->applyListFilters($query, $filters, [
            'search_columns' => [
                'name',
                'father_name',
                'grandfather_name',
                'tazkira_number',
                'id_card_number',
                'site',
            ],
        ]);

        $roster = (clone $query)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('mis/training/Field/Roster/Index', [
            'roster' => $roster,
            'stats' => [
                'roster' => TrainingFieldGuard::query()->count(),
                'reports' => TrainingFieldReport::query()->count(),
            ],
            'filters' => $filters,
        ]);
    }
}
