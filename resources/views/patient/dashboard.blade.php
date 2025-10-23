{{-- resources/views/patient/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">My Dashboard</h1>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm mb-2">Total Orders</div>
            <div class="text-4xl font-bold text-gray-900">{{ $totalOrders }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm mb-2">Pending Results</div>
            <div class="text-4xl font-bold text-orange-600">{{ $pendingResults }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-600 text-sm mb-2">Completed Tests</div>
            <div class="text-4xl font-bold text-green-600">{{ $completedTests }}</div>
        </div>
    </div>

    {{-- New Results Available --}}
    @if($availableResults->count() > 0)
    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-green-600 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <div class="ml-4 flex-1">
                <h2 class="text-xl font-semibold text-green-900 mb-4">New Results Available</h2>
                @foreach($availableResults as $result)
                <div class="bg-white rounded-lg border border-green-200 p-4 mb-3 last:mb-0">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium text-gray-900">Order {{ $result->order->order_number }}</div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ $result->order->items->count() }} test(s) • 
                                Result date: {{ $result->result_date->format('d M Y') }}
                            </div>
                        </div>
                        <a href="{{ route('patient.results.show', $result) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            View Result
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Upcoming Appointments --}}
    @if($upcomingOrders->count() > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-blue-900 mb-4">Upcoming Appointments</h2>
        <div class="space-y-3">
            @foreach($upcomingOrders as $order)
            <div class="bg-white rounded-lg border border-blue-200 p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="font-medium text-gray-900">{{ $order->collection_date->format('l, F j, Y') }}</div>
                        @if($order->collection_time)
                            <div class="text-sm text-gray-600">at {{ $order->collection_time->format('g:i A') }}</div>
                        @endif
                        <div class="text-sm text-gray-500 mt-1">
                            {{ $order->items->count() }} test(s) • Order {{ $order->order_number }}
                        </div>
                    </div>
                    <a href="{{ route('patient.orders.show', $order) }}" class="text-blue-600 hover:underline text-sm">
                        View Details →
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Recent Orders --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Orders</h2>
                    <a href="{{ route('patient.orders.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                        + New Order
                    </a>
                </div>
            </div>
            <div class="p-6">
                @forelse($recentOrders as $order)
                <div class="border-b border-gray-200 py-4 last:border-b-0">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <a href="{{ route('patient.orders.show', $order) }}" class="font-medium text-blue-600 hover:underline">
                                {{ $order->order_number }}
                            </a>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ $order->items->count() }} test(s) • 
                                {{ $order->created_at->format('d M Y') }}
                            </div>
                            <div class="mt-2">
                                @foreach($order->items->take(2) as $item)
                                    <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded mr-1 mb-1">
                                        {{ $item->test_name }}
                                    </span>
                                @endforeach
                                @if($order->items->count() > 2)
                                    <span class="text-xs text-gray-500">+{{ $order->items->count() - 2 }} more</span>
                                @endif
                            </div>
                        </div>
                        <div class="ml-4">
                            @php
                                $statusColors = [
                                    'pending_payment' => 'bg-gray-100 text-gray-800',
                                    'paid' => 'bg-blue-100 text-blue-800',
                                    'scheduled' => 'bg-blue-100 text-blue-800',
                                    'collected' => 'bg-purple-100 text-purple-800',
                                    'sent_to_lab' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-yellow-100 text-yellow-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-3 py-1 text-xs font-medium rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="mt-2">No orders yet</p>
                    <a href="{{ route('patient.orders.create') }}" class="mt-4 inline-block text-blue-600 hover:underline">
                        Create your first order →
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Quick Actions</h2>
            </div>
            <div class="p-6 space-y-4">
                <a href="{{ route('patient.tests.index') }}" class="block bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg p-4 transition">
                    <div class="flex items-center">
                        <div class="bg-blue-600 rounded-full p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="font-semibold text-gray-900">Browse Tests</div>
                            <div class="text-sm text-gray-600">View available tests and pricing</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('patient.orders.create') }}" class="block bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-4 transition">
                    <div class="flex items-center">
                        <div class="bg-green-600 rounded-full p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="font-semibold text-gray-900">Create New Order</div>
                            <div class="text-sm text-gray-600">Book lab tests online</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('patient.results.index') }}" class="block bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-4 transition">
                    <div class="flex items-center">
                        <div class="bg-purple-600 rounded-full p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="font-semibold text-gray-900">View Results</div>
                            <div class="text-sm text-gray-600">Access your test results</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('patient.profile.edit') }}" class="block bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg p-4 transition">
                    <div class="flex items-center">
                        <div class="bg-gray-600 rounded-full p-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <div class="font-semibold text-gray-900">My Profile</div>
                            <div class="text-sm text-gray-600">Update your information</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Help Section --}}
    <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-4">
                <h3 class="font-semibold text-gray-900">Need Help?</h3>
                <p class="text-sm text-gray-600 mt-1">
                    If you have any questions about your tests or results, please contact our support team.
                </p>
                <div class="mt-3 space-x-4">
                    <a href="mailto:support@labtestcenter.com" class="text-blue-600 hover:underline text-sm">
                        Email Support
                    </a>
                    <a href="tel:+441234567890" class="text-blue-600 hover:underline text-sm">
                        Call Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection