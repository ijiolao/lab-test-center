{{-- resources/views/admin/records/merge.blade.php --}}
@extends('layouts.admin')

@section('title', 'Merge Patient Records')

@section('content')
<div class="container mx-auto px-4 py-8">
    <a href="{{ route('admin.records.show', $patient) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
        ← Back to Profile
    </a>

    <div class="bg-white rounded-2xl shadow">
        <div class="px-6 py-5 border-b border-gray-100">
            <h1 class="text-3xl font-bold text-gray-900">Merge Records for {{ $patient->full_name }}</h1>
            <p class="text-sm text-gray-600 mt-1">Select the duplicate account to merge into the primary record.</p>
        </div>
        <div class="p-6 space-y-6">
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-900">
                <p class="font-semibold">Before you merge</p>
                <p class="mt-1">Confirm that both records belong to the same person. All orders and results from the duplicate will move to the primary account.</p>
            </div>

            <form method="POST" action="{{ route('admin.records.merge', $patient) }}" class="space-y-4">
                @csrf
                <label class="block text-sm font-medium text-gray-700">Duplicate Account</label>
                <select name="merge_with" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                    <option value="">Select an account</option>
                    @foreach($duplicates as $duplicate)
                        <option value="{{ $duplicate->id }}">
                            #{{ $duplicate->id }} • {{ $duplicate->full_name }} ({{ $duplicate->email }}) — {{ $duplicate->orders_count }} orders
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="w-full md:w-auto px-6 py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700">
                    Merge Records
                </button>
            </form>

            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Potential Duplicates</h2>
                <div class="border border-gray-200 rounded-xl divide-y divide-gray-200">
                    @forelse($duplicates as $duplicate)
                        <div class="p-4">
                            <p class="font-semibold text-gray-900">{{ $duplicate->full_name }}</p>
                            <p class="text-sm text-gray-500">{{ $duplicate->email }}</p>
                            <p class="text-xs text-gray-500 mt-1">Orders: {{ $duplicate->orders_count }} • Created {{ $duplicate->created_at->format('d M Y') }}</p>
                        </div>
                    @empty
                        <p class="p-4 text-sm text-gray-500">No duplicates detected.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
