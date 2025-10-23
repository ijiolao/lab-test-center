{{-- resources/views/admin/lab-partners/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Lab Partner')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('admin.lab-partners.show', $labPartner) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Lab Partner
        </a>

        <h1 class="text-3xl font-bold text-gray-900 mb-8">Edit Lab Partner: {{ $labPartner->name }}</h1>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.lab-partners.update', $labPartner) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Basic Information --}}
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Basic Information</h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Lab Partner Name <span class="text-red-600">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   value="{{ old('name', $labPartner->name) }}"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Code <span class="text-red-600">*</span>
                            </label>
                            <input type="text" 
                                   name="code" 
                                   value="{{ old('code', $labPartner->code) }}"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Lowercase, no spaces. Used for adapter matching.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Connection Type <span class="text-red-600">*</span>
                            </label>
                            <select name="connection_type" 
                                    required
                                    id="connectionType"
                                    onchange="toggleConnectionFields()"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                @foreach($connectionTypes as $type)
                                    <option value="{{ $type }}" {{ old('connection_type', $labPartner->connection_type) == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Priority <span class="text-red-600">*</span>
                            </label>
                            <input type="number" 
                                   name="priority" 
                                   value="{{ old('priority', $labPartner->priority) }}"
                                   required
                                   min="0"
                                   max="100"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Higher priority partners are used first (0-100)</p>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1"
                                   {{ old('is_active', $labPartner->is_active) ? 'checked' : '' }}
                                   class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-3 text-gray-700">Active (enable submissions to this partner)</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Connection Details --}}
            <div id="connectionDetails" class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Connection Details</h2>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            API Endpoint / Host
                        </label>
                        <input type="url" 
                               name="api_endpoint" 
                               value="{{ old('api_endpoint', $labPartner->api_endpoint) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Authentication Type
                        </label>
                        <select name="auth_type" 
                                id="authType"
                                onchange="toggleAuthFields()"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select...</option>
                            @foreach($authTypes as $type)
                                <option value="{{ $type }}" {{ old('auth_type', $labPartner->auth_type) == $type ? 'selected' : '' }}>
                                    {{ strtoupper(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="authFields" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                API Key
                            </label>
                            <input type="text" 
                                   name="api_key" 
                                   value="{{ old('api_key') }}"
                                   placeholder="Leave empty to keep existing"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Leave empty to keep existing value</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                API Secret
                            </label>
                            <input type="password" 
                                   name="api_secret" 
                                   value="{{ old('api_secret') }}"
                                   placeholder="Leave empty to keep existing"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Leave empty to keep existing value</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Credentials (JSON)
                        </label>
                        <textarea name="credentials" 
                                  rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-sm">{{ old('credentials', $labPartner->credentials_json) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Optional: Additional configuration as JSON</p>
                    </div>
                </div>
            </div>

            {{-- Test Configuration --}}
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Test Configuration</h2>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Supported Tests (JSON Array)
                        </label>
                        <textarea name="supported_tests" 
                                  rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-sm">{{ old('supported_tests', $labPartner->supported_tests_json) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Leave empty to support all tests</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Field Mapping (JSON)
                        </label>
                        <textarea name="field_mapping" 
                                  rows="6"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-sm">{{ old('field_mapping', $labPartner->field_mapping_json) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Optional: Map our fields to partner's fields</p>
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-between items-center">
                <a href="{{ route('admin.lab-partners.show', $labPartner) }}" class="text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700">
                    Update Lab Partner
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleConnectionFields() {
    const type = document.getElementById('connectionType').value;
    const detailsDiv = document.getElementById('connectionDetails');
    
    if (type && type !== 'manual') {
        detailsDiv.classList.remove('hidden');
    } else {
        detailsDiv.classList.add('hidden');
    }
}

function toggleAuthFields() {
    const authType = document.getElementById('authType').value;
    const authFields = document.getElementById('authFields');
    
    if (authType && authType !== 'none') {
        authFields.classList.remove('hidden');
    } else {
        authFields.classList.add('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleConnectionFields();
    toggleAuthFields();
});
</script>
@endsection