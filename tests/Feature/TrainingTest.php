<?php

namespace Tests\Feature;

use App\Enums\TrainingPath;
use App\Enums\TrainingStatus;
use App\Models\Training\TrainingGuard;
use App\Models\Training\TrainingMinistryPayment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
    }

    public function test_owner_can_register_guard(): void
    {
        $this->actingAs($this->owner)
            ->post(route('training.guards.store'), [
                'name' => 'Ahmad',
                'father_name' => 'Karim',
                'grandfather_name' => 'Hassan',
                'tazkira_number' => '1234567890',
                'id_card_number' => 'ID-001',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'batch_number' => 'B-2026-01',
            ])
            ->assertRedirect();

        $guard = TrainingGuard::query()->first();
        $this->assertNotNull($guard);
        $this->assertSame(TrainingStatus::Registered->value, $guard->status);
        $this->assertSame('Ahmad', $guard->name);
    }

    public function test_owner_can_record_ministry_payment_and_assign_guards(): void
    {
        $guard = TrainingGuard::query()->create([
            'name' => 'Ahmad',
            'father_name' => 'Karim',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'batch_number' => 'B-2026-01',
            'status' => TrainingStatus::Registered->value,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->post(route('training.payments.store'), [
                'batch_number' => 'B-2026-01',
                'payment_date' => '2026-02-01',
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'guards' => [
                    [
                        'id' => $guard->id,
                        'fee_amount' => 1500,
                    ],
                ],
            ])
            ->assertRedirect();

        $guard->refresh();
        $payment = TrainingMinistryPayment::query()->first();

        $this->assertNotNull($payment);
        $this->assertSame(TrainingStatus::Ministry->value, $guard->status);
        $this->assertSame(TrainingPath::Ministry->value, $guard->training_path);
        $this->assertNull($guard->fee_number);
        $this->assertSame('1500.00', (string) $guard->fee_amount);
        $this->assertSame('1500.00', (string) $payment->total_amount);
        $this->assertSame('B-2026-01', $payment->batch_number);
    }

    public function test_owner_can_assign_company_training_and_issue_certificate(): void
    {
        $guard = TrainingGuard::query()->create([
            'name' => 'Noor',
            'father_name' => 'Ali',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'batch_number' => 'B-2026-02',
            'status' => TrainingStatus::Registered->value,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->post(route('training.guards.company', $guard), [
                'company_trainer' => 'Instructor Khan',
                'company_location' => 'Kabul HQ',
            ])
            ->assertRedirect();

        $guard->refresh();
        $this->assertSame(TrainingStatus::Company->value, $guard->status);

        $this->actingAs($this->owner)
            ->post(route('training.guards.complete', $guard))
            ->assertRedirect();

        $guard->refresh();
        $this->assertSame(TrainingStatus::Completed->value, $guard->status);

        $this->actingAs($this->owner)
            ->post(route('training.guards.certificate', $guard), [
                'certificate_issued_at' => '2026-04-01',
            ])
            ->assertRedirect();

        $guard->refresh();
        $this->assertSame(TrainingStatus::Certified->value, $guard->status);
        $this->assertNotNull($guard->certificate_number);
    }

    public function test_viewer_cannot_create_guard(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)
            ->post(route('training.guards.store'), [
                'name' => 'Blocked',
                'father_name' => 'Test',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'batch_number' => 'B-1',
            ])
            ->assertForbidden();
    }

    public function test_training_index_renders(): void
    {
        $this->actingAs($this->owner)
            ->get(route('training.guards.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/training/Guards/Index'));
    }
}
