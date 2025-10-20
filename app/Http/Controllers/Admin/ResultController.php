<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResultController extends Controller
{
    protected $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->middleware('auth');
        $this->resultService = $resultService;
    }

    /**
     * Display a listing of results
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Result::class);

        $query = Result::with(['order.user', 'order.items', 'reviewer'])
            ->latest('result_date');

        // Filter by status
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'critical':
                    $query->critical();
                    break;
                case 'unreviewed':
                    $query->unreviewed();
                    break;
                case 'critical_unreviewed':
                    $query->critical()->unreviewed();
                    break;
                case 'ready_to_notify':
                    $query->reviewed()->whereNull('patient_notified_at');
                    break;
                case 'notified':
                    $query->whereNotNull('patient_notified_at');
                    break;
            }
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->where('result_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('result_date', '<=', $request->date_to);
        }

        // Search by order number
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('order', function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $results = $query->paginate(20);

        // Statistics
        $stats = $this->resultService->getStatistics();

        return view('admin.results.index', compact('results', 'stats'));
    }

    /**
     * Display the specified result
     */
    public function show(Result $result)
    {
        $this->authorize('view', $result);

        $result->load([
            'order.user',
            'order.items.test',
            'labSubmission.labPartner',
            'reviewer'
        ]);

        $summary = $result->getSummary();
        $abnormalTests = $result->getAbnormalTests();
        $criticalTests = $result->getCriticalTests();

        return view('admin.results.show', compact(
            'result',
            'summary',
            'abnormalTests',
            'criticalTests'
        ));
    }

    /**
     * Show review form
     */
    public function reviewForm(Result $result)
    {
        $this->authorize('review', $result);

        if ($result->is_reviewed) {
            return redirect()
                ->route('admin.results.show', $result)
                ->with('info', 'This result has already been reviewed');
        }

        $result->load(['order.user', 'order.items']);
        $criticalTests = $result->getCriticalTests();

        return view('admin.results.review', compact('result', 'criticalTests'));
    }

    /**
     * Review a result
     */
    public function review(Request $request, Result $result)
    {
        $this->authorize('review', $result);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->resultService->reviewResult(
                $result,
                auth()->id(),
                $validated['notes'] ?? null
            );

            Log::info('Result reviewed', [
                'result_id' => $result->id,
                'reviewer_id' => auth()->id(),
                'has_critical' => $result->has_critical_values,
            ]);

            return redirect()
                ->route('admin.results.show', $result)
                ->with('success', 'Result reviewed successfully. Patient will be notified.');

        } catch (\Exception $e) {
            Log::error('Failed to review result', [
                'result_id' => $result->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to review result: ' . $e->getMessage());
        }
    }

    /**
     * Download result PDF
     */
    public function download(Result $result)
    {
        $this->authorize('download', $result);

        try {
            return $result->downloadPdf();

        } catch (\Exception $e) {
            Log::error('Failed to download result PDF', [
                'result_id' => $result->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to download PDF: ' . $e->getMessage());
        }
    }

    /**
     * Regenerate result PDF
     */
    public function regeneratePdf(Result $result)
    {
        $this->authorize('regeneratePdf', $result);

        try {
            if ($result->regeneratePdf()) {
                return back()->with('success', 'PDF regenerated successfully');
            } else {
                return back()->with('error', 'Failed to regenerate PDF');
            }

        } catch (\Exception $e) {
            Log::error('Failed to regenerate PDF', [
                'result_id' => $result->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to regenerate PDF: ' . $e->getMessage());
        }
    }

    /**
     * View raw result data (for debugging)
     */
    public function viewRaw(Result $result)
    {
        $this->authorize('view', $result);

        return view('admin.results.raw', compact('result'));
    }

    /**
     * Manually notify patient
     */
    public function notifyPatient(Result $result)
    {
        $this->authorize('view', $result);

        if (!$result->canBeSharedWithPatient()) {
            return back()->with('error', 'Result cannot be shared yet (may need review or PDF generation)');
        }

        if ($result->is_notified) {
            return back()->with('info', 'Patient has already been notified');
        }

        try {
            \App\Jobs\SendResultNotification::dispatch($result);

            return back()->with('success', 'Notification queued to be sent to patient');

        } catch (\Exception $e) {
            Log::error('Failed to queue result notification', [
                'result_id' => $result->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to queue notification: ' . $e->getMessage());
        }
    }
}