<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proxy extends Model
{
    use HasFactory;

    protected $table = "proxies";

    public function customers(){
        return $this->hasMany("App\Models\Customer");
    }
}