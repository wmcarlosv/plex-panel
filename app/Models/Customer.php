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

    public function scopeCustomer($query){
        return $query->where('user_id',Auth::user()->id)->where('status','active');
    }
}
