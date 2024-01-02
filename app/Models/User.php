<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Auth;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public $additional_attributes = ['name_email_creator','name_and_role'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function customers(){
        return $this->hasMany('App\Models\Customer')->where('status','active');
    }

    public function scopeUser($query){
        if(Auth::user()->role_id == 3){
            $query->where('parent_user_id',Auth::user()->id);
            return $query->where('role_id',5);
        }else if(Auth::user()->role_id == 6){
            return $query->where(function($query){ 
                $query->where('role_id',5)->orWhere('role_id',3);
            })->where('parent_user_id',Auth::user()->id);
        }else{
            return $query->where('role_id',3)->orWhere('role_id',5);
        }
    }

    public function save($options = []){
        $this->status = "active";
        if(Auth::user()->role_id == 3 || Auth::user()->role_id == 4 || Auth::user()->role_id == 6 || Auth::user()->role_id == 1){
            if(empty($this->parent_user_id)){
                $this->parent_user_id = Auth::user()->id;
            }
            /*if($this->id != Auth::user()->id){
                $this->parent_user_id = Auth::user()->id;
            }*/
        }
        parent::save();
    }

    public function scopeFilterUsers($query){
        $allowRoles = [];
        
        switch (Auth::user()->role_id) {
            case 6:
                $allowRoles = [5,3];
                $query->where('parent_user_id',Auth::user()->id);
            break;
            case 4:
                $allowRoles = [2,3,4,5];
            break;

            case 3:
                $allowRoles = [5];
                $query->where('parent_user_id',Auth::user()->id);
            break;
            
            case 1:
                $allowRoles = [1,2,3,4,5,6];
            break;
        }

        return $query->whereIn('role_id', $allowRoles);
    }

    public function getNameEmailCreatorAttribute(){
        return $this->name." - ".$this->email;
    }

    public function getNameAndRoleAttribute(){
        return $this->name." (".$this->role->name.")";
    }

    public function role(){
        return $this->belongsTo('App\Models\Role');
    }

    public function servers(){
        return $this->hasMany('App\Models\Server');
    }

    public function assigned_servers(){
        return $this->belongsToMany('App\Models\Server','user_servers','user_id','server_id');
    }

    public static function getCustomersByReseller(){
        $data = User::with('customers','customers.server')->whereRelation('customers','customers.status','active')->get();
        return $data;
    }
}
