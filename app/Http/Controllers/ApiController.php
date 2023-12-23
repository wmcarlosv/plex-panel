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
use Session;
use App\Models\User;
use App\Models\Movement;
use App\Models\Demo;
use Auth;

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

        $this->plex->setServerCredentials($server_to->url, $server_to->token);

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);

            if($user['response']['status'] != "Valid user"){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }

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
                if($customer->password != "#5inCl4ve#"){
                    $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                }else{
                    $this->plex->createPlexAccountNoPasswordNoCredit($customer->email, $customer);
                }
                
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
        $cont = 0;
        foreach($libraries as $library){
            $this->plex->refreshLibraries($library);
            $cont++;
        }

        $data = [
            'success'=>true,
            'message'=>"Se Actualizaron: ".$cont." Librerias!!"
        ];

        return response()->json($data);
    }

    public function change_status($customer_id){
        $customer = Customer::findorfail($customer_id);
        $server = Server::findorfail($customer->server_id);
        $data = [];

        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);
            if(is_array($user)){
                if($user['response']['status'] != "Valid user"){
                    $data =[
                        'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                        'success'=>false
                    ];
                    return response()->json($data);
                }
            }else{
                $data = [
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
            
        }

        if($customer->status == "active"){
            $this->plex->setServerCredentials($server->url, $server->token);
            $this->plex->provider->removeFriend($customer->invited_id);
            $customer->plex_user_name = null;
            $customer->plex_user_token = null;
            $customer->invited_id = null;
            $customer->status = "inactive";
            $customer->save();

            $this->plex->addMovement("Inactivacion de Cuenta",$customer);

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

                if($customer->password != "#5inCl4ve#"){
                    $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                }else{
                    $this->plex->createPlexAccountNoPasswordNoCredit($customer->email, $customer);
                }
                
                $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();

                if(empty($the_data[0]->invited_id)){
                    $data = [
                        'success'=>false,
                        'message'=>"Ocurrio un error al momento de habilitar la cuenta, por favor utilice la opcion de reparar cuenta para solventar este problema!!"
                        ];
                }else{
                    if(!empty($server->limit_accounts)){
                        $tope = (intval($server->limit_accounts)-intval($server->customers->count()));
                        if($tope == 0){
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

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);

            if($user['response']['status'] != "Valid user"){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }
        
        $isValid = $this->plex->createHomeUser($server, $customer, $pin);

        if(!$isValid){
            return redirect()->route("voyager.customers.index")->with([
                'message'=>'Ocurrio un error a tratar de convertir esta cuenta en iphone, posiblemente sobrepasate el limite de 15 usuarios como usuarios administrados!!',
                'alert-type'=>'error'
            ]);
        }

        if($customer->server_id != $server->id){
            //Eliminamos del server anterior
            $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
            $this->plex->provider->removeFriend($customer->invited_id);
            //Insertamos en el server Nuevo
            $this->plex->setServerCredentials($server->url, $server->token);
            $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
        }

        

        $customer->server_id = $server->id;
        $customer->pin = $pin;
        $customer->save();

        $la_data = Customer::findorfail($customer->id);
        Session::flash('modal',$la_data);

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'Cuenta Convertida a Iphone de Manera Exitosa!!',
            'alert-type'=>'success'
        ]);
    }

    public function remove_iphone($customer_id){
        $customer = Customer::findorfail($customer_id);
        $servers = [];
        $selectedServer = null;

        if( setting('admin.dynamic_server') ){
            $servers = Server::where('status',1)->server()->get();
            if($servers->count() > 1){
                $selectedServer = $servers[ rand(0, ($servers->count() - 1)) ];
            }else{
                $selectedServer = $servers[0];
            }
        }else{
            $selectedServer = $customer->server;
        }

        $userPin = $this->plex->loginInPlex($customer->email, $customer->password);
        $this->plex->removeHomeUserPin($userPin, $customer->pin);
        $this->plex->removeHomeUser($customer);

        //Remove Actual Server
        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
        $this->plex->provider->removeFriend($customer->invited_id);

        //Add New Server
        $this->plex->setServerCredentials($selectedServer->url, $selectedServer->token);
        $this->plex->createPlexAccount($customer->email, $customer->password, $customer);

        $customer->server_id = $selectedServer->id;
        $customer->pin = null;
        $customer->save();

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'Cuenta Removida de Iphone de Manera Exitosa!!',
            'alert-type'=>'success'
        ]);
    }

    public function repair_account($customer_id){
        $customer = Customer::findorfail($customer_id);
        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);
        $user = null;

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'la cuenta que intenta reparar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'alert-type'=>"error"
                ]);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);
            if(is_array($user)){
                if($user['response']['status'] != "Valid user"){

                    return redirect()->route("voyager.customers.index")->with([
                        'message'=>'la cuenta que intenta Reparar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                        'alert-type'=>"error"
                    ]);
                }
            }else{

                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'la cuenta que intenta reparar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'alert-type'=>"error"
                ]);
            }
            
        }

        $this->plex->setServerCredentials($customer->server->url, $customer->server->token);

        $plex_data = $this->plex->provider->getAccounts();

        if(!is_array($plex_data)){

            return redirect()->route("voyager.customers.index")->with([
                'message'=>'El servidor a donde quieres mover al cliente, tiene problemas con sus credenciales, por favor verificalas y vuelve a intentar!!',
                'alert-type'=>'error'
            ]);

        }else{
            
            //Remove Plex
            if(!empty($customer->invited_id)){
                $this->plex->provider->removeFriend($customer->invited_id);
                if($customer->password != "#5inCl4ve#"){
                    $this->plex->createPlexAccountNotCredit($customer->email, $customer->password, $customer);
                }else{
                    $this->plex->createPlexAccountNoPasswordNoCredit($customer->email, $customer);
                }
                
            }else{
                //Add Plex
                if($customer->password != "#5inCl4ve#"){
                    $this->plex->createPlexAccount($customer->email, $customer->password, $customer);
                }else{
                    $this->plex->createPlexAccountNoPassword($customer->email, $customer);
                }
                
            }

            $the_data = DB::table('customers')->select('invited_id')->where('id',$customer->id)->get();
            if(empty($the_data[0]->invited_id)){
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'Ocurrio un Error al Reparar la cuenta por favor Contacte con el Administrador!!',
                    'alert-type'=>'error'
                ]);
            }else{
                return redirect()->route("voyager.customers.index")->with([
                    'message'=>'Cuenta Reparada con Exito!!',
                    'alert-type'=>'success'
                ]);
            }
        }
    }

    public function change_password_user_plex(Request $request){
        $customer = Customer::findorfail($request->chp_customer_id);

        if($customer->password != "#5inCl4ve#"){
            $user = $this->plex->loginInPlex($customer->email, $customer->password);
            if(!is_array($user)){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }else{
            $user = $this->plex->provider->validateUser($customer->email);

            if($user['response']['status'] != "Valid user"){
                $data =[
                    'message'=>'la cuenta que intenta inhabilitar o habilitar es invalida, por favor verifique que el correo o la clave sean los correctos!!',
                    'success'=>false
                ];
                return response()->json($data);
            }
        }
        
        $response = $this->plex->changeUserPlexPassword($request->chp_current_password, $request->chp_new_password, $request->remove_all_devices, $user);

        if($response){
            return redirect()->route("voyager.customers.index")->with([
                'message'=>'Ocurrio un error al tratar de actualizar la clave, por favor verifica que el formato sea el corecto, mensaje de plex:"'.(string) $response->error->attributes()->{'message'}.'"',
                'alert-type'=>'error'
            ]);
        }else{
            $customer->password = $request->chp_new_password;
            $customer->save();

            return redirect()->route("voyager.customers.index")->with([
                'message'=>'La clave fue cambiada de manera exitosa, y tambien se cerro session en todos los dispositivos!',
                'alert-type'=>'success'
            ]);
        }
    }

    public function change_user(Request $request){

        $user = User::findorfail($request->user_asigned_id);
        $customer = Customer::findorfail($request->user_asigned_customer_id);
        $duration = Duration::findorfail($customer->duration_id);

        $amount = $duration->months;
        if(!empty($duration->amount)){
            if($duration->amount > 0){
                $amount = intval($duration->amount);
            }
        }

        if($user->role_id == 3 || $user->role_id == 5){
           if($user->total_credits == 0 || $user->total_credits < $amount){
            return redirect()->route("voyager.customers.index")->with([
                'message'    => __('El usuario que intenta asignar no cuenta con los creditos suficientes!!'),
                'alert-type' => 'error',
            ]);
           }
        }

        $this->removeCredit($customer, $duration, $user);

        $customer->user_id = $user->id;
        $customer->update();

        return redirect()->route("voyager.customers.index")->with([
            'message'=>'La asignacion del usuario se realizo de manera satisfactoria!!',
            'alert-type'=>'success'
        ]);
    }

    public function removeCredit(Customer $customer, Duration $duration, $user){

        $amount = $duration->months;
        if(!empty($duration->amount)){
            if($duration->amount > 0){
                $amount = intval($duration->amount);
            }
        }

        if($user->role_id == 3 || $user->role_id == 5){
           if(!empty($customer->invited_id)){
               $user = User::findorfail($user->id);
               $current_credit = $user->total_credits;
               DB::table('users')->where('id',$user->id)->update([
                    'total_credits'=>($current_credit - intval($amount))
               ]);

               $customer->duration_id = $duration->id;
               $customer->save();
           }
        }
        
    }

    public function import_from_plex(Request $request){
        $accounts = $request->accounts_for_import;
        $contador = 0;
        foreach($accounts as $account){

            $acc = json_decode($account, true);
            $date_from = $request->input('date_from_'.$acc['id']);
            $date_to = $request->input('date_to_'.$acc['id']);

            if(!empty($date_from) and !empty($date_to)){

                $verifyAccount = Customer::where("email", $acc['email'])->get();

                if( $verifyAccount->count() == 0 ){
                   $customer = new Customer();
                    $customer->email = $acc['email'];
                    $customer->plex_user_name = $acc['username'];
                    $customer->invited_id = $acc['id'];
                    $customer->date_from = $date_from;
                    $customer->date_to = $date_to;
                    $customer->duration_id = 2;
                    $customer->password = "#5inCl4ve#";
                    $customer->server_id = $request->server_id;
                    $customer->save();
                    $contador++; 
                }
            }
        }


        $redirect = redirect()->back();
        return $redirect->with([
            'message'    => __('Se Importaron '.$contador.' cuentas de manera exitosa!!'),
            'alert-type' => 'success',
        ]);
    }

    public function get_active_sessions($server_id, $user_id=null){
        $server = Server::findorfail($server_id);
        $data = [];
        $cont = 0;
        $user = null;
        $this->plex->setServerCredentials($server->url, $server->token);

        $customers = [];

        $sessions = $this->plex->provider->getNowPlaying();

        if($user_id){
            $customers = Customer::where('status','active')->where('user_id',$user_id)->pluck('plex_user_name')->toArray();
            $user = User::findorfail($user_id);

            if(is_array($sessions)){
                if(intval($sessions['MediaContainer']['size']) > 0){
                    $server_url = $this->plex->serverData['scheme']."://".$this->plex->serverData['address'].":".$this->plex->serverData['port'];

                    foreach($sessions['MediaContainer']['Metadata'] as $session){

                        if($user->role_id != 1 && $user->role_id != 4){
                            if( !in_array($session['User']['title'], $customers) ){
                                continue;
                            }
                        }

                        $data[$cont]['user'] = [
                            'avatar'=> $session['User']['thumb'],
                            'name'=> $session['User']['title']
                        ];

                        $data[$cont]['player'] = [
                            'ip'=>$session['Player']['address'],
                            'device'=>!empty($session['Player']['title']) ? $session['Player']['title'] : $session['Player']['product']
                        ];

                        $data[$cont]['media'] = [
                            'title'=>!empty($session['originalTitle']) ? $session['originalTitle']: $session['title'],
                            'cover'=>$server_url.(isset($session['art']) ? $session['art'] : $session['thumb'])."?X-Plex-Token=".$this->plex->serverData['token']
                        ];

                        $cont++;
                    }
                }            
            }

        }else{
            if(is_array($sessions)){
                if(intval($sessions['MediaContainer']['size']) > 0){
                    $server_url = $this->plex->serverData['scheme']."://".$this->plex->serverData['address'].":".$this->plex->serverData['port'];
                    foreach($sessions['MediaContainer']['Metadata'] as $session){

                        $data[$cont]['user'] = [
                            'avatar'=> $session['User']['thumb'],
                            'name'=> $session['User']['title']
                        ];

                        $data[$cont]['player'] = [
                            'ip'=>$session['Player']['address'],
                            'device'=>!empty($session['Player']['title']) ? $session['Player']['title'] : $session['Player']['product']
                        ];

                        $data[$cont]['media'] = [
                            'title'=>!empty($session['originalTitle']) ? $session['originalTitle']: $session['title'],
                            'cover'=>$server_url.(isset($session['art']) ? $session['art'] : $session['thumb'])."?X-Plex-Token=".$this->plex->serverData['token']
                        ];

                        $cont++;
                    }
                }            
            }
        }

        return response()->json($data);
    }

    public function activate_device(Request $request){
        $data = Customer::find($request->customer_id);
        if(!$data){
            $data = Demo::find($request->customer_id);
        }
        
        $code = $request->code;
        $response = $this->plex->activateDevice($code, $data);

        $redirect = redirect()->back();

        if($response['success']){
            return $redirect->with([
                'message'=>'Cuenta activada en el dispositivo de manera satisfactoria!!',
                'alert-type'=>'success'
            ]);
        }else{
            return $redirect->with([
                'message'=>$response['message'],
                'alert-type'=>'error'
            ]);
        }
    }

}