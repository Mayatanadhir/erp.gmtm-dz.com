<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\EmployeeRepositoryInterface;
use App\Interfaces\EquipmentRepositoryInterface;
use App\Interfaces\GrandeurRepositoryInterface;
use App\Interfaces\InstrumentRepositoryInterface;
use App\Interfaces\ItemTypeRepositoryInterface;
use App\Interfaces\MissionOrderRepositoryInterface;
use App\Interfaces\MissionRepositoryInterface;
use App\Interfaces\SiteRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Interfaces\WarrantyRepositoryInterface;
use App\Repositories\CustomerRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\EquipmentRepository;
use App\Repositories\GrandeurRepository;
use App\Repositories\InstrumentRepository;
use App\Repositories\ItemTypeRepository;
use App\Repositories\MissionOrderRepository;
use App\Repositories\MissionRepository;
use App\Repositories\SiteRepository;
use App\Repositories\UserRepository;
use App\Repositories\WarrantyRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ItemTypeRepositoryInterface::class, ItemTypeRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(SiteRepositoryInterface::class, SiteRepository::class);
        $this->app->bind(WarrantyRepositoryInterface::class, WarrantyRepository::class);
        $this->app->bind(EquipmentRepositoryInterface::class, EquipmentRepository::class);
        $this->app->bind(GrandeurRepositoryInterface::class, GrandeurRepository::class);
        $this->app->bind(InstrumentRepositoryInterface::class, InstrumentRepository::class);
        $this->app->bind(MissionRepositoryInterface::class, MissionRepository::class);
        $this->app->bind(MissionOrderRepositoryInterface::class, MissionOrderRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
