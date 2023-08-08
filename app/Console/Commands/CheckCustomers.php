<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Auth;
use DB;
use Havenstd06\LaravelPlex\Services\Plex as PlexClient;

class CheckCustomers extends Command
{

    private $provider;
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
        $this->provider = new PlexClient;
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
        $customers = Customer::where('status','active')->get();

        foreach ($customers as $data) {
            if(isset($data->invited_id) and !empty($data->invited_id)){
                if(strtotime($data->date_to) < strtotime(date('Y-m-d'))){
                   $this->provider->removeFriend($data->invited_id);
                   DB::table('customers')->where('id',$data->id)->update(['status'=>'inactive']);
                   $total++; 
                }
            }
        }
        print "Total Cancelados: ".$total."\n";

        return 0;
    }
}