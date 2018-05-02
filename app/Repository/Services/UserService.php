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
use Illuminate\Http\Request;
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

}