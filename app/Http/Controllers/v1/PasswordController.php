<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Mail\ResetPassword;
use App\Repository\Helper\Response;
use App\ResetPasswordToken;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordController extends Controller
{
    public function sendLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'لطفا ایمیل را وارد کنید',
            'email.email' => 'لطفا ایمیل را درست وارد کنید',
        ]);
        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), 700);
        $token = ResetPasswordToken::where('email', $request->get('email'))->orderBy('created_at', 'DESC')->first();
        // TODO denial attack
        $user = User::where('email', $request->get('email'))->first();
        if (!$user)
            throw new  GeneralException('کاربر یافت نشد', 700);

        $token = md5(str_random());
        ResetPasswordToken::create([
            'token' => $token,
            'email' => $request->get('email'),
            'status' => 'new'
        ]);

        Mail::to($request->get('email'))->send(new ResetPassword($token));
        return Response::body(['success' => true]);
    }

    public function showResetForm($token)
    {
        $token = ResetPasswordToken::where('token', $token)->first();
        // TODO check time
        if (!$token || $token['status'] != 'new')
            return view('error', ['error' => 'لینک استفاده شده است']);
        $token['status'] = 'seen';
        $token->save();
        return view('auth.passwords.reset', compact('token'));
    }

    public function reset($token, Request $request)
    {
        $token = ResetPasswordToken::where('token', $token)->first();
        // TODO check time
        if (!$token || $token['status'] != 'seen')
            return view('error', ['error' => 'مهلت استفاده از لینک تمام شده یا لینک خراب است']);
        $user = User::where('email', $token['email'])->first();
        if (!$user)
            return view('error', ['error' => 'کاربر یافت نشد']);

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|confirmed|min:6',
        ], [
            'password.required' => 'لطفا رمزعبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل باید ۶ کارکتر باشد',
            'password.confirmed' => 'تکرار رمزعبور را درست وارد کنید',
        ]);
        if ($validator->fails()) {
            $token['status'] = 'new';
            $token->save();
            return redirect()->back()->withErrors($validator->errors());
        }
        $user['password'] = bcrypt($request->get('password'));
        $user->save();
        return view('success', ['message' => 'رمزعبور با موفقیت تغییر کرد.']);


    }
}
