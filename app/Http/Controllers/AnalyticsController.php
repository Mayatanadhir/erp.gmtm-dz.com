<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Analytics\StoreForecastRequest;
use App\Http\Requests\Analytics\UpdateForecastRequest;
use App\Models\IncomeForecast;
use App\Services\MissionStatisticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AnalyticsController extends Controller
{
    public function __construct(private readonly MissionStatisticsService $statisticsService) {}

    /**
     * Display the Internal & Analytical Management dashboard.
     */
    public function index(): View
    {
        Gate::authorize('view analytics');

        return view('analytics.index');
    }

    /**
     * Redirect to the Financial Expenses & Charges explorer.
     */
    public function expenses(): RedirectResponse
    {
        return redirect()->route('financial.expenses');
    }

    /**
     * Display the Annual Forecasts explorer.
     */
    public function forecasts(Request $request): View
    {
        Gate::authorize('view annual forecasts');

        $forecasts = IncomeForecast::query()
            ->when($request->filled('year'), fn ($q) => $q->where('year', (int) $request->year))
            ->orderBy('year', 'desc')
            ->paginate(15)
            ->withQueryString();

        $allForecasts = IncomeForecast::all();
        $availableYears = $allForecasts->pluck('year')->sortDesc()->values();
        $stats = [
            'total_years' => $allForecasts->count(),
            'latest_forecast' => $allForecasts->sortByDesc('year')->first(),
            'avg_planned_daily' => $allForecasts->count() > 0
                ? (float) $allForecasts->avg(fn ($f) => $f->planned_daily_rate)
                : 0.0,
        ];

        return view('analytics.forecasts', compact('forecasts', 'stats', 'availableYears'));
    }

    /**
     * Store a newly created annual forecast in storage.
     */
    public function storeForecast(StoreForecastRequest $request): RedirectResponse
    {
        IncomeForecast::create($request->validated());

        return redirect()->route('analytics.forecasts', $request->query())
            ->with('success', __('Forecast created successfully.'));
    }

    /**
     * Update the specified annual forecast in storage.
     */
    public function updateForecast(UpdateForecastRequest $request, IncomeForecast $forecast): RedirectResponse
    {
        $forecast->update($request->validated());

        return redirect()->route('analytics.forecasts', $request->query())
            ->with('success', __('Forecast updated successfully.'));
    }

    /**
     * Remove the specified annual forecast from storage.
     */
    public function destroyForecast(IncomeForecast $forecast): RedirectResponse
    {
        Gate::authorize('delete annual forecasts');

        $forecast->delete();

        return redirect()->route('analytics.forecasts', request()->query())
            ->with('success', __('Forecast deleted successfully.'));
    }

    /**
     * Display the Company Statistics explorer.
     */
    public function statistics(Request $request): View
    {
        Gate::authorize('view company statistics');

        $selectedYear = $request->filled('year') ? (int) $request->year : null;
        $stats = $this->statisticsService->calculateCompanyStats($selectedYear);

        $yearSql = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y', start_date)"
            : 'YEAR(start_date)';

        $availableYears = DB::table('missions')
            ->whereNotNull('start_date')
            ->selectRaw("DISTINCT {$yearSql} as year")
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y);

        return view('analytics.statistics', compact('stats', 'availableYears'));
    }
}
