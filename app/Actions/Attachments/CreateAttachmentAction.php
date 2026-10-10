<?php

declare(strict_types=1);

namespace App\Actions\Attachments;

use App\Models\Attachment;
use App\Models\AttachmentItem;
use App\Models\Mission;
use Illuminate\Support\Facades\DB;

class CreateAttachmentAction
{
    /**
     * Create a new work attachment along with its contractual item consumptions atomically.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Attachment
    {
        return DB::transaction(function () use ($data): Attachment {
            $mission = Mission::findOrFail($data['mission_id']);

            // Safely link mission to contract if not yet assigned
            if (! $mission->contract_id && ! empty($data['contract_id'])) {
                $mission->update(['contract_id' => (int) $data['contract_id']]);
            }

            // Concurrency-safe, sequential generation of code_ref: ATT-GMTM-{YEAR}-{NUM3}
            $codeRef = trim((string) ($data['code_ref'] ?? ''));
            if ($codeRef === '') {
                $year = date('Y', strtotime((string) $data['date']));

                // Query existing references for the given year with row locks
                $existingRefs = Attachment::whereYear('date', $year)
                    ->where('code_ref', 'like', "ATT-GMTM-{$year}-%")
                    ->lockForUpdate()
                    ->pluck('code_ref');

                $maxNumber = 0;
                foreach ($existingRefs as $ref) {
                    if (preg_match('/-(\d+)$/', (string) $ref, $matches)) {
                        $maxNumber = max($maxNumber, (int) $matches[1]);
                    }
                }

                $nextNumber = $maxNumber + 1;
                $codeRef = 'ATT-GMTM-'.$year.'-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            }

            $attachment = Attachment::create([
                'mission_id' => $mission->id,
                'date' => $data['date'],
                'ods' => $data['ods'] ?? null,
                'code_ref' => $codeRef,
                'type' => $data['type'],
                'status' => $data['status'],
                'frequency' => $data['frequency'] ?? null,
            ]);

            $items = (array) ($data['items'] ?? []);
            foreach ($items as $itemData) {
                $actual = (float) ($itemData['actual_quantity'] ?? 0);
                $planned = (float) ($itemData['planned_quantity'] ?? 0);

                if ($actual > 0 || $planned > 0) {
                    AttachmentItem::create([
                        'attachment_id' => $attachment->id,
                        'contract_item_id' => (int) $itemData['contract_item_id'],
                        'actual_quantity' => $actual,
                        'planned_quantity' => $planned,
                    ]);
                }
            }

            return $attachment->load(['items.contractItem', 'mission.site']);
        });
    }
}
