<?php

namespace App\Http\Controllers\Repo\PaymentGateway;

use DB;
use Mail;
use Session;
use Exception;
use App\User;
use App\TransactionSession;
use App\Merchantapplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Controller;
use App\Traits\StoreTransaction;

class Flutterwave extends Controller
{
    const BASE_URL = 'https://api.flutterwave.com/v3/';

    use StoreTransaction;

    // ================================================
    /* method : transaction
    * @param  : 
    * @Description : send to payment gateway
    // */// ==============================================
    public function checkout($input, $check_assign_mid) {

        try {
            
            $data = [
                "tx_ref" => $input['order_id'],
                "amount" => $input['converted_amount'],
                "currency" => $input['converted_currency'],
                "redirect_url" => route('flutterwave-callback', $input['session_id']),
                'customer' => [
                    'email' => $input['email'],
                    'phonenumber' => $input['phone_no'],
                    'name' => $input['first_name'] . $input['last_name']
                ],
            ];
            \Log::info([
                'flutterwave_input_response' => $data,
            ]);
            $url = self::BASE_URL . "payments";
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '. $check_assign_mid->secret_key
            ];
        
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 200);
            curl_setopt($curl, CURLOPT_TIMEOUT, 200);
            $response_body = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            $result = json_decode($response_body, true);

            \Log::info([
                'flutterwave-input' => $data
            ]);
            \Log::info([
                'flutterwave-response' => $response_body,
            ]);

            if ($err) {
                throw new \Exception($err);
            }
            
            if (isset($result['status']) && $result['status'] == 'success') {
                if ( isset($result['data']['link']) && $result['data']['link'] != ' ') {

                    $input['gateway_id'] = $input['session_id'] ?? null;
                    $this->updateGatewayResponseData($input, $result);
                    
                    // redirect to flutterwave server
                    return [
                        'status' => '7',
                        'reason' => '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
                        'redirect_3ds_url' => $result['data']['link'],
                    ];
                }
            }

