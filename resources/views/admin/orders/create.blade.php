{{-- resources/views/admin/orders/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create Manual Order')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <a href="{{ route('admin.orders.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Orders
        </a>

        <div class="bg-white rounded-2xl shadow">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create Manual Order</h1>
                    <p class="text-gray-600 text-sm mt-1">Capture patient information and select the required tests.</p>
                </div>
                <div class="px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-600">Admin action</div>
            </div>

            <form method="POST" action="{{ route('admin.orders.store') }}" class="p-6 space-y-8">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Existing Patient</h2>
                        <p class="text-sm text-gray-500">Provide an optional patient ID to attach the order to an existing profile.</p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Patient ID</label>
                            <input type="number" name="user_id" value="{{ old('user_id') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="Search ID">
                            <p class="text-xs text-gray-500 mt-1">Leave blank to create a new patient record.</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900">Contact Details</h2>
                        <div class="grid grid-cols-1 gap-3">
                            <input type="text" name="first_name" value="{{ old('first_name') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="First name">
                            <input type="text" name="last_name" value="{{ old('last_name') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="Last name">
                            <input type="email" name="email" value="{{ old('email') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="Email address">
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="Phone number">
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="border border-gray-300 rounded-lg px-4 py-3">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                        <select name="gender" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select gender</option>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="prefer_not_to_say" {{ old('gender') === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Collection Date</label>
                            <input type="date" name="collection_date" value="{{ old('collection_date', now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Collection Time</label>
                            <input type="time" name="collection_time" value="{{ old('collection_time') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Collection Location</label>
                    <input type="text" name="collection_location" value="{{ old('collection_location') }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" placeholder="Clinic or address">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                    <textarea name="special_instructions" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-3" placeholder="Optional notes for the phlebotomy team">{{ old('special_instructions') }}</textarea>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-3">Select Tests</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($tests as $category => $categoryTests)
                            <div class="border border-gray-200 rounded-xl p-4">
                                <p class="text-sm font-semibold text-gray-700 mb-3">{{ $category }}</p>
                                <div class="space-y-2 max-h-64 overflow-y-auto pr-2">
                                    @foreach($categoryTests as $test)
                                        <label class="flex items-start space-x-3">
                                            <input type="checkbox" name="tests[]" value="{{ $test->id }}" class="mt-1" {{ in_array($test->id, old('tests', [])) ? 'checked' : '' }}>
                                            <span>
                                                <span class="font-medium text-gray-900">{{ $test->name }}</span>
                                                <span class="block text-xs text-gray-500">{{ $test->code }} • {{ $test->formatted_price }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">
                        Create Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
