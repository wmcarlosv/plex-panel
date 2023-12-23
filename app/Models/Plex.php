<?php

namespace App\Models;
use Illuminate\Http\Request;
use Havenstd06\LaravelPlex\Services\Plex as PlexClient;
use Havenstd06\LaravelPlex\Classes\FriendRestrictionsSettings;
use App\Models\Customer;
use App\Models\Duration;
use App\Models\User;
use App\Models\Demo;
use App\Models\Server;
use App\Models\Session;
use Auth;
use DB;
use App\Models\Proxy;
use App\Models\Movement;

class Plex {

    public $provider;
    public $server_email;
    public $name;
    public $server_password;
    public $serverData;
    public $proxy;

    public function __construct(){
        $this->provider = new PlexClient;
        $this->proxy = null;
    }

    public function createPlexUser($email, $password) {
        $apiUrl = 'https://plex.tv/api/v2/users';
        
        $data = array(
            'email' => $email,
            'password' => $password
        );
        
        $headers = array(
            'X-Plex-Client-Identifier: '.uniqid(),
            'Content-Type: application/json'
        );

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(setting("admin.active_proxies")){

            $this->proxy = $this->getCorrectProxy();

            if($this->proxy){
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL , 1);
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy->ip);         
                curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy->port);
            }
        }
        
        $response = curl_exec($ch);

        //Check for errors.
        if(curl_errno($ch)){
            dd($this->proxy->ip, $this->proxy->port, curl_error($ch));
        }

        
        
        curl_close($ch);

        return $response;
    }

    public function verifyUser($email, $password){
        $apiUrl = "https://plex.tv/api/v2/users/signin";
        $data = array(
            'login'=>$email,
            'password'=>$password
        );

        return $this->curlPost($apiUrl, $data);
    }

    public function curlPost($url, $params){
        $headers = array(
            'X-Plex-Client-Identifier: '.uniqid(),
            'Content-Type: application/json'
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        return $response;
    }

    public function loginInPlex($username, $password){
        $data = [
            'auth' => [
                $username, // Required
                $password, // Required
            ],
            'headers' => [
                "X-Plex-Client-Identifier" => uniqid(),
                "X-Plex-Product"=>"Plex Web"
            ]
        ];

        $plexUser = $this->provider->signIn($data, false);
        return $plexUser;
    }

    public function getDataInvitation($email, $password, $ownerId){
        $data_user = $this->loginInPlex($email, $password);
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "X-Plex-Token: ".$data_user['user']['authToken']
            ]
        ];

        $context = stream_context_create($opts);

        $response = file_get_contents('https://plex.tv/api/invites/requests', false, $context);
        $data = simplexml_load_string($response);
        
        if(!empty($data)){
            if(is_array($data->Invite)){
                foreach($data->Invite as $invt){

                    $ownerId = $invt->attributes()->{'id'};
                    $friend = $invt->attributes()->{'friend'};
                    $home = $invt->attributes()->{'home'};
                    $server = $invt->attributes()->{'server'};

                    $this->accept_invitation($data_user['user']['authToken'], $ownerId, $friend, $home, $server);
                }
            }else{

                $ownerId = $data->Invite->attributes()->{'id'};
                $friend = $data->Invite->attributes()->{'friend'};
                $home = $data->Invite->attributes()->{'home'};
                $server = $data->Invite->attributes()->{'server'};
                $this->accept_invitation($data_user['user']['authToken'], $ownerId, $friend, $home, $server);

            }
        }

        $this->resetCustomization($data_user['user']['authToken'], uniqid());
    }

    public function accept_invitation($token, $ownerId, $friend, $home, $server){
        $ownerId = (string)$ownerId;
        $url = "https://plex.tv/api/invites/requests/".$ownerId;
        $data = [
            'friend' => (string)$friend,
            'home' => (string)$home,
            'server' => (string)$server
        ];

        $headers = [
            'X-Plex-Token:'.$token
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    public function resetCustomization($token, $ci){
        $url = "https://clients.plex.tv/api/v2/user/settings?sharedSettings=1&X-Plex-Product=Plex%20Web&X-Plex-Token=$token&X-Plex-Client-Identifier=$ci";
        $data = '{"value":[{
                    "id":"experience",
                    "type":"json",
                    "value":"[{autoHomeHubsEnabled: true, showAdvancedSettings:true, sidebarSettings:{pinnedSources:[]}, autoPinnedProviders:[myPlex--tv.plex.provider.epg--home,myPlex--tv.plex.provider.vod--movies,myPlex--tv.plex.provider.discover--home,myPlex--tv.plex.provider.discover--watchlist],}]",
                    "hidden":true
                }]}';

        $headers = ['Content-Type: application/json'];

        $data_arr = json_decode($data, true);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    public function setServerCredentials($user, $password){
        $validate = false;
        $this->serverData = $this->getServerCredentials($user, $password);
        if(count($this->serverData) > 0){
           $server_url = $this->serverData['scheme']."://".$this->serverData['address'].":".$this->serverData['port'];
           $token = $this->serverData['token'];
           $this->name = $this->serverData['name'];
           $config = [
                'server_url'        => $server_url,
                'token'             => $token,
                'client_identifier' => uniqid(),
                'product'           => 'CarlosVargas/panel-plex',
                'version'           => '1.0.0',
                'validate_ssl'      => false,
            ];

            $this->server_email = $user;
            $this->server_password = $password;
            $this->provider->setApiCredentials($config); 
            $validate = true;
        }

        return $validate;
        
    }

    public function getDataServer($data){
        $libraries = [];
        $get_libraries = DB::table("server_libraries")->select("library_id")->where("server_id",$data->server->id)->get();
        foreach($get_libraries as $gl){
           $libraries[] = (int) $gl->library_id; 
        }
        return $libraries;
    }

    public function createPlexAccount($email, $password, $data){
        $this->setServerCredentials($this->server_email, $this->server_password);
        $customer = Customer::findorfail($data->id);
        $duration = Duration::findorfail($data->duration_id);

        $amount = $duration->months;
        if(!empty($duration->amount)){
            if($duration->amount > 0){
                $amount = intval($duration->amount);
            }
        }

        $response = $this->provider->validateUser($email);
        $librarySectionIds = $this->getDataServer($data);
        $settings = new FriendRestrictionsSettings(
            allowChannels: '1',
            allowSubtitleAdmin: '1',
            allowSync: '0',
            allowTuners: '0',
            filterMovies: '',
            filterMusic: '',
            filterTelevision: '',
        );

        if($response['response']['status'] == "Valid user"){
            $this->provider->cancelInvite($email);
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            if(is_array($invited)){
                $customer->plex_user_name = $invited['invited']['username'];
                $customer->invited_id = $invited['invited']['id'];
            }else{
                $customer->plex_user_name = null;
                $customer->invited_id = null;
            }
            
        }else{
            $plex_user = simplexml_load_string($this->createPlexUser($email, $password));
            $customer->plex_user_name = $plex_user->attributes()->{'username'};
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);

            if(is_array($invited)){
                $customer->invited_id = $invited['invited']['id'];
            }else{
                $customer->invited_id = null;
            }

            if($this->proxy){
                $customer->proxy_id = $this->proxy->id;
            }
        }


        if(Auth::user()->role_id == 3 || Auth::user()->role_id == 5){

           if(!empty($customer->invited_id)){
               $user = User::findorfail(Auth::user()->id);
               $current_credit = $user->total_credits;
               DB::table('users')->where('id',$user->id)->update([
                    'total_credits'=>($current_credit - intval($amount))
               ]);

               $this->addMovement("Creacion de Cuenta",$customer, intval($amount));
           }
           
        }else{
            if(!empty($customer->invited_id)){
                $this->addMovement("Creacion de Cuenta",$customer);
            }
            
        }

        if(is_array($invited)){
            $this->getDataInvitation($email, $password, $invited['ownerId']);
        }
        
        $usr = $this->loginInPlex($email, $password);
        if(is_array($usr)){
            $customer->plex_user_token = $usr['user']['authToken'];
        }else{
            $customer->plex_user_token = null;
        }
        
        $customer->update();        
    }
    

    public function createPlexAccountDemo($email, $password, $data){
        $this->setServerCredentials($this->server_email, $this->server_password);
        $demo = Demo::findorfail($data->id);

        $response = $this->provider->validateUser($email);

        $librarySectionIds = $this->getDataServer($data);

        $settings = new FriendRestrictionsSettings(
            allowChannels: '1',
            allowSubtitleAdmin: '1',
            allowSync: '0',
            allowTuners: '0',
            filterMovies: '',
            filterMusic: '',
            filterTelevision: '',
        );

        if($response['response']['status'] == "Valid user"){
            $this->provider->cancelInvite($email);
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            $demo->plex_user_name = $invited['invited']['username'];
            $demo->invited_id = $invited['invited']['id'];
        }else{

            $plex_user = simplexml_load_string($this->createPlexUser($email, $password));
            $demo->plex_user_name = $plex_user->attributes()->{'username'};
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            $demo->invited_id = $invited['invited']['id'];
        }
        
        $this->getDataInvitation($email, $password, $invited['ownerId']);

        $usr = $this->loginInPlex($email, $password);
        if(is_array($usr)){
            $demo->plex_user_token = $usr['user']['authToken'];
        }else{
            $demo->plex_user_token = null;
        }
        
        $demo->update();

        $this->addMovement("Creacion de Cuenta Demo",$demo);
    }

    public function serverRequest($url, $username, $password) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); // Use basic authentication
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password); // Set username and password
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Set any additional headers if needed
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }

    public function getServerCredentials($user, $password){
        $response_data = [];
        $xml_response = simplexml_load_string($this->serverRequest("https://plex.tv/pms/servers.xml",$user, $password));
        $encontrado = false;
        if(empty($xml_response->error)){
            $data = $xml_response->Server;
            $indice = 0;
            foreach($data as $serv){
                $owned = (int)$serv->attributes()->{'owned'};
                if($owned == 1){
                    $data = $xml_response->Server[$indice];
                }
                $indice++;
            }



            $size = (int) $xml_response->attributes()->{'size'};
            if($size!=0){
                $response_data['name'] = (string) $data->attributes()->{'name'};
                $response_data['address'] = (string) $data->attributes()->{'address'};
                $response_data['port'] = (string) $data->attributes()->{'port'};
                $response_data['scheme'] = (string) $data->attributes()->{'scheme'};
                $response_data['machine'] = (string) $data->attributes()->{'machineIdentifier'};
                $devices = simplexml_load_string($this->serverRequest("https://plex.tv/devices.xml", $user, $password));

                foreach($devices->Device as $device){
                    $serverName = (string) trim($device->attributes()->{'name'});
                    if($response_data['name'] == $serverName){
                        $response_data['token'] = (string) $device->attributes()->{'token'};
                        $encontrado = true;
                        break;
                    }
                }

                if(!$encontrado){
                    foreach($devices->Device as $device){
                        $serverName = (string) trim($device->attributes()->{'name'});
                        if($serverName == "Plex Media Server"){
                            $response_data['token'] = (string) $device->attributes()->{'token'};
                            $encontrado = true;
                            break;
                        }
                    }
                }
            }
        }

        return $response_data;
    }

    public function getSessionsAllServers(){
        $servers = Server::where('status',1)->where('is_demo',0)->where('is_plex_pass', 1)->get();
        DB::table('sessions')->delete();
        foreach($servers as $server){
            if($server->customers->count() > 0){
                $data = $this->getServerCredentials($server->url, $server->token);
                $sessions = $this->getSessionsByServer($data);
                foreach($sessions as $session){
                    $ss = new Session();
                    $ss->server_id = $server->id;
                    $ss->plex_user_id = $session['plex_user_id'];
                    $ss->plex_session_id = $session['plex_session_id'];
                    $ss->save();
                }
            }
        }
        $this->setScreenMessage();
    }

    public function getSessionsByServer($data){
        $plex_sessions = [];
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "X-Plex-Token: ".$data['token']
            ]
        ];

        $context = stream_context_create($opts);
        $url = $data['scheme']."://".$data['address'].":".$data['port']."/status/sessions";
        $response = file_get_contents($url, false, $context);
        $sessions = simplexml_load_string($response);
        $cont = 0;
        foreach($sessions->Video as $video){
            $plex_sessions[$cont]['plex_session_id'] = (string) $video->Session->attributes()->{'id'};
            $plex_sessions[$cont]['plex_user_id'] = (string) $video->User->attributes()->{'id'};
            $cont++;
        }
        return $plex_sessions;
    }

    public function setScreenMessage(){
        $servers = Server::where('status',1)->where('is_demo',0)->where("is_plex_pass", 1)->get();
        foreach($servers as $server){
            if($server->customers->count() > 0){
                $data = $this->getServerCredentials($server->url, $server->token);
                $customers = Customer::where('server_id',$server->id)->where('status','active')->get();
                foreach($customers as $customer){
                    $session = Session::where('plex_user_id',$customer->invited_id)->get();
                    if($session->count() > $customer->screen){
                        $last_session_id = $session[$session->count() - 1]->plex_session_id;
                        $this->requestScreen($data, $last_session_id);
                    }
                }
            }
        }
    }

    public function requestScreen($data, $sessionId){
        $message = setting('admin.screen_message');
        $url = $data['scheme']."://".$data['address'].":".$data['port']."/status/sessions/terminate?sessionId=".$sessionId."&reason=".$message."&X-Plex-Token=".$data['token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        }
        curl_close($ch);
        echo $response;

    }

    public function createPlexAccountNotCredit($email, $password, $data){
        $this->setServerCredentials($this->server_email, $this->server_password);
        $customer = Customer::findorfail($data->id);
        $duration = Duration::findorfail($data->duration_id);

        $response = $this->provider->validateUser($email);

        $librarySectionIds = $this->getDataServer($data);

        $settings = new FriendRestrictionsSettings(
            allowChannels: '1',
            allowSubtitleAdmin: '1',
            allowSync: '0',
            allowTuners: '0',
            filterMovies: '',
            filterMusic: '',
            filterTelevision: '',
        );

        if($response['response']['status'] == "Valid user"){
            $this->provider->cancelInvite($email);
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            if(is_array($invited)){
                $customer->plex_user_name = $invited['invited']['username'];
                $customer->invited_id = $invited['invited']['id'];
            }else{
                $customer->plex_user_name = null;
                $customer->invited_id = null;
            }
            
        }else{
            $plex_user = simplexml_load_string($this->createPlexUser($email, $password));
            $customer->plex_user_name = $plex_user->attributes()->{'username'};
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);

            if(is_array($invited)){
                $customer->invited_id = $invited['invited']['id'];
            }else{
                $customer->invited_id = null;
            }
            
        }

        if(is_array($invited)){
            $this->getDataInvitation($email, $password, $invited['ownerId']);
        }
        
        $usr = $this->loginInPlex($email, $password);
        if(is_array($usr)){
            $customer->plex_user_token = $usr['user']['authToken'];
        }else{
            $customer->plex_user_token = null;
        }
        
        $customer->update();

        $this->addMovement("Creacion de Cuenta Sin Afectar Creditos",$customer);
    }

    public function getCorrectProxy(){
        $proxy = [];
        $proxies = Proxy::all();
        $cont = 0;
        $rand_pos = null;
        foreach($proxies as $p){
            if($p->customers->count() < 3){
                $proxy[$cont] = $p;
                $cont++;
            }
        }

        if($cont > 0){
            $rand_pos = rand(0, $cont);
            return $proxy[$rand_pos];
        }else{
            return null;
        }
    }

    public function createHomeUser(Server $server, Customer $customer, $pin){

        $url = "https://plex.tv/api/home/users?invitedEmail=".$customer->plex_user_name;
        $ch = curl_init($url);
        $validated = true;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $server->url . ':' . $server->token);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        $user = simplexml_load_string(curl_exec($ch));

        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        curl_close($ch);

        if(!empty($user)){
            if((int)$user->attributes()->{'code'} == '403'){
                $validated = false;
            }   
        }

        if($validated){
            $this->setServerCredentials($server->url, $server->token);
            $dataOwner = $this->loginInPlex($server->url, $server->token);
            $this->getDataInvitationHomeUser($customer->email, $customer->password, $dataOwner['user']['id']);
            $userPin = $this->loginInPlex($customer->email, $customer->password);
            $this->setHomeUserPin($userPin,$pin);
        }
        return $validated;
    }

    public function getDataInvitationHomeUser($email, $password, $ownerId){
        $data_user = $this->loginInPlex($email, $password);

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "X-Plex-Token: ".$data_user['user']['authToken']
            ]
        ];

        $context = stream_context_create($opts);


        $response = file_get_contents('https://plex.tv/api/invites/requests', false, $context);
        $data = simplexml_load_string($response);
        $ownerId = $data->Invite->attributes()->{'id'};
        $friend = $data->Invite->attributes()->{'friend'};
        $home = $data->Invite->attributes()->{'home'};
        $server = $data->Invite->attributes()->{'server'};
        $this->accept_invitation($data_user['user']['authToken'], $ownerId, $friend, $home, $server);
        $this->resetCustomization($data_user['user']['authToken'], uniqid());
    }

    public function setHomeUserPin($user,$pin=""){
        $url = "https://clients.plex.tv/api/home/users/".$user['user']['id']."?pin=".$pin."&X-Plex-Client-Identifier=".uniqid()."&X-Plex-Token=".$user['user']['authToken'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $pin = simplexml_load_string(curl_exec($ch));

        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        curl_close($ch);
    }

    public function removeHomeUserPin($user,$pin){
        $url = "https://clients.plex.tv/api/home/users/".$user['user']['id']."?currentPin=".$pin."&pin=&X-Plex-Client-Identifier=".uniqid()."&X-Plex-Token=".$user['user']['authToken'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $pin = simplexml_load_string(curl_exec($ch));

        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        curl_close($ch);
    }

    public function removeHomeUser($customer){
        $userIdDeleted = $this->loginInPlex($customer->email, $customer->password);
        $user = $this->loginInPlex($customer->server->url, $customer->server->token);
        $url = "https://clients.plex.tv/api/home/users/".$userIdDeleted['user']['id']."?&X-Plex-Client-Identifier=".uniqid()."&X-Plex-Token=".$user['user']['authToken'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $deletedUser = simplexml_load_string(curl_exec($ch));

        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        curl_close($ch);
    }

    public function refreshLibraries($library_id){
        $data = $this->serverData;
        $opts = [
            "http" => [
                "method" => "GET" 
            ]
        ];

        $context = stream_context_create($opts);
        $url = $data['scheme']."://".$data['address'].":".$data['port']."/library/sections/".$library_id."/refresh?X-Plex-Token=".$data['token'];
        $response = file_get_contents($url, false, $context);
        $refresh = simplexml_load_string($response);
    }

    public function changeUserPlexPassword($currentPassword, $newPassword, $removeAllDevices, $user){
        $resetDevices = false;
        if($removeAllDevices){
            $resetDevices = true;
        }

        $url = "https://clients.plex.tv/api/v2/user?X-Plex-Product=Plex%20Web&password=".$newPassword."&passwordConfirmation=".$newPassword."&currentPassword=".$currentPassword."&resetDevices=".$resetDevices."&X-Plex-Token=".$user['user']['authToken'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $change_password = simplexml_load_string(curl_exec($ch));

        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
        }
        curl_close($ch);
        return $change_password;
    }

    public function removeServer($data_user, $ownerId){
        $opts = [
            "http" => [
                "method" => "DELETE",
                "header" => "X-Plex-Token: ".$data_user['user']['authToken']
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents('https://clients.plex.tv/api/v2/sharings/'.$ownerId, false, $context);
        $data = simplexml_load_string($response);
    }

    public function createPlexAccountNoPassword($email, $data){

        $this->setServerCredentials($this->server_email, $this->server_password);
        
        $customer = Customer::findorfail($data->id);
        $duration = Duration::findorfail($data->duration_id);

        $amount = $duration->months;
        if(!empty($duration->amount)){
            if($duration->amount > 0){
                $amount = intval($duration->amount);
            }
        }

        $response = $this->provider->validateUser($email);

        $librarySectionIds = $this->getDataServer($data);

        $settings = new FriendRestrictionsSettings(
            allowChannels: '1',
            allowSubtitleAdmin: '1',
            allowSync: '0',
            allowTuners: '0',
            filterMovies: '',
            filterMusic: '',
            filterTelevision: '',
        );

        $invited = null;

        if($response['response']['status'] == "Valid user"){
            $this->provider->cancelInvite($email);
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            if(is_array($invited)){
                $customer->plex_user_name = $invited['invited']['username'];
                $customer->invited_id = $invited['invited']['id'];
                $customer->plex_user_id = $invited['inviteToken'];
            }else{
                $customer->plex_user_name = null;
                $customer->invited_id = null;
            }
            
        }else{
            $customer->plex_user_name = null;
            $customer->invited_id = null;
        }

        if(Auth::user()->role_id == 3 || Auth::user()->role_id == 5){

           if(!empty($customer->invited_id)){
               $user = User::findorfail(Auth::user()->id);
               $current_credit = $user->total_credits;
               DB::table('users')->where('id',$user->id)->update([
                    'total_credits'=>($current_credit - intval($amount))
               ]);
               $this->addMovement("Creacion de Cuenta Sin Clave",$customer, intval($amount));
           }
           
        }else{
            if(!empty($customer->invited_id)){
                $this->addMovement("Creacion de Cuenta Sin Clave",$customer);
            }
        }

        $customer->update();
    }

    public function createPlexAccountNoPasswordNoCredit($email, $data){

        $this->setServerCredentials($this->server_email, $this->server_password);
        
        $customer = Customer::findorfail($data->id);
        $duration = Duration::findorfail($data->duration_id);

        $response = $this->provider->validateUser($email);

        $librarySectionIds = $this->getDataServer($data);

        $settings = new FriendRestrictionsSettings(
            allowChannels: '1',
            allowSubtitleAdmin: '1',
            allowSync: '0',
            allowTuners: '0',
            filterMovies: '',
            filterMusic: '',
            filterTelevision: '',
        );

        $invited = null;

        if($response['response']['status'] == "Valid user"){
            $this->provider->cancelInvite($email);
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            if(is_array($invited)){
                $customer->plex_user_name = $invited['invited']['username'];
                $customer->invited_id = $invited['invited']['id'];
                $customer->plex_user_id = $invited['inviteToken'];
            }else{
                $customer->plex_user_name = null;
                $customer->invited_id = null;
            }
            
        }else{
            $customer->plex_user_name = null;
            $customer->invited_id = null;
        }
        $customer->update();

        $this->addMovement("Creacion de Cuenta Sin Clave Sin Afectar Creditos",$customer);
    }

    public function createPlexAccountNoPasswordNoCreditDemo($email, $data){

        $this->setServerCredentials($this->server_email, $this->server_password);
        $demo = Demo::findorfail($data->id);
        $response = $this->provider->validateUser($email);
        $librarySectionIds = $this->getDataServer($data);

        $settings = new FriendRestrictionsSettings(
            allowChannels: '1',
            allowSubtitleAdmin: '1',
            allowSync: '0',
            allowTuners: '0',
            filterMovies: '',
            filterMusic: '',
            filterTelevision: '',
        );

        $invited = null;

        if($response['response']['status'] == "Valid user"){
            $this->provider->cancelInvite($email);
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            if(is_array($invited)){
                $demo->plex_user_name = $invited['invited']['username'];
                $demo->invited_id = $invited['invited']['id'];
                $demo->plex_user_token = $invited['inviteToken'];
            }else{
                $demo->plex_user_name = null;
                $demo->invited_id = null;
            }
            
        }else{
            $demo->plex_user_name = null;
            $demo->invited_id = null;
        }
        $demo->update();

        $this->addMovement("Creacion de Cuenta Demo Sin Clave",$demo);
    }

    public function getRealAccountServerData($data_user){
        $data_array = [];
        $contador = 0;
        $url = "https://clients.plex.tv/api/users?X-Plex-Client-Identifier=".uniqid()."&X-Plex-Token=".$data_user['user']['authToken'];
        $opts = [
            "http" => [
                "method" => "GET"
            ]
        ];

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $data = simplexml_load_string($response);
        foreach($data->User as $user){
            $data_array[$contador]['id'] = (int)$user->attributes()->{'id'};
            $data_array[$contador]['username'] = (string)$user->attributes()->{'username'};
            $data_array[$contador]['email'] = (string)$user->attributes()->{'email'};
            $contador++;
        }

        return $data_array;
    }

    public function removeServerNoPassword($owner, $server, $customer){

        $this->setServerCredentials($server->url, $server->token);

        $accounts = $this->getRealAccountServerData($owner);
        $data = [];
        
        foreach($accounts as $account){
            if($customer->email == $account['email']){
                $data = $account;
                break;
            }
        }

        return $data;
    }

    public function getRealUsersInPlex(Customer $customer){
        $owner = $this->loginInPlex($this->server_email, $this->server_password);
        $accounts = $this->getRealAccountServerData($owner);
        $data = [];
        
        foreach($accounts as $account){
            if($customer->email == $account['email']){
                $data = $account;
                break;
            }
        }

        return $data;
    }

    public function activateDevice($pin, $data){
        $response['success'] = false;

        if($data->password == "#5inCl4ve#"){
             $this->setServerCredentials($data->server->url, $data->server->token);
             $user = $this->provider->validateUser($data->email);
        }else{
             $user = $this->loginInPlex($data->email, $data->password);
        }   
        
        if(is_array($user)){
            $url = "https://plex.tv/api/v2/pins/link?X-Plex-Client-Identifier=".uniqid()."&X-Plex-Token=".$user['user']['authToken']."&X-Plex-Product=Plex%20SSO&code=".$pin;
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            $data = simplexml_load_string(curl_exec($ch));
            curl_close($ch);
            if(isset($data) and !empty($data)){
                $response['message'] = (string) $data->error->attributes()->{'message'};
            }else{
                $response['success'] = true;
            }
        }else{
            $response['message'] = "Ocurrio un Error al activar, por favor verifia que las credenciales del usuario sean las correctas!!";
        }

        return $response;
    }

    public function addMovement($description, $data, $credits=0){
        $movement = new Movement();
        $movement->user = Auth::user()->name." (".Auth::user()->role->name.")";
        $movement->customer = $data->email;
        $movement->description = $description;
        $movement->credits = $credits;
        $movement->movement_time = date('Y-m-d H:i:s');
        $movement->server = $data->server->name." - ".$data->server->local_name;
        $movement->save();
    }
}