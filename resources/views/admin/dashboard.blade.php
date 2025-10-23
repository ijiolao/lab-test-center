{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <div class="flex items-center space-x-4">
            <input type="date" 
                   name="date" 
                   value="{{ $date->format('Y-m-d') }}"
                   class="border border-gray-300 rounded-lg px-4 py-2"
                   onchange="window.location.href='{{ route('admin.dashboard') }}?date='+this.value">
            <span class="text-gray-600">{{ $date->format('D, M j, Y') }}</span>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Today's Total --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Today's Orders</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['today_total'] }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-gray-500">{{ $stats['today_collected'] }} collected</span>
            </div>
        </div>

        {{-- Pending Collection --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Collection</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2">{{ $stats['pending_collection'] }}</p>
                </div>
                <div class="bg-orange-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.orders.index') }}?status=scheduled" class="text-sm text-orange-600 hover:underline">View all →</a>
            </div>
        </div>

        {{-- Awaiting Lab Submission --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Awaiting Lab Submission</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ $stats['awaiting_lab_submission'] }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.orders.index') }}?status=collected" class="text-sm text-purple-600 hover:underline">Submit orders →</a>
            </div>
        </div>

        {{-- Critical Reviews Needed --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Critical Review Needed</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ $stats['critical_review_needed'] }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.results.index') }}?filter=critical_unreviewed" class="text-sm text-red-600 hover:underline">Review now →</a>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Today's Schedule --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Today's Collection Schedule</h2>
                        <a href="{{ route('admin.orders.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                            + New Order
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @forelse($pendingCollection as $order)
                        <div class="border-l-4 border-orange-500 pl-4 py-3 mb-4 hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <span class="font-semibold text-gray-900">{{ $order->user->full_name }}</span>
                                        <span class="text-sm text-gray-500">{{ $order->order_number }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        {{ $order->items->count() }} test(s) • 
                                        @if($order->collection_time)
                                            {{ $order->collection_time->format('H:i') }}
                                        @else
                                            Time not set
                                        @endif
                                    </div>
                                    <div class="mt-1">
                                        @foreach($order->items->take(3) as $item)
                                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded mr-1">
                                                {{ $item->test_name }}
                                            </span>
                                        @endforeach
                                        @if($order->items->count() > 3)
                                            <span class="text-xs text-gray-500">+{{ $order->items->count() - 3 }} more</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">
                                        Pending
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="mt-2">All collections completed for today</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Awaiting Lab Submission --}}
            @if($awaitingLabSubmission->count() > 0)
                <div class="bg-white rounded-lg shadow mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Ready for Lab Submission</h2>
                    </div>
                    <div class="p-6">
                        @foreach($awaitingLabSubmission as $order)
                            <div class="border-l-4 border-purple-500 pl-4 py-3 mb-4 hover:bg-gray-50">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-gray-900 hover:text-blue-600">
                                            {{ $order->order_number }}
                                        </a>
                                        <span class="text-sm text-gray-500 ml-2">{{ $order->user->full_name }}</span>
                                        <div class="text-sm text-gray-600 mt-1">
                                            Collected {{ $order->collected_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="bg-purple-600 text-white px-3 py-1 rounded text-sm hover:bg-purple-700">
                                        Submit
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Critical Results --}}
            @if($awaitingReview->count() > 0)
                <div class="bg-red-50 border border-red-200 rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-red-200 bg-red-100">
                        <h2 class="text-lg font-semibold text-red-900 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Critical Results
                        </h2>
                    </div>
                    <div class="p-4">
                        @foreach($awaitingReview as $result)
                            <div class="mb-3 pb-3 border-b border-red-100 last:border-0">
                                <a href="{{ route('admin.results.show', $result) }}" class="font-medium text-red-900 hover:text-red-700">
                                    {{ $result->order->order_number }}
                                </a>
                                <div class="text-sm text-red-700 mt-1">
                                    {{ $result->order->user->full_name }}
                                </div>
                                <div class="text-xs text-red-600 mt-1">
                                    {{ $result->result_date->diffForHumans() }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Lab Submission Stats --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Lab Submission Stats (7 days)</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Submissions</span>
                            <span class="font-semibold text-gray-900">{{ $labSubmissionStats['total'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Successful</span>
                            <span class="font-semibold text-green-600">{{ $labSubmissionStats['successful'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Pending</span>
                            <span class="font-semibold text-yellow-600">{{ $labSubmissionStats['pending'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Failed</span>
                            <span class="font-semibold text-red-600">{{ $labSubmissionStats['failed'] }}</span>
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-900">Success Rate</span>
                                <span class="font-bold text-lg text-blue-600">{{ $labSubmissionStats['success_rate'] }}%</span>
                            </div>
                            <div class="mt-2 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $labSubmissionStats['success_rate'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Activity --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @forelse($recentActivity->take(10) as $activity)
                            <div class="text-sm">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-1.5"></div>
                                    <div class="ml-3 flex-1">
                                        <p class="text-gray-700">{{ $activity['description'] }}</p>
                                        <p class="text-gray-500 text-xs mt-1">
                                            {{ $activity['causer'] }} • {{ $activity['created_at']->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">No recent activity</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">This Week</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['week_orders'] }}</p>
            <p class="text-sm text-gray-500 mt-1">orders</p>
            <p class="text-lg font-semibold text-green-600 mt-2">£{{ number_format($stats['week_revenue'], 2) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">This Month</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['month_orders'] }}</p>
            <p class="text-sm text-gray-500 mt-1">orders</p>
            <p class="text-lg font-semibold text-green-600 mt-2">£{{ number_format($stats['month_revenue'], 2) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Results Today</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['results_today'] }}</p>
            <p class="text-sm text-gray-500 mt-1">completed</p>
            <p class="text-sm text-orange-600 mt-2">{{ $stats['results_ready_to_notify'] }} ready to notify</p>
        </div>
    </div>
</div>
@endsection