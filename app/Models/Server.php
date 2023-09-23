<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;

    protected $table = "servers";

    public $tmpName;

    public function scopeServer($query){
        return $query->where('status',1);   
    }

    public function scopeServerDemo($query){
        return $query->where('status',1)->where('is_demo',1);   
    }

    public function save($options = []){
        if(!empty($this->tmpName)){
            $this->name = $this->tmpName;
        }
        parent::save();
    }
}