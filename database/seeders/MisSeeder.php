<?php

namespace Database\Seeders;

use App\Models\Archive\DocumentCategory;
use App\Models\Department;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\Currency;
use App\Models\Forms\AttachmentType;
use App\Models\OrganizationType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MisSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $orgTypes = [
            ['name' => 'Government', 'color' => '#2563eb'],
            ['name' => 'NGO', 'color' => '#16a34a'],
            ['name' => 'UN Agency', 'color' => '#0891b2'],
            ['name' => 'Private Company', 'color' => '#9333ea'],
            ['name' => 'Embassy', 'color' => '#ca8a04'],
        ];

        foreach ($orgTypes as $index => $type) {
            OrganizationType::query()->firstOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => $type['name'],
                    'color' => $type['color'],
                    'sort_order' => $index,
                ],
            );
        }

        foreach (['HR', 'Finance', 'Operations', 'Bidding'] as $dept) {
            Department::query()->firstOrCreate(
                ['code' => Str::upper(Str::substr($dept, 0, 3))],
                ['name' => $dept],
            );
        }

        Currency::query()->firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_default' => true],
        );

        Currency::query()->firstOrCreate(
            ['code' => 'AFN'],
            ['name' => 'Afghan Afghani', 'symbol' => '؋'],
        );

        foreach (['Revenue', 'Cost of Services', 'Payroll', 'Overhead', 'Equipment'] as $account) {
            ChartOfAccount::query()->firstOrCreate(
                ['code' => Str::upper(Str::substr(str_replace(' ', '', $account), 0, 4)).rand(10, 99)],
                ['name' => $account, 'type' => 'expense'],
            );
        }

        foreach (['Contract', 'Letter', 'Invoice', 'RFP', 'Certificate', 'Report'] as $index => $cat) {
            DocumentCategory::query()->firstOrCreate(
                ['slug' => Str::slug($cat)],
                ['name' => $cat, 'sort_order' => $index],
            );
        }

        foreach (['Tazkira', 'Police Clearance', 'Medical Certificate', 'Training Certificate', 'Weapon License', 'Guarantee Form'] as $index => $type) {
            AttachmentType::query()->firstOrCreate(
                ['slug' => Str::slug($type)],
                [
                    'name' => $type,
                    'requires_expiry' => in_array($type, ['Medical Certificate', 'Training Certificate', 'Weapon License']),
                    'sort_order' => $index,
                ],
            );
        }

        $user = User::query()->where('email', 'test@example.com')->first();
        if ($user && ! $user->hasRole('Owner')) {
            $user->assignRole('Owner');
        }
    }
}
