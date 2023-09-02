<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Voyager\UserController;
use App\Console\Commands\CheckCustomers;

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

if (config('app.debug')) {

    Route::get('/dev/{command}', function ($command) {
        Artisan::call($command);
        $output = Artisan::output();
        dd($output);
    });
}

Route::get('/clear-accounts', function(){
    $c = new CheckCustomers();
    $c->handle();
});

Route::group(['prefix' => 'admin'], function () {
    Route::post('users/store',[UserController::class, 'custom_store'])->name('user_custom_store');
    Voyager::routes();
});
