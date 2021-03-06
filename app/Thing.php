<?php

namespace App;

use App\Repository\Services\LoraService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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

    protected $appends = ['last_seen_at', 'last_parsed_at', 'keys', 'owner'];
    protected $lora_thing;
    protected $last_parsed;
    protected $lora_activation;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'loc', 'description', 'period', 'interface', 'type', 'dev_eui', 'active', 'activation', 'keys'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'created_at', 'user_id', 'id', 'project_id', 'profile_id', 'codec', 'permissions'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
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
        $user = $this->user()->first();
        if ($user)
            return $user;
        return null;
    }

    public function getLastSeenAtAttribute($value)
    {
        try {
            if (!$this->lora_thing) {
                $loraService = resolve('App\Repository\Services\LoraService');
                $this->lora_thing = $loraService->getDevice($this->dev_eui);
            }
        } catch (\Exception $e) {
            Log::error("Lora Get Thing\t" . $this['dev_eui']);
            return "";
        }
        $time = $this->lora_thing->lastSeenAt;
        $status = 'success';
        if (lora_time($time)) {
            if (Carbon::now()->subMinutes(2 * $this->period)->gt(lora_time($time)))
                $status = 'warning';
            if (Carbon::now()->subMinutes(3 * $this->period)->gt(lora_time($time)))
                $status = 'danger';
            if (Carbon::now()->subMinutes(4 * $this->period)->gt(lora_time($time)))
                $status = 'secondary';
        } else
            $status = 'secondary';

        return ['status' => $status, 'time' => $time ? (string)lora_time($time) : ''];
    }

    public function getLastParsedAtAttribute($value)
    {
        try {
            if (!$this->core_thing) {
                $coreService = resolve('App\Repository\Services\CoreService');
                $this->last_parsed = $coreService->getThingLastParsed($this);
            }
            return $this->last_parsed ?
                $this->last_parsed : 0;
        } catch (\Exception $e) {
            Log::error("Core Get Thing\t" . $this['dev_eui']);
            return "";
        }
    }

    public function getKeysAttribute()
    {
        if ($this->activation == 'ABP' || $this->activation == 'JWT')
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
