<?php

declare(strict_types=1);

namespace App\Actions\Missions;

use App\Models\Mission;
use App\Services\MissionService;

class RevertMissionAction
{
    public function __construct(
        protected MissionService $missionService
    ) {}

    /**
     * Revert an active or completed mission back to planned status.
     */
    public function execute(Mission $mission): void
    {
        $this->missionService->revertMission($mission);
    }
}
