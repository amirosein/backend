<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\AuthException;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Repository\Helper\Response;
use App\Repository\Services\UserService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private $userService;

    /**
     * UserController constructor.
     * @param userService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function register(Request $request)
    {
        //AuthValidation::register($request);


        $this->userService->validateRegisterUser($request);

        $user = $this->userService->insertUser($request);
        # generate token
        # $token = JWTAuth::fromUser($user);

        Mail::to($user['email'])->send(new EmailVerification($user));

        return Response::body(['message' => 'ایمیل فعال سازی برای شما فرستاده شد']);
    }

    /**
     * @param Request $request
     * @return array
     * @throws AuthException
     * @throws GeneralException
     */
    public function login(Request $request)
    {
        $request->merge($request->json()->all());
        $validator = $this->loginValidator($request);
        if ($validator->fails()) {
            throw new GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
        }

        $token = $this->userService->generateToken($request);
        $user = Auth::user();
        if (!$user->active)
            throw new AuthException(AuthException::M_USER_NOT_ACTIVE, AuthException::UNAUTHORIZED);

        return Response::body(compact('user', 'token'));
    }

    /**
     * @param Request $request
     * @return array
     */
    public function refresh(Request $request)
    {

        try {
            $token = $this->userService->refreshToken();
            $user = User::where('_id', JWTAuth::getPayload($token)->toArray()['sub'])->first();
        } catch (\Exception $e) {
            return Response::body($e->getMessage(), $e->getCode());
        }
        $request->headers->set('authorization', 'Bearer ' . $token);
        return Response::body(['token' => $token, 'user' => $user]);
    }

    /*
     * @return Validator
     */
    public function loginValidator($request)
    {
        $captcha = env('CAPTCHA_ENABLE', 0);
        $data = $request->only(['email', 'password', 'g-recaptcha-response']);
        return Validator::make($data, [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'g-recaptcha-response' => $captcha ? 'required|captcha' : ''
        ]);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            JWTAuth::invalidate($token);
        } catch (\Exception $e) {
        }
        $message = "با موفقیت خارج شدید";
        return Response::body($message);
    }

    /**
     * @param User $user
     * @param $token
     * @return void
     */
    public function verifyEmail(User $user, $token)
    {
        if (md5($user['email_token']) == $token) {
            $user['active'] = true;
            $user->unset('email_token');
            $user->save();
            return view('auth.verified');
        }
        return view('auth.not_verified');
    }
}
