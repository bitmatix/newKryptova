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
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Repo\TransactionRepo;
use App\Helpers\PaymentResponse;

class DirectApiController extends Controller
{
    use ApiResponse;

    // ================================================
    /* method : __construct
    * @param  :
    * @Description : Create a new controller instance.
    */// ==============================================
    public function __construct()
    {
        $this->user = new User;
        $this->Transaction = new Transaction;
        $this->transaction_repo = new TransactionRepo;
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
        $check_assign_mid = checkAssignMID($payment_gateway_id->mid);
        // dd($check_assign_mid);
        //dd($payment_gateway_id);
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
        $request->merge([
            'payment_type' => $request->payment_type ?? 'card',
            'request_from_ip' => $request->ip(),
            'request_origin' => $_SERVER['HTTP_HOST'],
            'is_request_from_vt' => 'API',
            'user_id' => $payment_gateway_id->id,
            'payment_gateway_id' => $payment_gateway_id->mid,
            'is_disable_rule' =>$payment_gateway_id->is_disable_rule
        ]);

        // remove api_key
        $api_key = $input['api_key'];

        $validations = json_decode($check_assign_mid->required_fields, 1);

        // create validations array
        foreach ($validations as $value) {
            $new_validations[$value] = config('required_field.total_fields.'.$value.'.validate');
        }

        //dd($new_validations);
        $validator = Validator::make($input, $new_validations);
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
        if ($payment_gateway_id->is_ip_remove == '0') {
            $getIPData = WebsiteUrl::where('user_id', $payment_gateway_id->id)
                ->where('ip_address', request()->ip())
                ->first();

            // if IP is not added on the IP whitelist
            if(!$getIPData) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'This API key is not permitted for transactions from this IP address ('.request()->ip().'). Please add your IP from login your dashboard.',
                    'data' => [
                        'order_id' => null,
                        'amount' => $input['amount'],
                        'currency' => $input['currency'],
                        'email' => $input['email'],
                        'customer_order_id' => $customer_order_id,
                    ]
                ]);
            }

            // if IP is not approved
            if($getIPData->is_active == '0') {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Your Website URL and your IP ('.request()->ip().') is still under approval , Please contact PAYPOUND Support for more information',
                    'data' => [
                        'order_id' => null,
                        'amount' => $input['amount'],
                        'currency' => $input['currency'],
                        'email' => $input['email'],
                        'customer_order_id' => $customer_order_id,
                    ]
                ]);
            }

            request()->merge([
                'website_url_id' => $getIPData->id
            ]);
        }

        // send request to transaction repo class
        $return_data = $this->transaction_repo->store($request->all());
        // dd($return_data);
        // if return_data is null
        if(!$return_data || $return_data == null) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Something went wrong, please contact technical team.',
                'data' => [
                    'order_id' => null,
                    'amount' => $input['amount'],
                    'currency' => $input['currency'],
                    'email' => $input['email'],
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        }

        // transaction requires 3DS redirect
        if($return_data['status'] == '7') {
            return response()->json([
                'status' => '3d_redirect',
                'message' => $return_data['reason'],
                'redirect_3ds_url' => $return_data['redirect_3ds_url'],
                'customer_order_id' => $customer_order_id,
                'api_key' => $api_key,
            ]);
        // transaction success
        } elseif($return_data['status'] == '1') {
            return response()->json([
                'status' => 'success',
                'message' => $return_data['reason'],
                'data' => [
                    'order_id' => $return_data['order_id'],
                    'amount' => $input['amount'],
                    'currency' => $input['currency'],
                    'email' => $input['email'],
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        // transaction pending
        } elseif ($return_data['status'] == '2') {
            return response()->json([
                'status' => 'pending',
                'message' => $return_data['reason'],
                'data' => [
                    'order_id' => $return_data['order_id'],
                    'amount' => $input['amount'],
                    'currency' => $input['currency'],
                    'email' => $input['email'],
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        // transaction fail
        } elseif ($return_data['status'] == '0') {
            return response()->json([
                'status' => 'fail',
                'message' => $return_data['reason'],
                'data' => [
                    'order_id' => isset($return_data['order_id'])?$return_data['order_id']:null,
                    'amount' => $input['amount'],
                    'currency' => $input['currency'],
                    'email' => $input['email'],
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        // transaction blocked
        } elseif ($return_data['status'] == '5') {
            return response()->json([
                'status' => 'blocked',
                'message' => $return_data['reason'],
                'data' => [
                    'order_id' => $return_data['order_id'],
                    'amount' => $input['amount'],
                    'currency' => $input['currency'],
                    'email' => $input['email'],
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        // no response
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => isset($return_data['reason']) ? $return_data['reason'] : 'Something went wrong, please contact technical team.',
                'data' => [
                    'order_id' => isset($return_data['order_id']) ? $return_data['order_id'] : null,
                    'amount' => $input['amount'],
                    'currency' => $input['currency'],
                    'email' => $input['email'],
                    'customer_order_id' => $customer_order_id,
                ]
            ]);
        }
    }

    // ================================================
    /* method : getTransactionDetails
    * @param  :
    * @Description : get-transaction-details
    */// ==============================================
    public function getTransactionDetails(Request $request)
    {
        $data = Transaction::select(
            'transactions.order_id',
            'merchantapplications.company_name','transactions.first_name',
            'transactions.last_name','transactions.address',
            'transactions.customer_order_id','transactions.country',
            'transactions.state','transactions.city',
            'transactions.zip','transactions.ip_address',
            'transactions.birth_date','transactions.email',
            'transactions.phone_no','transactions.reason',
            'transactions.status','transactions.created_at as transaction_date',
            'transactions.card_type','transactions.amount',
            'transactions.currency','transactions.card_no',
            'transactions.ccExpiryMonth','transactions.ccExpiryYear',
            'transactions.cvvNumber','transactions.shipping_first_name',
            'transactions.shipping_last_name','transactions.shipping_address',
            'transactions.shipping_country','transactions.shipping_state',
            'transactions.shipping_city','transactions.shipping_zip',
            'transactions.shipping_email','transactions.shipping_phone_no',
            'transactions.is_flagged','transactions.flagged_date',
            'transactions.chargebacks','transactions.changebanks_date',
            'transactions.changebanks_reason','transactions.refund',
            'transactions.refund_date','transactions.refund_reason',
            'transactions.is_retrieval','transactions.retrieval_date'
        )
            ->join('merchantapplications','merchantapplications.user_id','transactions.user_id')
            ->where('order_id',$request['order_id'])
            ->first();

        if(isset($data['card_no'])){
            $data['card_no'] = 'XXXXXXXXXXXX'.substr($data['card_no'], -4);
        }
        if(isset($data['ccExpiryYear'])){
            $data['ccExpiryYear'] = 'XXXX';
        }
        if(isset($data['ccExpiryMonth'])){
            $data['ccExpiryMonth'] = 'XX';
        }
        if(isset($data['cvvNumber'])){
            $data['cvvNumber'] = 'XXX';
        }

        if(!isset($data)){
            $data = [];
        }
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // ================================================
    /* method  : getTransaction
    * @ param  :
    * @ Description : get transaction details wether success or failed
    */// ==============================================
    public function getTransaction(Request $request)
    {
        if ($request->api_key == null) {
            return response()->json([
                'status' => 'fail',
                'message' => 'api_key field is required',
            ]);
        } elseif ($request->order_id == null && $request->customer_order_id == null) {
            return response()->json([
                'status' => 'fail',
                'message' => 'order_id or customer_order_id field is required',
            ]);
        }

        $input = $request->only(['api_key', 'order_id', 'customer_order_id']);

        $user = DB::table('users')
            ->where('api_key', $input['api_key'])
            ->where('is_active', '1')
            ->whereNull('deleted_at')
            ->first();

        if ($user == null) {
            return response()->json([
                'status' => 'fail',
                'message' => 'api_key is not valid.',
            ]);
        }

        $transaction = $this->Transaction->getSingleTransaction($input);


        if($transaction != null) {
            if($transaction->status == '1') {
                $transactionStatus = 'success';
            } elseif ($transaction->status == '2') {
                $transactionStatus = 'pending';
            } else {
                $transactionStatus = 'declined';
            }

            $response_array = [];
            $response_array['status'] = 'success';
            $response_array['transaction']['order_id'] = $transaction->order_id;
            $response_array['transaction']['customer_order_id'] = $transaction->customer_order_id;
            $response_array['transaction']['transaction_status'] = $transactionStatus;
            $response_array['transaction']['reason'] = $transaction->reason;
            $response_array['transaction']['card_no'] = substr_replace($transaction['card_no'], 'XXXXXXXXXXXX', 0, -4);
            $response_array['transaction']['ccExpiryMonth'] = $transaction->ccExpiryMonth;
            $response_array['transaction']['ccExpiryYear'] = $transaction->ccExpiryYear;
            $response_array['transaction']['currency'] = $transaction->currency;
            $response_array['transaction']['amount'] = $transaction->amount;
            $response_array['transaction']['transaction_date'] = convertDateToLocal($transaction->created_at, 'Y-m-d H:i:s');
            // $response_array['transaction']['test'] = in_array($transaction->payment_gateway_id, ['16', '41']) ? true : false;

        } else {
            $response_array = [];
            $response_array['status'] = 'fail';
            $response_array['message'] = 'Transaction not found.';
        }

        return response()->json($response_array);
    }

    // ================================================
    /* method : getCreditCardTypeNew
    * @param  :
    * @Description : return card_type
    */// ==============================================
    public function getCreditCardTypeNew($cc, $extra_check = false)
    {
        if (empty($cc)) {
            return false;
        }

        $cards = array(
            "visa" => "(4\d{12}(?:\d{3})?)",
            "mastercard" => "(5[1-5]\d{14})",
            "amex" => "(3[47]\d{13})",
            "jcb" => "(35[2-8][89]\d\d\d{10})",
            "solo" => "((?:6334|6767)\d{12}(?:\d\d)?\d?)",
            "maestro" => "((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)",
            "discover" => "/^65[4-9][0-9]{13}|64[4-9][0-9]{13}|6011[0-9]{12}|(622(?:12[6-9]|1[3-9][0-9]|[2-8][0-9][0-9]|9[01][0-9]|92[0-5])[0-9]{10})$/",
            "switch" => "/^(4903|4905|4911|4936|6333|6759)[0-9]{12}|(4903|4905|4911|4936|6333|6759)[0-9]{14}|(4903|4905|4911|4936|6333|6759)[0-9]{15}|564182[0-9]{10}|564182[0-9]{12}|564182[0-9]{13}|633110[0-9]{10}|633110[0-9]{12}|633110[0-9]{13}$/",
        );

        $names = array("Visa", "American Express", "JCB", "Maestro", "Solo", "Mastercard", "Switch","Discover");
        $matches = array();
        $pattern = "#^(?:".implode("|", $cards).")$#";
        $result = preg_match($pattern, str_replace(" ", "", $cc), $matches);
        if($extra_check && $result > 0){
            $result = (validatecard($cc))?1:0;
        }
        $card = ($result>0)?$names[sizeof($matches)-2]:false;

        // Valid Following Card Type.
        // 1 - For Amex
        // 2 - For Visa
        // 3 - For Mastercard
        // 4 - For Discover

        switch ($card):
            case 'Visa':
                return '2';
                break;
            case 'American Express':
                return '1';
                break;
            case 'Maestro':
                return '6';
                break;
            case 'Mastercard':
                return '3';
                break;
            case 'Discover':
                return '4';
                break;
            case 'JCB':
                return '5';
                break;
            case 'Switch':
                return '7';
                break;
            case 'Solo':
                return '8';
                break;
            default :
                return false;
                break;
        endswitch;
    }
}