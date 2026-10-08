<?php

declare(strict_types=1);

namespace App\Actions\Missions;

use App\Models\Mission;
use App\Services\MissionService;

class ActivateMissionAction
{
    public function __construct(
        protected MissionService $missionService
    ) {}

    /**
     * Activate a planned mission after ensuring resource conflict verification.
     */
    public function execute(Mission $mission): void
    {
        $this->missionService->activateMission($mission);
    }
}
