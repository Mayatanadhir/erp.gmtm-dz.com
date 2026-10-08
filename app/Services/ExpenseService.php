<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExpenseAffiliation;
use App\Models\AttachmentItem;
use App\Models\Expense;

class ExpenseService extends BaseService
{
    /**
     * Clean and normalize relation identifiers based on affiliation type.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function cleanData(array $data): array
    {
        $type = $data['type'] instanceof ExpenseAffiliation ? $data['type']->value : (string) ($data['type'] ?? '');

        if ($type !== ExpenseAffiliation::Mission->value) {
            $data['mission_id'] = null;
        }

        if ($type !== ExpenseAffiliation::Contract->value) {
            $data['contract_id'] = null;
        }

        if ($type !== ExpenseAffiliation::Item->value) {
            $data['attachment_item_id'] = null;
            $data['item_type_id'] = null;
        } elseif (! empty($data['attachment_item_id'])) {
            $attachmentItem = AttachmentItem::with('contractItem')->find($data['attachment_item_id']);
            $data['item_type_id'] = $attachmentItem?->contractItem?->item_type_id ?? null;
        }

        if ($type !== ExpenseAffiliation::Gmtm->value) {
            $data['charge_type'] = null;
        }

        return $data;
    }

    /**
     * Create a new expense record within a database transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function createExpense(array $data): Expense
    {
        return $this->executeInTransaction(function () use ($data): Expense {
            return Expense::create($this->cleanData($data));
        });
    }

    /**
     * Update an existing expense record within a database transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        return $this->executeInTransaction(function () use ($expense, $data): Expense {
            $expense->update($this->cleanData($data));

            return $expense->refresh();
        });
    }

    /**
     * Delete an expense record within a database transaction.
     */
    public function deleteExpense(Expense $expense): bool
    {
        return $this->executeInTransaction(function () use ($expense): bool {
            return (bool) $expense->delete();
        });
    }
}
