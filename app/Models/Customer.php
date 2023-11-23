<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Models\User;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';


    public function save($options = []){
        if(empty($this->user_id)){
            $this->user_id = Auth::user()->id;
        }
        parent::save();
    }

    public function server(){
        return $this->belongsTo('App\Models\Server');
    }

    public function scopeByUser($query){

        $role = Auth::user()->role_id;

        if($role == 3 || $role == 5){
            $query->where('user_id',Auth::user()->id);
        }

        if($role == 6){
            $childers = User::where('parent_user_id',Auth::user()->id)->pluck('id')->toArray();
            $query->where(function($query) use ($childers){
                $query->whereIn('user_id',$childers);  
            })->orWhere('user_id',Auth::user()->id);
        }

        return $query;
    }
}
