<?php

declare(strict_types=1);

namespace App\Http\Controllers\Metrology;

use App\Http\Controllers\Controller;
use App\Http\Requests\Metrology\StoreGrandeurRequest;
use App\Http\Requests\Metrology\UpdateGrandeurRequest;
use App\Models\Grandeur;
use App\Services\GrandeurService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GrandeurUnitController extends Controller
{
    public function __construct(
        protected GrandeurService $grandeurService,
    ) {}

    /**
     * Display the Quantities & Units explorer.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view quantities units');

        $grandeurs = $this->grandeurService->getPaginatedGrandeurs($request, 15);
        $stats = $this->grandeurService->getStatistics();

        return view('metrology.units', compact('grandeurs', 'stats'));
    }

    /**
     * Store a newly created physical quantity and unit.
     */
    public function store(StoreGrandeurRequest $request): RedirectResponse
    {
        $this->grandeurService->storeGrandeur($request->validated());

        return redirect()->route('metrology.units', $request->query())
            ->with('success', __('Quantity and unit created successfully.'));
    }

    /**
     * Update the specified physical quantity and unit.
     */
    public function update(UpdateGrandeurRequest $request, Grandeur $grandeur): RedirectResponse
    {
        $this->grandeurService->updateGrandeur($grandeur, $request->validated());

        return redirect()->route('metrology.units', $request->query())
            ->with('success', __('Quantity and unit updated successfully.'));
    }

    /**
     * Remove the specified physical quantity and unit.
     */
    public function destroy(Grandeur $grandeur): RedirectResponse
    {
        Gate::authorize('delete quantities units');

        try {
            $this->grandeurService->deleteGrandeur($grandeur);

            return redirect()->route('metrology.units', request()->query())
                ->with('success', __('Quantity and unit deleted successfully.'));
        } catch (\DomainException $e) {
            return redirect()->route('metrology.units', request()->query())
                ->with('error', $e->getMessage());
        }
    }
}
