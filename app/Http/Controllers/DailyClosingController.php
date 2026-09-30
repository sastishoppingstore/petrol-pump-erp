<?php

namespace App\Http\Controllers;

use App\Models\DailyClosing;
use App\Services\Security\BranchScopeService;
use App\Services\Shift\DailyClosingService;
use App\Support\PermissionList;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DailyClosingController extends Controller
{
    public function __construct(
        private readonly DailyClosingService $closingService,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function index(Request $request): View
    {
        $branchId = $this->branchScope->activeBranchId($request) ?? 1;
        $date = $request->get('date', now()->format('Y-m-d'));

        $checklist = $this->closingService->getChecklist($branchId, $date);

        return view('closing.index', [
            'date' => $date,
            'checklist' => $checklist,
            'existingClosing' => $checklist['existing_closing'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branchId = $this->branchScope->activeBranchId($request) ?? 1;
        $date = $request->input('closing_date', now()->format('Y-m-d'));

        try {
            $closing = $this->closingService->closeDay(
                branchId: $branchId,
                date: $date,
                actor: $request->user(),
                input: $request->all(),
                forceOverride: $request->boolean('force_override')
            );

            return redirect()->route('closing.index', ['date' => $date])
                ->with('success', "Daily Closing #{$closing->closing_number} completed. Date {$date} locked and summary email sent to station owner.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Daily closing failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Unable to complete daily closing: ' . $e->getMessage());
        }
    }

    public function unlock(Request $request, DailyClosing $closing): RedirectResponse
    {
        try {
            $this->closingService->unlockDay(
                closing: $closing,
                actor: $request->user(),
                reason: $request->input('reason', 'Manager correction unlock')
            );

            return redirect()->route('closing.index', ['date' => $closing->closing_date->format('Y-m-d')])
                ->with('success', "Day {$closing->closing_date->format('Y-m-d')} unlocked successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
