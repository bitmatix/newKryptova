<?php

namespace App\Http\Controllers\Repo\PaymentGateway;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Traits\StoreTransaction;
use App\User;
use App\TransactionSession;
class Paycos extends Controller
{
    use StoreTransaction;
    
    const BASE_URL = 'https://gateway-router.paycos.com';
    
    public function checkout($input, $check_assign_mid)
    {
        try {
            
            $data = [
                'product' => 'Pay by ' . $input['first_name'] . ' ' . $input['last_name'],
                'amount' => $input['converted_amount']*100,
                'currency' => $input['converted_currency'], // For payment_page_url - only use EUR or RUB
                'redirectSuccessUrl' => route('paycos-success', $input['session_id']),
                'redirectFailUrl' => route('paycos-fail', $input['session_id']),
                'locale' => 'en',
                'callback_url' => route('paycos-callback', $input['session_id']),
                'available_amounts_list' => [],
                // No need for below details, it is only required for the Host to host
                // 'card' => [
                //     'pan' => $input['card_no'],
                //     'expires' => $input['ccExpiryMonth'] . '/' . $input['ccExpiryYear'],
                //     'holder' => $input['first_name'] . ' ' . $input['last_name'],
                //     'cvv' => $input['cvvNumber']
                // ],
                'customer' => [
                    'email' => $input['email'],
                    'address' => $input['address'],
                    'ip' => $input['ip_address'],
                    'phone' => $input['phone_no']
                ]
            ];
            
            $curl = curl_init();
            
            $response = curl_setopt_array($curl, [
                CURLOPT_URL => self::BASE_URL . '/api/v1/init/pay',
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $check_assign_mid->merchant_private_key,
                    'Content-Type: application/json',
                ]
            ]);
            
            ob_start();
            curl_exec($curl);
            $response = json_decode(ob_get_contents(), true);
            ob_end_clean();
            $info = curl_getinfo($curl);
            $err = curl_error($curl);
            
            curl_close($curl);
            
            \Log::info([
                'paycos-input' => $data
            ]);
            
            \Log::info([
                'paycos-response' => $response
            ]);
            // update session data
            if(isset($response['token'])) {
                $input['gateway_id'] = $response['token'] ?? null;
                $this->updateGatewayResponseData($input, $response);
            }

            if ($err) {
                throw new \Exception('Error: ' . $err);
            }
            
            if (! empty($response['success']) && $response['success'] == true && ! empty($response['payment_page_url']['total'])) {
                return [
                    'status' => '7',
                    'reason' => '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
                    'redirect_3ds_url' => $response['payment_page_url']['total'],
                ];
            }
            return [
                'status' => '0',
                'reason' => (isset($response['errors']) ? reset($response['errors']) : 'Your transaction could not processed.'),
                'order_id' => $input['order_id'],
            ];
            
        } catch(\Exception $e) {
            
            \Log::info([
                'paycos-exception' => $e->getMessage()
            ]);
            return [
                'status' => '0',
                'reason' => $e->getMessage(), // 'Your transaction could not processed.',
                'order_id' => $input['order_id'],
            ];
            
        }
    }
    
    public function callback($id, Request $request) {
        $body = $request->all();
        \Log::info([
            'paycos-callback' => $body,
            'id' => $id
        ]);
        
        $input_json = TransactionSession::where('transaction_id', $id)
            ->orderBy('id', 'desc')
            ->first();
        if ($input_json == null) {
            return abort(404);
        }
        $input = json_decode($input_json['request_data'], true);
        $input['gateway_id'] = isset($body['token']) ? $body['token'] : '1';
        $input['status'] = '2';
        $input['reason'] = 'Your transaction is in Pending.';
        if(isset($body['status']) && $body['status'] == "declined"){
            $input['status'] = '0';
            $input['reason'] = isset($body['declinationReason']) ? $body['declinationReason'] : 'Your transaction was Declined.';
        }else if(isset($body['status']) && $body['status'] == "approved"){
            $input['status'] = '1';
            $input['reason'] = 'Your transaction was proccessed successfully.';
        }
        $transaction_response = $this->storeTransaction($input);
        exit();
    }
    
    public function success($id, Request $request) {
        $body = $request->all();
        \Log::info([
            'paycos-success' => $body,
            'id' => $id
        ]);
        $input_json = TransactionSession::where('transaction_id', $id)
            ->orderBy('id', 'desc')
            ->first();
        if ($input_json == null) {
            return abort(404);
        }
        $input = json_decode($input_json['request_data'], true);
        $input['status'] = '1';
        $input['reason'] = 'Your transaction was proccessed successfully.';
        $transaction_response = $this->storeTransaction($input);
        $store_transaction_link = $this->getRedirectLink($input);
        return redirect($store_transaction_link);
    }
    
    public function fail($id, Request $request) {
        $body = $request->all();
        \Log::info([
            'paycos-fail' => $body,
            'id' => $id
        ]);
        $input_json = TransactionSession::where('transaction_id', $id)
            ->orderBy('id', 'desc')
            ->first();
        if ($input_json == null) {
            return abort(404);
        }
        $input = json_decode($input_json['request_data'], true);
        $input['status'] = '0';
        $input['reason'] = 'Your transaction was Declined.';
        $transaction_response = $this->storeTransaction($input);
        $store_transaction_link = $this->getRedirectLink($input);
        return redirect($store_transaction_link);
    }
}