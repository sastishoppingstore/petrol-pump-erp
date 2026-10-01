<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\StationDocument;
use App\Services\Audit\AuditLogService;
use App\Services\Security\BranchScopeService;
use App\Services\System\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Document Vault — station compliance documents (OGRA licence,
 * dealership agreement, NOCs, calibration certificates) with
 * expiry tracking. Uploads follow the project pattern: `public`
 * disk, validated mime + size, linked via asset('storage/...').
 */
class DocumentVaultController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
        private readonly AuditLogService $audit,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Document register with expiry status and filters.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $this->branchScope->apply(
            StationDocument::query()->with(['uploader', 'branch']),
            $user,
        );

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            match ($request->input('status')) {
                'expired' => $query->expired(),
                'expiring' => $query->expiringSoon(),
                'valid' => $query->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>', now()->addDays(StationDocument::EXPIRING_SOON_DAYS)),
                'no-expiry' => $query->whereNull('expiry_date'),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('note', 'like', $search);
            });
        }

        // Soonest expiry first; documents without expiry last.
        $documents = $query
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->paginate(20)
            ->withQueryString();

        $statsQuery = fn () => $this->branchScope->apply(StationDocument::query(), $user);

        return view('documents.index', [
            'documents' => $documents,
            'categories' => StationDocument::categories(),
            'stats' => [
                'total' => $statsQuery()->count(),
                'valid' => $statsQuery()->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>', now()->addDays(StationDocument::EXPIRING_SOON_DAYS))->count(),
                'expiring' => $statsQuery()->expiringSoon()->count(),
                'expired' => $statsQuery()->expired()->count(),
            ],
        ]);
    }

    /**
     * Upload form.
     */
    public function create(): View
    {
        return view('documents.create', [
            'categories' => StationDocument::categories(),
        ]);
    }

    /**
     * Store an uploaded document.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'in:' . implode(',', array_keys(StationDocument::categories()))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $branchId = $this->branchScope->activeBranchId($request)
            ?? $user->branch_id
            ?? $this->branchScope->selectableBranches($user)->first()?->id;

        if ($branchId === null) {
            return back()->with('error', 'No branch is available for this upload. Please select an active branch first.')->withInput();
        }

        $filePath = $request->file('file')->store('documents', 'public');

        $document = StationDocument::create([
            'branch_id' => $branchId,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'file_path' => $filePath,
            'issue_date' => $validated['issue_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'note' => $validated['note'] ?? null,
            'uploaded_by' => $user->id,
        ]);

        $this->audit->log(
            $user,
            'document.uploaded',
            'DocumentVault',
            StationDocument::class,
            $document->id,
            null,
            $document->only(['title', 'category', 'issue_date', 'expiry_date']),
        );

        // Immediate heads-up to the uploader when the document is
        // already expired or about to lapse (dedupe-safe).
        $status = $document->expiryStatus();
        if (in_array($status, ['expired', 'expiring'], true)) {
            $this->notifications->notifyOnce(
                $user->id,
                'DOCUMENT_EXPIRY',
                $status === 'expired' ? 'Document expired' : 'Document expiring soon',
                "Document \"{$document->title}\" " . ($status === 'expired' ? 'has expired on ' : 'expires on ')
                    . $document->expiry_date?->format('d M Y') . '. Please renew it from the Document Vault.',
                'document:' . $document->id . ':expiry:' . $document->expiry_date?->toDateString(),
                $status === 'expired' ? Notification::LEVEL_CRITICAL : Notification::LEVEL_WARNING,
                'DocumentVault',
                StationDocument::class,
                $document->id,
            );
        }

        return redirect()->route('documents.index')
            ->with('success', "Document \"{$document->title}\" uploaded to the vault.");
    }

    /**
     * Delete a document and its stored file.
     */
    public function destroy(Request $request, StationDocument $document): RedirectResponse
    {
        $user = $request->user();

        // Branch isolation: the bound document must be in scope.
        $this->branchScope->apply(StationDocument::query(), $user)->findOrFail($document->id);

        $title = $document->title;
        $filePath = $document->file_path;

        $document->delete();

        if ($filePath) {
            Storage::disk('public')->delete($filePath);
        }

        $this->audit->log(
            $user,
            'document.deleted',
            'DocumentVault',
            StationDocument::class,
            $document->id,
            ['title' => $title, 'file_path' => $filePath],
            null,
        );

        return back()->with('success', "Document \"{$title}\" deleted from the vault.");
    }
}
