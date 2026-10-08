<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Mission;
use Illuminate\Pagination\LengthAwarePaginator;

interface MissionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate filtered missions with eager loaded relationships to avoid N+1 queries.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    public function paginateWithFilter(array $filters = [], int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find a mission by its ID with all associated relations loaded.
     */
    public function findWithDetails(int $id): ?Mission;

    /**
     * Find a mission by ID or fail with all relations loaded.
     */
    public function findOrFailWithDetails(int $id): Mission;

    /**
     * Generate the next atomic sequential mission reference (e.g. M-2026-001).
     */
    public function getNextReference(): string;
}
