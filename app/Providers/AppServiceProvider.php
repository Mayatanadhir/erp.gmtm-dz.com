<?php

namespace App\Providers;

use App\Models\Contract;
use App\Observers\ContractObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * الأدوار الخارقة التي تتجاوز كل الصلاحيات
     *
     * @var array<int, string>
     */
    protected array $superRoles = ['Super-Admin'];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            return (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($this->superRoles)) ? true : null;
        });

        Blade::if('registrationOpen', function (): bool {
            return is_registration_open();
        });

        // Observers
        Contract::observe(ContractObserver::class);

        $this->ensureSafeDriversWhenUnmigrated();
    }

    /**
     * Fallback to file-based drivers if database tables for session or cache are not yet migrated.
     */
    protected function ensureSafeDriversWhenUnmigrated(): void
    {
        try {
            if (config('session.driver') === 'database' && ! Schema::hasTable('sessions')) {
                config(['session.driver' => 'file']);
            }

            if (config('cache.default') === 'database' && ! Schema::hasTable('cache')) {
                config(['cache.default' => 'file']);
            }
        } catch (\Throwable) {
            config(['session.driver' => 'file']);
            config(['cache.default' => 'file']);
        }
    }
}
