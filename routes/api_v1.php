<?php

use Bookkeeper\Controllers\AuthController;
use Bookkeeper\Controllers\FinancialRecordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    Route::get('/financialRecords', [FinancialRecordController::class, 'index'])
        ->name('financialRecords.index');
    Route::get('/financialRecords/{recordId}', [FinancialRecordController::class, 'show'])
        ->name('financialRecords.show');
    Route::post('/financialRecords', [FinancialRecordController::class, 'store'])
        ->name('financialRecords.store');
    Route::put('/financialRecords/{recordId}', [FinancialRecordController::class, 'update'])
        ->name('financialRecords.update');
    Route::delete('/financialRecords/{recordId}', [FinancialRecordController::class, 'destroy'])
        ->name('financialRecords.destroy');
});
