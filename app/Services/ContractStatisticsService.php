<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContractStatisticsService
{
    /**
     * Calculate lifetime financial and operational statistics for a contract.
     *
     * @return array<string, mixed>
     */
    public function calculate(Contract $contract): array
    {
        // Eager load relations if not already loaded
        $contract->loadMissing(['items', 'missions', 'customer', 'warranty']);

        $missions = $contract->missions;
        $missionIds = $missions->pluck('id');
        $itemIds = $contract->items->pluck('id');

        // ── Date / Duration ──────────────────────────────────────────
        $startDate = $contract->date_signature ? Carbon::parse($contract->date_signature) : null;
        $endDate = $startDate ? $startDate->copy()->addMonthsNoOverflow((int) $contract->duree) : null;
        $totalDays = $startDate && $endDate && $endDate->gte($startDate) ? (int) $startDate->diffInDays($endDate) : 0;
        $elapsedDays = $startDate && now()->gte($startDate) ? (int) min(now()->diffInDays($startDate), $totalDays) : 0;
        $remainingDays = $contract->remain_days ?? 0;

        // ── Revenue ──────────────────────────────────────────────────
        $totalRevenue = 0.0;
        if ($itemIds->isNotEmpty()) {
            $totalRevenue = (float) DB::table('attachment_items')
                ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
                ->join('attachments', 'attachment_items.attachment_id', '=', 'attachments.id')
                ->whereIn('attachment_items.contract_item_id', $itemIds)
                ->where('attachments.status', 'approved')
                ->sum(DB::raw('COALESCE(attachment_items.actual_quantity, 0) * contract_items.unit_price'));
        }

        $plannedRevenue = (float) $contract->items->sum(
            fn ($item) => (float) $item->unit_price * (int) $item->quantity
        );

        $consumptionRate = $plannedRevenue > 0
            ? round(($totalRevenue / $plannedRevenue) * 100, 2)
            : 0.0;

        // ── Direct charges & HR costs per mission ────────────────────
        $chargesByMission = collect();
        if ($missionIds->isNotEmpty()) {
            $chargesByMission = DB::table('charges')
                ->whereIn('mission_id', $missionIds)
                ->groupBy('mission_id')
                ->select('mission_id', DB::raw('SUM(amount) as total_charges'))
                ->pluck('total_charges', 'mission_id');
        }

        $orders = collect();
        if ($missionIds->isNotEmpty()) {
            $orders = DB::table('mission_orders')
                ->whereIn('mission_id', $missionIds)
                ->whereNull('deleted_at')
                ->whereNotNull('started_at')
                ->whereNotNull('ended_at')
                ->select(['mission_id', 'daily_rate', 'started_at', 'ended_at'])
                ->get();
        }
        $ordersByMission = $orders->groupBy('mission_id');

        $revenueByMission = collect();
        if ($missionIds->isNotEmpty()) {
            $revenueByMission = DB::table('attachment_items')
                ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
                ->whereIn('attachments.mission_id', $missionIds)
                ->where('attachments.status', 'approved')
                ->groupBy('attachments.mission_id')
                ->select('attachments.mission_id', DB::raw('SUM(COALESCE(attachment_items.actual_quantity, 0) * contract_items.unit_price) as rev'))
                ->pluck('rev', 'mission_id');
        }

        // Detailed mission breakdown with per-mission direct expenses
        $missionsDetail = $missions->map(function ($mission) use ($ordersByMission, $chargesByMission, $revenueByMission) {
            $missionOrders = $ordersByMission->get($mission->id, collect());
            $mHrCost = 0.0;
            foreach ($missionOrders as $order) {
                $oStart = $order->started_at ? Carbon::parse($order->started_at) : null;
                $oEnd = $order->ended_at ? Carbon::parse($order->ended_at) : null;
                $days = ($oStart && $oEnd && $oEnd->gte($oStart))
                    ? (int) $oStart->diffInDays($oEnd) + 1
                    : 0;
                $mHrCost += $days * (float) ($order->daily_rate ?? 0);
            }

            $mDirectCharges = (float) ($chargesByMission->get($mission->id) ?? 0.0);
            $mTotalExpenses = $mHrCost + $mDirectCharges;
            $mRevenue = (float) ($revenueByMission->get($mission->id) ?? 0.0);
            $mGrossProfit = $mRevenue - $mTotalExpenses;
            $mGrossMargin = $mRevenue > 0 ? round(($mGrossProfit / $mRevenue) * 100, 2) : 0.0;

            return [
                'id' => $mission->id,
                'reference' => $mission->reference,
                'start_date' => $mission->start_date?->format('Y-m-d'),
                'end_date' => $mission->end_date?->format('Y-m-d'),
                'mob_days' => (float) ($mission->mob_dmob_days ?? 0.0),
                'status' => $mission->status instanceof \BackedEnum
                    ? $mission->status->value
                    : (string) $mission->status,
                'hr_cost' => $mHrCost,
                'direct_charges' => $mDirectCharges,
                'total_expenses' => $mTotalExpenses,
                'revenue' => $mRevenue,
                'profit' => $mGrossProfit,
                'gross_margin' => $mGrossMargin,
            ];
        })->toArray();

        // ── Aggregate contract-level costs (sum of missions direct expenses) ──
        $totalHrCost = (float) collect($missionsDetail)->sum('hr_cost');
        $missionsDirectCharges = (float) collect($missionsDetail)->sum('direct_charges');

        // Contract-level charges not bound to any specific mission
        $contractOnlyCharges = (float) DB::table('charges')
            ->where('contract_id', $contract->id)
            ->where(function ($q) use ($missionIds) {
                $q->whereNull('mission_id')
                    ->orWhereNotIn('mission_id', $missionIds);
            })
            ->sum('amount');

        $directCharges = $missionsDirectCharges + $contractOnlyCharges;
        $totalExpenses = $totalHrCost + $directCharges;

        // ── Profit ────────────────────────────────────────────────────
        $grossProfit = $totalRevenue - $totalExpenses;
        $grossMargin = $totalRevenue > 0
            ? round(($grossProfit / $totalRevenue) * 100, 2)
            : 0.0;

        // ── Operational ──────────────────────────────────────────────
        $attachmentsCount = $contract->attachmentsCount();

        $totalMobDays = (float) $missions->sum('mob_dmob_days');
        $avgMobDays = $missions->count() > 0
            ? round((float) $missions->avg('mob_dmob_days'), 1)
            : 0.0;

        $avgMobCost = $missions->count() > 0 && $totalMobDays > 0
            ? round($totalHrCost / $totalMobDays, 2)
            : 0.0;

        return [
            'contract_info' => [
                'reference' => $contract->reference,
                'object' => $contract->object,
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
                'total_days' => $totalDays,
                'elapsed_days' => $elapsedDays,
                'remaining_days' => $remainingDays,
                'customer' => $contract->customer?->company_name ?? '—',
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'planned' => $plannedRevenue,
                'consumption_rate' => $consumptionRate,
                'remaining' => max(0.0, $plannedRevenue - $totalRevenue),
            ],
            'costs' => [
                'total_hr' => $totalHrCost,
                'direct_charges' => $directCharges,
                'total_expenses' => $totalExpenses,
            ],
            'profit' => [
                'gross' => $grossProfit,
                'gross_margin' => $grossMargin,
            ],
            'operational' => [
                'missions_count' => $missions->count(),
                'attachments_count' => $attachmentsCount,
                'missions_detail' => $missionsDetail,
                'total_mob_days' => $totalMobDays,
                'average_mob_days' => $avgMobDays,
                'average_mob_cost' => $avgMobCost,
                'average_mission_mob_and_expenses' => $missions->count() > 0
                    ? round($totalExpenses / $missions->count(), 2)
                    : 0.0,
            ],
        ];
    }
}
