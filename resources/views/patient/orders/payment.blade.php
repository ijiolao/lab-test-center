{{-- resources/views/patient/orders/payment.blade.php --}}
@extends('layouts.app')

@section('title', 'Complete Payment for Order ' . $order->order_number)

@section('content')
<div class="container mx-auto px-4 py-10">
    <div class="max-w-5xl mx-auto">
        <a href="{{ route('patient.orders.show', $order) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Order Details
        </a>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Payment Form --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h1 class="text-2xl font-bold text-gray-900">Secure Payment</h1>
                    <p class="text-gray-600 text-sm mt-1">Use a debit or credit card to complete your order.</p>
                </div>
                <div class="p-6 space-y-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex -space-x-2">
                            <span class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 rounded-full">💳</span>
                            <span class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 rounded-full">💠</span>
                            <span class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 rounded-full">💰</span>
                        </div>
                        <p class="text-sm text-gray-500">Payments processed securely over encrypted connection.</p>
                    </div>

                    @if(session('error'))
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('patient.orders.payment.process', $order) }}" class="space-y-5">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                            <input type="text" name="cardholder_name" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="Jane Doe" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Card Details</label>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <input type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="19" placeholder="1234 5678 9012 3456" class="md:col-span-2 border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" required>
                                <input type="text" placeholder="MM / YY" maxlength="7" class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" required>
                                <input type="text" placeholder="CVC" maxlength="4" class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Card details are securely tokenised. No information is stored on our servers.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Billing Postal Code</label>
                            <input type="text" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="SW1A 1AA" required>
                        </div>

                        <input type="hidden" name="payment_method_id" value="manual-token">

                        <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700">
                            Pay {{ $order->formatted_total }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="bg-white rounded-2xl shadow h-max">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>
                    <p class="text-sm text-gray-500">Order {{ $order->order_number }}</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Tests</span>
                        <span>{{ $order->items->count() }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        @foreach($order->items as $item)
                            <div class="flex justify-between text-sm">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $item->test_name }}</p>
                                    <p class="text-gray-500">{{ $item->test_code }}</p>
                                </div>
                                <p class="font-semibold text-gray-900">{{ $item->formatted_price }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-between text-base font-semibold text-gray-900">
                            <span>Total Due</span>
                            <span>{{ $order->formatted_total }}</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                        Need assistance? Contact our support team at
                        <a href="mailto:billing@labtestcenter.com" class="text-blue-600 hover:underline">billing@labtestcenter.com</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
