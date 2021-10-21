<?php

namespace App\Http\Controllers\Bank;

use DB;
use Str;
use App\User;
// use App\Agent;
use Validator;
use Session;
use Carbon\Carbon;
// use App\Mail\SendForgotEmailAgent;
use App\Mail\SendForgotEmailBank;
use App\Mail\AgentOtpMail;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Bank\BankController;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use \Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\BankUsers;
use App\Providers\RouteServiceProvider;
use App\Application;

class BankController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    protected $agentUser;
    public function __construct()
    {
        view()->share('bankUserTheme', 'layouts.bank.default');
        

        $this->middleware(function ($request, $next) {
            $userData = BankUsers::where('bank_users.id', auth()->guard('bank_user')->user()->id)
                ->first();

            view()->share('userData', $userData);
            return $next($request);
        });
        $this->user = new User;
        

        
    }

    public function dashboard(Request $request)
    {   //dd('Dashboard');
        //auth()->guard('bank_user')->user()
        return view('bank.dashboard');
    }
}
