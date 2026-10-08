<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Operations\StoreWarrantyRequest;
use App\Http\Requests\Operations\UpdateWarrantyRequest;
use App\Interfaces\WarrantyRepositoryInterface;
use App\Models\Warranty;
use App\Services\WarrantyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FinancialController extends Controller
{
    public function __construct(
        protected WarrantyService $warrantyService,
        protected WarrantyRepositoryInterface $warrantyRepository
    ) {}

    /**
     * Display the Financial Management dashboard.
     */
    public function index(): View
    {
        Gate::authorize('view financial');

        return view('Financial.index');
    }

    /**
     * Display the Bank Guarantees explorer.
     */
    public function warranties(Request $request): View
    {
        Gate::authorize('view warranties');

        $filters = $request->only(['search', 'status', 'type']);
        $warranties = $this->warrantyRepository->paginateWithFilter($filters, 15);

        return view('Financial.warranties', compact('warranties'));
    }

    /**
     * Store a new bank guarantee.
     */
    public function storeWarranty(StoreWarrantyRequest $request): RedirectResponse
    {
        $this->warrantyService->createWarranty($request->validated());

        return redirect()
            ->route('financial.warranties', $request->query())
            ->with('success', __('Bank guarantee created successfully.'));
    }

    /**
     * Update an existing bank guarantee.
     */
    public function updateWarranty(UpdateWarrantyRequest $request, Warranty $warranty): RedirectResponse
    {
        $this->warrantyService->updateWarranty($warranty, $request->validated());

        return redirect()
            ->route('financial.warranties', $request->query())
            ->with('success', __('Bank guarantee updated successfully.'));
    }

    /**
     * Delete a bank guarantee.
     */
    public function destroyWarranty(Request $request, Warranty $warranty): RedirectResponse
    {
        Gate::authorize('delete warranties');

        $this->warrantyService->deleteWarranty($warranty);

        return redirect()
            ->route('financial.warranties', $request->query())
            ->with('success', __('Bank guarantee deleted successfully.'));
    }

    /**
     * Display the Expenses & Charges explorer (redirects to dedicated ExpenseController).
     */
    public function expenses(): RedirectResponse
    {
        return redirect()->route('financial.expenses');
    }
}
