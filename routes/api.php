<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

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

Route::get('get-months-duration/{duration_id}',[ApiController::class, 'get_months_duration']);
Route::post('login-customer',[ApiController::class, 'loginCustomer']);
Route::post('get-libraries', [ApiController::class, 'getLibraries']);
Route::post('get-library', [ApiController::class, 'getLibrary']);
Route::post('search-library', [ApiController::class, 'searchLibrary']);