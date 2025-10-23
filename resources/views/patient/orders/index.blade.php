{{-- resources/views/patient/orders/index.blade.php --}}
@extends('layouts.app')

@section('title', 'My Orders')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Orders</h1>
            <a href="{{ route('patient.orders.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Order
            </a>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <select name="status" class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="collected" {{ request('status') == 'collected' ? 'selected' : '' }}>Collected</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('patient.orders.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 text-center">
                    Reset
                </a>
            </form>
        </div>

        {{-- Orders List --}}
        <div class="space-y-4">
            @forelse($orders as $order)
            <div class="bg-white rounded-lg shadow hover:shadow-md transition">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <a href="{{ route('patient.orders.show', $order) }}" class="text-xl font-semibold text-blue-600 hover:underline">
                                    {{ $order->order_number }}
                                </a>
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

                            <div class="text-sm text-gray-600 space-y-1">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Created: {{ $order->created_at->format('F j, Y') }}
                                </div>
                                @if($order->collection_date)
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Collection: {{ $order->collection_date->format('F j, Y') }}
                                    @if($order->collection_time)
                                        at {{ $order->collection_time->format('g:i A') }}
                                    @endif
                                </div>
                                @endif
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    {{ $order->items->count() }} test(s)
                                </div>
                            </div>

                            {{-- Tests --}}
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($order->items->take(3) as $item)
                                    <span class="inline-block bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
                                        {{ $item->test_name }}
                                    </span>
                                @endforeach
                                @if($order->items->count() > 3)
                                    <span class="inline-block text-gray-500 text-xs px-2 py-1">
                                        +{{ $order->items->count() - 3 }} more
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="ml-6 flex flex-col items-end space-y-3">
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-900">{{ $order->formatted_total }}</div>
                                <div class="text-xs text-gray-500">
                                    <span class="px-2 py-1 rounded-full {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('patient.orders.show', $order) }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-sm">
                                View Details
                            </a>
                        </div>
                    </div>

                    {{-- Payment Alert --}}
                    @if($order->payment_status === 'pending')
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex items-center justify-between">
                            <div class="flex items-center text-yellow-800 text-sm">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Payment required to complete your order
                            </div>
                            <a href="{{ route('patient.orders.payment', $order) }}" class="bg-yellow-600 text-white px-4 py-1 rounded text-sm hover:bg-yellow-700">
                                Pay Now
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No Orders Found</h3>
                <p class="mt-2 text-gray-600">You haven't placed any orders yet.</p>
                <a href="{{ route('patient.orders.create') }}" class="mt-6 inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    Create Your First Order
                </a>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection