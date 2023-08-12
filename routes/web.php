<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Voyager\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('admin/login');
});

Route::get('/foo', function () {
    Artisan::call('storage:link');
});


Route::group(['prefix' => 'admin'], function () {
    Route::post('users/store',[UserController::class, 'custom_store'])->name('user_custom_store');
    Voyager::routes();
});
