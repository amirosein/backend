<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;

class ThingProfile extends Eloquent
{
    protected $appends = ['name'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'thing_profile_slug', 'data', 'type', 'user_id', 'device_profile_id', 'name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'created_at', 'data', 'user_id'
    ];

    public function things()
    {
        return $this->hasMany(Thing::class, 'profile_id');
    }

    public function getNameAttribute($value)
    {
        return $this['data']['name'];
    }
}
