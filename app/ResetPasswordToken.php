<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Model;

class ResetPasswordToken extends Eloquent
{
    protected $guarded = [];
}
