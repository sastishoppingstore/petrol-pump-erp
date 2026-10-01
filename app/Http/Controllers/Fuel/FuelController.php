<?php

namespace App\Http\Controllers\Fuel;

use App\Http\Controllers\Controller;
use App\Http\Requests\DispenserRequest;
use App\Http\Requests\FuelProductRequest;
use App\Http\Requests\FuelPriceRequest;
use App\Http\Requests\MeterCorrectionRequest;
use App\Http\Requests\NozzleRequest;
use App\Http\Requests\TankReadingRequest;
use App\Http\Requests\TankRequest;
use App\Models\Branch;
use App\Models\Dispenser;
use App\Models\FuelPrice;
use App\Models\FuelProduct;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Tank;
use App\Models\TankReading;
use App\Services\Fuel\FuelPriceService;
use App\Services\Fuel\MeterService;
use App\Services\Fuel\NozzleService;
use App\Services\Fuel\TankService;
use App\Services\Security\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FuelController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
        private readonly NozzleService $nozzles,
        private readonly TankService $tanks,
        private readonly FuelPriceService $prices,
        private readonly MeterService $meters,
    ) {
    }

    // ---------------------------------------------------------------
    // Fuel products
    // ---------------------------------------------------------------

    public function fuels(): View
    {
        return view('fuels.index', [
            'fuels' => FuelProduct::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function createFuel(): View
    {
        return view('fuels.create', ['fuel' => new FuelProduct(['status' => 'ACTIVE', 'unit' => 'LITRE'])]);
    }

    public function storeFuel(FuelProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $fuel = FuelProduct::create($data);

            // Seed the price history so a sale can resolve a rate immediately.
            FuelPrice::create([
                'fuel_product_id' => $fuel->id,
                'branch_id' => null,
                'price' => $data['selling_price'],
                'effective_from' => now(),
                'effective_to' => null,
                'created_by' => $request->user()->id,
                'reason' => 'Initial price',
            ]);
        } catch (\Throwable $e) {
            Log::error('Fuel product creation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to create the fuel product. No changes were saved.');
        }

        return redirect()->route('fuels.index')->with('success', "Fuel '{$fuel->name}' created.");
    }

    public function editFuel(FuelProduct $fuel): View
    {
        return view('fuels.edit', ['fuel' => $fuel]);
    }

    public function updateFuel(FuelProductRequest $request, FuelProduct $fuel): RedirectResponse
    {
        $data = $request->validated();

        // A price change goes through FuelPriceService so history and audit
        // are written; it is never a silent column update.
        $newPrice = (string) $data['selling_price'];
        $priceChanged = \App\Support\Money::compare($newPrice, \App\Support\Money::n($fuel->selling_price)) !== 0;

        unset($data['selling_price']);

        $fuel->fill($data)->save();

        if ($priceChanged) {
            $this->prices->changePrice(
                fuel: $fuel->fresh(),
                newPrice: $newPrice,
                branchId: null,
                userId: $request->user()->id,
                reason: 'Changed from fuel product screen',
            );
        }

        return redirect()->route('fuels.index')->with('success', "Fuel '{$fuel->name}' updated.");
    }

    // ---------------------------------------------------------------
    // Fuel prices
    // ---------------------------------------------------------------

    public function prices(): View
    {
        return view('fuel-prices.index', [
            'fuels' => FuelProduct::query()->where('status', FuelProduct::STATUS_ACTIVE)->orderBy('name')->get(),
            'history' => FuelPrice::query()
                ->with(['fuelProduct', 'branch', 'creator'])
                ->orderByDesc('effective_from')
                ->paginate(30),
        ]);
    }

    public function storePrice(FuelPriceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $fuel = FuelProduct::findOrFail($data['fuel_product_id']);

        try {
            $this->prices->changePrice(
                fuel: $fuel,
                newPrice: (string) $data['selling_price'],
                branchId: $data['branch_id'] ?? null,
                userId: $request->user()->id,
                reason: $data['reason'] ?? null,
                effectiveFrom: isset($data['effective_from']) ? \Carbon\Carbon::parse($data['effective_from']) : null,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('fuel-prices.index')
            ->with('success', "Price for '{$fuel->name}' updated.");
    }

    // ---------------------------------------------------------------
    // Tanks
    // ---------------------------------------------------------------

    public function tanks(Request $request): View
    {
        $query = Tank::query()->with(['fuelProduct', 'branch'])->orderBy('branch_id')->orderBy('tank_number');
        $this->branchScope->apply($query, $request->user());

        return view('tanks.index', [
            'tanks' => $query->get(),
        ]);
    }

    public function createTank(Request $request): View
    {
        return view('tanks.create', [
            'tank' => new Tank(['status' => 'ACTIVE']),
            'fuels' => FuelProduct::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'branches' => $this->branchScope->selectableBranches($request->user()),
        ]);
    }

    public function storeTank(TankRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Level sanity is a business rule, not just a form constraint.
        $tank = new Tank($data);
        $this->tanks->validateLevels($tank);

        try {
            $tank = Tank::create($data);

            // Stock starts at the declared opening quantity.
            $tank->forceFill(['current_stock' => \App\Support\Quantity::n($tank->opening_stock)])->save();
        } catch (\Throwable $e) {
            Log::error('Tank creation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to create the tank. No changes were saved.');
        }

        return redirect()->route('tanks.index')->with('success', "Tank '{$tank->tank_number}' created.");
    }

    public function editTank(Tank $tank): View
    {
        $request = request();

        return view('tanks.edit', [
            'tank' => $tank,
            'fuels' => FuelProduct::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'branches' => $this->branchScope->selectableBranches($request->user()),
        ]);
    }

    public function updateTank(TankRequest $request, Tank $tank): RedirectResponse
    {
        $data = $request->validated();

        $candidate = new Tank($data);
        $this->tanks->validateLevels($candidate);

        $tank->fill($data)->save();

        return redirect()->route('tanks.index')->with('success', "Tank '{$tank->tank_number}' updated.");
    }

    // ---------------------------------------------------------------
    // Tank readings
    // ---------------------------------------------------------------

    public function readings(Request $request): View
    {
        return view('tank-readings.index', [
            'tanks' => $this->scopedTanks($request->user()),
            'readings' => TankReading::query()
                ->with(['tank', 'fuelProduct', 'creator'])
                ->when($request->filled('tank_id'), fn ($q) => $q->where('tank_id', $request->input('tank_id')))
                ->when($request->filled('date'), fn ($q) => $q->whereDate('reading_date', $request->input('date')))
                ->orderByDesc('reading_date')
                ->orderByDesc('id')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }

    public function storeReading(TankReadingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tank = Tank::findOrFail($data['tank_id']);

        if (! $request->user()->canAccessBranch((int) $tank->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $reading = $this->tanks->recordReading(
                tank: $tank,
                physicalQuantity: (string) $data['physical_quantity'],
                notes: $data['notes'] ?? null,
                userId: $request->user()->id,
                readingDate: $data['reading_date'] ?? null,
            );

            // Optional temperature / density (K3) — sirf record ke liye;
            // variance calculation in par depend nahi karti, is liye
            // recordReading ke baad row par likhe jate hain.
            $extra = $request->validate([
                'temperature_c' => ['nullable', 'numeric', 'min:-10', 'max:80'],
                'density' => ['nullable', 'numeric', 'min:0.5000', 'max:1.2000'],
            ]);
            $extra = array_filter($extra, static fn ($v) => $v !== null && $v !== '');
            if ($extra !== []) {
                $reading->update($extra);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', sprintf(
            'Reading saved. Expected %s, physical %s, variance %s.',
            \App\Support\Quantity::format($reading->expected_quantity),
            \App\Support\Quantity::format($reading->physical_quantity),
            \App\Support\Quantity::format($reading->variance_quantity),
        ));
    }

    // ---------------------------------------------------------------
    // Dispensers
    // ---------------------------------------------------------------

    public function dispensers(Request $request): View
    {
        $query = Dispenser::query()->with('branch')->withCount('nozzles')->orderBy('branch_id')->orderBy('dispenser_number');
        $this->branchScope->apply($query, $request->user());

        return view('dispensers.index', ['dispensers' => $query->get()]);
    }

    public function createDispenser(Request $request): View
    {
        return view('dispensers.create', [
            'dispenser' => new Dispenser(['status' => 'ACTIVE']),
            'branches' => $this->branchScope->selectableBranches($request->user()),
        ]);
    }

    public function storeDispenser(DispenserRequest $request): RedirectResponse
    {
        try {
            $dispenser = Dispenser::create($request->validated());
        } catch (\Throwable $e) {
            Log::error('Dispenser creation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to create the dispenser. No changes were saved.');
        }

        return redirect()->route('dispensers.index')
            ->with('success', "Dispenser '{$dispenser->dispenser_number}' created.");
    }

    public function editDispenser(Dispenser $dispenser): View
    {
        return view('dispensers.edit', [
            'dispenser' => $dispenser,
            'branches' => $this->branchScope->selectableBranches(request()->user()),
        ]);
    }

    public function updateDispenser(DispenserRequest $request, Dispenser $dispenser): RedirectResponse
    {
        $dispenser->fill($request->validated())->save();

        return redirect()->route('dispensers.index')
            ->with('success', "Dispenser '{$dispenser->dispenser_number}' updated.");
    }

    // ---------------------------------------------------------------
    // Nozzles
    // ---------------------------------------------------------------

    public function nozzles(Request $request): View
    {
        $query = Nozzle::query()
            ->with(['dispenser', 'tank', 'fuelProduct'])
            ->orderBy('branch_id')
            ->orderBy('dispenser_id')
            ->orderBy('nozzle_number');

        $this->branchScope->apply($query, $request->user());

        return view('nozzles.index', [
            'nozzles' => $query->get(),
        ]);
    }

    public function createNozzle(Request $request): View
    {
        return view('nozzles.create', [
            'nozzle' => new Nozzle(['status' => 'ACTIVE']),
            'branches' => $this->branchScope->selectableBranches($request->user()),
            'fuels' => FuelProduct::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'tanks' => $this->scopedTanks($request->user()),
            'dispensers' => $this->scopedDispensers($request->user()),
        ]);
    }

    public function storeNozzle(NozzleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! $request->user()->canAccessBranch((int) $data['branch_id'])) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $nozzle = $this->nozzles->create(
                branch: Branch::findOrFail($data['branch_id']),
                dispenser: Dispenser::findOrFail($data['dispenser_id']),
                tank: Tank::findOrFail($data['tank_id']),
                fuelProductId: (int) $data['fuel_product_id'],
                nozzleNumber: (string) $data['nozzle_number'],
                openingMeter: (string) ($data['opening_meter'] ?? '0'),
                meterMultiplier: (string) ($data['meter_multiplier'] ?? '1'),
                notes: $data['notes'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Nozzle creation failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to create the nozzle. No changes were saved.');
        }

        return redirect()->route('nozzles.index')
            ->with('success', "Nozzle '{$nozzle->label()}' created.");
    }

    public function editNozzle(Nozzle $nozzle): View
    {
        return view('nozzles.edit', [
            'nozzle' => $nozzle,
            'branches' => $this->branchScope->selectableBranches(request()->user()),
            'fuels' => FuelProduct::query()->where('status', 'ACTIVE')->orderBy('name')->get(),
            'tanks' => $this->scopedTanks(request()->user()),
            'dispensers' => $this->scopedDispensers(request()->user()),
        ]);
    }

    public function updateNozzle(NozzleRequest $request, Nozzle $nozzle): RedirectResponse
    {
        $data = $request->validated();

        // opening_meter is history: it is only settable on creation.
        unset($data['opening_meter']);

        $this->nozzles->update($nozzle, $data);

        return redirect()->route('nozzles.index')
            ->with('success', "Nozzle '{$nozzle->label()}' updated.");
    }

    // ---------------------------------------------------------------
    // Meter readings & correction
    // ---------------------------------------------------------------

    public function meterReadings(Request $request): View
    {
        return view('meter-readings.index', [
            'readings' => MeterReading::query()
                ->with(['nozzle.dispenser', 'user'])
                ->when($request->filled('nozzle_id'), fn ($q) => $q->where('nozzle_id', $request->input('nozzle_id')))
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
                ->orderByDesc('id')
                ->paginate(40)
                ->withQueryString(),
            'nozzles' => Nozzle::query()->with('dispenser')->orderBy('dispenser_id')->orderBy('nozzle_number')->get(),
        ]);
    }

    /**
     * Manual meter reading wizard (meter-readings/create).
     * Nozzles are branch-scoped so an attendant only sees own station.
     */
    public function createMeterReading(Request $request): View
    {
        $query = Nozzle::query()
            ->with(['dispenser', 'fuelProduct'])
            ->where('status', Nozzle::STATUS_ACTIVE)
            ->orderBy('dispenser_id')
            ->orderBy('nozzle_number');

        $this->branchScope->apply($query, $request->user());

        return view('meter-readings.create', [
            'nozzles' => $query->get(),
        ]);
    }

    /**
     * Store a manual closing meter reading from the wizard.
     * The reading is recorded as a physical CLOSING reading (the same
     * canonical path as the forecourt terminal); optional test litres
     * are logged as a nozzle calibration test (fuel returned to tank).
     */
    public function storeMeterReading(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nozzle_id' => ['required', 'integer', 'exists:nozzles,id'],
            'meter_start' => ['required', 'numeric', 'min:0'],
            'meter_end' => ['required', 'numeric', 'min:0'],
            'test_litres' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $nozzle = Nozzle::findOrFail($data['nozzle_id']);

        if (! $request->user()->canAccessBranch((int) $nozzle->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            // A meter may only move forward (spec section 3); the wizard's
            // opening value is the nozzle's current meter at entry time.
            $this->meters->assertNotLower((string) $data['meter_end'], (string) $nozzle->current_meter);

            $reading = $this->meters->recordPhysical(
                nozzle: $nozzle,
                meter: (string) $data['meter_end'],
                type: MeterReading::TYPE_CLOSING,
                reason: 'Manual closing reading via meter entry wizard',
            );

            // Meter ki photo (saboot) — purchases ke bill_photo wala
            // pattern: public disk par store, path reading row par.
            // Photo fail ho jaye to reading nahi rukti, sirf log hota hai.
            if ($request->hasFile('photo')) {
                try {
                    $photoPath = $request->file('photo')->store('meter-readings', 'public');
                    if ($photoPath) {
                        $reading->update(['photo_path' => $photoPath]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Meter reading photo save failed', ['error' => $e->getMessage()]);
                }
            }

            $testLitres = (string) ($data['test_litres'] ?? '0');
            if (\App\Support\Quantity::compare($testLitres, '0') > 0) {
                $this->meters->recordNozzleTest(
                    nozzle: $nozzle,
                    litres: $testLitres,
                    reason: 'Calibration test / پیمانہ ٹیسٹ (meter entry wizard)',
                    shiftId: null,
                    actor: $request->user(),
                );
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Manual meter reading failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Unable to save the meter reading. No changes were saved.');
        }

        return redirect()->route('meter-readings.index')
            ->with('success', "Closing reading saved for nozzle '{$nozzle->label()}'.");
    }

    public function correctMeter(MeterCorrectionRequest $request, Nozzle $nozzle): RedirectResponse
    {
        if (! $request->user()->canAccessBranch((int) $nozzle->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        try {
            $this->meters->correct(
                nozzle: $nozzle,
                newMeter: (string) $request->validated('new_meter'),
                reason: (string) $request->validated('reason'),
                userId: $request->user()->id,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', "Meter for nozzle '{$nozzle->label()}' corrected.");
    }

    // ---------------------------------------------------------------

    private function scopedTanks(?\App\Models\User $user)
    {
        $query = Tank::query()->with('fuelProduct')->orderBy('tank_number');
        $this->branchScope->apply($query, $user);

        return $query->get();
    }

    private function scopedDispensers(?\App\Models\User $user)
    {
        $query = Dispenser::query()->orderBy('dispenser_number');
        $this->branchScope->apply($query, $user);

        return $query->get();
    }
}
