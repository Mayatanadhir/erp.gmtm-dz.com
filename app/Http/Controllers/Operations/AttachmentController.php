<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Actions\Attachments\CreateAttachmentAction;
use App\Actions\Attachments\UpdateAttachmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Http\Requests\Attachment\UpdateAttachmentRequest;
use App\Models\Attachment;
use App\Models\Contract;
use App\Models\Mission;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AttachmentController extends Controller
{
    /**
     * Display a listing of attachments.
     */
    public function index(Request $request): View
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

        // Consolidated single-query count aggregation
        $counts = DB::table('attachments')
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
            ->first();

        $totalCount = (int) ($counts->total ?? 0);
        $approvedCount = (int) ($counts->approved ?? 0);
        $draftCount = (int) ($counts->draft ?? 0);

        $totalInvoiced = (float) (DB::table('attachment_items')
            ->join('attachments', 'attachment_items.attachment_id', '=', 'attachments.id')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->where('attachments.status', 'approved')
            ->selectRaw('SUM(attachment_items.actual_quantity * contract_items.unit_price) as total')
            ->value('total') ?? 0);

        $driver = DB::getDriverName();
        $yearCol = match ($driver) {
            'sqlite' => "strftime('%Y', attachments.date)",
            'pgsql' => "to_char(attachments.date, 'YYYY')",
            default => 'YEAR(attachments.date)',
        };
        $yearlyInvoicedRaw = DB::table('attachment_items')
            ->join('attachments', 'attachment_items.attachment_id', '=', 'attachments.id')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->where('attachments.status', 'approved')
            ->whereNotNull('attachments.date')
            ->selectRaw("{$yearCol} as yr, SUM(attachment_items.actual_quantity * contract_items.unit_price) as total")
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->pluck('total', 'yr')
            ->toArray();

        $yearDate = match ($driver) {
            'sqlite' => "strftime('%Y', date)",
            'pgsql' => "to_char(date, 'YYYY')",
            default => 'YEAR(date)',
        };
        $yearlyCountsRaw = DB::table('attachments')
            ->whereNotNull('date')
            ->selectRaw("{$yearDate} as yr, COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->get()
            ->keyBy('yr');

        $yearlyYears = array_map('intval', array_unique(array_merge(
            array_keys($yearlyInvoicedRaw),
            $yearlyCountsRaw->keys()->map(fn ($k) => (int) $k)->all()
        )));
        $currentYear = (int) date('Y');
        if (! in_array($currentYear, $yearlyYears, true)) {
            array_unshift($yearlyYears, $currentYear);
        }
        rsort($yearlyYears);

        $yearlyInvoicedData = [
            'all' => number_format($totalInvoiced, 2, '.', ' ').' DA',
        ];
        $yearlyTotalData = [
            'all' => $totalCount,
        ];
        $yearlyApprovedData = [
            'all' => $approvedCount,
        ];
        $yearlyDraftData = [
            'all' => $draftCount,
        ];

        foreach ($yearlyYears as $yr) {
            $invAmount = (float) ($yearlyInvoicedRaw[$yr] ?? 0);
            $yearlyInvoicedData[(string) $yr] = number_format($invAmount, 2, '.', ' ').' DA';

            $cRow = $yearlyCountsRaw->get($yr);
            $yearlyTotalData[(string) $yr] = (int) ($cRow->total ?? 0);
            $yearlyApprovedData[(string) $yr] = (int) ($cRow->approved ?? 0);
            $yearlyDraftData[(string) $yr] = (int) ($cRow->draft ?? 0);
        }

        $stats = [
            'total' => $totalCount,
            'approved' => $approvedCount,
            'draft' => $draftCount,
            'total_invoiced' => $totalInvoiced,
            'yearly_invoiced' => $yearlyInvoicedData,
            'yearly_total' => $yearlyTotalData,
            'yearly_approved' => $yearlyApprovedData,
            'yearly_draft' => $yearlyDraftData,
            'yearly_years' => $yearlyYears,
            'current_year' => $currentYear,
        ];

        $attachments = $query->orderByDesc('date')->orderByDesc('id')->paginate(15)->withQueryString();
        $contracts = Contract::active()->orderBy('reference')->get();

        return view('operations.attachments.index', compact('attachments', 'stats', 'contracts'));
    }

    /**
     * Show the form for creating a new attachment.
     */
    public function create(): View
    {
        Gate::authorize('create attachments');

        // Strictly enforce Active Contracts Invariant — expired contracts must never be shown
        $contracts = Contract::active()->with([
            'customer.sites.missions' => fn ($q) => $q->where('status', '!=', 'completed')->with('site'),
            'missions' => fn ($q) => $q->where('status', '!=', 'completed')->with('site'),
            'items.itemType',
        ])->orderBy('reference')->get();

        $contracts->each(function ($contract): void {
            // Only merge missions that belong to THIS contract or have no contract assigned yet
            $siteMissions = $contract->customer?->sites?->flatMap->missions
                ->filter(fn ($m): bool => is_null($m->contract_id) || (int) $m->contract_id === (int) $contract->id) ?? collect();
            $directMissions = $contract->missions ?? collect();
            $merged = $directMissions->concat($siteMissions)->unique('id')->values();
            $contract->setRelation('missions', $merged);
        });

        return view('operations.attachments.create', compact('contracts'));
    }

    /**
     * Store a newly created attachment in storage.
     */
    public function store(StoreAttachmentRequest $request, CreateAttachmentAction $action): RedirectResponse
    {
        Gate::authorize('create attachments');

        $action->execute($request->validated());

        return redirect()->route('operations.attachments', $request->query())
            ->with('success', __('Attachment created successfully.'));
    }

    /**
     * Display the specified attachment.
     */
    public function show(Attachment $attachment): View
    {
        Gate::authorize('view attachments');

        $attachment->load([
            'mission.site',
            'mission.contract.customer',
            'items.contractItem.itemType',
        ]);

        $contract = $attachment->mission?->contract;

        return view('operations.attachments.show', compact('attachment', 'contract'));
    }

    /**
     * Show the form for editing the specified attachment.
     */
    public function edit(Attachment $attachment): View|RedirectResponse
    {
        Gate::authorize('edit attachments');

        if ($attachment->status === 'approved') {
            return redirect()->route('operations.attachments.show', $attachment->id)
                ->with('warning', __('Cannot modify an approved attachment. Please revert it to draft first.'));
        }

        $attachment->load([
            'mission.site',
            'items.contractItem',
        ]);

        $selectedContractId = $attachment->mission?->contract_id
            ?? $attachment->items->first()?->contractItem?->contract_id;

        // If selectedContractId is still null, try finding it via the customer of the mission's site
        if (! $selectedContractId && $attachment->mission?->site?->customer_id) {
            $selectedContractId = Contract::active()
                ->where('customer_id', $attachment->mission->site->customer_id)
                ->orderByDesc('id')
                ->value('id');
        }

        // Enforce Active Contracts Invariant — retain selected contract if already assigned
        $contracts = Contract::where(function ($query) use ($selectedContractId): void {
            $query->active();
            if ($selectedContractId) {
                $query->orWhere('id', $selectedContractId);
            }
        })->with([
            'customer.sites.missions' => fn ($q) => $q->where('status', '!=', 'completed')->with('site'),
            'missions' => fn ($q) => $q->where('status', '!=', 'completed')->with('site'),
            'items.itemType',
        ])->orderBy('reference')->get();

        $contracts->each(function ($contract) use ($attachment, $selectedContractId): void {
            // Only merge missions that belong to THIS contract or have no contract assigned yet
            $siteMissions = $contract->customer?->sites?->flatMap->missions
                ->filter(fn ($m): bool => is_null($m->contract_id) || (int) $m->contract_id === (int) $contract->id) ?? collect();
            $directMissions = $contract->missions ?? collect();
            $merged = $directMissions->concat($siteMissions);

            // Always ensure the attachment's existing mission is present in the list for its contract
            if ($attachment->mission && ((int) $contract->id === (int) $selectedContractId || (int) $contract->customer_id === (int) $attachment->mission->site?->customer_id)) {
                $merged->push($attachment->mission);
            }

            $contract->setRelation('missions', $merged->unique('id')->values());
        });

        return view('operations.attachments.edit', compact('attachment', 'contracts', 'selectedContractId'));
    }

    /**
     * Update the specified attachment in storage.
     */
    public function update(UpdateAttachmentRequest $request, Attachment $attachment, UpdateAttachmentAction $action): RedirectResponse
    {
        Gate::authorize('edit attachments');

        try {
            $action->execute($attachment, $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('operations.attachments.show', $attachment->id)
            ->with('success', __('Attachment updated successfully.'));
    }

    /**
     * Remove the specified attachment from storage.
     */
    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        Gate::authorize('delete attachments');

        if ($attachment->status === 'approved') {
            return back()->with('error', __('Cannot delete an approved attachment. Please revert it to draft first.'));
        }

        if ($attachment->items()->whereHas('charges')->exists()) {
            return back()->with('error', __('Cannot delete attachment because one or more line items have associated expense charges.'));
        }

        DB::transaction(function () use ($attachment): void {
            foreach ($attachment->items as $item) {
                $item->delete();
            }
            $attachment->delete();
        });

        return redirect()->route('operations.attachments', $request->query())
            ->with('success', __('Attachment deleted successfully.'));
    }

    /**
     * Approve the attachment.
     */
    public function updateStatus(Attachment $attachment): RedirectResponse
    {
        Gate::authorize('edit attachments');

        if ($attachment->status === 'approved') {
            return back()->with('info', __('Attachment is already approved.'));
        }

        $attachment->update(['status' => 'approved']);

        return back()->with('success', __('Attachment status updated to Approved successfully.'));
    }

    /**
     * Revert the attachment to draft.
     */
    public function revertToDraft(Attachment $attachment): RedirectResponse
    {
        Gate::authorize('edit attachments');

        if ($attachment->status === 'draft') {
            return back()->with('info', __('Attachment is already in draft status.'));
        }

        $attachment->update(['status' => 'draft']);

        return back()->with('success', __('Attachment reverted to Draft successfully.'));
    }

    /**
     * Printable Voucher / PV view.
     */
    public function printSingle(Attachment $attachment): View
    {
        Gate::authorize('view attachments');

        $attachment->load([
            'mission.site',
            'mission.contract.customer',
            'items.contractItem.itemType',
        ]);

        $contract = $attachment->mission?->contract;

        return view('operations.attachments.print_single', compact('attachment', 'contract'));
    }

    /**
     * Printable Delivery Note (Bon de Livraison) view.
     */
    public function printSingleBL(Attachment $attachment): View
    {
        Gate::authorize('view attachments');

        $attachment->load([
            'mission.site',
            'mission.contract.customer',
            'items.contractItem.itemType',
        ]);

        $contract = $attachment->mission?->contract;

        return view('operations.attachments.print_singleBL', compact('attachment', 'contract'));
    }
}
