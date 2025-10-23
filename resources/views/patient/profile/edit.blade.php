{{-- resources/views/patient/profile/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">My Profile</h1>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Sidebar Navigation --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow">
                    <nav class="space-y-1">
                        <a href="#personal" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 border-l-4 border-blue-600 bg-blue-50">
                            Personal Information
                        </a>
                        <a href="#security" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 border-l-4 border-transparent">
                            Security
                        </a>
                        <a href="#danger" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 border-l-4 border-transparent">
                            Danger Zone
                        </a>
                    </nav>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Personal Information --}}
                <div id="personal" class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Personal Information</h2>
                    </div>
                    <form action="{{ route('patient.profile.update') }}" method="POST" class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    First Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" 
                                       name="first_name" 
                                       value="{{ old('first_name', $user->first_name) }}"
                                       required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" 
                                       name="last_name" 
                                       value="{{ old('last_name', $user->last_name) }}"
                                       required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Email <span class="text-red-600">*</span>
                                </label>
                                <input type="email" 
                                       name="email" 
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone
                                </label>
                                <input type="tel" 
                                       name="phone" 
                                       value="{{ old('phone', $user->phone) }}"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Date of Birth <span class="text-red-600">*</span>
                                </label>
                                <input type="date" 
                                       name="date_of_birth" 
                                       value="{{ old('date_of_birth', $user->date_of_birth->format('Y-m-d')) }}"
                                       required
                                       max="{{ today()->format('Y-m-d') }}"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Gender
                                </label>
                                <select name="gender" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select...</option>
                                    <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                    <option value="prefer_not_to_say" {{ old('gender', $user->gender) == 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Address (Optional)</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1</label>
                                    <input type="text" 
                                           name="address_line1" 
                                           value="{{ old('address_line1', $user->address['line1'] ?? '') }}"
                                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                                    <input type="text" 
                                           name="address_line2" 
                                           value="{{ old('address_line2', $user->address['line2'] ?? '') }}"
                                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                        <input type="text" 
                                               name="address_city" 
                                               value="{{ old('address_city', $user->address['city'] ?? '') }}"
                                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Postcode</label>
                                        <input type="text" 
                                               name="address_postcode" 
                                               value="{{ old('address_postcode', $user->address['postcode'] ?? '') }}"
                                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                        <input type="text" 
                                               name="address_country" 
                                               value="{{ old('address_country', $user->address['country'] ?? 'GB') }}"
                                               maxlength="2"
                                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Security --}}
                <div id="security" class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Change Password</h2>
                    </div>
                    <form action="{{ route('patient.profile.update-password') }}" method="POST" class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Current Password <span class="text-red-600">*</span>
                                </label>
                                <input type="password" 
                                       name="current_password" 
                                       required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    New Password <span class="text-red-600">*</span>
                                </label>
                                <input type="password" 
                                       name="password" 
                                       required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirm New Password <span class="text-red-600">*</span>
                                </label>
                                <input type="password" 
                                       name="password_confirmation" 
                                       required
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Danger Zone --}}
                <div id="danger" class="bg-white rounded-lg shadow border border-red-200">
                    <div class="px-6 py-4 border-b border-red-200 bg-red-50">
                        <h2 class="text-xl font-semibold text-red-900">Danger Zone</h2>
                    </div>
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Delete Account</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Once you delete your account, there is no going back. Please be certain. 
                            You cannot delete your account if you have pending orders or results.
                        </p>
                        <button onclick="if(confirm('Are you sure you want to delete your account? This cannot be undone.')) document.getElementById('delete-form').style.display='block'" 
                                class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200">
                            Delete My Account
                        </button>

                        <form id="delete-form" 
                              action="{{ route('patient.profile.destroy') }}" 
                              method="POST" 
                              class="mt-4 hidden">
                            @csrf
                            @method('DELETE')
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <label class="block text-sm font-medium text-red-900 mb-2">
                                    Enter your password to confirm deletion
                                </label>
                                <input type="password" 
                                       name="password" 
                                       required
                                       class="w-full border border-red-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 mb-4">
                                <div class="flex space-x-3">
                                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                                        Confirm Deletion
                                    </button>
                                    <button type="button" onclick="document.getElementById('delete-form').style.display='none'" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection