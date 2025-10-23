<?php

namespace App\Http\Controllers\Patient;

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
        $this->middleware(['auth', 'verified']);
        $this->resultService = $resultService;
    }

    /**
     * Display a listing of patient's results
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Result::forPatient($user->id)
            ->with('order.items')
            ->latest('result_date');

        // Filter
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'new':
                    $query->whereNotNull('patient_notified_at')
                          ->whereNull('patient_viewed_at');
                    break;
                case 'viewed':
                    $query->whereNotNull('patient_viewed_at');
                    break;
                case 'critical':
                    $query->critical();
                    break;
            }
        }

        // Date range filter
        if ($request->has('date_from')) {
            try {
                $query->where('result_date', '>=', \Carbon\Carbon::parse($request->date_from));
            } catch (\Exception $e) {
                // Skip invalid date
            }
        }

        if ($request->has('date_to')) {
            try {
                $query->where('result_date', '<=', \Carbon\Carbon::parse($request->date_to));
            } catch (\Exception $e) {
                // Skip invalid date
            }
        }

        // Pagination with limit
        $perPage = min($request->input('per_page', 10), 50);
        $results = $query->paginate($perPage);

        // Count unviewed results
        $unviewedCount = Result::forPatient($user->id)
            ->whereNotNull('patient_notified_at')
            ->whereNull('patient_viewed_at')
            ->count();

        // Statistics
        $stats = [
            'total_results' => Result::forPatient($user->id)->count(),
            'unviewed' => $unviewedCount,
            'critical' => Result::forPatient($user->id)->critical()->count(),
            'this_year' => Result::forPatient($user->id)
                ->whereYear('result_date', now()->year)
                ->count(),
        ];

        return view('patient.results.index', compact('results', 'unviewedCount', 'stats'));
    }

    /**
     * Display the specified result
     */
    public function show(Result $result)
    {
        $this->authorize('view', $result);

        // Mark as viewed if not already
        if (!$result->is_viewed) {
            try {
                $this->resultService->markAsViewed($result);
            } catch (\Exception $e) {
                Log::warning('Failed to mark result as viewed', [
                    'result_id' => $result->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $result->load([
            'order.user',
            'order.items.test',
            'labSubmission.labPartner',
        ]);

        $summary = $result->getSummary();
        $abnormalTests = $result->getAbnormalTests();
        $criticalTests = $result->getCriticalTests();

        return view('patient.results.show', compact(
            'result',
            'summary',
            'abnormalTests',
            'criticalTests'
        ));
    }

    /**
     * Download result PDF
     */
    public function download(Result $result)
    {
        $this->authorize('download', $result);

        try {
            // Mark as viewed if not already
            if (!$result->is_viewed) {
                $this->resultService->markAsViewed($result);
            }

            return $result->downloadPdf();

        } catch (\Exception $e) {
            Log::error('Patient failed to download result', [
                'result_id' => $result->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            $errorMessage = config('app.debug')
                ? 'Failed to download PDF: ' . $e->getMessage()
                : 'Failed to download PDF. Please try again or contact support.';

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Compare multiple results (trend analysis)
     */
    public function compare(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'result_ids' => 'required|array|min:2|max:5',
            'result_ids.*' => 'exists:results,id',
        ]);

        // Verify all results belong to user
        $results = Result::whereIn('id', $request->result_ids)
            ->forPatient($user->id)
            ->with('order.items.test')
            ->orderBy('result_date')
            ->get();

        if ($results->count() !== count($request->result_ids)) {
            return back()->with('error', 'Some results were not found or do not belong to you');
        }

        // Build comparison data
        $comparisonData = $this->buildComparisonData($results);

        return view('patient.results.compare', compact('results', 'comparisonData'));
    }

    /**
     * Build comparison data structure
     */
    protected function buildComparisonData($results)
    {
        $testCodes = [];
        $comparisonData = [];

        // Collect all test codes
        foreach ($results as $result) {
            foreach ($result->getTests() as $test) {
                $testCodes[$test['test_code']] = $test['test_name'];
            }
        }

        // Build comparison matrix
        foreach ($testCodes as $code => $name) {
            $values = [];
            foreach ($results as $result) {
                $test = $result->getTestByCode($code);
                $values[] = [
                    'result_date' => $result->result_date->format('Y-m-d'),
                    'value' => $test['value'] ?? 'N/A',
                    'unit' => $test['unit'] ?? '',
                    'flag' => $test['flag'] ?? null,
                ];
            }

            $comparisonData[$code] = [
                'name' => $name,
                'values' => $values,
            ];
        }

        return $comparisonData;
    }

    /**
     * Share result with healthcare provider
     */
    public function shareForm(Result $result)
    {
        $this->authorize('view', $result);

        return view('patient.results.share', compact('result'));
    }

    /**
     * Send result to healthcare provider
     */
    public function share(Request $request, Result $result)
    {
        $this->authorize('view', $result);

        $request->validate([
            'recipient_email' => 'required|email',
            'recipient_name' => 'required|string|max:255',
            'message' => 'nullable|string|max:500',
        ]);

        try {
            // Send email with result PDF
            \Mail::to($request->recipient_email)
                ->send(new \App\Mail\SharedResult(
                    $result,
                    $request->recipient_name,
                    strip_tags($request->message)
                ));

            activity()
                ->performedOn($result)
                ->withProperties([
                    'recipient_email' => $request->recipient_email,
                    'recipient_name' => $request->recipient_name,
                ])
                ->log('Result shared with healthcare provider');

            Log::info('Result shared by patient', [
                'result_id' => $result->id,
                'user_id' => auth()->id(),
                'recipient' => $request->recipient_email,
            ]);

            return redirect()
                ->route('patient.results.show', $result)
                ->with('success', 'Result shared successfully');

        } catch (\Exception $e) {
            Log::error('Failed to share result', [
                'result_id' => $result->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to share result. Please try again.');
        }
    }
}