            throw new \Exception('Your transaction could not processed.');

        } catch (Exception $e) {

            \Log::info([
                'flutterwave-exception' => $e->getMessage()
            ]);
            return [
                'status' => '0',
                'reason' => $e->getMessage(), // 'Your transaction could not processed.',
                'order_id' => $input['order_id']
            ];
        
        }      
    }

    public function callback($session_id, Request $request) {

        $response = $request->all();
        $id = $session_id;
        \Log::info([
            'flutterwave-callback' => $response,
            'id' => $id
        ]);

        if (! empty($id)) {

            $transaction_session = DB::table('transaction_session')
                ->where('transaction_id', $id)
                ->first();

            if ($transaction_session == null) {
               return abort(404);
            }
            $input = json_decode($transaction_session->request_data, 1);

            if ($response['status'] == 'successful') {
                $input['status'] = '1';
                $input['reason'] = 'Your transaction has been processed successfully.';
            } else {
                $input['status'] = '0';
                $input['reason'] = (isset($response['reason']) ? $response['reason'] : 'Your transaction could not processed.');
            }

            // store transaction
            $transaction_response = $this->storeTransaction($input);
            $store_transaction_link = $this->getRedirectLink($input);

            return redirect($store_transaction_link);
        }
    }

    // ================================================
    /* method : transaction
    * @param  : 
    * @Description : send to payment gateway
    // */// ==============================================
    // public function checkout($input, $curleck_assign_mid)
    // {
    //     $currency = $input['converted_currency'];
    //     $amount = $input['converted_amount'];
    //     $ip_address = $input['ip_address'] ?? '52.56.249.139';
    //     // $arrCountry = [
    //     //     'city' => $input['city'],
    //     //     'address' => $input['address'],
    //     //     'state' => $input['state'],
    //     //     'country' => $input['country'],
    //     //     'zipcode' => $input['zip']
    //     // ];
    //     $data = [
    //         "tx_ref" => $input['session_id'],
    //         "amount" => $amount,
    //         "currency" => $currency,
    //         'card_number' => $input['card_no'],
    //         "cvv" => $input['cvvNumber'],
    //         "expiry_month" => $input['ccExpiryMonth'],
    //         "expiry_year" => $input['ccExpiryYear'],
    //         "email" => $input['email'],
    //         "phonenumber" => $input['phone_no'],
    //         "client_ip" => $ip_address,
    //         "firstname" => $input['first_name'],
    //         "lastname" => $input['last_name'],
    //         //'authorization' => $arrCountry,
    //         "redirect_url" => route('flutterwave-response.responseFromFlutterwave'),
    //     ];
    //     \Log::info([
    //         'flutterwave_input_response' => $data,
    //     ]);
    //     $url = "https://api.flutterwave.com/v3/charges?type=card";
    //     $headers = [
    //         'Content-Type: application/json',
    //         'Authorization: Bearer '.$curleck_assign_mid->secret_key
    //     ];
    //     $key = raveFlutterwaveGetKey($curleck_assign_mid->secret_key);
    //     // encrypt json data via helper method
    //     $post_enc = raveFlutterwaveEncrypt3Des(json_encode($data), $key);

    //     $postdata = array(
    //         'client' => $post_enc,
    //     );
    //     $curl = curl_init();
    //     curl_setopt($curl, CURLOPT_URL, $url);
    //     curl_setopt($curl, CURLOPT_POST, 1);
    //     curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
    //     curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 200);
    //     curl_setopt($curl, CURLOPT_TIMEOUT, 200);
        
    //     $response_body = curl_exec($curl);
    //     //echo "<pre>";print_r($response_body);exit();
    //     \Log::info([
    //         'flutterwave_response' => $response_body,
    //     ]);
    //     curl_close($curl);
    //     $result = json_decode($response_body, true);
    //     try {
    //         // update transaction_session record
    //         if (isset($result['data']['flwRef']) && $result['data']['flwRef'] != null) {
    //             $input['gateway_id'] = $result['data']['flwRef'];
    //         } else {
    //             $input['gateway_id'] = null;
    //         }
    //         $this->updateGatewayResponseData($input, $result);
    //     } catch (Exception $e) {
    //         \Log::info($e->getMessage());
    //     }
    //     if (isset($result['status']) && $result['status'] == 'success') {
    //         if ( isset($result['data']['auth_model']) && $result['data']['auth_model'] == 'VBVSECURECODE') {
    //             // redirect to flutterwave server
    //             return [
    //                 'status' => '7',
    //                 'reason' => '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
    //                 'redirect_3ds_url' => $result['meta']['authorization']['redirect'],
    //             ];
    //         }
    //     }
    //     return [
    //         'status' => '0',
    //         'reason' => 'Your transaction was declined by issuing bank.',
    //         'order_id' => $input['order_id'],
    //     ];       
    // }

    // ================================================
    /* method  : responseFromFlutterwave
    * @ param  : 
    * @ Description : response from rave API 
    */// ==============================================
    // public function responseFromFlutterwave(Request $request)
    // {
    //     // if query has response key and value as json, then need to consider
    //     if (isset($request['response']) && $request['response'] != null) {
    //         $response = json_decode($request['response'], true);
    //         // response value has be key 'txRef' so we can call verify api
    //         if (isset($response['txRef']) && $response['txRef'] != null) {
    //             // get $input data
                
    //             $input_json = TransactionSession::where('transaction_id', $response['txRef'])
    //                 ->orderBy('id', 'desc')
    //                 ->first();
    //             if ($input_json == null) {
    //                 return abort(404);
    //             }
    //             \Log::info([
    //                 'flutterwave_success_response' => $request->all(),
    //             ]);
    //             $input = json_decode($input_json['request_data'], true);
    //             $input["gateway_id"] = $response['txRef'];
    //             $curleck_assign_mid = checkAssignMID($input['payment_gateway_id']);
    //             // call verify api method
    //             $verify_response = $this->raveApiVerify($response['txRef'], $curleck_assign_mid);
    //             // transaction was successful...
    //             if(isset($response['status']) && $response['status'] == 'successful') {
    //                 $input['status'] = '1';
    //                 $input['reason'] = 'Your transaction was proccessed successfully.';
    //                 $input['descriptor'] = 'PayPound';
    //             // if transaction declined with reason
    //             } elseif (isset($verify_response['data']['status']) && $verify_response['data']['status'] == 'error') {
    //                 $input['status'] = '0';
    //                 $input['descriptor'] = 'PayPound';
    //                 if (isset($verify_response['data']['vbvmessage']) && $verify_response['data']['vbvmessage'] != null) {
    //                     $input['reason'] = $verify_response['data']['vbvmessage'];
    //                 } elseif (isset($verify_response['data']['chargeResponseMessage']) && $verify_response['data']['chargeResponseMessage'] != null) {
    //                     $input['reason'] = $verify_response['data']['chargeResponseMessage'];
    //                 } else {
    //                     $input['reason'] = 'Your transaction was declined by bank.';
    //                 }
    //             // if transaction declined with status=failed and reason
    //             } elseif (isset($verify_response['data']['status']) && $verify_response['data']['status'] == 'failed') {
                    
    //                 $input['status'] = '0';
    //                 $input['descriptor'] = 'PayPound';
                    
    //                 if (isset($verify_response['data']['vbvmessage']) && $verify_response['data']['vbvmessage'] != null) {
    //                     $input['reason'] = $verify_response['data']['vbvmessage'];
    //                 } elseif (isset($verify_response['data']['chargeResponseMessage']) && $verify_response['data']['chargeResponseMessage'] != null) {
    //                     $input['reason'] = $verify_response['data']['chargeResponseMessage'];
    //                 } else {
    //                     $input['reason'] = 'Your transaction was declined by bank.';
    //                 }
    //             } elseif (isset($verify_response['status']) && $verify_response['status'] == 'error' && isset($verify_response['message']) && $verify_response['message'] != null) {

    //                 $input['status'] = '0';
    //                 $input['reason'] = $verify_response['message'];
    //                 $input['descriptor'] = 'PayPound';
    //             // if transaction declined without reason
    //             } else {

    //                 $input['status'] = '0';
    //                 $input['reason'] = 'Your transaction was declined by bank';
    //                 $input['descriptor'] = 'PayPound';
    //             }
    //             //print_r($input);exit();
    //             $transaction_response = $this->storeTransaction($input);
    //             $store_transaction_link = $this->getRedirectLink($input);
    //             return redirect($store_transaction_link);
    //         } else {
    //             return response()->json(['error' => 'unauthorised']);
    //         }
    //     } else {
    //         return response()->json(['error' => 'unauthorised']);
    //     }
    // }

    // ================================================
    /*  method : raveApiVerify
    * @ param  : 
    * @ Description : verify rave transaction
    */// ==============================================
    // public function raveApiVerify($session_id, $curleck_assign_mid)
    // {
    //     $postdata = [
    //         'txref' => $session_id,
    //         'SECKEY' => $curleck_assign_mid->secret_key,
    //     ];
        
    //     $url = 'https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify'; // test
    //     //$url = 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify'; // production
    //     $headers = [
    //         'Content-Type: application/json',
    //     ];

    //     $curl = curl_init();
    //     curl_setopt($curl, CURLOPT_URL, $url);
    //     curl_setopt($curl, CURLOPT_POST, 1);
    //     curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
    //     curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    //     $response_body = curl_exec ($curl);
    //     curl_close ($curl);

    //     $result = json_decode($response_body, true);

    //     return $result;
    // }

}