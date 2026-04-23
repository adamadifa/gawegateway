<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MessageController;

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
    return redirect()->route('devices.index');
});

Route::prefix('devices')->name('devices.')->group(function () {
    Route::get('/', [DeviceController::class, 'index'])->name('index');
    Route::post('/store', [DeviceController::class, 'store'])->name('store');
    Route::get('/{uuid}/scan', [DeviceController::class, 'scan'])->name('scan');
    Route::delete('/{uuid}', [DeviceController::class, 'destroy'])->name('destroy');
});

Route::get('/messages/create', [DeviceController::class, 'createMessage'])->name('messages.create');
    Route::post('/messages/send', [DeviceController::class, 'sendMessage'])->name('messages.send');

Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
