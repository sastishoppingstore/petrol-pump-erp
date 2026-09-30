<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPaymentRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerVehicleRequest;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Services\Customer\CustomerLedgerService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerLedgerService $ledgerService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Customer::query()
            ->withCount('vehicles')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('cnic', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('name');

        $customers = $query->paginate(20)->withQueryString();

        $totalOutstanding = Money::round(Customer::sum('current_balance'));
        $totalCreditLimit = Money::round(Customer::sum('credit_limit'));
        $activeCustomersCount = Customer::where('status', Customer::STATUS_ACTIVE)->count();

        return view('customers.index', compact(
            'customers',
            'totalOutstanding',
            'totalCreditLimit',
            'activeCustomersCount',
            'search',
            'status'
        ));
    }

    public function create(): View
    {
        $nextNumber = Customer::max('id') + 1;
        $suggestedCode = 'CUST-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        return view('customers.create', compact('suggestedCode'));
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $openingBalance = Money::round(Money::n($data['opening_balance'] ?? '0.00'));
        $data['opening_balance'] = $openingBalance;
        $data['current_balance'] = $openingBalance;
        $data['branch_id'] = session('active_branch_id');

        $customer = Customer::create($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Customer {$customer->name} ({$customer->code}) created successfully.");
    }

    public function show(Customer $customer): View
    {
        $customer->load(['vehicles', 'payments.bankAccount', 'payments.creator']);

        $ledgerEntries = $customer->ledgerEntries()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'ledger_page');

        $bankAccounts = BankAccount::where('status', 'ACTIVE')->get();
        $ageing = $this->ledgerService->getCustomerAgeing($customer);
        $whatsappLink = $this->ledgerService->generateWhatsAppLink($customer);

        return view('customers.show', compact(
            'customer',
            'ledgerEntries',
            'bankAccounts',
            'ageing',
            'whatsappLink'
        ));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Customer {$customer->name} updated successfully.");
    }

    public function addVehicle(CustomerVehicleRequest $request, Customer $customer): RedirectResponse
    {
        $customer->vehicles()->create($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'Vehicle added successfully.');
    }

    public function removeVehicle(Customer $customer, CustomerVehicle $vehicle): RedirectResponse
    {
        if ($vehicle->customer_id === $customer->id) {
            $vehicle->delete();
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'Vehicle removed.');
    }

    public function recordPayment(CustomerPaymentRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $data['branch_id'] = session('active_branch_id') ?? $customer->branch_id ?? 1;

        $payment = $this->ledgerService->recordPayment($customer, $data, auth()->id());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Payment of Rs. {$payment->amount} recorded successfully under receipt #{$payment->payment_number}.");
    }

    public function statement(Request $request, Customer $customer): View|\Illuminate\Http\Response
    {
        $startDate = $request->query('start_date', today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $statementData = $this->ledgerService->generateStatementData($customer, $startDate, $endDate);

        if ($request->query('format') === 'pdf' || $request->boolean('pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('customers.statement_pdf', $statementData)
                ->setPaper('a4', 'portrait');

            return $pdf->download("Customer_Statement_{$customer->code}_{$startDate}_to_{$endDate}.pdf");
        }

        return view('customers.statement', $statementData);
    }

    public function statementPdf(Request $request, Customer $customer): \Illuminate\Http\Response
    {
        $startDate = $request->query('start_date', today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', today()->toDateString());

        $statementData = $this->ledgerService->generateStatementData($customer, $startDate, $endDate);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('customers.statement_pdf', $statementData)
            ->setPaper('a4', 'portrait');

        return $pdf->download("Customer_Statement_{$customer->code}_{$startDate}_to_{$endDate}.pdf");
    }

    public function ageingReport(Request $request): View
    {
        $branchId = session('active_branch_id');
        $report = $this->ledgerService->getAgeingReport($branchId);

        return view('customers.ageing', [
            'rows' => $report['rows'],
            'totals' => $report['totals'],
        ]);
    }
}
