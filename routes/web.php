<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Voyager\UserController;
use App\Models\Customer;
use App\Models\Plex;
use App\Models\Demo;

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

Route::get('cron', function(){
    $total = 0;
    $plex = new Plex();
    $customers = Customer::where('status','active')->get();

    foreach ($customers as $data) {
        $server = $data->server;
        $plex->setServerCredentials($server->url, $server->token);
        if(isset($data->invited_id) and !empty($data->invited_id)){
            if(strtotime($data->date_to) < strtotime(date('Y-m-d'))){
               $plex->provider->removeFriend($data->invited_id);
               DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
               $total++; 
            }
        }
    }

    print "Total Cancelados: ".$total."\n";

    $total_demos = 0;

    $demos = Demo::all();
    foreach($demos as $demo){
        $server = $demo->server;
        $plex->setServerCredentials($server->url, $server->token);
        if(isset($demo->invited_id) and !empty($demo->invited_id)){
            if(strtotime($demo->end_date) < strtotime(date('Y-m-d H:i:s'))){
               $plex->provider->removeFriend($demo->invited_id);
               DB::table('demos')->where('id',$demo->id)->delete();
               $total_demos++; 
            }
        }
    }

    print "Total Demos Cancelados: ".$total_demos."\n";

});

Route::group(['prefix' => 'admin'], function () {
    Route::post('users/store',[UserController::class, 'custom_store'])->name('user_custom_store');
    Voyager::routes();
});
