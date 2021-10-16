<?php

namespace App\Http\Controllers\Auth;

use App\Admin;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Mail\AdminOtpMail;
use App\User;
use App\Adminlog;
use App\AdminAction;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Session;
use DB;
use Str;
use Validator;
use Carbon\Carbon;
use App\Mail\SendForgetEmailAdmin;

class AdminAuthController extends AdminController
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'firstname' => 'required|max:255',
            'lastname' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    public function getLogin()
    {
        // dd(\Hash::make('123456'));
        return view('auth.adminLogin');
    }

    /**
     * Show the application loginprocess.
     *
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
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

        $userData = Admin::where('email', $request->input('email'))->first();
        if (!is_null($userData)) {
            if ($userData->is_password_expire == '1') {
                return back()->with('error', 'Your password is expire please forgot.');
            }

            if ($userData && \Hash::check($request->input('password'), $userData->password)) {

                // if otp required is null
                if ($userData->is_otp_required == '0') {
                    if (auth()->guard('admin')->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
                        addToLog('OTP Log.', $request->all(), 'general', $userData->id);
                        $admins  = DB::table("admins")->where("id", auth()->guard('admin')->user()->id)->first();
                        Session::put('user_name', $admins->name);
                        $notification = [
                            'user_id' => auth()->guard('admin')->user()->id,
                            'sendor_id' => auth()->guard('admin')->user()->id,
                            'type' => 'admin',
                            'title' => 'Account Login',
                            'body' => 'Logged in successfully',
                            'url' => '/admin/dashboard',
                            'is_read' => '0'
                        ];

                        $realNotification = addNotification($notification);
                        $ArrRequest = [ 'email' => $request->email, 'password' => $request->password];
                        addAdminLog(AdminAction::LOGIN, null,$ArrRequest,"Admin Login");
                        return redirect()->route('dashboard');
                    } else {
                        \Session::put('error', 'Wrong login details , Please try again');
                        return redirect()->back();
                    }
                }

                $OTP = rand(111111, 999999);
                $generateOTP = Admin::where('email', $request->input('email'))->update(['otp' => $OTP]);
                $content = [
                    'otp' => $OTP,
                ];
                $admin = Admin::where('email', $request['email'])->first();
                \Session::put('email', $request->input('email'));
                \Session::put('password', $request->input('password'));

                \Mail::to($request->input('email'))->send(new AdminOtpMail($admin));
                \Session::put('success', 'Enter the OTP received on your email id.');
                return redirect()->route('admin.kryptova-otp');
                try {
                } catch (\Exception $e) {
                    \Session::put('error', 'Mail has not been sent due to some technical error. Please resend the OTP.');
                    return redirect()->route('admin.kryptova-otp');
                }

                $response = $this->sendOtpSMS($userData);

                // if($response->type == 'success') {
                if ($response == true) {
                    \Session::put('email', $request->input('email'));
                    \Session::put('password', $request->input('password'));
                    Session::put('success', 'You will receive the OTP at the time of your login on your mobile number as well as email id.');
                    return redirect()->route('admin.kryptova-otp');
                } else {
                    Session::put('error', 'Something went wrong. problem in OTP generation.');
                    return view('auth.adminLogin');
                }
            } else {
                return back()->with('error', 'your username and password are wrong.');
            }
        } else {
            return back()->with('error', 'Your email is not registered with us.');
        }
    }

    /**
     * Show the application logout.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        addAdminLog(AdminAction::LOGOUT,null, array(), "Admin Logout");
        auth()->guard('admin')->logout();
        // Session::flush();        
        // Session::put('success','you are logout Successfully');
        return redirect()->to('/admin/login');
    }

    public function resendotp()
    {
        $user = Admin::where('email', \Session::get('email'))->first();
        $OTP = rand(111111, 999999);
        $generateOTP = Admin::where('email', \Session::get('email'))->update(['otp' => $OTP]);

        $response = $this->sendOtpSMS($user);

        // if($response->type == 'success') {
        if ($response == true) {
            \Session::put('success', 'OTP has been resent on your email id.');
            return redirect()->route('admin.kryptova-otp');
        } else {
            \Session::put('error', 'OTP send fail, Please try again.');
            return redirect()->route('admin.kryptova-otp');
        }
    }

    public function otpform()
    {
        return view('auth.adminotpform');
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

        $otp = implode("",$request->otp);

        $userData = Admin::where('email', \Session::get('email'))->first();
        if (isset($userData->otp) && $userData->otp != $otp) {

            \Session::put('error', 'Wrong OTP , Please try again');
            return redirect()->back();
        }

        if (auth()->guard('admin')->attempt(['email' => \Session::get('email'), 'password' => \Session::get('password')])) {
            addToLog('OTP Log.', $request->all(), 'general', $userData->id);

            Admin::where('email', \Session::get('email'))->update(['otp' => '']);


            if ($userData->is_active == '0') {
                \Session::put('maintenance', true);
            }
            $user = auth()->guard('admin')->user();
            Session::put('user_name', $user->name);
            \Session::forget('email');
            \Session::forget('password');
            $notification = [
                'user_id' => auth()->guard('admin')->user()->id,
                'sendor_id' => auth()->guard('admin')->user()->id,
                'type' => 'admin',
                'title' => 'Account Login',
                'body' => 'Logged in successfully',
                'url' => '/dashboard',
                'is_read' => '0'
            ];

            $realNotification = addNotification($notification);
            return redirect()->route('dashboard');
        } else {
            \Session::put('error', 'Wrong OTP , Please try again');
            return redirect()->back();
        }
    }

    public function sendOtpSMS($user)
    {
        $OTP = rand(111111, 999999);
        $generateOTP = Admin::where('email', $user->email)->update(['otp' => $OTP]);
        $message = "Use " . $OTP . " to sign in to your Kryptova CRM account. Never forward this code.";

        $content = [
            'otp' => $OTP,
        ];
        try {
            \Mail::to($user->email)
                ->send(new AdminOtpMail($content));
            \Session::put('success', 'OTP has been successfully sent. Please check your registered mail.');
        } catch (\Exception $e) {
        }

        return true;
    }

    public function adminForgetPassword(Request $request)
    {
        return view('auth.admin_password_email');
    }

    public function adminForgetEmail(Request $request)
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

        $user = DB::table('admins')->where('email', $request->email)->first();
        //Check if the user exists
        if ($user == NULL) {
            return redirect()->back()->with(['error' => 'User does not exist']);
        }
        //Create Password Reset Token
        DB::table('admin_password_resets')->insert([
            'email' => $request->email,
            'token' => Str::random(60),
            'created_at' => Carbon::now()
        ]);
        //Get the token just created above
        $tokenData = DB::table('admin_password_resets')->where('email', $request->email)->first();
        try {
            \Mail::to($request->email)
                ->send(new SendForgetEmailAdmin($tokenData));
            return redirect()->back()->with(['status' => 'A reset link has been sent to your email address.']);
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => 'A Network Error occurred. Please try again.']);
        }
    }

    public function adminForgetPasswordForm(Request $request, $token)
    {
        return view('auth.admin_password_reset', compact('token'));
    }

    public function adminForgetPasswordFormPost(Request $request)
    {
        //Validate input
        $this->validate(
            $request,
            [
                'email' => 'required|string|email|max:255|exists:admins,email',
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
            $tokenData = DB::table('admin_password_resets')->where('token', $request->token)->first();
            // Redirect the user back if the email is invalid
            if (!$tokenData) {
                return redirect()->back()->with(['error' => 'Token not found']);
            }
            $user = Admin::where('email', $tokenData->email)->first();
            // Redirect the user back if the email is invalid
            if (!$user) {
                return redirect()->back()->with(['error' => 'Email not found']);
            }
            $user->password = \Hash::make($request->password);
            $user->is_password_expire = '0';
            $user->update();
            //Delete the token
            DB::table('admin_password_resets')->where('email', $user->email)->delete();
            Session::put('success', 'Your Password Reset Successfully');
            return redirect()->to('/admin/login');
        } catch (Exception $e) {
            Session::put('error', 'Something went Wrong!');
            return redirect()->to('/admin/login');
        }
    }
}
