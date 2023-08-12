<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;

    protected $table = "servers";

    public function scopeServer($query){
        return $query->where('status',1)->where('is_demo',0);   
    }
}
