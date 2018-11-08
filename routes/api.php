<?php

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

Route::post('/register', 'API\RegisterController@register')->name('registration');
Route::post('/topup/{account}', 'API\TopupController@topup')->name('topup');
Route::post('/transfer/{senderAccount}/{receiverAccount}', 'API\TransferController@transfer')->name('transfer');
Route::post('/exchange_rates/{iso}/usd', 'API\ExchangeRateController@store')->name('exchange_rate');
