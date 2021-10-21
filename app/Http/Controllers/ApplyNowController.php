<?php

namespace App\Http\Controllers;

use DB;
use URL;
use Log;
use Session;
use App\User;
use App\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\userRegisterMail;
use Illuminate\Validation\Rule;

class ApplyNowController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = new User;
    }

    /**
     * show registration form
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('applynow');
    }

    // ================================================
    /* method : store
    * @param  :
    * @Description : store merchant details
    */ // ==============================================
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|max:50',
                'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
                'mobile_no' => 'required|max:14',
                'password' => 'required||min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
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

        $input = \Arr::except($request->all(), array('_token', '_method'));
        if(isset($input['RP']) && $input['RP'] != ''){
            $agentData = Agent::where('referral_code', $input['RP'])->first();
            $input['agent_id'] = $agentData->id;
        }else{
            $input['agent_id'] = NULL;
        }

        $uuid = Str::uuid()->toString();

        unset($input['password_confirmation']);

        $input['uuid'] = $uuid;
        $input['token'] = Str::random(40) . time();
        $input['is_active'] = '0';

        DB::beginTransaction();
        try {

            $userData = $this->user->storeData($input);

            if(isset($input['RP']) && $input['RP'] != ''){
                $notification = [
                    'user_id' => $agentData->id,
                    'sendor_id' => $userData->id,
                    'type' => 'RP',
                    'title' => 'Merchant Registered',
                    'body' => 'New Merchant Registered Successfully.',
                    'url' => '/rp/user-management',
                    'is_read' => '0'
                ];

                $realNotification = addNotification($notification);
            }
            
            $notification = [
                'user_id' => $userData->id,
                'sendor_id' => $userData->id,
                'type' => 'user',
                'title' => 'Registration',
                'body' => 'Registered Successfully.',
                'url' => '/login',
                'is_read' => '0'
            ];

            $realNotification = addNotification($notification);
            DB::commit();
            $data = [];
            $data['token'] = $input['token'];
            $data['name'] = $input['name'];
            Mail::to($input['email'])->send(new userRegisterMail($data));
        } catch (\Exception $e) {
            //echo $e->getMessage();
            Log::info($e->getMessage());
            DB::rollBack();
            return redirect()->route('register')
                ->with(['error' => 'Something wrong! try Again later.']);
        }
        return redirect()->route('login')
            ->with(['success' => 'Your account has been registered successfully. You will receive an email shortly to activate your account.']);
    }

    public function verifyUserEmail($token)
    {
        $check = DB::table('users')->where('token', $token)->first();
        if (!is_null($check)) {
            if ($check->is_active == 1) {
                return redirect()->to('login')
                    ->with('success', "user are already actived.");
            }

            $user = $this->user::where('token', $token)->first();

            $token_api  = $user->createToken('kryptova')->plainTextToken;

            $this->user::where('token', $token)->update(['is_active' => '1', 'token' => '', 'email_verified_at' => date('Y-m-d H:i:s'), 'api_key' => $token_api]);
            return redirect()->to('user/confirm-mail-active')
                ->with('success', "Your account has been activated successfully. <br> Please login using your email id and password.");
        }
        return redirect()->to('login')
            ->with('error', "Your token is invalid.");
    }

    public function verifyUserChangeEmail(Request $request)
    {
        $check = DB::table('users')->where('id', $request->id)->first();
        if (!is_null($check)) {
            if (!empty($check->email_changes)) {
                $this->user::where('id', $request->id)->update(['email' => $check->email_changes, 'token' => '', "email_changes" => ""]);
                \Session::put('success', 'Your new email has been changed successfully.');
                return redirect()->to('setting');
            } else {
                \Session::put('error', 'Email already changed');
                return redirect()->to('setting');
            }
        } else {
            return redirect()->to('login')->with('error', "Don't find your record.");
        }
    }

    public function confirmMailActive()
    {
        return view('auth.confirmMailActive');
    }
}
