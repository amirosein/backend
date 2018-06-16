<?php

namespace app\Http\Middleware;

use App\Exceptions\AuthException;
use App\Exceptions\GeneralException;
use Closure;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws GeneralException
     */
    public function handle($request, Closure $next)
    {
        $this->authenticate();

        return $next($request);
    }

    /**
     * @throws GeneralException
     */
    private function authenticate()
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin())
            throw new GeneralException(GeneralException::M_ACCESS_DENIED, GeneralException::ACCESS_DENIED);

    }
}
