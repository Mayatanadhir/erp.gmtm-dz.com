<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\MissionOrderRepositoryInterface;
use App\Models\MissionOrder;

class MissionOrderRepository extends BaseRepository implements MissionOrderRepositoryInterface
{
    public function __construct(MissionOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * Generate the next atomic sequential travel order reference (e.g. 001/ALG/26).
     */
    public function getNextOrderReference(int $year): string
    {
        $shortYear = (int) substr((string) $year, -2);
        $suffix = "/ALG/{$shortYear}";

        $latest = $this->model->newQuery()
            ->where('order_reference', 'like', "%{$suffix}")
            ->orderByDesc('order_reference')
            ->value('order_reference');

        if ($latest) {
            $parts = explode('/', (string) $latest);
            $lastNumber = isset($parts[0]) ? (int) $parts[0] : 0;
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%03d/ALG/%02d', $nextNumber, $shortYear);
    }

    /**
     * Get accompanists text for a specific employee in a mission.
     */
    public function getAccompanistsText(int $missionId, int $currentEmployeeId): string
    {
        /** @var list<string> */
        $otherEmployees = $this->model->newQuery()
            ->join('employees', 'employees.id', '=', 'mission_orders.employee_id')
            ->where('mission_orders.mission_id', $missionId)
            ->where('mission_orders.employee_id', '!=', $currentEmployeeId)
            ->pluck('employees.full_name')
            ->toArray();

        if (empty($otherEmployees)) {
            return __('Seul');
        }

        return implode(' / ', $otherEmployees);
    }
}
