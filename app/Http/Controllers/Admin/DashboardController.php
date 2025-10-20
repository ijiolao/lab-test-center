<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Result;
use App\Models\LabSubmission;
use App\Services\OrderService;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $orderService;
    protected $resultService;

    public function __construct(OrderService $orderService, ResultService $resultService)
    {
        $this->middleware(['auth', 'can:view-admin-dashboard']);
        $this->orderService = $orderService;
        $this->resultService = $resultService;
    }

    /**
     * Display the admin dashboard
     */
    public function index(Request $request)
    {
        // Date validation
        $date = today();
        if ($request->has('date')) {
            try {
                $date = \Carbon\Carbon::parse($request->date);
            } catch (\Exception $e) {
                // Invalid date, use today
                $date = today();
            }
        }

        // Get statistics
        $stats = $this->getStatistics($date);

        // Get today's orders
        $todaysOrders = Order::with(['user', 'items'])
            ->whereDate('collection_date', $date)
            ->orderBy('collection_time')
            ->limit(10)
            ->get();

        // Get orders awaiting collection
        $pendingCollection = Order::with(['user', 'items'])
            ->whereDate('collection_date', $date)
            ->whereIn('status', ['paid', 'scheduled'])
            ->orderBy('collection_time')
            ->get();

        // Get orders awaiting lab submission
        $awaitingLabSubmission = Order::with(['user', 'items'])
            ->awaitingLabSubmission()
            ->limit(10)
            ->get();

        // Get results awaiting review
        $awaitingReview = Result::with(['order.user', 'order.items'])
            ->unreviewed()
            ->critical()
            ->latest('result_date')
            ->limit(10)
            ->get();

        // Get recent activity (fix N+1)
        $recentActivity = $this->getRecentActivity();

        // Get lab submission status
        $labSubmissionStats = $this->getLabSubmissionStats();

        return view('admin.dashboard', compact(
            'stats',
            'date',
            'todaysOrders',
            'pendingCollection',
            'awaitingLabSubmission',
            'awaitingReview',
            'recentActivity',
            'labSubmissionStats'
        ));
    }

    /**
     * Get dashboard statistics
     */
    protected function getStatistics($date)
    {
        return [
            // Today's orders
            'today_total' => Order::whereDate('collection_date', $date)->count(),
            'today_collected' => Order::whereDate('collected_at', $date)->count(),
            'pending_collection' => Order::whereDate('collection_date', $date)
                ->whereIn('status', ['paid', 'scheduled'])
                ->count(),

            // This week
            'week_orders' => Order::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'week_revenue' => Order::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->where('payment_status', 'paid')->sum('total'),

            // This month
            'month_orders' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'month_revenue' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('payment_status', 'paid')
                ->sum('total'),

            // Lab submissions
            'awaiting_lab_submission' => Order::awaitingLabSubmission()->count(),
            'pending_results' => Order::whereIn('status', ['sent_to_lab', 'processing'])->count(),

            // Results
            'results_today' => Result::whereDate('result_date', $date)->count(),
            'critical_review_needed' => Result::critical()->unreviewed()->count(),
            'results_ready_to_notify' => Result::reviewed()
                ->whereNull('patient_notified_at')
                ->count(),

            // Failed submissions needing attention
            'failed_submissions' => LabSubmission::failed()
                ->where('retry_count', '<', config('lab-partners.max_retries', 3))
                ->count(),
        ];
    }

    /**
     * Get recent activity (Fixed N+1)
     */
    protected function getRecentActivity()
    {
        return activity()
            ->with('causer') // Eager load to prevent N+1
            ->latest()
            ->limit(20)
            ->get()
            ->map(function($activity) {
                return [
                    'description' => $activity->description,
                    'subject_type' => class_basename($activity->subject_type),
                    'subject_id' => $activity->subject_id,
                    'causer' => $activity->causer?->full_name ?? 'System',
                    'created_at' => $activity->created_at,
                    'properties' => $activity->properties,
                ];
            });
    }

    /**
     * Get lab submission statistics
     */
    protected function getLabSubmissionStats()
    {
        $recent = LabSubmission::recent(7)->get();

        return [
            'total' => $recent->count(),
            'successful' => $recent->where('status', 'completed')->count(),
            'pending' => $recent->whereIn('status', ['pending', 'submitted', 'processing'])->count(),
            'failed' => $recent->where('status', 'failed')->count(),
            'success_rate' => $recent->count() > 0 
                ? round(($recent->where('status', 'completed')->count() / $recent->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get statistics for charts (AJAX endpoint)
     */
    public function chartData(Request $request)
    {
        $days = min($request->input('days', 30), 365); // Max 1 year
        $startDate = now()->subDays($days);

        // Orders by day
        $ordersByDay = Order::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('payment_status', 'paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Orders by status
        $ordersByStatus = Order::where('created_at', '>=', $startDate)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Results by day
        $resultsByDay = Result::where('result_date', '>=', $startDate)
            ->select(
                DB::raw('DATE(result_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN has_critical_values = 1 THEN 1 ELSE 0 END) as critical_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'orders_by_day' => $ordersByDay,
            'orders_by_status' => $ordersByStatus,
            'results_by_day' => $resultsByDay,
        ]);
    }
}