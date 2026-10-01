@extends('layouts.app')

@section('title', 'Upload Document / دستاویز اپ لوڈ')
@section('breadcrumb')
    <li><a href="{{ route('documents.index') }}" class="hover:text-navy-700 dark:hover:text-slate-300">Document Vault</a></li>
    <li class="text-slate-500">Upload</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="page-head">
        <h1>Upload Document / دستاویز اپ لوڈ کریں</h1>
        <p>PDF ya image (JPG/PNG), zyada se zyada 5 MB. Expiry date zaroor likhein taake waqt par alert milay.</p>
    </div>

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="glass-card space-y-5 p-6">
        @csrf

        <div class="field-3d">
            <label for="title" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Document Title / عنوان *</label>
            <input id="title" type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                   placeholder="Masalan: OGRA Licence 2026 — Mehar Filling Station" class="input-3d w-full text-center text-sm">
            @error('title') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d">
                <label for="category" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Category / زمرہ *</label>
                <select id="category" name="category" required class="input-3d w-full text-center text-sm">
                    <option value="">— Select category —</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="field-3d">
                <label for="file" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">File (PDF/JPG/PNG, max 5 MB) *</label>
                <input id="file" type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png" class="input-3d w-full text-center text-sm">
                @error('file') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d">
                <label for="issue_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Issue Date / اجراء کی تاریخ</label>
                <input id="issue_date" type="date" name="issue_date" value="{{ old('issue_date') }}" class="input-3d w-full text-center text-sm">
                @error('issue_date') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="field-3d">
                <label for="expiry_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Expiry Date / ختم ہونے کی تاریخ</label>
                <input id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="input-3d w-full text-center text-sm">
                <span class="mt-1 block text-center text-[11px] text-slate-400">Expiry se 30 din pehle document "Expiring Soon" ho jayega aur alert milega.</span>
                @error('expiry_date') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="field-3d">
            <label for="note" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Note / نوٹ</label>
            <textarea id="note" name="note" rows="3" maxlength="500" class="input-3d w-full text-sm"
                      placeholder="Optional tafseel — licence number, issuing authority waghera...">{{ old('note') }}</textarea>
            @error('note') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-center gap-2 pt-2">
            <a href="{{ route('documents.index') }}" class="btn-3d btn-3d-ghost">Cancel</a>
            <button type="submit" class="btn-3d btn-3d-success">Upload / اپ لوڈ کریں</button>
        </div>
    </form>
</div>
@endsection
