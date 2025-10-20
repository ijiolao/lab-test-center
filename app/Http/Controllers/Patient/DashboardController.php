<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Result;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display patient dashboard
     */
    public function index()
    {
        $user = auth()->user();

        // Statistics
        $totalOrders = $user->orders()->count();
        $pendingResults = $user->orders()
            ->whereIn('status', ['sent_to_lab', 'processing'])
            ->count();
        $completedTests = $user->orders()
            ->where('status', 'completed')
            ->withCount('items')
            ->get()
            ->sum('items_count');

        // Recent orders
        $recentOrders = $user->orders()
            ->with('items.test')
            ->latest()
            ->limit(5)
            ->get();

        // Available results (new, unviewed)
        $availableResults = Result::forPatient($user->id)
            ->whereNotNull('patient_notified_at')
            ->whereNull('patient_viewed_at')
            ->with('order.items')
            ->latest('result_date')
            ->get();

        // Upcoming appointments
        $upcomingOrders = $user->orders()
            ->whereIn('status', ['paid', 'scheduled'])
            ->where('collection_date', '>=', today())
            ->orderBy('collection_date')
            ->orderBy('collection_time')
            ->limit(3)
            ->get();

        return view('patient.dashboard', compact(
            'totalOrders',
            'pendingResults',
            'completedTests',
            'recentOrders',
            'availableResults',
            'upcomingOrders'
        ));
    }
}