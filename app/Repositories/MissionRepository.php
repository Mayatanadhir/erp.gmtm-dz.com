<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\MissionRepositoryInterface;
use App\Models\Mission;
use Illuminate\Pagination\LengthAwarePaginator;

class MissionRepository extends BaseRepository implements MissionRepositoryInterface
{
    public function __construct(Mission $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate filtered missions with eager loaded relationships to avoid N+1 queries.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    public function paginateWithFilter(array $filters = [], int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with([
            'site',
            'teamLeaderOrder.employee.user',
            'employees.user',
            'equipments',
        ]);

        if (method_exists($this->model, 'scopeFilter')) {
            $query->filter($filters);
        }

        // Support direct filter parameters if passed
        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('end_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('employees', function ($emp) use ($search) {
                        $emp->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('equipments', function ($eq) use ($search) {
                        $eq->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage, $columns)
            ->withQueryString();
    }

    /**
     * Find a mission by its ID with all associated relations loaded.
     */
    public function findWithDetails(int $id): ?Mission
    {
        /** @var Mission|null */
        return $this->model->newQuery()
            ->with([
                'site',
                'teamLeaderOrder.employee.user',
                'missionOrders.employee.user',
                'missionOrders.vehicle',
                'deployments.equipment',
            ])
            ->find($id);
    }

    /**
     * Find a mission by ID or fail with all relations loaded.
     */
    public function findOrFailWithDetails(int $id): Mission
    {
        /** @var Mission */
        return $this->model->newQuery()
            ->with([
                'site',
                'teamLeaderOrder.employee.user',
                'missionOrders.employee.user',
                'missionOrders.vehicle',
                'deployments.equipment',
            ])
            ->findOrFail($id);
    }

    /**
     * Generate the next atomic sequential mission reference (e.g. M-2026-001).
     * Uses withTrashed() to include soft-deleted missions so references are never reused.
     */
    public function getNextReference(): string
    {
        $year = (int) date('Y');
        $prefix = "M-{$year}-";

        $references = $this->model->newQueryWithoutScopes()
            ->where('reference', 'like', "{$prefix}%")
            ->pluck('reference');

        $maxNumber = 0;
        foreach ($references as $ref) {
            $parts = explode('-', (string) $ref);
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $num = (int) $parts[2];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return sprintf('M-%d-%03d', $year, $nextNumber);
    }
}
