<?php

namespace App\Models;

use Havenstd06\LaravelPlex\Services\Plex as PlexClient;
use Havenstd06\LaravelPlex\Classes\FriendRestrictionsSettings;
use App\Models\Customer;
use App\Models\Duration;
use App\Models\User;
use App\Models\Demo;
use Auth;

class Plex {

    public $provider;
    public $token;

    public $server;

    public function __construct(){
        $this->provider = new PlexClient;
    }

    public function createPlexUser($email, $password) {
        $apiUrl = 'https://plex.tv/api/v2/users';
        
        $data = array(
            'email' => $email,
            'password' => $password
        );
        
        return $this->curlPost($apiUrl, $data);
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
        $plexToken = $this->token;

        $headers = array(
            'X-Plex-Client-Identifier: '.$plexToken,
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
                // Headers: https://github.com/Arcanemagus/plex-api/wiki/Plex.tv#request-headers
                // X-Plex-Client-Identifier is already defined in default config file
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
        $ownerId = $data->Invite->attributes()->{'id'};
        $friend = $data->Invite->attributes()->{'friend'};
        $home = $data->Invite->attributes()->{'home'};
        $server = $data->Invite->attributes()->{'server'};
        $this->accept_invitation($data_user['user']['authToken'], $ownerId, $friend, $home, $server);
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

    public function setServerCredentials($server_url, $token){
        $config = [
            'server_url'        => $server_url,
            'token'             => $token,
            'client_identifier' => $token,
            'product'           => 'havenstd06/laravel-plex',
            'version'           => '1.0.0',
            'validate_ssl'      => false,
        ];

        $this->token = $token;
        $this->server = $server_url;
        $this->provider->setApiCredentials($config);
    }

    public function createPlexAccount($email, $password, $data){
        $this->setServerCredentials($this->server, $this->token);
        $customer = Customer::findorfail($data->id);
        $duration = Duration::findorfail($data->duration_id);

        $response = $this->provider->validateUser($email);

        $librarySectionIds = [];

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
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            $customer->plex_user_name = $invited['invited']['username'];
            $customer->invited_id = $invited['invited']['id'];
        }else{
            $plex_user = simplexml_load_string($this->createPlexUser($email, $password));
            $customer->plex_user_name = $plex_user->attributes()->{'username'};
            $invited = $this->provider->inviteFriend($email, $librarySectionIds, $settings);
            $customer->invited_id = $invited['invited']['id'];
        }

        if(Auth::user()->role_id == 3){
           $user = User::findorfail(Auth::user()->id);
           $current_credit = $user->total_credits;
           $user->total_credits = ($current_credit - intval($duration->months));
           $user->update();
        }

        $this->getDataInvitation($email, $password, $invited['ownerId']);

        $usr = $this->loginInPlex($email, $password);
        $customer->plex_user_token = $usr['user']['authToken'];
        $customer->update();
    }
    

    public function createPlexAccountDemo($email, $password, $data){
        $this->setServerCredentials($this->server, $this->token);
        $demo = Demo::findorfail($data->id);

        $response = $this->provider->validateUser($email);

        $librarySectionIds = [];

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
        $demo->plex_user_token = $usr['user']['authToken'];
        $demo->update();
    }
}