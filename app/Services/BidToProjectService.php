<?php

namespace App\Services;

use App\Enums\BidStatus;
use App\Enums\ProjectActivityType;
use App\Enums\ProjectStatus;
use App\Models\Procurement\Bid;
use App\Models\Project\Project;
use App\Models\Project\ProjectDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BidToProjectService
{
    public function promote(Bid $bid): Project
    {
        return DB::transaction(function () use ($bid) {
            $bid->load('procurementOpportunity.organization');

            $opportunity = $bid->procurementOpportunity;

            $project = Project::query()->create([
                'bid_id' => $bid->id,
                'organization_id' => $opportunity->organization_id,
                'code' => $this->generateProjectCode(),
                'name' => $opportunity->title,
                'scope_summary' => $opportunity->description,
                'total_contract_value' => $bid->our_total_amount,
                'currency' => $bid->currency,
                'status' => ProjectStatus::Draft->value,
                'project_manager_id' => $bid->created_by,
                'won_at' => now(),
                'created_by' => auth()->id(),
            ]);

            ProjectDetail::query()->create(['project_id' => $project->id]);

            $bid->update([
                'status' => BidStatus::Won->value,
                'project_id' => $project->id,
            ]);

            ProjectActivityLogger::log(
                $project,
                ProjectActivityType::ProjectCreated,
                'Project created from won bid',
                "Bid {$bid->bid_number} was won and converted to project {$project->code}.",
                ['bid_id' => $bid->id],
            );

            return $project->fresh(['organization', 'detail', 'bid']);
        });
    }

    private function generateProjectCode(): string
    {
        do {
            $code = 'GS-'.now()->format('Y').'-'.Str::upper(Str::random(4));
        } while (Project::query()->where('code', $code)->exists());

        return $code;
    }
}
