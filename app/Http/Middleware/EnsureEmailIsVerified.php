<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user() && !$request->user()->hasVerifiedEmail()){
            return response()->json([
                'message'=>'يرجى تأكيد بريدك الالكتروني اولا'
            ],403);
        }
        return $next($request);
    }
}
