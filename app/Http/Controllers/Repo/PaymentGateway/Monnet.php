<?php

namespace App\Http\Controllers\Repo\PaymentGateway;

use DB;
use Mail;
use Session;
use Exception;
use App\User;
use App\Transaction;
use App\TransactionSession;
use App\Http\Controllers\Controller;
use App\Traits\StoreTransaction;
use Illuminate\Http\Request;
use Cartalyst\Stripe\Laravel\Facades\Stripe;

class Monnet extends Controller
{
    use StoreTransaction;
    //const BASE_URL = 'https://monnetpayments.com/api-payin'; // live
    const BASE_URL = 'https://cert.monnetpayments.com/api-payin'; // test
    public function __construct() {
        $this->user = new User;
        $this->Transaction = new Transaction;
    }

    public function checkout($input, $check_assign_mid)
    {
        //echo "<pre>";print_r($input);exit();
        if(!isset($input['card_no'])) {
            $sessionData = $input;
            unset($sessionData['api_key']);

            DB::table('transaction_session')->insert([
                'user_id' => $input['user_id'],
                'payment_gateway_id' => $input['payment_gateway_id'],
                'transaction_id' => $input['session_id'],
                'order_id' => $input['order_id'],
                'request_data' => json_encode($sessionData),
                'amount' => $input['amount'],
                'email' => $input['email'],
                'is_completed' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        return [
            'status' => '7',
            'reason' => '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
            'redirect_3ds_url' => route('Monnet.transactionForm', $input['session_id'])
        ];   
    }

    public function transactionForm($session_id)
    {
        $input = DB::table('transaction_session')
                ->where('transaction_id', $session_id)
                ->value('request_data');

        if ($input != null) {
            $input = json_decode($input, true);
        } else {
            return abort('404');
        }

        return view('gateway.monnet.redirect', compact('session_id'));
    }

    public function transactionResponse(Request $request, $session_id)
    {
        //echo "<pre>";print_r($request->toArray());exit();
        $this->validate($request, [
            'payinCustomerTypeDocument' => 'required',
            'payinCustomerDocument' => 'required',
        ]);

        $request_only = $request->only(['payinCustomerTypeDocument', 'payinCustomerDocument']);

        // get request data from database
        $input_json = DB::table('transaction_session')
                ->where('transaction_id', $session_id)
                ->value('request_data');

        if ($input_json == null) {
            return abort('404');
        }

        $input = json_decode($input_json, true);

        $check_assign_mid = checkAssignMID($input['payment_gateway_id']);
        
        // put data in session to save after transaction complete
        $input['customer_order_id'] = $input['customer_order_id'] ?? null;

        // set gateway default currency
        $check_selected_currency = checkSelectedCurrency($input['payment_gateway_id'], $input['currency'], $input['amount']);
        
        if ($check_selected_currency) {
            $currency = $check_selected_currency['currency'];
            $amount = $check_selected_currency['amount'];
            $input['is_converted'] = '1';
            $input['converted_amount'] = $check_selected_currency['amount'];
            $input['converted_currency'] = $check_selected_currency['currency'];
        } else {
            $currency = $input['currency'];
            $amount = $input['amount'];
        }
        //echo $currency;exit();
        $amount = number_format((float)$amount, 2, '.', '');
        //echo $input['session_id']."<br>".$amount."<br>".$currency."<br>".$check_assign_mid->merchant_id."<br>".$check_assign_mid->monnet_key;exit();
        //$hash_data = $check_assign_mid->merchant_id.$input['session_id'].$amount.$currency.$check_assign_mid->monnet_key;
        $hash_data = $check_assign_mid->merchant_id.$input['session_id'].$amount."PEN".$check_assign_mid->monnet_key;
        $signature = hash('sha512', $hash_data);
        //echo $signature
        $request_data = [
            'payinMerchantID' => (int) $check_assign_mid->merchant_id,
            'payinAmount' => $amount,
            'payinCurrency' => 'PEN',
            //'payinCurrency' => $currency,
            'payinMerchantOperationNumber' => $input['session_id'],
            'payinMethod' => $check_assign_mid->descriptor,
            'payinVerification' => $signature,
            //'payinTransactionOKURL' => Route('Monnet.redirect',["status"=>'success',"session"=>$input['session_id']]),
            'payinTransactionOKURL' => 'https://webhook.site/87bff569-b33e-4587-80b9-3180cbdc4475',
            'payinTransactionErrorURL' => 'https://webhook.site/87bff569-b33e-4587-80b9-3180cbdc4475',
            //'payinTransactionErrorURL' => Route('Monnet.redirect',["status"=>'fail',"session"=>$input['session_id']]),
            'payinExpirationTime' => '30',
            'payinLanguage' => 'EN',
            'payinCustomerEmail' => $input['email'],
            'payinCustomerName' => $input['first_name'],
            'payinCustomerLastName' => $input['last_name'],
            'payinCustomerTypeDocument' => $request_only['payinCustomerTypeDocument'],
            'payinCustomerDocument' => $request_only['payinCustomerDocument'],
            'payinCustomerPhone' => $input['phone_no'],
            'payinCustomerAddress' => $input['address'],
            'payinCustomerCity' => $input['city'],
            'payinCustomerRegion' => $input['state'],
            'payinCustomerCountry' => $input['country'],
            'payinCustomerZipCode' => $input['zip'],
            'payinCustomerShippingName' => $input['first_name'].' '.$input['last_name'],
            'payinCustomerShippingPhone' => $input['phone_no'],
            'payinCustomerShippingAddress' => $input['address'],
            'payinCustomerShippingCity' => $input['city'],
            'payinCustomerShippingRegion' => $input['state'],
            'payinCustomerShippingCountry' => $input['country'],
            'payinCustomerShippingZipCode' => $input['zip'],
            // 'payinRegularCustomer' => $input['first_name'].' '.$input['last_name'],
            // 'payinCustomerID' => $input['session_id'],
            // 'payinDiscountCoupon' => $input['session_id'],
            // 'payinFilterBy' => $input['session_id'],
            'payinProductID' => $input['session_id'],
            'payinProductDescription' => 'ECOM PAYMENT',
            'payinProductAmount' => $amount,
            'payinDateTime' => date('Y-m-d'),
            'payinProductSku' => 'ECOM PAYMENT',
            'payinProductQuantity' => '1',
            'URLMonnet' => 'https://cert.monnetpayments.com/api-payin/v3/online-payments',
            'typePost' => 'json',
            // 'payinPan' => $input['card_no'],
            // 'payinCvv' => $input['cvvNumber'],
            // 'payinExpirationYear' => $input['ccExpiryYear'],
            // 'payinExpirationMonth' => $input['ccExpiryMonth'],
        ];

        $request_json = json_encode($request_data);

        $headers = [
            'Content-Type: application/json',
        ];
        //echo "<pre>";print_r($headers);print_r($request_data);exit();
        $request_url = self::BASE_URL.'/v3/online-payments';
        //echo $request_url;exit();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response_body = curl_exec($ch);
        echo "<pre>";print_r($response_body);exit();
        curl_close ($ch);

        $response_data = json_decode($response_body, 1);

        if (isset($response_data['url']) && $response_data['url'] != null) {
            // redirect to acquirer server
            return redirect($response_data['url']);
        } elseif (isset($response_data['payinErrorCode']) && $response_data['payinErrorCode'] == '0000') {
            $input['status'] = '1';
            $input['reason'] = 'Your transaction was proccessed successfully.';

            // store transaction
            $this->Transaction->storeData($input, $input['is_request_from_vt']);

            // update transaction_session record
            try {
                DB::table('transaction_session')
                    ->where('transaction_id', $input['session_id'])
                    ->update(['is_completed' => '1']);
            } catch(\Exception $e) {
                // 
            }

            $domain = parse_url($input['response_url'], PHP_URL_HOST);

            // return to portal.paypound.ltd with session instead of query string
            if ($domain == 'portal.paypound.ltd') {
                $redirect_url = $input['response_url'];

                Session::put('success', $input['reason']);
            } else {
                if (parse_url($input['response_url'], PHP_URL_QUERY)) {
                    $redirect_url = $input['response_url'].'&status=success&message='.$input['reason'].'&order_id='.$input['order_id'];
                } else {
                    $redirect_url = $input['response_url'].'?status=success&message='.$input['reason'].'&order_id='.$input['order_id'];
                }
            }

            // redirect back to $response_url
            return redirect($redirect_url);
        } else {

            \Log::info(['monnet_error_response' => $response_data]);

            $input['status'] = '0';
            $input['reason'] = $response_data['payinErrorMessage'] ?? 'Transaction authentication failed.';

            // store transaction
            $this->Transaction->storeData($input, $input['is_request_from_vt']);

            // update transaction_session record
            try {
                DB::table('transaction_session')
                    ->where('transaction_id', $input['session_id'])
                    ->update(['is_completed' => '1']);
            } catch(\Exception $e) {
                // 
            }

            $domain = parse_url($input['response_url'], PHP_URL_HOST);

            // return to portal.paypound.ltd with session instead of query string
            if ($domain == 'portal.paypound.ltd') {
                $redirect_url = $input['response_url'];

                Session::put('error', $input['reason']);
            } else {
                if (parse_url($input['response_url'], PHP_URL_QUERY)) {
                    $redirect_url = $input['response_url'].'&status=fail&message='.$input['reason'].'&order_id='.$input['order_id'];
                } else {
                    $redirect_url = $input['response_url'].'?status=fail&message='.$input['reason'].'&order_id='.$input['order_id'];
                }
            }

            // redirect back to $response_url
            return redirect($redirect_url);
        }
    }

    // ================================================
    /* method : redirect
    * @param  : 
    * @description : redirect back after 3ds
    */// ==============================================
    public function redirect(Request $request, $status, $session_id)
    {
        \Log::info(['monnet_redirect_string' => $request->all()]);
        // get $input data
        $input_json = TransactionSession::where('transaction_id', $session_id)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($input_json == null) {
            return abort(404);
        }

        $input = json_decode($input_json['request_data'], true);
        $input['customer_order_id'] = $input['customer_order_id'] ?? null;

        // transaction was successful...
        if ($status == 'success') {

            $input['status'] = '1';
            $input['reason'] = 'Your transaction was proccessed successfully.';

        // if transaction declined with reason
        } elseif ($status == 'fail') {
            
            $input['status'] = '0';
            $input['reason'] = $request['reason'] ?? 'TRANSACTION DECLINED.';

        // if transaction status pending
        } elseif ($status == 'pending') {

            $input['status'] = '2';
            $input['reason'] = 'Transaction pending, please wait to get update from acquirer.';

        } else {

            $input['status'] = '0';
            $input['reason'] = 'TRANSACTION DECLINED.';

        }

        // store transaction
        $this->Transaction->storeData($input, $input['is_request_from_vt']);

        // update transaction_session record if not pending
        if ($input['status'] != '2') {
            
            \DB::table('transaction_session')
                ->where('transaction_id', $input['session_id'])
                ->update(['is_completed' => '1']);
        }

        $domain = parse_url($input['response_url'], PHP_URL_HOST);

        if ($input['status'] == '1') {
            $status = 'success';
        } elseif ($input['status'] == '2') {
            $status = 'pending';
        } else {
            $status = 'fail';
        }

        // return to portal.paypound.ltd with session instead of query string
        if ($domain == 'portal.paypound.ltd') {
            $redirect_url = $input['response_url'];

            if ($input['status'] == '1') {
                Session::put('success', $input['reason']);
            } elseif ($input['status'] == '2') {
                Session::put('success', $input['reason']);
            } else {
                Session::put('error', $input['reason']);
            }

        } else {
            if (parse_url($input['response_url'], PHP_URL_QUERY)) {
                $redirect_url = $input['response_url'].'&status='.$status.'&message='.$input['reason'].'&order_id='.$input['order_id'];
            } else {
                $redirect_url = $input['response_url'].'?status='.$status.'&message='.$input['reason'].'&order_id='.$input['order_id'];
            }
        }

        // redirect back to $response_url
        return redirect($redirect_url);
    }

    // ================================================
    /* method : notify
    * @param  : 
    * @description : notify after complete
    */// ==============================================
    public function notify(Request $request)
    {
        \Log::info(['monnet_notify_string' => $request->all()]);
        $request_data = $request->all();

        $session_id = $request_data['payinMerchantOperationNumber'] ?? null;

        if ($session_id != null) {
            exit();
        }

        http_response_code(200);

        // check in transactions table
        $same_transaction = DB::table('transactions')
            ->where('session_id', $session_id)
            ->where('status', '!=', '2')
            ->first();

        if ($same_transaction != null) {
            exit();
        }

        // get $input data
        $input_json = DB::table('transaction_session')
            ->where('transaction_id', $session_id)
            ->where('is_completed', '0')
            ->value('request_data');

        if ($input_json == null) {
            exit();
        }

        $input = json_decode($input_json, true);
        $input['is_webhook'] = '1';

        // wait for 10 seconds
        sleep(10);

        if (isset($request_data['payinStateID']) && $request_data['payinStateID'] == '5') {
            
            $input['status'] = '1';
            $input['reason'] = 'Your transaction was proccessed successfully.';

        } elseif (isset($request_data['payinStateID']) && in_array($request_data['payinStateID'], ['1', '2', '4'])) {
            
            exit();

        } elseif (isset($request_data['payinStateID']) && in_array($request_data['payinStateID'], ['0', '3', '6'])) {
            
            $input['status'] = '0';
            $input['reason'] = $request_data['payinStatusErrorMessage'] ?? 'Transaction unknown error.';

        }

        $this->Transaction->storeData($input, $input['is_request_from_vt']);

        // update transaction_session record
        try {
            DB::table('transaction_session')
                ->where('transaction_id', $session_id)
                ->update(['is_completed' => '1']);
        } catch(\Exception $e) {
            // 
        }
        
        exit();
    }
}
