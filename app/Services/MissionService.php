<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MissionDeploymentStatus;
use App\Enums\MissionOrderStatus;
use App\Enums\MissionStatus;
use App\Interfaces\MissionOrderRepositoryInterface;
use App\Interfaces\MissionRepositoryInterface;
use App\Models\Employee;
use App\Models\Mission;
use App\Models\MissionOrder;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class MissionService extends BaseService
{
    public function __construct(
        protected MissionRepositoryInterface $missionRepository,
        protected MissionOrderRepositoryInterface $missionOrderRepository,
        protected MissionConflictService $conflictService
    ) {}

    /**
     * Create a new mission with atomic team assignment and equipment mobilization.
     *
     * @param  array<string, mixed>  $data
     */
    public function createMission(array $data): Mission
    {
        return $this->executeInTransaction(function () use ($data): Mission {
            $reference = $this->missionRepository->getNextReference();

            $site = Site::find($data['site_id']);
            $siteLocation = $site?->location ?? $site?->short_name ?? __('Designated Site');

            /** @var Mission $mission */
            $mission = $this->missionRepository->create([
                'site_id' => $data['site_id'],
                'contract_id' => $data['contract_id'] ?? null,
                'reference' => $reference,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'mob_dmob_days' => $data['mob_dmob_days'] ?? 0,
                'description' => $data['description'] ?? null,
                'status' => MissionStatus::Planned->value,
            ]);

            // 1. Assign employees and create travel orders
            if (! empty($data['employees'])) {
                $employeeIds = [];
                $chiefId = isset($data['chief_id']) ? (int) $data['chief_id'] : null;

                foreach ($data['employees'] as $emp) {
                    if (is_array($emp)) {
                        $empId = (int) ($emp['id'] ?? 0);
                        if ($empId > 0) {
                            $employeeIds[] = $empId;
                            if (! empty($emp['is_leader'])) {
                                $chiefId = $empId;
                            }
                        }
                    } else {
                        $empId = (int) $emp;
                        if ($empId > 0) {
                            $employeeIds[] = $empId;
                        }
                    }
                }

                $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

                foreach ($employeeIds as $empId) {
                    $employee = $employees->get($empId);
                    $address = $employee?->address ? trim((string) $employee->address) : __('Headquarters');
                    $destination = "{$address} - {$siteLocation} - {$address}";

                    $mission->missionOrders()->create([
                        'employee_id' => $empId,
                        'is_leader' => ($empId === $chiefId),
                        'status' => MissionOrderStatus::Active->value,
                        'started_at' => $data['start_date'],
                        'ended_at' => $data['end_date'],
                        'daily_rate' => $employee?->daily_rate ?? 0.00,
                        'destination' => $destination,
                        'vehicle_id' => $data['vehicle_id'] ?? null,
                        'all_vehicles' => false,
                    ]);
                }
            }

            // 2. Mobilize designated transport vehicle
            if (! empty($data['vehicle_id'])) {
                $mission->deployments()->create([
                    'equipment_id' => $data['vehicle_id'],
                    'status' => MissionDeploymentStatus::Active->value,
                    'deployed_at' => $data['start_date'],
                ]);
            }

            // 3. Mobilize technical equipment and calibrators
            if (! empty($data['equipments'])) {
                foreach ($data['equipments'] as $eqId) {
                    if ($eqId != ($data['vehicle_id'] ?? null)) {
                        $mission->deployments()->create([
                            'equipment_id' => (int) $eqId,
                            'status' => MissionDeploymentStatus::Active->value,
                            'deployed_at' => $data['start_date'],
                        ]);
                    }
                }
            }

            return $mission->refresh();
        });
    }

    /**
     * Update an existing mission in planned status.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMission(Mission $mission, array $data): Mission
    {
        return $this->executeInTransaction(function () use ($mission, $data): Mission {
            if (! $mission->status->isModifiable()) {
                throw new InvalidArgumentException(__('Only missions in Planned status can be modified.'));
            }

            $site = Site::find($data['site_id']);
            $siteLocation = $site?->location ?? $site?->short_name ?? __('Designated Site');

            $this->missionRepository->update($mission->id, [
                'site_id' => $data['site_id'],
                'contract_id' => $data['contract_id'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'mob_dmob_days' => $data['mob_dmob_days'] ?? 0,
                'description' => $data['description'] ?? null,
            ]);

            // Sync employees and mission orders
            if (isset($data['employees'])) {
                $employeeIds = [];
                $chiefId = isset($data['chief_id']) ? (int) $data['chief_id'] : null;

                foreach ($data['employees'] as $emp) {
                    if (is_array($emp)) {
                        $empId = (int) $emp['id'];
                        $employeeIds[] = $empId;
                        if (! empty($emp['is_leader'])) {
                            $chiefId = $empId;
                        }
                    } else {
                        $employeeIds[] = (int) $emp;
                    }
                }

                // Remove unassigned orders permanently for planned missions
                $mission->missionOrders()->whereNotIn('employee_id', $employeeIds)->forceDelete();

                $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

                foreach ($employeeIds as $empId) {
                    $employee = $employees->get($empId);
                    $address = $employee?->address ? trim((string) $employee->address) : __('Headquarters');
                    $destination = "{$address} - {$siteLocation} - {$address}";
                    $isLeader = ($empId === $chiefId);

                    $existing = $mission->missionOrders()->withTrashed()->where('employee_id', $empId)->first();
                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update([
                            'is_leader' => $isLeader,
                            'started_at' => $data['start_date'],
                            'ended_at' => $data['end_date'],
                            'vehicle_id' => $data['vehicle_id'] ?? $existing->vehicle_id,
                        ]);
                    } else {
                        $mission->missionOrders()->create([
                            'employee_id' => $empId,
                            'is_leader' => $isLeader,
                            'status' => MissionOrderStatus::Active->value,
                            'started_at' => $data['start_date'],
                            'ended_at' => $data['end_date'],
                            'daily_rate' => $employee?->daily_rate ?? 0.00,
                            'destination' => $destination,
                            'vehicle_id' => $data['vehicle_id'] ?? null,
                        ]);
                    }
                }
            }

            // Sync equipment deployments
            $allEquipmentIds = [];
            if (! empty($data['vehicle_id'])) {
                $allEquipmentIds[] = (int) $data['vehicle_id'];
            }
            if (! empty($data['equipments'])) {
                foreach ($data['equipments'] as $eqId) {
                    $allEquipmentIds[] = (int) $eqId;
                }
            }
            $allEquipmentIds = array_unique($allEquipmentIds);

            $mission->deployments()->whereNotIn('equipment_id', $allEquipmentIds)->delete();
            $existingEqIds = $mission->deployments()->pluck('equipment_id')->toArray();

            foreach ($allEquipmentIds as $eqId) {
                if (! in_array($eqId, $existingEqIds, true)) {
                    $mission->deployments()->create([
                        'equipment_id' => $eqId,
                        'status' => MissionDeploymentStatus::Active->value,
                        'deployed_at' => $data['start_date'],
                    ]);
                }
            }

            return $mission->refresh();
        });
    }

    /**
     * Activate a planned mission after ensuring no resource conflicts.
     */
    public function activateMission(Mission $mission): void
    {
        $this->executeInTransaction(function () use ($mission): void {
            if (! $mission->status->canActivate()) {
                throw new InvalidArgumentException(__('Only planned missions can be activated.'));
            }

            // 1. Validate temporal conflicts
            $this->conflictService->validateNoConflicts($mission);

            // 2. Change state to Active
            $mission->update(['status' => MissionStatus::Active->value]);
            $mission->missionOrders()->update(['status' => MissionOrderStatus::Active->value]);
            $mission->deployments()->update(['status' => MissionDeploymentStatus::Active->value]);
        });
    }

    /**
     * Complete an active mission and release deployed assets.
     */
    public function completeMission(Mission $mission): void
    {
        $this->executeInTransaction(function () use ($mission): void {
            if (! $mission->status->canComplete()) {
                throw new InvalidArgumentException(__('Only active missions can be completed.'));
            }

            $mission->update(['status' => MissionStatus::Completed->value]);
            $mission->missionOrders()->update(['status' => MissionOrderStatus::Completed->value]);
            $mission->deployments()->update([
                'status' => MissionDeploymentStatus::Returned->value,
                'returned_at' => now(),
            ]);
        });
    }

    /**
     * Revert a mission back to Planned status.
     */
    public function revertMission(Mission $mission): void
    {
        $this->executeInTransaction(function () use ($mission): void {
            if (! $mission->status->canRevert()) {
                throw new InvalidArgumentException(__('Only active or completed missions can be reverted.'));
            }

            $mission->update(['status' => MissionStatus::Planned->value]);
            $mission->missionOrders()->update(['status' => MissionOrderStatus::Active->value]);
            $mission->deployments()->update(['status' => MissionDeploymentStatus::Active->value]);
        });
    }

    /**
     * Safely delete a planned mission.
     */
    public function deleteMission(Mission $mission): bool
    {
        return $this->executeInTransaction(function () use ($mission): bool {
            if (! $mission->status->isModifiable()) {
                throw new InvalidArgumentException(__('Only planned missions can be deleted. Active or completed missions cannot be removed.'));
            }

            // Guard against FK RESTRICT violation on reports
            if (Schema::hasTable('reports')) {
                $hasReports = DB::table('reports')->where('mission_id', $mission->id)->exists();
                if ($hasReports) {
                    throw new RuntimeException(__('Cannot delete mission because associated metrological calibration reports exist.'));
                }
            }

            $mission->deployments()->delete();
            $mission->missionOrders()->delete();

            return (bool) $mission->delete();
        });
    }

    /**
     * Update an individual travel order (Ordre de mission).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMissionOrder(MissionOrder $order, array $data): MissionOrder
    {
        return $this->executeInTransaction(function () use ($order, $data): MissionOrder {
            $order->update($data);

            return $order->refresh();
        });
    }
}
