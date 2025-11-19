{{-- resources/views/admin/records/results.blade.php --}}
@extends('layouts.admin')

@section('title', 'Patient Results')

@section('content')
<div class="container mx-auto px-4 py-8">
    <a href="{{ route('admin.records.show', $patient) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
        ← Back to Profile
    </a>

    <div class="bg-white rounded-2xl shadow">
        <div class="px-6 py-5 border-b border-gray-100">
            <h1 class="text-3xl font-bold text-gray-900">Results for {{ $patient->full_name }}</h1>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Critical</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Viewed</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($results as $result)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $result->result_date->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $result->order->order_number }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $result->has_critical_values ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $result->has_critical_values ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $result->patient_viewed_at ? $result->patient_viewed_at->diffForHumans() : 'Not yet' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.results.show', $result) }}" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No results available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $results->links() }}
        </div>
    </div>
</div>
@endsection
