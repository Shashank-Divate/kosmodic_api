<?php

namespace App\Http\Middleware;

use App\Helpers\TokenPayloadHelper;
use Closure;
use Illuminate\Http\Request;

class AuthenticateDoctor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('token');

        $result = array(
            'success' => false,
            'error' => array(
                'error_code' => 'T001',
                'message' => 'Access Denied'
            )
        );

        if (!empty($token) && $token != "") {
            $userRole = TokenPayloadHelper::getUserRole($token);
            if ($userRole == 'doctor') {
                return $next($request);
            } else {
                return response()->json($result, 401);
            }
        } else {
            return response()->json($result, 401);
        }
    }
}
