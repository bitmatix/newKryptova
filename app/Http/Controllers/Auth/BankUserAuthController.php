<?php

namespace App\Http\Controllers\Auth;


use App\Jobs\SendEmailJob;
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
use App\Http\Controllers\BankFrontController;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use \Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

use App\BankUsers;


use App\Providers\RouteServiceProvider;
use App\Application;



class BankUserAuthController extends BankFrontController
{
    // protected $redirectTo = '/bank_user/login';

    public function __construct()
    {
        //$this->middleware('bankauth');
        // $this->middleware('guest', ['except' => 'logout']);
    }

    public function getBankUserLogin()
    {
        // dd(auth()->guard('bank_user')->check());
        return view('auth.bankUser.login');
    }

    public function postBankUserLogin(Request $request)
    {
    	$this->validate($request, [
    		"email" => "required",
    		"password" => "required|min:8",
    	]);

        $bank_user = BankUsers::where('email', $request->input('email'))
                    ->where('is_active', '1')
                     ->first();

    	if ($bank_user && \Hash::check($request->input('password'), $bank_user->password)) {
    		if(auth()->guard('bank_user')->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])){

                    auth()->guard('bank_user')->attempt(['email' => $request->input('email'), 'password' => $request->input('password')]);
                   $user = auth()->guard('bank_user')->user();
                    return redirect()->to('/bank/dashboard');
    			} else {

    				\Session::put('error', 'Wrong login details , Please try again');
                    return redirect()->back();

    		}
    	}else {
            return back()->with('error', 'your username and password are wrong.');
        }
    }




    public function logout()
    {
    	auth()->guard('bank_user')->logout();
 		return redirect()->to('/bank/login');
    }
    public function bankForgetPassword(Request $request)
    {
        return view('auth.bankUser.bank_password_email');
    }

    public function bankForgetEmail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'g-recaptcha-response' => 'required'
        ]);

        $request_url = 'https://www.google.com/recaptcha/api/siteverify';

        $request_data = [
            'secret' => config('app.captch_secret'),
            'response' => $request['g-recaptcha-response']
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response_body = curl_exec($ch);

        curl_close($ch);

        $response_data = json_decode($response_body, true);

        if ($response_data['success'] == false) {
            \Session::put('error', 'Recaptcha verification failed.');

            return redirect()->back();
        }

        $user = BankUsers::where(['email' => $request->email])->first();
        //Check if the user exists
        if ($user == NULL) {
            return redirect()->back()->with(['error' => 'User does not exist']);
        }
        DB::table('banks_password_resets')->where('email', $request->email)->delete();
        //Create Password Reset Token
        DB::table('banks_password_resets')->insert([
            'email' => $request->email,
            'token' => Str::random(60),
            'created_at' => Carbon::now()
        ]);
        //Get the token just created above
        $tokenData = DB::table('banks_password_resets')->where('email', $request->email)->first();
        try {
            /*$mailData = [
                'email_to' => $request->email,
                'init_email_class' => new SendForgotEmailBank($tokenData)
            ];
            dispatch(new SendEmailJob($mailData));*/

            \Mail::to($request->email)->send(new SendForgotEmailBank($tokenData));
            return redirect()->back()->with(['status' => 'A reset link has been sent to your email address.']);
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => 'A Network Error occurred. Please try again.']);
        }
    }

    public function bankForgetPasswordForm(Request $request, $token)
    {
        return view('auth.bankUser.bank_password_reset', compact('token'));
    }

    public function bankForgetPasswordFormPost(Request $request)
    {
        //Validate input
        $this->validate(
            $request,
            [
                'email' => 'required|string|email|max:255|exists:bank_users,email',
                'password' => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
                'password_confirmation' => "same:password",
                'g-recaptcha-response' => 'required'
            ],
            ['password.regex' => 'Enter valid format.(One Upper,Lower,Numeric,and Special character.)']
        );

        $request_url = 'https://www.google.com/recaptcha/api/siteverify';

        $request_data = [
            'secret' => config('app.captch_secret'),
            'response' => $request['g-recaptcha-response']
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response_body = curl_exec($ch);

        curl_close($ch);

        $response_data = json_decode($response_body, true);

        if ($response_data['success'] == false) {
            \Session::put('error', 'Recaptcha verification failed.');

            return redirect()->back();
        }

        try {
            $tokenData = DB::table('banks_password_resets')->where('token', $request->token)->first();
            // Redirect the user back if the email is invalid
            if (!$tokenData) {
                return redirect()->back()->with(['error' => 'Token not found']);
            }
            $user = BankUsers::where(['email' => $tokenData->email])->first();
            // Redirect the user back if the email is invalid
            if (!$user) {
                return redirect()->back()->with(['error' => 'Email not found']);
            }
            $user->password = \Hash::make($request->password);
            $user->update();
            //Delete the token
            DB::table('agents_password_resets')->where('email', $user->email)->delete();
            Session::put('success', 'Your Password Reset Successfully');
            return redirect()->to('/bank/login');
        } catch (Exception $e) {
            Session::put('error', 'Something went Wrong!');
            return redirect()->to('/bank/login');
        }
    }

    public function otpform()
    {
        return view('auth.bankUser.otpform');
    }

    public function sendOtpSMS($user)
    {
        $OTP = rand(111111, 999999);
        $generateOTP = Agent::where(['email' => $user->email])->update(['login_otp' => $OTP]);
        $message = "Use " . $OTP . " to sign in to your PAYSTUDIO CRM account. Never forward this code.";

        $content = [
            'otp' => $OTP,
            'name' => $user->name
        ];
        try {
            /*$mailData = [
                'email_to' => $user->email,
                'init_email_class' => new AgentOtpMail($content)
            ];
            dispatch(new SendEmailJob($mailData));*/

            \Mail::to($user->email)->send(new AgentOtpMail($content));
            \Session::put('success', 'OTP has been successfully sent. Please check your registered mail.');
        } catch (\Exception $e) {
            //dd($e->getMessage());
        }

        return true;
    }

    public function resendotp()
    {
        $user = Agent::where(['email' => \Session::get('email')])->first();

        if (empty($user)) {
            \Session::put('error', 'OTP send fail, Please try again.');
            return redirect()->route('rp.paystudio-otp');
        }

        $OTP = rand(111111, 999999);
        $generateOTP = Agent::where(['email' => \Session::get('email')])->update(['login_otp' => $OTP]);

        $response = $this->sendOtpSMS($user);

        // if($response->type == 'success') {
        if ($response == true) {
            \Session::put('success', 'OTP has been successfully sent. Please check your registered mail.');
            return redirect()->route('rp.paystudio-otp');
        } else {
            \Session::put('error', 'OTP send fail, Please try again.');
            return redirect()->route('rp.paystudio-otp');
        }
    }

    public function checkotp(Request $request)
    {
        $this->validate($request, [
            'otp' => 'required',
            'g-recaptcha-response' => 'required'
        ]);

        $request_url = 'https://www.google.com/recaptcha/api/siteverify';

        $request_data = [
            'secret' => config('app.captch_secret'),
            'response' => $request['g-recaptcha-response']
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response_body = curl_exec($ch);

        curl_close($ch);

        $response_data = json_decode($response_body, true);

        if ($response_data['success'] == false) {
            \Session::put('error', 'Recaptcha verification failed.');

            return redirect()->back();
        }

        $userData = Agent::where(['email' => \Session::get('email')])->first();

        if (isset($userData->login_otp) && $userData->login_otp != $request->otp) {

            \Session::put('error', 'Wrong OTP , Please try again');
            return redirect()->back();
        }

        if (auth()->guard('agentUser')->attempt(['email' => \Session::get('email'), 'password' => \Session::get('password')])) {
            Agent::where(['email' => \Session::get('email')])->update(['login_otp' => '']);
            $user = auth()->guard('agentUser')->user();
            Session::put('user_name', $user->name);
            \Session::forget('email');
            \Session::forget('password');
            return redirect()->route('rp.dashboard');
        } else {
            \Session::put('error', 'Wrong OTP , Please try again');
            return redirect()->back();
        }
    }
}
