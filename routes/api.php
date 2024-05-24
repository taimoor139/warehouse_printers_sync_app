<?php

use App\Http\Controllers\GeneratedQRController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::group(['prefix' => 'v1/printing'], function(){
    Route::post('print_qrs', [GeneratedQRController::class, 'printQRs']);
    Route::post('print_qrs/stop', [GeneratedQRController::class, 'stopPrintingQRs']);
    Route::post('print_qrs/resume', [GeneratedQRController::class, 'resumePrintingQRs']);
    Route::post('print_qrs/replace_printer', [GeneratedQRController::class, 'replacePrinter']);
});
