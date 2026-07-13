<?php

namespace Database\Seeders;

use App\Data\NotificationPayload;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('notifications')->exists()) {
            return;
        }

        $service = app(NotificationService::class);

        $owner = User::query()->where('email', 'test@example.com')->first();

        if ($owner) {
            $service->notifyUser(
                $owner,
                NotificationPayload::success(
                    title: __('Welcome to :name', ['name' => config('app.name')]),
                    body: __('Your account is ready. Explore the dashboard to get started.'),
                    actionUrl: route('dashboard', [], false),
                ),
            );
        }

        $service->notifyRole(
            'Owner',
            NotificationPayload::warning(
                title: __('System update available'),
                body: __('Review the latest settings and permissions when you have a moment.'),
                actionUrl: route('settings.roles.index', [], false),
                actionLabel: __('View'),
            ),
        );

        $service->notifyPermission(
            'hr.view',
            NotificationPayload::info(
                title: __('Payroll reminder'),
                body: __('Check pending payroll runs before the end of the month.'),
                actionUrl: route('hr.payroll.index', [], false),
            ),
        );
    }
}
