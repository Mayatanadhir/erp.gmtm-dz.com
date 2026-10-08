<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $mobDemobDays = (int) ($mission->mob_dmob_days ?? 0);
        $operationalDays = max(0, $totalMissionDays - $mobDemobDays);
        $totalDueDays = $operationalDays + $mobDemobDays; // Total due days = operational days + transit/mobility days (worker is on duty during transit)

        // 2. Mission HR Costs & Paid Days (Strict individual employee calculation)
        $detailedOrdersQuery = DB::table('mission_orders')
            ->join('employees', 'employees.id', '=', 'mission_orders.employee_id')
            ->where('mission_orders.mission_id', $mission->id);

        if (Schema::hasColumn('mission_orders', 'deleted_at')) {
            $detailedOrdersQuery->whereNull('mission_orders.deleted_at');
        }

        $detailedOrders = $detailedOrdersQuery->select([
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
            $days = ($order->started_at && $order->ended_at)
                ? (int) Carbon::parse($order->started_at)->diffInDays(Carbon::parse($order->ended_at)) + 1
                : 0;
            $totalAmount = $days * (float) $order->daily_rate;

            $totalPaidDays += $days;
            $totalHrCost += $totalAmount;

            return (object) [
                'employee_id' => $order->employee_id,
                'full_name' => $order->full_name,
                'daily_rate' => (float) $order->daily_rate,
                'started_at' => $order->started_at ? Carbon::parse($order->started_at)->format('Y-m-d') : null,
                'ended_at' => $order->ended_at ? Carbon::parse($order->ended_at)->format('Y-m-d') : null,
                'is_leader' => (bool) $order->is_leader,
                'days_count' => $days,
                'total_amount' => $totalAmount,
            ];
        });

        $assignedStaffCount = $detailedOrders->pluck('employee_id')->unique()->count();
        $dailyTeamRate = 0.0; // Replaced by individual employee entitlements

        // 3. Direct Mission Charges
        $directCharges = 0.0;
        if (Schema::hasTable('charges')) {
            $directCharges = (float) DB::table('charges')
                ->where('mission_id', $mission->id)
                ->sum('amount');
        }

        // 4. Mission Gross Revenue from Attachments
        $totalRevenue = 0.0;
        if (Schema::hasTable('attachments') && Schema::hasTable('attachment_items')) {
            if (Schema::hasTable('contract_items')) {
                $totalRevenue = (float) DB::table('attachment_items')
                    ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                    ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
                    ->where('attachments.mission_id', $mission->id)
                    ->sum(DB::raw('COALESCE(attachment_items.actual_quantity, 0) * contract_items.unit_price'));
            } elseif (Schema::hasTable('item_contracts')) {
                $totalRevenue = (float) DB::table('attachment_items')
                    ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                    ->join('item_contracts', 'item_contracts.id', '=', 'attachment_items.item_contract_id')
                    ->where('attachments.mission_id', $mission->id)
                    ->sum(DB::raw('attachment_items.actual_quantity * item_contracts.unit_price'));
            }
        }

        $totalDepenses = $totalHrCost + $directCharges;
        $grossProfit = $totalRevenue - $totalDepenses;
        $grossMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0.0;
        // Mobility cost & mission transit expenses: Direct Charges + (Mobility Days × Total Daily Team Rates)
        $dailyTeamRate = (float) $detailedOrders->unique('employee_id')->sum('daily_rate');
        $mobilityCost = $directCharges + ($mobDemobDays * $dailyTeamRate);
        $totalExpensesMission = $mobilityCost;

        // 5. Global Annual Expenses & Company Overhead
        $totalChargesForYear = 0.0;
        $globalDirectCharges = 0.0;
        $annualFixedCharges = 0.0;
        $gmtmFixed = 0.0;
        $gmtmVar = 0.0;
        $gmtmTotal = 0.0;

        if (Schema::hasTable('charges')) {
            $totalChargesForYear = (float) DB::table('charges')
                ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                ->sum('amount');
            $globalDirectCharges = (float) DB::table('charges')
                ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                ->whereNotNull('mission_id')
                ->sum('amount');
            $annualFixedCharges = $totalChargesForYear - $globalDirectCharges;

            $chargeTypeCol = Schema::hasColumn('charges', 'charge_type') ? 'charge_type' : (Schema::hasColumn('charges', 'ChargeType') ? 'ChargeType' : null);
            if (Schema::hasColumn('charges', 'type') && $chargeTypeCol) {
                $gmtmFixed = (float) DB::table('charges')
                    ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                    ->where('type', 'gmtm')
                    ->where($chargeTypeCol, 'fixed')
                    ->sum('amount');
                $gmtmVar = (float) DB::table('charges')
                    ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                    ->where('type', 'gmtm')
                    ->where($chargeTypeCol, 'variable')
                    ->sum('amount');
                $gmtmTotal = $gmtmFixed + $gmtmVar;
            }

            if ($gmtmTotal === 0.0 && $annualFixedCharges > 0.0) {
                $gmtmTotal = $annualFixedCharges;
            }
        }

        // Active Employee Annual Payroll
        $monthlyPayroll = 0.0;
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'salary')) {
            $monthlyPayroll = (float) DB::table('employees')
                ->where('status', 'active')
                ->sum('salary');
        }
        $annualPayroll = $monthlyPayroll * 12;

        // Annual Global HR Mission Cost
        $annualOrdersQuery = DB::table('mission_orders')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', ["{$currentYear}-01-01", "{$currentYear}-12-31"]);

        if (Schema::hasColumn('mission_orders', 'deleted_at')) {
            $annualOrdersQuery->whereNull('deleted_at');
        }

        $globalHrCost = 0.0;
        foreach ($annualOrdersQuery->get() as $ord) {
            $d = (int) Carbon::parse($ord->started_at)->diffInDays(Carbon::parse($ord->ended_at)) + 1;
            $globalHrCost += $d * (float) ($ord->daily_rate ?? 0);
        }

        // Annual Calibration Costs
        $annualCalibrationCosts = 0.0;
        if (Schema::hasTable('calibration_certificates') && Schema::hasColumn('calibration_certificates', 'price')) {
            $annualCalibrationCosts = (float) DB::table('calibration_certificates')
                ->whereBetween('calibration_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                ->sum('price');
        }

        // Active Bank Guarantees Total
        $activeGuaranteesTotal = 0.0;
        if (Schema::hasTable('garanties') && Schema::hasColumn('garanties', 'amount')) {
            $activeGuaranteesTotal = (float) DB::table('garanties')
                ->where('status', 'active')
                ->sum('amount');
        }

        $globalTotalDepenses = $annualFixedCharges + $annualPayroll + $globalHrCost + $globalDirectCharges + $annualCalibrationCosts;

        // 6. Annual Global Revenue
        $globalRevenue = 0.0;
        $missionIdsInYear = DB::table('missions')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->pluck('id')
            ->filter()
            ->toArray();

        if (! empty($missionIdsInYear) && Schema::hasTable('attachments') && Schema::hasTable('attachment_items')) {
            if (Schema::hasTable('contract_items')) {
                $globalRevenue = (float) DB::table('attachment_items')
                    ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                    ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
                    ->whereIn('attachments.mission_id', $missionIdsInYear)
                    ->sum(DB::raw('attachment_items.actual_quantity * contract_items.unit_price'));
            } elseif (Schema::hasTable('item_contracts')) {
                $globalRevenue = (float) DB::table('attachment_items')
                    ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                    ->join('item_contracts', 'item_contracts.id', '=', 'attachment_items.item_contract_id')
                    ->whereIn('attachments.mission_id', $missionIdsInYear)
                    ->sum(DB::raw('attachment_items.actual_quantity * item_contracts.unit_price'));
            }
        }

        $globalGrossProfit = $globalRevenue - $globalTotalDepenses;
        $globalGrossMargin = $globalRevenue > 0 ? ($globalGrossProfit / $globalRevenue) * 100 : 0.0;

        // 7. Forecast & Working Days Configuration
        $forecastDays = 0;
        if (Schema::hasTable('income_forecasts') && Schema::hasColumn('income_forecasts', 'expected_work_days')) {
            $forecastDays = (int) DB::table('income_forecasts')
                ->where('year', $currentYear)
                ->value('expected_work_days');
        }

        $missionsThisYearQuery = DB::table('missions')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date');

        if (Schema::hasColumn('missions', 'deleted_at')) {
            $missionsThisYearQuery->whereNull('deleted_at');
        }

        $missionsThisYear = $missionsThisYearQuery->get(['start_date', 'end_date']);

        $totalCompanyWorkingDays = 0;
        foreach ($missionsThisYear as $m) {
            $totalCompanyWorkingDays += (int) Carbon::parse($m->start_date)->diffInDays(Carbon::parse($m->end_date)) + 1;
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

        $totalChargesForYear = 0.0;
        $globalDirectCharges = 0.0;
        $annualFixedCharges = 0.0;
        $gmtmFixed = 0.0;
        $gmtmVar = 0.0;
        $gmtmTotal = 0.0;

        if (Schema::hasTable('charges')) {
            $totalChargesForYear = (float) DB::table('charges')
                ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                ->sum('amount');
            $globalDirectCharges = (float) DB::table('charges')
                ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                ->whereNotNull('mission_id')
                ->sum('amount');
            $annualFixedCharges = $totalChargesForYear - $globalDirectCharges;

            $chargeTypeCol = Schema::hasColumn('charges', 'charge_type') ? 'charge_type' : (Schema::hasColumn('charges', 'ChargeType') ? 'ChargeType' : null);
            if (Schema::hasColumn('charges', 'type') && $chargeTypeCol) {
                $gmtmFixed = (float) DB::table('charges')
                    ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                    ->where('type', 'gmtm')
                    ->where($chargeTypeCol, 'fixed')
                    ->sum('amount');
                $gmtmVar = (float) DB::table('charges')
                    ->whereBetween('date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                    ->where('type', 'gmtm')
                    ->where($chargeTypeCol, 'variable')
                    ->sum('amount');
                $gmtmTotal = $gmtmFixed + $gmtmVar;
            }

            if ($gmtmTotal === 0.0 && $annualFixedCharges > 0.0) {
                $gmtmTotal = $annualFixedCharges;
            }
        }

        $monthlyPayroll = 0.0;
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'salary')) {
            $monthlyPayroll = (float) DB::table('employees')
                ->where('status', 'active')
                ->sum('salary');
        }
        $annualPayroll = $monthlyPayroll * 12;

        $annualOrdersQuery = DB::table('mission_orders')
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', ["{$currentYear}-01-01", "{$currentYear}-12-31"]);

        if (Schema::hasColumn('mission_orders', 'deleted_at')) {
            $annualOrdersQuery->whereNull('deleted_at');
        }

        $globalHrCost = 0.0;
        foreach ($annualOrdersQuery->get() as $ord) {
            $d = (int) Carbon::parse($ord->started_at)->diffInDays(Carbon::parse($ord->ended_at)) + 1;
            $globalHrCost += $d * (float) ($ord->daily_rate ?? 0);
        }

        $annualCalibrationCosts = 0.0;
        if (Schema::hasTable('calibration_certificates') && Schema::hasColumn('calibration_certificates', 'price')) {
            $annualCalibrationCosts = (float) DB::table('calibration_certificates')
                ->whereBetween('calibration_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
                ->sum('price');
        }

        $activeGuaranteesTotal = 0.0;
        if (Schema::hasTable('garanties') && Schema::hasColumn('garanties', 'amount')) {
            $activeGuaranteesTotal = (float) DB::table('garanties')
                ->where('status', 'active')
                ->sum('amount');
        }

        $globalTotalDepenses = $annualFixedCharges + $annualPayroll + $globalHrCost + $globalDirectCharges + $annualCalibrationCosts;

        $missionIdsInYear = DB::table('missions')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->pluck('id')
            ->filter()
            ->toArray();

        $globalRevenue = 0.0;
        if (! empty($missionIdsInYear) && Schema::hasTable('attachments') && Schema::hasTable('attachment_items')) {
            if (Schema::hasTable('contract_items')) {
                $globalRevenue = (float) DB::table('attachment_items')
                    ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                    ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
                    ->whereIn('attachments.mission_id', $missionIdsInYear)
                    ->sum(DB::raw('attachment_items.actual_quantity * contract_items.unit_price'));
            } elseif (Schema::hasTable('item_contracts')) {
                $globalRevenue = (float) DB::table('attachment_items')
                    ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                    ->join('item_contracts', 'item_contracts.id', '=', 'attachment_items.item_contract_id')
                    ->whereIn('attachments.mission_id', $missionIdsInYear)
                    ->sum(DB::raw('attachment_items.actual_quantity * item_contracts.unit_price'));
            }
        }

        $globalGrossProfit = $globalRevenue - $globalTotalDepenses;
        $globalGrossMargin = $globalRevenue > 0 ? ($globalGrossProfit / $globalRevenue) * 100 : 0.0;

        $forecastDays = 0;
        if (Schema::hasTable('income_forecasts') && Schema::hasColumn('income_forecasts', 'expected_work_days')) {
            $forecastDays = (int) DB::table('income_forecasts')
                ->where('year', $currentYear)
                ->value('expected_work_days');
        }

        $missionsThisYearQuery = DB::table('missions')
            ->whereBetween('start_date', ["{$currentYear}-01-01", "{$currentYear}-12-31"])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date');

        if (Schema::hasColumn('missions', 'deleted_at')) {
            $missionsThisYearQuery->whereNull('deleted_at');
        }

        $missionsThisYear = $missionsThisYearQuery->get(['start_date', 'end_date']);

        $totalCompanyWorkingDays = 0;
        foreach ($missionsThisYear as $m) {
            $totalCompanyWorkingDays += (int) Carbon::parse($m->start_date)->diffInDays(Carbon::parse($m->end_date)) + 1;
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

        $missionsDetails = [];
        foreach ($missions as $m) {
            $totalDays = ($m->start_date && $m->end_date)
                ? (int) Carbon::parse($m->start_date)->diffInDays(Carbon::parse($m->end_date)) + 1
                : 0;

            $workersCount = DB::table('mission_orders')->where('mission_id', $m->id)->count();

            $ordList = DB::table('mission_orders')
                ->where('mission_id', $m->id)
                ->whereNotNull('started_at')
                ->whereNotNull('ended_at')
                ->get();

            $hrCost = 0.0;
            foreach ($ordList as $o) {
                $daysCount = (int) Carbon::parse($o->started_at)->diffInDays(Carbon::parse($o->ended_at)) + 1;
                $hrCost += $daysCount * (float) ($o->daily_rate ?? 0);
            }

            $mDirectCharges = 0.0;
            if (Schema::hasTable('charges')) {
                $mDirectCharges = (float) DB::table('charges')->where('mission_id', $m->id)->sum('amount');
            }

            $mExpenses = $hrCost + $mDirectCharges;

            $mRevenue = 0.0;
            if (Schema::hasTable('attachments') && Schema::hasTable('attachment_items')) {
                if (Schema::hasTable('contract_items')) {
                    $mRevenue = (float) DB::table('attachment_items')
                        ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                        ->join('contract_items', 'contract_items.id', '=', 'attachment_items.contract_item_id')
                        ->where('attachments.mission_id', $m->id)
                        ->sum(DB::raw('attachment_items.actual_quantity * contract_items.unit_price'));
                } elseif (Schema::hasTable('item_contracts')) {
                    $mRevenue = (float) DB::table('attachment_items')
                        ->join('attachments', 'attachments.id', '=', 'attachment_items.attachment_id')
                        ->join('item_contracts', 'item_contracts.id', '=', 'attachment_items.item_contract_id')
                        ->where('attachments.mission_id', $m->id)
                        ->sum(DB::raw('attachment_items.actual_quantity * item_contracts.unit_price'));
                }
            }

            $mGrossProfit = $mRevenue - $mExpenses;
            $mNetProfit = $mGrossProfit - ($totalDays * $gmtmExpenseRate);

            $missionsDetails[] = [
                'id' => $m->id,
                'reference' => $m->reference,
                'site_name' => $m->site?->short_name ?? $m->site?->full_name ?? '—',
                'customer_name' => $m->contract?->customer?->short_name ?? $m->contract?->customer?->company_name ?? '—',
                'start_date' => $m->start_date ? Carbon::parse($m->start_date)->format('Y-m-d') : null,
                'end_date' => $m->end_date ? Carbon::parse($m->end_date)->format('Y-m-d') : null,
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
