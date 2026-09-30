<div class="flex min-h-screen items-center justify-center p-2 sm:p-4 bg-slate-900"
     x-data="{
        shake: false,
        init() {
            window.addEventListener('keydown', (e) => {
                if (e.key >= '0' && e.key <= '9') {
                    $wire.enterDigit(e.key);
                } else if (e.key === 'Backspace') {
                    $wire.backspace();
                } else if (e.key === 'Escape' || e.key === 'c' || e.key === 'C') {
                    $wire.clearPin();
                }
            });
            $wire.on('pin-error', () => {
                this.shake = true;
                setTimeout(() => this.shake = false, 600);
            });
        }
     }">

    {{-- Mobile Container (360-430px centered frame) --}}
    <div class="w-full max-w-[420px] rounded-3xl bg-white shadow-2xl overflow-hidden border border-slate-700/30 flex flex-col min-h-[640px] dark:bg-slate-900">

        {{-- Top Brand Banner (Vital Petroleum) --}}
        <div class="vital-hero-card p-5 text-white text-center relative">
            <div class="flex items-center justify-between mb-2">
                {{-- Vital Red Emblem --}}
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-md">
                        <span class="text-vital-primary font-black text-lg">V</span>
                    </div>
                    <div class="text-left leading-tight">
                        <div class="text-[11px] font-black tracking-wider uppercase opacity-90">VITAL PETROLEUM</div>
                        <div class="text-xs font-bold text-amber-200">مہر فلنگ اسٹیشن</div>
                    </div>
                </div>

                {{-- Language / Normal Login Link --}}
                <a href="{{ route('login') }}" class="text-[11px] bg-white/20 hover:bg-white/30 px-2.5 py-1 rounded-full text-white font-medium transition flex items-center gap-1">
                    <span>پاس ورڈ</span>
                    <span>🔑</span>
                </a>
            </div>

            <h1 class="text-xl font-black mt-2 tracking-tight">کیشئر لاگ اِن (PIN)</h1>
            <p class="text-xs text-white/80 font-medium">4 سے 6 ہندسوں کا سیکیورٹی پن درج کریں</p>
        </div>

        {{-- Main Body --}}
        <div class="p-5 flex-1 flex flex-col justify-between">

            {{-- 1. Cashier Selector / Avatar Grid --}}
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 text-center">
                    کیشئر منتخب کریں / Select Cashier
                </label>

                <div class="flex gap-2.5 overflow-x-auto pb-2 px-1 justify-center no-scrollbar">
                    @forelse ($users as $user)
                        <button type="button"
                                wire:click="selectUser({{ $user->id }})"
                                @class([
                                    'flex flex-col items-center p-2 rounded-2xl transition-all border-2 shrink-0 w-20',
                                    'border-vital-primary bg-red-50 text-vital-primary shadow-md scale-105' => $selectedUserId === $user->id,
                                    'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300' => $selectedUserId !== $user->id,
                                ])>
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                                 class="w-11 h-11 rounded-full object-cover shadow border border-white">
                            <span class="text-[11px] font-bold mt-1.5 truncate max-w-full text-center">{{ Str::limit($user->name, 9) }}</span>
                            <span class="text-[9px] text-slate-400 font-mono">{{ $user->employee_code ?: 'EMP-'.$user->id }}</span>
                        </button>
                    @empty
                        <div class="text-xs text-slate-400 py-2">کوئی کیشئر رجسٹرڈ نہیں ہے</div>
                    @endforelse
                </div>
            </div>

            {{-- 2. Selected User Banner & PIN Masked Display --}}
            <div class="text-center my-2">
                @if ($selectedUser)
                    <div class="inline-flex items-center gap-2 bg-slate-100 dark:bg-slate-800 px-3.5 py-1.5 rounded-full mb-3 shadow-inner">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $selectedUser->name }}</span>
                        <span class="text-[10px] bg-vital-primary text-white font-bold px-1.5 py-0.5 rounded">{{ $selectedUser->roleLabel() }}</span>
                    </div>
                @endif

                {{-- Masked PIN Dots (4 to 6 dots) --}}
                <div class="flex justify-center items-center gap-3 my-2" :class="{ 'animate-bounce': shake }">
                    @for ($i = 0; $i < 4; $i++)
                        <div @class([
                            'w-5 h-5 rounded-full border-2 transition-all duration-150',
                            'bg-vital-primary border-vital-primary scale-110 shadow-lg' => strlen($pin) > $i,
                            'border-slate-300 bg-slate-100 dark:border-slate-700 dark:bg-slate-800' => strlen($pin) <= $i,
                        ])></div>
                    @endfor
                </div>

                {{-- Error Message Display --}}
                @if ($errorMessage)
                    <div class="text-xs font-bold text-vital-primary bg-red-50 dark:bg-red-950/40 py-1 px-3 rounded-lg inline-block mt-1 animate-pulse">
                        {{ $errorMessage }}
                    </div>
                @endif
            </div>

            {{-- 3. 3D Tactile Numeric Keypad (1 to 9, C, 0, ⌫) --}}
            <div class="grid grid-cols-3 gap-3 max-w-[280px] mx-auto my-auto w-full">
                @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $num)
                    <button type="button"
                            wire:click="enterDigit('{{ $num }}')"
                            class="touch-tile-3d h-14 rounded-2xl bg-white border border-slate-200 font-bold text-2xl text-slate-800 hover:bg-slate-50 flex items-center justify-center dark:bg-slate-800 dark:text-white dark:border-slate-700">
                        {{ $num }}
                    </button>
                @endforeach

                {{-- Clear 'C' --}}
                <button type="button"
                        wire:click="clearPin"
                        class="touch-tile-3d h-14 rounded-2xl bg-amber-50 border border-amber-200 font-bold text-lg text-amber-700 hover:bg-amber-100 flex items-center justify-center dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800">
                    C
                </button>

                {{-- Zero --}}
                <button type="button"
                        wire:click="enterDigit('0')"
                        class="touch-tile-3d h-14 rounded-2xl bg-white border border-slate-200 font-bold text-2xl text-slate-800 hover:bg-slate-50 flex items-center justify-center dark:bg-slate-800 dark:text-white dark:border-slate-700">
                    0
                </button>

                {{-- Backspace --}}
                <button type="button"
                        wire:click="backspace"
                        class="touch-tile-3d h-14 rounded-2xl bg-slate-100 border border-slate-300 font-bold text-xl text-slate-700 hover:bg-slate-200 flex items-center justify-center dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700"
                        title="حذف کریں / Backspace">
                    ⌫
                </button>
            </div>

            {{-- 4. Footer info & Standard Password Switch --}}
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-center">
                <a href="{{ route('login') }}" class="text-xs text-vital-primary font-bold hover:underline flex items-center justify-center gap-1">
                    <span>ای میل اور روایتی پاس ورڈ کے ساتھ لاگ اِن کریں</span>
                    <span>←</span>
                </a>
                <div class="text-[10px] text-slate-400 mt-1">
                    مہر فلنگ اسٹیشن (شیخوپورہ) — Vital ERP 2026
                </div>
            </div>

        </div>

    </div>

</div>
