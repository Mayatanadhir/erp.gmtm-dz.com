<?php

declare(strict_types=1);

namespace App\Http\Controllers\Financial;

use App\Enums\ExpenseAffiliation;
use App\Enums\MissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Financial\StoreExpenseRequest;
use App\Http\Requests\Financial\UpdateExpenseRequest;
use App\Models\AttachmentItem;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Mission;
use App\Services\ExpenseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    /**
     * Display the expenses and charges explorer with annual/affiliation filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view expenses');

        $year = (string) $request->input('year', date('Y'));
        $type = $request->input('type');
        $chargeType = $request->input('charge_type');
        $search = $request->input('search');

        $query = Expense::with([
            'mission:id,reference',
            'contract:id,reference',
            'attachmentItem.attachment:id,ods,code_ref',
            'attachmentItem.contractItem:id,designation',
        ])
            ->when($year !== 'all' && ! empty($year), fn ($q) => $q->whereYear('date', $year))
            ->when($type && $type !== 'all', fn ($q) => $q->where('type', $type))
            ->when(
                $type === ExpenseAffiliation::Gmtm->value && $chargeType && $chargeType !== 'all',
                fn ($q) => $q->where('charge_type', $chargeType)
            )
            ->when($search, function ($q, $search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            });

        $yearlyTotal = (float) (clone $query)->sum('amount');

        $expenses = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('Financial.expenses.index', compact(
            'expenses',
            'yearlyTotal',
            'year',
            'type',
            'chargeType',
            'search'
        ));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(): View
    {
        Gate::authorize('create expenses');

        $missions = Mission::where('status', '!=', MissionStatus::Completed->value)
            ->select('id', 'reference')
            ->orderBy('reference')
            ->get();

        $contracts = Contract::active()
            ->select('id', 'reference')
            ->orderBy('reference')
            ->get();

        $attachmentItems = AttachmentItem::whereHas('attachment', function ($query): void {
            $query->where('status', '!=', 'approved');
        })
            ->with(['attachment:id,ods,code_ref', 'contractItem:id,designation'])
            ->get();

        return view('Financial.expenses.create', compact('missions', 'contracts', 'attachmentItems'));
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Gate::authorize('create expenses');

        $this->expenseService->createExpense($request->validated());

        return redirect()
            ->route('financial.expenses', $request->query())
            ->with('success', __('Expense created successfully.'));
    }

    /**
     * Display the specified expense details.
     */
    public function show(Expense $expense): View
    {
        Gate::authorize('view expenses');

        $expense->load([
            'mission',
            'contract',
            'attachmentItem.attachment',
            'attachmentItem.contractItem',
            'itemType',
        ]);

        return view('Financial.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense): View
    {
        Gate::authorize('edit expenses');

        $missions = Mission::where('status', '!=', MissionStatus::Completed->value)
            ->select('id', 'reference')
            ->orderBy('reference')
            ->get();

        $contracts = Contract::active()
            ->select('id', 'reference')
            ->orderBy('reference')
            ->get();

        $attachmentItems = AttachmentItem::whereHas('attachment', function ($query): void {
            $query->where('status', '!=', 'approved');
        })
            ->with(['attachment:id,ods,code_ref', 'contractItem:id,designation'])
            ->get();

        return view('Financial.expenses.edit', compact('expense', 'missions', 'contracts', 'attachmentItems'));
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('edit expenses');

        $this->expenseService->updateExpense($expense, $request->validated());

        return redirect()
            ->route('financial.expenses', $request->query())
            ->with('success', __('Expense updated successfully.'));
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('delete expenses');

        $this->expenseService->deleteExpense($expense);

        return redirect()
            ->route('financial.expenses', $request->query())
            ->with('success', __('Expense deleted successfully.'));
    }
}
