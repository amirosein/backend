<?php

namespace App;

use Illuminate\Notifications\Notifiable;
//use Illuminate\Foundation\Auth\User as Authenticatable;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Facades\JWTAuth;

class User extends Eloquent implements AuthenticatableContract, AuthorizableContract
{
    use Authorizable;
    use Authenticatable;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'other_info', 'active', 'legal', 'mobile', 'package'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'active', '_id', 'updated_at', 'created_at', 'files', 'email_token'
    ];

    public function projects()
    {
        return $this->permissions()->where('item_type', 'project')->with('project');
    }


    public function things()
    {
        return $this->permissions()->where('item_type', 'thing')->with(['thing', 'thing.project']);
    }


    public function permissions()
    {
        return $this->hasMany(Permission::class, 'user_id');
    }

    public function gateways()
    {
        return $this->hasMany(Gateway::class, 'user_id');
    }

    public function config()
    {
        return $this->hasOne(UserConfig::class, 'user_id');
    }

    public function thingProfiles()
    {
        return $this->hasMany(ThingProfile::class, 'user_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }

    public function isAdmin()
    {

        $main_user = JWTAuth::getPayload()->toArray();
        $main_user = isset($main_user['impersonate_id']) ? User::where('_id', $main_user['impersonate_id'])->first() : null;
        if ($main_user)
            return (isset($main_user['is_admin']) && $main_user['is_admin']);
        return (isset($this['is_admin']) && $this['is_admin']);
    }

}
