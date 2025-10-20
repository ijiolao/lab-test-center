<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patient;
use App\Http\Controllers\Admin;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Lab partner webhooks
Route::post('/webhooks/lab-results', 
    [Api\LabWebhookController::class, 'receiveResults'])
    ->middleware('verify.webhook.signature');

Route::post('/webhooks/lab-hl7', 
    [Api\LabWebhookController::class, 'receiveHL7'])
    ->middleware('verify.webhook.signature');

// Stripe webhooks
Route::post('/webhooks/stripe', 
    [Api\PaymentWebhookController::class, 'handle']);
