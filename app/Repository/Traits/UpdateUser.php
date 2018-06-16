<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 12/15/17
 * Time: 12:28 PM
 */

namespace App\Repository\Traits;

use App\Exceptions\GeneralException;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

trait UpdateUser
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function updateUser(Request $request)
    {
        if ($request->get('legal')) {
            $this->updateRealUser($request);
            return $this->updateLegalUser($request);
        } else {
            return $this->updateRealUser($request);
        }
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();
        if ($request->get('new_password') && Hash::check($request->get('password'), $user['password']))
            $user['password'] = Hash::make($request->get('new_password'));
        else
            throw new GeneralException('اطلاعات را درست وارد کنید', GeneralException::VALIDATION_ERROR);
        $user->save();
        return $user;
    }

    public function validateUpdateUser(Request $request)
    {
        if ($request->has('legal') && $request->get('legal') == 1) {
            $this->validateUpdateLegal($request);
        } else {
            $this->validateUpdateReal($request);
        }

    }

    private function validateUpdateReal(Request $request)
    {
        $messages = [
            'name.filled' => 'لطفا نام را وارد کنید',
            'password.filled' => 'لطفا رمزعبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل باید ۶ کارکتر باشد',
            'other_info.filled' => 'لطفا سایر اطلاعات را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'filled|string|max:255',
            'password' => 'filled|string|min:6',
            'other_info' => 'json',
        ], $messages);

        if ($validator->fails())
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    private function validateUpdateLegal(Request $request)
    {
        $data = collect(json_decode($request->get('legal_info'), true));
        $messages = [
            'org_interface_name.filled' => 'لطفا نام رابط سازمان را وارد کنید',
            'org_interface_last_name.filled' => 'لطفا نام خانوادگی رابط سازمان را وارد کنید',
            'org_interface_phone.filled' => 'لطفا شماره تلفن ثابت رابط سازمان را وارد کنید',
            'org_interface_mobile.filled' => 'لطفا شماره موبایل شخصی را وارد کنید',
            'type.filled' => 'لطفا نوع حقوقی را وارد کنید',
            'org_name.filled' => 'لطفا نام سازمان را وارد کنید',
            'reg_number.filled' => 'لطفا شماره ثبت را وارد کنید',
            'ec_code.filled' => 'لطفا کد اقتصادی را وارد کنید',
        ];

        $validator = Validator::make($data->all(), [
            'org_interface_name' => 'filled',
            'org_interface_last_name' => 'filled',
            'org_interface_phone' => 'filled',
            'org_interface_mobile' => 'filled',
            'type' => 'filled',
            'org_name' => 'filled',
            'reg_number' => 'filled',
            'ec_code' => 'filled',
        ], $messages);

        if ($validator->fails())
            throw new UserException($validator->errors()->first(), UserException::C_UE);
    }


    private function updateRealUser(Request $request)
    {
        $user = Auth::user();

        if ($request->get('name')) {
            $user->name = $request->get('name');
        }
        if ($request->get('mobile')) {
            $user->mobile = $request->get('mobile');
        }
        if ($request->get('phone')) {
            $user->phone = $request->get('phone');
        }

        $updated_other_info = json_decode($request->get('other_info'), true) ?: [];

        $other_info = $user['other_info'];
        foreach ($updated_other_info as $key => $value) {
            $other_info[$key] = $value;
        }
        $user->other_info = $other_info;
        if (!$request->get('legal')) {
            unset($user['legal_info']);
            $user['legal'] = false;
        } else
            $user['legal'] = true;


        $user->save();

        return $user;
    }

    private function updateLegalUser(Request $request)
    {
        $user = Auth::user();
        $data = collect(json_decode($request->get('legal_info'), true));

        $updated_legal_info = $data->only([
            'org_interface_name',
            'org_interface_last_name',
            'org_interface_phone',
            'org_interface_mobile',
            'type',
            'org_name',
            'reg_number',
            'ec_code'
        ]);

        $legal_info = $user['legal_info'];
        foreach ($updated_legal_info as $key => $value) {
            $legal_info[$key] = $value;
        }
        $user->legal_info = $legal_info;

        $user->save();

        return $user;
    }


}