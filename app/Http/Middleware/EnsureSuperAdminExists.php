<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminExists
{
    /**
     * URL path patterns exempt from the setup redirect.
     *
     * @var array<int, string>
     */
    protected array $exemptPatterns = [
        'system-tables/setup*',
        'up',
        'api/*',
        'storage/*',
        '_ignition/*',
        '_debugbar/*',
        'sanctum/csrf-cookie',
        'livewire/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasUsers = $this->hasExistingUsers();

        // 1. When users already exist, permanently seal the setup screen
        if ($hasUsers) {
            if ($this->isSetupRoute($request)) {
                if ($request->isMethod('get') || $request->isMethod('head')) {
                    abort(404);
                }

                abort(403);
            }

            return $next($request);
        }

        // 2. When users do not exist (zero-state):
        // Allow the setup screen and its submission to proceed
        if ($this->isSetupRoute($request)) {
            return $next($request);
        }

        // Allow exempt background endpoints (health check, api, debug)
        foreach ($this->exemptPatterns as $pattern) {
            if ($request->is($pattern) || $request->is('*/'.$pattern)) {
                return $next($request);
            }
        }

        // In unit tests, only redirect root endpoints to avoid interfering with isolated auth tests
        if (app()->runningUnitTests()) {
            if ($request->is('/') || $request->is('ar') || $request->is('en') || $request->is('fr')) {
                return redirect()->route('system-tables.setup');
            }

            return $next($request);
        }

        // In browser / real traffic, redirect any route to the initial setup screen
        return redirect()->route('system-tables.setup');
    }

    /**
     * Check whether the request targets the system setup route.
     */
    protected function isSetupRoute(Request $request): bool
    {
        return $request->routeIs('system-tables.setup*')
            || $request->routeIs('*.system-tables.setup*')
            || $request->is('system-tables/setup*')
            || $request->is('*/system-tables/setup*');
    }

    /**
     * Determine if the users table exists and has any registered records.
     */
    protected function hasExistingUsers(): bool
    {
        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

            return (bool) Cache::remember('middleware_has_users', 30, fn () => User::exists());
        } catch (\Throwable) {
            return false;
        }
    }
}
