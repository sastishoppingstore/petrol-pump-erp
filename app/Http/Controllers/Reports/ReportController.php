<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AutoReport;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Shift;
use App\Models\Supplier;
use App\Services\Admin\SettingsService;
use App\Services\Report\ReportService;
use App\Services\Reports\FleetSettlementService;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\ReportGenerationService;
use App\Services\System\SettingService as SystemSettingService;
use App\Services\Security\BranchScopeService;
use App\Support\PakistaniCurrency;
use App\Support\PermissionList;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private static array $reportMetadata = [
        // 1. Sales
        'sales' => ['title' => 'Sales Summary & Detail', 'urdu' => 'سیلز سمری اور تفصیل', 'group' => 'Sales'],
        'fuel-sales' => ['title' => 'Fuel Sales by Product', 'urdu' => 'ایندھن فروخت بلحاظ مصنوعات', 'group' => 'Sales'],
        'nozzle-sales' => ['title' => 'Nozzle Sales Report', 'urdu' => 'نوزل فروخت رپورٹ', 'group' => 'Sales'],
        'meter-reading' => ['title' => 'Meter Reading Audit', 'urdu' => 'میٹر ریڈنگ آڈٹ', 'group' => 'Sales'],
        'cashier-performance' => ['title' => 'Cashier Performance', 'urdu' => 'کیشیئر کارکردگی رپورٹ', 'group' => 'Sales'],

        // 2. Cash & Bank
        'cash-book' => ['title' => 'Cash Book', 'urdu' => 'کیش بک (روکڑ)', 'group' => 'Cash & Bank'],
        'bank-book' => ['title' => 'Bank Book', 'urdu' => 'بینک بک', 'group' => 'Cash & Bank'],
        'cheque-register' => ['title' => 'Cheque Register', 'urdu' => 'چیک رجسٹر', 'group' => 'Cash & Bank'],
        'fleet-settlement' => ['title' => 'Fleet Card Settlement', 'urdu' => 'فلیٹ کارڈ سیٹلمنٹ', 'group' => 'Cash & Bank'],
        'daily-closing' => ['title' => 'Daily Closing History', 'urdu' => 'روزانہ اختتامی ریکارڈ', 'group' => 'Cash & Bank'],

        // 3. Udhaar (Customer)
        'customer-ledger' => ['title' => 'Customer Statement / Ledger', 'urdu' => 'کسٹمر لیجر / کھاتہ', 'group' => 'Udhaar / Customers'],
        'customer-outstanding' => ['title' => 'Customer Outstanding', 'urdu' => 'کسٹمر بقایا جات', 'group' => 'Udhaar / Customers'],
        'customer-ageing' => ['title' => 'Customer Ageing Analysis', 'urdu' => 'کسٹمر ایجنگ رپورٹ', 'group' => 'Udhaar / Customers'],

        // 4. Supplier
        'supplier-ledger' => ['title' => 'Supplier Statement / Ledger', 'urdu' => 'سپلائر کھاتہ / لیجر', 'group' => 'Suppliers'],
        'supplier-payable' => ['title' => 'Supplier Payable Report', 'urdu' => 'سپلائر واجب الادا رقوم', 'group' => 'Suppliers'],
        'purchase' => ['title' => 'Fuel Purchase & Decantation', 'urdu' => 'ایندھن خریداری و ترسیل', 'group' => 'Suppliers'],

        // 5. Stock
        'tank-stock-variance' => ['title' => 'Tank Stock & Variance', 'urdu' => 'ٹینکی اسٹاک اور ڈِپ فرق', 'group' => 'Stock'],
        'price-change-gain-loss' => ['title' => 'Price-Change Gain/Loss', 'urdu' => 'قیمت تبدیلی نفع و نقصان', 'group' => 'Stock'],

        // 6. Financial & HR
        'profit-and-loss' => ['title' => 'Profit & Loss Statement', 'urdu' => 'نفع و نقصان گوشوارہ', 'group' => 'Financial & HR'],
        'balance-sheet' => ['title' => 'Balance Sheet', 'urdu' => 'بیلنس شیٹ (اثاثے و واجبات)', 'group' => 'Financial & HR'],
        'trial-balance' => ['title' => 'Trial Balance', 'urdu' => 'میزان نامہ (ٹرائل بیلنس)', 'group' => 'Financial & HR'],
        'expenses' => ['title' => 'Operating Expenses Report', 'urdu' => 'کاروباری اخراجات رپورٹ', 'group' => 'Financial & HR'],
        'staff-salary' => ['title' => 'Staff & Salary Report', 'urdu' => 'عملہ اور تنخواہ رپورٹ', 'group' => 'Financial & HR'],
    ];

    public function __construct(
        private readonly ReportService $reports,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * Reports Hub: Tile 15.
     */
    public function index(Request $request)
    {
        return view('reports.index', [
            'reports' => self::$reportMetadata,
            'grouped' => collect(self::$reportMetadata)->groupBy('group', preserveKeys: true),
        ]);
    }

    /**
     * Auto-Report admin control — kaun se periods (12h/24h/7d/15d/30d)
     * auto-generate hon, admin yahin se on/off karta hai. Settings wohi
     * keys hain jo scheduler (reports:generate, hourly) parhta hai.
     */
    public function autoSettings(SettingsService $settings, SystemSettingService $systemSettings)
    {
        $periods = [
            '12h' => ['key' => 'auto_report_12h', 'label' => '12 Hours', 'default' => true],
            '24h' => ['key' => 'auto_report_24h', 'label' => '24 Hours (Daily)', 'default' => true],
            '7d' => ['key' => 'auto_report_7d', 'label' => '7 Days (Weekly)', 'default' => true],
            '15d' => ['key' => 'auto_report_15d', 'label' => '15 Days', 'default' => false],
            '30d' => ['key' => 'auto_report_30d', 'label' => '30 Days (Monthly)', 'default' => true],
        ];

        foreach ($periods as $period => &$config) {
            $config['enabled'] = filter_var(
                $settings->get($config['key'], $config['default']),
                FILTER_VALIDATE_BOOLEAN
            );
            $config['last'] = AutoReport::where('period', $period)
                ->latest('updated_at')
                ->first();
        }
        unset($config);

        $recentReports = AutoReport::with('branch')
            ->latest('updated_at')
            ->limit(30)
            ->get();

        // Email delivery + custom interval — System settings
        // (group "reports") se ate hain, generator/email service bhi
        // yehi parhte hain.
        $customHours = (int) ($systemSettings->get('auto_report_custom_hours', '0') ?? 0);

        return view('reports.auto-settings', [
            'periods' => $periods,
            'recentReports' => $recentReports,
            'sendTime' => $settings->get('report_send_time', '23:00'),
            'reportEmailRecipients' => (string) ($systemSettings->get('report_email_recipients') ?? ''),
            'customHours' => $customHours,
            'customLast' => AutoReport::where('period', 'custom')->latest('updated_at')->first(),
            'sendViaEmail' => filter_var($systemSettings->get('report_send_via_email', '1'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Auto-report period toggles save karna (settings.edit permission
     * route par lagi hui hai).
     */
    public function updateAutoSettings(Request $request, SettingsService $settings, SystemSettingService $systemSettings)
    {
        $keys = [
            'auto_report_12h' => 'Generate 12-Hour Reports',
            'auto_report_24h' => 'Generate Daily Reports',
            'auto_report_7d' => 'Generate Weekly Reports',
            'auto_report_15d' => 'Generate 15-Day Reports',
            'auto_report_30d' => 'Generate Monthly Reports',
        ];

        foreach ($keys as $key => $label) {
            $settings->set($key, $request->boolean($key) ? 1 : 0, 'boolean', $label);
        }

        $sendTime = (string) $request->input('report_send_time', '');
        if (preg_match('/^\d{2}:\d{2}$/', $sendTime)) {
            $settings->set('report_send_time', $sendTime, 'string', 'Daily Report Send Time (HH:MM)');
        }

        // Owner email recipients (comma-separated) — khali ho to email
        // company email par jati hai.
        $recipients = trim((string) $request->input('report_email_recipients', ''));
        if (strlen($recipients) <= 1000) {
            $clean = collect(explode(',', $recipients))
                ->map(fn ($email) => trim($email))
                ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->implode(', ');
            $systemSettings->set('report_email_recipients', $clean !== '' ? $clean : null);
        }

        // Custom interval (hours) — 0 = off, zyada se zyada 90 din.
        $customHours = (int) $request->input('auto_report_custom_hours', 0);
        $customHours = max(0, min($customHours, 24 * 90));
        $systemSettings->set('auto_report_custom_hours', (string) $customHours);

        return redirect()->route('reports.auto-reports')
            ->with('success', 'Auto-report settings save ho gayi — agli hourly run se apply hongi.');
    }

    /**
     * Generated auto-report ki PDF download (auto-reports list ke
     * buttons se). Export service wahi hai jo email attachment banata
     * hai — file dono jagah ek jaisi milti hai.
     */
    public function downloadAutoReportPdf(AutoReport $autoReport, ReportExportService $exports): Response
    {
        $bytes = $exports->pdfBytes($autoReport);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $exports->pdfFilename($autoReport) . '"',
        ]);
    }

    /**
     * Generated auto-report ki Excel (.xlsx) download.
     */
    public function downloadAutoReportExcel(AutoReport $autoReport, ReportExportService $exports): Response
    {
        $bytes = $exports->excelBytes($autoReport);

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $exports->excelFilename($autoReport) . '"',
        ]);
    }

    /**
     * "Generate Now" — cron ka intezaar kiye baghair enabled periods ki
     * reports tamam active branches ke liye foran generate karna.
     */
    public function generateAutoReportsNow(ReportGenerationService $generator)
    {
        $count = $generator->generateAllScheduledReports();

        return redirect()->route('reports.auto-reports')
            ->with('success', "{$count} report(s) generate ho gayi (sirf enabled periods, tamam active branches).");
    }

    /**
     * Show / Print / PDF / CSV single report.
     */
    public function show(Request $request, string $report)
    {
        if (! isset(self::$reportMetadata[$report])) {
            abort(404, "Report '{$report}' not found.");
        }

        // Fleet Card Settlement ka apna dedicated page hai (period
        // filter + OMC-wise groups + Mark Settled action) — generic
        // report flow se alag handle hota hai.
        if ($report === 'fleet-settlement') {
            return $this->fleetSettlement($request);
        }

        $meta = self::$reportMetadata[$report];
        $branchId = $this->branchScope->activeBranchId($request) ?? 1;
        $branch = Branch::find($branchId) ?? Branch::first();

        // Date range
        $preset = $request->get('preset', 'today');
        $customFrom = $request->get('from');
        $customTo = $request->get('to');
        $shiftId = $request->get('shift_id');

        $range = $this->reports->resolveDateRange($preset, $customFrom, $customTo, $shiftId ? (int) $shiftId : null);
        $from = $range['from'];
        $to = $range['to'];

        // Entities for filters
        $customers = Customer::where('status', 'ACTIVE')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'ACTIVE')->orderBy('name')->get();
        $bankAccounts = BankAccount::where('status', 'ACTIVE')->with('bank')->get();
        $shifts = Shift::where('branch_id', $branchId)->orderByDesc('id')->limit(30)->get();

        $customerId = $request->get('customer_id') ? (int) $request->get('customer_id') : $customers->first()?->id;
        $supplierId = $request->get('supplier_id') ? (int) $request->get('supplier_id') : $suppliers->first()?->id;
        $bankAccountId = $request->get('bank_account_id') ? (int) $request->get('bank_account_id') : $bankAccounts->first()?->id;

        // Fetch data
        $data = match ($report) {
            'sales' => $this->reports->salesSummary($branchId, $from, $to),
            'fuel-sales' => $this->reports->fuelSales($branchId, $from, $to),
            'nozzle-sales' => $this->reports->nozzleSales($branchId, $from, $to),
            'meter-reading' => $this->reports->meterReadings($branchId, $from, $to),
            'cashier-performance' => $this->reports->cashierPerformance($branchId, $from, $to),
            'cash-book' => $this->reports->cashBook($branchId, $from, $to),
            'bank-book' => $this->reports->bankBook($branchId, $bankAccountId, $from, $to),
            'cheque-register' => $this->reports->chequeRegister($branchId, $from, $to),
            'daily-closing' => $this->reports->dailyClosingHistory($branchId, $from, $to),
            'customer-ledger' => $customerId ? $this->reports->customerLedger($branchId, $customerId, $from, $to) : [],
            'customer-outstanding' => $this->reports->customerOutstanding($branchId),
            'customer-ageing' => $this->reports->customerAgeing($branchId),
            'supplier-ledger' => $supplierId ? $this->reports->supplierLedger($branchId, $supplierId, $from, $to) : [],
            'supplier-payable' => $this->reports->supplierPayable($branchId),
            'purchase' => $this->reports->purchases($branchId, $from, $to),
            'tank-stock-variance' => $this->reports->tankStockVariance($branchId, $from, $to),
            'price-change-gain-loss' => $this->reports->priceChangeGainLoss($branchId, $from, $to),
            'profit-and-loss' => $this->reports->profitAndLoss($branchId, $from, $to),
            'balance-sheet' => $this->reports->balanceSheet($branchId, $to),
            'trial-balance' => $this->reports->trialBalance($branchId, $to),
            'expenses' => $this->reports->expenses($branchId, $from, $to),
            'staff-salary' => $this->reports->staffSalary($branchId, $from, $to),
        };

        $format = $request->get('format', 'html');

        // CSV export
        if (in_array($format, ['csv', 'excel'], true)) {
            return $this->exportCsv($report, $meta, $branch, $range, $data);
        }

        // PDF export
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.print', [
                'report' => $report,
                'meta' => $meta,
                'branch' => $branch,
                'range' => $range,
                'data' => $data,
                'isPdf' => true,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream("{$report}_{$from}_{$to}.pdf");
        }

        // Print view
        if ($format === 'print') {
            return view('reports.print', [
                'report' => $report,
                'meta' => $meta,
                'branch' => $branch,
                'range' => $range,
                'data' => $data,
                'isPdf' => false,
            ]);
        }

        // Default screen view
        return view('reports.show', [
            'report' => $report,
            'meta' => $meta,
            'branch' => $branch,
            'range' => $range,
            'data' => $data,
            'customers' => $customers,
            'suppliers' => $suppliers,
            'bankAccounts' => $bankAccounts,
            'shifts' => $shifts,
            'selectedCustomerId' => $customerId,
            'selectedSupplierId' => $supplierId,
            'selectedBankAccountId' => $bankAccountId,
        ]);
    }

    /**
     * Fleet Card Settlement page — period filter, OMC (reference)
     * wise groups, settled vs pending split aur detail list.
     */
    public function fleetSettlement(Request $request)
    {
        $meta = self::$reportMetadata['fleet-settlement'];
        $branchId = $this->branchScope->activeBranchId($request) ?? 1;
        $branch = Branch::find($branchId) ?? Branch::first();

        $range = $this->reports->resolveDateRange(
            $request->get('preset', 'month'),
            $request->get('from'),
            $request->get('to'),
        );

        $from = Carbon::parse($range['from'])->startOfDay();
        $to = Carbon::parse($range['to'])->endOfDay();

        $fleet = app(FleetSettlementService::class);
        $data = $fleet->report($branchId, $from, $to);

        return view('reports.fleet-settlement', [
            'meta' => $meta,
            'branch' => $branch,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'totals' => $data['totals'],
            'groups' => $data['groups'],
            'payments' => $data['payments'],
        ]);
    }

    /**
     * Ek OMC (reference) group ki pending fleet payments ko settled
     * mark karna — sirf form me diye gaye period/branch ke andar.
     * Route par reports.export permission lagi hai (view-only user
     * settlement change nahi kar sakta).
     */
    public function settleFleetPayments(Request $request, FleetSettlementService $fleet)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $branchId = $this->branchScope->activeBranchId($request) ?? 1;

        $count = $fleet->markSettled(
            $branchId,
            $validated['reference'],
            Carbon::parse($validated['from'])->startOfDay(),
            Carbon::parse($validated['to'])->endOfDay(),
        );

        $label = $validated['reference'] === FleetSettlementService::NO_REFERENCE
            ? __('finance.fleet.no_reference')
            : $validated['reference'];

        return back()->with(
            'success',
            $count > 0
                ? "{$count} fleet payment(s) settled mark ho gayin — {$label}."
                : "Koi pending fleet payment nahi mili — {$label} (is period me sab pehle se settled hain)."
        );
    }

    private function exportCsv(string $report, array $meta, Branch $branch, array $range, array $data): StreamedResponse
    {
        $fileName = "{$report}_{$range['from']}_{$range['to']}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($report, $meta, $branch, $range, $data) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ["Vital Petroleum - {$branch->name}"]);
            fputcsv($handle, [$meta['title'] . " ({$meta['urdu']})"]);
            fputcsv($handle, ["Period: {$range['label']}"]);
            fputcsv($handle, []);

            switch ($report) {
                case 'sales':
                    fputcsv($handle, ['Invoice #', 'Date & Time', 'Cashier', 'Customer', 'Litres', 'Total (Rs.)', 'Payment Status']);
                    foreach ($data['sales'] ?? [] as $s) {
                        fputcsv($handle, [$s->invoice_number, $s->created_at->format('Y-m-d H:i'), $s->user?->name, $s->customer?->name ?? 'Walk-in', $s->total_litres, $s->total, $s->status]);
                    }
                    fputcsv($handle, ['TOTALS', '', '', '', $data['total_litres'] ?? '', $data['total_sales'] ?? '', '']);
                    break;

                case 'fuel-sales':
                    fputcsv($handle, ['Product', 'Litres Dispensed', 'Sales Amount (Rs.)', 'Average Rate', 'Cost Amount', 'Gross Margin']);
                    foreach ($data['products'] ?? [] as $p) {
                        fputcsv($handle, [$p['name'], $p['litres'], $p['amount'], $p['average_rate'], $p['cost'], $p['margin']]);
                    }
                    fputcsv($handle, ['TOTAL', $data['total_litres'] ?? '', $data['total_amount'] ?? '', '', $data['total_cost'] ?? '', $data['gross_margin'] ?? '']);
                    break;

                case 'nozzle-sales':
                    fputcsv($handle, ['Nozzle #', 'Product', 'Dispenser', 'Opening Meter', 'Closing Meter', 'Litres Dispensed', 'Total Amount (Rs.)']);
                    foreach ($data['nozzles'] ?? [] as $n) {
                        fputcsv($handle, [$n['nozzle_number'] ?? $n['name'] ?? '', $n['product_name'] ?? '', $n['dispenser_name'] ?? '', $n['opening_meter'] ?? '', $n['closing_meter'] ?? '', $n['litres'] ?? '', $n['amount'] ?? '']);
                    }
                    fputcsv($handle, ['TOTAL', '', '', '', '', $data['total_litres'] ?? '', $data['total_amount'] ?? '']);
                    break;

                case 'cash-book':
                    fputcsv($handle, ['Opening Balance (Rs.)', $data['opening_balance'] ?? '0.00']);
                    fputcsv($handle, ['Date', 'Description / Source', 'Reference', 'Cash In / Receipt (Rs.)', 'Cash Out / Payment (Rs.)', 'Running Balance (Rs.)']);
                    foreach ($data['entries'] ?? [] as $e) {
                        fputcsv($handle, [$e['date'] ?? '', $e['description'] ?? '', $e['reference'] ?? '', $e['in'] ?? '0.00', $e['out'] ?? '0.00', $e['balance'] ?? '0.00']);
                    }
                    fputcsv($handle, ['TOTALS', '', '', $data['total_in'] ?? '0.00', $data['total_out'] ?? '0.00', $data['closing_balance'] ?? '0.00']);
                    break;

                case 'bank-book':
                    fputcsv($handle, ['Opening Balance (Rs.)', $data['opening_balance'] ?? '0.00']);
                    fputcsv($handle, ['Date', 'Description', 'Cheque / Ref #', 'Deposit (Rs.)', 'Withdrawal (Rs.)', 'Balance (Rs.)']);
                    foreach ($data['entries'] ?? [] as $e) {
                        fputcsv($handle, [$e['date'] ?? '', $e['description'] ?? '', $e['reference'] ?? '', $e['deposit'] ?? '0.00', $e['withdrawal'] ?? '0.00', $e['balance'] ?? '0.00']);
                    }
                    fputcsv($handle, ['TOTALS', '', '', $data['total_deposits'] ?? '0.00', $data['total_withdrawals'] ?? '0.00', $data['closing_balance'] ?? '0.00']);
                    break;

                case 'cheque-register':
                    fputcsv($handle, ['Cheque Number', 'Bank Name', 'Type', 'Party Name', 'Cheque Date', 'Due Date', 'Status', 'Amount (Rs.)']);
                    foreach ($data['cheques'] ?? [] as $c) {
                        fputcsv($handle, [$c->cheque_number ?? '', $c->bank_name ?? '', $c->type ?? '', $c->party_name ?? '', $c->cheque_date ?? '', $c->due_date ?? '', $c->status ?? '', $c->amount ?? '']);
                    }
                    fputcsv($handle, ['TOTAL AMOUNT', '', '', '', '', '', '', $data['total_amount'] ?? '']);
                    break;

                case 'daily-closing':
                    fputcsv($handle, ['Closing #', 'Date', 'Fuel Litres', 'Sales Amount (Rs.)', 'Cash Sales', 'Expected Cash', 'Actual Cash', 'Cash Variance', 'Status']);
                    foreach ($data['closings'] ?? [] as $dc) {
                        fputcsv($handle, [$dc->closing_number, $dc->closing_date->format('Y-m-d'), $dc->total_fuel_litres, $dc->total_sales_amount, $dc->total_cash_sales, $dc->expected_cash, $dc->actual_cash_counted, $dc->cash_variance, $dc->status]);
                    }
                    break;

                case 'customer-ledger':
                    fputcsv($handle, ['Customer', ($data['customer']->name ?? '') . ' (' . ($data['customer']->phone ?? '') . ')']);
                    fputcsv($handle, ['Opening Balance (Rs.)', $data['opening_balance'] ?? '0.00']);
                    fputcsv($handle, ['Date', 'Description', 'Reference #', 'Debit (Rs.)', 'Credit (Rs.)', 'Running Balance (Rs.)']);
                    foreach ($data['entries'] ?? [] as $e) {
                        fputcsv($handle, [$e['date'] ?? '', $e['description'] ?? '', $e['reference'] ?? '', $e['debit'] ?? '0.00', $e['credit'] ?? '0.00', $e['running_balance'] ?? '0.00']);
                    }
                    fputcsv($handle, ['TOTALS', '', '', $data['total_debit'] ?? '0.00', $data['total_credit'] ?? '0.00', $data['closing_balance'] ?? '0.00']);
                    break;

                case 'customer-outstanding':
                    fputcsv($handle, ['Code', 'Customer Name', 'Phone', 'Credit Limit (Rs.)', 'Outstanding Balance (Rs.)', 'Status']);
                    foreach ($data['customers'] ?? [] as $cust) {
                        fputcsv($handle, [$cust['code'] ?? '', $cust['name'] ?? '', $cust['phone'] ?? '', $cust['credit_limit'] ?? '', $cust['current_balance'] ?? '', $cust['status'] ?? '']);
                    }
                    fputcsv($handle, ['TOTAL OUTSTANDING', '', '', '', $data['total_outstanding'] ?? '0.00', '']);
                    break;

                case 'customer-ageing':
                    fputcsv($handle, ['Customer Name', 'Total Due (Rs.)', 'Current (0-30 Days)', '31-60 Days', '61-90 Days', '90+ Days Overdue']);
                    foreach ($data['customers'] ?? [] as $row) {
                        fputcsv($handle, [$row['name'] ?? '', $row['total'] ?? '', $row['current'] ?? '', $row['days_30'] ?? '', $row['days_60'] ?? '', $row['days_90_plus'] ?? '']);
                    }
                    fputcsv($handle, ['TOTALS', $data['totals']['total'] ?? '', $data['totals']['current'] ?? '', $data['totals']['days_30'] ?? '', $data['totals']['days_60'] ?? '', $data['totals']['days_90_plus'] ?? '']);
                    break;

                case 'supplier-ledger':
                    fputcsv($handle, ['Supplier', ($data['supplier']->name ?? '')]);
                    fputcsv($handle, ['Opening Balance (Rs.)', $data['opening_balance'] ?? '0.00']);
                    fputcsv($handle, ['Date', 'Description', 'Reference #', 'Debit / Paid (Rs.)', 'Credit / Invoiced (Rs.)', 'Balance (Rs.)']);
                    foreach ($data['entries'] ?? [] as $e) {
                        fputcsv($handle, [$e['date'] ?? '', $e['description'] ?? '', $e['reference'] ?? '', $e['debit'] ?? '0.00', $e['credit'] ?? '0.00', $e['running_balance'] ?? '0.00']);
                    }
                    fputcsv($handle, ['TOTALS', '', '', $data['total_debit'] ?? '0.00', $data['total_credit'] ?? '0.00', $data['closing_balance'] ?? '0.00']);
                    break;

                case 'supplier-payable':
                    fputcsv($handle, ['Supplier Name', 'Contact Person', 'Phone', 'Total Invoiced (Rs.)', 'Total Paid (Rs.)', 'Net Payable (Rs.)']);
                    foreach ($data['suppliers'] ?? [] as $s) {
                        fputcsv($handle, [$s['name'] ?? '', $s['contact_person'] ?? '', $s['phone'] ?? '', $s['total_invoiced'] ?? '', $s['total_paid'] ?? '', $s['current_balance'] ?? '']);
                    }
                    fputcsv($handle, ['TOTAL PAYABLE', '', '', '', '', $data['total_payable'] ?? '0.00']);
                    break;

                case 'purchase':
                    fputcsv($handle, ['Purchase #', 'Date', 'Supplier', 'Fuel Product', 'Tanker #', 'Ordered L', 'Received L', 'Shortage L', 'Rate / L', 'Total Amount (Rs.)', 'Status']);
                    foreach ($data['purchases'] ?? [] as $pur) {
                        fputcsv($handle, [$pur->purchase_number, $pur->purchase_date->format('Y-m-d'), $pur->supplier?->name, $pur->fuelProduct?->name, $pur->tanker_number, $pur->volume_ordered, $pur->volume_received, $pur->shortage_litres, $pur->purchase_rate, $pur->total_amount, $pur->status]);
                    }
                    fputcsv($handle, ['TOTALS', '', '', '', '', '', $data['total_litres'] ?? '', '', '', $data['total_amount'] ?? '', '']);
                    break;

                case 'tank-stock-variance':
                    fputcsv($handle, ['Tank #', 'Fuel Product', 'Opening Stock (L)', 'Purchases / Received (L)', 'Sales Dispensed (L)', 'Book Stock (L)', 'Physical Dip (L)', 'Variance (L)', 'Status']);
                    foreach ($data['tanks'] ?? [] as $t) {
                        fputcsv($handle, [$t['tank_number'] ?? '', $t['product_name'] ?? '', $t['opening'] ?? '', $t['purchases'] ?? '', $t['sales'] ?? '', $t['book_stock'] ?? '', $t['physical_stock'] ?? '', $t['variance'] ?? '', $t['status'] ?? '']);
                    }
                    fputcsv($handle, ['TOTAL VARIANCE', '', '', '', '', '', '', $data['total_variance'] ?? '', '']);
                    break;

                case 'price-change-gain-loss':
                    fputcsv($handle, ['Effective Date', 'Fuel Product', 'Old Rate / L', 'New Rate / L', 'Price Delta / L', 'Tank Stock (L)', 'Gain / Loss Amount (Rs.)']);
                    foreach ($data['changes'] ?? [] as $ch) {
                        fputcsv($handle, [$ch['date'] ?? '', $ch['product_name'] ?? '', $ch['old_price'] ?? '', $ch['new_price'] ?? '', $ch['price_diff'] ?? '', $ch['stock'] ?? '', $ch['gain_loss'] ?? '']);
                    }
                    fputcsv($handle, ['NET GAIN / LOSS', '', '', '', '', '', $data['net_gain_loss'] ?? '']);
                    break;

                case 'trial-balance':
                    fputcsv($handle, ['Account Code', 'Account Name', 'Type', 'Debit Balance (Rs.)', 'Credit Balance (Rs.)']);
                    foreach ($data['accounts'] ?? [] as $a) {
                        fputcsv($handle, [$a['code'], $a['name'], $a['type'], $a['debit_balance'], $a['credit_balance']]);
                    }
                    fputcsv($handle, ['TOTALS', '', '', $data['total_debit'] ?? '0.00', $data['total_credit'] ?? '0.00']);
                    break;

                case 'profit-and-loss':
                    fputcsv($handle, ['Statement Item', 'Amount (Rs.)']);
                    fputcsv($handle, ['Fuel Sales Revenue', $data['sales_revenue'] ?? '0.00']);
                    fputcsv($handle, ['Cost of Goods Sold (COGS)', $data['cost_of_goods'] ?? '0.00']);
                    fputcsv($handle, ['GROSS PROFIT', $data['gross_profit'] ?? '0.00']);
                    fputcsv($handle, ['Total Operating Expenses', $data['operating_expenses'] ?? '0.00']);
                    fputcsv($handle, ['NET PROFIT / (LOSS)', $data['net_profit'] ?? '0.00']);
                    break;

                case 'expenses':
                    fputcsv($handle, ['Date', 'Expense #', 'Category', 'Title / Description', 'Payment Method', 'Amount (Rs.)']);
                    foreach ($data['expenses'] ?? [] as $exp) {
                        fputcsv($handle, [$exp->date->format('Y-m-d'), $exp->expense_number, $exp->category?->name, $exp->title, $exp->payment_method, $exp->amount]);
                    }
                    fputcsv($handle, ['TOTAL OPERATING EXPENSES', '', '', '', '', $data['total_amount'] ?? '0.00']);
                    break;

                case 'staff-salary':
                    fputcsv($handle, ['Employee #', 'Name', 'Designation', 'CNIC', 'Basic Salary (Rs.)', 'Allowances', 'Deductions', 'Net Payable (Rs.)']);
                    foreach ($data['payroll'] ?? [] as $st) {
                        fputcsv($handle, [$st['employee_code'] ?? '', $st['name'] ?? '', $st['designation'] ?? '', $st['cnic'] ?? '', $st['basic_salary'] ?? '', $st['allowances'] ?? '0.00', $st['deductions'] ?? '0.00', $st['net_salary'] ?? '']);
                    }
                    fputcsv($handle, ['TOTAL SALARIES DISBURSED', '', '', '', '', '', '', $data['total_paid'] ?? '0.00']);
                    break;

                default:
                    fputcsv($handle, ['Field', 'Value']);
                    foreach ($data as $k => $v) {
                        if (is_scalar($v)) {
                            fputcsv($handle, [$k, $v]);
                        }
                    }
                    break;
            }

            fclose($handle);
        }, 200, $headers);
    }
}
