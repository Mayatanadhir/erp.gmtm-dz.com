<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Contract;
use App\Models\ContractItem;
use Illuminate\Support\Facades\DB;

class UpdateContractAction
{
    /**
     * Update an existing commercial contract and synchronize its contractual line items.
     * Protects items that have recorded attachment consumptions from deletion and price alteration.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data): Contract {
            $contractData = collect($data)->except('items')->toArray();
            $contract->update($contractData);

            // Fetch existing items keyed by id
            $existingItems = $contract->items()
                ->withSum('attachmentItems as total_consumed_qty', 'actual_quantity')
                ->get()
                ->keyBy('id');

            // Identify items that are protected (have attachment_items) — cannot be deleted
            $protectedItemIds = $existingItems
                ->filter(fn ($item) => (float) ($item->total_consumed_qty ?? 0) > 0 || $item->attachmentItems()->exists())
                ->pluck('id')
                ->all();

            $submittedItems = collect($data['items'] ?? []);
            $submittedIds = $submittedItems
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            // Delete items not present in the submitted form and not protected using Eloquent model delete
            $itemsToDelete = $existingItems->whereNotIn('id', array_merge($protectedItemIds, $submittedIds));
            foreach ($itemsToDelete as $itemToDelete) {
                $itemToDelete->delete();
            }

            // Create or update items via Eloquent models
            foreach ($submittedItems as $itemData) {
                if (blank($itemData['designation'] ?? null)) {
                    continue;
                }

                $itemId = ! empty($itemData['id']) ? (int) $itemData['id'] : null;
                $cleanData = collect($itemData)->except('id')->toArray();

                if ($itemId && $existingItems->has($itemId)) {
                    /** @var ContractItem $existingItem */
                    $existingItem = $existingItems->get($itemId);

                    // If item is protected (has consumption):
                    if (in_array($itemId, $protectedItemIds, true)) {
                        $consumedQty = (float) ($existingItem->total_consumed_qty ?? $existingItem->attachmentItems()->sum('actual_quantity'));

                        // Guard 1: Prevent changing unit price on consumed items to protect historical financial integrity
                        $cleanData['unit_price'] = $existingItem->unit_price;

                        // Guard 2: Prevent lowering planned quantity below already consumed quantity
                        if (isset($cleanData['quantity']) && (float) $cleanData['quantity'] < $consumedQty) {
                            $cleanData['quantity'] = (int) ceil($consumedQty);
                        }
                    }

                    $existingItem->update($cleanData);
                } else {
                    $contract->items()->create($cleanData);
                }
            }

            return $contract->fresh(['items']);
        });
    }
}
