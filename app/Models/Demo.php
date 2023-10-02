<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Demo extends Model
{
    use HasFactory;

    protected $table = "demos";

    public function save($options = []){
        $dates = $this->sumHours($this->hours);
        $this->start_date = $dates['start'];
        $this->end_date = $dates['end'];
        $this->user_id = Auth::user()->id;
        parent::save();
    }

    public function sumHours($hours){
        $startDate = new \DateTime();
        $hoursToAdd = $hours;
        $startDate->modify("+{$hoursToAdd} hours");
        $endDate = $startDate->format('Y-m-d H:i:s');
        return ['start'=>date('Y-m-d H:i:s'), 'end'=>$endDate];
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
