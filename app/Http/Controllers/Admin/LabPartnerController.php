<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabPartner;
use App\Services\LabPartner\LabPartnerManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LabPartnerController extends Controller
{
    protected $labManager;

    public function __construct(LabPartnerManager $labManager)
    {
        $this->middleware(['auth', 'can:manage-lab-partners']);
        $this->labManager = $labManager;
    }

    /**
     * Display a listing of lab partners
     */
    public function index()
    {
        $labPartners = LabPartner::withCount(['labSubmissions', 'results'])
            ->byPriority()
            ->get()
            ->map(function($partner) {
                return [
                    'partner' => $partner,
                    'success_rate' => $partner->getSuccessRate(),
                    'avg_turnaround' => $partner->getAverageTurnaroundTime(),
                    'pending' => $partner->getPendingSubmissions(),
                    'failed' => $partner->getFailedSubmissions(),
                ];
            });

        return view('admin.lab-partners.index', compact('labPartners'));
    }

    /**
     * Show the form for creating a new lab partner
     */
    public function create()
    {
        $connectionTypes = ['api', 'hl7', 'manual'];
        $authTypes = ['api_key', 'oauth', 'basic', 'none'];
        $registeredAdapters = $this->labManager->getRegisteredAdapters();

        return view('admin.lab-partners.create', compact(
            'connectionTypes',
            'authTypes',
            'registeredAdapters'
        ));
    }

    /**
     * Store a newly created lab partner
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:lab_partners,code|alpha_dash',
            'connection_type' => 'required|in:api,hl7,manual',
            'api_endpoint' => 'nullable|url|max:500',
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'auth_type' => 'nullable|in:api_key,oauth,basic,none',
            'credentials' => 'nullable|json',
            'supported_tests' => 'nullable|json',
            'field_mapping' => 'nullable|json',
            'priority' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        // Parse and validate JSON fields
        if ($request->has('credentials') && is_string($request->credentials)) {
            $decoded = json_decode($request->credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->withInput()
                    ->withErrors(['credentials' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }
            $validated['credentials'] = $decoded;
        }

        if ($request->has('supported_tests') && is_string($request->supported_tests)) {
            $decoded = json_decode($request->supported_tests, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->withInput()
                    ->withErrors(['supported_tests' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }
            $validated['supported_tests'] = $decoded;
        }

        if ($request->has('field_mapping') && is_string($request->field_mapping)) {
            $decoded = json_decode($request->field_mapping, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->withInput()
                    ->withErrors(['field_mapping' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }
            $validated['field_mapping'] = $decoded;
        }

        try {
            $labPartner = LabPartner::create($validated);

            Log::info('Lab partner created', [
                'id' => $labPartner->id,
                'name' => $labPartner->name,
                'code' => $labPartner->code,
            ]);

            return redirect()
                ->route('admin.lab-partners.show', $labPartner)
                ->with('success', 'Lab partner created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create lab partner', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = config('app.debug')
                ? 'Failed to create lab partner: ' . $e->getMessage()
                : 'Failed to create lab partner. Please try again.';

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Display the specified lab partner
     */
    public function show(LabPartner $labPartner)
    {
        $labPartner->loadCount(['labSubmissions', 'results']);

        $stats = [
            'total_submissions' => $labPartner->getTotalSubmissions(),
            'pending_submissions' => $labPartner->getPendingSubmissions(),
            'failed_submissions' => $labPartner->getFailedSubmissions(),
            'success_rate' => $labPartner->getSuccessRate(),
            'avg_turnaround' => $labPartner->getAverageTurnaroundTime(),
        ];

        $recentSubmissions = $labPartner->labSubmissions()
            ->with('order.user')
            ->latest()
            ->limit(20)
            ->get();

        // Test connection
        $connectionStatus = null;
        if ($labPartner->is_active && $labPartner->connection_type !== 'manual') {
            try {
                $connectionStatus = $labPartner->testConnection();
            } catch (\Exception $e) {
                $connectionStatus = false;
                Log::warning('Connection test failed', [
                    'lab_partner' => $labPartner->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('admin.lab-partners.show', compact(
            'labPartner',
            'stats',
            'recentSubmissions',
            'connectionStatus'
        ));
    }

    /**
     * Show the form for editing the specified lab partner
     */
    public function edit(LabPartner $labPartner)
    {
        $connectionTypes = ['api', 'hl7', 'manual'];
        $authTypes = ['api_key', 'oauth', 'basic', 'none'];
        $registeredAdapters = $this->labManager->getRegisteredAdapters();

        // Convert JSON fields to strings for form
        $labPartner->credentials_json = json_encode($labPartner->credentials, JSON_PRETTY_PRINT);
        $labPartner->supported_tests_json = json_encode($labPartner->supported_tests, JSON_PRETTY_PRINT);
        $labPartner->field_mapping_json = json_encode($labPartner->field_mapping, JSON_PRETTY_PRINT);

        return view('admin.lab-partners.edit', compact(
            'labPartner',
            'connectionTypes',
            'authTypes',
            'registeredAdapters'
        ));
    }

    /**
     * Update the specified lab partner
     */
    public function update(Request $request, LabPartner $labPartner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|alpha_dash|unique:lab_partners,code,' . $labPartner->id,
            'connection_type' => 'required|in:api,hl7,manual',
            'api_endpoint' => 'nullable|url|max:500',
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'auth_type' => 'nullable|in:api_key,oauth,basic,none',
            'credentials' => 'nullable|json',
            'supported_tests' => 'nullable|json',
            'field_mapping' => 'nullable|json',
            'priority' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        // Parse and validate JSON fields
        if ($request->has('credentials') && is_string($request->credentials)) {
            $decoded = json_decode($request->credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->withInput()
                    ->withErrors(['credentials' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }
            $validated['credentials'] = $decoded;
        }

        if ($request->has('supported_tests') && is_string($request->supported_tests)) {
            $decoded = json_decode($request->supported_tests, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->withInput()
                    ->withErrors(['supported_tests' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }
            $validated['supported_tests'] = $decoded;
        }

        if ($request->has('field_mapping') && is_string($request->field_mapping)) {
            $decoded = json_decode($request->field_mapping, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()
                    ->withInput()
                    ->withErrors(['field_mapping' => 'Invalid JSON format: ' . json_last_error_msg()]);
            }
            $validated['field_mapping'] = $decoded;
        }

        // Don't update credentials if they're empty (preserve existing)
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        if (empty($validated['api_secret'])) {
            unset($validated['api_secret']);
        }

        try {
            $labPartner->update($validated);

            Log::info('Lab partner updated', [
                'id' => $labPartner->id,
                'name' => $labPartner->name,
            ]);

            return redirect()
                ->route('admin.lab-partners.show', $labPartner)
                ->with('success', 'Lab partner updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update lab partner', [
                'id' => $labPartner->id,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = config('app.debug')
                ? 'Failed to update lab partner: ' . $e->getMessage()
                : 'Failed to update lab partner. Please try again.';

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Remove the specified lab partner
     */
    public function destroy(LabPartner $labPartner)
    {
        // Check if lab partner has any submissions (including soft deleted)
        if ($labPartner->labSubmissions()->withTrashed()->exists()) {
            return back()->with('error', 'Cannot delete lab partner with existing submissions');
        }

        try {
            $name = $labPartner->name;
            $labPartner->delete();

            Log::warning('Lab partner deleted', [
                'name' => $name,
            ]);

            return redirect()
                ->route('admin.lab-partners.index')
                ->with('success', 'Lab partner deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete lab partner', [
                'id' => $labPartner->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete lab partner: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to lab partner (AJAX)
     */
    public function testConnection(LabPartner $labPartner)
    {
        if ($labPartner->connection_type === 'manual') {
            return response()->json([
                'success' => false,
                'message' => 'Manual lab partners do not have testable connections',
            ]);
        }

        try {
            $connected = $labPartner->testConnection();

            if ($connected) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection failed',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Lab partner connection test failed', [
                'id' => $labPartner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch test catalog from lab partner (AJAX)
     */
    public function fetchTestCatalog(LabPartner $labPartner)
    {
        try {
            $adapter = $this->labManager->getAdapter($labPartner);
            $tests = $adapter->getSupportedTests();

            return response()->json([
                'success' => true,
                'tests' => $tests,
                'count' => count($tests),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch test catalog', [
                'id' => $labPartner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test catalog: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(LabPartner $labPartner)
    {
        try {
            $labPartner->update(['is_active' => !$labPartner->is_active]);

            $status = $labPartner->is_active ? 'activated' : 'deactivated';

            Log::info("Lab partner {$status}", [
                'id' => $labPartner->id,
                'name' => $labPartner->name,
            ]);

            return back()->with('success', "Lab partner {$status} successfully");

        } catch (\Exception $e) {
            Log::error('Failed to toggle lab partner status', [
                'id' => $labPartner->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
}