<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ChargingChartController;
use App\Http\Controllers\BMEChartController;
use App\Http\Controllers\AdminController;


use App\Http\Controllers\GuestController;
use Illuminate\Support\Facades\Artisan;

Route::get('/run-weekly-charging-job/{secret}', function ($secret) {
    if ($secret !== env('CRON_SECRET')) {
        abort(403, 'Unauthorized');
    }
    Artisan::call('your:weeklyChargingJob'); // Create this command (explained below)
    return 'Weekly charging job executed.';
});
Route::get('/guest', [GuestController::class, 'index'])->name('guest.dashboard');
Route::get('/guest/group/{group}', [GuestController::class, 'showGroup'])->name('guest.group');
Route::get('/guest/device/{device}', [GuestController::class, 'showDevice'])->name('guest.device');


Route::get('/admin-dashboard', [AdminController::class, 'index'])
    ->middleware('auth', 'role:admin')
    ->name('admin.dashboard');

Route::get('/user-dashboard', [UserController::class, 'index'])
    ->middleware('auth', 'role:user')
    ->name('user.dashboard');




Route::get('/charging-chart', [ChargingChartController::class, 'index'])->name('charging.chart');
Route::post('/charging-scan/{barcode}', [ChargingChartController::class, 'scanOnce']);




Route::get('/device/{device_id}', [DeviceController::class, 'show'])->name('device.show');

// Edit Charging Chart
Route::get('/charging-chart/edit/{id}', [ChargingChartController::class, 'edit'])->name('charging.edit');
Route::post('/charging-chart/update/{id}', [ChargingChartController::class, 'update'])->name('charging.update');

// Edit BME Chart
Route::get('/bme-chart/edit/{id}', [BMEChartController::class, 'edit'])->name('bme.edit');
Route::post('/bme-chart/update/{id}', [BMEChartController::class, 'update'])->name('bme.update');


Route::middleware(['auth'])->group(function () {
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/user/device/{id}', [UserDashboardController::class, 'showDevice'])->name('user.device.show');
});






Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::post('/admin/store-device', [AdminController::class, 'storeDevice'])->name('admin.store_device');
Route::post('/admin/store-bme', [AdminController::class, 'storeBme'])->name('admin.store_bme');


Route::get('devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
Route::get('devices/{device}', [DeviceController::class, 'show'])->name('devices.show');




Route::post('/admin/store-charging', [ChargingChartController::class, 'store'])->name('admin.store_charging');
Route::get('/admin/charging-chart', [ChargingChartController::class, 'index'])->name('admin.charging_chart');


// Admin Dashboard
Route::get('/admin/dashboard', [DeviceController::class, 'dashboard'])->name('admin.dashboard');

// Device Management
Route::get('/devices/group/{group}', [DeviceController::class, 'showGroup'])->name('devices.group');
Route::get('/devices/{id}', [DeviceController::class, 'showDevice'])->name('devices.show');
Route::post('/devices/add', [DeviceController::class, 'store'])->name('devices.store');
Route::post('/devices/remove/{id}', [DeviceController::class, 'destroy'])->name('devices.destroy');

// BME Chart
Route::post('/bme/store', [BMEChartController::class, 'store'])->name('bme.store');
Route::get('/bme/edit/{id}', [BMEChartController::class, 'edit'])->name('bme.edit');
Route::post('/bme/update/{id}', [BMEChartController::class, 'update'])->name('bme.update');



Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Redirect users based on role after login
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('user.dashboard');
    })->name('dashboard');
});

// Admin Dashboard (Only Admins)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

// User Dashboard (All Authenticated Users)
Route::middleware(['auth'])->group(function () {
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
});



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
