<?php

declare(strict_types=1);

namespace App\Actions\Attachments;

use App\Models\Attachment;
use App\Models\AttachmentItem;
use App\Models\Mission;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateAttachmentAction
{
    /**
     * Update an existing work attachment and synchronize its contractual line items in-place.
     * Preserves item primary keys and relational integrity with expense charges.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Attachment $attachment, array $data): Attachment
    {
        if ($attachment->status === 'approved') {
            throw new DomainException(__('Cannot modify an approved attachment. Please revert it to draft first.'));
        }

        return DB::transaction(function () use ($attachment, $data): Attachment {
            $mission = Mission::findOrFail($data['mission_id']);

            if (! $mission->contract_id && ! empty($data['contract_id'])) {
                $mission->update(['contract_id' => (int) $data['contract_id']]);
            }

            $attachment->update([
                'mission_id' => $mission->id,
                'date' => $data['date'],
                'ods' => $data['ods'] ?? null,
                'code_ref' => ! empty($data['code_ref']) ? $data['code_ref'] : $attachment->code_ref,
                'type' => $data['type'],
                'status' => $data['status'],
                'frequency' => $data['frequency'] ?? null,
            ]);

            // Existing line items keyed by contract_item_id
            $existingItems = $attachment->items()->get()->keyBy('contract_item_id');
            $submittedItems = collect($data['items'] ?? []);
            $keepItemIds = [];

            foreach ($submittedItems as $itemData) {
                $actual = (float) ($itemData['actual_quantity'] ?? 0);
                $planned = (float) ($itemData['planned_quantity'] ?? 0);

                if ($actual <= 0 && $planned <= 0) {
                    continue;
                }

                $contractItemId = (int) $itemData['contract_item_id'];

                if ($existingItems->has($contractItemId)) {
                    /** @var AttachmentItem $existingItem */
                    $existingItem = $existingItems->get($contractItemId);
                    $existingItem->update([
                        'actual_quantity' => $actual,
                        'planned_quantity' => $planned,
                    ]);
                    $keepItemIds[] = $existingItem->id;
                } else {
                    $newItem = $attachment->items()->create([
                        'contract_item_id' => $contractItemId,
                        'actual_quantity' => $actual,
                        'planned_quantity' => $planned,
                    ]);
                    $keepItemIds[] = $newItem->id;
                }
            }

            // Remove items no longer submitted, guarding against deleting items with linked charges
            $itemsToDelete = $attachment->items()->whereNotIn('id', $keepItemIds)->get();
            foreach ($itemsToDelete as $itemToDelete) {
                if ($itemToDelete->charges()->exists()) {
                    throw new DomainException(__('Cannot remove line item because it has associated expense charges.'));
                }
                $itemToDelete->delete();
            }

            return $attachment->fresh(['items.contractItem', 'mission.site']);
        });
    }
}
