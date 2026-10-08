<?php

declare(strict_types=1);

namespace App\Interfaces;

interface MissionOrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Generate the next atomic sequential travel order reference (e.g. 001/ALG/26).
     */
    public function getNextOrderReference(int $year): string;

    /**
     * Get accompanists text for a specific employee in a mission.
     */
    public function getAccompanistsText(int $missionId, int $currentEmployeeId): string;
}
