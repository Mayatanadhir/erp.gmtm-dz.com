<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Contract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateContractAction
{
    /**
     * Update an existing commercial contract and synchronize its contractual line items.
     * Protects items that have recorded attachment consumptions from deletion.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Contract $contract, array $data): Contract
    {
        return DB::transaction(function () use ($contract, $data): Contract {
            $contractData = collect($data)->except('items')->toArray();
            $contract->update($contractData);

            // Identify items that are protected (have attachment_items) — cannot be deleted
            $protectedItemIds = [];
            if (Schema::hasTable('attachment_items')) {
                $protectedItemIds = DB::table('attachment_items')
                    ->whereIn('contract_item_id', $contract->items()->pluck('id'))
                    ->pluck('contract_item_id')
                    ->unique()
                    ->toArray();
            }

            $submittedItems = collect($data['items'] ?? []);
            $submittedIds = $submittedItems
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->toArray();

            // Delete items not present in the submitted form and not protected
            $contract->items()
                ->whereNotIn('id', array_merge($protectedItemIds, $submittedIds))
                ->delete();

            // Create or update items
            foreach ($submittedItems as $item) {
                if (blank($item['designation'] ?? null)) {
                    continue;
                }

                if (! empty($item['id'])) {
                    $contract->items()
                        ->where('id', $item['id'])
                        ->update(collect($item)->except('id')->toArray());
                } else {
                    $contract->items()->create(collect($item)->except('id')->toArray());
                }
            }

            return $contract->fresh(['items']);
        });
    }
}
