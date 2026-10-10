<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Enums\EquipmentCategory;
use App\Enums\EquipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\UpdateMissionOrderRequest;
use App\Interfaces\MissionOrderRepositoryInterface;
use App\Interfaces\MissionRepositoryInterface;
use App\Models\Equipment;
use App\Models\MissionOrder;
use App\Services\MissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MissionOrderController extends Controller
{
    public function __construct(
        protected MissionService $missionService,
        protected MissionRepositoryInterface $missionRepository,
        protected MissionOrderRepositoryInterface $missionOrderRepository
    ) {}

    /**
     * Show the edit form for a specific employee's travel order.
     */
    public function edit(int $missionId, int $orderId): View
    {
        Gate::authorize('edit missions');

        $mission = $this->missionRepository->findOrFailWithDetails($missionId);
        /** @var MissionOrder $order */
        $order = $mission->missionOrders()->with(['employee', 'vehicle'])->findOrFail($orderId);

        $vehicles = Equipment::where('status', EquipmentStatus::Active->value)
            ->where('category', EquipmentCategory::Vehicle->value)
            ->orderBy('full_name')
            ->get(['id', 'internal_code', 'full_name', 'serial_number']);

        return view('operations.missions.orders.edit', compact('mission', 'order', 'vehicles'));
    }

    /**
     * Update an individual travel order.
     */
    public function update(UpdateMissionOrderRequest $request, int $missionId, int $orderId): RedirectResponse
    {
        Gate::authorize('edit missions');

        $mission = $this->missionRepository->findOrFail($missionId);
        /** @var MissionOrder $order */
        $order = $mission->missionOrders()->findOrFail($orderId);

        $this->missionService->updateMissionOrder($order, $request->validated());

        return redirect()
            ->route('operations.missions.show', $mission->id)
            ->with('success', __('Travel order updated successfully.'));
    }

    /**
     * Print official Ordre de Mission document.
     */
    public function print(int $missionId, int $orderId): View
    {
        Gate::authorize('view missions');

        $mission = $this->missionRepository->findOrFailWithDetails($missionId);
        /** @var MissionOrder $order */
        $order = $mission->missionOrders()->with(['employee', 'vehicle'])->findOrFail($orderId);

        // Atomically generate sequential reference if missing (e.g. 001/ALG/26)
        if (blank($order->order_reference)) {
            DB::transaction(function () use ($order): void {
                /** @var MissionOrder|null $lockedOrder */
                $lockedOrder = MissionOrder::where('id', $order->id)->lockForUpdate()->first();
                if ($lockedOrder && blank($lockedOrder->order_reference)) {
                    $year = $lockedOrder->started_at ? (int) $lockedOrder->started_at->year : (int) date('Y');
                    $ref = $this->missionOrderRepository->getNextOrderReference($year);
                    $lockedOrder->update(['order_reference' => $ref]);
                    $order->order_reference = $ref;
                }
            });
            $order->refresh();
        }

        $accompanists = $this->missionOrderRepository->getAccompanistsText($mission->id, $order->employee_id);

        return view('operations.missions.orders.print', compact('mission', 'order', 'accompanists'));
    }
}
