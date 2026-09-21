<?php

namespace Tests\Feature;

use App\Models\Finance\Invoice;
use App\Models\Finance\Quotation;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\User;
use App\Support\NumberToWords;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceInvoiceQuotationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');

        $this->organization = Organization::query()->create([
            'organization_type_id' => OrganizationType::query()->create([
                'name' => 'Client',
                'slug' => 'client',
            ])->id,
            'name' => 'Darya Village Hotel Services',
            'address' => 'Kabul, Afghanistan',
            'email' => 'client@example.com',
            'phone' => '+93799111911',
            'is_active' => true,
        ]);
    }

    public function test_invoice_numbers_are_generated_by_the_system(): void
    {
        $this->actingAs($this->owner)
            ->post(route('finance.invoices.store'), [
                'invoice_number' => 'INV-MANUAL',
                'issue_date' => '2026-08-31',
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
                'services' => 'Static Security Guard',
                'currency' => 'USD',
                'status' => 'sent',
                'organization_id' => $this->organization->id,
                'line_items' => [
                    [
                        'description' => 'Site Security Manager',
                        'quantity' => 1,
                        'unit_price' => 400,
                        'days' => 31,
                    ],
                    [
                        'description' => 'Guard Supervisor',
                        'quantity' => 3,
                        'unit_price' => 275,
                        'days' => 31,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->post(route('finance.invoices.store'), [
                'issue_date' => '2026-08-31',
                'currency' => 'USD',
                'organization_id' => $this->organization->id,
                'line_items' => [
                    [
                        'description' => 'Static Armed Guards',
                        'quantity' => 6,
                        'unit_price' => 245,
                        'days' => 31,
                    ],
                ],
            ])
            ->assertRedirect();

        $invoices = Invoice::query()->orderBy('id')->get();
        $year = now()->year;

        $this->assertCount(2, $invoices);
        $this->assertSame('INV-MANUAL', $invoices[0]->invoice_number);
        $this->assertSame("SSGSC-{$year}-01012", $invoices[1]->invoice_number);
        $this->assertSame(37975.0, (float) $invoices[0]->subtotal);
        $this->assertSame(37975.0, (float) $invoices[0]->total);
        $this->assertCount(2, $invoices[0]->lineItems);
        $this->assertSame(31, (int) $invoices[0]->lineItems->first()->days);
    }

    public function test_invoice_create_form_includes_next_invoice_number(): void
    {
        $year = now()->year;

        $this->actingAs($this->owner)
            ->get(route('finance.invoices'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/Invoices/Index')
                ->where('next_invoice_number', "SSGSC-{$year}-01012")
            );
    }

    public function test_invoice_print_page_uses_company_template(): void
    {
        $this->actingAs($this->owner)
            ->post(route('finance.invoices.store'), [
                'issue_date' => '2026-08-31',
                'currency' => 'USD',
                'organization_id' => $this->organization->id,
                'line_items' => [
                    [
                        'description' => 'Site Security Manager',
                        'quantity' => 1,
                        'unit_price' => 400,
                        'days' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $invoice = Invoice::query()->first();

        $this->actingAs($this->owner)
            ->get(route('finance.invoices.print', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/InvoicePrint')
                ->where('invoice.invoice_number', $invoice->invoice_number)
                ->where('invoice.organization.name', 'Darya Village Hotel Services')
                ->has('company.name')
                ->has('company.bank.usd.account')
                ->where('amount_in_words', NumberToWords::money(400, 'USD'))
            );
    }

    public function test_quotation_numbers_are_generated_and_printable(): void
    {
        $this->actingAs($this->owner)
            ->get(route('finance.quotations'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/Quotations/Index')
                ->where('next_quote_number', 'SSGSC-001')
            );

        $this->actingAs($this->owner)
            ->post(route('finance.quotations.store'), [
                'quote_date' => '2026-08-23',
                'valid_until' => '2026-09-15',
                'description_of_work' => 'Armed Security Guards and armored vehicle.',
                'currency' => 'USD',
                'tax' => 156,
                'organization_id' => $this->organization->id,
                'line_items' => [
                    [
                        'description' => 'Armed Security Guard CPU',
                        'quantity' => 1,
                        'unit_price' => 600,
                    ],
                    [
                        'description' => 'Driver',
                        'quantity' => 1,
                        'unit_price' => 400,
                    ],
                    [
                        'description' => 'Armored Land Cruiser',
                        'quantity' => 1,
                        'unit_price' => 2900,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->post(route('finance.quotations.store'), [
                'quote_date' => '2026-08-24',
                'currency' => 'USD',
                'line_items' => [
                    [
                        'description' => 'Static Unarmed Guards',
                        'quantity' => 2,
                        'unit_price' => 190,
                    ],
                ],
            ])
            ->assertRedirect();

        $quotations = Quotation::query()->orderBy('id')->get();

        $this->assertCount(2, $quotations);
        $this->assertSame('SSGSC-001', $quotations[0]->quote_number);
        $this->assertSame('SSGSC-002', $quotations[1]->quote_number);
        $this->assertSame(3900.0, (float) $quotations[0]->subtotal);
        $this->assertSame(156.0, (float) $quotations[0]->tax);
        $this->assertSame(4056.0, (float) $quotations[0]->total);

        $this->actingAs($this->owner)
            ->get(route('finance.quotations.print', $quotations[0]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/QuotationPrint')
                ->where('quotation.quote_number', 'SSGSC-001')
                ->where('quotation.organization.name', 'Darya Village Hotel Services')
                ->has('company.phone_alt')
            );
    }
}
