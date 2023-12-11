<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Models\User;
use DB;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    public function user(){
        return $this->belongsTo("App\Models\User");
    }

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

        if($role == 5){
            $query->where('user_id',Auth::user()->id);
        }

        if($role == 3){
            $childers = User::where('parent_user_id',Auth::user()->id)->pluck('id')->toArray();
            $query->where(function($query) use ($childers){
                $query->whereIn('user_id',$childers);  
            })->orWhere('user_id',Auth::user()->id);
        }

        return $query;
    }

    public static function verifyCustomer($invited_id){
        $data = DB::table("customers")->where('invited_id', $invited_id)->get();
        return $data;
    }

    public static function getNextExpiredAccounts(){
        $data = [];
        if(Auth::user()->role_id == 4 || Auth::user()->role_id == 1){
            $data = Customer::where('status','active')->get();
        }else{
            $data = Customer::where('status','active')->where('user_id',Auth::user()->id)->get();
        }
        
        return $data;
    }
}
