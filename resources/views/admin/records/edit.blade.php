{{-- resources/views/admin/records/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Patient')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('admin.records.show', $patient) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Profile
        </a>

        <div class="bg-white rounded-2xl shadow">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-3xl font-bold text-gray-900">Update Patient Details</h1>
            </div>
            <form method="POST" action="{{ route('admin.records.update', $patient) }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $patient->first_name) }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $patient->last_name) }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" value="{{ old('email', $patient->email) }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $patient->phone) }}" class="w-full border border-gray-300 rounded-lg px-4 py-3">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($patient->date_of_birth)->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-4 py-3" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                        <select name="gender" class="w-full border border-gray-300 rounded-lg px-4 py-3">
                            <option value="">Select</option>
                            @foreach(['male','female','other','prefer_not_to_say'] as $option)
                                <option value="{{ $option }}" {{ old('gender', $patient->gender) === $option ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $option)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="address_line1" value="{{ old('address_line1', $patient->address['line1'] ?? '') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="Line 1">
                        <input type="text" name="address_line2" value="{{ old('address_line2', $patient->address['line2'] ?? '') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="Line 2">
                        <input type="text" name="address_city" value="{{ old('address_city', $patient->address['city'] ?? '') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="City">
                        <input type="text" name="address_postcode" value="{{ old('address_postcode', $patient->address['postcode'] ?? '') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="Postcode">
                        <input type="text" name="address_country" value="{{ old('address_country', $patient->address['country'] ?? 'GB') }}" class="border border-gray-300 rounded-lg px-4 py-3" placeholder="Country">
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="is_active" value="1" class="h-4 w-4 text-blue-600" {{ old('is_active', $patient->is_active) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700">Account is active</span>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.records.show', $patient) }}" class="px-6 py-3 rounded-lg border border-gray-300">Cancel</a>
                    <button type="submit" class="px-6 py-3 rounded-lg bg-blue-600 text-white font-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
