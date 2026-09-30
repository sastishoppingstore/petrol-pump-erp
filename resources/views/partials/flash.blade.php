{{-- Flash messages. Tailwind alerts, auto-dismissed by app.js. --}}
@foreach (['success' => 'Success', 'error' => 'Error', 'warning' => 'Warning', 'info' => 'Note', 'status' => 'Note'] as $key => $label)
    @if (session()->has($key))
        @php $style = [
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
            'error'   => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',
            'info'    => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200',
            'status'  => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200',
        ][$key]; @endphp
        <div class="no-print mb-4 rounded-md border px-4 py-3 text-sm {{ $style }}"
             data-auto-dismiss="6000" role="alert">
            <strong>{{ $label }}.</strong> {{ session($key) }}
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="no-print mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
         role="alert">
        <strong>Please fix the following:</strong>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
