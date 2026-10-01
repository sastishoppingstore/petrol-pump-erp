<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPaymentRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerVehicleRequest;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerVehicle;
use App\Services\Customer\CustomerLedgerService;
use App\Services\Notifications\SmsService;
use App\Services\System\SettingService as SystemSettingService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
        // Kul Dene Hain: customers whose balance is negative hold OUR
        // money (advance / overpayment) — total of the negative side.
        $totalPayable = Money::round(Money::subtract(
            '0.00',
            Money::n(Customer::where('current_balance', '<', 0)->sum('current_balance'))
        ));
        $totalCreditLimit = Money::round(Customer::sum('credit_limit'));
        $activeCustomersCount = Customer::where('status', Customer::STATUS_ACTIVE)->count();

        // Ready-made WhatsApp reminder links for the listed customers
        // (one per row/card, only when a phone number exists).
        $whatsappLinks = [];
        foreach ($customers as $listed) {
            if (! empty($listed->phone)) {
                $whatsappLinks[$listed->id] = $this->ledgerService->generateWhatsAppLink($listed);
            }
        }

        return view('customers.index', compact(
            'customers',
            'totalOutstanding',
            'totalPayable',
            'totalCreditLimit',
            'activeCustomersCount',
            'whatsappLinks',
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

        $documents = CustomerDocument::query()
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();

        // Paperless statement sharing: signed public link (30 days) +
        // a WhatsApp message that carries it to the customer's phone.
        $publicStatementUrl = $this->publicStatementUrl($customer);
        $statementWhatsappUrl = ! empty($customer->phone)
            ? $this->ledgerService->whatsappShareUrl($customer, $publicStatementUrl)
            : null;

        return view('customers.show', compact(
            'customer',
            'ledgerEntries',
            'bankAccounts',
            'ageing',
            'whatsappLink',
            'documents',
            'publicStatementUrl',
            'statementWhatsappUrl'
        ));
    }

    /**
     * Signed, login-free statement URL for a customer (30-day expiry).
     * Only unguessable signed URLs are shared — the route itself is
     * guarded by the `signed` middleware.
     */
    protected function publicStatementUrl(Customer $customer): string
    {
        return URL::temporarySignedRoute(
            'customers.statement.public',
            now()->addDays(30),
            ['customer' => $customer->id]
        );
    }

    /**
     * Guest statement page behind the signed link. Read-only, last
     * 90 days, no app layout/sidebar — safe to open on any phone.
     */
    public function publicStatement(Customer $customer): View
    {
        $statementData = $this->ledgerService->generateStatementData(
            $customer,
            today()->subDays(90)->toDateString(),
            today()->toDateString()
        );
        $statementData['station'] = app(SystemSettingService::class)->stationIdentity();

        return view('customers.public-statement', $statementData);
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

        $this->notifyCustomerPaymentBySms($customer, $payment->amount);

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Payment of Rs. {$payment->amount} recorded successfully under receipt #{$payment->payment_number}.");
    }

    /**
     * Optional customer SMS after a payment is recorded. Controlled by
     * the `sms.notify_customer_on_payment` setting (default OFF, so no
     * SMS ever leaves until the admin configures a gateway and turns
     * it on). Never blocks or fails the payment flow.
     */
    protected function notifyCustomerPaymentBySms(Customer $customer, string $amount): void
    {
        try {
            $enabled = (float) (app(SystemSettingService::class)
                ->get('sms.notify_customer_on_payment', '0') ?? '0') > 0;

            if (! $enabled || empty($customer->phone)) {
                return;
            }

            $newBalance = Money::n($customer->fresh()?->current_balance ?? $customer->current_balance);
            $station = app(SystemSettingService::class)->stationIdentity();
            $stationName = $station['station_name_en'] ?? 'Mehar Filling Station';

            $message = "{$stationName}: Payment received — Rs. " . number_format((float) Money::n($amount), 2)
                . " from {$customer->name}. New balance: Rs. " . number_format((float) $newBalance, 2)
                . '. Thank you! ادائیگی موصول ہو گئی — شکریہ';

            app(SmsService::class)->send($customer->phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Customer payment SMS failed: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
            ]);
        }
    }

    /**
     * Upload a customer document (CNIC copy, NTN, …) to the khata.
     * Public disk + validated mime/size, same pattern as the station
     * Document Vault.
     */
    public function storeDocument(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'in:' . implode(',', array_keys(CustomerDocument::categories()))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->file('file')->store('customer-documents', 'public');

        CustomerDocument::create([
            'branch_id' => $customer->branch_id ?? session('active_branch_id'),
            'customer_id' => $customer->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'file_path' => $path,
            'note' => $validated['note'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('status', __('sales.collection.doc_uploaded'));
    }

    public function destroyDocument(Customer $customer, CustomerDocument $document): RedirectResponse
    {
        abort_unless($document->customer_id === $customer->id, 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('status', __('sales.collection.doc_deleted'));
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

        // Paperless sharing controls on the statement screen.
        $statementData['publicStatementUrl'] = $this->publicStatementUrl($customer);
        $statementData['statementWhatsappUrl'] = ! empty($customer->phone)
            ? $this->ledgerService->whatsappShareUrl($customer, $statementData['publicStatementUrl'])
            : null;

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
