<?php

namespace App\Http\Controllers\Repo\PaymentGateway;

use DB;
use Session;
use App\Transaction;
use App\TransactionSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Dixonpay extends Controller
{
    // const BASE_URL = 'https://secure.dixonpay.com/test/payment'; // test
    const BASE_URL = 'https://secure.dixonpay.com/payment'; // live

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->Transaction = new Transaction;
    }

    // ================================================
    /* method : transaction
     * @param  : 
     * @Description : transaction method
     */// ==============================================
    public function checkout($input, $check_assign_mid)
    {
        $input['converted_amount'] = number_format((float)$input['converted_amount'], 2, '.', '');
        $hash_data = $check_assign_mid->mer_no.$check_assign_mid->terminal_no.$input['session_id'].$input['converted_currency'].$input['converted_amount'].$input['card_no'].$input['ccExpiryYear'].$input['ccExpiryMonth'].$input['cvvNumber'].$check_assign_mid->key;
        $signature = hash('sha256', trim($hash_data));
        $request_data = [
            'merNo' => $check_assign_mid->mer_no,
            'terminalNo' => $check_assign_mid->terminal_no,
            'orderNo' => $input['session_id'],
            'orderCurrency' => $input['converted_currency'],
            'orderAmount' => $input['converted_amount'],
            'cardNo' => $input['card_no'],
            'cardExpireMonth' => $input['ccExpiryMonth'],
            'cardExpireYear' => $input['ccExpiryYear'],
            'cardSecurityCode' => $input['cvvNumber'],
            'firstName' => $input['first_name'],
            'lastName' => $input['last_name'],
            'email' => $input['email'],
            'ip' => $input['ip_address'],
            'phone' => $input['phone_no'],
            'country' => $input['country'],
            'state' => $input['state'],
            'city' => $input['city'],
            'address' => $input['address'],
            'zip' => $input['zip'],
            'encryption' => $signature,
            'webSite' => $check_assign_mid->website,
            'uniqueId' => (string) \Str::uuid(),
        ];
        $request_query = http_build_query($request_data);

        $gateway_url = self::BASE_URL;
        
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_URL, $gateway_url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request_query);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        $result = curl_exec($curl);
        
        curl_close($curl);

        $xml = simplexml_load_string($result);
        $json_array = json_encode($xml);
        $response_data = json_decode($json_array, true);
        try {
            $input['gateway_id'] = $response_data['tradeNo'] ?? null;

            // update transaction_session record
            $session_update_data = TransactionSession::where('transaction_id', $input['session_id'])
                ->first();

            $session_request_data = json_decode($session_update_data->request_data, 1);

            $session_request_data['gateway_id'] = $input['gateway_id'];

            $session_update_data->update([
                'request_data' => json_encode($session_request_data),
                'gateway_reference_id' => $input['gateway_id'],
                'response_data' => $json_array
            ]);

            $session_update_data->save();
        
        } catch (\Exception $e) {
            \Log::info(['dixonpay_sesion_update' => $e->getMessage()]);
        }
        $return_data['order_id'] = $input['order_id'];
        if($response_data['orderStatus'] == '1') {
            
            $return_data['status'] = '1';
            $return_data['reason'] = 'Your transaction was proccessed successfully.';

        } else {
            if(isset($response_data['orderInfo']) && $response_data['orderInfo'] == 'ENCRYTION ERROR') {
                \Log::info([
                    'DixonpayEncryptionError' => [
                        'hash_data' => $hash_data,
                        'signature' => $signature,
                        'request_data' => $request_data
                    ]
                ]);
            }
            $return_data['status'] = '0';
            $return_data['reason'] = $response_data['orderInfo'] ?? 'Transaction declined.';
            
        }

        return $return_data;
    }
}
