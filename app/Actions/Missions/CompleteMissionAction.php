<?php

declare(strict_types=1);

namespace App\Actions\Missions;

use App\Models\Mission;
use App\Services\MissionService;

class CompleteMissionAction
{
    public function __construct(
        protected MissionService $missionService
    ) {}

    /**
     * Complete an active mission and release deployed assets.
     */
    public function execute(Mission $mission): void
    {
        $this->missionService->completeMission($mission);
    }
}
