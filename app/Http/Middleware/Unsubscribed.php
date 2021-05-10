<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Unsubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // if user has active subscription and is autheticated user then redirect to home
        if(optional($request->user())->hasActiveSubscription())
        {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
