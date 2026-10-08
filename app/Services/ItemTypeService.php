<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ItemTypeRepositoryInterface;
use App\Models\ItemType;

class ItemTypeService extends BaseService
{
    public function __construct(
        protected ItemTypeRepositoryInterface $itemTypeRepository
    ) {}

    /**
     * Create a new article type classification.
     *
     * @param  array<string, mixed>  $data
     */
    public function createItemType(array $data): ItemType
    {
        return $this->executeInTransaction(function () use ($data): ItemType {
            /** @var ItemType */
            return $this->itemTypeRepository->create($data);
        });
    }

    /**
     * Update an existing article type classification.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItemType(ItemType $itemType, array $data): ItemType
    {
        return $this->executeInTransaction(function () use ($itemType, $data): ItemType {
            $this->itemTypeRepository->update($itemType->id, $data);

            return $itemType->refresh();
        });
    }

    /**
     * Delete an article type classification if it is not in use by any contract item.
     */
    public function deleteItemType(ItemType $itemType): bool
    {
        if ($itemType->contractItems()->exists()) {
            return false;
        }

        return $this->executeInTransaction(function () use ($itemType): bool {
            return $this->itemTypeRepository->delete($itemType->id);
        });
    }
}
