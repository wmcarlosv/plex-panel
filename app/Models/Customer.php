<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';


    public function save($options = []){
        $this->user_id = Auth::user()->id;
        parent::save();
    }

    public function server(){
        return $this->belongsTo('App\Models\Server');
    }

    public function scopeByUser($query){
        $role = Auth::user()->role_id;
        if($role == 3 || $role == 5){
            return $query->where('user_id',Auth::user()->id);
        }
    }
}
