{{-- resources/views/admin/results/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Results Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Results Management</h1>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Awaiting Review</div>
            <div class="text-3xl font-bold text-yellow-600 mt-2">{{ $stats['awaiting_review'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Ready to Notify</div>
            <div class="text-3xl font-bold text-blue-600 mt-2">{{ $stats['ready_to_notify'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">This Week</div>
            <div class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_this_week'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Critical This Week</div>
            <div class="text-3xl font-bold text-red-600 mt-2">{{ $stats['critical_this_week'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
                <select name="filter" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Results</option>
                    <option value="critical" {{ request('filter') == 'critical' ? 'selected' : '' }}>Critical Values</option>
                    <option value="unreviewed" {{ request('filter') == 'unreviewed' ? 'selected' : '' }}>Unreviewed</option>
                    <option value="critical_unreviewed" {{ request('filter') == 'critical_unreviewed' ? 'selected' : '' }}>Critical & Unreviewed</option>
                    <option value="ready_to_notify" {{ request('filter') == 'ready_to_notify' ? 'selected' : '' }}>Ready to Notify</option>
                    <option value="notified" {{ request('filter') == 'notified' ? 'selected' : '' }}>Notified</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input type="date" 
                       name="date_from" 
                       value="{{ request('date_from') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input type="date" 
                       name="date_to" 
                       value="{{ request('date_to') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" 
                       name="search" 
                       placeholder="Order # or patient..."
                       value="{{ request('search') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
                    Filter
                </button>
                <a href="{{ route('admin.results.index') }}" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Results Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Order #
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Patient
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tests
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Result Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($results as $result)
                <tr class="hover:bg-gray-50 {{ $result->has_critical_values && !$result->is_reviewed ? 'bg-red-50' : '' }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('admin.orders.show', $result->order) }}" class="text-blue-600 hover:underline font-medium">
                            {{ $result->order->order_number }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $result->order->user->full_name }}</div>
                        <div class="text-sm text-gray-500">{{ $result->order->user->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $result->order->items->count() }} test(s)</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $result->result_date->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-500">{{ $result->result_date->format('H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="space-y-1">
                            @if($result->has_critical_values)
                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                    Critical
                                </span>
                            @endif
                            @if(!$result->is_reviewed)
                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                    Unreviewed
                                </span>
                            @else
                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    Reviewed
                                </span>
                            @endif
                            @if($result->patient_notified_at)
                                <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                    Notified
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.results.show', $result) }}" class="text-blue-600 hover:underline">
                                View
                            </a>
                            @if(!$result->is_reviewed)
                                <a href="{{ route('admin.results.review-form', $result) }}" class="text-green-600 hover:underline">
                                    Review
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="mt-2">No results found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $results->links() }}
    </div>
</div>
@endsection