<div class="{{ $simpleMode ? 'simple-mode' : '' }} min-h-screen bg-slate-900 py-0 sm:py-6"
     x-data="{
        voiceSearching: false,
        idleTime: 0,
        init() {
            // Auto-lock inactivity tracker (120 seconds)
            const resetIdle = () => { this.idleTime = 0; };
            window.addEventListener('mousemove', resetIdle, { passive: true });
            window.addEventListener('touchstart', resetIdle, { passive: true });
            window.addEventListener('keydown', resetIdle, { passive: true });
            setInterval(() => {
                this.idleTime++;
                if (this.idleTime >= 120 && ! $wire.isLocked) {
                    $wire.lockScreen();
                }
            }, 1000);
        },
        startVoiceSearch() {
            if (! ('webkitSpeechRecognition' in window) && ! ('SpeechRecognition' in window)) {
                alert('Voice recognition not supported in this browser. Please type your search.');
                return;
            }
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.lang = '{{ $lang === 'ur' ? 'ur-PK' : 'en-US' }}';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            this.voiceSearching = true;
            recognition.start();

            recognition.onresult = (event) => {
                this.voiceSearching = false;
                const transcript = event.results[0][0].transcript;
                $wire.handleVoiceSearch(transcript);
            };

            recognition.onerror = () => {
                this.voiceSearching = false;
            };

            recognition.onend = () => {
                this.voiceSearching = false;
            };
        }
     }">

    {{-- Center Mobile Frame (360px to 430px on desktop, full-width responsive on mobile) --}}
    <div class="w-full max-w-[430px] mx-auto min-h-screen bg-slate-100 dark:bg-slate-950 sm:rounded-[36px] sm:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] overflow-hidden flex flex-col relative border-x border-slate-200/40 dark:border-slate-800">

        {{-- ================= 1. Top Bar ================= --}}
        <header class="sticky top-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-3.5 py-2.5 shadow-sm">
            <div class="flex items-center justify-between gap-2">

                {{-- Vital Logo & Station Identity --}}
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-2xl bg-vital-primary flex items-center justify-center text-white font-black text-xl shadow-md border-2 border-white dark:border-slate-800">
                        V
                    </div>
                    <div class="leading-tight">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-black text-vital-primary uppercase tracking-wider">VITAL</span>
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">{{ $lang === 'ur' ? 'مہر فلنگ اسٹیشن' : 'Mehar Station' }}</span>
                        </div>
                        <div class="text-[10px] text-slate-500 font-medium">
                            {{ $lang === 'ur' ? $currentTimeUrdu : $currentTimeEn }}
                        </div>
                    </div>
                </div>

                {{-- Action Icons: Notification, Language, Simple Mode, Help, Auto-Lock --}}
                <div class="flex items-center gap-1.5">

                    {{-- Simple Mode Toggle (Low-end devices) --}}
                    <button type="button"
                            wire:click="toggleSimpleMode"
                            class="px-2 py-1 rounded-lg text-[10px] font-bold transition {{ $simpleMode ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}"
                            title="{{ $lang === 'ur' ? 'سادہ موڈ (بغیر اینیمیشن)' : 'Simple Mode (No Animation)' }}">
                        {{ $simpleMode ? 'سادہ' : '3D' }}
                    </button>

                    {{-- Language Toggle --}}
                    <button type="button"
                            wire:click="toggleLanguage"
                            class="px-2 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300 transition">
                        {{ $lang === 'ur' ? 'EN' : 'اردو' }}
                    </button>

                    {{-- Notifications with Red Badge --}}
                    <div class="relative">
                        <button type="button"
                                wire:click="toggleMoreDrawer"
                                class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm hover:bg-slate-200 transition">
                            🔔
                        </button>
                        @if (count($metrics['alerts']) > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-vital-primary text-white text-[10px] font-bold flex items-center justify-center shadow animate-pulse">
                                {{ count($metrics['alerts']) }}
                            </span>
                        @endif
                    </div>

                    {{-- Red Help Button --}}
                    <button type="button"
                            wire:click="openHelpModal"
                            class="bg-vital-primary hover:bg-vital-darkred text-white text-xs font-bold px-2 py-1 rounded-lg shadow transition flex items-center gap-0.5"
                            title="Help & Emergency">
                        <span>❓</span>
                        <span class="text-[11px]">{{ $lang === 'ur' ? 'مدد' : 'Help' }}</span>
                    </button>

                    {{-- Quick Lock Button --}}
                    <button type="button"
                            wire:click="lockScreen"
                            class="w-7 h-7 rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs hover:bg-slate-300"
                            title="Lock Forecourt App">
                        🔒
                    </button>

                </div>

            </div>

            {{-- ================= 2. Live Global Search Bar ================= --}}
            <div class="mt-2.5 relative">
                <div class="relative flex items-center">
                    <span class="absolute left-3 text-slate-400 text-sm">🔍</span>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchQuery"
                           placeholder="{{ $lang === 'ur' ? 'گاہک، بل نمبر، گاڑی نمبر تلاش کریں...' : 'Search customer, invoice, vehicle...' }}"
                           class="w-full pl-9 pr-10 py-2 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-vital-primary focus:bg-white dark:focus:bg-slate-800 transition">

                    {{-- Voice Search Trigger Button --}}
                    <button type="button"
                            @click="startVoiceSearch()"
                            :class="{ 'voice-recording-pulse text-vital-primary': voiceSearching, 'text-slate-400 hover:text-vital-primary': ! voiceSearching }"
                            class="absolute right-2.5 p-1 rounded-full text-sm transition"
                            title="Voice search / آواز سے تلاش کریں">
                        🎤
                    </button>
                </div>

                {{-- Voice search listening indicator --}}
                <div x-show="voiceSearching" x-cloak class="absolute z-50 left-0 right-0 mt-1 bg-vital-primary text-white text-xs py-1.5 px-3 rounded-xl shadow-lg flex items-center justify-between animate-pulse">
                    <span>{{ $lang === 'ur' ? 'بولیں، سن رہا ہے... (گاہک، بل، گاڑی)' : 'Listening... Speak now' }}</span>
                    <span class="text-sm">🎙️</span>
                </div>

                {{-- Live Search Results Dropdown --}}
                @if ($isSearching && strlen(trim($searchQuery)) >= 2)
                    <div class="absolute z-50 left-0 right-0 mt-1.5 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-h-72 overflow-y-auto p-2 divide-y divide-slate-100 dark:divide-slate-800">
                        <div class="flex items-center justify-between pb-1 px-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $lang === 'ur' ? 'تلاش کے نتائج' : 'Search Results' }}</span>
                            <button type="button" wire:click="clearSearch" class="text-[10px] text-slate-400 hover:text-slate-600">✕ بند کریں</button>
                        </div>

                        {{-- Customers --}}
                        @if (! empty($searchResults['customers']))
                            <div class="py-1">
                                <div class="text-[10px] font-bold text-indigo-600 px-2 py-0.5">👥 {{ $lang === 'ur' ? 'گاہک' : 'Customers' }}</div>
                                @foreach ($searchResults['customers'] as $c)
                                    <a href="{{ $c['link'] }}" class="flex items-center justify-between px-2.5 py-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $c['title'] }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $c['subtitle'] }}</div>
                                        </div>
                                        <div class="text-[11px] font-bold text-vital-primary">{{ $c['meta'] }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Sales / Bills --}}
                        @if (! empty($searchResults['sales']))
                            <div class="py-1">
                                <div class="text-[10px] font-bold text-vital-primary px-2 py-0.5">🧾 {{ $lang === 'ur' ? 'انوائسز / بل' : 'Sales / Bills' }}</div>
                                @foreach ($searchResults['sales'] as $s)
                                    <a href="{{ $s['link'] }}" class="flex items-center justify-between px-2.5 py-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $s['title'] }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $s['subtitle'] }}</div>
                                        </div>
                                        <div class="text-[11px] font-bold text-emerald-600">{{ $s['meta'] }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Vehicles --}}
                        @if (! empty($searchResults['vehicles']))
                            <div class="py-1">
                                <div class="text-[10px] font-bold text-amber-600 px-2 py-0.5">🚗 {{ $lang === 'ur' ? 'گاڑیاں' : 'Vehicles' }}</div>
                                @foreach ($searchResults['vehicles'] as $v)
                                    <a href="{{ $v['link'] }}" class="flex items-center justify-between px-2.5 py-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $v['title'] }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $v['subtitle'] }}</div>
                                        </div>
                                        <div class="text-[10px] bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-slate-600">{{ $v['meta'] }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if (empty($searchResults['customers']) && empty($searchResults['sales']) && empty($searchResults['vehicles']))
                            <div class="py-3 text-center text-xs text-slate-400">
                                {{ $lang === 'ur' ? 'کوئی نتیجہ نہیں ملا' : 'No records found' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </header>

        {{-- ================= Scrollable Content Area ================= --}}
        <main class="flex-1 px-3.5 py-3 space-y-3.5 pb-24 overflow-y-auto no-scrollbar" wire:poll.15s="refreshMetrics">

            {{-- ================= 3. 3D Hero Card ("آج / TODAY") ================= --}}
            <section class="vital-hero-card rounded-3xl p-4 text-white relative overflow-hidden">
                {{-- Decorative background glow --}}
                <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/10 blur-xl pointer-events-none"></div>

                {{-- Header row --}}
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-300 animate-ping"></span>
                        <span class="text-xs font-black tracking-wider uppercase opacity-95">
                            {{ $lang === 'ur' ? 'آج کی صورتحال / TODAY' : 'TODAY / Forecourt' }}
                        </span>
                    </div>
                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-bold">
                        {{ $metrics['hero']['today_litres'] }} L
                    </span>
                </div>

                {{-- 3-Column Metrics Grid: Total Sales, Cash in Hand, Profit --}}
                <div class="grid grid-cols-3 gap-2 text-center pt-1 pb-2">

                    {{-- Total Sales (کل سیل) --}}
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-2 border border-white/10">
                        <div class="text-[10px] font-bold text-amber-200 uppercase tracking-tight">
                            {{ $lang === 'ur' ? 'کل سیل' : 'Total Sales' }}
                        </div>
                        <div class="text-sm sm:text-base font-black tracking-tight mt-0.5 text-white tabular">
                            {{ $metrics['hero']['today_sales_formatted'] }}
                        </div>
                        <div class="text-[9px] font-bold mt-1 flex items-center justify-center gap-0.5 {{ $metrics['hero']['sales_trend_dir'] === 'up' ? 'text-emerald-300' : ($metrics['hero']['sales_trend_dir'] === 'down' ? 'text-rose-200' : 'text-slate-300') }}">
                            <span>{{ $metrics['hero']['sales_trend_dir'] === 'up' ? '▲' : ($metrics['hero']['sales_trend_dir'] === 'down' ? '▼' : '—') }}</span>
                            <span>{{ $metrics['hero']['sales_trend'] }}</span>
                        </div>
                    </div>

                    {{-- Cash in Hand (کیش ہاتھ میں) --}}
                    <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-2 border border-white/20 shadow-inner">
                        <div class="text-[10px] font-bold text-amber-200 uppercase tracking-tight">
                            {{ $lang === 'ur' ? 'کیش ہاتھ میں' : 'Cash in Hand' }}
                        </div>
                        <div class="text-sm sm:text-base font-black tracking-tight mt-0.5 text-white tabular">
                            {{ $metrics['hero']['cash_in_hand_formatted'] }}
                        </div>
                        <div class="text-[9px] font-bold mt-1 flex items-center justify-center gap-0.5 {{ $metrics['hero']['cash_trend_dir'] === 'up' ? 'text-emerald-300' : 'text-rose-200') }}">
                            <span>{{ $metrics['hero']['cash_trend_dir'] === 'up' ? '▲' : '▼' }}</span>
                            <span>{{ $metrics['hero']['cash_trend'] }}</span>
                        </div>
                    </div>

                    {{-- Gross Profit (منافع) --}}
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-2 border border-white/10">
                        <div class="text-[10px] font-bold text-amber-200 uppercase tracking-tight">
                            {{ $lang === 'ur' ? 'منافع' : 'Gross Profit' }}
                        </div>
                        <div class="text-sm sm:text-base font-black tracking-tight mt-0.5 text-white tabular">
                            {{ $metrics['hero']['profit_formatted'] }}
                        </div>
                        <div class="text-[9px] font-bold mt-1 flex items-center justify-center gap-0.5 {{ $metrics['hero']['profit_trend_dir'] === 'up' ? 'text-emerald-300' : 'text-rose-200') }}">
                            <span>{{ $metrics['hero']['profit_trend_dir'] === 'up' ? '▲' : '▼' }}</span>
                            <span>{{ $metrics['hero']['profit_trend'] }}</span>
                        </div>
                    </div>

                </div>

                {{-- Urdu Amount in Words --}}
                <div class="text-center pt-1 border-t border-white/15">
                    <span class="text-[11px] font-medium text-amber-100 opacity-95">
                        {{ $metrics['hero']['today_sales_words'] }}
                    </span>
                </div>
            </section>

            {{-- ================= 4. Current Shift Card (3D Livewire polling 15s) ================= --}}
            <section class="touch-tile-3d bg-white dark:bg-slate-900 rounded-3xl p-3.5 border border-slate-200 dark:border-slate-800">
                @if ($metrics['shift'])
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 mb-2.5">
                        <div class="flex items-center gap-2">
                            <img src="{{ $metrics['shift']['cashier_avatar'] }}" alt="avatar" class="w-8 h-8 rounded-full border border-slate-200 object-cover shadow-sm">
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-black text-slate-800 dark:text-slate-100">{{ $metrics['shift']['cashier_name'] }}</span>
                                    <span class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.2 rounded">{{ $metrics['shift']['shift_number'] }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $metrics['shift']['duration_human'] }} ({{ $metrics['shift']['opened_at'] }})
                                </div>
                            </div>
                        </div>

                        {{-- Close Shift Button --}}
                        <a href="{{ $metrics['shift']['close_url'] }}"
                           class="text-xs font-bold bg-rose-50 text-vital-primary border border-red-200 hover:bg-vital-primary hover:text-white px-2.5 py-1 rounded-xl transition shadow-sm">
                            {{ $lang === 'ur' ? 'شفٹ بند کریں' : 'Close Shift' }}
                        </a>
                    </div>

                    {{-- Shift metrics mini-grid: Litres sold, Cash collected, Udhaar --}}
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-1.5">
                            <div class="text-[9px] text-slate-400 uppercase font-bold">{{ $lang === 'ur' ? 'فروخت لیٹر' : 'Litres' }}</div>
                            <div class="text-xs font-black text-slate-800 dark:text-slate-200 tabular">{{ $metrics['shift']['litres_sold_formatted'] }}</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-1.5">
                            <div class="text-[9px] text-slate-400 uppercase font-bold">{{ $lang === 'ur' ? 'کیش وصولی' : 'Cash Coll.' }}</div>
                            <div class="text-xs font-black text-emerald-600 tabular">{{ $metrics['shift']['cash_collected_formatted'] }}</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-1.5">
                            <div class="text-[9px] text-slate-400 uppercase font-bold">{{ $lang === 'ur' ? 'ادھار / کریڈٹ' : 'Credit/Udhaar' }}</div>
                            <div class="text-xs font-black text-vital-primary tabular">{{ $metrics['shift']['udhaar_formatted'] }}</div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between py-1">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">⏱️</div>
                            <div>
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    {{ $lang === 'ur' ? 'کوئی شفٹ کھلی نہیں ہے' : 'No Open Shift' }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    {{ $lang === 'ur' ? 'فروخت کے لیے شفٹ شروع کریں' : 'Open a shift to start forecourt POS' }}
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('shifts.create') }}" class="text-xs font-bold bg-vital-primary text-white px-3 py-1.5 rounded-xl hover:bg-vital-darkred transition shadow">
                            {{ $lang === 'ur' ? 'اوپن شفٹ' : 'Open Shift' }}
                        </a>
                    </div>
                @endif
            </section>

            {{-- ================= 5. Animated 3D Cylindrical Fuel Tanks ================= --}}
            <section class="touch-tile-3d bg-white dark:bg-slate-900 rounded-3xl p-3.5 border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center gap-1.5">
                        <span class="text-base">🛢️</span>
                        <span class="text-xs font-black text-slate-800 dark:text-slate-200">
                            {{ $lang === 'ur' ? 'تیل ٹینک اسٹاک لیول' : 'Fuel Tank Stock Levels' }}
                        </span>
                    </div>
                    <a href="{{ route('tanks.index') }}" class="text-[10px] font-bold text-vital-primary hover:underline">
                        {{ $lang === 'ur' ? 'تفصیل' : 'Details' }} →
                    </a>
                </div>

                {{-- 3D Tanks Visualizer Grid --}}
                <div class="grid grid-cols-3 gap-2.5">
                    @foreach ($metrics['tanks'] as $tank)
                        <div class="flex flex-col items-center">
                            {{-- 3D Cylinder Vessel --}}
                            <div class="tank-3d-cylinder w-16 h-28 relative flex flex-col justify-end">
                                {{-- Liquid Wave Fill with dynamic height --}}
                                <div class="tank-liquid-wave"
                                     style="height: {{ max(10, min(100, $tank['percentage'])) }}%; background-color: {{ $tank['color'] }};">
                                    {{-- Surface elliptical shine --}}
                                    <div class="absolute top-0 inset-x-0 h-2 bg-white/40 rounded-full blur-[1px]"></div>
                                </div>

                                {{-- Cylindrical glass reflections --}}
                                <div class="tank-gloss-sheen"></div>

                                {{-- Overlay Percentage Tag --}}
                                <div class="relative z-10 text-center pb-1">
                                    <span class="text-[11px] font-black text-white drop-shadow-md">
                                        {{ $tank['percentage'] }}%
                                    </span>
                                </div>
                            </div>

                            {{-- Tank Title & Product --}}
                            <div class="mt-1.5 text-center leading-tight">
                                <div class="text-[10px] font-bold text-slate-800 dark:text-slate-200 truncate w-20">
                                    {{ $lang === 'ur' ? $tank['name'] : $tank['name_en'] }}
                                </div>
                                <div class="text-[9px] text-slate-400 font-medium">
                                    {{ number_format((float)$tank['current_stock'], 0) }} L
                                </div>

                                {{-- Warning status badge --}}
                                @if ($tank['warning'])
                                    <span class="inline-block mt-0.5 text-[8px] bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 font-bold px-1 rounded animate-pulse">
                                        {{ $lang === 'ur' ? 'کم اسٹاک' : 'Low' }}
                                    </span>
                                @else
                                    <span class="inline-block mt-0.5 text-[8px] bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-bold px-1 rounded">
                                        {{ $lang === 'ur' ? 'محفوظ' : 'Normal' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ================= 6. Alert Strip ================= --}}
            @if (! empty($metrics['alerts']))
                <section class="space-y-1.5">
                    @foreach ($metrics['alerts'] as $alert)
                        <a href="{{ $alert['link'] }}" class="flex items-center justify-between p-2.5 rounded-2xl bg-amber-50 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-900/60 shadow-sm transition hover:shadow">
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ $alert['icon'] }}</span>
                                <div class="leading-tight">
                                    <div class="text-[11px] font-bold text-amber-900 dark:text-amber-200">
                                        {{ $lang === 'ur' ? $alert['title_ur'] : $alert['title_en'] }}
                                    </div>
                                    <div class="text-[10px] text-amber-700 dark:text-amber-300">
                                        {{ $lang === 'ur' ? $alert['message_ur'] : $alert['message_en'] }}
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs text-amber-500 font-bold">→</span>
                        </a>
                    @endforeach
                </section>
            @endif

            {{-- ================= 7. Owner's One-Glance Summary Card ("مالک کا خلاصہ") ================= --}}
            @if (! $isCashier)
                <section class="touch-tile-3d bg-slate-900 text-white rounded-3xl p-4 border border-slate-800 shadow-xl">
                    <div class="flex items-center justify-between mb-3 border-b border-white/10 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">👑</span>
                            <div>
                                <h3 class="text-xs font-black text-amber-300 uppercase tracking-wider">
                                    {{ $lang === 'ur' ? 'مالک کا خلاصہ' : "Owner's Summary" }}
                                </h3>
                                <span class="text-[10px] text-slate-400">One-Glance Station Financials</span>
                            </div>
                        </div>
                        <span class="text-[10px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full font-bold">
                            {{ $lang === 'ur' ? 'فوری نظر' : 'Live' }}
                        </span>
                    </div>

                    {{-- 4x2 Grid of Key Station Balance Figures --}}
                    <div class="grid grid-cols-2 gap-2 text-left">
                        {{-- Forecourt Cash --}}
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="text-[9px] text-slate-400 uppercase font-semibold">{{ $lang === 'ur' ? 'کیش دست میں' : 'Cash in Hand' }}</div>
                            <div class="text-xs font-black text-emerald-400 tabular">{{ $metrics['owner_summary']['cash_in_hand_formatted'] }}</div>
                        </div>

                        {{-- Total Bank Balance --}}
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="text-[9px] text-slate-400 uppercase font-semibold">{{ $lang === 'ur' ? 'بینک بیلنس' : 'Bank Balance' }}</div>
                            <div class="text-xs font-black text-blue-400 tabular">{{ $metrics['owner_summary']['bank_balance_formatted'] }}</div>
                        </div>

                        {{-- Customer Udhaar --}}
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="text-[9px] text-slate-400 uppercase font-semibold">{{ $lang === 'ur' ? 'گاہکوں کا ادھار' : 'Customer Udhaar' }}</div>
                            <div class="text-xs font-black text-amber-400 tabular">{{ $metrics['owner_summary']['customer_udhaar_formatted'] }}</div>
                        </div>

                        {{-- Supplier Payable --}}
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="text-[9px] text-slate-400 uppercase font-semibold">{{ $lang === 'ur' ? 'سپلائر ادائیگی' : 'Payables' }}</div>
                            <div class="text-xs font-black text-rose-400 tabular">{{ $metrics['owner_summary']['supplier_payable_formatted'] }}</div>
                        </div>

                        {{-- Total Fuel Stock in Litres --}}
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="text-[9px] text-slate-400 uppercase font-semibold">{{ $lang === 'ur' ? 'کل ایندھن ذخیرہ' : 'Total Fuel Stock' }}</div>
                            <div class="text-xs font-black text-cyan-300 tabular">{{ $metrics['owner_summary']['total_fuel_litres'] }}</div>
                        </div>

                        {{-- Urgent Alerts --}}
                        <div class="bg-white/5 p-2 rounded-xl border border-white/5">
                            <div class="text-[9px] text-slate-400 uppercase font-semibold">{{ $lang === 'ur' ? 'فوری انتباہات' : 'Urgent Alerts' }}</div>
                            <div class="text-xs font-black text-rose-300 tabular">{{ $metrics['owner_summary']['alerts_count'] }}</div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- ================= 8. 18 3D Touch Tiles (3-Column Grid) ================= --}}
            <section>
                <div class="flex items-center justify-between mb-2 px-1">
                    <span class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                        {{ $lang === 'ur' ? 'ایپ لانچر ٹائلز' : 'App Launcher Tiles' }}
                    </span>
                    <span class="text-[10px] text-slate-400">
                        {{ count($tiles) }} {{ $lang === 'ur' ? 'شارٹ کٹس' : 'Tiles' }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2.5 entrance-stagger">
                    @foreach ($tiles as $tile)
                        @if (isset($tile['action']))
                            {{-- Action modal trigger button --}}
                            <button type="button"
                                    wire:click="openQuickAction('{{ $tile['action'] }}')"
                                    class="touch-tile-3d touch-tile-tilt bg-white dark:bg-slate-900 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center border border-slate-200 dark:border-slate-800 transition aspect-square hover:shadow-lg">
                                <div class="w-10 h-10 rounded-2xl {{ $tile['color'] }} flex items-center justify-center text-lg shadow-md mb-1.5 transition transform hover:scale-110">
                                    {{ $tile['icon'] }}
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                    {{ $lang === 'ur' ? $tile['title_ur'] : $tile['title_en'] }}
                                </div>
                                <div class="text-[9px] text-slate-400 mt-0.5 truncate max-w-full">
                                    {{ $lang === 'ur' ? $tile['desc_ur'] : $tile['desc_en'] }}
                                </div>
                            </button>
                        @else
                            {{-- Standard Route Link Tile --}}
                            <a href="{{ $tile['url'] }}"
                               class="touch-tile-3d touch-tile-tilt bg-white dark:bg-slate-900 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center border border-slate-200 dark:border-slate-800 transition aspect-square hover:shadow-lg">
                                <div class="w-10 h-10 rounded-2xl {{ $tile['color'] }} flex items-center justify-center text-lg shadow-md mb-1.5 transition transform hover:scale-110">
                                    {{ $tile['icon'] }}
                                </div>
                                <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                    {{ $lang === 'ur' ? $tile['title_ur'] : $tile['title_en'] }}
                                </div>
                                <div class="text-[9px] text-slate-400 mt-0.5 truncate max-w-full">
                                    {{ $lang === 'ur' ? $tile['desc_ur'] : $tile['desc_en'] }}
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>

        </main>

        {{-- ================= 9. Floating (+) Action Menu Speed-Dial ================= --}}
        @if ($showActionMenu)
            <div class="absolute inset-0 z-40 bg-slate-900/60 backdrop-blur-sm flex flex-col justify-end p-4 pb-24"
                 wire:click.self="toggleActionMenu">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-2 animate-in fade-in slide-in-from-bottom duration-200">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            {{ $lang === 'ur' ? 'فوری اقدامات (6 اہم ترین)' : 'Quick Forecourt Actions' }}
                        </span>
                        <button type="button" wire:click="toggleActionMenu" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        {{-- 1. Meter Reading --}}
                        <a href="{{ route('meter-readings.index') }}" class="flex items-center gap-2.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 transition">
                            <span class="text-xl">⛽</span>
                            <div class="text-left leading-tight">
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $lang === 'ur' ? 'میٹر ریڈنگ' : 'Meter' }}</div>
                                <div class="text-[9px] text-slate-400">ریڈنگ درج کریں</div>
                            </div>
                        </a>

                        {{-- 2. New Bill --}}
                        <a href="{{ route('pos.index') }}" class="flex items-center gap-2.5 p-2 rounded-xl bg-red-50 dark:bg-red-950/40 hover:bg-red-100 transition">
                            <span class="text-xl text-vital-primary">🧾</span>
                            <div class="text-left leading-tight">
                                <div class="text-xs font-bold text-vital-primary">{{ $lang === 'ur' ? 'نیا بل' : 'New Bill' }}</div>
                                <div class="text-[9px] text-slate-400">پی او ایس اسکرین</div>
                            </div>
                        </a>

                        {{-- 3. Cash In --}}
                        <button type="button" wire:click="openQuickAction('cash_in')" class="flex items-center gap-2.5 p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 transition text-left">
                            <span class="text-xl text-emerald-600">💵</span>
                            <div class="leading-tight">
                                <div class="text-xs font-bold text-emerald-700 dark:text-emerald-300">{{ $lang === 'ur' ? 'رقم آئی' : 'Cash In' }}</div>
                                <div class="text-[9px] text-slate-400">کیش آمد</div>
                            </div>
                        </button>

                        {{-- 4. Cash Out --}}
                        <button type="button" wire:click="openQuickAction('cash_out')" class="flex items-center gap-2.5 p-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 transition text-left">
                            <span class="text-xl text-rose-600">💸</span>
                            <div class="leading-tight">
                                <div class="text-xs font-bold text-rose-700 dark:text-rose-300">{{ $lang === 'ur' ? 'رقم گئی' : 'Cash Out' }}</div>
                                <div class="text-[9px] text-slate-400">کیش روانگی</div>
                            </div>
                        </button>

                        {{-- 5. Customer Payment --}}
                        <button type="button" wire:click="openQuickAction('customer_payment')" class="flex items-center gap-2.5 p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 transition text-left">
                            <span class="text-xl text-indigo-600">👥</span>
                            <div class="leading-tight">
                                <div class="text-xs font-bold text-indigo-700 dark:text-indigo-300">{{ $lang === 'ur' ? 'گاہک ادائیگی' : 'Udhaar Pay' }}</div>
                                <div class="text-[9px] text-slate-400">ادھار وصولی</div>
                            </div>
                        </button>

                        {{-- 6. Expense --}}
                        <button type="button" wire:click="openQuickAction('expense')" class="flex items-center gap-2.5 p-2 rounded-xl bg-fuchsia-50 dark:bg-fuchsia-950/40 hover:bg-fuchsia-100 transition text-left">
                            <span class="text-xl text-fuchsia-600">🧾</span>
                            <div class="leading-tight">
                                <div class="text-xs font-bold text-fuchsia-700 dark:text-fuchsia-300">{{ $lang === 'ur' ? 'اخراجات' : 'Expense' }}</div>
                                <div class="text-[9px] text-slate-400">روزمرہ خرچہ</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ================= 10. Fixed Bottom Nav Bar (5 Buttons with Raised FAB) ================= --}}
        <nav class="absolute bottom-0 inset-x-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-3 py-2 shadow-lg">
            <div class="flex items-center justify-between relative">

                {{-- Button 1: Home --}}
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center text-vital-primary w-14">
                    <span class="text-lg">🏠</span>
                    <span class="text-[10px] font-black mt-0.5">{{ $lang === 'ur' ? 'ہوم' : 'Home' }}</span>
                </a>

                {{-- Button 2: Sale / POS --}}
                <a href="{{ route('pos.index') }}" class="flex flex-col items-center text-slate-500 hover:text-vital-primary w-14">
                    <span class="text-lg">🧾</span>
                    <span class="text-[10px] font-bold mt-0.5">{{ $lang === 'ur' ? 'سیل' : 'Sale' }}</span>
                </a>

                {{-- Center Floating Round Quick Button (+) --}}
                <div class="relative -top-5 flex justify-center">
                    <button type="button"
                            wire:click="toggleActionMenu"
                            class="fab-quick-btn w-14 h-14 rounded-full bg-vital-primary text-white flex items-center justify-center text-2xl font-black transition {{ $showActionMenu ? 'rotate-45' : '' }}"
                            title="Quick Actions">
                        +
                    </button>
                </div>

                {{-- Button 4: Cash --}}
                <a href="{{ route('bank-deposits.index') }}" class="flex flex-col items-center text-slate-500 hover:text-vital-primary w-14">
                    <span class="text-lg">💵</span>
                    <span class="text-[10px] font-bold mt-0.5">{{ $lang === 'ur' ? 'کیش' : 'Cash' }}</span>
                </a>

                {{-- Button 5: More Drawer --}}
                <button type="button"
                        wire:click="toggleMoreDrawer"
                        class="flex flex-col items-center text-slate-500 hover:text-vital-primary w-14">
                    <span class="text-lg">⋯</span>
                    <span class="text-[10px] font-bold mt-0.5">{{ $lang === 'ur' ? 'مزید' : 'More' }}</span>
                </button>

            </div>
        </nav>

        {{-- ================= 11. "More / مزید" Sliding Drawer Modal ================= --}}
        @if ($showMoreDrawer)
            <div class="absolute inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex flex-col justify-end"
                 wire:click.self="toggleMoreDrawer">
                <div class="bg-white dark:bg-slate-900 rounded-t-[32px] p-5 shadow-2xl border-t border-slate-200 dark:border-slate-800 max-h-[85vh] overflow-y-auto space-y-4 animate-in slide-in-from-bottom duration-300">

                    {{-- Handle --}}
                    <div class="w-12 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700 mx-auto mb-2"></div>

                    {{-- Drawer Title & Station Banner --}}
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                {{ $lang === 'ur' ? 'مزید انتظامی اختیارات' : 'Management & Admin Tools' }}
                            </h2>
                            <p class="text-[11px] text-slate-500">{{ $stationName }}</p>
                        </div>
                        <button type="button" wire:click="toggleMoreDrawer" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold flex items-center justify-center">✕</button>
                    </div>

                    {{-- Station Setup & Forecourt Hardware --}}
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                            {{ $lang === 'ur' ? 'ہارڈ ویئر و پیمائش' : 'Forecourt Hardware' }}
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('dispensers.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>⛽</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'ڈسپنسر کنفگ' : 'Dispensers' }}</span>
                            </a>
                            <a href="{{ route('nozzles.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>🔧</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'نوزلز ماسٹر' : 'Nozzles' }}</span>
                            </a>
                            <a href="{{ route('fuel-prices.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>💰</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'تیل نرخ تبدیلی' : 'Fuel Prices' }}</span>
                            </a>
                            <a href="{{ route('tank-readings.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>📐</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'ڈپ ریڈنگز' : 'Dip Readings' }}</span>
                            </a>
                        </div>
                    </div>

                    {{-- Admin & Security --}}
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                            {{ $lang === 'ur' ? 'سسٹم ایڈمنسٹریشن' : 'System Administration' }}
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('users.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>👥</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'صارفین اور عملہ' : 'Users & Staff' }}</span>
                            </a>
                            <a href="{{ route('roles.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>🔑</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'اختیارات و رولز' : 'Roles & Access' }}</span>
                            </a>
                            <a href="{{ route('branches.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>🏢</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'برانچ مہر اسٹیشن' : 'Branch Details' }}</span>
                            </a>
                            <a href="{{ route('banks.index') }}" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 hover:bg-slate-100">
                                <span>🏦</span>
                                <span class="text-xs font-bold">{{ $lang === 'ur' ? 'بینک اکاؤنٹس' : 'Bank Accounts' }}</span>
                            </a>
                        </div>
                    </div>

                    {{-- User Profile & Sign Out --}}
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <img src="{{ Auth::user()->avatar_url }}" alt="avatar" class="w-9 h-9 rounded-full object-cover border">
                            <div class="text-left leading-tight">
                                <div class="text-xs font-bold">{{ Auth::user()->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ Auth::user()->roleLabel() }}</div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="bg-rose-50 text-vital-primary font-bold text-xs px-3 py-1.5 rounded-xl border border-red-200 hover:bg-vital-primary hover:text-white transition">
                                {{ $lang === 'ur' ? 'لاگ آؤٹ' : 'Sign Out' }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        @endif

        {{-- ================= 12. Quick Action Modal (Cash In, Cash Out, Expense, Udhaar Pay) ================= --}}
        @if ($quickModalType)
            <div class="absolute inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-[360px] space-y-3 animate-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">
                                @if ($quickModalType === 'cash_in') 💵 @elseif ($quickModalType === 'cash_out') 💸 @elseif ($quickModalType === 'expense') 🧾 @else 👥 @endif
                            </span>
                            <h3 class="text-xs font-black uppercase text-slate-800 dark:text-slate-100">
                                @if ($quickModalType === 'cash_in') {{ $lang === 'ur' ? 'رقم موصولی (Cash In)' : 'Cash In' }}
                                @elseif ($quickModalType === 'cash_out') {{ $lang === 'ur' ? 'رقم ادائیگی / ڈراپ (Cash Out)' : 'Cash Out' }}
                                @elseif ($quickModalType === 'expense') {{ $lang === 'ur' ? 'روزمرہ خرچہ (Expense)' : 'Expense' }}
                                @else {{ $lang === 'ur' ? 'گاہک ادھار ادائیگی' : 'Customer Payment' }}
                                @endif
                            </h3>
                        </div>
                        <button type="button" wire:click="closeQuickAction" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                    </div>

                    @if ($quickSuccess)
                        <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
                            {{ $quickSuccess }}
                        </div>
                    @endif

                    @if ($quickError)
                        <div class="p-2.5 rounded-xl bg-rose-50 text-rose-800 text-xs font-bold border border-rose-200">
                            {{ $quickError }}
                        </div>
                    @endif

                    {{-- Customer Selector if paying udhaar --}}
                    @if ($quickModalType === 'customer_payment')
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">گاہک منتخب کریں</label>
                            <select wire:model="quickCustomerId" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 py-2">
                                <option value="">-- گاہک منتخب کریں --</option>
                                @foreach ($customersList as $cust)
                                    <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->phone }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Amount --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">رقم (روپے) / Amount (PKR)</label>
                        <input type="number" step="0.01" wire:model="quickAmount" placeholder="0.00"
                               class="w-full text-lg font-black text-center rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 py-2 tabular">
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">تفصیل / Note</label>
                        <input type="text" wire:model="quickNotes" placeholder="تفصیل درج کریں..."
                               class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 py-2">
                    </div>

                    <div class="pt-2 flex gap-2">
                        <button type="button" wire:click="closeQuickAction" class="w-1/2 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-600">
                            منسوخ
                        </button>
                        <button type="button" wire:click="submitQuickAction" class="w-1/2 py-2 rounded-xl bg-vital-primary hover:bg-vital-darkred text-xs font-bold text-white shadow">
                            محفوظ کریں
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ================= 13. Emergency Help Modal ================= --}}
        @if ($showHelpModal)
            <div class="absolute inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-[360px] space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xl text-vital-primary font-black">❓</span>
                            <h3 class="text-xs font-black uppercase text-slate-900 dark:text-white">
                                {{ $lang === 'ur' ? 'اسٹیشن ہنگامی مدد و رابطہ' : 'Station Help & Hotline' }}
                            </h3>
                        </div>
                        <button type="button" wire:click="closeHelpModal" class="text-slate-400 font-bold">✕</button>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="p-2.5 rounded-xl bg-red-50 text-vital-primary border border-red-200 dark:bg-red-950/40 font-bold">
                            🚨 ایمرجنسی شٹ آف: ڈسپنسر ماسٹر سوئچ کیبنٹ A میں موجود ہے۔
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 leading-relaxed">
                            <div class="font-bold text-slate-800 dark:text-slate-200">مالک / مینیجر رابطہ:</div>
                            <div class="text-slate-500">محمد رضوان اسلم: 0300-4342343</div>
                            <div class="text-slate-500">مہر فلنگ اسٹیشن، شیخوپورہ روڈ</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 leading-relaxed">
                            <div class="font-bold text-slate-800 dark:text-slate-200">وائٹل پیٹرولیم سپلائی ہیلپ لائن:</div>
                            <div class="text-slate-500">0800-VITAL (84825)</div>
                        </div>
                    </div>

                    <button type="button" wire:click="closeHelpModal" class="w-full py-2 rounded-xl bg-vital-primary text-white text-xs font-bold">
                        بند کریں / Close
                    </button>
                </div>
            </div>
        @endif

        {{-- ================= 14. Auto-Lock Overlay ================= --}}
        @if ($isLocked)
            <div class="absolute inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex flex-col justify-between p-6 text-white text-center animate-in fade-in duration-200">
                <div class="pt-4">
                    <div class="w-14 h-14 rounded-full bg-vital-primary mx-auto flex items-center justify-center text-white text-2xl font-black shadow-lg">
                        🔒
                    </div>
                    <h2 class="text-base font-black mt-3">{{ $lang === 'ur' ? 'اسکرین لاک ہے' : 'Forecourt Screen Locked' }}</h2>
                    <p class="text-xs text-slate-400">{{ Auth::user()->name }} — پن درج کریں</p>
                </div>

                {{-- Masked PIN Dots --}}
                <div class="my-4">
                    <div class="flex justify-center items-center gap-3">
                        @for ($i = 0; $i < 4; $i++)
                            <div @class([
                                'w-4 h-4 rounded-full border-2 transition-all',
                                'bg-vital-primary border-vital-primary scale-110 shadow-lg' => strlen($lockPin) > $i,
                                'border-slate-700 bg-slate-900' => strlen($lockPin) <= $i,
                            ])></div>
                        @endfor
                    </div>

                    @if ($lockError)
                        <div class="text-xs font-bold text-rose-400 mt-2 animate-bounce">
                            {{ $lockError }}
                        </div>
                    @endif
                </div>

                {{-- Keypad --}}
                <div class="grid grid-cols-3 gap-3 max-w-[260px] mx-auto w-full">
                    @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $n)
                        <button type="button" wire:click="enterLockDigit('{{ $n }}')"
                                class="h-12 rounded-2xl bg-slate-800 hover:bg-slate-700 text-xl font-bold text-white border border-slate-700">
                            {{ $n }}
                        </button>
                    @endforeach
                    <button type="button" wire:click="clearLockPin" class="h-12 rounded-2xl bg-slate-900 text-sm font-bold text-amber-400 border border-slate-800">
                        C
                    </button>
                    <button type="button" wire:click="enterLockDigit('0')" class="h-12 rounded-2xl bg-slate-800 hover:bg-slate-700 text-xl font-bold text-white border border-slate-700">
                        0
                    </button>
                    <button type="button" wire:click="backspaceLock" class="h-12 rounded-2xl bg-slate-800 text-lg font-bold text-slate-300 border border-slate-700">
                        ⌫
                    </button>
                </div>

                <div class="pb-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-slate-300 underline">
                            دوسرے اکاؤنٹ سے لاگ اِن کریں (Logout)
                        </button>
                    </form>
                </div>
            </div>
        @endif

    </div>
</div>
