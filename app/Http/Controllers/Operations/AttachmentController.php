<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\AttachmentItem;
use App\Models\Contract;
use App\Models\Mission;
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

        $totalCount = Attachment::count();
        $approvedCount = Attachment::where('status', 'approved')->count();
        $draftCount = Attachment::where('status', 'draft')->count();
        $totalInvoiced = (float) (DB::table('attachment_items')
            ->join('attachments', 'attachment_items.attachment_id', '=', 'attachments.id')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->where('attachments.status', 'approved')
            ->selectRaw('SUM(attachment_items.actual_quantity * contract_items.unit_price) as total')
            ->value('total') ?? 0);

        $yearlyInvoicedRaw = DB::table('attachment_items')
            ->join('attachments', 'attachment_items.attachment_id', '=', 'attachments.id')
            ->join('contract_items', 'attachment_items.contract_item_id', '=', 'contract_items.id')
            ->where('attachments.status', 'approved')
            ->whereNotNull('attachments.date')
            ->selectRaw('YEAR(attachments.date) as yr, SUM(attachment_items.actual_quantity * contract_items.unit_price) as total')
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->pluck('total', 'yr')
            ->toArray();

        $yearlyCountsRaw = DB::table('attachments')
            ->whereNotNull('date')
            ->selectRaw("YEAR(date) as yr, COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft")
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

        $contracts->each(function ($contract) {
            $siteMissions = $contract->customer?->sites?->flatMap->missions ?? collect();
            $directMissions = $contract->missions ?? collect();
            $merged = $directMissions->concat($siteMissions)->unique('id')->values();
            $contract->setRelation('missions', $merged);
        });

        return view('operations.attachments.create', compact('contracts'));
    }

    /**
     * Store a newly created attachment in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create attachments');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'mission_id' => ['required', 'exists:missions,id'],
            'ods' => ['nullable', 'string', 'max:100'],
            'code_ref' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:service,supply'],
            'status' => ['required', 'string', 'in:draft,approved'],
            'frequency' => ['nullable', 'string', 'in:annuelle,semestrielle'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.contract_item_id' => ['required', 'exists:contract_items,id'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.planned_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $mission = Mission::findOrFail($validated['mission_id']);

        if (! $mission->contract_id && ! empty($validated['contract_id'])) {
            $mission->update(['contract_id' => (int) $validated['contract_id']]);
        }

        DB::transaction(function () use ($validated, $mission): void {
            // Auto generate code_ref if not provided: ATT-GMTM-{YEAR}-{NUM3}
            $codeRef = trim((string) ($validated['code_ref'] ?? ''));
            if ($codeRef === '') {
                $year = date('Y', strtotime($validated['date']));
                $latest = Attachment::whereYear('date', $year)->orderByDesc('id')->first();
                $nextNumber = 1;
                if ($latest && $latest->code_ref && preg_match('/-(\d+)$/', (string) $latest->code_ref, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }
                $codeRef = 'ATT-GMTM-'.$year.'-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            }

            $attachment = Attachment::create([
                'mission_id' => $mission->id,
                'date' => $validated['date'],
                'ods' => $validated['ods'] ?? null,
                'code_ref' => $codeRef,
                'type' => $validated['type'],
                'status' => $validated['status'],
                'frequency' => $validated['frequency'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                // Only insert if actual quantity or planned quantity > 0
                $actual = (float) ($itemData['actual_quantity'] ?? 0);
                $planned = (float) ($itemData['planned_quantity'] ?? 0);
                if ($actual > 0 || $planned > 0) {
                    AttachmentItem::create([
                        'attachment_id' => $attachment->id,
                        'contract_item_id' => (int) $itemData['contract_item_id'],
                        'actual_quantity' => $actual,
                        'planned_quantity' => $planned,
                    ]);
                }
            }
        });

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
    public function edit(Attachment $attachment): View
    {
        Gate::authorize('edit attachments');

        $attachment->load([
            'mission.site',
            'items.contractItem',
        ]);

        $selectedContractId = $attachment->mission?->contract_id
            ?? $attachment->items->first()?->contractItem?->contract_id;

        // If selectedContractId is still null, try finding it via the customer of the mission's site
        if (! $selectedContractId && $attachment->mission?->site?->customer_id) {
            $selectedContractId = Contract::where('customer_id', $attachment->mission->site->customer_id)->value('id');
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

        $contracts->each(function ($contract) use ($attachment, $selectedContractId) {
            $siteMissions = $contract->customer?->sites?->flatMap->missions ?? collect();
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
    public function update(Request $request, Attachment $attachment): RedirectResponse
    {
        Gate::authorize('edit attachments');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'mission_id' => ['required', 'exists:missions,id'],
            'ods' => ['nullable', 'string', 'max:100'],
            'code_ref' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:service,supply'],
            'status' => ['required', 'string', 'in:draft,approved'],
            'frequency' => ['nullable', 'string', 'in:annuelle,semestrielle'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.contract_item_id' => ['required', 'exists:contract_items,id'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.planned_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $mission = Mission::findOrFail($validated['mission_id']);

        if (! $mission->contract_id && ! empty($validated['contract_id'])) {
            $mission->update(['contract_id' => (int) $validated['contract_id']]);
        }

        DB::transaction(function () use ($validated, $attachment, $mission): void {
            $attachment->update([
                'mission_id' => $mission->id,
                'date' => $validated['date'],
                'ods' => $validated['ods'] ?? null,
                'code_ref' => $validated['code_ref'] ?: $attachment->code_ref,
                'type' => $validated['type'],
                'status' => $validated['status'],
                'frequency' => $validated['frequency'] ?? null,
            ]);

            // Replace line items
            $attachment->items()->delete();

            foreach ($validated['items'] as $itemData) {
                $actual = (float) ($itemData['actual_quantity'] ?? 0);
                $planned = (float) ($itemData['planned_quantity'] ?? 0);
                if ($actual > 0 || $planned > 0) {
                    AttachmentItem::create([
                        'attachment_id' => $attachment->id,
                        'contract_item_id' => (int) $itemData['contract_item_id'],
                        'actual_quantity' => $actual,
                        'planned_quantity' => $planned,
                    ]);
                }
            }
        });

        return redirect()->route('operations.attachments.show', $attachment->id)
            ->with('success', __('Attachment updated successfully.'));
    }

    /**
     * Remove the specified attachment from storage.
     */
    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        Gate::authorize('delete attachments');

        DB::transaction(function () use ($attachment): void {
            $attachment->items()->delete();
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

        $attachment->update(['status' => 'approved']);

        return back()->with('success', __('Attachment status updated to Approved successfully.'));
    }

    /**
     * Revert the attachment to draft.
     */
    public function revertToDraft(Attachment $attachment): RedirectResponse
    {
        Gate::authorize('edit attachments');

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
