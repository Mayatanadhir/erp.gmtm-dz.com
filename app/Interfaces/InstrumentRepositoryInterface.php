<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Models\Instrument;
use Illuminate\Pagination\LengthAwarePaginator;

interface InstrumentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate filtered instruments with relations eagerly loaded.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    public function paginateWithFilter(array $filters = [], int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Find instrument by serial number.
     */
    public function findBySerialNumber(string $serialNumber): ?Instrument;

    /**
     * Find instrument by tag number.
     */
    public function findByTagNumber(string $tagNumber): ?Instrument;
}
