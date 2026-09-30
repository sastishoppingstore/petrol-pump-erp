<?php

namespace App\Livewire\Dashboard;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\CashEntry;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\User;
use App\Services\Accounts\ExpenseService;
use App\Services\Cash\CashBookService;
use App\Services\Customer\CustomerLedgerService;
use App\Services\Dashboard\DashboardMetricsService;
use App\Services\Security\BranchScopeService;
use App\Support\UrduNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Mobile-First 3D Forecourt App Launcher & Station Control Center.
 * Polling every 15s for live forecourt updates.
 */
class AppLauncher extends Component
{
    // Search
    public string $searchQuery = '';
    public array $searchResults = ['customers' => [], 'sales' => [], 'vehicles' => []];
    public bool $isSearching = false;

    // UI States
    public string $lang = 'ur'; // 'ur' or 'en'
    public bool $simpleMode = false;
    public bool $showMoreDrawer = false;
    public bool $showActionMenu = false;
    public bool $showHelpModal = false;
    public bool $isLocked = false;
    public string $lockPin = '';
    public string $lockError = '';

    // Quick Action Modal states (Cash In, Cash Out, Customer Payment, Expense)
    public ?string $quickModalType = null;
    public string $quickAmount = '';
    public string $quickNotes = '';
    public ?int $quickCustomerId = null;
    public ?string $quickSuccess = null;
    public ?string $quickError = null;

    protected $listeners = [
        'refreshDashboard' => 'refreshMetrics',
        'voiceQueryReceived' => 'handleVoiceSearch',
    ];

    public function mount(): void
    {
        $this->lang = session('locale', 'ur');
        $this->simpleMode = (bool) session('simple_mode', false);
    }

    public function refreshMetrics(): void
    {
        // Polled every 15s by wire:poll.15s
        // Blade re-renders with fresh database figures automatically.
    }

