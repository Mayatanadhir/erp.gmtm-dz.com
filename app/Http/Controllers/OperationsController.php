<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Operations\MissionController;
use App\Http\Requests\Operations\StoreItemTypeRequest;
use App\Http\Requests\Operations\StoreWarrantyRequest;
use App\Http\Requests\Operations\UpdateItemTypeRequest;
use App\Http\Requests\Operations\UpdateWarrantyRequest;
use App\Interfaces\ItemTypeRepositoryInterface;
use App\Interfaces\WarrantyRepositoryInterface;
use App\Models\Attachment;
use App\Models\Contract;
use App\Models\ItemType;
use App\Models\Warranty;
use App\Services\ItemTypeService;
use App\Services\WarrantyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OperationsController extends Controller
{
    public function __construct(
        protected WarrantyService $warrantyService,
        protected WarrantyRepositoryInterface $warrantyRepository,
        protected ItemTypeService $itemTypeService,
        protected ItemTypeRepositoryInterface $itemTypeRepository
    ) {}

    /**
     * Display the Operations & Projects dashboard.
     */
    public function index(): View
    {
        Gate::authorize('view operations');

        return view('operations.index');
    }

    /**
     * Display the Mission Management explorer.
     */
    public function missions(Request $request): View
    {
        return app(MissionController::class)->index($request);
    }

    /**
     * Display the Contracts explorer.
     */
    public function contracts(Request $request): View
    {
        return app(ContractController::class)->index($request);
    }

    /**
     * Display the Attachments List explorer.
     */
    public function attachments(Request $request): View
    {
        Gate::authorize('view attachments');

        $query = Attachment::with([
            'mission.site',
            'mission.contract.customer',
            'items.contractItem.itemType',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('code_ref', 'like', "%{$search}%")
                    ->orWhere('ods', 'like', "%{$search}%")
                    ->orWhereHas('mission', function ($mq) use ($search): void {
                        $mq->where('reference', 'like', "%{$search}%")
                            ->orWhereHas('site', fn ($sq) => $sq->where('short_name', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%"))
                            ->orWhereHas('contract', fn ($cq) => $cq->where('reference', 'like', "%{$search}%")->orWhere('object', 'like', "%{$search}%"));
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->input('frequency'));
        }

        if ($request->filled('contract_id')) {
            $query->whereHas('mission', function ($mq) use ($request): void {
                $mq->where('contract_id', $request->input('contract_id'));
            });
        }

        $totalCount = Attachment::count();
        $approvedCount = Attachment::where('status', 'approved')->count();
        $draftCount = Attachment::where('status', 'draft')->count();
        $totalInvoiced = (float) (DB::table('attachment_items')
            ->join('attachments', 'attachment_items.attachment_id', '=', 'attachments.id')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->where('attachments.status', 'approved')
            ->selectRaw('SUM(attachment_items.actual_quantity * contract_items.unit_price) as total')
            ->value('total') ?? 0);

        $stats = [
            'total' => $totalCount,
            'approved' => $approvedCount,
            'draft' => $draftCount,
            'total_invoiced' => $totalInvoiced,
        ];

        $attachments = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();
        $contracts = Contract::active()->orderBy('reference')->get();

        return view('operations.attachments', compact('attachments', 'stats', 'contracts'));
    }

    /**
     * Redirect to the Financial Management Bank Guarantees explorer.
     */
    public function warranties(Request $request): RedirectResponse
    {
        return redirect()->route('financial.warranties', $request->query());
    }

    /**
     * Store a new bank guarantee and redirect to financial management.
     */
    public function storeWarranty(StoreWarrantyRequest $request): RedirectResponse
    {
        $this->warrantyService->createWarranty($request->validated());

        return redirect()
            ->route('financial.warranties', $request->query())
            ->with('success', __('Bank guarantee created successfully.'));
    }

    /**
     * Update an existing bank guarantee and redirect to financial management.
     */
    public function updateWarranty(UpdateWarrantyRequest $request, Warranty $warranty): RedirectResponse
    {
        $this->warrantyService->updateWarranty($warranty, $request->validated());

        return redirect()
            ->route('financial.warranties', $request->query())
            ->with('success', __('Bank guarantee updated successfully.'));
    }

    /**
     * Delete a bank guarantee and redirect to financial management.
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
     * Display the Classification of Articles explorer.
     */
    public function articleTypes(Request $request): View
    {
        Gate::authorize('view article types');

        $itemTypes = $this->itemTypeRepository->paginateWithFilter($request->query());

        return view('operations.article-types', compact('itemTypes'));
    }

    /**
     * Store a newly created article type classification.
     */
    public function storeItemType(StoreItemTypeRequest $request): RedirectResponse
    {
        $this->itemTypeService->createItemType($request->validated());

        return redirect()
            ->route('operations.article-types', $request->query())
            ->with('success', __('Article type created successfully.'));
    }

    /**
     * Update an existing article type classification.
     */
    public function updateItemType(UpdateItemTypeRequest $request, ItemType $itemType): RedirectResponse
    {
        $this->itemTypeService->updateItemType($itemType, $request->validated());

        return redirect()
            ->route('operations.article-types', $request->query())
            ->with('success', __('Article type updated successfully.'));
    }

    /**
     * Delete an article type classification (blocked if in use by contract items).
     */
    public function destroyItemType(Request $request, ItemType $itemType): RedirectResponse
    {
        Gate::authorize('delete article types');

        $deleted = $this->itemTypeService->deleteItemType($itemType);

        if (! $deleted) {
            return redirect()
                ->route('operations.article-types', $request->query())
                ->with('error', __('Cannot delete this article type because it is linked to contract items.'));
        }

        return redirect()
            ->route('operations.article-types', $request->query())
            ->with('success', __('Article type deleted successfully.'));
    }
}
