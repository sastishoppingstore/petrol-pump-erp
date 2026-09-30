<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[#D71920] text-white shadow-sm font-bold text-lg">
                    🎨
                </span>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>Bill Designer & Brand Identity</span>
                        <span class="text-base font-normal text-slate-500 font-urdu">انوائس و بل ڈیزائنر</span>
                    </h1>
                    <p class="text-xs text-slate-500">
                        Customize colors, logos, field visibility, and formats for customer tax invoices and thermal receipts.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="resetToDefaults"
                    type="button"
                    class="px-3.5 py-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 shadow-sm transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                ↺ Reset Vital Red
            </button>
            <button wire:click="save"
                    type="button"
                    class="px-5 py-2 text-xs font-bold text-white bg-[#D71920] hover:bg-[#A30F15] rounded-lg shadow-sm transition flex items-center gap-1.5">
                <span>💾</span>
                <span>Save Template</span>
            </button>
        </div>
    </div>

    @if ($savedSuccess)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between dark:bg-emerald-950 dark:border-emerald-900 dark:text-emerald-300">
            <div class="flex items-center gap-2">
                <span class="text-base">✓</span>
                <span>بل ڈیزائن کامیابی سے محفوظ ہو گیا! Template settings saved successfully.</span>
            </div>
            <button type="button" wire:click="$set('savedSuccess', false)" class="text-emerald-600 hover:text-emerald-900">✕</button>
        </div>
    @endif

    {{-- 2-Column Layout: Controls (Left) & Live Preview (Right) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Left: Controls Panel (5 cols) --}}
        <div class="lg:col-span-5 space-y-6">
            {{-- Template Selector --}}
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm dark:bg-slate-900 dark:border-slate-800 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                    <span>1. Template Preset</span>
                    <span>📑</span>
                </h2>

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Select Preset</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" wire:click="selectTemplate('modern_red_band')"
                                @class([
                                    'p-2.5 rounded-lg text-xs font-bold border transition text-center',
                                    'border-[#D71920] bg-red-50 text-[#D71920] dark:bg-red-950/40' => $slug === 'modern_red_band',
                                    'border-slate-200 hover:border-slate-300 bg-white text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300' => $slug !== 'modern_red_band',
                                ])>
                            Modern Red Band
                        </button>

                        <button type="button" wire:click="selectTemplate('classic')"
                                @class([
                                    'p-2.5 rounded-lg text-xs font-bold border transition text-center',
                                    'border-[#D71920] bg-red-50 text-[#D71920] dark:bg-red-950/40' => $slug === 'classic',
                                    'border-slate-200 hover:border-slate-300 bg-white text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300' => $slug !== 'classic',
                                ])>
                            Classic
                        </button>

                        <button type="button" wire:click="selectTemplate('minimal')"
                                @class([
                                    'p-2.5 rounded-lg text-xs font-bold border transition text-center',
                                    'border-[#D71920] bg-red-50 text-[#D71920] dark:bg-red-950/40' => $slug === 'minimal',
                                    'border-slate-200 hover:border-slate-300 bg-white text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300' => $slug !== 'minimal',
                                ])>
                            Minimal
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Template Name</label>
                    <input type="text" wire:model.live="name"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-[#D71920] focus:ring-[#D71920] dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                </div>
            </div>

            {{-- Brand Colors & Quick Palettes --}}
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm dark:bg-slate-900 dark:border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">2. Brand Colors</h2>
                    <span class="text-xs">🎨</span>
                </div>

                {{-- Quick Palettes --}}
                <div>
                    <label class="block text-[11px] font-medium text-slate-500 mb-1.5">Quick Palette Themes</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button type="button" wire:click="applyPalette('vital_red')"
                                class="p-2 rounded-lg border border-slate-200 hover:border-slate-400 text-center transition flex flex-col items-center gap-1">
                            <span class="w-6 h-6 rounded-full bg-[#D71920] shadow-sm"></span>
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Vital Red</span>
                        </button>

                        <button type="button" wire:click="applyPalette('corporate_navy')"
                                class="p-2 rounded-lg border border-slate-200 hover:border-slate-400 text-center transition flex flex-col items-center gap-1">
                            <span class="w-6 h-6 rounded-full bg-[#1E293B] shadow-sm"></span>
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Navy</span>
                        </button>

                        <button type="button" wire:click="applyPalette('emerald_green')"
                                class="p-2 rounded-lg border border-slate-200 hover:border-slate-400 text-center transition flex flex-col items-center gap-1">
                            <span class="w-6 h-6 rounded-full bg-[#059669] shadow-sm"></span>
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Emerald</span>
                        </button>

                        <button type="button" wire:click="applyPalette('slate_charcoal')"
                                class="p-2 rounded-lg border border-slate-200 hover:border-slate-400 text-center transition flex flex-col items-center gap-1">
                            <span class="w-6 h-6 rounded-full bg-[#334155] shadow-sm"></span>
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">Charcoal</span>
                        </button>
                    </div>
                </div>

                {{-- Detailed Color Pickers --}}
                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Primary Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="primary_color" class="w-8 h-8 rounded border p-0 cursor-pointer">
                            <input type="text" wire:model.live="primary_color" class="w-full text-xs font-mono rounded border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dark Accent</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="dark_red_color" class="w-8 h-8 rounded border p-0 cursor-pointer">
                            <input type="text" wire:model.live="dark_red_color" class="w-full text-xs font-mono rounded border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Header BG</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="header_bg" class="w-8 h-8 rounded border p-0 cursor-pointer">
                            <input type="text" wire:model.live="header_bg" class="w-full text-xs font-mono rounded border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Header Text</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="header_text" class="w-8 h-8 rounded border p-0 cursor-pointer">
                            <input type="text" wire:model.live="header_text" class="w-full text-xs font-mono rounded border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Footer BG</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="footer_bg" class="w-8 h-8 rounded border p-0 cursor-pointer">
                            <input type="text" wire:model.live="footer_bg" class="w-full text-xs font-mono rounded border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Footer Text</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="footer_text" class="w-8 h-8 rounded border p-0 cursor-pointer">
                            <input type="text" wire:model.live="footer_text" class="w-full text-xs font-mono rounded border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Field Toggles --}}
            <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-sm dark:bg-slate-900 dark:border-slate-800 space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                    <span>3. Elements & Field Toggles</span>
                    <span>🔘</span>
                </h2>

                <div class="space-y-2 text-xs divide-y divide-slate-100 dark:divide-slate-800">
                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show Brand Logo / مونوگرام</span>
                        <input type="checkbox" wire:model.live="show_logo" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show Urdu Station Name / اردو نام</span>
                        <input type="checkbox" wire:model.live="show_urdu_name" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show Customer Box / کسٹمر باکس</span>
                        <input type="checkbox" wire:model.live="show_customer_box" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show Vehicle Info / گاڑی نمبر</span>
                        <input type="checkbox" wire:model.live="show_vehicle" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show Amount in Urdu Words / الفاظ میں رقم</span>
                        <input type="checkbox" wire:model.live="show_amount_in_words" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show QR Code / کیو آر کوڈ</span>
                        <input type="checkbox" wire:model.live="show_qr_code" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show Customer Udhaar Balance / ادھار بقایا</span>
                        <input type="checkbox" wire:model.live="show_udhaar_balance" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300">Show Signatures / دستخط</span>
                        <input type="checkbox" wire:model.live="show_signatures" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>

                    <label class="flex items-center justify-between py-1.5 cursor-pointer">
                        <span class="font-medium text-slate-700 dark:text-slate-300 font-bold">Set as Default Template</span>
                        <input type="checkbox" wire:model.live="is_default" class="rounded text-[#D71920] focus:ring-[#D71920]">
                    </label>
                </div>
            </div>
        </div>

        {{-- Right: Live Interactive Preview Panel (7 cols) --}}
        <div class="lg:col-span-7 space-y-4 sticky top-20">
            {{-- Preview Controls Toolbar --}}
            <div class="flex items-center justify-between bg-white rounded-xl border border-slate-200/80 p-3 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Live Preview:</span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-red-100 text-[#D71920]">
                        {{ $name }}
                    </span>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" wire:click="$set('previewFormat', 'a4')"
                            @class([
                                'px-3 py-1 text-xs font-bold rounded transition',
                                'bg-[#D71920] text-white shadow-sm' => $previewFormat === 'a4',
                                'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' => $previewFormat !== 'a4',
                            ])>
                        A4 Paper
                    </button>
                    <button type="button" wire:click="$set('previewFormat', 'thermal_80mm')"
                            @class([
                                'px-3 py-1 text-xs font-bold rounded transition',
                                'bg-[#D71920] text-white shadow-sm' => $previewFormat === 'thermal_80mm',
                                'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' => $previewFormat !== 'thermal_80mm',
                            ])>
                        Thermal 80mm
                    </button>
                    <button type="button" wire:click="$set('previewFormat', 'thermal_58mm')"
                            @class([
                                'px-3 py-1 text-xs font-bold rounded transition',
                                'bg-[#D71920] text-white shadow-sm' => $previewFormat === 'thermal_58mm',
                                'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' => $previewFormat !== 'thermal_58mm',
                            ])>
                        58mm
                    </button>
                </div>
            </div>

            {{-- Live Simulated Paper Container --}}
            <div class="bg-slate-200/80 p-4 rounded-2xl flex justify-center overflow-auto max-h-[800px] border border-slate-300 shadow-inner">
                @if ($previewFormat === 'a4')
                    {{-- Simulated A4 Sheet --}}
                    <div style="background-color: {{ $background_color }}; color: {{ $text_color }}; width: 100%; max-width: 580px; min-height: 720px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);"
                         class="rounded border border-slate-300 text-[11px] flex flex-col justify-between overflow-hidden">
                        <div>
                            {{-- Header Band --}}
                            <div style="background-color: {{ $header_bg }}; color: {{ $header_text }}; border-bottom: 3px solid {{ $dark_red_color }};"
                                 class="p-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    @if ($show_logo)
                                        <div class="w-10 h-10 rounded bg-white p-1 flex items-center justify-center shadow-sm">
                                            <svg viewBox="0 0 100 100" width="30" height="30">
                                                <circle cx="50" cy="50" r="46" fill="#D71920" />
                                                <circle cx="50" cy="50" r="39" fill="#FFFFFF" />
                                                <polygon points="50,16 78,74 65,74 50,42 35,74 22,74" fill="#D71920" />
                                                <circle cx="50" cy="74" r="5" fill="#D71920" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-black text-sm uppercase tracking-wide">
                                            {{ $station['station_name_en'] ?? 'MEHAR FILLING STATION' }}
                                        </div>
                                        @if ($show_urdu_name)
                                            <div class="font-bold text-xs font-urdu opacity-95">
                                                {{ $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن' }}
                                            </div>
                                        @endif
                                        <div class="text-[9px] opacity-80 mt-0.5">
                                            Vital Petroleum Franchise • Sheikhupura
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <span class="inline-block bg-white text-[9px] font-black uppercase px-2 py-0.5 rounded shadow-sm" style="color: {{ $primary_color }};">
                                        Tax Invoice
                                    </span>
                                    <div class="font-bold text-xs mt-1">MFS-2026-000123</div>
                                    <div class="text-[9px] opacity-80">{{ now()->format('d M Y') }}</div>
                                </div>
                            </div>

                            {{-- Meta Bar --}}
                            <div style="background-color: {{ $light_grey_color }};" class="px-4 py-1.5 text-[9px] border-b border-slate-200 flex justify-between text-slate-600">
                                <div>📍 Sheikhupura–Sharaqpur Road, Sheikhupura</div>
                                <div>📞 0300-4342343</div>
                            </div>

                            {{-- Customer Box --}}
                            @if ($show_customer_box)
                                <div class="p-4 grid grid-cols-2 gap-3 border-b border-slate-200">
                                    <div class="p-2 rounded border border-slate-200 bg-white text-[10px]">
                                        <div class="font-bold text-slate-500 uppercase text-[9px]">Bill To:</div>
                                        <div class="font-bold text-slate-800 text-xs">Haji Muhammad Akram (اکرم ٹریڈرز)</div>
                                        <div class="text-slate-500">Phone: 0301-7654321 • NTN: 4123984-7</div>
                                        @if ($show_vehicle)
                                            <div class="mt-1 font-mono font-bold text-slate-700">🚗 LES-2024-9872</div>
                                        @endif
                                    </div>

                                    <div class="p-2 rounded border border-slate-200 bg-white text-[10px]">
                                        <div class="font-bold text-slate-500 uppercase text-[9px]">Payment & Attendant:</div>
                                        <div class="text-slate-700">Method: <strong>Credit (کھاتہ ادھار)</strong></div>
                                        <div class="text-slate-700">Attendant: <strong>Muhammad Rizwan</strong></div>
                                        <div class="text-slate-700">Shift: <strong>#124</strong></div>
                                    </div>
                                </div>
                            @endif

                            {{-- Items Table --}}
                            <div class="p-4">
                                <table class="w-full text-left text-[10px] border-collapse">
                                    <thead>
                                        <tr style="background-color: {{ $primary_color }}; color: #ffffff;">
                                            <th class="py-1 px-2">#</th>
                                            <th class="py-1 px-2">Product</th>
                                            <th class="py-1 px-2 text-right">Litres</th>
                                            <th class="py-1 px-2 text-right">Rate</th>
                                            <th class="py-1 px-2 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td class="py-1.5 px-2">1</td>
                                            <td class="py-1.5 px-2 font-bold">Super Petrol (پٹرول)</td>
                                            <td class="py-1.5 px-2 text-right font-mono">50.000 L</td>
                                            <td class="py-1.5 px-2 text-right font-mono">285.50</td>
                                            <td class="py-1.5 px-2 text-right font-bold font-mono">Rs. 14,275.00</td>
                                        </tr>
                                        <tr class="bg-slate-50">
                                            <td class="py-1.5 px-2">2</td>
                                            <td class="py-1.5 px-2 font-bold">High Speed Diesel (ڈیزل)</td>
                                            <td class="py-1.5 px-2 text-right font-mono">20.000 L</td>
                                            <td class="py-1.5 px-2 text-right font-mono">290.00</td>
                                            <td class="py-1.5 px-2 text-right font-bold font-mono">Rs. 5,800.00</td>
                                        </tr>
                                    </tbody>
                                </table>

                                {{-- Totals Box --}}
                                <div class="mt-4 grid grid-cols-2 gap-3 items-start">
                                    {{-- Left: Urdu Words --}}
                                    <div class="p-2 rounded bg-slate-50 border border-slate-200">
                                        @if ($show_amount_in_words)
                                            <div class="text-[9px] uppercase font-bold text-slate-500">Amount in Words:</div>
                                            <div class="text-xs font-bold font-urdu mt-0.5" style="color: {{ $dark_red_color }};">
                                                بیس ہزار پچھتر روپے صرف
                                            </div>
                                            <div class="text-[9px] text-slate-500 mt-0.5">
                                                Twenty Thousand Seventy-Five Rupees Only
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Right: Total Card --}}
                                    <div class="rounded border overflow-hidden" style="border-color: {{ $primary_color }};">
                                        <div class="p-1 px-2 text-[9px] flex justify-between">
                                            <span>Subtotal:</span>
                                            <span class="font-bold">Rs. 20,075.00</span>
                                        </div>
                                        <div class="p-1 px-2 text-[9px] flex justify-between">
                                            <span>POS Fee:</span>
                                            <span>Rs. 1.00</span>
                                        </div>
                                        <div class="p-1.5 px-2 text-xs font-black flex justify-between text-white" style="background-color: {{ $primary_color }};">
                                            <span>TOTAL:</span>
                                            <span>Rs. 20,076.00</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Udhaar Balance --}}
                                @if ($show_udhaar_balance)
                                    <div class="mt-2 p-1.5 bg-amber-50 border border-amber-200 rounded text-[9px] text-amber-900 flex justify-between">
                                        <span>Customer Udhaar Balance (سابقہ + بل):</span>
                                        <strong>Total Due: Rs. 1,45,850.00</strong>
                                    </div>
                                @endif

                                {{-- QR Code Row --}}
                                @if ($show_qr_code)
                                    <div class="mt-3 p-2 bg-slate-50 border border-slate-200 rounded flex items-center gap-3">
                                        <div class="w-12 h-12 bg-white border p-0.5 rounded flex items-center justify-center">
                                            <svg viewBox="0 0 100 100" width="40" height="40">
                                                <rect width="100" height="100" fill="#fff"/>
                                                <rect x="10" y="10" width="30" height="30" fill="#000"/>
                                                <rect x="15" y="15" width="20" height="20" fill="#fff"/>
                                                <rect x="20" y="20" width="10" height="10" fill="#000"/>
                                                <rect x="60" y="10" width="30" height="30" fill="#000"/>
                                                <rect x="65" y="15" width="20" height="20" fill="#fff"/>
                                                <rect x="70" y="20" width="10" height="10" fill="#000"/>
                                                <rect x="10" y="60" width="30" height="30" fill="#000"/>
                                                <rect x="15" y="65" width="20" height="20" fill="#fff"/>
                                                <rect x="20" y="70" width="10" height="10" fill="#000"/>
                                                <rect x="50" y="50" width="15" height="15" fill="#000"/>
                                                <rect x="70" y="70" width="15" height="15" fill="#000"/>
                                            </svg>
                                        </div>
                                        <div class="text-[9px] text-slate-600">
                                            <div class="font-bold text-emerald-700">✓ Genuine Tax Invoice Verified</div>
                                            <div>Scan to verify online on Mehar Filling Station portal</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Signatures --}}
                                @if ($show_signatures)
                                    <div class="mt-4 grid grid-cols-2 gap-8 text-center text-[9px] text-slate-500">
                                        <div>
                                            <div class="border-b border-dashed border-slate-400 mb-1 h-4"></div>
                                            <span>Customer Signature</span>
                                        </div>
                                        <div>
                                            <div class="border-b border-dashed border-slate-400 mb-1 h-4"></div>
                                            <span>Authorized Signature & Stamp</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Footer Band --}}
                        <div style="background-color: {{ $footer_bg }}; color: {{ $footer_text }}; border-top: 2px solid {{ $dark_red_color }};"
                             class="p-2 px-4 text-[9px] flex justify-between font-medium">
                            <div>Thank you! Drive safely • ہماری سروس استعمال کرنے کا شکریہ</div>
                            <div>Powered by Wafa Tech ERP</div>
                        </div>
                    </div>
                @else
                    {{-- Simulated Thermal Receipt (80mm or 58mm) --}}
                    <div style="width: {{ $previewFormat === 'thermal_58mm' ? '240px' : '320px' }};"
                         class="bg-white p-3 font-mono text-[11px] shadow-lg rounded text-slate-900 leading-tight">
                        <div class="text-center font-bold">
                            <div class="text-xs">MEHAR FILLING STATION</div>
                            <div class="font-urdu text-sm">مہر فلنگ اسٹیشن</div>
                            <div class="text-[9px]">VITAL PETROLEUM (وائٹل پیٹرول)</div>
                            <div class="text-[9px]">Sheikhupura–Sharaqpur Road</div>
                            <div class="text-[9px]">Tel: 0300-4342343</div>
                        </div>

                        <div class="border-t border-b border-black my-1.5 py-0.5 text-center font-bold text-[10px]">
                            TAX INVOICE / کیش میمو
                        </div>

                        <div class="flex justify-between text-[10px]">
                            <span>Inv:</span>
                            <span class="font-bold">MFS-2026-000123</span>
                        </div>
                        <div class="flex justify-between text-[10px]">
                            <span>Date:</span>
                            <span>{{ now()->format('d/m/Y h:i A') }}</span>
                        </div>

                        @if ($show_customer_box)
                            <div class="border-t border-dashed border-slate-400 my-1 pt-1 text-[10px]">
                                <div>Cust: Haji M. Akram</div>
                                @if ($show_vehicle)
                                    <div>Veh: LES-2024-9872</div>
                                @endif
                            </div>
                        @endif

                        <div class="border-t border-dashed border-slate-400 my-1 pt-1">
                            <div class="font-bold">Super Petrol</div>
                            <div class="flex justify-between text-[10px]">
                                <span>50.000 L @ 285.50</span>
                                <span class="font-bold">Rs. 14,275</span>
                            </div>
                        </div>

                        <div class="border-t border-black my-1 pt-1">
                            <div class="flex justify-between font-bold text-xs">
                                <span>TOTAL:</span>
                                <span>Rs. 14,275</span>
                            </div>
                        </div>

                        @if ($show_amount_in_words)
                            <div class="text-center font-urdu font-bold text-xs my-1" style="color: {{ $primary_color }};">
                                چودہ ہزار دو سو پچہتر روپے صرف
                            </div>
                        @endif

                        @if ($show_udhaar_balance)
                            <div class="border-t border-dashed border-slate-400 my-1 pt-1 text-[9px]">
                                <div>Total Udhaar Balance: Rs. 1,45,850</div>
                            </div>
                        @endif

                        @if ($show_qr_code)
                            <div class="text-center my-2">
                                <div class="inline-block p-1 border">
                                    <svg viewBox="0 0 100 100" width="50" height="50">
                                        <rect width="100" height="100" fill="#fff"/>
                                        <rect x="10" y="10" width="30" height="30" fill="#000"/>
                                        <rect x="15" y="15" width="20" height="20" fill="#fff"/>
                                        <rect x="20" y="20" width="10" height="10" fill="#000"/>
                                        <rect x="60" y="10" width="30" height="30" fill="#000"/>
                                        <rect x="65" y="15" width="20" height="20" fill="#fff"/>
                                        <rect x="70" y="20" width="10" height="10" fill="#000"/>
                                        <rect x="10" y="60" width="30" height="30" fill="#000"/>
                                        <rect x="15" y="65" width="20" height="20" fill="#fff"/>
                                        <rect x="20" y="70" width="10" height="10" fill="#000"/>
                                        <rect x="50" y="50" width="15" height="15" fill="#000"/>
                                    </svg>
                                </div>
                                <div class="text-[8px]">Scan to Verify Bill</div>
                            </div>
                        @endif

                        <div class="border-t border-black my-1 pt-1 text-center text-[9px]">
                            <div>Thank you! Drive safely.</div>
                            <div class="font-urdu">شکریہ! باحفاظت سفر کریں</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
