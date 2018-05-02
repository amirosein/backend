<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed name
 * @property mixed description
 */
class Project extends Eloquent
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'active', 'container', 'application_id', '_id',
    ];
    protected $appends = ['owner'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'created_at', 'user_id', 'permissions','aliases'
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'item_id')
            ->where('item_type', 'project')
            ->with('user');
    }


    public function things()
    {
        return $this->hasMany(Thing::class);
    }

    public function scenarios()
    {
        return $this->hasMany(Scenario::class);
    }

    public function codecs()
    {
        return $this->hasMany(Codec::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function getOwnerAttribute($value)
    {
        foreach ($this->permissions as $permissions)
            if ($permissions['name'] == 'PROJECT-OWNER')
                return $permissions['user'];
        return null;
    }

    public function activeScenario(Scenario $scenario)
    {
        $other_scenarios = $scenario->project()->first()->scenarios()->get();
        foreach ($other_scenarios as $s) {
            if ($s['is_active'] == true) {
                $s['is_active'];
                $s['is_active'] = false;
                $s->save();
            }
        }
        $scenario['is_active'] = true;
        $scenario->save();
    }

}
