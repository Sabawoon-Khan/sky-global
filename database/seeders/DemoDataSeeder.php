<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Models\Archive\ArchivedDocument;
use App\Models\Archive\DocumentCategory;
use App\Models\Department;
use App\Models\Equipment\EquipmentCatalog;
use App\Models\Equipment\EquipmentStock;
use App\Models\Equipment\TrainingCatalog;
use App\Models\Equipment\TrainingSession;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\ExchangeRate;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceLineItem;
use App\Models\Finance\ProjectBudget;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Forms\AttachmentType;
use App\Models\Forms\PersonnelAttachment;
use App\Models\Hr\Contractor;
use App\Models\Hr\ContractorAgreement;
use App\Models\Hr\ContractorRate;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeContract;
use App\Models\Hr\EmployeeJobDetail;
use App\Models\Hr\EmployeeSalary;
use App\Models\Hr\PayrollRun;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Organization;
use App\Models\OrganizationContact;
use App\Models\OrganizationType;
use App\Models\Procurement\Bid;
use App\Models\Procurement\BidLineItem;
use App\Models\Procurement\CompetitorBid;
use App\Models\Procurement\ProcurementOpportunity;
use App\Models\Project\Project;
use App\Models\Project\ProjectBidLineItem;
use App\Models\Project\ProjectDeployment;
use App\Models\Project\ProjectDetail;
use App\Models\Project\ProjectIssue;
use App\Models\Project\ProjectMilestone;
use App\Models\Project\ProjectSite;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /** @var list<User> */
    private array $users = [];

    /** @var list<Organization> */
    private array $organizations = [];

    /** @var list<Employee> */
    private array $employees = [];

    /** @var list<Contractor> */
    private array $contractors = [];

    /** @var list<Project> */
    private array $projects = [];

    private User $owner;

    public function run(): void
    {
        if (Project::query()->count() >= 10) {
            $this->command?->info('Demo data already seeded — skipping.');

            return;
        }

        $this->owner = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->seedUsers();
        $this->seedOrganizations();
        $this->seedExchangeRates();
        $this->seedEmployees();
        $this->seedContractors();
        $this->seedEquipmentAndTraining();
        $this->seedProjects();
        $this->seedLegacyProcurement();
        $this->seedFinance();
        $this->seedPayroll();
        $this->seedPersonnelAttachments();
        $this->seedArchiveDocuments();

        $this->command?->info('Demo data seeded: '.count($this->projects).' projects, '.count($this->employees).' employees, '.count($this->contractors).' contractors.');
    }

    private function seedUsers(): void
    {
        $staff = [
            ['name' => 'Ahmad Wali', 'email' => 'ahmad.wali@global-security.af', 'role' => 'Manager'],
            ['name' => 'Sara Mohammadi', 'email' => 'sara.mohammadi@global-security.af', 'role' => 'Manager'],
            ['name' => 'Hamid Karimi', 'email' => 'hamid.karimi@global-security.af', 'role' => 'Staff'],
            ['name' => 'Fatima Nazari', 'email' => 'fatima.nazari@global-security.af', 'role' => 'Staff'],
            ['name' => 'Omar Hassani', 'email' => 'omar.hassani@global-security.af', 'role' => 'Viewer'],
        ];

        foreach ($staff as $entry) {
            $user = User::query()->firstOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            if (! $user->hasRole($entry['role'])) {
                $user->assignRole($entry['role']);
            }

            $this->users[] = $user;
        }
    }

    private function seedOrganizations(): void
    {
        $types = OrganizationType::query()->pluck('id', 'slug');

        $entries = [
            ['name' => 'UNAMA — United Nations Assistance Mission', 'type' => 'un-agency', 'province' => 'Kabul', 'phone' => '+93 20 230 0500'],
            ['name' => 'World Food Programme Afghanistan', 'type' => 'un-agency', 'province' => 'Kabul', 'phone' => '+93 20 230 0400'],
            ['name' => 'United States Embassy Kabul', 'type' => 'embassy', 'province' => 'Kabul', 'phone' => '+93 700 108 000'],
            ['name' => 'Ministry of Interior — Afghanistan', 'type' => 'government', 'province' => 'Kabul', 'phone' => '+93 20 210 3000'],
            ['name' => 'Afghan Red Crescent Society', 'type' => 'ngo', 'province' => 'Kabul', 'phone' => '+93 20 250 0200'],
            ['name' => 'International Rescue Committee', 'type' => 'ngo', 'province' => 'Herat', 'phone' => '+93 40 220 1100'],
            ['name' => 'World Bank Group — Kabul Office', 'type' => 'private-company', 'province' => 'Kabul', 'phone' => '+93 20 230 0600'],
            ['name' => 'Save the Children Afghanistan', 'type' => 'ngo', 'province' => 'Mazar-i-Sharif', 'phone' => '+93 50 220 3300'],
            ['name' => 'German Embassy Kabul', 'type' => 'embassy', 'province' => 'Kabul', 'phone' => '+93 20 210 1500'],
            ['name' => 'Aga Khan Development Network', 'type' => 'ngo', 'province' => 'Kabul', 'phone' => '+93 20 250 0800'],
            ['name' => 'Kabul Municipality', 'type' => 'government', 'province' => 'Kabul', 'phone' => '+93 20 220 0100'],
            ['name' => 'Chemonics International', 'type' => 'private-company', 'province' => 'Kabul', 'phone' => '+93 20 230 0700'],
        ];

        foreach ($entries as $entry) {
            $org = Organization::query()->firstOrCreate(
                ['name' => $entry['name']],
                [
                    'organization_type_id' => $types[$entry['type']],
                    'address' => 'District 10, '.$entry['province'].', Afghanistan',
                    'province' => $entry['province'],
                    'phone' => $entry['phone'],
                    'email' => Str::slug(Str::before($entry['name'], ' — ')).'@client.af',
                    'is_active' => true,
                ],
            );

            OrganizationContact::query()->firstOrCreate(
                ['organization_id' => $org->id, 'name' => fake()->name()],
                [
                    'title' => 'Security Coordinator',
                    'phone' => '+93 7'.rand(0, 9).rand(1000000, 9999999),
                    'email' => 'security@'.Str::slug($org->name).'.af',
                    'is_primary' => true,
                ],
            );

            $this->organizations[] = $org;
        }
    }

    private function seedExchangeRates(): void
    {
        ExchangeRate::query()->firstOrCreate(
            ['from_currency' => 'AFN', 'to_currency' => 'USD', 'effective_date' => now()->startOfMonth()->toDateString()],
            ['rate' => 0.0115],
        );

        ExchangeRate::query()->firstOrCreate(
            ['from_currency' => 'EUR', 'to_currency' => 'USD', 'effective_date' => now()->startOfMonth()->toDateString()],
            ['rate' => 1.08],
        );
    }

    private function seedEmployees(): void
    {
        $department = Department::query()->where('code', 'OPE')->first()
            ?? Department::query()->first();

        $names = [
            ['first' => 'Nasir', 'last' => 'Ahmadzai', 'father' => 'Gul'],
            ['first' => 'Farid', 'last' => 'Popal', 'father' => 'Rahim'],
            ['first' => 'Zalmay', 'last' => 'Noori', 'father' => 'Hakim'],
            ['first' => 'Bashir', 'last' => 'Wardak', 'father' => 'Karim'],
            ['first' => 'Jamal', 'last' => 'Safi', 'father' => 'Aziz'],
            ['first' => 'Rashid', 'last' => 'Khan', 'father' => 'Mir'],
            ['first' => 'Ehsan', 'last' => 'Mohammadi', 'father' => 'Wali'],
            ['first' => 'Tariq', 'last' => 'Azimi', 'father' => 'Shah'],
            ['first' => 'Mustafa', 'last' => 'Rahmani', 'father' => 'Ali'],
            ['first' => 'Khalid', 'last' => 'Stanekzai', 'father' => 'Omar'],
            ['first' => 'Yusuf', 'last' => 'Barakzai', 'father' => 'Hassan'],
            ['first' => 'Nadir', 'last' => 'Ghafoori', 'father' => 'Said'],
            ['first' => 'Parwiz', 'last' => 'Amiri', 'father' => 'Jawad'],
            ['first' => 'Shahid', 'last' => 'Kakar', 'father' => 'Faiz'],
            ['first' => 'Wahid', 'last' => 'Tanha', 'father' => 'Masoud'],
            ['first' => 'Latif', 'last' => 'Qaderi', 'father' => 'Nawab'],
            ['first' => 'Imran', 'last' => 'Hotak', 'father' => 'Daud'],
            ['first' => 'Samir', 'last' => 'Lodin', 'father' => 'Fazal'],
        ];

        $designations = ['Security Supervisor', 'Operations Manager', 'Site Commander', 'Guard Team Lead', 'Training Officer', 'Logistics Coordinator'];

        foreach ($names as $index => $name) {
            $employee = Employee::query()->create([
                'first_name' => $name['first'],
                'last_name' => $name['last'],
                'father_name' => $name['father'],
                'original_address' => 'District '.rand(1, 22).', Kabul',
                'current_address' => 'Company Housing, Kabul',
                'phone' => '+93 7'.rand(0, 9).rand(1000000, 9999999),
                'email' => Str::lower($name['first']).'.'.Str::lower($name['last']).'@global-security.af',
                'tazkira_number' => 'TK-'.rand(100000, 999999),
                'date_of_birth' => now()->subYears(rand(25, 50))->subDays(rand(1, 365)),
                'gender' => 'male',
                'status' => 'active',
            ]);

            EmployeeJobDetail::query()->create([
                'employee_id' => $employee->id,
                'department_id' => $department?->id,
                'designation' => $designations[$index % count($designations)],
                'hire_date' => now()->subMonths(rand(6, 48)),
                'salary_grade' => 'G'.rand(3, 8),
            ]);

            EmployeeSalary::query()->create([
                'employee_id' => $employee->id,
                'amount' => rand(400, 1200),
                'currency' => 'USD',
                'effective_from' => now()->subMonths(rand(3, 24)),
            ]);

            EmployeeContract::query()->create([
                'employee_id' => $employee->id,
                'contract_number' => 'EMP-'.now()->format('Y').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'start_date' => now()->subMonths(rand(6, 36)),
                'end_date' => now()->addMonths(rand(6, 24)),
            ]);

            $this->employees[] = $employee;
        }
    }

    private function seedContractors(): void
    {
        $names = [
            ['first' => 'Abdul', 'last' => 'Rahman', 'father' => 'Ghulam'],
            ['first' => 'Mirwais', 'last' => 'Yousufi', 'father' => 'Sultan'],
            ['first' => 'Habib', 'last' => 'Rahmati', 'father' => 'Jan'],
            ['first' => 'Faisal', 'last' => 'Khosti', 'father' => 'Nadir'],
            ['first' => 'Ghulam', 'last' => 'Farooq', 'father' => 'Aman'],
            ['first' => 'Dawood', 'last' => 'Sultani', 'father' => 'Bismillah'],
            ['first' => 'Ismail', 'last' => 'Khalili', 'father' => 'Qasim'],
            ['first' => 'Noor', 'last' => 'Ahmadi', 'father' => 'Wali'],
            ['first' => 'Saeed', 'last' => 'Mansoor', 'father' => 'Hamid'],
            ['first' => 'Aziz', 'last' => 'Kakar', 'father' => 'Rashid'],
            ['first' => 'Javed', 'last' => 'Nawabi', 'father' => 'Karim'],
            ['first' => 'Rahim', 'last' => 'Sharifi', 'father' => 'Omar'],
            ['first' => 'Sadiq', 'last' => 'Zadran', 'father' => 'Hakim'],
            ['first' => 'Masood', 'last' => 'Alokozay', 'father' => 'Faiz'],
            ['first' => 'Hakim', 'last' => 'Shinwari', 'father' => 'Gul'],
        ];

        foreach ($names as $index => $name) {
            $contractor = Contractor::query()->create([
                'first_name' => $name['first'],
                'last_name' => $name['last'],
                'father_name' => $name['father'],
                'original_address' => 'Province of '.['Kabul', 'Nangarhar', 'Kandahar', 'Herat', 'Balkh'][$index % 5],
                'current_address' => 'Kabul',
                'phone' => '+93 7'.rand(0, 9).rand(1000000, 9999999),
                'tazkira_number' => 'TK-C-'.rand(100000, 999999),
                'date_of_birth' => now()->subYears(rand(22, 45))->subDays(rand(1, 365)),
                'gender' => 'male',
                'status' => 'active',
            ]);

            ContractorAgreement::query()->create([
                'contractor_id' => $contractor->id,
                'agreement_number' => 'CTR-'.now()->format('Y').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'start_date' => now()->subMonths(rand(3, 18)),
                'end_date' => now()->addMonths(rand(6, 18)),
            ]);

            $this->contractors[] = $contractor;
        }
    }

    private function seedEquipmentAndTraining(): void
    {
        $equipment = [
            ['name' => 'Body Armor Vest', 'sku' => 'EQ-BAV-001', 'qty' => 45],
            ['name' => 'Ballistic Helmet', 'sku' => 'EQ-BH-001', 'qty' => 38],
            ['name' => 'Two-Way Radio', 'sku' => 'EQ-RAD-001', 'qty' => 60],
            ['name' => 'Flashlight (Tactical)', 'sku' => 'EQ-FL-001', 'qty' => 80],
            ['name' => 'Metal Detector Wand', 'sku' => 'EQ-MD-001', 'qty' => 12],
            ['name' => 'First Aid Kit', 'sku' => 'EQ-FAK-001', 'qty' => 30],
        ];

        foreach ($equipment as $item) {
            $catalog = EquipmentCatalog::query()->firstOrCreate(
                ['sku' => $item['sku']],
                ['name' => $item['name'], 'description' => 'Standard issue '.$item['name'], 'is_active' => true],
            );

            EquipmentStock::query()->firstOrCreate(
                ['equipment_catalog_id' => $catalog->id],
                ['quantity_on_hand' => $item['qty'], 'quantity_reserved' => rand(0, 5)],
            );
        }

        $trainings = [
            ['name' => 'Close Protection Basics', 'months' => 12],
            ['name' => 'First Aid & CPR', 'months' => 24],
            ['name' => 'Firearms Safety', 'months' => 12],
            ['name' => 'Access Control Procedures', 'months' => null],
            ['name' => 'Crowd Management', 'months' => 18],
        ];

        foreach ($trainings as $training) {
            $catalog = TrainingCatalog::query()->firstOrCreate(
                ['name' => $training['name']],
                ['description' => $training['name'].' certification', 'validity_months' => $training['months'], 'is_active' => true],
            );

            TrainingSession::query()->firstOrCreate(
                ['training_catalog_id' => $catalog->id, 'title' => $training['name'].' — Batch '.now()->format('Y-m')],
                [
                    'session_date' => now()->subDays(rand(10, 90)),
                    'location' => 'Global Security Training Center, Kabul',
                    'instructor_id' => $this->users[0]->id ?? $this->owner->id,
                ],
            );
        }
    }

    private function seedProjects(): void
    {
        $managers = array_merge([$this->owner], $this->users);
        $locations = ['Kabul', 'Herat', 'Mazar-i-Sharif', 'Kandahar', 'Jalalabad', 'Kunduz', 'Bamyan'];
        $scopes = ['Static Guarding', 'Mobile Escort', 'Access Control', 'VIP Protection', 'Perimeter Security', 'Event Security'];

        $definitions = [
            ['name' => 'UNAMA Compound Static Guarding', 'status' => ProjectStatus::Active, 'value' => 480000, 'bid' => 465000, 'months' => 12],
            ['name' => 'US Embassy Perimeter Security', 'status' => ProjectStatus::Active, 'value' => 720000, 'bid' => 698000, 'months' => 18],
            ['name' => 'WFP Warehouse Protection — Kabul', 'status' => ProjectStatus::Active, 'value' => 156000, 'bid' => 152000, 'months' => 6],
            ['name' => 'World Bank Office Access Control', 'status' => ProjectStatus::Active, 'value' => 96000, 'bid' => 94000, 'months' => 4],
            ['name' => 'IRC Herat Field Office Security', 'status' => ProjectStatus::Active, 'value' => 84000, 'bid' => 82000, 'months' => 6],
            ['name' => 'Save the Children Mazar Site Guard', 'status' => ProjectStatus::Active, 'value' => 72000, 'bid' => 70000, 'months' => 6],
            ['name' => 'AKDN Guest House Protection', 'status' => ProjectStatus::Completed, 'value' => 54000, 'bid' => 52000, 'months' => 3],
            ['name' => 'Kabul Municipality Building Security', 'status' => ProjectStatus::Completed, 'value' => 120000, 'bid' => 115000, 'months' => 8],
            ['name' => 'German Embassy VIP Escort', 'status' => ProjectStatus::Completed, 'value' => 88000, 'bid' => 86000, 'months' => 4],
            ['name' => 'Chemonics Project Site Guarding', 'status' => ProjectStatus::Won, 'value' => 200000, 'bid' => 195000, 'months' => 10],
            ['name' => 'MoI Training Center Perimeter', 'status' => ProjectStatus::Won, 'value' => 340000, 'bid' => 330000, 'months' => 12],
            ['name' => 'Red Crescent Clinic Security', 'status' => ProjectStatus::Submitted, 'value' => null, 'bid' => 68000, 'months' => 6],
            ['name' => 'UNDP Regional Office Protection', 'status' => ProjectStatus::Submitted, 'value' => null, 'bid' => 112000, 'months' => 8],
            ['name' => 'Embassy Event Security Package', 'status' => ProjectStatus::Submitted, 'value' => null, 'bid' => 45000, 'months' => 2],
            ['name' => 'NGO Compound Mobile Escort', 'status' => ProjectStatus::Draft, 'value' => null, 'bid' => 38000, 'months' => 4],
            ['name' => 'Airport Cargo Zone Guarding', 'status' => ProjectStatus::Draft, 'value' => null, 'bid' => 92000, 'months' => 6],
            ['name' => 'Hospital Emergency Wing Security', 'status' => ProjectStatus::Draft, 'value' => null, 'bid' => null, 'months' => 3],
            ['name' => 'Provincial Governor Residence Guard', 'status' => ProjectStatus::Lost, 'value' => null, 'bid' => 175000, 'months' => 12, 'winner' => 'Shield Force Ltd', 'winning' => 168000],
            ['name' => 'Diplomatic Convoy Escort Services', 'status' => ProjectStatus::Lost, 'value' => null, 'bid' => 89000, 'months' => 6, 'winner' => 'Guardian Security Co', 'winning' => 85000],
            ['name' => 'Construction Site Night Watch', 'status' => ProjectStatus::Lost, 'value' => null, 'bid' => 42000, 'months' => 4, 'winner' => 'Alpha Protection Group', 'winning' => 39500],
            ['name' => 'Election Monitoring Security', 'status' => ProjectStatus::Suspended, 'value' => 250000, 'bid' => 245000, 'months' => 3],
            ['name' => 'Border Checkpoint Support', 'status' => ProjectStatus::Closed, 'value' => 180000, 'bid' => 175000, 'months' => 6],
        ];

        $codeSequence = 1;

        foreach ($definitions as $index => $def) {
            $org = $this->organizations[$index % count($this->organizations)];
            $location = $locations[$index % count($locations)];
            $scope = $scopes[$index % count($scopes)];
            $manager = $managers[$index % count($managers)];
            $status = $def['status'];

            $contractStart = in_array($status, [ProjectStatus::Active, ProjectStatus::Completed, ProjectStatus::Suspended, ProjectStatus::Closed, ProjectStatus::Won], true)
                ? now()->subMonths(rand(1, 8))
                : null;

            $project = Project::query()->create([
                'organization_id' => $org->id,
                'code' => $this->projectCode($codeSequence++),
                'name' => $def['name'],
                'reference_number' => 'REF-'.now()->format('Y').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'contract_number' => $contractStart ? 'CNT-'.now()->format('Y').'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT) : null,
                'contract_start' => $contractStart,
                'contract_end' => $contractStart ? $contractStart->copy()->addMonths($def['months']) : null,
                'scope_summary' => "Provide {$scope} services at client facility in {$location}.",
                'source' => ['Direct Invitation', 'UNGM Portal', 'Referral', 'Public Tender'][$index % 4],
                'location' => $location,
                'security_scope' => $scope,
                'published_at' => now()->subMonths(rand(2, 10)),
                'submission_deadline' => now()->subMonths(rand(0, 6))->addDays(rand(5, 30)),
                'bid_submitted_at' => in_array($status, [ProjectStatus::Submitted, ProjectStatus::Won, ProjectStatus::Lost, ProjectStatus::Active, ProjectStatus::Completed, ProjectStatus::Suspended, ProjectStatus::Closed], true)
                    ? now()->subMonths(rand(1, 8))
                    : null,
                'total_contract_value' => $def['value'],
                'our_bid_amount' => $def['bid'],
                'loss_reason' => $status === ProjectStatus::Lost ? 'Competitor offered lower price with comparable qualifications.' : null,
                'winning_competitor_name' => $def['winner'] ?? null,
                'winning_amount' => $def['winning'] ?? null,
                'currency' => 'USD',
                'status' => $status->value,
                'project_manager_id' => $manager->id,
                'won_at' => in_array($status, [ProjectStatus::Won, ProjectStatus::Active, ProjectStatus::Completed, ProjectStatus::Suspended], true) ? now()->subMonths(rand(1, 6)) : null,
                'started_at' => in_array($status, [ProjectStatus::Active, ProjectStatus::Completed, ProjectStatus::Suspended, ProjectStatus::Closed], true) ? now()->subMonths(rand(1, 5)) : null,
                'completed_at' => $status === ProjectStatus::Completed ? now()->subWeeks(rand(2, 8)) : null,
                'created_by' => $this->owner->id,
            ]);

            ProjectDetail::query()->create([
                'project_id' => $project->id,
                'client_requirements' => 'Minimum 12 guards on rotating 8-hour shifts. All personnel must hold valid medical and training certificates.',
                'risk_notes' => 'Medium threat environment. Coordinate with client security focal point daily.',
                'guards_required' => rand(8, 24),
                'supervisors_required' => rand(1, 4),
                'shift_details' => '3 shifts × 8 hours (06:00–14:00, 14:00–22:00, 22:00–06:00)',
                'equipment_requirements' => 'Body armor, radios, flashlights for all deployed personnel.',
                'training_requirements' => 'Close Protection Basics, First Aid & CPR',
                'client_contact_on_site' => 'Mr. Security Focal Point — +93 700 000 '.str_pad((string) ($index + 100), 4, '0', STR_PAD_LEFT),
                'reporting_frequency' => 'Daily incident report; weekly summary to client.',
            ]);

            $siteCount = rand(1, 2);
            $sites = [];
            for ($s = 0; $s < $siteCount; $s++) {
                $sites[] = ProjectSite::query()->create([
                    'project_id' => $project->id,
                    'name' => $s === 0 ? 'Main Site' : 'Secondary Site',
                    'address' => 'District '.rand(1, 15).", {$location}",
                    'province' => $location,
                    'is_active' => true,
                ]);
            }

            if ($def['bid']) {
                $this->seedProjectBidLineItems($project, (float) $def['bid']);
            }

            if ($status === ProjectStatus::Lost) {
                $this->seedCompetitorBids($project, $def['winner'] ?? 'Unknown Competitor', (float) ($def['winning'] ?? $def['bid'] * 0.95));
            }

            if (in_array($status, [ProjectStatus::Submitted, ProjectStatus::Draft], true)) {
                $this->seedCompetitorBids($project, 'Estimated Competitor A', (float) ($def['bid'] ?? 50000) * (rand(90, 110) / 100), estimated: true);
            }

            if (in_array($status, [ProjectStatus::Active, ProjectStatus::Completed, ProjectStatus::Suspended], true)) {
                $this->seedOperationalData($project, $sites);
            }

            $this->projects[] = $project;
        }
    }

    /** @param list<ProjectSite> $sites */
    private function seedOperationalData(Project $project, array $sites): void
    {
        ProjectMilestone::query()->create([
            'project_id' => $project->id,
            'title' => 'Mobilization Complete',
            'due_date' => $project->contract_start?->copy()->addWeeks(2),
            'completed_date' => $project->contract_start?->copy()->addWeeks(2),
            'status' => 'completed',
        ]);

        ProjectMilestone::query()->create([
            'project_id' => $project->id,
            'title' => 'Mid-Term Review',
            'due_date' => $project->contract_start?->copy()->addMonths((int) (($project->contract_end?->diffInMonths($project->contract_start) ?? 6) / 2)),
            'status' => $project->status === 'completed' ? 'completed' : 'pending',
            'completed_date' => $project->status === 'completed' ? now()->subMonths(2) : null,
        ]);

        ProjectIssue::query()->create([
            'project_id' => $project->id,
            'title' => 'Radio equipment malfunction at main gate',
            'description' => 'Two radios lost signal during night shift. Replacement requested from logistics.',
            'severity' => 'medium',
            'status' => rand(0, 1) ? 'resolved' : 'open',
            'category' => 'equipment',
            'reported_by' => $this->owner->id,
            'assigned_to' => $this->users[0]->id ?? $this->owner->id,
            'opened_at' => now()->subDays(rand(5, 30)),
            'resolved_at' => rand(0, 1) ? now()->subDays(rand(1, 4)) : null,
        ]);

        $guardCount = rand(4, 8);
        $personnel = array_merge(
            array_slice($this->contractors, 0, min($guardCount, count($this->contractors))),
            array_slice($this->employees, 0, min(2, count($this->employees))),
        );

        foreach ($personnel as $i => $person) {
            $isEmployee = $person instanceof Employee;
            ProjectDeployment::query()->create([
                'project_id' => $project->id,
                'project_site_id' => $sites[$i % count($sites)]->id,
                'personnel_type' => $isEmployee ? Employee::class : Contractor::class,
                'personnel_id' => $person->id,
                'role' => $isEmployee ? 'supervisor' : 'guard',
                'shift_pattern' => ['day', 'night', 'rotating'][$i % 3],
                'start_date' => $project->contract_start,
                'end_date' => $project->contract_end,
                'monthly_rate' => $isEmployee ? rand(800, 1200) : rand(350, 550),
                'currency' => 'USD',
            ]);

            if (! $isEmployee) {
                ContractorRate::query()->create([
                    'contractor_id' => $person->id,
                    'project_id' => $project->id,
                    'monthly_rate' => rand(350, 550),
                    'currency' => 'USD',
                    'effective_from' => $project->contract_start,
                ]);
            }
        }

        foreach (['Personnel', 'Equipment', 'Overhead'] as $category) {
            $amount = match ($category) {
                'Personnel' => ($project->total_contract_value ?? 100000) * 0.65,
                'Equipment' => ($project->total_contract_value ?? 100000) * 0.15,
                default => ($project->total_contract_value ?? 100000) * 0.10,
            };

            ProjectBudget::query()->create([
                'project_id' => $project->id,
                'category' => $category,
                'budgeted_amount' => round($amount, 2),
                'currency' => 'USD',
            ]);
        }
    }

    private function seedProjectBidLineItems(Project $project, float $total): void
    {
        $categories = [
            ['category' => 'Personnel', 'desc' => 'Security guards (monthly)', 'qty' => 12, 'unit' => 'month', 'share' => 0.55],
            ['category' => 'Supervision', 'desc' => 'Site supervisors', 'qty' => 2, 'unit' => 'month', 'share' => 0.15],
            ['category' => 'Equipment', 'desc' => 'Radios, armor, helmets', 'qty' => 1, 'unit' => 'lot', 'share' => 0.12],
            ['category' => 'Training', 'desc' => 'Pre-deployment training', 'qty' => 1, 'unit' => 'lot', 'share' => 0.08],
            ['category' => 'Overhead', 'desc' => 'Management & admin', 'qty' => 1, 'unit' => 'lot', 'share' => 0.10],
        ];

        foreach ($categories as $i => $cat) {
            $lineTotal = round($total * $cat['share'], 2);
            $unitPrice = round($lineTotal / max($cat['qty'], 1), 2);

            ProjectBidLineItem::query()->create([
                'project_id' => $project->id,
                'category' => $cat['category'],
                'description' => $cat['desc'],
                'quantity' => $cat['qty'],
                'unit' => $cat['unit'],
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
                'currency' => 'USD',
                'sort_order' => $i,
            ]);
        }
    }

    private function seedCompetitorBids(Project $project, string $name, float $amount, bool $estimated = false): void
    {
        CompetitorBid::query()->create([
            'project_id' => $project->id,
            'procurement_opportunity_id' => null,
            'competitor_name' => $name,
            'bid_amount' => $amount,
            'currency' => 'USD',
            'is_winner' => $project->status === ProjectStatus::Lost->value,
            'is_estimated' => $estimated,
            'source' => $estimated ? 'Market estimate' : 'Award notification',
        ]);

        if (! $estimated) {
            CompetitorBid::query()->create([
                'project_id' => $project->id,
                'competitor_name' => 'Regional Security Partners',
                'bid_amount' => round($amount * 1.05, 2),
                'currency' => 'USD',
                'is_estimated' => true,
                'source' => 'Industry intel',
            ]);
        }
    }

    private function seedLegacyProcurement(): void
    {
        $org = $this->organizations[0];

        $opportunity = ProcurementOpportunity::query()->create([
            'organization_id' => $org->id,
            'reference_number' => 'UNGM-2025-'.rand(1000, 9999),
            'title' => 'Long-term Guard Services — UN Compound B',
            'description' => 'Legacy procurement opportunity for guard services.',
            'source' => 'UNGM',
            'published_at' => now()->subMonths(3),
            'submission_deadline' => now()->addWeeks(2),
            'estimated_value' => 320000,
            'currency' => 'USD',
            'security_scope' => 'Static Guarding',
            'location' => 'Kabul',
            'duration_months' => 12,
            'status' => 'open',
            'created_by' => $this->owner->id,
        ]);

        ProcurementOpportunity::query()->create([
            'organization_id' => $this->organizations[2]->id,
            'reference_number' => 'EMB-2025-'.rand(1000, 9999),
            'title' => 'Diplomatic Residence Security Upgrade',
            'description' => 'Enhanced perimeter security for diplomatic residences.',
            'source' => 'Direct',
            'published_at' => now()->subWeeks(2),
            'submission_deadline' => now()->addMonth(),
            'estimated_value' => 95000,
            'currency' => 'USD',
            'security_scope' => 'Perimeter Security',
            'location' => 'Kabul',
            'duration_months' => 6,
            'status' => 'open',
            'created_by' => $this->owner->id,
        ]);

        $bidStatuses = ['won', 'lost', 'submitted', 'draft', 'under_review'];
        foreach ($bidStatuses as $i => $bidStatus) {
            $bid = Bid::query()->create([
                'procurement_opportunity_id' => $opportunity->id,
                'bid_number' => 'B-'.now()->format('Y').'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'status' => $bidStatus,
                'submitted_at' => in_array($bidStatus, ['submitted', 'won', 'lost', 'under_review']) ? now()->subMonths(rand(1, 4)) : null,
                'our_total_amount' => rand(80000, 350000),
                'currency' => 'USD',
                'loss_reason' => $bidStatus === 'lost' ? 'Price not competitive.' : null,
                'winning_competitor_name' => $bidStatus === 'lost' ? 'Competitor Alpha' : null,
                'winning_amount' => $bidStatus === 'lost' ? rand(70000, 300000) : null,
                'created_by' => $this->owner->id,
            ]);

            BidLineItem::query()->create([
                'bid_id' => $bid->id,
                'category' => 'Personnel',
                'description' => 'Guard services',
                'quantity' => 10,
                'unit' => 'month',
                'unit_price' => rand(3000, 5000),
                'total' => $bid->our_total_amount * 0.7,
                'currency' => 'USD',
            ]);

            CompetitorBid::query()->create([
                'procurement_opportunity_id' => $opportunity->id,
                'competitor_name' => 'Legacy Competitor '.($i + 1),
                'bid_amount' => rand(75000, 360000),
                'currency' => 'USD',
                'is_winner' => $bidStatus === 'lost',
                'is_estimated' => $bidStatus === 'draft',
            ]);
        }
    }

    private function seedFinance(): void
    {
        $accounts = ChartOfAccount::query()->pluck('id');
        $revenueAccount = ChartOfAccount::query()->firstOrCreate(
            ['code' => 'REV01'],
            ['name' => 'Service Revenue', 'type' => 'revenue'],
        );

        $activeProjects = collect($this->projects)->filter(
            fn (Project $p) => in_array($p->status, ['active', 'completed', 'suspended'], true),
        );

        foreach ($activeProjects as $project) {
            $contractValue = (float) ($project->total_contract_value ?? 100000);
            $months = max(1, (int) ($project->contract_start?->diffInMonths(now()) ?? 3));

            for ($m = 0; $m < min($months, 6); $m++) {
                $date = now()->subMonths($m)->day(rand(1, 28));
                $incomeAmount = round($contractValue / max($project->contract_end?->diffInMonths($project->contract_start) ?? 6, 1), 2);

                ProjectIncome::query()->create([
                    'project_id' => $project->id,
                    'account_id' => $revenueAccount->id,
                    'amount' => $incomeAmount,
                    'currency' => 'USD',
                    'amount_usd' => $incomeAmount,
                    'description' => 'Monthly service fee — '.$date->format('F Y'),
                    'transaction_date' => $date,
                    'reference_number' => 'INV-'.$project->code.'-'.$date->format('Ym'),
                    'payment_method' => 'bank_transfer',
                    'status' => 'recorded',
                    'created_by' => $this->owner->id,
                ]);

                ProjectExpense::query()->create([
                    'project_id' => $project->id,
                    'account_id' => $accounts->random(),
                    'amount' => round($incomeAmount * (rand(55, 75) / 100), 2),
                    'currency' => 'USD',
                    'amount_usd' => round($incomeAmount * (rand(55, 75) / 100), 2),
                    'description' => 'Personnel & operational costs — '.$date->format('F Y'),
                    'transaction_date' => $date,
                    'reference_number' => 'EXP-'.$project->code.'-'.$date->format('Ym'),
                    'payment_method' => 'bank_transfer',
                    'status' => 'recorded',
                    'created_by' => $this->owner->id,
                ]);
            }

            $invoiceTotal = round($contractValue / 6, 2);
            $invoice = Invoice::query()->create([
                'project_id' => $project->id,
                'organization_id' => $project->organization_id,
                'invoice_number' => 'GS-INV-'.str_pad((string) $project->id, 5, '0', STR_PAD_LEFT),
                'issue_date' => now()->subDays(rand(10, 45)),
                'due_date' => now()->addDays(30),
                'subtotal' => $invoiceTotal,
                'tax' => 0,
                'total' => $invoiceTotal,
                'currency' => 'USD',
                'status' => ['sent', 'paid', 'draft'][rand(0, 2)],
                'created_by' => $this->owner->id,
            ]);

            InvoiceLineItem::query()->create([
                'invoice_id' => $invoice->id,
                'description' => 'Security services — '.$project->name,
                'quantity' => 1,
                'unit_price' => $invoiceTotal,
                'total' => $invoiceTotal,
            ]);
        }

        $overheadCategories = ['office_rent', 'utilities', 'insurance', 'vehicles', 'admin'];
        for ($m = 0; $m < 6; $m++) {
            $date = now()->subMonths($m)->day(rand(1, 28));
            $amount = rand(1500, 8000);

            GeneralExpense::query()->create([
                'account_id' => $accounts->random(),
                'amount' => $amount,
                'currency' => 'USD',
                'amount_usd' => $amount,
                'description' => 'Overhead — '.str_replace('_', ' ', $overheadCategories[$m % count($overheadCategories)]),
                'category' => $overheadCategories[$m % count($overheadCategories)],
                'transaction_date' => $date,
                'reference_number' => 'OH-'.$date->format('Ym'),
                'payment_method' => 'bank_transfer',
                'status' => 'recorded',
                'created_by' => $this->owner->id,
            ]);

            if ($m % 2 === 0) {
                GeneralIncome::query()->create([
                    'account_id' => $revenueAccount->id,
                    'amount' => rand(2000, 12000),
                    'currency' => 'USD',
                    'amount_usd' => rand(2000, 12000),
                    'description' => 'Miscellaneous service income',
                    'category' => 'consulting',
                    'transaction_date' => $date,
                    'reference_number' => 'GI-'.$date->format('Ym'),
                    'payment_method' => 'bank_transfer',
                    'status' => 'recorded',
                    'created_by' => $this->owner->id,
                ]);
            }
        }
    }

    private function seedPayroll(): void
    {
        for ($m = 0; $m < 3; $m++) {
            $date = now()->subMonths($m);
            $run = PayrollRun::query()->firstOrCreate(
                ['period_year' => $date->year, 'period_month' => $date->month],
                ['status' => $m === 0 ? 'draft' : 'processed', 'processed_by' => $m === 0 ? null : $this->owner->id],
            );

            $activeProject = collect($this->projects)->first(fn (Project $p) => $p->status === 'active');

            foreach (array_slice($this->contractors, 0, 8) as $contractor) {
                PersonnelAttendance::query()->firstOrCreate(
                    [
                        'personnel_type' => Contractor::class,
                        'personnel_id' => $contractor->id,
                        'project_id' => $activeProject?->id,
                        'year' => $date->year,
                        'month' => $date->month,
                    ],
                    [
                        'days_present' => rand(22, 28),
                        'days_absent' => rand(0, 3),
                        'days_leave' => rand(0, 2),
                        'overtime_hours' => rand(0, 20),
                        'status' => $m === 0 ? 'draft' : 'approved',
                        'approved_by' => $m === 0 ? null : $this->owner->id,
                    ],
                );
            }
        }
    }

    private function seedPersonnelAttachments(): void
    {
        $types = AttachmentType::query()->get();
        $expiringTypes = $types->whereIn('slug', ['medical-certificate', 'training-certificate', 'weapon-license']);

        foreach (array_slice($this->contractors, 0, 10) as $i => $contractor) {
            foreach ($types->take(3) as $type) {
                $expiresAt = $expiringTypes->contains('id', $type->id)
                    ? ($i < 4 ? now()->addDays(rand(5, 25)) : now()->addMonths(rand(3, 12)))
                    : null;

                PersonnelAttachment::query()->create([
                    'personnel_type' => Contractor::class,
                    'personnel_id' => $contractor->id,
                    'attachment_type_id' => $type->id,
                    'file_path' => 'demo/attachments/'.Str::slug($type->name).'-'.$contractor->id.'.pdf',
                    'issued_at' => now()->subMonths(rand(1, 12)),
                    'expires_at' => $expiresAt,
                    'verified_by' => $this->owner->id,
                ]);
            }
        }

        foreach (array_slice($this->employees, 0, 5) as $employee) {
            $type = $types->first();
            if ($type) {
                PersonnelAttachment::query()->create([
                    'personnel_type' => Employee::class,
                    'personnel_id' => $employee->id,
                    'attachment_type_id' => $type->id,
                    'file_path' => 'demo/attachments/tazkira-'.$employee->id.'.pdf',
                    'issued_at' => now()->subYears(2),
                    'verified_by' => $this->owner->id,
                ]);
            }
        }
    }

    private function seedArchiveDocuments(): void
    {
        $category = DocumentCategory::query()->first();
        if (! $category) {
            return;
        }

        $docs = [
            ['title' => 'Master Service Agreement — UNAMA 2025', 'direction' => 'incoming'],
            ['title' => 'Insurance Certificate — Global Security', 'direction' => 'internal'],
            ['title' => 'Bid Submission Acknowledgment — WFP', 'direction' => 'outgoing'],
            ['title' => 'Security Assessment Report — Herat', 'direction' => 'internal'],
            ['title' => 'Contract Amendment No. 2 — Embassy', 'direction' => 'incoming'],
        ];

        foreach ($docs as $i => $doc) {
            $project = $this->projects[$i % count($this->projects)] ?? null;

            ArchivedDocument::query()->create([
                'reference_number' => 'ARC-'.now()->format('Y').'-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'title' => $doc['title'],
                'description' => 'Archived document for demo purposes.',
                'direction' => $doc['direction'],
                'document_category_id' => $category->id,
                'organization_id' => $project?->organization_id,
                'project_id' => $project?->id,
                'file_path' => 'demo/archive/doc-'.($i + 1).'.pdf',
                'original_filename' => Str::slug($doc['title']).'.pdf',
                'file_size' => rand(50000, 500000),
                'document_date' => now()->subMonths(rand(1, 12)),
                'uploaded_by' => $this->owner->id,
            ]);
        }
    }

    private function projectCode(int $sequence): string
    {
        return 'GS-'.now()->format('Y').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
