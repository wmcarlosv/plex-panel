<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Models\User;

class Credit extends Model
{
    use HasFactory;

    protected $table = 'credits';

    public function save($options = []){
        if(Auth::user()->role_id == 3){
            /*Super Reseller*/
            $userr = User::findorfail(Auth::user()->id);
            $current_creditr = intval($userr->total_credits);

            if($current_creditr < $this->qty){
                
                $redirect = redirect()->back();
                return $redirect->with([
                    'message'    => "No tienes creditos suficientes, por favor solicita mas creditos!!",
                    'alert-type' => 'error',
                ]);
            }

            $userr->total_credits = ($current_creditr-intval($this->qty));
            $userr->update();

            /*Reseller*/
            $user = User::findorfail($this->user_id);
            $current_credit = intval($user->total_credits);
            $user->total_credits = ($current_credit+intval($this->qty));
            $user->update();
        }else{
             /*Reseller*/
            $user = User::findorfail($this->user_id);
            $current_credit = intval($user->total_credits);
            $user->total_credits = ($current_credit+intval($this->qty));
            $user->update();
        }

        $this->user_parent_id = Auth::user()->id;
        
        parent::save();
    }

    public function scopeByUser($query){
        if(Auth::user()->role_id == 3){
          return $query->where('user_parent_id',Auth::user()->id);
        }
    }
}
