<?php

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
//
Route::post('/set/webhook/{token}',
    [\App\Http\Controllers\TelegramController::class,'setWebhook'])->name('set.webhook');


Route::post('admin/set/webhook/{token}',
    [\App\Http\Controllers\TelegramAdminController::class,'setWebhook'])->name('set.webhook');

Route::post('support/set/webhook/{token}',
    [\App\Http\Controllers\TelegramSupportController::class,'setWebhook'])->name('set.webhook');
