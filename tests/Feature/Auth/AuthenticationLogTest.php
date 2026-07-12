<?php

namespace Tests\Feature\Auth;

use App\Models\AuthenticationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthenticationLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('settings.view_login_logs');
        Permission::findOrCreate('settings.manage_users');
    }

    public function test_successful_login_is_logged_with_request_metadata(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/120.0.0.0',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
        ]);

        $this->assertAuthenticated();

        $log = AuthenticationLog::query()->where('event', AuthenticationLog::EVENT_LOGIN_SUCCESS)->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->success);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($user->email, $log->email);
        $this->assertSame('127.0.0.1', $log->ip_address);
        $this->assertStringContainsString('Chrome', $log->browser ?? '');
        $this->assertSame('desktop', $log->device_type);
        $this->assertSame('en-US,en;q=0.9', $log->accept_language);
    }

    public function test_failed_login_is_logged_without_storing_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();

        $log = AuthenticationLog::query()->where('event', AuthenticationLog::EVENT_LOGIN_FAILED)->first();

        $this->assertNotNull($log);
        $this->assertFalse($log->success);
        $this->assertSame($user->email, $log->email);
        $this->assertSame('invalid_credentials', $log->failure_reason);
        $this->assertStringNotContainsString('wrong-password', json_encode($log->metadata ?? []));
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'));

        $log = AuthenticationLog::query()->where('event', AuthenticationLog::EVENT_LOGOUT)->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->success);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_admin_can_view_login_logs_page(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('settings.view_login_logs');

        AuthenticationLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'event' => AuthenticationLog::EVENT_LOGIN_SUCCESS,
            'success' => true,
            'ip_address' => '127.0.0.1',
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('settings.login-logs.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/AuthenticationLogs/Index')
            ->has('logs.data', 1));
    }

    public function test_user_with_manage_users_permission_can_view_login_logs_page(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('settings.manage_users');

        AuthenticationLog::query()->create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'event' => AuthenticationLog::EVENT_LOGIN_SUCCESS,
            'success' => true,
            'ip_address' => '127.0.0.1',
            'logged_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('settings.login-logs.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/AuthenticationLogs/Index')
            ->has('logs.data', 1));
    }

    public function test_user_without_permission_cannot_view_login_logs_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.login-logs.index'))
            ->assertForbidden();
    }
}
