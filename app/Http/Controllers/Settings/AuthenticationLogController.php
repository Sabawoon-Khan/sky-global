<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticationLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->can('settings.view_login_logs')
                || $request->user()?->can('settings.manage_users'),
            403,
        );

        $search = $request->string('search')->trim()->toString();
        $event = $request->string('event')->trim()->toString();
        $success = $request->string('success')->trim()->toString();

        $logs = AuthenticationLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('email', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('user_agent', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($success === '1', fn ($query) => $query->where('success', true))
            ->when($success === '0', fn ($query) => $query->where('success', false))
            ->orderByDesc('logged_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('settings/AuthenticationLogs/Index', [
            'logs' => $logs,
            'events' => [
                AuthenticationLog::EVENT_LOGIN_SUCCESS,
                AuthenticationLog::EVENT_LOGIN_FAILED,
                AuthenticationLog::EVENT_LOGOUT,
                AuthenticationLog::EVENT_TWO_FACTOR_CHALLENGED,
                AuthenticationLog::EVENT_TWO_FACTOR_FAILED,
                AuthenticationLog::EVENT_LOCKOUT,
                AuthenticationLog::EVENT_SESSION_REVOKED_INACTIVE,
            ],
            'filters' => [
                'search' => $search ?: null,
                'event' => $event ?: null,
                'success' => in_array($success, ['0', '1'], true) ? $success : null,
            ],
        ]);
    }
}
