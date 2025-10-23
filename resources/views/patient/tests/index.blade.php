{{-- resources/views/patient/tests/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Available Tests')

@section('content')
<div class="bg-blue-600 text-white py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold mb-4">Available Laboratory Tests</h1>
        <p class="text-xl text-blue-100">Professional, accurate testing with fast results</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Tests</label>
                <input type="text" 
                       name="search" 
                       placeholder="Search by name, code, or description..."
                       value="{{ request('search') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select name="category" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Search
                </button>
            </div>
        </form>
    </div>

    {{-- Tests Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($tests as $test)
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
            <div class="p-6">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $test->name }}</h3>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                        {{ $test->category }}
                    </span>
                </div>
                
                <p class="text-sm text-gray-600 mb-4 line-clamp-3">{{ $test->description }}</p>
                
                <div class="space-y-2 mb-4 text-sm text-gray-600">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Results in {{ $test->expected_completion }}
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                        {{ ucfirst($test->specimen_type) }} sample
                    </div>
                    @if($test->fasting_required)
                    <div class="flex items-center text-orange-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Fasting required
                    </div>
                    @endif
                </div>

                <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $test->formatted_price }}</div>
                        <div class="text-xs text-gray-500">inc. VAT</div>
                    </div>
                    <div class="space-x-2">
                        <a href="{{ route('patient.tests.show', $test) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            Details →
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-4 text-gray-600">No tests found matching your criteria</p>
            <a href="{{ route('patient.tests.index') }}" class="mt-4 inline-block text-blue-600 hover:underline">
                View all tests →
            </a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $tests->links() }}
    </div>

    {{-- CTA Section --}}
    <div class="mt-12 bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-8 text-white text-center">
        <h2 class="text-2xl font-bold mb-4">Ready to Order?</h2>
        <p class="text-blue-100 mb-6">Create an order and choose your collection date</p>
        <a href="{{ route('patient.orders.create') }}" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
            Create Order Now
        </a>
    </div>
</div>
@endsection