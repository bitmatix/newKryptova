<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;
class AuthCheck
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
        if(Auth::user() == 'null' || empty(Auth::user()) || Auth::user()  == '' || Auth::user()  ==null)
        {
           return route('login');
        }
        else{
            if(auth()->user()->main_user_id == 0){
                if(!is_null(\App\Application::where('user_id',auth()->user()->id)->first())){
                    if(\App\Application::where('user_id',auth()->user()->id)->first()->status == 4 || \App\Application::where('user_id',auth()->user()->id)->first()->status == 5 || \App\Application::where('user_id',auth()->user()->id)->first()->status == 6){}
                }else{
                    return redirect()->route('dashboardPage');
                }
            }
        }
        return $next($request);
    }
}
