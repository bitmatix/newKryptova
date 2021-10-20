<?php

namespace App\Http\Controllers\API;

use App\PaymentGateways\PaymentGatewayContract;
use DB;
use Mail;
use Session;
use Validator;
use App\User;
use App\WebsiteUrl;
use App\Transaction;
use App\TransactionSession;
use App\Traits\StoreTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Repo\TransactionRepo;

class HostedAPIControllerBack extends Controller
{
    // trait
    use StoreTransaction;

    // ================================================
    /* method : __construct
    * @param  :
    * @Description : Create a new controller instance.
    */// ==============================================
    public function __construct()
    {
        $this->transaction = new Transaction;
        $this->TransactionRepo = new TransactionRepo;
        $this->transactionSession = new TransactionSession;
    }

    // ================================================
    /* method : store
    * @param  :
    * @Description : create transaction API $request
    */// ==============================================
    public function store(Request $request)
    {

        $user = auth()->user();
        $customer_order_id = $request->customer_order_id;
        $payment_gateway_id = ($user->crypto_mid != null && $request->payment_type == 'crypto') ?
            $user->crypto_mid : $user->mid;
        // gateway object
        $check_assign_mid = checkAssignMID($payment_gateway_id);
        if ($check_assign_mid == false) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Your account is temporarily disabled, please contact admin.',
                'customer_order_id' => $customer_order_id,
            ]);
        }

        Config::set('custom.payment.gateway', $check_assign_mid->title);

        // user IP and domain and request from API
        $request->merge([
            'payment_type' => $request->payment_type ?? 'card',
            'request_from_ip' => $request->ip(),
            'request_origin' => $_SERVER['HTTP_HOST'],
            'is_request_from_vt' => 'API',
            'user_id' => $user->id,
            'payment_gateway_id' => $payment_gateway_id
        ]);

        $validationIds = json_decode($check_assign_mid->required_fields, 1);

        $paymentGateway = app(PaymentGatewayContract::class);
        $validator = $paymentGateway->validate($validationIds);

        if ($validator->fails()) {
            $errors = $validator->errors()->messages();

            return response()->json([
                'status' => 'fail',
                'message' => 'Some parameters are missing or invalid request data, please check \'errors\' parameter for more details.',
                'errors' => $errors,
                'customer_order_id' => $customer_order_id,
            ]);
        }

        // check ip_restriction
        $isRestricted = $user->isIPRestricted();
        if ($isRestricted) {
            return $isRestricted;
        }

        $input = $request->all();

        // provide payment method form if merchant crypto_mid is not null
        if ($payment_gateway_id->crypto_mid != null && $input['payment_type'] == null) {

            // saving to transaction_session
            $this->transactionSession->storeData($input);

            return response()->json([
                'status' => '3d_redirect',
                'payment_redirect_url' => route('hostedAPI.paymentTypeSelect', $input['session_id']),
                'customer_order_id' => $customer_order_id,
                'valid_till' => \Carbon\Carbon::now()->addHours(2)->toDateTimeString(),
            ]);

        // assign crypto mid if merchant crypto_mid is not null
        } elseif ($payment_gateway_id->crypto_mid != null && $input['payment_type'] == 'crypto') {

            $input['payment_gateway_id'] = $payment_gateway_id->crypto_mid;

        // card details page
        } elseif (!empty($input['card_no'])) {

            // saving to transaction_session
            $this->transactionSession->storeData($input);

            return response()->json([
                'status' => '3d_redirect',
                'payment_redirect_url' => route('hostedAPI.cardForm', $input['session_id']),
                'customer_order_id' => $customer_order_id,
                'valid_till' => \Carbon\Carbon::now()->addHours(2)->toDateTimeString(),
            ]);
        }

        // send $input to transactionRepo class
        $return_data = $this->TransactionRepo->store($input);

        // if return_data is null
        if(!$return_data || $return_data == null) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Something went wrong, please contact technical team.',
                'customer_order_id' => $customer_order_id,
            ]);
        }

        // return status
        $return_status = $this->getStatus($return_data['status']);

        // transaction requires 3DS redirect
        if($return_data['status'] == '7') {
            return response()->json([
                'status' => $return_status,
                'message' => $return_data['reason'] ?? '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
                'payment_redirect_url' => $return_data['redirect_3ds_url'],
                'customer_order_id' => $customer_order_id,
            ]);
        // transaction success, pending or fail
        } elseif(in_array($return_data['status'], ['0', '1', '2', '3', '5'])) {
            return response()->json([
                'status' => $return_status,
                'message' => $return_data['reason'],
                'order_id' => $return_data['order_id'],
                'customer_order_id' => $customer_order_id,
            ]);
        // no response
        } else {
            return response()->json([
                'status' => $return_status,
                'message' => $return_data['reason'] ?? 'Something went wrong, please contact technical team.',
                'customer_order_id' => $customer_order_id,
                'order_id' => $return_data['order_id'] ?? null,
            ]);
        }
    }

    // ================================================
    /* method : paymentTypeSelect
    * @param  :
    * @description : select payment method form
    */// ==============================================
    public function paymentTypeSelect($session_id)
    {
        // Get all input data
        $session_data = TransactionSession::where('transaction_id', $session_id)
            ->where('is_completed', '0')
            ->where('created_at', '<', \Carbon\Carbon::now()->addHours(2)->toDateTimeString())
            ->orderBy('id', 'desc')
            ->first();

        if ($session_data == null) {
            return view('gateway.hosted.error');
        }

        $userData = User::select('iframe_logo')
            ->where('id', $session_data->user_id)
            ->first();

        return view('gateway.hosted.paymentMethod', compact('session_id', 'userData'));
    }

    // ================================================
    /* method : paymentTypeSubmit
    * @param  :
    * @Description : submit payment type page
    */// ==============================================
    public function paymentTypeSubmit(Request $request, $session_id)
    {
        $this->validate($request, [
            'payment_type' => 'required',
        ]);

        // Get all input data
        $input_session = TransactionSession::where('transaction_id', $session_id)
            ->where('is_completed', '0')
            ->where('created_at', '<', \Carbon\Carbon::now()->addHours(2)->toDateTimeString())
            ->orderBy('id', 'desc')
            ->first();

        if ($input_session == null) {
            return view('gateway.hosted.error');
        }

        if ($request->payment_type == 'card') {
            return redirect()->route('hostedAPI.cardForm', $session_id);
        }

        $input = json_decode($input_session['request_data'], 1);

        $input['payment_type'] = $request->payment_type;

        $input['request_from_ip'] = $request->ip();
        $input['request_origin'] = $_SERVER['HTTP_HOST'];
        $input['is_request_from_vt'] = 'HOSTED API';
        $input['ip_address'] = $this->getClientIP();

        TransactionSession::where('transaction_id', $session_id)
            ->update(['is_completed' => '1']);

        // send request to transaction repo class
        $return_data = $this->TransactionRepo->store($input);

        return redirect()->away($return_data['redirect_3ds_url']);
    }

    // ================================================
    /* method : cardForm
    * @param  :
    * @Description : credit card view page
    */// ==============================================
    public function cardForm(Request $request, $session_id)
    {
        // Get all input data
        $session_data = TransactionSession::where('transaction_id', $session_id)
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
            'ccExpiryMonth' => 'required',
            'ccExpiryYear' => 'required',
            'cvvNumber' => 'required',
        ]);

        // Get all input data
        $input_session = TransactionSession::where('transaction_id', $session_id)
            ->where('is_completed', '0')
            ->where('created_at', '<', \Carbon\Carbon::now()->addHours(2)->toDateTimeString())
            ->orderBy('id', 'desc')
            ->first();

        if ($input_session == null) {
            return view('gateway.hosted.error');
        }

        $input = json_decode($input_session['request_data'], 1);

        $input['card_no'] = str_replace(" ", "", $request->card_no);
        $input['ccExpiryMonth'] = $request->ccExpiryMonth;
        $input['ccExpiryYear'] = $request->ccExpiryYear;
        $input['cvvNumber'] = $request->cvvNumber;

        $input['request_from_ip'] = $request->ip();
        $input['request_origin'] = $_SERVER['HTTP_HOST'];
        $input['is_request_from_vt'] = 'HOSTED API';
        $input['ip_address'] = $this->getClientIP();

        TransactionSession::where('transaction_id', $session_id)
            ->update(['is_completed' => '1']);

        // send request to transaction repo class
        $return_data = $this->TransactionRepo->store($input);

        return redirect()->away($return_data['redirect_3ds_url']);
    }

    // ================================================
    /* method : cancelTransaction
    * @param  :
    * @Description : cancel transaction from credit card page
    */// ==============================================
    public function cancelTransaction($session_id)
    {
        // Get all input data
        $input_session = TransactionSession::where('transaction_id', $session_id)
            ->where('is_completed', '0')
            ->orderBy('id', 'desc')
            ->first();

        if ($input_session == null) {
            return view('gateway.hosted.error');
        }

        $input = json_decode($input_session['request_data'], 1);

        $input['request_from_ip'] = \Request::ip();
        $input['request_origin'] = $_SERVER['HTTP_HOST'];
        $input['is_request_from_vt'] = 'HOSTED API';
        $input['ip_address'] = $this->getClientIP();

        $input['order_id'] = getOrderNo();
        $input['status'] = '0';
        $input['reason'] = 'The customer has cancelled the transaction.';

        // store transaction
        $store_transaction_link = $this->storeTransaction($input);

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

    // ================================================
    /* method : getStatus
    * @param  :
    * @description : status response by status code
    */// ==============================================
    public function getStatus($code)
    {
        $status = [
            '0' => 'fail',
            '1' => 'success',
            '2' => 'pending',
            '5' => 'blocked',
            '7' => '3d_redirect',
        ];

        if (array_key_exists($code, $status)) {
            return $status[$code];
        } else {
            return '0';
        }
    }
}
