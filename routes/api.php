<?php

use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');



Route::prefix('v1')->group(function () {
    Route::get('wallets/{symbol}', [WalletController::class, 'getWalletsByCurrency']);
});
