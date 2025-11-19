{{-- resources/views/admin/records/orders.blade.php --}}
@extends('layouts.admin')

@section('title', 'Patient Orders')

@section('content')
<div class="container mx-auto px-4 py-8">
    <a href="{{ route('admin.records.show', $patient) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
        ← Back to Profile
    </a>

    <div class="bg-white rounded-2xl shadow">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Orders for {{ $patient->full_name }}</h1>
                <p class="text-sm text-gray-600">{{ $orders->total() }} orders in total</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collection</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900">{{ $order->order_number }}</p>
                                <p class="text-xs text-gray-500">Placed {{ $order->created_at->format('d M Y') }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $order->items->count() }} tests</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $order->collection_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100">{{ ucfirst(str_replace('_',' ', $order->status)) }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($order->payment_status) }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
