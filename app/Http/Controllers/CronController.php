<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Plex;
use App\Models\Demo;
use DB;

class CronController extends Controller
{
    private $plex;

    public function __construct(){
        $this->plex = new Plex();
    }

    public function verifySubscriptions(){
            $total = 0;
            $total_no_invited_id = 0;
            $plex = $this->plex;

            $customers = Customer::where('status', 'active')
            ->where(function ($query) {
                $query->where('date_to', '<', date('Y-m-d'));
            })->limit(5)->get();


            foreach ($customers as $data) {
                $server = $data->server;
                if(!empty($data->server->url) and !empty($data->server->token)){
                    $plex->setServerCredentials($server->url, $server->token);
                    if(isset($data->invited_id) and !empty($data->invited_id)){
                        if(strtotime($data->date_to) < strtotime(date('Y-m-d'))){

                            if(setting('admin.only_remove_libraries')){
                                $this->plex->managerLibraries($data->id, "delete");
                            }else{
                                $plex->provider->removeFriend($data->invited_id);
                            }
                           
                           DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
                           $total++; 
                        }
                    }else{
                        DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
                        $total_no_invited_id++;
                    }
                }
            }

            print "Total Cancelados: ".$total."\n Total Sin Invited ID: ".$total_no_invited_id."\n";

            $total_demos = 0;
            $total_demos_no_invited_id = 0;

            $demos = Demo::where('end_date','<',now())->limit(5)->get();

            foreach($demos as $demo){
                $server = $demo->server;
                if(!empty($demo->server->url) and !empty($demo->server->token)){
                    $plex->setServerCredentials($server->url, $server->token);
                    if(isset($demo->invited_id) and !empty($demo->invited_id)){
                        if(strtotime($demo->end_date) < strtotime(date('Y-m-d H:i:s'))){
                           $plex->provider->removeFriend($demo->invited_id);
                           DB::table('demos')->where('id',$demo->id)->delete();
                           $total_demos++; 
                        }
                    }else{
                        DB::table('demos')->where('id',$demo->id)->delete();
                        $total_demos_no_invited_id++;
                    }
                }
            }

            print "Total Demos Cancelados: ".$total_demos."\n Total Demos Sin Invited ID: ".$total_demos_no_invited_id."\n";
    }

    public function verifySessions(){
        $this->plex->getSessionsAllServers();
    }

    public function updateServerData(){
        
    }
}
