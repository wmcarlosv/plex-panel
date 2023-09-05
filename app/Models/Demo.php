<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demo extends Model
{
    use HasFactory;

    protected $table = "demos";

    public function save($options = []){
        $dates = $this->sumHours($this->hours);
        $this->start_date = $dates['start'];
        $this->end_date = $dates['end'];
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
}
