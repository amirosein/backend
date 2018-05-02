<?php

function lora_time($time)
{
    if (!$time)
        return '';
    $time = str_replace('T', ' ', $time);
    $time = substr($time, 0, strpos($time, '.'));
    return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $time, 'UTC')
        ->timezone(\Illuminate\Support\Facades\Config::get('app.timezone'));
}