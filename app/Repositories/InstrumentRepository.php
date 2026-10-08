<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\InstrumentRepositoryInterface;
use App\Models\Instrument;
use Illuminate\Pagination\LengthAwarePaginator;

class InstrumentRepository extends BaseRepository implements InstrumentRepositoryInterface
{
    public function __construct(Instrument $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate filtered instruments with relations eagerly loaded.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    public function paginateWithFilter(array $filters = [], int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with([
                'site',
                'specifications.grandeur',
                'standardGaugeSpecification',
                'proverSpecification',
                'linkedTransmitters',
            ]);

        if (method_exists($this->model, 'scopeFilter')) {
            $query->filter($filters);
        }

        $query->orderBy('id', 'desc');

        return $query->paginate($perPage, $columns)->withQueryString();
    }

    /**
     * Find instrument by serial number.
     */
    public function findBySerialNumber(string $serialNumber): ?Instrument
    {
        /** @var Instrument|null */
        return $this->model->newQuery()->where('serial_number', $serialNumber)->first();
    }

    /**
     * Find instrument by tag number.
     */
    public function findByTagNumber(string $tagNumber): ?Instrument
    {
        /** @var Instrument|null */
        return $this->model->newQuery()->where('tag_number', $tagNumber)->first();
    }
}
