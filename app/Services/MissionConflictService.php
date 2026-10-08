<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MissionStatus;
use App\Exceptions\MissionConflictException;
use App\Models\Mission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MissionConflictService
{
    /**
     * Verify that no employees or equipment assigned to this mission overlap with another
     * **Active** (running) mission during the same timeframe.
     *
     * - Planned missions are intentionally excluded: multiple missions may be planned
     *   in parallel with shared resources. Conflict is only enforced at activation time.
     * - Completed and Cancelled missions release their resources and must NOT block reuse.
     *
     * @throws MissionConflictException
     */
    public function validateNoConflicts(Mission $mission): void
    {
        $employeeConflicts = [];
        $equipmentConflicts = [];

        $employeeIds = $mission->employees()->pluck('employees.id')->toArray();
        $equipmentIds = $mission->equipments()->pluck('equipment.id')->toArray();

        // Only Active (running) missions block resource reuse.
        // Planned missions may share resources freely — conflict is only enforced at activation.
        // Completed and Cancelled missions release their resources and must NOT block reuse.

        // 1. Check temporal conflicts for employees
        if (! empty($employeeIds) && $mission->start_date && $mission->end_date) {
            /** @var Collection<int, object{full_name: string|null, registration_number: string|null, reference: string}> */
            $busyEmployees = DB::table('mission_orders')
                ->join('missions', 'mission_orders.mission_id', '=', 'missions.id')
                ->join('employees', 'mission_orders.employee_id', '=', 'employees.id')
                ->whereIn('mission_orders.employee_id', $employeeIds)
                ->where('missions.status', MissionStatus::Active->value)
                ->where('missions.id', '!=', $mission->id)
                ->where('missions.start_date', '<=', $mission->end_date)
                ->where('missions.end_date', '>=', $mission->start_date)
                ->whereNull('missions.deleted_at')
                ->whereNull('mission_orders.deleted_at')
                ->select([
                    'employees.full_name',
                    'employees.registration_number',
                    'missions.reference',
                ])
                ->get();

            foreach ($busyEmployees as $busy) {
                $employeeConflicts[] = [
                    'name' => $busy->full_name ?? (string) $busy->registration_number,
                    'mission' => (string) $busy->reference,
                ];
            }
        }

        // 2. Check temporal conflicts for equipment and vehicles
        if (! empty($equipmentIds) && $mission->start_date && $mission->end_date) {
            /** @var Collection<int, object{full_name: string|null, internal_code: string|null, reference: string}> */
            $busyEquipments = DB::table('mission_deployments')
                ->join('missions', 'mission_deployments.mission_id', '=', 'missions.id')
                ->join('equipment', 'mission_deployments.equipment_id', '=', 'equipment.id')
                ->whereIn('mission_deployments.equipment_id', $equipmentIds)
                ->where('missions.status', MissionStatus::Active->value)
                ->where('missions.id', '!=', $mission->id)
                ->where('missions.start_date', '<=', $mission->end_date)
                ->where('missions.end_date', '>=', $mission->start_date)
                ->whereNull('missions.deleted_at')
                ->whereNull('equipment.deleted_at')
                ->select([
                    'equipment.full_name',
                    'equipment.internal_code',
                    'missions.reference',
                ])
                ->get();

            foreach ($busyEquipments as $busy) {
                $equipmentConflicts[] = [
                    'name' => $busy->full_name ?? (string) $busy->internal_code,
                    'mission' => (string) $busy->reference,
                ];
            }
        }

        if (! empty($employeeConflicts) || ! empty($equipmentConflicts)) {
            throw new MissionConflictException($employeeConflicts, $equipmentConflicts);
        }
    }
}
