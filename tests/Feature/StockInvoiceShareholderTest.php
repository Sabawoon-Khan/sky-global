<?php

namespace Tests\Feature;

use App\Models\Equipment\EquipmentCatalog;
use App\Models\Equipment\EquipmentStock;
use App\Models\Equipment\ProjectEquipmentIssue;
use App\Models\Finance\Invoice;
use App\Models\Hr\AttendanceSheet;
use App\Models\Hr\Employee;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project\Project;
use App\Models\Project\ProjectShareholder;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInvoiceShareholderTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');

        $organization = Organization::query()->create([
            'organization_type_id' => OrganizationType::query()->create([
                'name' => 'Gov',
                'slug' => 'gov',
            ])->id,
            'name' => 'Test Client',
        ]);

        $this->project = Project::query()->create([
            'organization_id' => $organization->id,
            'code' => 'GS-2026-STOCK',
            'name' => 'Stock Test Project',
            'currency' => 'AFN',
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_attendance_sheet_can_be_approved(): void
    {
        $employee = Employee::query()->create([
            'first_name' => 'Ahmad',
            'last_name' => 'Khan',
            'status' => 'active',
            'is_permanent' => true,
        ]);

        $sheet = AttendanceSheet::query()->create([
            'title' => 'March Attendance',
            'attendance_type' => 'general',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'year' => 2026,
            'month' => 3,
            'created_by' => $this->owner->id,
        ]);

        PersonnelAttendance::query()->create([
            'personnel_type' => Employee::class,
            'personnel_id' => $employee->id,
            'attendance_sheet_id' => $sheet->id,
            'year' => 2026,
            'month' => 3,
            'days_present' => 20,
            'days_absent' => 0,
            'days_leave' => 0,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->owner)
            ->post(route('hr.attendance.sheets.approve', $sheet))
            ->assertRedirect();

        $this->assertDatabaseHas('personnel_attendances', [
            'attendance_sheet_id' => $sheet->id,
            'status' => 'approved',
            'approved_by' => $this->owner->id,
        ]);
    }

    public function test_finance_index_and_invoice_create_work(): void
    {
        $this->actingAs($this->owner)
            ->get(route('finance.index'))
            ->assertOk();

        $this->actingAs($this->owner)
            ->get(route('finance.invoices'))
            ->assertOk();

        $this->actingAs($this->owner)
            ->post(route('finance.invoices.store'), [
                'issue_date' => '2026-03-01',
                'due_date' => '2026-03-15',
                'subtotal' => 1000,
                'tax' => 50,
                'total' => 1050,
                'currency' => 'AFN',
                'status' => 'draft',
                'organization_id' => $this->project->organization_id,
                'project_id' => $this->project->id,
            ])
            ->assertRedirect();

        $invoice = Invoice::query()->first();

        $this->assertNotNull($invoice);
        $this->assertMatchesRegularExpression('/^SSGSC-\d{4}-\d{5}$/', $invoice->invoice_number);
        $this->assertEquals(1050, $invoice->total);
        $this->assertSame('draft', $invoice->status);

        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_stock_item_can_be_created_and_issued_to_project(): void
    {
        $this->actingAs($this->owner)
            ->get(route('equipment.index'))
            ->assertOk();

        $this->actingAs($this->owner)
            ->post(route('equipment.store'), [
                'name' => 'AK-47 Rifle',
                'sku' => 'GUN-AK47-TEST',
                'category' => 'Weapons',
                'unit' => 'pcs',
                'initial_quantity' => 10,
                'is_active' => true,
            ])
            ->assertRedirect();

        $catalog = EquipmentCatalog::query()->where('sku', 'GUN-AK47-TEST')->firstOrFail();

        $this->assertDatabaseHas('equipment_stock', [
            'equipment_catalog_id' => $catalog->id,
            'quantity_on_hand' => 10,
        ]);

        $this->actingAs($this->owner)
            ->post(route('projects.equipment-issues.store', $this->project), [
                'equipment_catalog_id' => $catalog->id,
                'quantity' => 3,
                'issued_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_equipment_issues', [
            'project_id' => $this->project->id,
            'equipment_catalog_id' => $catalog->id,
            'quantity' => 3,
        ]);

        $this->assertSame(7, (int) EquipmentStock::query()
            ->where('equipment_catalog_id', $catalog->id)
            ->value('quantity_on_hand'));

        $issue = ProjectEquipmentIssue::query()->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('projects.equipment-issues.return', [$this->project, $issue]), [
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(1, (int) $issue->fresh()->quantity_returned);
        $this->assertSame(8, (int) EquipmentStock::query()
            ->where('equipment_catalog_id', $catalog->id)
            ->value('quantity_on_hand'));
    }

    public function test_shareholder_capital_and_return_flow(): void
    {
        $this->actingAs($this->owner)
            ->get(route('projects.show', $this->project))
            ->assertOk();

        $this->actingAs($this->owner)
            ->post(route('projects.shareholders.store', $this->project), [
                'name' => 'Karim Investor',
                'share_percent' => 40,
                'invested_amount' => 100000,
                'currency' => 'AFN',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $shareholder = ProjectShareholder::query()->firstOrFail();

        $this->assertSame(100000.0, (float) $shareholder->invested_amount);

        $this->actingAs($this->owner)
            ->post(route('projects.shareholders.contribute', [$this->project, $shareholder]), [
                'amount' => 25000,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame(125000.0, (float) $shareholder->fresh()->invested_amount);

        $this->actingAs($this->owner)
            ->post(route('projects.shareholders.distribute', [$this->project, $shareholder]), [
                'amount' => 40000,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $shareholder->refresh();

        $this->assertSame(40000.0, (float) $shareholder->returned_amount);
        $this->assertSame(85000.0, $shareholder->outstandingAmount());
    }

    public function test_stock_item_can_be_deleted(): void
    {
        $this->actingAs($this->owner)
            ->post(route('equipment.store'), [
                'name' => 'Radio Set',
                'sku' => 'RADIO-DEL-TEST',
                'category' => 'Radios',
                'unit' => 'pcs',
                'initial_quantity' => 4,
                'is_active' => true,
            ])
            ->assertRedirect();

        $catalog = EquipmentCatalog::query()->where('sku', 'RADIO-DEL-TEST')->firstOrFail();

        $this->actingAs($this->owner)
            ->from(route('equipment.index'))
            ->delete(route('equipment.destroy', $catalog))
            ->assertRedirect();

        $this->assertDatabaseMissing('equipment_catalog', ['id' => $catalog->id]);
        $this->assertDatabaseMissing('equipment_stock', ['equipment_catalog_id' => $catalog->id]);
    }

    public function test_stock_item_can_be_updated(): void
    {
        $this->actingAs($this->owner)
            ->post(route('equipment.store'), [
                'name' => 'Old Radio',
                'sku' => 'RADIO-EDIT-TEST',
                'category' => 'Radios',
                'unit' => 'pcs',
                'initial_quantity' => 2,
                'is_active' => true,
            ])
            ->assertRedirect();

        $catalog = EquipmentCatalog::query()->where('sku', 'RADIO-EDIT-TEST')->firstOrFail();

        $this->actingAs($this->owner)
            ->from(route('equipment.index'))
            ->put(route('equipment.update', $catalog), [
                'name' => 'Updated Radio',
                'sku' => 'RADIO-EDIT-TEST',
                'category' => 'Communications',
                'unit' => 'set',
                'description' => 'Field radio set',
                'is_active' => '0',
            ])
            ->assertRedirect();

        $catalog->refresh();

        $this->assertSame('Updated Radio', $catalog->name);
        $this->assertSame('Communications', $catalog->category);
        $this->assertSame('set', $catalog->unit);
        $this->assertSame('Field radio set', $catalog->description);
        $this->assertFalse($catalog->is_active);
    }

    public function test_issued_stock_item_cannot_be_deleted(): void
    {
        $this->actingAs($this->owner)
            ->post(route('equipment.store'), [
                'name' => 'AK-47 Rifle',
                'sku' => 'GUN-AK47-KEEP',
                'category' => 'Weapons',
                'unit' => 'pcs',
                'initial_quantity' => 10,
                'is_active' => true,
            ])
            ->assertRedirect();

        $catalog = EquipmentCatalog::query()->where('sku', 'GUN-AK47-KEEP')->firstOrFail();

        $this->actingAs($this->owner)
            ->post(route('projects.equipment-issues.store', $this->project), [
                'equipment_catalog_id' => $catalog->id,
                'quantity' => 2,
                'issued_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->from(route('equipment.index'))
            ->delete(route('equipment.destroy', $catalog))
            ->assertRedirect()
            ->assertSessionHasErrors('equipment');

        $this->assertDatabaseHas('equipment_catalog', ['id' => $catalog->id]);
    }

    public function test_staff_cannot_delete_stock_item(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('Staff');

        $this->actingAs($this->owner)
            ->post(route('equipment.store'), [
                'name' => 'Helmet',
                'sku' => 'HELM-DEL-TEST',
                'initial_quantity' => 1,
            ])
            ->assertRedirect();

        $catalog = EquipmentCatalog::query()->where('sku', 'HELM-DEL-TEST')->firstOrFail();

        $this->actingAs($staff)
            ->delete(route('equipment.destroy', $catalog))
            ->assertForbidden();

        $this->assertDatabaseHas('equipment_catalog', ['id' => $catalog->id]);
    }
}
