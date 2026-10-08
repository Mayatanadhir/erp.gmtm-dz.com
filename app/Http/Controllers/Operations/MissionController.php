<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Actions\Missions\ActivateMissionAction;
use App\Actions\Missions\CompleteMissionAction;
use App\Actions\Missions\RevertMissionAction;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentStatus;
use App\Exceptions\MissionConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreMissionRequest;
use App\Http\Requests\Operations\UpdateMissionRequest;
use App\Interfaces\MissionRepositoryInterface;
use App\Interfaces\SiteRepositoryInterface;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Mission;
use App\Services\MissionService;
use App\Services\MissionStatisticsService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MissionController extends Controller
{
    public function __construct(
        protected MissionService $missionService,
        protected MissionRepositoryInterface $missionRepository,
        protected SiteRepositoryInterface $siteRepository
    ) {}

    /**
     * Display a paginated listing of missions.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view missions');

        $filters = $request->only(['search', 'site_id', 'status', 'date_from', 'date_to']);
        $missions = $this->missionRepository->paginateWithFilter($filters, 15);
        $sites = $this->siteRepository->all(['id', 'short_name', 'full_name']);

        return view('operations.missions.index', compact('missions', 'sites', 'filters'));
    }

    /**
     * Show the form for creating a new mission.
     */
    public function create(): View
    {
        Gate::authorize('create missions');

        $contracts = Contract::active()
            ->select('id', 'reference', 'customer_id')
            ->orderBy('reference')
            ->get();

        $sites = $this->siteRepository->all(['id', 'short_name', 'full_name', 'location', 'customer_id']);
        $employees = Employee::with('user:id,profile_photo_path')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'user_id', 'full_name', 'position', 'daily_rate', 'profile_photo_path']);

        $equipments = Equipment::where('status', EquipmentStatus::Active->value)
            ->whereIn('category', [
                EquipmentCategory::MeasuringInstrument->value,
                EquipmentCategory::WorkTool->value,
            ])
            ->orderBy('package')
            ->orderBy('full_name')
            ->get(['id', 'internal_code', 'full_name', 'package', 'image_path']);

        $vehicles = Equipment::where('status', EquipmentStatus::Active->value)
            ->where('category', EquipmentCategory::Vehicle->value)
            ->orderBy('full_name')
            ->get(['id', 'internal_code', 'full_name', 'serial_number', 'image_path']);

        return view('operations.missions.create', compact('sites', 'employees', 'equipments', 'vehicles', 'contracts'));
    }

    /**
     * Store a newly created mission.
     */
    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $mission = $this->missionService->createMission($request->validated());

        return redirect()
            ->route('operations.missions.show', $mission->id)
            ->with('success', __('Mission created successfully.'));
    }

    /**
     * Display the specified mission and its operational team/equipment.
     */
    public function show(int $id): View
    {
        Gate::authorize('view missions');

        $mission = $this->missionRepository->findOrFailWithDetails($id);

        return view('operations.missions.show', compact('mission'));
    }

    /**
     * Show the form for editing the specified mission.
     */
    public function edit(int $id): View|RedirectResponse
    {
        Gate::authorize('edit missions');

        $mission = $this->missionRepository->findOrFailWithDetails($id);

        if (! $mission->status->isModifiable()) {
            return redirect()
                ->route('operations.missions.show', $mission->id)
                ->with('warning', __('Only planned missions can be modified.'));
        }

        $contracts = Contract::where(function ($query) use ($mission): void {
            $query->active();
            if ($mission->contract_id) {
                $query->orWhere('id', $mission->contract_id);
            }
        })
            ->select('id', 'reference', 'customer_id', 'date_signature', 'duree')
            ->orderBy('reference')
            ->get();

        $sites = $this->siteRepository->all(['id', 'short_name', 'full_name', 'location', 'customer_id']);
        $employees = Employee::with('user:id,profile_photo_path')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'user_id', 'full_name', 'position', 'daily_rate', 'profile_photo_path']);

        $equipments = Equipment::where('status', EquipmentStatus::Active->value)
            ->whereIn('category', [
                EquipmentCategory::MeasuringInstrument->value,
                EquipmentCategory::WorkTool->value,
            ])
            ->orderBy('package')
            ->orderBy('full_name')
            ->get(['id', 'internal_code', 'full_name', 'package', 'image_path']);

        $vehicles = Equipment::where('status', EquipmentStatus::Active->value)
            ->where('category', EquipmentCategory::Vehicle->value)
            ->orderBy('full_name')
            ->get(['id', 'internal_code', 'full_name', 'serial_number', 'image_path']);

        $assignedEmployeeIds = $mission->missionOrders->pluck('employee_id')->toArray();
        $chiefId = $mission->teamLeader?->id;
        $vehicleId = $mission->missionOrders->firstWhere('vehicle_id', '!=', null)?->vehicle_id;
        $assignedEquipmentIds = $mission->equipments->where('category', '!=', EquipmentCategory::Vehicle->value)->pluck('id')->toArray();

        return view('operations.missions.edit', compact(
            'mission',
            'sites',
            'employees',
            'equipments',
            'vehicles',
            'contracts',
            'assignedEmployeeIds',
            'chiefId',
            'vehicleId',
            'assignedEquipmentIds'
        ));
    }

    /**
     * Update the specified mission.
     */
    public function update(UpdateMissionRequest $request, int $id): RedirectResponse
    {
        $mission = $this->missionRepository->findOrFail($id);
        $this->missionService->updateMission($mission, $request->validated());

        return redirect()
            ->route('operations.missions.show', $mission->id)
            ->with('success', __('Mission updated successfully.'));
    }

    /**
     * Activate a planned mission after conflict verification.
     */
    public function activate(int $id, ActivateMissionAction $action): RedirectResponse
    {
        Gate::authorize('edit missions');

        $mission = $this->missionRepository->findOrFail($id);

        try {
            $action->execute($mission);

            return redirect()
                ->route('operations.missions.show', $mission->id)
                ->with('success', __('Mission activated successfully.'));
        } catch (MissionConflictException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Exception $e) {
            return back()->with('error', __('Failed to activate mission: ').$e->getMessage());
        }
    }

    /**
     * Complete an active mission and release deployed assets.
     */
    public function complete(int $id, CompleteMissionAction $action): RedirectResponse
    {
        Gate::authorize('edit missions');

        $mission = $this->missionRepository->findOrFail($id);

        try {
            $action->execute($mission);

            return redirect()
                ->route('operations.missions.show', $mission->id)
                ->with('success', __('Mission marked as completed and assets released.'));
        } catch (Exception $e) {
            return back()->with('error', __('Failed to complete mission: ').$e->getMessage());
        }
    }

    /**
     * Revert mission back to planned status.
     */
    public function revert(int $id, RevertMissionAction $action): RedirectResponse
    {
        Gate::authorize('edit missions');

        $mission = $this->missionRepository->findOrFail($id);

        try {
            $action->execute($mission);

            return redirect()
                ->route('operations.missions.show', $mission->id)
                ->with('success', __('Mission reverted back to planned status.'));
        } catch (Exception $e) {
            return back()->with('error', __('Failed to revert mission: ').$e->getMessage());
        }
    }

    /**
     * Remove the specified planned mission.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('delete missions');

        $mission = $this->missionRepository->findOrFail($id);

        try {
            $this->missionService->deleteMission($mission);

            return redirect()
                ->route('operations.missions', $request->query())
                ->with('success', __('Mission deleted successfully.'));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display Unit Economics and financial performance statistics for a mission.
     */
    public function statistics(int $id, Request $request, MissionStatisticsService $statisticsService): View
    {
        Gate::authorize('view missions');

        $mission = $this->missionRepository->findOrFailWithDetails($id);
        $mission->loadMissing(['attachments', 'contract.customer', 'site']);
        $selectedYear = $request->filled('year') ? (int) $request->year : null;
        $stats = $statisticsService->calculate($mission, $selectedYear);

        return view('operations.missions.statistics', compact('mission', 'stats'));
    }

    /**
     * Display the deployed equipment and calibrators manifest for a mission.
     */
    public function equipments(int $id): View
    {
        Gate::authorize('view missions');

        $mission = $this->missionRepository->findOrFailWithDetails($id);
        $mission->loadMissing(['equipments', 'deployments.equipment']);

        return view('operations.missions.equipments-list', compact('mission'));
    }
}
