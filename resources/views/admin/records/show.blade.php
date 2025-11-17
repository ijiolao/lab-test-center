{{-- resources/views/admin/records/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Patient Profile')

@section('content')
<div class="container mx-auto px-4 py-8">
    <a href="{{ route('admin.records.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
        ← Back to Patient Records
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $patient->full_name }}</h1>
                        <p class="text-gray-500">Patient #{{ $patient->id }} • Joined {{ $patient->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="space-x-2">
                        <a href="{{ route('admin.records.edit', $patient) }}" class="px-4 py-2 text-sm rounded-lg border border-gray-300">Edit</a>
                        <a href="{{ route('admin.records.reset-password-form', $patient) }}" class="px-4 py-2 text-sm rounded-lg border border-gray-300">Reset Password</a>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Email</p>
                        <p class="font-semibold text-gray-900">{{ $patient->email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Phone</p>
                        <p class="font-semibold text-gray-900">{{ $patient->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Date of Birth</p>
                        <p class="font-semibold text-gray-900">{{ optional($patient->date_of_birth)->format('d M Y') }} ({{ $patient->age }} yrs)</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Status</p>
                        <form method="POST" action="{{ route('admin.records.toggle-active', $patient) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $patient->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $patient->is_active ? 'Active - click to suspend' : 'Inactive - click to activate' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    <a href="{{ route('admin.records.orders', $patient) }}" class="text-sm text-blue-600 hover:underline">View all</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">Order {{ $order->order_number }}</p>
                                <p class="text-xs text-gray-500">{{ $order->collection_date?->format('d M Y') }} • {{ $order->items->count() }} tests</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-gray-100">{{ ucfirst(str_replace('_',' ', $order->status)) }}</span>
                                <div class="text-sm text-gray-500 mt-1">{{ $order->formatted_total }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-6 text-sm text-gray-500">No orders found.</p>
                    @endforelse
                </div>
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $orders->withQueryString()->links() }}
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Snapshot</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Total Orders</dt>
                        <dd class="font-semibold text-gray-900">{{ $stats['total_orders'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Total Spent</dt>
                        <dd class="font-semibold text-gray-900">£{{ number_format($stats['total_spent'] ?? 0, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Completed Tests</dt>
                        <dd class="font-semibold text-gray-900">{{ $stats['completed_tests'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Pending Results</dt>
                        <dd class="font-semibold text-gray-900">{{ $stats['pending_results'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-2xl shadow">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Results</h3>
                    <a href="{{ route('admin.records.results', $patient) }}" class="text-sm text-blue-600 hover:underline">Open</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($results as $result)
                        <div class="px-6 py-4">
                            <p class="font-semibold text-gray-900">{{ $result->result_date->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500">Order {{ $result->order->order_number }}</p>
                            <span class="inline-flex mt-2 px-2 py-1 rounded-full text-xs font-medium {{ $result->has_critical_values ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                {{ $result->has_critical_values ? 'Critical' : 'Normal' }}
                            </span>
                        </div>
                    @empty
                        <p class="px-6 py-6 text-sm text-gray-500">No recent results available.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Activity</h3>
                </div>
                <div class="max-h-72 overflow-y-auto divide-y divide-gray-100">
                    @forelse($activityLog as $activity)
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at->diffForHumans() }} • {{ optional($activity->causer)->full_name ?? 'System' }}</p>
                        </div>
                    @empty
                        <p class="px-6 py-6 text-sm text-gray-500">No recent activity logged.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