    public function updatedSearchQuery(): void
    {
        $term = trim($this->searchQuery);
        if (strlen($term) >= 2) {
            $this->isSearching = true;
            $service = app(DashboardMetricsService::class);
            $this->searchResults = $service->search($term, session('active_branch_id'));
        } else {
            $this->isSearching = false;
            $this->searchResults = ['customers' => [], 'sales' => [], 'vehicles' => []];
        }
    }

    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->isSearching = false;
        $this->searchResults = ['customers' => [], 'sales' => [], 'vehicles' => []];
    }

    public function handleVoiceSearch(string $text): void
    {
        $this->searchQuery = $text;
        $this->updatedSearchQuery();
    }

    public function toggleLanguage(): void
    {
        $this->lang = $this->lang === 'ur' ? 'en' : 'ur';
        session(['locale' => $this->lang]);
    }

    public function toggleSimpleMode(): void
    {
        $this->simpleMode = ! $this->simpleMode;
        session(['simple_mode' => $this->simpleMode]);
    }

    public function toggleMoreDrawer(): void
    {
        $this->showMoreDrawer = ! $this->showMoreDrawer;
        if ($this->showMoreDrawer) {
            $this->showActionMenu = false;
        }
    }

    public function toggleActionMenu(): void
    {
        $this->showActionMenu = ! $this->showActionMenu;
    }

    public function openHelpModal(): void
    {
        $this->showHelpModal = true;
    }

    public function closeHelpModal(): void
    {
        $this->showHelpModal = false;
    }

    public function lockScreen(): void
    {
        $this->isLocked = true;
        $this->lockPin = '';
        $this->lockError = '';
        $this->showMoreDrawer = false;
        $this->showActionMenu = false;
    }

    public function enterLockDigit(string $digit): void
    {
        if (strlen($this->lockPin) < 6) {
            $this->lockPin .= $digit;
            if (strlen($this->lockPin) >= 4) {
                $this->unlockScreen();
            }
        }
    }

    public function backspaceLock(): void
    {
        if (strlen($this->lockPin) > 0) {
            $this->lockPin = substr($this->lockPin, 0, -1);
        }
    }

    public function clearLockPin(): void
    {
        $this->lockPin = '';
        $this->lockError = '';
    }

    public function unlockScreen(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if ($user->verifyPin($this->lockPin) || (empty($user->pin) && $this->lockPin === '1234')) {
            $this->isLocked = false;
            $this->lockPin = '';
            $this->lockError = '';
        } else {
            $this->lockError = $this->lang === 'ur' ? 'غلط سیکیورٹی پن!' : 'Invalid Security PIN!';
            $this->lockPin = '';
        }
    }

    public function openQuickAction(string $type): void
    {
        $this->quickModalType = $type;
        $this->quickAmount = '';
        $this->quickNotes = '';
        $this->quickCustomerId = null;
        $this->quickSuccess = null;
        $this->quickError = null;
        $this->showActionMenu = false;
    }

    public function closeQuickAction(): void
    {
        $this->quickModalType = null;
        $this->quickSuccess = null;
        $this->quickError = null;
    }

    public function submitQuickAction(): void
    {
        $amount = (float) $this->quickAmount;
        if ($amount <= 0) {
            $this->quickError = $this->lang === 'ur' ? 'درست رقم درج کریں' : 'Please enter a valid amount.';
            return;
        }

        $user = Auth::user();
        if (! $user) {
            $this->quickError = 'Unauthenticated';
            return;
        }

        $branchId = (int) (session('active_branch_id') ?: ($user->branches()->first()?->id ?? 1));
        $activeShift = Shift::where('status', Shift::STATUS_OPEN)
            ->where('branch_id', $branchId)
            ->latest('opened_at')
            ->first();

        try {
            DB::transaction(function () use ($user, $branchId, $activeShift, $amount) {
                switch ($this->quickModalType) {
                    case 'cash_in':
                        $cashBook = app(CashBookService::class);
                        $cashBook->recordCashIn(
                            actor: $user,
                            branchId: $branchId,
                            amount: (string) $amount,
                            category: 'QUICK_CASH_IN',
                            personName: $user->name ?: 'Forecourt Cashier',
                            shiftId: $activeShift?->id,
                            referenceNo: null,
                            attachmentPath: null,
                            notes: $this->quickNotes ?: 'Quick Forecourt Cash In',
                            entryDate: now()->toDateString(),
                        );

                        if ($activeShift) {
                            $activeShift->opening_cash = bcadd((string) $activeShift->opening_cash, (string) $amount, 2);
                            $activeShift->notes = trim(($activeShift->notes ?? '') . " [Cash In: Rs. {$amount} by {$user->name}]");
                            $activeShift->save();
                        }

                        $this->quickSuccess = $this->lang === 'ur'
                            ? "رقم موصول درج ہوگئی: Rs. " . UrduNumber::lakhFormat($amount, 0, false)
                            : "Cash In recorded: Rs. " . number_format($amount);
                        break;

                    case 'cash_out':
                        $cashBook = app(CashBookService::class);
                        $cashBook->recordCashOut(
                            actor: $user,
                            branchId: $branchId,
                            amount: (string) $amount,
                            category: 'QUICK_CASH_OUT',
                            personName: $user->name ?: 'Forecourt Cashier',
                            shiftId: $activeShift?->id,
                            referenceNo: null,
                            attachmentPath: null,
                            notes: $this->quickNotes ?: 'Quick Forecourt Cash Drop/Out',
                            entryDate: now()->toDateString(),
                        );

                        if ($activeShift) {
                            $activeShift->cash_drops_total = bcadd((string) ($activeShift->cash_drops_total ?? '0.00'), (string) $amount, 2);
                            $activeShift->save();
                        }

                        $this->quickSuccess = $this->lang === 'ur'
                            ? "رقم ادائیگی درج ہوگئی: Rs. " . UrduNumber::lakhFormat($amount, 0, false)
                            : "Cash Out recorded: Rs. " . number_format($amount);
                        break;

                    case 'expense':
                        $category = ExpenseCategory::where('status', ExpenseCategory::STATUS_ACTIVE)->first();
                        if (! $category) {
                            $category = ExpenseCategory::create([
                                'code' => 'EXP-GEN',
                                'name' => 'General Expenses',
                                'urdu_name' => 'عام اخراجات',
                                'status' => ExpenseCategory::STATUS_ACTIVE,
                            ]);
                        }

                        // Ensure required GL accounts exist for double-entry ledger posting
                        if (Schema::hasTable('accounts')) {
                            Account::firstOrCreate(
                                ['code' => Account::CODE_OPERATING_EXPENSES],
                                ['name' => 'Operating Expenses', 'type' => Account::TYPE_EXPENSE, 'normal_balance' => Account::BALANCE_DEBIT, 'is_system' => true]
                            );
                            Account::firstOrCreate(
                                ['code' => Account::CODE_CASH_IN_HAND],
                                ['name' => 'Cash in Hand', 'type' => Account::TYPE_ASSET, 'normal_balance' => Account::BALANCE_DEBIT, 'is_system' => true]
                            );
                        }

                        $expenseService = app(ExpenseService::class);
                        $expenseService->createExpense(
                            branchId: $branchId,
                            categoryId: $category->id,
                            title: $this->quickNotes ?: 'Forecourt Expense',
                            amount: (string) $amount,
                            paymentMethod: Expense::METHOD_CASH,
                            date: now()->toDateString(),
                            bankAccountId: null,
                            shiftId: $activeShift?->id,
                            payee: $user->name,
                            receiptNumber: null,
                            notes: $this->quickNotes ?: 'Forecourt Operating Expense',
                            actor: $user,
                        );

                        if ($activeShift) {
                            $activeShift->expenses_total = bcadd((string) ($activeShift->expenses_total ?? '0.00'), (string) $amount, 2);
                            $activeShift->save();
                        }

                        $this->quickSuccess = $this->lang === 'ur'
                            ? "خرچہ درج ہوگیا: Rs. " . UrduNumber::lakhFormat($amount, 0, false)
                            : "Expense recorded: Rs. " . number_format($amount);
                        break;

                    case 'customer_payment':
                        if (! $this->quickCustomerId) {
                            throw ValidationException::withMessages([
                                'customer_id' => $this->lang === 'ur' ? 'گاہک منتخب کریں' : 'Please select a customer.',
                            ]);
                        }

                        $customer = Customer::findOrFail($this->quickCustomerId);
                        $ledgerService = app(CustomerLedgerService::class);
                        $ledgerService->recordPayment(
                            customer: $customer,
                            data: [
                                'amount' => (string) $amount,
                                'payment_method' => CustomerPayment::METHOD_CASH,
                                'payment_date' => now()->toDateString(),
                                'branch_id' => $branchId,
                                'shift_id' => $activeShift?->id,
                                'notes' => $this->quickNotes ?: 'Quick customer payment at forecourt',
                            ],
                            userId: $user->id,
                        );

                        if ($activeShift) {
                            $activeShift->opening_cash = bcadd((string) $activeShift->opening_cash, (string) $amount, 2);
                            $activeShift->save();
                        }

                        $this->quickSuccess = $this->lang === 'ur'
                            ? "گاہک ادائیگی درج ہوگئی: Rs. " . UrduNumber::lakhFormat($amount, 0, false)
                            : "Customer payment recorded: Rs. " . number_format($amount);
                        break;
                }
            });

            $this->quickAmount = '';
            $this->quickNotes = '';
            $this->quickCustomerId = null;
            $this->refreshMetrics();
            $this->dispatch('metrics-updated');
        } catch (ValidationException $e) {
            $this->quickError = collect($e->errors())->flatten()->first() ?: $e->getMessage();
        } catch (\Throwable $e) {
            $this->quickError = $e->getMessage();
        }
    }

    public function render()
    {
        $user = Auth::user();
        $branchId = session('active_branch_id');
        $service = app(DashboardMetricsService::class);
        $data = $service->getMetrics($branchId, $user);

        // Role-based filtering
        $isCashier = $user && ($user->hasRole('CASHIER') || $user->hasRole('ATTENDANT')) && ! $user->isSuperAdmin() && ! $user->hasRole('ADMIN') && ! $user->hasRole('MANAGER');

        // All 18 Tiles configuration
        $allTiles = [
            [
                'id' => 1,
                'key' => 'meter',
                'icon' => '⛽',
                'title_ur' => 'میٹر ریڈنگ',
                'title_en' => 'Meter Reading',
                'desc_ur' => 'نوزل ریڈنگ اندراج',
                'desc_en' => 'Forecourt meters',
                'route' => 'meter-readings.index',
                'url' => route('meter-readings.index'),
                'cashier' => true,
                'color' => 'bg-amber-500 text-white',
            ],
            [
                'id' => 2,
                'key' => 'pos',
                'icon' => '🧾',
                'title_ur' => 'نیا بل / Sale',
                'title_en' => 'New Bill / Sale',
                'desc_ur' => 'فوری پی او ایس بلنگ',
                'desc_en' => 'Forecourt POS',
                'route' => 'pos.index',
                'url' => route('pos.index'),
                'cashier' => true,
                'color' => 'bg-vital-primary text-white',
            ],
            [
                'id' => 3,
                'key' => 'customers',
                'icon' => '👥',
                'title_ur' => 'گاہک / ادھار',
                'title_en' => 'Customers / Udhaar',
                'desc_ur' => 'کھاتہ اور کریڈٹ لمٹ',
                'desc_en' => 'Credit ledgers',
                'route' => 'sales.index',
                'url' => route('sales.index'),
                'cashier' => true,
                'color' => 'bg-indigo-600 text-white',
            ],
            [
                'id' => 4,
                'key' => 'cash_in',
                'icon' => '💵',
                'title_ur' => 'رقم آئی',
                'title_en' => 'Cash In',
                'desc_ur' => 'کیش موصولی اندراج',
                'desc_en' => 'Forecourt cash received',
                'action' => 'cash_in',
                'cashier' => true,
                'color' => 'bg-emerald-600 text-white',
            ],
            [
                'id' => 5,
                'key' => 'cash_out',
                'icon' => '💸',
                'title_ur' => 'رقم گئی',
                'title_en' => 'Cash Out',
                'desc_ur' => 'کیش ڈراپ / ادائیگی',
                'desc_en' => 'Cash drops & payouts',
                'action' => 'cash_out',
                'cashier' => false,
                'color' => 'bg-rose-600 text-white',
            ],
            [
                'id' => 6,
                'key' => 'bank',
                'icon' => '🏦',
                'title_ur' => 'بینک',
                'title_en' => 'Bank',
                'desc_ur' => 'بینک کھاتے و ڈپازٹ',
                'desc_en' => 'Bank deposits & accounts',
                'route' => 'banks.index',
                'url' => route('banks.index'),
                'cashier' => false,
                'color' => 'bg-blue-600 text-white',
            ],
            [
                'id' => 7,
                'key' => 'cheques',
                'icon' => '📝',
                'title_ur' => 'چیک',
                'title_en' => 'Cheques',
                'desc_ur' => 'کلیرنگ اور وصولی',
                'desc_en' => 'Cheque clearing khata',
                'url' => route('cash-deposits'),
                'cashier' => false,
                'color' => 'bg-teal-600 text-white',
            ],
            [
                'id' => 8,
                'key' => 'purchases',
                'icon' => '🚚',
                'title_ur' => 'تیل خریداری',
                'title_en' => 'Fuel Purchase',
                'desc_ur' => 'ٹینکر آمد و ڈپ پیمائش',
                'desc_en' => 'OMC tanker supply',
                'route' => 'stock.movements',
                'url' => route('stock.movements'),
                'cashier' => false,
                'color' => 'bg-orange-600 text-white',
            ],
            [
                'id' => 9,
                'key' => 'suppliers',
                'icon' => '🏭',
                'title_ur' => 'سپلائر',
                'title_en' => 'Suppliers',
                'desc_ur' => 'او ایم سی و دیگر کھاتے',
                'desc_en' => 'OMC Vital khata',
                'url' => route('banks.index'),
                'cashier' => false,
                'color' => 'bg-violet-600 text-white',
            ],
            [
                'id' => 10,
                'key' => 'tanks',
                'icon' => '🛢️',
                'title_ur' => 'ٹینک / اسٹاک',
                'title_en' => 'Tanks & Stock',
                'desc_ur' => 'اسٹاک لیول اور ڈپ',
                'desc_en' => 'Live fuel stock levels',
                'route' => 'tanks.index',
                'url' => route('tanks.index'),
                'cashier' => false,
                'color' => 'bg-amber-600 text-white',
            ],
            [
                'id' => 11,
                'key' => 'shifts',
                'icon' => '⏱️',
                'title_ur' => 'شفٹ',
                'title_en' => 'Shift',
                'desc_ur' => 'شفٹ ہینڈ اوور و کلوزنگ',
                'desc_en' => 'Shift closing & handover',
                'route' => 'shifts.index',
                'url' => route('shifts.index'),
                'cashier' => true,
                'color' => 'bg-cyan-600 text-white',
            ],
            [
                'id' => 12,
                'key' => 'profit_loss',
                'icon' => '📈',
                'title_ur' => 'منافع / نقصان',
                'title_en' => 'Profit / Loss',
                'desc_ur' => 'مارجن و خالص بچت',
                'desc_en' => 'Gross & net forecourt P&L',
                'url' => route('sales.index'),
                'cashier' => false,
                'color' => 'bg-emerald-700 text-white',
            ],
            [
                'id' => 13,
                'key' => 'staff',
                'icon' => '🧑‍💼',
                'title_ur' => 'عملہ / تنخواہ',
                'title_en' => 'Staff / Salary',
                'desc_ur' => 'حاضری، ایڈوانس و اجرت',
                'desc_en' => 'Forecourt attendants',
                'route' => 'users.index',
                'url' => route('users.index'),
                'cashier' => false,
                'color' => 'bg-sky-600 text-white',
            ],
            [
                'id' => 14,
                'key' => 'expenses',
                'icon' => '🧾',
                'title_ur' => 'اخراجات',
                'title_en' => 'Expenses',
                'desc_ur' => 'روزمرہ اخراجات بل',
                'desc_en' => 'Pump daily expenses',
                'action' => 'expense',
                'cashier' => false,
                'color' => 'bg-fuchsia-600 text-white',
            ],
            [
                'id' => 15,
                'key' => 'reports',
                'icon' => '📊',
                'title_ur' => 'رپورٹس',
                'title_en' => 'Reports',
                'desc_ur' => 'سیل، اسٹاک و لیجر',
                'desc_en' => 'Station analytics',
                'route' => 'sales.index',
                'url' => route('sales.index'),
                'cashier' => true,
                'color' => 'bg-purple-600 text-white',
            ],
            [
                'id' => 16,
                'key' => 'documents',
                'icon' => '📥',
                'title_ur' => 'دستاویزات',
                'title_en' => 'Documents',
                'desc_ur' => 'ایف بی آر انوائسز و رسیدیں',
                'desc_en' => 'FBR receipts & archives',
                'route' => 'sales.index',
                'url' => route('sales.index'),
                'cashier' => false,
                'color' => 'bg-blue-700 text-white',
            ],
            [
                'id' => 17,
                'key' => 'notifications',
                'icon' => '🔔',
                'title_ur' => 'اطلاعات',
                'title_en' => 'Notifications',
                'desc_ur' => 'الرٹس اور انتباہات',
                'desc_en' => 'Forecourt alert centre',
                'url' => route('shifts.index'),
                'cashier' => false,
                'color' => 'bg-yellow-600 text-white',
            ],
            [
                'id' => 18,
                'key' => 'settings',
                'icon' => '⚙️',
                'title_ur' => 'ترتیبات',
                'title_en' => 'Settings',
                'desc_ur' => 'نرخ، رول اور اسٹیشن کنفگ',
                'desc_en' => 'System configuration',
                'route' => 'roles.index',
                'url' => route('roles.index'),
                'cashier' => false,
                'color' => 'bg-slate-700 text-white',
            ],
        ];

        // Filter tiles for cashiers if active
        $tiles = $isCashier
            ? collect($allTiles)->filter(fn ($t) => $t['cashier'])->values()->all()
            : $allTiles;

        $customersList = Customer::query()->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'phone']);

        return view('livewire.dashboard.app-launcher', [
            'metrics' => $data,
            'tiles' => $tiles,
            'isCashier' => $isCashier,
            'customersList' => $customersList,
            'stationName' => 'مہر فلنگ اسٹیشن (شیخوپورہ)',
            'stationNameEn' => 'Mehar Filling Station (Vital Petroleum)',
            'currentTimeUrdu' => UrduNumber::urduDate(now()) . ' | ' . UrduNumber::urduTime(now()),
            'currentTimeEn' => now()->format('D, d M Y | h:i A'),
        ])->layout('layouts.app');
    }
}
