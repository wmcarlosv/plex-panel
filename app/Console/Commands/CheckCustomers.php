<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Auth;
use DB;
use App\Models\Plex;
use App\Models\Server;
use App\Models\Demo;

class CheckCustomers extends Command
{

    private $plex;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica los clientes y en tal caso de que su subscripcion este fuera del rango de fecha, la cancela';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->plex = new Plex();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $total = 0;
        $customers = Customer::where('status', 'active')
            ->where(function ($query) {
                $query->where('date_to', '<', date('Y-m-d'));
            })->limit(5)->get();

        foreach ($customers as $data) {
            $server = Server::findorfail($data->server_id);
            $this->plex->setServerCredentials($server->url, $server->token);
            if(isset($data->invited_id) and !empty($data->invited_id)){
                if(strtotime($data->date_to) < strtotime(date('Y-m-d'))){
                   $this->plex->provider->removeFriend($data->invited_id);
                   DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
                   $total++; 
                }
            }
        }

        print "Total Cancelados: ".$total."\n";

        $total_demos = 0;

        $demos = Demo::where('end_date','<',now())->limit(5)->get();
        foreach($demos as $demo){
            $server = Server::findorfail($demo->server_id);
            $this->plex->setServerCredentials($server->url, $server->token);
            if(isset($demo->invited_id) and !empty($demo->invited_id)){
                if(strtotime($demo->end_date) < strtotime(date('Y-m-d H:i:s'))){
                   $this->plex->provider->removeFriend($demo->invited_id);
                   DB::table('demos')->where('id',$demo->id)->delete();
                   $total_demos++; 
                }
            }
        }

        print "Total Demos Cancelados: ".$total_demos."\n";

        return 0;
    }
}