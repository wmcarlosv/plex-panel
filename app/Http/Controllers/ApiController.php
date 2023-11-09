<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Duration;
use App\Models\Customer;
use App\Models\Plex;
use App\Models\Server;
use Havenstd06\LaravelPlex\Classes\FriendRestrictionsSettings;
use DB;
use File;
use App\Models\Proxy;

class ApiController extends Controller
{
    private $plex;

    public function __construct(){
        $this->plex = new Plex();
    }

    public function get_months_duration($duration_id){
        $data = Duration::findorfail($duration_id);
        $date = $this->addMonthsToCurrentDate($data->months);
        return response()->json(['new_date'=>$date]);
    }

    public function addMonthsToCurrentDate($monthsToAdd) {
        // Get the current date
        $startDate = new \DateTime();

        // Add the specified number of months
        $startDate->modify("+$monthsToAdd months");

        // Format the updated date as YYYY-MM-DD
        $updatedDate = $startDate->format("Y-m-d");

        return $updatedDate;
    }

    public function get_extend_months_duration($actualToDate, $monthsToAdd) {
        // Get the current date
        $startDate = new \DateTime($actualToDate);
        // Add the specified number of months
        $startDate->modify("+$monthsToAdd months");

        // Format the updated date as YYYY-MM-DD
        $updatedDate = $startDate->format("Y-m-d");

        return response()->json(['date'=>$updatedDate]);
    }

    public function loginCustomer(Request $request){
        $email = $request->email;
        $password = $request->password;
        $data = [];
        $customer = Customer::where('email',$email)->where('password',$password)->first();

        if($customer){
            if($customer->status == 'active'){
                $data = ['message'=>'Bienvenido!!', 'success'=>true, 'data'=>$customer];
            }else{
                $data = ['message'=>'Tu subscripcion esta inactiva, por favor contacta con tu vendedor!!', 'success'=>false];
            }
        }else{
            $data = ['message'=>'Email o Password Incorrectos!!', 'success'=>false];
        }

        return response()->json($data);
    }

    public function getLibraries(Request $request){
        $server_id = $request->server_id;
        $this->activeServer($server_id);
        return response()->json(['response'=>$this->plex->provider->getLibraries()['MediaContainer']['Directory'], 'server_data'=>$this->plex->serverData]);
    }

    public function getLibrary(Request $request){
        $server_id = $request->server_id;
        $library_key = $request->library_key;
        $this->activeServer($server_id);
        return response()->json(['response'=>$this->plex->provider->getLibrary($library_key)['MediaContainer']['Metadata'], 'server_data'=>$this->plex->serverData]);
    }

    public function activeServer($server_id){
        $server = Server::findorfail($server_id);
        $this->plex->setServerCredentials($server->url, $server->token);
    }

    public function searchLibrary(Request $request){
        $server_id = $request->server_id;
        $q = $request->q;
        $this->activeServer($server_id);
        return response()->json(['response'=>$this->plex->provider->searchLibrary($q, 20), 'server_data'=>$this->plex->serverData]);
    }

    public function change_server(Request $request){
        $data = [];
        $customer = Customer::findorfail($request->id);
        $server_to = Server::findorfail($request->server_id);

        if(isset($customer->invited_id) and !empty($customer->invited_id)){
            /*Remove Before Server*/
            $server = Server::findorfail($customer->server_id);
            $this->plex->setServerCredentials($server->url, $server->token);
            $this->plex->provider->removeFriend($customer->invited_id);

            /* Add to New Server*/
            $this->plex->setServerCredentials($server_to->url, $server_to->token);
            $plex_data = $this->plex->provider->getAccounts();
            if(!is_array($plex_data)){
                $data = [
                    'success'=>false,
                    'message'=>"El servidor a donde quieres mover al cliente, tiene problemas con sus credenciales, por favor verificalas y vuelve a intentar!!"
                ];   
            }else{
                $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();
                if(empty($the_data[0]->invited_id)){
                    $data = [
                        'success'=>false,
                        'message'=>"Ocurrio un error al momento de realizar el cambio de servidor, por favor utilice la opcion de reparar cuenta para solventar este problema!!"
                        ];
                }else{
                    if(!empty($server_to->limit_accounts)){
                        $tope = (intval($server_to->limit_accounts)-intval($server_to->customers->count()));
                        if($tope == 0){
                            /*$server_to->status = 0;
                            $server_to->save();*/
                            DB::table('servers')->where('id',$server->id)->update([
                                'status'=>0
                            ]);
                        }
                    }

                    $data = [
                        'success'=>true,
                        'message'=>"El cambio de servidor se ha realizado con exito, esta pagina se recargara en breve!!"
                    ];

                    $customer->server_id = $server_to->id;
                    $customer->save();
                }
            }
        }else{
            $data = [
                'success'=>false,
                'message'=>"El cliente no estar correctamente vinculado con plex, por favor verifica bien los datos!!"
            ];
        }

        return response()->json($data);
    }

