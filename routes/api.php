<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Api\ExternalGatewayController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Internal Callbacks from Node.js
Route::post('/update-status', [DeviceController::class, 'updateStatus']);
Route::post('/update-qr', [DeviceController::class, 'updateQr']);
Route::post('/incoming-message', [MessageController::class, 'storeInbound']);

/*
|--------------------------------------------------------------------------
| Compatibility API for External Projects (e.g. presensigpsv2)
|--------------------------------------------------------------------------
*/
Route::controller(ExternalGatewayController::class)->group(function () {
    Route::post('/create-device', 'createDevice');
    Route::post('/generate-qr', 'generateQR');
    Route::post('/info-device', 'infoDevice');
    Route::post('/send-message', 'sendMessage');
    Route::post('/logout-device', 'logoutDevice');
    Route::post('/fetch-contact-group', 'fetchGroups');
});
