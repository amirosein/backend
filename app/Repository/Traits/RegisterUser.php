<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 12/15/17
 * Time: 12:28 PM
 */

namespace App\Repository\Traits;

use App\Exceptions\GeneralException;
use App\Repository\Helper\MobileFactory;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait RegisterUser
{

    /**
     * @param Request $request
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function insertUser(Request $request)
    {
        return User::create([
            'legal' => $request->get('legal') ? true : false,
            'active' => false,
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
        ]);
        // Todo delete
        if ($request->has('legal') && $request->get('legal') == 1) {
            return $this->insertLegalUser($request);
        } else {
            return $this->insertRealUser($request);
        }
    }

    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     */
    public function validateRegisterUser(Request $request)
    {
        $messages = [
            'email.required' => 'لطفا ایمیل را وارد کنید',
            'email.email' => 'لطفا ایمیل را درست وارد کنید',
            'email.unique' => 'این ایمیل قبلا ثبت شده است',
            'password.required' => 'لطفا رمزعبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل باید ۶ کارکتر باشد',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ], $messages);

        if ($validator->fails())
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
        return;
        // Todo delete
        if ($request->has('legal') && $request->get('legal') == 1) {
            $this->validateRegisterLegal($request);
        } else {
            $this->validateRegisterReal($request);
        }

    }

    // Todo delete
    private function validateRegisterReal(Request $request)
    {
        $messages = [
            'email.required' => 'لطفا ایمیل را وارد کنید',
            'email.email' => 'لطفا ایمیل را درست وارد کنید',
            'email.unique' => 'این ایمیل قبلا ثبت شده است',
            'password.required' => 'لطفا رمزعبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل باید ۶ کارکتر باشد',
            'other_info.json' => 'لطفا سایر اطلاعات را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'other_info' => 'json',
        ], $messages);

        if ($validator->fails())
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    // Todo delete
    private function validateRegisterLegal(Request $request)
    {
        $messages = [
            'org_interface_name.required' => 'لطفا نام رابط سازمان را وارد کنید',
            'org_interface_last_name.required' => 'لطفا نام خانوادگی رابط سازمان را وارد کنید',
            'org_interface_phone.required' => 'لطفا شماره تلفن ثابت رابط سازمان را وارد کنید',
            'org_interface_mobile.required' => 'لطفا شماره موبایل شخصی را وارد کنید',
            'type.required' => 'لطفا نوع حقوقی را وارد کنید',
            'org_name.required' => 'لطفا نام سازمان را وارد کنید',
            'reg_number.required' => 'لطفا شماره ثبت را وارد کنید',
            'ec_code.required' => 'لطفا کد اقتصادی را وارد کنید',

            'mobile.required' => 'لطفا شماره موبایل را وارد کنید',
            'mobile.regex' => 'لطفا شماره موبایل را درست وارد کنید',
            'mobile.unique' => ' شماره موبایل قبلا وجود دارد',
            'email.email' => 'لطفا ایمیل را درست وارد کنید',
            'email.unique' => 'این ایمیل قبلا ثبت شده است',
            'password.required' => 'لطفا رمزعبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل باید ۶ کارکتر باشد',
        ];

        $validator = Validator::make($request->all(), [
            'org_interface_name' => 'required',
            'org_interface_last_name' => 'required',
            'org_interface_phone' => 'required',
            'org_interface_mobile' => 'required',
            'type' => 'required',
            'org_name' => 'required',
            'reg_number' => 'required',
            'ec_code' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'mobile' => 'regex:/^\d{11}$/|unique:users|required',
        ], $messages);

        if ($validator->fails())
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    // Todo delete
    private function insertRealUser(Request $request)
    {
        MobileFactory::sendWelcome($request->get('mobile'));
        return User::create([
            'legal' => false,
            'active' => false,
            'mobile' => $request->get('mobile'),
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'other_info' => json_decode($request->get('other_info'), true)
        ]);
    }

    // Todo delete
    private function insertLegalUser(Request $request)
    {
        return User::create([
            'legal' => true,
            'active' => false,
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'other_info' => $request->only([
                'org_interface_name',
                'org_interface_last_name',
                'org_interface_phone',
                'org_interface_mobile',
                'type',
                'org_name',
                'reg_number',
                'ec_code'
            ])
        ]);
    }
}