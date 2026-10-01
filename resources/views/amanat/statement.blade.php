@extends('layouts.app')

@section('title', __('admin.amanat.statement_title'))
@section('breadcrumb')
    <li><a href="{{ route('amanat.index') }}" class="hover:text-navy-700 dark:hover:text-slate-300">{{ __('admin.amanat.title') }}</a></li>
    <li class="text-slate-500">{{ $customer->name }}</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>{{ $customer->name }} — {{ __('admin.amanat.statement_title') }}</h1>
        <p>{{ $customer->code }} @if ($customer->phone) &bull; {{ $customer->phone }} @endif &bull; {{ $customer->branch?->name }}</p>
        <div class="page-actions">
            <a href="{{ route('amanat.index') }}" class="btn-3d btn-3d-ghost">&larr; {{ __('admin.amanat.all_customers') }}</a>
            <a href="{{ route('amanat.create', ['customer_id' => $customer->id]) }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.amanat.new_entry') }}
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">{{ __('admin.amanat.current_balance') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) $balance, 2) }}</div>
            <div class="stat-sub">{{ __('admin.amanat.balance_sub') }}</div>
        </div>
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">{{ __('admin.amanat.total_deposited') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($totals['deposits'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ __('admin.amanat.all_time') }}</div>
        </div>
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">{{ __('admin.amanat.total_deducted') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format((float) ($totals['deductions'] ?? 0), 2) }}</div>
            <div class="stat-sub">{{ __('admin.amanat.all_time') }}</div>
        </div>
    </div>

    {{-- Ledger entries --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.common.date') }}</th>
                        <th>{{ __('admin.amanat.type') }}</th>
                        <th>{{ __('admin.common.amount') }}</th>
                        <th>{{ __('admin.amanat.balance_after') }}</th>
                        <th>{{ __('admin.amanat.reference_label') }}</th>
                        <th>{{ __('admin.amanat.note_label') }}</th>
                        <th>{{ __('admin.amanat.entered_by') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="text-xs text-slate-500">{{ $entry->created_at?->format('d M Y, h:i A') }}</td>
                            <td>
                                <span @class([
                                    'rounded px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $entry->type === 'deposit',
                                    'bg-red-100 text-red-800' => $entry->type === 'deduction',
                                    'bg-amber-100 text-amber-800' => $entry->type === 'adjustment',
                                ])>{{ $entry->typeLabel() }}</span>
                            </td>
                            <td class="tabular font-mono font-bold {{ $entry->type === 'deduction' || (float) $entry->amount < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                @php $sign = ($entry->type === 'deduction' || (float) $entry->amount < 0) ? '−' : '+'; @endphp
                                {{ $sign }} Rs. {{ number_format(abs((float) $entry->amount), 2) }}
                            </td>
                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $entry->balance_after, 2) }}
                            </td>
                            <td class="text-xs text-slate-600 dark:text-slate-300">{{ $entry->reference ?? '—' }}</td>
                            <td class="text-xs text-slate-600 dark:text-slate-300">{{ $entry->note ?? '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $entry->creator?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                {{ __('admin.amanat.stmt_none') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $entries->links() }}</div>
    </div>

    <a href="{{ route('amanat.create', ['customer_id' => $customer->id]) }}" class="fab-3d" title="{{ __('admin.amanat.fab_title_customer', ['name' => $customer->name]) }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>
</div>
@endsection
