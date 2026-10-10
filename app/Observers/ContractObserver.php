<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractActivityNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ContractObserver
{
    /**
     * Notify after commit — fire only once DB transaction is committed.
     */
    public bool $afterCommit = true;

    /**
     * Handle the Contract "created" event.
     */
    public function created(Contract $contract): void
    {
        $this->notifyGroup($contract, 'created');
    }

    /**
     * Handle the Contract "updated" event.
     */
    public function updated(Contract $contract): void
    {
        if ($contract->wasChanged()) {
            $this->notifyGroup($contract, 'updated');
        }
    }

    /**
     * Handle the Contract "deleted" event.
     */
    public function deleted(Contract $contract): void
    {
        $this->notifyGroup($contract, 'deleted');
    }

    // ==========================================
    // Internal helpers
    // ==========================================

    /**
     * Send a database notification to all users who have the 'view contracts' permission.
     */
    private function notifyGroup(Contract $contract, string $event): void
    {
        try {
            $currentUserId = auth()->id();
            $users = User::permission('view contracts')
                ->when($currentUserId, fn ($q) => $q->where('id', '!=', $currentUserId))
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            Notification::send(
                $users,
                new ContractActivityNotification($contract, $event)
            );
        } catch (\Throwable $e) {
            // Notification failure must never break the main transaction
            Log::error(
                'ContractObserver notification failed',
                ['contract_id' => $contract->id, 'event' => $event, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Build the notification payload for the contract.
     *
     * @return array<string, mixed>
     */
    public static function buildPayload(Contract $contract, string $event): array
    {
        $customer = $contract->customer?->company_name ?? '—';
        $warranty = $contract->warranty?->reference ?? '—';

        return [
            'event' => $event,
            'contract' => [
                'id' => $contract->id,
                'reference' => $contract->reference,
                'object' => $contract->object,
                'customer' => $customer,
                'warranty' => $warranty,
                'status' => $contract->expiry_status,
            ],
        ];
    }
}
