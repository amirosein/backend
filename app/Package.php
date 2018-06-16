<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;

class Package extends Eloquent
{

    public $timestamps = false;

    protected $appends = [];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'node_num', 'project_num', 'time', 'price','is_active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function things()
    {
        return $this->hasMany(Thing::class)->with('user');
    }

}
