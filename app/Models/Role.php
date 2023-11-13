<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Tests\Database\Factories\RoleFactory;
use Auth;

class Role extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users()
    {
        $userModel = Voyager::modelClass('User');

        return $this->belongsToMany($userModel, 'user_roles')
                    ->select(app($userModel)->getTable().'.*')
                    ->union($this->hasMany($userModel))->getQuery();
    }

    public function permissions()
    {
        return $this->belongsToMany(Voyager::modelClass('Permission'));
    }

    protected static function newFactory()
    {
        return RoleFactory::new();
    }

    public function scopeFilterRole($query){
        $allowRoles = [];
        switch (Auth::user()->role_id) {
            case 6:
                $allowRoles = [5,3];
            break;
            case 4:
                $allowRoles = [2,3,5];
            break;

            case 3:
                $allowRoles = [5];
            break;
            
            case 1:
                $allowRoles = [1,2,3,4,5,6];
            break;
        }

        return $query->whereIn('id', $allowRoles);
    }
}