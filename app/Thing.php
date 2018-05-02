<?php

namespace App;

use App\Repository\Services\LoraService;
use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed loc
 * @property mixed period
 * @property mixed description
 * @property mixed name
 */
class Thing extends Eloquent
{

    protected $appends = ['last_seen_at', 'keys', 'owner'];
    protected $lora_thing;
    protected $lora_activation;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'loc', 'description', 'period', 'interface', 'type', 'dev_eui'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'created_at', 'user_id', 'id', 'project_id', 'profile_id', 'codec'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'item_id')
            ->where('item_type', 'thing')
            ->with('user');
    }

    public function profile()
    {
        return $this->belongsTo(ThingProfile::class);
    }

    public function getCodecAttribute($value)
    {
        if (!$value)
            return "";
        return $value;
    }

    public function getOwnerAttribute($value)
    {
        foreach ($this->permissions as $permissions)
            if ($permissions['name'] == 'THING-OWNER')
                return $permissions['user'];
        return null;
    }

    public function getLastSeenAtAttribute($value)
    {
        if (!$this->lora_thing) {
            $loraService = resolve('App\Repository\Services\LoraService');
            $this->lora_thing = $loraService->getDevice($this->dev_eui);
        }
        $time = $this->lora_thing->lastSeenAt;
        $status = 'green';
        if (Carbon::now()->subSecond(2 * $this->period) > $time)
            $status = 'orange';
        if (Carbon::now()->subSecond(3 * $this->period) > $time)
            $status = 'red';
        if (Carbon::now()->subSecond(4 * $this->period) > $time)
            $status = 'gray';
        return ['status' => $status, 'time' => $time ? (string)lora_time($time) : ''];
    }

    public function getKeysAttribute()
    {
        if ($this->type == 'ABP')
            return isset($this->attributes['keys']) ? $this->attributes['keys'] : [];
        try {
            if (!$this->lora_activation) {
                $loraService = resolve('App\Repository\Services\LoraService');
                $this->lora_activation = $loraService->getActivation($this->dev_eui);
            }
            return array_merge(json_decode(json_encode($this->lora_activation, true)), $this->attributes['keys']);
        } catch (\Exception $e) {
            return isset($this->attributes['keys']) ? $this->attributes['keys'] : [];
        }

    }
}
