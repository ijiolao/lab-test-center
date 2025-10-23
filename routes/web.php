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

// Public test catalog
Route::get('/tests', [Patient\TestController::class, 'index'])->name('tests.index');
Route::get('/tests/{test}', [Patient\TestController::class, 'show'])->name('tests.show');
Route::get('/api/tests/{test}/details', [Patient\TestController::class, 'details'])->name('tests.details');

// Authentication routes
Auth::routes(['verify' => true]);

/*
|--------------------------------------------------------------------------
| Patient Portal Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('patient')->name('patient.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [Patient\DashboardController::class, 'index'])->name('dashboard');
    
    // Orders
    Route::controller(Patient\OrderController::class)->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{order}', 'show')->name('show');
        Route::get('/{order}/payment', 'paymentForm')->name('payment');
        Route::post('/{order}/payment', 'processPayment')->name('payment.process');
        Route::post('/{order}/cancel', 'cancel')->name('cancel');
        Route::get('/{order}/receipt', 'downloadReceipt')->name('receipt');
    });
    
    // Results
    Route::controller(Patient\ResultController::class)->prefix('results')->name('results.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{result}', 'show')->name('show');
        Route::get('/{result}/download', 'download')->name('download');
    });
    
    // Profile
    Route::controller(Patient\ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::put('/password', 'updatePassword')->name('password.update');
        Route::delete('/', 'destroy')->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'can:view-admin-dashboard'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [Admin\DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    
    // Order Management
    Route::controller(Admin\OrderManagementController::class)->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{order}', 'show')->name('show');
        Route::put('/{order}', 'update')->name('update');
        Route::post('/{order}/collect', 'markCollected')->name('collect');
        Route::post('/{order}/print', 'printLabel')->name('print');
        Route::post('/{order}/submit-to-lab', 'submitToLab')->name('submit-to-lab');
        Route::post('/{order}/cancel', 'cancel')->name('cancel');
        Route::get('/search/patients', 'searchPatients')->name('search.patients');
        Route::get('/export', 'export')->name('export');
    });
    
    // Results Management
    Route::controller(Admin\ResultController::class)->prefix('results')->name('results.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{result}', 'show')->name('show');
        Route::get('/{result}/review-form', 'reviewForm')->name('review-form');
        Route::post('/{result}/review', 'review')->name('review');
        Route::get('/{result}/download', 'download')->name('download');
        Route::post('/{result}/regenerate-pdf', 'regeneratePdf')->name('regenerate-pdf');
        Route::get('/{result}/raw', 'viewRaw')->name('raw');
        Route::post('/{result}/notify', 'notifyPatient')->name('notify');
    });

    // Patient Records Management
    Route::controller(Admin\RecordController::class)->prefix('records')->name('records.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{patient}', 'show')->name('show')->where('patient', '[0-9]+');
        Route::get('/{patient}/edit', 'edit')->name('edit');
        Route::put('/{patient}', 'update')->name('update');
        Route::get('/{patient}/orders', 'orders')->name('orders');
        Route::get('/{patient}/results', 'results')->name('results');
        Route::post('/{patient}/toggle-active', 'toggleActive')->name('toggle-active');
        Route::get('/{patient}/reset-password', 'resetPasswordForm')->name('reset-password-form');
        Route::post('/{patient}/reset-password', 'resetPassword')->name('reset-password');
        Route::get('/{patient}/export', 'exportData')->name('export');
        Route::get('/{patient}/merge', 'mergeForm')->name('merge-form');
        Route::post('/{patient}/merge', 'merge')->name('merge');
    });
    
    // Lab Partners (Admin only)
    Route::middleware('can:manage-lab-partners')->group(function () {

        Route::get('/{result}/download', 'download')->name('download');
        Route::post('/{result}/regenerate-pdf', 'regeneratePdf')->name('regenerate-pdf');
        Route::get('/{result}/raw', 'viewRaw')->name('raw');
        Route::post('/{result}/notify', 'notifyPatient')->name('notify');
    });
    
    // Lab Partners (Admin only)
    Route::middleware('can:manage-lab-partners')->group(function () {
        Route::controller(Admin\LabPartnerController::class)->prefix('lab-partners')->name('lab-partners.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{labPartner}', 'show')->name('show');
            Route::get('/{labPartner}/edit', 'edit')->name('edit');
            Route::put('/{labPartner}', 'update')->name('update');
            Route::delete('/{labPartner}', 'destroy')->name('destroy');
            Route::post('/{labPartner}/test-connection', 'testConnection')->name('test-connection');
            Route::post('/{labPartner}/fetch-catalog', 'fetchTestCatalog')->name('fetch-catalog');
            Route::post('/{labPartner}/toggle-active', 'toggleActive')->name('toggle-active');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
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
