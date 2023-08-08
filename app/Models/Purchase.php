<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Purchase extends Model
{
    use HasFactory;

    protected $table = "purchases";

    public function save($options = []){
        $this->user_id = Auth::user()->id;
        parent::save();
    }

    public function customer(){
        return $this->belongsTo('App\Models\Customer');
    }
}
