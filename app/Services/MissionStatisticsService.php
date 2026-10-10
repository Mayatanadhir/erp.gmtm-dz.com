<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MissionStatisticsService
{
    /**
     * Calculate financial and operational statistics for a given mission.
     * Full unit economics and financial analysis matching legacy app.gmtm-dz.
     *
     * @return array<string, mixed>
     */
    public function calculate(Mission $mission, ?int $year = null): array
    {
        // 1. Operational & Year Configuration (strictly subject to the year the mission took place)
        $startDate = $mission->start_date ? Carbon::parse($mission->start_date) : null;
        $endDate = $mission->end_date ? Carbon::parse($mission->end_date) : null;
        $currentYear = $year ?? ($startDate ? (int) $startDate->year : ($endDate ? (int) $endDate->year : (int) ($mission->created_at?->year ?? date('Y'))));

        $totalMissionDays = 0;
        if ($startDate && $endDate && $endDate->gte($startDate)) {
            $totalMissionDays = (int) $startDate->diffInDays($endDate) + 1;
        }

        $mobDemobDays = (float) ($mission->mob_dmob_days ?? 0.0);
        $operationalDays = max(0.0, (float) $totalMissionDays - $mobDemobDays);
        $totalDueDays = $operationalDays + $mobDemobDays; // Total due days = operational days + transit/mobility days

        // 2. Mission HR Costs & Paid Days (Strict individual employee calculation)
        $detailedOrders = DB::table('mission_orders')
            ->join('employees', 'employees.id', '=', 'mission_orders.employee_id')
            ->where('mission_orders.mission_id', $mission->id)
            ->whereNull('mission_orders.deleted_at')
            ->select([
                'mission_orders.employee_id',
                'employees.full_name',
                'mission_orders.daily_rate',
                'mission_orders.started_at',
                'mission_orders.ended_at',
                'mission_orders.is_leader',
            ])
            ->orderByDesc('mission_orders.is_leader')
            ->orderBy('employees.full_name')
            ->get();

        $totalPaidDays = 0;
        $totalHrCost = 0.0;

        $hrBreakdown = $detailedOrders->map(function ($order) use (&$totalPaidDays, &$totalHrCost) {
            $oStart = $order->started_at ? Carbon::parse($order->started_at) : null;
            $oEnd = $order->ended_at ? Carbon::parse($order->ended_at) : null;
            $days = ($oStart && $oEnd && $oEnd->gte($oStart))
                ? (int) $oStart->diffInDays($oEnd) + 1
                : 0;
            $totalAmount = $days * (float) $order->daily_rate;

            $totalPaidDays += $days;
            $totalHrCost += $totalAmount;

            return (object) [
                'employee_id' => $order->employee_id,
                'full_name' => $order->full_name,
                'daily_rate' => (float) $order->daily_rate,
                'started_at' => $oStart?->format('Y-m-d'),
                'ended_at' => $oEnd?->format('Y-m-d'),
                'is_leader' => (bool) $order->is_leader,
                'days_count' => $days,
                'total_amount' => $totalAmount,
            ];
        });

        $assignedStaffCount = $detailedOrders->pluck('employee_id')->unique()->count();

        // 3. Direct Mission Charges
        $directCharges = (float) DB::table('charges')
            ->where('mission_id', $mission->id)
            ->sum('amount');

        // 4. Mission Gross Revenue from Attachments
        $totalRevenue = (float) DB::table('attachment_items')
            ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
            ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
            ->where('attachments.mission_id', $mission->id)
            ->sum(DB::raw('COALESCE(attachment_items.actual_quantity, 0) * contract_items.unit_price'));

        $totalDepenses = $totalHrCost + $directCharges;
        $grossProfit = $totalRevenue - $totalDepenses;
        $grossMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0.0;

        // Mobility cost & mission transit expenses: Direct Charges + (Mobility Days × Total Daily Team Rates)
        $dailyTeamRate = (float) $detailedOrders->unique('employee_id')->sum('daily_rate');
        $mobilityCost = $directCharges + ($mobDemobDays * $dailyTeamRate);
        $totalExpensesMission = $mobilityCost;

        // 5. Global Annual Expenses & Company Overhead
        $totalChargesForYear = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->sum('amount');
        $globalDirectCharges = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->whereNotNull('mission_id')
            ->sum('amount');
        $annualFixedCharges = $totalChargesForYear - $globalDirectCharges;

        $gmtmFixed = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->where('type', 'gmtm')
            ->where('charge_type', 'fixed')
            ->sum('amount');
        $gmtmVar = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->where('type', 'gmtm')
            ->where('charge_type', 'variable')
            ->sum('amount');
        $gmtmTotal = $gmtmFixed + $gmtmVar;

        if ($gmtmTotal === 0.0 && $annualFixedCharges > 0.0) {
            $gmtmTotal = $annualFixedCharges;
        }

        // Active Employee Annual Payroll
        $monthlyPayroll = (float) DB::table('employees')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->sum('salary');
        $annualPayroll = $monthlyPayroll * 12;

        // Annual Global HR Mission Cost
        $annualOrders = DB::table('mission_orders')
            ->whereNull('deleted_at')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->get();

        $globalHrCost = 0.0;
        foreach ($annualOrders as $ord) {
            $ordStart = Carbon::parse($ord->started_at);
            $ordEnd = Carbon::parse($ord->ended_at);
            $d = $ordEnd->gte($ordStart) ? (int) $ordStart->diffInDays($ordEnd) + 1 : 0;
            $globalHrCost += $d * (float) ($ord->daily_rate ?? 0);
        }

        // Annual Calibration Costs
        $annualCalibrationCosts = (float) DB::table('calibration_certificates')
            ->whereNull('deleted_at')
            ->whereBetween('calibration_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->sum('price');

        // Active Bank Guarantees Total
        $activeGuaranteesTotal = (float) DB::table('garanties')
            ->where('status', 'active')
            ->sum('amount');

        $globalTotalDepenses = $annualFixedCharges + $annualPayroll + $globalHrCost + $globalDirectCharges + $annualCalibrationCosts;

        // 6. Annual Global Revenue
        $missionIdsInYear = DB::table('missions')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->pluck('id')
            ->filter()
            ->toArray();

        $globalRevenue = 0.0;
        if (! empty($missionIdsInYear)) {
            $globalRevenue = (float) DB::table('attachment_items')
                ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
                ->whereIn('attachments.mission_id', $missionIdsInYear)
                ->sum(DB::raw('COALESCE(attachment_items.actual_quantity, 0) * contract_items.unit_price'));
        }

        $globalGrossProfit = $globalRevenue - $globalTotalDepenses;
        $globalGrossMargin = $globalRevenue > 0 ? ($globalGrossProfit / $globalRevenue) * 100 : 0.0;

        // 7. Forecast & Working Days Configuration
        $forecastDays = (int) DB::table('income_forecasts')
            ->where('year', $currentYear)
            ->value('expected_work_days');

        $missionsThisYear = DB::table('missions')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get(['start_date', 'end_date']);

        $totalCompanyWorkingDays = 0;
        foreach ($missionsThisYear as $m) {
            $mStart = Carbon::parse($m->start_date);
            $mEnd = Carbon::parse($m->end_date);
            $totalCompanyWorkingDays += $mEnd->gte($mStart) ? (int) $mStart->diffInDays($mEnd) + 1 : 0;
        }

        $totalMissionsThisYear = $missionsThisYear->count();

        $divisorDays = ($forecastDays > 0) ? $forecastDays : $totalCompanyWorkingDays;
        $gmtmExpenseRate = $divisorDays > 0 ? ($gmtmTotal / $divisorDays) : 0.0;

        // 8. Gross Unit Economics (before GMTM overhead absorption)
        $rateJGmtmAnn = $divisorDays > 0 ? ($globalGrossProfit / $divisorDays) : 0.0;
        // Turnover per day: total revenue ÷ total mission days
        $rateJGeneral = $totalMissionDays > 0 ? ($totalRevenue / $totalMissionDays) : 0.0;
        // Gross daily yield: gross profit ÷ operational days (matches UI tooltip)
        $rateJAvecDepenses = $operationalDays > 0 ? ($grossProfit / $operationalDays) : 0.0;
        // Net daily yield: gross profit ÷ total mission days (matches UI tooltip)
        $rateJReel = $totalMissionDays > 0 ? ($grossProfit / $totalMissionDays) : 0.0;

        // 9. Final Net Profit Calculation (after GMTM overhead absorption)
        $netProfit = $grossProfit - ($totalMissionDays * $gmtmExpenseRate);
        $netMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0.0;

        $operationalRatio = $rateJGmtmAnn > 0 ? ($rateJReel / $rateJGmtmAnn) * 100 : 0.0;

        // Available years for dropdown filter
        $availableYears = DB::table('missions')
            ->whereNull('deleted_at')
            ->whereNotNull('start_date')
            ->selectRaw('DISTINCT '.(DB::getDriverName() === 'sqlite' ? "strftime('%Y', start_date)" : 'YEAR(start_date)').' as yr')
            ->pluck('yr')
            ->filter()
            ->map(fn ($y) => (int) $y)
            ->sortDesc()
            ->values()
            ->toArray();

        if (! in_array($currentYear, $availableYears, true)) {
            $availableYears[] = $currentYear;
            rsort($availableYears);
        }

        return [
            // Flat keys for backwards compatibility and easy Blade access
            'current_year' => $currentYear,
            'available_years' => $availableYears,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalDepenses,
            'gross_profit' => $grossProfit,
            'gross_margin' => round($grossMargin, 2),
            'net_profit' => round($netProfit, 2),
            'net_margin' => round($netMargin, 2),
            'total_mission_days' => $totalMissionDays,
            'total_paid_days' => $totalPaidDays,
            'operational_days' => $operationalDays,
            'mob_dmob_days' => $mobDemobDays,
            'total_due_days' => $totalDueDays,
            'total_hr_cost' => $totalHrCost,
            'daily_team_rate' => $dailyTeamRate,
            'staff_count' => $assignedStaffCount,
            'hr_breakdown' => $hrBreakdown,
            'direct_charges' => $directCharges,
            'total_expenses_mission' => $totalExpensesMission,
            'mobility_cost' => $mobilityCost,
            'rate_j_avec_depenses' => $rateJAvecDepenses,
            'rate_j_reel' => $rateJReel,
            'rate_j_general' => $rateJGeneral,
            'gmtm_expense_rate' => $gmtmExpenseRate,

            // Full nested structure matching legacy app.gmtm-dz
            'company_annual_stats' => [
                'revenue' => $globalRevenue,
                'expenses' => $globalTotalDepenses,
                'gross_profit' => $globalGrossProfit,
                'gross_margin' => $globalGrossMargin,
                'details' => [
                    'mission_charges' => $globalDirectCharges,
                    'contract_charges' => 0.0,
                    'prisma_charges' => 0.0,
                    'direct_charges' => $globalDirectCharges,
                    'mission_hr_cost' => $globalHrCost,
                    'fixed_charges' => $annualFixedCharges,
                    'annual_payroll' => $annualPayroll,
                    'calibration_costs' => $annualCalibrationCosts,
                    'active_guarantees_total' => $activeGuaranteesTotal,
                    'gmtm' => [
                        'fixed' => $gmtmFixed,
                        'variable' => $gmtmVar,
                        'total_expenses' => $gmtmTotal,
                        'daily_rate' => $gmtmExpenseRate,
                        'expense_rate' => $gmtmExpenseRate,
                        'profit_rate' => $divisorDays > 0 ? (($globalRevenue - $gmtmTotal) / $divisorDays) : 0.0,
                    ],
                ],
            ],
            'payroll' => [
                'monthly' => $monthlyPayroll,
                'annual' => $annualPayroll,
            ],
            'days' => [
                'total' => $totalMissionDays,
                'paid' => $totalPaidDays,
                'operational' => $operationalDays,
                'mob_demob' => $mobDemobDays,
                'total_due_days' => $totalDueDays,
                'total_company_working_days' => $totalCompanyWorkingDays,
                'total_missions_this_year' => $totalMissionsThisYear,
                'forecast_days' => $forecastDays,
            ],
            'rates' => [
                'team_daily_rate' => $dailyTeamRate,
                'rate_j_gmtm_ann' => $rateJGmtmAnn,
                'rate_j_avec_depenses' => $rateJAvecDepenses,
                'rate_j_reel' => $rateJReel,
                'rate_j_general' => $rateJGeneral,
            ],
            'costs' => [
                'total_hr' => $totalHrCost,
                'hr_breakdown' => $hrBreakdown,
                'direct_charges' => $directCharges,
                'total_depenses' => $totalDepenses,
                'total_expenses_mission' => $totalExpensesMission,
                'mobility_cost' => $mobilityCost,
            ],
            'revenue' => [
                'total' => $totalRevenue,
            ],
            'profit' => [
                'gross' => $grossProfit,
                'gross_margin' => $grossMargin,
                'net' => $netProfit,
                'net_margin' => $netMargin,
                'operational_ratio' => $operationalRatio,
            ],
        ];
    }

    /**
     * Calculate general company operational & financial statistics for a specified year.
     * Matches calculateCompanyStats from legacy app.gmtm-dz.
     *
     * @return array<string, mixed>
     */
    public function calculateCompanyStats(?int $year = null): array
    {
        $currentYear = $year ?? (int) date('Y');

        $totalChargesForYear = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->sum('amount');
        $globalDirectCharges = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->whereNotNull('mission_id')
            ->sum('amount');
        $annualFixedCharges = $totalChargesForYear - $globalDirectCharges;

        $gmtmFixed = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->where('type', 'gmtm')
            ->where('charge_type', 'fixed')
            ->sum('amount');
        $gmtmVar = (float) DB::table('charges')
            ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->where('type', 'gmtm')
            ->where('charge_type', 'variable')
            ->sum('amount');
        $gmtmTotal = $gmtmFixed + $gmtmVar;

        if ($gmtmTotal === 0.0 && $annualFixedCharges > 0.0) {
            $gmtmTotal = $annualFixedCharges;
        }

        $monthlyPayroll = (float) DB::table('employees')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->sum('salary');
        $annualPayroll = $monthlyPayroll * 12;

        $annualOrders = DB::table('mission_orders')
            ->whereNull('deleted_at')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->get();

        $globalHrCost = 0.0;
        foreach ($annualOrders as $ord) {
            $ordStart = Carbon::parse($ord->started_at);
            $ordEnd = Carbon::parse($ord->ended_at);
            $d = $ordEnd->gte($ordStart) ? (int) $ordStart->diffInDays($ordEnd) + 1 : 0;
            $globalHrCost += $d * (float) ($ord->daily_rate ?? 0);
        }

        $annualCalibrationCosts = (float) DB::table('calibration_certificates')
            ->whereNull('deleted_at')
            ->whereBetween('calibration_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->sum('price');

        $activeGuaranteesTotal = (float) DB::table('garanties')
            ->where('status', 'active')
            ->sum('amount');

        $globalTotalDepenses = $annualFixedCharges + $annualPayroll + $globalHrCost + $globalDirectCharges + $annualCalibrationCosts;

        $missionIdsInYear = DB::table('missions')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->pluck('id')
            ->filter()
            ->toArray();

        $globalRevenue = 0.0;
        if (! empty($missionIdsInYear)) {
            $globalRevenue = (float) DB::table('attachment_items')
                ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
                ->whereIn('attachments.mission_id', $missionIdsInYear)
                ->sum(DB::raw('COALESCE(attachment_items.actual_quantity, 0) * contract_items.unit_price'));
        }

        $globalGrossProfit = $globalRevenue - $globalTotalDepenses;
        $globalGrossMargin = $globalRevenue > 0 ? ($globalGrossProfit / $globalRevenue) * 100 : 0.0;

        $forecastDays = (int) DB::table('income_forecasts')
            ->where('year', $currentYear)
            ->value('expected_work_days');

        $missionsThisYear = DB::table('missions')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get(['start_date', 'end_date']);

        $totalCompanyWorkingDays = 0;
        foreach ($missionsThisYear as $m) {
            $mStart = Carbon::parse($m->start_date);
            $mEnd = Carbon::parse($m->end_date);
            $totalCompanyWorkingDays += $mEnd->gte($mStart) ? (int) $mStart->diffInDays($mEnd) + 1 : 0;
        }

        $totalMissionsThisYear = $missionsThisYear->count();

        $divisorDays = ($forecastDays > 0) ? $forecastDays : $totalCompanyWorkingDays;
        $gmtmExpenseRate = $divisorDays > 0 ? ($gmtmTotal / $divisorDays) : 0.0;
        $rateJGmtmAnn = $divisorDays > 0 ? ($globalGrossProfit / $divisorDays) : 0.0;

        // Details for each mission in the year
        $missions = Mission::with(['site', 'contract.customer'])
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->whereNotNull('start_date')
            ->orderBy('start_date', 'asc')
            ->get();

        $missionIds = $missions->pluck('id')->toArray();

        $ordersByMission = DB::table('mission_orders')
            ->whereIn('mission_id', $missionIds)
            ->whereNull('deleted_at')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->get()
            ->groupBy('mission_id');

        $orderCountsByMission = DB::table('mission_orders')
            ->whereIn('mission_id', $missionIds)
            ->whereNull('deleted_at')
            ->select('mission_id', DB::raw('count(*) as count'))
            ->groupBy('mission_id')
            ->pluck('count', 'mission_id');

        $chargesByMission = DB::table('charges')
            ->whereIn('mission_id', $missionIds)
            ->select('mission_id', DB::raw('sum(amount) as total'))
            ->groupBy('mission_id')
            ->pluck('total', 'mission_id');

        $revenueByMission = DB::table('attachment_items')
            ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
            ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
            ->whereIn('attachments.mission_id', $missionIds)
            ->select('attachments.mission_id', DB::raw('sum(COALESCE(attachment_items.actual_quantity, 0) * contract_items.unit_price) as total'))
            ->groupBy('attachments.mission_id')
            ->pluck('total', 'attachments.mission_id');

        $missionsDetails = [];
        foreach ($missions as $m) {
            $mStart = $m->start_date ? Carbon::parse($m->start_date) : null;
            $mEnd = $m->end_date ? Carbon::parse($m->end_date) : null;
            $totalDays = ($mStart && $mEnd && $mEnd->gte($mStart))
                ? (int) $mStart->diffInDays($mEnd) + 1
                : 0;

            $workersCount = (int) ($orderCountsByMission[$m->id] ?? 0);

            $ordList = $ordersByMission->get($m->id, collect());
            $hrCost = 0.0;
            foreach ($ordList as $o) {
                $oStart = Carbon::parse($o->started_at);
                $oEnd = Carbon::parse($o->ended_at);
                $daysCount = $oEnd->gte($oStart) ? (int) $oStart->diffInDays($oEnd) + 1 : 0;
                $hrCost += $daysCount * (float) ($o->daily_rate ?? 0);
            }

            $mDirectCharges = (float) ($chargesByMission[$m->id] ?? 0.0);
            $mExpenses = $hrCost + $mDirectCharges;
            $mRevenue = (float) ($revenueByMission[$m->id] ?? 0.0);

            $mGrossProfit = $mRevenue - $mExpenses;
            $mNetProfit = $mGrossProfit - ($totalDays * $gmtmExpenseRate);

            $missionsDetails[] = [
                'id' => $m->id,
                'reference' => $m->reference,
                'site_name' => $m->site?->short_name ?? $m->site?->full_name ?? '—',
                'customer_name' => $m->contract?->customer?->short_name ?? $m->contract?->customer?->company_name ?? '—',
                'start_date' => $mStart?->format('Y-m-d'),
                'end_date' => $mEnd?->format('Y-m-d'),
                'total_days' => $totalDays,
                'workers_count' => $workersCount,
                'revenue' => $mRevenue,
                'direct_charges' => $mDirectCharges,
                'hr_cost' => $hrCost,
                'total_expenses' => $mExpenses,
                'gross_profit' => $mGrossProfit,
                'net_profit' => $mNetProfit,
            ];
        }

        return [
            'current_year' => $currentYear,
            'company_annual_stats' => [
                'revenue' => $globalRevenue,
                'expenses' => $globalTotalDepenses,
                'gross_profit' => $globalGrossProfit,
                'gross_margin' => $globalGrossMargin,
                'details' => [
                    'mission_charges' => $globalDirectCharges,
                    'contract_charges' => 0.0,
                    'prisma_charges' => 0.0,
                    'direct_charges' => $globalDirectCharges,
                    'mission_hr_cost' => $globalHrCost,
                    'fixed_charges' => $annualFixedCharges,
                    'annual_payroll' => $annualPayroll,
                    'calibration_costs' => $annualCalibrationCosts,
                    'active_guarantees_total' => $activeGuaranteesTotal,
                    'gmtm' => [
                        'fixed' => $gmtmFixed,
                        'variable' => $gmtmVar,
                        'total_expenses' => $gmtmTotal,
                        'daily_rate' => $gmtmExpenseRate,
                        'expense_rate' => $gmtmExpenseRate,
                        'profit_rate' => $divisorDays > 0 ? (($globalRevenue - $gmtmTotal) / $divisorDays) : 0.0,
                    ],
                ],
            ],
            'payroll' => [
                'monthly' => $monthlyPayroll,
                'annual' => $annualPayroll,
            ],
            'days' => [
                'total_company_working_days' => $totalCompanyWorkingDays,
                'total_missions_this_year' => $totalMissionsThisYear,
                'forecast_days' => $forecastDays,
            ],
            'rates' => [
                'rate_j_gmtm_ann' => $rateJGmtmAnn,
            ],
            'missions_details' => $missionsDetails,
        ];
    }
}
