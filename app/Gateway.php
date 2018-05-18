<?php

namespace App;

use App\Exceptions\LoraException;
use Carbon\Carbon;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Eloquent
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'mac', 'user_id', 'description', 'loc', 'altitude', '_id'
    ];
    protected $appends = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at', 'created_at', 'user_id', 'lora_info'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function load_last_seen()
    {
        try {
            $info = resolve('App\Repository\Services\LoraService')->getGW($this['mac']);
            $time = lora_time($info->lastSeenAt);
            $last_seen = [
                'time' => (string)lora_time($info->lastSeenAt),
                'status' => Carbon::now()->subHour() > $time ? 'red' : 'green'
            ];
            $this['last_seen_at'] = $last_seen;
        } catch (LoraException $e) {
            $this['last_seen_at'] = ['time' => '', 'status' => ''];
        }
    }

}
