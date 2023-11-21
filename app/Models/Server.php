<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Models\User;

class Server extends Model
{
    use HasFactory;

    protected $table = "servers";

    public $tmpName;

    public $additional_attributes = ['name_and_local_name'];

    public function customers(){
        return $this->hasMany("App\Models\Customer")->where('status','active');
    }

    public function scopeServer($query){
        $servers_assigned = Auth::user()->assigned_servers();
        if($servers_assigned->count() > 0){
            $servers = $servers_assigned->pluck('server_id')->toArray();
        }else{
            $servers = $this->getServerIds();
        }

        if(Auth::user()->role_id == 4 || Auth::user()->role_id == 1 ){
            
        }else{
            if( setting("admin.iphone_only_server") ){
    
                $query->where("is_plex_pass",0);
            }
        }
        
        return $query->where('status',1)->where('is_demo',0)->whereIn('id',$servers);   
    }

    public function scopeServerDemo($query){
        $servers_assigned = Auth::user()->assigned_servers();
        if($servers_assigned->count() > 0){
            $servers = $servers_assigned->pluck('server_id')->toArray();
        }else{
            $servers = $this->getServerIds();
        }
        
        if( setting("admin.server_for_alls") ){
            return $query->where('status',1)->whereIn('id',$servers); 
        }else{
            return $query->where('status',1)->where('is_demo',1)->whereIn('id',$servers); 
        }
            
    }

    public function scopeNormalServer($query){
        $servers_assigned = Auth::user()->assigned_servers();
        if($servers_assigned->count() > 0){
            $servers = $servers_assigned->pluck('server_id')->toArray();
        }else{
            $servers = $this->getServerIds();
        }
        
        return $query->where('status',1)->whereIn('id',$servers);    
    }

    public function scopeServerByUser($query){
        return $query->where('user_id',Auth::user()->id);
    }

    public function save($options = []){
        if(!empty($this->tmpName)){
            $this->name = $this->tmpName;
        }

        $this->user_id = Auth::user()->id;

        parent::save();
    }

    public function getServerIds(){
        $servers = [];
        $role = Auth::user()->role_id;
        if($role == 5){
            $parent = User::findorfail(Auth::user()->parent_user_id);
            if($parent->role->id == 3){
                $ag = User::findorfail($parent->parent_user_id);
                $servers = $ag->servers->pluck('id')->toArray();
            }else if($parent->role->id == 4 || $parent->role->id == 6 || $parent->role->id == 1){
                $servers = $parent->servers->pluck('id')->toArray();
            }
        }else if($role == 3){
            $parent = User::findorfail(Auth::user()->parent_user_id);
            $servers = $parent->servers->pluck('id')->toArray();
        }else if($role == 4 || $role == 6 || $role == 1){
            $servers = Auth::user()->servers->pluck('id')->toArray();
        }

        return $servers;
    }

    public function getNameAndLocalNameAttribute(){
        if(setting('admin.show_only_server_local_name')){
            $role = Auth::user()->role_id;
            if($role == 5 || $role == 3){
                return $this->local_name;
            }else{
                return $this->name." (".$this->local_name.")";
            }
        }else{
            if(!empty($this->local_name)){
                return $this->name." (".$this->local_name.")";
            }else{
                return $this->name;
            }
        }
    }
}