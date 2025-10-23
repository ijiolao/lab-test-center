{{-- resources/views/patient/orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('patient.orders.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Orders
        </a>

        <h1 class="text-3xl font-bold text-gray-900 mb-2">Order {{ $order->order_number }}</h1>
        <p class="text-gray-600 mb-8">Created on {{ $order->created_at->format('F j, Y') }}</p>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        {{-- Payment Required Alert --}}
        @if($order->payment_status === 'pending')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 mt-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-4 flex-1">
                        <h3 class="font-semibold text-yellow-900">Payment Required</h3>
                        <p class="text-sm text-yellow-800 mt-1">Complete payment to confirm your order.</p>
                        <a href="{{ route('patient.orders.payment', $order) }}" class="mt-3 inline-block bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700">
                            Pay Now
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Order Status --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Order Status</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="text-sm text-gray-600">Current Status</div>
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
                        <span class="inline-block mt-1 px-4 py-2 text-sm font-medium rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Payment Status</div>
                        @php
                            $paymentColors = [
                                'pending' => 'bg-gray-100 text-gray-800',
                                'paid' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'refunded' => 'bg-orange-100 text-orange-800',
                            ];
                        @endphp
                        <span class="inline-block mt-1 px-4 py-2 text-sm font-medium rounded-full {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="relative">
                    <div class="absolute top-4 left-0 right-0 h-1 bg-gray-200">
                        <div class="h-full bg-blue-600 transition-all duration-500" style="width: {{ $order->getProgressPercentage() }}%"></div>
                    </div>
                    <div class="relative flex justify-between">
                        @php
                            $steps = [
                                'pending_payment' => 'Payment',
                                'scheduled' => 'Scheduled',
                                'collected' => 'Collected',
                                'processing' => 'Processing',
                                'completed' => 'Completed'
                            ];
                            $currentStep = array_search($order->status, array_keys($steps));
                        @endphp
                        @foreach($steps as $key => $label)
                            @php
                                $stepIndex = array_search($key, array_keys($steps));
                                $isComplete = $stepIndex <= $currentStep || $order->status === 'completed';
                            @endphp
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $isComplete ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    @if($isComplete)
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        {{ $stepIndex + 1 }}
                                    @endif
                                </div>
                                <div class="text-xs mt-2 text-center {{ $isComplete ? 'text-blue-600 font-medium' : 'text-gray-500' }}">
                                    {{ $label }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Collection Details --}}
        @if($order->collection_date)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Collection Details</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm text-gray-600">Collection Date</div>
                        <div class="font-medium text-gray-900 mt-1">
                            {{ $order->collection_date->format('l, F j, Y') }}
                        </div>
                    </div>
                    @if($order->collection_time)
                    <div>
                        <div class="text-sm text-gray-600">Collection Time</div>
                        <div class="font-medium text-gray-900 mt-1">
                            {{ $order->collection_time->format('g:i A') }}
                        </div>
                    </div>
                    @endif
                    @if($order->collection_location)
                    <div class="md:col-span-2">
                        <div class="text-sm text-gray-600">Location</div>
                        <div class="font-medium text-gray-900 mt-1">
                            {{ $order->collection_location }}
                        </div>
                    </div>
                    @endif
                    @if($order->collected_at)
                    <div class="md:col-span-2">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center text-green-800">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Specimen collected on {{ $order->collected_at->format('F j, Y \a\t g:i A') }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Tests Ordered --}}
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Tests Ordered</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($order->items as $item)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $item->test_name }}</div>
                                <div class="text-sm text-gray-500">{{ $item->test_code }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ ucfirst($item->test->specimen_type) }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900">
                                {{ $item->formatted_price }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right text-sm font-medium text-gray-900">Subtotal:</td>
                            <td class="px-6 py-3 text-right font-medium text-gray-900">{{ $order->formatted_subtotal }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right text-sm font-medium text-gray-900">VAT (20%):</td>
                            <td class="px-6 py-3 text-right font-medium text-gray-900">{{ $order->formatted_tax }}</td>
                        </tr>
                        <tr class="border-t-2 border-gray-300">
                            <td colspan="2" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total:</td>
                            <td class="px-6 py-3 text-right text-lg font-bold text-gray-900">{{ $order->formatted_total }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Result Available --}}
        @if($order->result)
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-green-600 mt-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div class="ml-4 flex-1">
                    <h3 class="font-semibold text-green-900">Your Results Are Ready!</h3>
                    <p class="text-sm text-green-800 mt-1">
                        Your test results are now available. Click below to view them.
                    </p>
                    <a href="{{ route('patient.results.show', $order->result) }}" class="mt-3 inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                        View Results
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Special Instructions --}}
        @if($order->special_instructions)
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Special Instructions</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-700">{{ $order->special_instructions }}</p>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex justify-between items-center">
            <a href="{{ route('patient.orders.index') }}" class="text-gray-600 hover:text-gray-900">
                ← Back to All Orders
            </a>
            <div class="space-x-3">
                @if($order->payment_status === 'paid' && !$order->result)
                    <a href="{{ route('patient.orders.receipt', $order) }}" class="inline-block bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                        Download Receipt
                    </a>
                @endif
                @if($order->canBeCancelled())
                    <button onclick="if(confirm('Are you sure you want to cancel this order?')) document.getElementById('cancel-form').submit()" 
                            class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200">
                        Cancel Order
                    </button>
                    <form id="cancel-form" action="{{ route('patient.orders.cancel', $order) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="reason" value="Cancelled by patient">
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection