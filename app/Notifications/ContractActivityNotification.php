<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Contract;
use App\Observers\ContractObserver;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContractActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Contract $contract,
        private readonly string $event,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return ContractObserver::buildPayload($this->contract, $this->event);
    }
}
