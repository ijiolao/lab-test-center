{{-- resources/views/admin/lab-partners/show.blade.php --}}
@extends('layouts.admin')

@section('title', $labPartner->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <a href="{{ route('admin.lab-partners.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">
            ← Back to Lab Partners
        </a>

        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $labPartner->name }}</h1>
                <p class="text-gray-600 mt-1">Code: <span class="font-mono">{{ $labPartner->code }}</span></p>
            </div>
            <div class="flex space-x-3">
                <button onclick="testConnection()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    Test Connection
                </button>
                <a href="{{ route('admin.lab-partners.edit', $labPartner) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Edit
                </a>
            </div>
        </div>

        {{-- Connection Status --}}
        @if($connectionStatus !== null)
            <div class="mb-6 p-4 rounded-lg {{ $connectionStatus ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                <div class="flex items-center">
                    @if($connectionStatus)
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-green-800 font-medium">Connection Successful</span>
                    @else
                        <svg class="w-6 h-6 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-red-800 font-medium">Connection Failed</span>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Statistics --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Statistics</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Total Submissions</div>
                                <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_submissions'] }}</div>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Success Rate</div>
                                <div class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['success_rate'] }}%</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Avg Turnaround</div>
                                <div class="text-2xl font-bold text-green-600 mt-1">
                                    @if($stats['avg_turnaround'])
                                        {{ $stats['avg_turnaround'] }}h
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Pending Submissions</div>
                                <div class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending_submissions'] }}</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="text-sm text-gray-600">Failed Submissions</div>
                                <div class="text-2xl font-bold text-red-600 mt-1">{{ $stats['failed_submissions'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Configuration Details --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Configuration</h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm text-gray-600">Connection Type</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $labPartner->connection_type_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-600">Priority</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ $labPartner->priority }}</dd>
                            </div>
                            @if($labPartner->api_endpoint)
                            <div class="md:col-span-2">
                                <dt class="text-sm text-gray-600">API Endpoint</dt>
                                <dd class="mt-1 font-medium text-gray-900 break-all">{{ $labPartner->api_endpoint }}</dd>
                            </div>
                            @endif
                            @if($labPartner->auth_type)
                            <div>
                                <dt class="text-sm text-gray-600">Authentication</dt>
                                <dd class="mt-1 font-medium text-gray-900">{{ strtoupper(str_replace('_', ' ', $labPartner->auth_type)) }}</dd>
                            </div>
                            @endif
                        </dl>

                        @if($labPartner->credentials)
                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Additional Credentials</h3>
                            <pre class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs overflow-x-auto">{{ json_encode($labPartner->credentials, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        @endif

                        @if($labPartner->supported_tests)
                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Supported Tests</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($labPartner->supported_tests as $testCode)
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                                        {{ $testCode }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($labPartner->field_mapping)
                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Field Mapping</h3>
                            <pre class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs overflow-x-auto">{{ json_encode($labPartner->field_mapping, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Recent Submissions --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Recent Submissions</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentSubmissions as $submission)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('admin.orders.show', $submission->order) }}" class="text-blue-600 hover:underline">
                                            {{ $submission->order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $submission->order->user->full_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($submission->submitted_at)
                                            {{ $submission->submitted_at->format('d/m/Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-{{ $submission->status_color }}-100 text-{{ $submission->status_color }}-800">
                                            {{ $submission->status_display_name }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        No submissions yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Status Card --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Status</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <div class="text-sm text-gray-600">Status</div>
                            <div class="mt-1">
                                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $labPartner->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $labPartner->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Created</div>
                            <div class="text-sm text-gray-900 mt-1">{{ $labPartner->created_at->format('d/m/Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Last Updated</div>
                            <div class="text-sm text-gray-900 mt-1">{{ $labPartner->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Actions</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <button onclick="testConnection()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center">
                            Test Connection
                        </button>

                        <button onclick="fetchTestCatalog()" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-center">
                            Fetch Test Catalog
                        </button>

                        <form action="{{ route('admin.lab-partners.toggle-active', $labPartner) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full {{ $labPartner->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} px-4 py-2 rounded-lg text-center">
                                {{ $labPartner->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        <a href="{{ route('admin.lab-partners.edit', $labPartner) }}" class="block w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 text-center">
                            Edit Configuration
                        </a>

                        @if($labPartner->labSubmissions()->withTrashed()->count() === 0)
                        <form action="{{ route('admin.lab-partners.destroy', $labPartner) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this lab partner?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-center">
                                Delete Lab Partner
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testConnection() {
    if (confirm('Test connection to this lab partner?')) {
        fetch(`/admin/lab-partners/{{ $labPartner->id }}/test-connection`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Connection successful!');
                location.reload();
            } else {
                alert('✗ Connection failed: ' + data.message);
            }
        })
        .catch(error => {
            alert('✗ Connection test failed: ' + error);
        });
    }
}

function fetchTestCatalog() {
    if (confirm('Fetch test catalog from this lab partner?')) {
        fetch(`/admin/lab-partners/{{ $labPartner->id }}/fetch-test-catalog`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✓ Successfully fetched ${data.count} test(s)!\n\nTests: ${data.tests.join(', ')}`);
            } else {
                alert('✗ Failed to fetch test catalog: ' + data.message);
            }
        })
        .catch(error => {
            alert('✗ Failed to fetch test catalog: ' + error);
        });
    }
}
</script>
@endsection