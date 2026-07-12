<?php

namespace App\Http\Middleware;

use App\Services\AuthenticationLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(
        protected AuthenticationLogService $authenticationLogService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_active === false) {
            $this->authenticationLogService->logInactiveSessionRevoked($request, $user);

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your account has been disabled.');
        }

        return $next($request);
    }
}
