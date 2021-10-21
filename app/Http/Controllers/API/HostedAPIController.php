<?php

namespace App\Http\Controllers\API;

use App\PaymentGateways\PaymentGatewayContract;
use App\RequiredField;
use App\Traits\ApiResponse;
use Auth;
use DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use URL;
use Mail;
use App\User;
use App\WebsiteUrl;
use App\Transaction;
use App\TransactionHostedSession;
use App\Traits\StoreTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Repo\TransactionRepo;
use App\Helpers\PaymentResponse;

class HostedAPIController extends Controller
{
    use ApiResponse, StoreTransaction;

    // ================================================
    /* method : __construct
    * @param  :
    * @Description : Create a new controller instance.
    */// ==============================================
    public function __construct()
    {
        $this->user = new User;
        $this->Transaction = new Transaction;
        $this->TransactionRepo = new TransactionRepo;
        $this->transactionHostedSession = new TransactionHostedSession;
    }

    private function validator($data)
    {
        return Validator::make($data, [
            'api_key' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'country' => 'required|max:2|min:2|regex:(\b[A-Z]+\b)',
            'state' => 'required',
            'city' => 'required',
            'ip_address' => 'required',
            'zip' => 'required',
            'email' => 'required',
            'phone_no' => 'required',
            'amount' => 'required',
            'response_url' => 'required',
            'currency' => 'required|max:3|min:3|regex:(\b[A-Z]+\b)',
        ]);
    }

