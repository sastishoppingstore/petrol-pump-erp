@extends('layouts.app')

@section('title', __('admin.documents.upload_btn'))
@section('breadcrumb')
    <li><a href="{{ route('documents.index') }}" class="hover:text-navy-700 dark:hover:text-slate-300">{{ __('admin.documents.title') }}</a></li>
    <li class="text-slate-500">{{ __('admin.documents.upload_crumb') }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="page-head">
        <h1>{{ __('admin.documents.upload_btn') }}</h1>
        <p>{{ __('admin.documents.create_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="glass-card space-y-5 p-6">
        @csrf

        <div class="field-3d">
            <label for="title" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.doc_title') }} *</label>
            <input id="title" type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                   placeholder="{{ __('admin.documents.title_placeholder') }}" class="input-3d w-full text-center text-sm">
            @error('title') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d">
                <label for="category" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.category') }} *</label>
                <select id="category" name="category" required class="input-3d w-full text-center text-sm">
                    <option value="">{{ __('admin.documents.select_category') }}</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="field-3d">
                <label for="file" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.file_label') }} *</label>
                <input id="file" type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png" class="input-3d w-full text-center text-sm">
                @error('file') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d">
                <label for="issue_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.issue_date') }}</label>
                <input id="issue_date" type="date" name="issue_date" value="{{ old('issue_date') }}" class="input-3d w-full text-center text-sm">
                @error('issue_date') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="field-3d">
                <label for="expiry_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.expiry_date') }}</label>
                <input id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="input-3d w-full text-center text-sm">
                <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('admin.documents.expiry_hint') }}</span>
                @error('expiry_date') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="field-3d">
            <label for="note" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.amanat.note_label') }}</label>
            <textarea id="note" name="note" rows="3" maxlength="500" class="input-3d w-full text-sm"
                      placeholder="{{ __('admin.documents.note_placeholder') }}">{{ old('note') }}</textarea>
            @error('note') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-center gap-2 pt-2">
            <a href="{{ route('documents.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
            <button type="submit" class="btn-3d btn-3d-success">{{ __('admin.documents.upload_submit') }}</button>
        </div>
    </form>
</div>
@endsection
