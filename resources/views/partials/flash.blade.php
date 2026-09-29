{{-- Flash messages + validation errors. Bootstrap alerts, auto-dismissed by app.js. --}}
@foreach (['success', 'error', 'warning', 'info', 'status'] as $key)
    @if (session()->has($key))
        <div class="alert alert-{{ $key === 'status' ? 'info' : $key }} alert-dismissible fade show erp-no-print"
             role="alert" data-bs-dismiss="alert">
            @if ($key === 'success')<strong>Success.</strong>@endif
            @if ($key === 'error')<strong>Error.</strong>@endif
            @if ($key === 'warning')<strong>Warning.</strong>@endif
            @if ($key === 'info')<strong>Note.</strong>@endif
            {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show erp-no-print" role="alert" data-bs-dismiss="alert">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
