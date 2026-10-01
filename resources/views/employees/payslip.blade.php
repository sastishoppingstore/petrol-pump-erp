<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip — {{ $employee->name }} ({{ $salary->month }})</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .payslip-card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
        @import url('https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap');
        .urdu-text {
            font-family: 'Noto Nastaliq Urdu', serif;
            direction: rtl;
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 font-sans">
    <div class="no-print mx-auto mb-4 max-w-2xl flex justify-between items-center">
        <a href="{{ route('employees.payroll', ['month' => $salary->month]) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">&larr; {{ __('admin.payslip.back') }}</a>
        <button onclick="window.print()" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">🖨️ {{ __('admin.payslip.print') }}
        </button>
    </div>

    <div class="payslip-card mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        {{-- Station Branding Header --}}
        <div class="border-b-2 border-red-600 pb-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-red-600 px-2 py-0.5 text-xs font-black tracking-widest text-white">VITAL</span>
                        <h1 class="text-xl font-black tracking-tight text-slate-900">MEHAR FILLING STATION</h1>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Vital Petroleum Dealer &bull; GT Road, Sheikhupura, Punjab</p>
                </div>
                <div class="text-right">
                    <span class="inline-block rounded-md bg-slate-100 px-3 py-1 font-mono text-xs font-bold text-slate-700">
                        PAYSLIP / تنخواہ سلپ
                    </span>
                    <div class="mt-1 font-mono text-sm font-bold text-slate-900">
                        {{ \Carbon\Carbon::parse($salary->month . '-01')->format('F Y') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Employee Info --}}
        <div class="mt-6 grid grid-cols-2 gap-4 rounded-xl bg-slate-50 p-4 text-xs">
            <div>
                <span class="text-slate-400 uppercase font-semibold">Employee Name:</span>
                <div class="text-sm font-bold text-slate-900">{{ $employee->name }}</div>
                <div class="text-slate-500 font-mono">Code: {{ $employee->code }}</div>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold">Designation:</span>
                <div class="text-sm font-bold text-slate-900">{{ $employee->designation }}</div>
                <div class="text-slate-500 font-mono">CNIC: {{ $employee->cnic ?: '—' }}</div>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold">Attendance Record:</span>
                <div class="font-mono font-semibold text-slate-800">
                    Present: {{ $salary->present_days }} | Absent: {{ $salary->absent_days }} | Leave: {{ $salary->leave_days }}
                </div>
            </div>
            <div>
                <span class="text-slate-400 uppercase font-semibold">Payment Status:</span>
                <div class="font-bold {{ $salary->isPaid() ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $salary->status }} {{ $salary->payment_date ? '(' . $salary->payment_date->format('d M Y') . ')' : '' }}
                </div>
            </div>
        </div>

        {{-- Earnings & Deductions Breakdown --}}
        <div class="mt-6 grid grid-cols-2 gap-6 text-sm">
            {{-- Earnings --}}
            <div>
                <h3 class="border-b border-slate-200 pb-1.5 font-bold text-emerald-800 uppercase text-xs">Earnings (آمدنی)</h3>
                <div class="mt-2 space-y-1.5 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Basic Salary:</span>
                        <span class="font-mono font-semibold">Rs. {{ number_format((float) $salary->basic_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Overtime:</span>
                        <span class="font-mono font-semibold text-emerald-600">+Rs. {{ number_format((float) $salary->overtime_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Bonus / Performance:</span>
                        <span class="font-mono font-semibold text-emerald-600">+Rs. {{ number_format((float) $salary->bonus_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 font-bold text-emerald-800 border-t border-slate-200 pt-1.5">
                        <span>Total Earnings:</span>
                        <span class="font-mono">Rs. {{ number_format((float) ($salary->basic_salary + $salary->allowances), 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Deductions --}}
            <div>
                <h3 class="border-b border-slate-200 pb-1.5 font-bold text-red-800 uppercase text-xs">Deductions (کٹوتیاں)</h3>
                <div class="mt-2 space-y-1.5 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Advance Loan Recovery:</span>
                        <span class="font-mono font-semibold text-red-600">-Rs. {{ number_format((float) $salary->advance_deduction, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600">Fines / Shortage:</span>
                        <span class="font-mono font-semibold text-red-600">-Rs. {{ number_format((float) $salary->fine_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 font-bold text-red-800 border-t border-slate-200 pt-1.5">
                        <span>Total Deductions:</span>
                        <span class="font-mono">Rs. {{ number_format((float) $salary->deductions, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Net Payable Highlight --}}
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50/60 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase text-red-900 tracking-wider">Net Salary Payable (خالص قابلِ ادا رقم)</span>
                    <div class="mt-1 font-mono text-2xl font-black text-red-700">
                        Rs. {{ number_format((float) $salary->net_salary, 2) }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="urdu-text text-sm font-bold text-red-900">
                        {{ $net_salary_in_words }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Signatures --}}
        <div class="mt-12 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="border-t border-slate-300 pt-2 font-medium text-slate-600">
                    Employee Signature / دستخط ملازم
                </div>
            </div>
            <div>
                <div class="border-t border-slate-300 pt-2 font-medium text-slate-600">
                    Manager / Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</body>
</html>
