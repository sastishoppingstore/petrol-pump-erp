<?php

namespace App\Http\Controllers;

use App\Models\AmanatDeposit;
use App\Models\Customer;
use App\Services\Audit\AuditLogService;
use App\Services\Security\BranchScopeService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Amanat — customer prepaid deposits (امانت).
 *
 * Customers (mostly fleet/commercial) jama karte hain paisa pehle se;
 * har entry ek append-only ledger row hai jisme balance_after frozen
 * hota hai. Balance kabhi overwrite nahi hota — correction hamesha
 * nayi `adjustment` entry se hoti hai.
 */
class AmanatController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Customers of the accessible branches with their amanat balances.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $customersQuery = $this->branchScope->apply(
            Customer::query()->orderBy('name'),
            $user,
        );

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $customersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        $customers = $customersQuery->paginate(20)->withQueryString();

        // Current balance per listed customer = balance_after of their
        // latest entry (fetched in one query, keyed by customer).
        $balances = collect();
        if ($customers->isNotEmpty()) {
            $balances = AmanatDeposit::query()
                ->whereIn('customer_id', $customers->pluck('id'))
                ->orderByDesc('id')
                ->get()
                ->unique('customer_id')
                ->mapWithKeys(fn (AmanatDeposit $entry) => [$entry->customer_id => $entry->balance_after]);
        }

        $stats = $this->getStats($user);

        return view('amanat.index', [
            'customers' => $customers,
            'balances' => $balances,
            'stats' => $stats,
        ]);
    }

    /**
     * Form to record a deposit / deduction / adjustment.
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        $customers = $this->branchScope->apply(
            Customer::query()->where('status', Customer::STATUS_ACTIVE)->orderBy('name'),
            $user,
        )->get();

        return view('amanat.create', [
            'customers' => $customers,
            'selectedCustomerId' => $request->filled('customer_id') ? (int) $request->input('customer_id') : null,
        ]);
    }

    /**
     * Store a new ledger entry. The balance is computed server-side
     * inside a transaction with the customer's latest row locked, so
     * two simultaneous entries can never fork the running balance.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'type' => ['required', 'in:' . implode(',', [
                AmanatDeposit::TYPE_DEPOSIT, AmanatDeposit::TYPE_DEDUCTION, AmanatDeposit::TYPE_ADJUSTMENT,
            ])],
            'amount' => ['required', 'numeric', 'not_in:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // The customer must belong to a branch this user may access.
        $customer = $this->branchScope->apply(Customer::query(), $user)
            ->findOrFail($validated['customer_id']);

        $amount = Money::round((string) $validated['amount']);

        if ($validated['type'] !== AmanatDeposit::TYPE_ADJUSTMENT && Money::isNegative($amount)) {
            return back()->with('error', 'Deposit / deduction amount must be positive. Negative values are only allowed for adjustments.')->withInput();
        }

        try {
            $entry = DB::transaction(function () use ($customer, $validated, $amount, $user) {
                $current = AmanatDeposit::query()
                    ->where('customer_id', $customer->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->value('balance_after');

                $current = $current === null ? '0.00' : Money::round((string) $current);

                $signed = match ($validated['type']) {
                    AmanatDeposit::TYPE_DEPOSIT => $amount,
                    AmanatDeposit::TYPE_DEDUCTION => Money::subtract('0', $amount),
                    default => $amount, // adjustment keeps its entered sign
                };

                $newBalance = Money::add($current, $signed);

                if (Money::isNegative($newBalance)) {
                    throw new \RuntimeException(
                        "Insufficient amanat balance for {$customer->name}. Current balance is Rs. " . number_format((float) $current, 2) . '.'
                    );
                }

                return AmanatDeposit::create([
                    'branch_id' => $customer->branch_id,
                    'customer_id' => $customer->id,
                    'type' => $validated['type'],
                    'amount' => $amount,
                    'balance_after' => $newBalance,
                    'reference' => $validated['reference'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'created_by' => $user->id,
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $this->audit->log(
            $user,
            'amanat.' . $entry->type,
            'Amanat',
            AmanatDeposit::class,
            $entry->id,
            null,
            $entry->only(['customer_id', 'type', 'amount', 'balance_after', 'reference']),
        );

        return redirect()->route('amanat.statement', $customer)
            ->with('success', "Amanat {$entry->type} of Rs. " . number_format((float) $entry->amount, 2) . " recorded for {$customer->name}. New balance: Rs. " . number_format((float) $entry->balance_after, 2) . '.');
    }

    /**
     * Full amanat statement (ledger history) for one customer.
     */
    public function statement(Request $request, Customer $customer): View
    {
        $user = $request->user();

        // Branch isolation: the bound customer must be in scope.
        $this->branchScope->apply(Customer::query(), $user)->findOrFail($customer->id);

        $entries = AmanatDeposit::query()
            ->with('creator')
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $totals = [
            'deposits' => AmanatDeposit::where('customer_id', $customer->id)->where('type', AmanatDeposit::TYPE_DEPOSIT)->sum('amount'),
            'deductions' => AmanatDeposit::where('customer_id', $customer->id)->where('type', AmanatDeposit::TYPE_DEDUCTION)->sum('amount'),
        ];

        return view('amanat.statement', [
            'customer' => $customer,
            'entries' => $entries,
            'balance' => AmanatDeposit::currentBalanceFor($customer->id),
            'totals' => $totals,
        ]);
    }

    /**
     * Header stats for the amanat index (accessible branches only).
     */
    private function getStats($user): array
    {
        $scoped = fn () => $this->branchScope->apply(AmanatDeposit::query(), $user);

        // Latest entry id per customer (fresh builder on every call).
        $latestIds = fn () => $scoped()
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('customer_id');

        $totalBalance = AmanatDeposit::query()
            ->joinSub($latestIds(), 'latest', 'latest.latest_id', '=', 'amanat_deposits.id')
            ->sum('amanat_deposits.balance_after');

        return [
            'total_balance' => $totalBalance,
            'customers_with_balance' => AmanatDeposit::query()
                ->joinSub($latestIds(), 'latest_b', 'latest_b.latest_id', '=', 'amanat_deposits.id')
                ->where('amanat_deposits.balance_after', '>', 0)
                ->count(),
            'deposits_this_month' => $scoped()
                ->where('type', AmanatDeposit::TYPE_DEPOSIT)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'deductions_this_month' => $scoped()
                ->where('type', AmanatDeposit::TYPE_DEDUCTION)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];
    }
}
