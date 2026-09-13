<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceTaxTest extends TestCase
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

    public function test_owner_can_view_tax_tab(): void
    {
        $this->actingAs($this->owner)
            ->get(route('finance.tax'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/Tax')
                ->has('tax')
                ->where('tax.quarterly_rate_percent', 4)
                ->where('tax.yearly_rate_percent', 10)
                ->has('tax.daily')
                ->has('tax.weekly')
                ->has('tax.monthly')
                ->has('tax.yearly')
                ->has('tax.quarters')
                ->has('payments')
            );
    }

    public function test_owner_can_print_tax_report(): void
    {
        $this->actingAs($this->owner)
            ->get(route('finance.tax.print', ['period' => 'weekly']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/TaxPrint')
                ->where('period', 'weekly')
                ->has('rows')
                ->has('totals')
            );
    }

    public function test_tax_payment_requires_documents(): void
    {
        $this->actingAs($this->owner)
            ->post(route('finance.tax.payments.store'), [
                'period_type' => 'yearly',
                'year' => now()->year,
                'amount' => 1000,
                'payment_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('documents');
    }

    public function test_owner_can_record_tax_payment_with_documents(): void
    {
        Storage::fake('local');

        $this->actingAs($this->owner)
            ->post(route('finance.tax.payments.store'), [
                'period_type' => 'quarterly',
                'year' => now()->year,
                'quarter' => 1,
                'their_amount' => 1250.25,
                'company_amount' => 1250.25,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'bank_transfer',
                'reference_number' => 'TAX-001',
                'documents' => [
                    UploadedFile::fake()->create('tax-receipt.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tax_payments', [
            'period_type' => 'quarterly',
            'year' => now()->year,
            'quarter' => 1,
            'amount' => 2500.50,
            'their_amount' => 1250.25,
            'company_amount' => 1250.25,
            'reference_number' => 'TAX-001',
        ]);

        $this->assertDatabaseCount('attachments', 1);
    }
}
