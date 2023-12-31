<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Voyager\UserController;
use App\Http\Controllers\Voyager\CustomerController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\Voyager\DemoController;
use App\Http\Controllers\ApiController;

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

Route::get('cron',[CronController::class, 'verifySubscriptions']);
Route::get('verify-sessions',[CronController::class, 'verifySessions']);

Route::group(['prefix' => 'admin'], function () {
    Route::post('users/store',[UserController::class, 'custom_store'])->name('user_custom_store');
    Route::post('demos/convert-client',[DemoController::class, 'convert_client'])->name('convert_client');
    Route::put('customers/extend-membership',[CustomerController::class, 'extend_membership'])->name('extend_membership');
    Route::post('change-server',[ApiController::class, 'change_server'])->name('change_server');
    Route::post('update-libraries/{server_id?}',[ApiController::class, 'updateLibraries'])->name('update_libraries');
    Route::get('change-status/{customer_id}',[ApiController::class, 'change_status'])->name('change_status');
    Route::post("import-proxies",[ApiController::class, 'import_proxies'])->name('import_proxies');
    Route::post("convert-iphone",[ApiController::class, 'convert_iphone'])->name('convert_iphone');
    Route::get("remove-iphone/{customer_id}",[ApiController::class, 'remove_iphone'])->name('remove_iphone');
    Route::get("repair-account/{customer_id}",[ApiController::class, 'repair_account'])->name('repair_account');
    Route::post("change-password-plex-user",[ApiController::class, 'change_password_user_plex'])->name('change_password_user_plex');
    Route::post("change-user",[ApiController::class, 'change_user'])->name('change_user');
    Route::post("import-from-plex",[ApiController::class, 'import_from_plex'])->name('import_from_plex');
    Route::post("activate-device", [ApiController::class, 'activate_device'])->name('activate_device');

    Route::get("remove-libraries/{customer_id}",[ApiController::class, 'remove_libraries'])->name('remove_libraries');
    Route::get("add-libraries/{customer_id}",[ApiController::class, 'add_libraries'])->name('add_libraries');
    Voyager::routes();
});
