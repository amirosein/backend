<?php
/**
 * Created by PhpStorm.
 * User: Sajjad Rahnama
 * Date: 8/12/17
 * Time: 12:39 PM
 */

namespace App\Repository\Services;

use App\Exceptions\AuthException;
use App\Exceptions\GeneralException;
use App\Repository\Traits\RegisterUser;
use App\Repository\Traits\UpdateUser;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\UTCDateTime;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService
{
    use RegisterUser;
    use UpdateUser;


    const GRAVATAR_BASE_URL = 'https://www.gravatar.com/avatar/';

    /**
     * @param Request $request
     * @return string
     * @throws AuthException
     */
    public function generateToken(Request $request): string
    {
        # verify user with db and generate token
        $credentials = $request->only('email', 'password');
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            throw new AuthException(AuthException::M_INVALID_CREDENTIALS, AuthException::UNAUTHORIZED);
        }

        return $token;
    }

    public function activateImpersonate(User $user)
    {
        $main_user_id = JWTAuth::getPayload()->toArray();
        $main_user = isset($main_user_id['impersonate_id']) ? User::where('_id', $main_user_id['impersonate_id'])->first() : null;
        if (!$main_user)
            $main_user = Auth::user();
        $token = JWTAuth::fromUser($user, ['impersonate_id' => $main_user['_id']]);
        return ['user' => $user, 'token' => $token];
    }

    public function deactivateImpersonate()
    {
        $main_user_id = JWTAuth::getPayload()->toArray();
        $main_user = isset($main_user_id['impersonate_id']) ? User::where('_id', $main_user_id['impersonate_id'])->first() : null;
        if (!$main_user)
            $main_user = Auth::user();
        $main_user['impersonated'] = false;
        return ['user' => $main_user, 'token' => JWTAuth::fromUser($main_user)];
    }


    /**
     * @return string
     * @throws GeneralException
     */
    public function refreshToken(): string
    {
        try {
            return JWTAuth::refresh(JWTAuth::getToken());
        } catch (TokenBlacklistedException $exception) {
            throw new GeneralException(GeneralException::M_UNKNOWN, 701);
        }
    }

    public function updatePackage(User $user, $package)
    {
        $remaining = ($user['package']['time'] - Carbon::createFromTimestamp($user['package']['start_date']->toDateTime()->getTimestamp())->diffInDays()) * $user['package']['node_num'];
        $package['time'] += (int)$remaining / $package['node_num'];
        $package['start_date'] = new UTCDateTime(Carbon::now());
        $user['package'] = $package;
        $user->save();
    }

}