    // ================================================
    /* method : store
    * @param  :
    * @Description : create transaction API $request
    */// ==============================================
    public function store(Request $request)
    {
        // only accept parameters that are available
        $request_only = config('required_field.required_all_fields');
        $input = $request->only($request_only);
        // \Log::info($input);
        $customer_order_id = isset($request->customer_order_id)?$request->customer_order_id:null;
        
        // if api_key is not included in request
        if(empty($input['api_key']) || $input['api_key'] == null) {
            return response()->json([
                'status' => 'fail',
                'message' => 'api_key parameter is required.',
                'data' => [
                    'order_id' => null,
                    'amount' => isset($input['amount'])?$input['amount']:null,
                    'currency' => isset($input['currency'])?$input['currency']:null,
                    'email' => isset($input['email'])?$input['email']:null,
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        }

        // validate API key
        $payment_gateway_id = DB::table('users')
            ->select('middetails.id as midid', 'middetails.gateway_table', 'users.*')
            ->leftJoin('middetails', 'middetails.id','users.mid')
            ->where('users.api_key', $input['api_key'])
            ->where('users.is_active', '1')
            ->where('users.deleted_at', null)
            ->first();

        // if api_key is not valid or user deleted
        if(!$payment_gateway_id) {
            return response()->json([
                'status' => 'fail',
                'message' => 'please check your API key',
                'data' => [
                    'order_id' => null,
                    'amount' => isset($input['amount'])?$input['amount']:null,
                    'currency' => isset($input['currency'])?$input['currency']:null,
                    'email' => isset($input['email'])?$input['email']:null,
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        }

        // gateway object
        if(isset($input['request_from_type']) && isset($input['token'])) {
            $encrypt_method = "AES-256-CBC";
            $secret_key = 'dsflkIZxusugQdpMyjqTSE3sadjL5vsd';
            $secret_iv = '7sad4vdsJjas87saMLmlNi9x63MRAFLgk';

            // hash
            $key = hash('sha256', $secret_key);

            // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
            $iv = substr(hash('sha256', $secret_iv), 0, 16);

            // decrypt token
            $iframe_json = openssl_decrypt(base64_decode($input['token']), $encrypt_method, $key, 0, $iv);
            
            if($iframe_json == false) {
                return response()->json([
                    'status' => 'fail',
                    'errors' => 'invalid token iframe code.'
                ]);
            }

            $iframe_array = json_decode($iframe_json, 1);
            $check_assign_mid = checkAssignMID($iframe_array['mid']);
            // \Log::info($iframe_array);
            // $input['mid'] = $iframe_array['mid'];
        } else {
            $check_assign_mid = checkAssignMID($payment_gateway_id->mid);
        }
        // dd($check_assign_mid);
        if ($check_assign_mid == false) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Your account is temporarily disabled, please contact admin.',
                'data' => [
                    'order_id' => null,
                    'amount' => isset($input['amount'])?$input['amount']:null,
                    'currency' => isset($input['currency'])?$input['currency']:null,
                    'email' => isset($input['email'])?$input['email']:null,
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        }
        
        // user IP and domain and request from API
        if(isset($input['request_from_type']) && isset($input['token'])) {
            $PaymentMID = $iframe_array['mid'];
            unset($input['request_from_type']);
            unset($input['token']);
        } else {
            $PaymentMID = $payment_gateway_id->mid;
        }
        $input = array_merge($input, [
            'payment_type' => $request->payment_type ?? 'card',
            'request_from_ip' => $request->ip(),
            'request_origin' => $_SERVER['HTTP_HOST'],
            'is_request_from_vt' => 'HOSTED API',
            'user_id' => $payment_gateway_id->id,
            'payment_gateway_id' => $PaymentMID,
            'is_disable_rule' =>$payment_gateway_id->is_disable_rule
        ]);

        // remove api_key
        $api_key = $input['api_key'];

        $validations = json_decode($check_assign_mid->required_fields, 1);

        $validator = $this->validator($input);

        if ($validator->fails()) {
            $errors = $validator->errors()->messages();

            return response()->json([
                'status' => 'fail',
                'message' => 'Some parameters are missing or invalid request data, please check \'errors\' parameter for more details.',
                'errors' => $errors,
                'data' => [
                    'order_id' => null,
                    'amount' => isset($input['amount'])?$input['amount']:null,
                    'currency' => isset($input['currency'])?$input['currency']:null,
                    'email' => isset($input['email'])?$input['email']:null,
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        }

        // check ip_restriction
        // if ($payment_gateway_id->is_ip_remove == '0') {
        //     $getIPData = WebsiteUrl::where('user_id', $payment_gateway_id->id)
        //         ->where('ip_address', request()->ip())
        //         ->first();

        //     // if IP is not added on the IP whitelist
        //     if(!$getIPData) {
        //         return response()->json([
        //             'status' => 'fail',
        //             'message' => 'This API key is not permitted for transactions from this IP address ('.request()->ip().'). Please add your IP from login your dashboard.',
        //             'data' => [
        //                 'order_id' => null,
        //                 'amount' => $input['amount'],
        //                 'currency' => $input['currency'],
        //                 'email' => $input['email'],
        //                 'customer_order_id' => $customer_order_id,
        //             ]
        //         ]);
        //     }

        //     // if IP is not approved
        //     if($getIPData->is_active == '0') {
        //         return response()->json([
        //             'status' => 'fail',
        //             'message' => 'Your Website URL and your IP ('.request()->ip().') is still under approval , Please contact Kryptova Support for more information',
        //             'data' => [
        //                 'order_id' => null,
        //                 'amount' => $input['amount'],
        //                 'currency' => $input['currency'],
        //                 'email' => $input['email'],
        //                 'customer_order_id' => $customer_order_id,
        //             ]
        //         ]);
        //     }

        //     request()->merge([
        //         'website_url_id' => $getIPData->id
        //     ]);
        // }
        $input['session_id'] = strtoupper(\Str::random(4)).time();
        $input['order_id'] = time().strtoupper(\Str::random(10));
        // saving to transaction_hosted_session
        $this->transactionHostedSession->storeData($input);

        // card page
        return response()->json([
            'status' => '3d_redirect',
            'message' => '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
            'redirect_3ds_url' => route('hostedAPI.cardForm', $input['session_id']),
            'customer_order_id' => $customer_order_id,
            'api_key' => $api_key,
        ]);
    }

    // ================================================
    /* method : cardForm
    * @param  :
    * @Description : credit card view page
    */// ==============================================
    public function cardForm(Request $request, $session_id)
    {
        // Get all input data
        $session_data = TransactionHostedSession::where('transaction_id', $session_id)
            ->where('is_completed', '0')
            ->where('created_at', '<', \Carbon\Carbon::now()->addHours(2)->toDateTimeString())
            ->orderBy('id', 'desc')
            ->first();

        if ($session_data == null) {
            return view('gateway.hosted.error');
        }

        $input = json_decode($session_data->request_data, 1);

        $userData = User::select('iframe_logo')
            ->where('id', $session_data->user_id)
            ->first();

        return view('gateway.hosted.index', compact('session_id', 'userData', 'input'));
    }

    // ================================================
    /* method : cardSubmit
    * @param  :
    * @Description : submit credit card page
    */// ==============================================
    public function cardSubmit(Request $request, $session_id)
    {
        $this->validate($request, [
            'card_no' => 'required',
            'ccExpiryMonthYear' => 'required',
            'cvvNumber' => 'required',
        ]);

        // Get all input data
        $input_session = TransactionHostedSession::where('transaction_id', $session_id)
            ->where('is_completed', '0')
            ->where('created_at', '<', \Carbon\Carbon::now()->addHours(2)->toDateTimeString())
            ->orderBy('id', 'desc')
            ->first();

        if ($input_session == null) {
            return view('gateway.hosted.error');
        }

        $input = json_decode($input_session['request_data'], 1);

        $ccExpiryMonth = substr($request->ccExpiryMonthYear, 0, 2);
        $ccExpiryYear = substr($request->ccExpiryMonthYear, -2);

        $input['card_no'] = str_replace(" ", "", $request->card_no);
        $input['ccExpiryMonth'] = $ccExpiryMonth;
        $input['ccExpiryYear'] = '20'.$ccExpiryYear;
        $input['cvvNumber'] = $request->cvvNumber;
        $input['request_from_ip'] = $request->ip();
        $input['request_origin'] = $_SERVER['HTTP_HOST'];
        $input['is_request_from_vt'] = 'HOSTED API';
        $input['ip_address'] = $this->getClientIP();
        
        if ($request->card_type == 'amex') {
            $input['card_type'] = '1';
        } elseif ($request->card_type == 'mastercard') {
            $input['card_type'] = '3';
        } elseif ($request->card_type == 'discover') {
            $input['card_type'] = '4';
        } elseif ($request->card_type == 'jcb') {
            $input['card_type'] = '5';
        } else {
            $input['card_type'] = '2';
        }
        
        TransactionHostedSession::where('transaction_id', $session_id)
            ->update(['is_completed' => '1']);

        // send request to transaction repo class
        // \Log::info($input);
        $return_data = $this->TransactionRepo->store($input);

        // transaction requires 3DS redirect
        if($return_data['status'] == '7') {
            return redirect($return_data['redirect_3ds_url']);
        }

        $input['status'] = $return_data['status'];
        $input['reason'] = $return_data['reason'];

        $store_transaction_link = $this->getRedirectLink($input);
        
        return redirect($store_transaction_link);
    }

    // ================================================
    /* method : getClientIP
    * @param  :
    * @description : get client public ip
    */// ==============================================
    public function getClientIP()
    {
        $ip_address = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip_address = 'UNKNOWN';
        }

        return $ip_address;
    }
}
