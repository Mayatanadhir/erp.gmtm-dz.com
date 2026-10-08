<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\ItemTypeRepositoryInterface;
use App\Models\ItemType;
use Illuminate\Pagination\LengthAwarePaginator;

class ItemTypeRepository extends BaseRepository implements ItemTypeRepositoryInterface
{
    public function __construct(ItemType $model)
    {
        parent::__construct($model);
    }

    /**
     * Paginate item types with contract usage count, ordered alphabetically.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $columns
     */
    public function paginateWithFilter(array $filters = [], int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withCount('contractItems');

        if (method_exists($this->model, 'scopeFilter')) {
            $query->filter($filters);
        }

        $query->orderBy('designation');

        return $query->paginate($perPage, $columns)->withQueryString();
    }
}
