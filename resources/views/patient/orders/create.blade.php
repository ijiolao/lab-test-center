{{-- resources/views/patient/orders/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Create New Order')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Create New Order</h1>
        <p class="text-gray-600 mb-8">Select the tests you'd like to order and choose your collection date.</p>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('patient.orders.store') }}" method="POST" id="orderForm">
            @csrf

            {{-- Test Selection --}}
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Select Tests</h2>
                    <p class="text-sm text-gray-600 mt-1">Choose one or more tests to include in your order</p>
                </div>
                <div class="p-6">
                    @foreach($tests as $category => $categoryTests)
                    <div class="mb-6 last:mb-0">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $category }}</h3>
                        <div class="space-y-3">
                            @foreach($categoryTests as $test)
                            <label class="flex items-start p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition">
                                <input type="checkbox" 
                                       name="tests[]" 
                                       value="{{ $test->id }}" 
                                       class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                       onchange="updateTotal()">
                                <div class="ml-4 flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ $test->name }}</div>
                                            <div class="text-sm text-gray-600 mt-1">{{ $test->description }}</div>
                                            <div class="flex items-center mt-2 text-xs text-gray-500 space-x-4">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    {{ $test->expected_completion }}
                                                </span>
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                                    </svg>
                                                    {{ ucfirst($test->specimen_type) }}
                                                </span>
                                                @if($test->fasting_required)
                                                <span class="flex items-center text-orange-600">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Fasting required
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="ml-4 text-right">
                                            <div class="text-lg font-bold text-gray-900">{{ $test->formatted_price }}</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Collection Details --}}
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Collection Details</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Collection Date <span class="text-red-600">*</span>
                            </label>
                            <input type="date" 
                                   name="collection_date" 
                                   required
                                   min="{{ today()->format('Y-m-d') }}"
                                   max="{{ today()->addMonths(3)->format('Y-m-d') }}"
                                   value="{{ old('collection_date') }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Preferred Time <span class="text-red-600">*</span>
                            </label>
                            <select name="collection_time" 
                                    required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select time...</option>
                                <option value="09:00:00">9:00 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="12:00:00">12:00 PM</option>
                                <option value="13:00:00">1:00 PM</option>
                                <option value="14:00:00">2:00 PM</option>
                                <option value="15:00:00">3:00 PM</option>
                                <option value="16:00:00">4:00 PM</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Special Instructions (Optional)
                        </label>
                        <textarea name="special_instructions" 
                                  rows="3"
                                  maxlength="500"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                                  placeholder="Any special requirements or notes...">{{ old('special_instructions') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Maximum 500 characters</p>
                    </div>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Order Summary</h2>
                </div>
                <div class="p-6">
                    <div id="selectedTests" class="mb-4">
                        <p class="text-gray-500 text-sm">No tests selected</p>
                    </div>
                    <div class="border-t border-gray-200 pt-4 space-y-2">
                        <div class="flex justify-between text-gray-700">
                            <span>Subtotal:</span>
                            <span id="subtotal">£0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-700">
                            <span>VAT (20%):</span>
                            <span id="tax">£0.00</span>
                        </div>
                        <div class="flex justify-between text-xl font-bold text-gray-900 pt-2 border-t border-gray-300">
                            <span>Total:</span>
                            <span id="total">£0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-between items-center">
                <a href="{{ route('patient.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit" 
                        id="submitBtn"
                        disabled
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                    Continue to Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const testPrices = {
        @foreach($tests as $categoryTests)
            @foreach($categoryTests as $test)
                {{ $test->id }}: {{ $test->price }},
            @endforeach
        @endforeach
    };

    const testNames = {
        @foreach($tests as $categoryTests)
            @foreach($categoryTests as $test)
                {{ $test->id }}: "{{ $test->name }}",
            @endforeach
        @endforeach
    };

    function updateTotal() {
        const checkboxes = document.querySelectorAll('input[name="tests[]"]:checked');
        const selectedTests = document.getElementById('selectedTests');
        const submitBtn = document.getElementById('submitBtn');
        
        let subtotal = 0;
        let testsHtml = '';

        if (checkboxes.length === 0) {
            selectedTests.innerHTML = '<p class="text-gray-500 text-sm">No tests selected</p>';
            submitBtn.disabled = true;
        } else {
            checkboxes.forEach(checkbox => {
                const testId = parseInt(checkbox.value);
                const price = testPrices[testId];
                subtotal += price;
                testsHtml += `<div class="flex justify-between text-sm mb-2">
                    <span>${testNames[testId]}</span>
                    <span>£${price.toFixed(2)}</span>
                </div>`;
            });
            selectedTests.innerHTML = testsHtml;
            submitBtn.disabled = false;
        }

        const tax = subtotal * 0.20;
        const total = subtotal + tax;

        document.getElementById('subtotal').textContent = '£' + subtotal.toFixed(2);
        document.getElementById('tax').textContent = '£' + tax.toFixed(2);
        document.getElementById('total').textContent = '£' + total.toFixed(2);
    }
</script>
@endsection