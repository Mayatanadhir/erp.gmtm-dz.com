<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Contracts\CreateContractAction;
use App\Actions\Contracts\UpdateContractAction;
use App\Enums\WarrantyType;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Models\Attachment;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\ItemType;
use App\Models\Warranty;
use App\Services\ContractStatisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

final class ContractController extends Controller
{
    // ==========================================
    // Index — List
    // ==========================================

    public function index(Request $request): View
    {
        Gate::authorize('view contracts');

        $query = Contract::query()
            ->with(['customer', 'items', 'warranty'])
            ->withCount('items');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'LIKE', $search)
                    ->orWhere('object', 'LIKE', $search);
            });
        }

        // Active contracts first, then by newest
        if (DB::getDriverName() === 'mysql') {
            $query->orderByRaw(
                'CASE WHEN DATE_ADD(date_signature, INTERVAL duree MONTH) >= CURDATE() THEN 0 ELSE 1 END ASC, created_at DESC'
            );
        } else {
            $query->orderByDesc('created_at');
        }

        $contracts = $query->paginate(10)->withQueryString();
        $customers = Customer::orderBy('short_name')->get();

        return view('operations.contracts.index', compact('contracts', 'customers'));
    }

    // ==========================================
    // Create / Store
    // ==========================================

    public function create(): View
    {
        Gate::authorize('create contracts');

        $customers = Customer::orderBy('short_name')->get();
        $itemTypes = Schema::hasTable('item_types') ? ItemType::orderBy('designation')->get() : collect([]);
        $warranties = Warranty::where('status', 'active')
            ->whereIn('type', [WarrantyType::Performance, 'garantie_bonne_execution'])
            ->doesntHave('contracts')
            ->orderBy('reference')
            ->get();

        return view('operations.contracts.create', compact('customers', 'itemTypes', 'warranties'));
    }

    public function store(StoreContractRequest $request, CreateContractAction $action): RedirectResponse
    {
        Gate::authorize('create contracts');

        $action->execute($request->validated());

        return redirect()->route('operations.contracts', $request->query())
            ->with('success', __('Contract created successfully.'));
    }

    // ==========================================
    // Show
    // ==========================================

    public function show(Contract $contract): View
    {
        Gate::authorize('view contracts');

        $contract->load([
            'customer',
            'warranty',
            'items' => function ($q): void {
                if (Schema::hasTable('attachment_items')) {
                    $q->withSum('attachmentItems as attachment_items_sum_quantity', 'actual_quantity');
                }
                if (Schema::hasTable('item_types')) {
                    $q->with('itemType');
                }
                $q->orderByRaw("CASE WHEN type = 'service' THEN 0 WHEN type = 'supply' THEN 1 ELSE 2 END ASC")
                    ->orderBy('id', 'asc');
            },
            'missions.site',
        ]);

        if (Schema::hasTable('attachments')) {
            $contract->load('attachments');
        }

        $sortedItems = $contract->items->sortBy([
            fn (ContractItem $a, ContractItem $b): int => (($a->type ?? 'service') === 'supply' ? 1 : 0) <=> (($b->type ?? 'service') === 'supply' ? 1 : 0),
            ['id', 'asc'],
        ])->values();
        $contract->setRelation('items', $sortedItems);

        $totalPlanned = $contract->totalPlanned();
        $totalInvoiced = $contract->totalInvoiced();
        $totalConsumed = $contract->totalConsumed();
        $totalUnconsumed = $contract->totalUnconsumed();
        $rawUnconsumed = $contract->rawTotalUnconsumed();

        $servicesTotalPlanned = $contract->servicesTotalPlanned();
        $suppliesTotalPlanned = $contract->suppliesTotalPlanned();
        $servicesCount = $contract->servicesCount();
        $suppliesCount = $contract->suppliesCount();

        return view('operations.contracts.show', compact(
            'contract',
            'totalPlanned',
            'totalInvoiced',
            'totalConsumed',
            'totalUnconsumed',
            'rawUnconsumed',
            'servicesTotalPlanned',
            'suppliesTotalPlanned',
            'servicesCount',
            'suppliesCount'
        ));
    }

    // ==========================================
    // Edit / Update
    // ==========================================

    public function edit(Contract $contract): View
    {
        Gate::authorize('edit contracts');

        $contract->load(['warranty', 'items' => function ($q): void {
            $q->with('itemType')->withCount('attachmentItems');
        }]);

        $customers = Customer::orderBy('short_name')->get();
        $itemTypes = ItemType::orderBy('designation')->get();
        $warranties = Warranty::where(function ($q): void {
            $q->where('status', 'active')
                ->whereIn('type', [WarrantyType::Performance, 'garantie_bonne_execution'])
                ->doesntHave('contracts');
        })->orWhere('id', $contract->garantie_id)
            ->orderBy('reference')
            ->get();

        return view('operations.contracts.edit', compact('contract', 'customers', 'itemTypes', 'warranties'));
    }

    public function update(UpdateContractRequest $request, Contract $contract, UpdateContractAction $action): RedirectResponse
    {
        Gate::authorize('edit contracts');

        $action->execute($contract, $request->validated());

        return redirect()->route('operations.contracts.show', $contract)
            ->with('success', __('Contract updated successfully.'));
    }

    // ==========================================
    // Delete
    // ==========================================

    public function destroy(Request $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('delete contracts');

        // Guard 1: cannot delete if any attachments exist for this contract's items
        $attachmentsCount = Attachment::whereHas(
            'items',
            fn ($q) => $q->whereIn('contract_item_id', $contract->items()->pluck('id'))
        )->count();

        if ($attachmentsCount > 0) {
            return back()->with(
                'error',
                __('Cannot delete: this contract has :count linked attachment(s).', ['count' => $attachmentsCount])
            );
        }

        // Guard 2: cannot delete if linked missions exist
        $missionsCount = $contract->missions()->count();
        if ($missionsCount > 0) {
            return back()->with(
                'error',
                __('Cannot delete: this contract has :count linked mission(s).', ['count' => $missionsCount])
            );
        }

        // Guard 3: cannot delete if linked expenses/charges exist
        $chargesCount = $contract->charges()->count();
        if ($chargesCount > 0) {
            return back()->with(
                'error',
                __('Cannot delete: this contract has :count linked expense charge(s).', ['count' => $chargesCount])
            );
        }

        DB::transaction(function () use ($contract): void {
            foreach ($contract->items as $item) {
                $item->delete();
            }
            $contract->delete();
        });

        return redirect()->route('operations.contracts', $request->query())
            ->with('success', __('Contract deleted successfully.'));
    }

    // ==========================================
    // Statistics
    // ==========================================

    public function statistics(Contract $contract, ContractStatisticsService $statisticsService): View
    {
        Gate::authorize('view contracts');

        $stats = $statisticsService->calculate($contract);

        return view('operations.contracts.statistics', compact('contract', 'stats'));
    }
}
