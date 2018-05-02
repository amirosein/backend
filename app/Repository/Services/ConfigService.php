<?php
/**
 * Created by PhpStorm.
 * User: Sajjad Rahnama
 * Date: 8/4/18
 * Time: 12:50 PM
 */

namespace App\Repository\Services;

use App\Exceptions\AuthException;
use App\Exceptions\GeneralException;
use App\Repository\Traits\RegisterUser;
use App\Repository\Traits\UpdateUser;
use App\UserConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConfigService
{


    /**
     * @param Collection $data
     * @return string
     * @throws GeneralException
     */
    public function setWidgetChart(Collection $data)
    {
        $this->validateWidgetChart($data);
        $config = Auth::user()->config()->first();
        if (!$config)
            $config = UserConfig::create(['user_id' => Auth::user()['_id']]);
        $config_widgets = isset($config['widgets']) ? $config['widgets'] : [];
        $thing = Auth::user()->things()->with(['thing' => function ($query) use ($data) {
            $query->where('dev_eui', $data->get('devEUI'));
        }])->first();
        if (!$thing)
            throw new GeneralException('شی یافت نشد', GeneralException::NOT_FOUND);
        $widget = $data->only([
            'title',
            'devEUI',
            'key',
            'window',
        ])->map(function ($value) {
            return $value ?: 0;
        })->toArray();
        if ($data->get('id'))
            $config_widgets[$data->get('id')] = $widget;
        else
            $config_widgets[] = $widget;
        $config['widgets'] = $config_widgets;
        $config->save();
        return $config['widgets'];

    }

    /**
     * @param Collection $data
     * @return string
     */
    public function deleteWidgetChart(Collection $data)
    {
        $config = Auth::user()->config()->first();
        if (!$config)
            $config = UserConfig::create(['user_id' => Auth::user()['_id']]);
        $config_widgets = isset($config['widgets']) ? $config['widgets'] : [];
        unset($config_widgets[$data->get('id')]);
        $config['widgets'] = $config_widgets;
        $config->save();

    }

    public function validateWidgetChart($data)
    {
        $messages = [
            'title.*' => 'لطفا عنوان ویجت را وارد کنید',
            'devEUI.required' => 'لطفا شناسه شی را وارد کنید',
            'devEUI.regex' => 'لطفا شناسه شی را درست وارد کنید',
            'key.*' => 'لطفا کلید سنسور را وارد کنید',
        ];

        $validator = Validator::make($data->all(), [
            'title' => 'required|string|max:255',
            'devEUI' => 'required|regex:/^[0-9a-fA-F]{16}$/',
            'key' => 'required',

        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

}