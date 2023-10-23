<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Plex;
use App\Models\Demo;

class CronController extends Controller
{
    private $plex;

    public function __construct(){
        $this->plex = new Plex();
    }

    public function verifySubscriptions(){
            $total = 0;
            $plex = $this->plex;

            $customers = Customer::where('status', 'active')
            ->where(function ($query) {
                $query->where('date_to', '<', date('Y-m-d'));
            })->get();


            foreach ($customers as $data) {
                $server = $data->server;
                if(!empty($data->server->url) and !empty($data->server->token)){
                    $plex->setServerCredentials($server->url, $server->token);
                    if(isset($data->invited_id) and !empty($data->invited_id)){
                        if(strtotime($data->date_to) < strtotime(date('Y-m-d'))){
                           $plex->provider->removeFriend($data->invited_id);
                           DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
                           $total++; 
                        }
                    }
                }
            }

            print "Total Cancelados: ".$total."\n";

            $total_demos = 0;

            $demos = Demo::where('end_date','<',now())->get();
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
                    }
                }
            }

            print "Total Demos Cancelados: ".$total_demos."\n";
    }

    public function verifySessions(){
        $this->plex->getSessionsAllServers();
    }
}
