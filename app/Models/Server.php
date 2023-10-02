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

    public function scopeServer($query){
        $servers = $this->getServerIds();
        return $query->where('status',1)->where('is_demo',0)->whereIn('id',$servers);   
    }

    public function scopeServerDemo($query){
        $servers = $this->getServerIds();
        return $query->where('status',1)->where('is_demo',1)->whereIn('id',$servers);     
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
            }else if($parent->role->id == 4 || $parent->role->id == 6){
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
}