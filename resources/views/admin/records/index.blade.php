{{-- resources/views/admin/records/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Patient Records')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Patient Records</h1>
            <p class="text-gray-600">Manage patient accounts and view their order history.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Total Patients</p>
            <p class="text-3xl font-semibold text-gray-900 mt-2">{{ $stats['total_patients'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">Active</p>
            <p class="text-3xl font-semibold text-green-600 mt-2">{{ $stats['active_patients'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">New this month</p>
            <p class="text-3xl font-semibold text-blue-600 mt-2">{{ $stats['new_this_month'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-sm text-gray-500">With Orders</p>
            <p class="text-3xl font-semibold text-purple-600 mt-2">{{ $stats['with_orders'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow mb-6">
        <form method="GET" class="p-6 grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Name, email or phone">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Any</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Registered From</label>
                <input type="date" name="registered_from" value="{{ request('registered_from') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Registered To</label>
                <input type="date" name="registered_to" value="{{ request('registered_to') }}" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 bg-gray-900 text-white px-4 py-2 rounded-lg">Filter</button>
                <a href="{{ route('admin.records.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($patients as $patient)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $patient->full_name }}</div>
                            <div class="text-sm text-gray-500">{{ $patient->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900">{{ $patient->orders_count }} total</p>
                            <p class="text-xs text-gray-500">{{ $patient->completed_orders_count }} completed</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $patient->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $patient->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $patient->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('admin.records.show', $patient) }}" class="text-blue-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">No patients found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $patients->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