    public function updateLibraries(Request $request,$server_id){
        $data = [];
        $librarySectionIds = [];
        $server = Server::findorfail($server_id);

        $this->plex->setServerCredentials($server->url, $server->token);
        $libraries = $request->libraries;

        foreach($libraries as $library){
            $librarySectionIds[] = (int) $library;
        }

        $cont = 0;
        foreach($server->customers as $customer){
            $this->plex->provider->updateFriendLibraries($customer->invited_id, $librarySectionIds);
            $data_user = $this->plex->loginInPlex($customer->email, $customer->password);
            #$this->plex->resetCustomization($data_user['user']['authToken'], uniqid());
            $cont++;
        }



        $data = [
            'success'=>true,
            'message'=>"Se les actualizo la librerias a ".$cont.", Clientes"
        ];

        return response()->json($data);
    }

    public function change_status($customer_id){
        $customer = Customer::findorfail($customer_id);
        $server = Server::findorfail($customer->server_id);
        $data = [];
        if($customer->status == "active"){
            $this->plex->setServerCredentials($server->url, $server->token);
            $this->plex->provider->removeFriend($customer->invited_id);
            $customer->plex_user_name = null;
            $customer->plex_user_token = null;
            $customer->invited_id = null;
            $customer->status = "inactive";
            $customer->save();
            $data = [
                'success'=>true,
                'message'=>'Cliente Inhabhilitado con Exito!!'
            ];
        }else{
            $this->plex->setServerCredentials($server->url, $server->token);
            $plex_data = $this->plex->provider->getAccounts();
            if(!is_array($plex_data)){
                $data = [
                    'success'=>false,
                    'message'=>"El servidor a donde quieres mover al cliente, tiene problemas con sus credenciales, por favor verificalas y vuelve a intentar!!"
                ];   
            }else{
                $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();

                if(empty($the_data[0]->invited_id)){
                    $data = [
                        'success'=>false,
                        'message'=>"Ocurrio un error al momento de realizar el cambio de servidor, por favor utilice la opcion de reparar cuenta para solventar este problema!!"
                        ];
                }else{
                    if(!empty($server->limit_accounts)){
                        $tope = (intval($server->limit_accounts)-intval($server->customers->count()));
                        if($tope == 0){
                            /*$server->status = 0;
                            $server->save();*/
                            DB::table('servers')->where('id',$server->id)->update([
                                'status'=>0
                            ]);
                        }
                    }

                    $data = [
                        'success'=>true,
                        'message'=>"Cliente Habilitado con Exito!"
                    ];

                    $customer->status = "active";
                    $customer->server_id = $server->id;
                    $customer->save();
                }
            }
        }

        return response()->json($data);
    }

    public function import_proxies(Request $request){
        $proxies = [];
        $file = $request->file('proxies');
        $content = $file->get();
        $cont = 0;
        $message = "";
        foreach(explode(PHP_EOL, $content) as $proxy) {
            $arr_proxy = explode(':', $proxy);
            if(!empty($arr_proxy[0]) and !empty($arr_proxy[1])){
                $ip = $arr_proxy[0];
                $port = str_replace("\r","",$arr_proxy[1]);
                $verify = Proxy::where("ip",$ip)->where("port",$port)->first();
                if(!$verify){
                    $proxy = new Proxy();
                    $proxy->ip = $ip;
                    $proxy->port = $port;
                    $proxy->save();
                    $cont++;
                }
            }
        }

        $redirect = redirect()->route("voyager.proxies.index");

        if($cont == 0){
            $message = "No se importo ningun proxy Nuevo!!";
        }else{
            $message = "Se importaron: ".$cont.", proxies de manera exitosa!!";
        }

        return $redirect->with([
                'message'    => $message,
                'alert-type' => 'success',
            ]);
    }

    public function convert_iphone(Request $request){
        $customer = Customer::findorfail($request->pp_customer_id);
        $server = Server::findorfail($request->server_pp_id);
        $pin = $request->pin;
        
        if($customer->server_id != $server->id){
            //Eliminamos del server anterior
            $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
            $this->plex->provider->removeFriend($customer->invited_id);
            //Insertamos en el server Nuevo
            $this->plex->setServerCredentials($server->url, $server->token);
            $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
        }

        $this->plex->createHomeUser($server, $customer, $pin);

        $customer->pin = $pin;
        $customer->save();

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'Cuenta Convertida a Iphone de Manera Exitosa!!',
            'alert-type'=>'success'
        ]);
    }

    public function remove_iphone($customer_id){
        $customer = Customer::findorfail($customer_id);
        $userPin = $this->plex->loginInPlex($customer->email, $customer->password);
        $this->plex->removeHomeUserPin($userPin, $customer->pin);
        $this->plex->removeHomeUser($customer);
        $customer->pin = null;
        $customer->save();

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'Cuenta Removida de Iphone de Manera Exitosa!!',
            'alert-type'=>'success'
        ]);
    }
}