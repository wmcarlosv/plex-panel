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

    public function scopeUser($query){
        if(Auth::user()->role_id == 3){
            return $query->where('role_id',5);
        }else{
            return $query->where('role_id',3)->orWhere('role_id',5);
        }
    }

    public function save($options = []){
        $this->status = "active";

        if(Auth::user()->role_id == 3){
            $this->parent_user_id = Auth::user()->id;
        }

        parent::save();
    }

    public function scopeFilterUsers($query){
        $allowRoles = [];
        
        switch (Auth::user()->role_id) {
            case 4:
                $allowRoles = [2,3,4,5];
            break;

            case 3:
                $allowRoles = [5];
            break;
            
            case 1:
                $allowRoles = [1,2,3,4,5];
            break;
        }

        return $query->whereIn('role_id', $allowRoles);
    }

    protected function getFullNameAttribute(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => strtoupper($value),
        );
    }
}
