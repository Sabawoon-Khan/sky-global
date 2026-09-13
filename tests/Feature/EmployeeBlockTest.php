<?php

namespace Tests\Feature;

use App\Models\Hr\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeBlockTest extends TestCase
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

    public function test_blocking_requires_reason_and_file(): void
    {
        $employee = Employee::query()->create([
            'first_name' => 'Ahmad',
            'last_name' => 'Khan',
            'status' => 'active',
            'is_permanent' => true,
        ]);

        $this->actingAs($this->owner)
            ->from(route('hr.employees.show', $employee))
            ->put(route('hr.employees.update', $employee), [
                'status' => 'blocked',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['reason', 'attachment']);

        $this->assertSame('active', $employee->fresh()->status);
    }

    public function test_owner_can_block_employee_with_reason_and_file(): void
    {
        Storage::fake('local');

        $employee = Employee::query()->create([
            'first_name' => 'Ahmad',
            'last_name' => 'Khan',
            'status' => 'active',
            'is_permanent' => true,
        ]);

        $file = UploadedFile::fake()->create('block-notice.pdf', 120, 'application/pdf');

        $this->actingAs($this->owner)
            ->from(route('hr.employees.show', $employee))
            ->put(route('hr.employees.update', $employee), [
                'status' => 'blocked',
                'reason' => 'Policy violation',
                'attachment' => $file,
            ])
            ->assertRedirect();

        $employee->refresh();

        $this->assertSame('blocked', $employee->status);

        $log = $employee->statusChangeLogs()->where('to_status', 'blocked')->first();

        $this->assertNotNull($log);
        $this->assertSame('Policy violation', $log->reason);
        $this->assertSame(1, $log->attachments()->count());
        $this->assertSame('block-notice.pdf', $log->attachments()->first()?->original_filename);
        $this->assertSame(0, $employee->attachments()->count());
    }
}
