<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_is_redirected_from_system_report(): void
    {
        $this->get(route('analytics.reports'))
            ->assertRedirect(route('login'));
    }

    public function test_finance_viewer_sees_finance_section(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('finance.view');

        $this->actingAs($user)
            ->get(route('analytics.reports'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/analytics/Reports')
                ->has('sections', 1)
                ->where('sections.0.key', 'finance')
                ->has('sections.0.metrics')
                ->has('year')
                ->has('company.name'));
    }

    public function test_hr_viewer_does_not_see_finance_section(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('hr.view');

        $this->actingAs($user)
            ->get(route('analytics.reports'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/analytics/Reports')
                ->has('sections', 1)
                ->where('sections.0.key', 'hr'));
    }

    public function test_reports_print_can_filter_to_single_module(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['finance.view', 'hr.view']);

        $this->actingAs($user)
            ->get(route('analytics.reports.print', ['module' => 'finance', 'year' => now()->year]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/analytics/ReportsPrint')
                ->has('sections', 1)
                ->where('sections.0.key', 'finance'));
    }

    public function test_reports_print_includes_all_permitted_modules(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['finance.view', 'hr.view']);

        $this->actingAs($user)
            ->get(route('analytics.reports.print', ['year' => now()->year]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/analytics/ReportsPrint')
                ->has('sections', 2));
    }
}
