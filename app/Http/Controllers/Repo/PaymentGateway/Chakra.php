<?php
namespace App\Http\Controllers\Repo\PaymentGateway;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Traits\StoreTransaction;
use App\User;
use App\Transaction;
use App\TransactionSession;
use Illuminate\Support\Facades\Hash;

class Chakra extends Controller {

    const BASE_URL = 'https://webdev.mychakra.io/';
    const DECRYPT_STRING = '95bff5599';
    private $response = [];

    use StoreTransaction;

    public function checkout($input, $check_assign_mid) {
        
        try {

            $token = $this->getAccessToken($input,$check_assign_mid);
        
            if (isset($token['data']['accessToken'])) {
               
                $input['token'] = $token['data']['accessToken'];

                $preInitializePayment = $this->preInitializePayment($input, $check_assign_mid); // 1 preinitialize api for get transaction reference number

                if (! empty($preInitializePayment)) {

                    $input['transactionRef'] = isset($preInitializePayment['data']['transactionRef']) ? $preInitializePayment['data']['transactionRef'] : '';

                    $initializePayment = $this->initializePayment($input , $check_assign_mid); // 2 initializepayment action for payment initialization

                    if (! empty($initializePayment)) {

                        $cardPayment = $this->processCardPayment($input, $check_assign_mid); // 3 processcardpayment action for get authurl
                        $input['gateway_id'] = $input['transactionRef'] ?? $input['order_id'];
                        $this->updateGatewayResponseData($input, $this->response);

                        return [
                            'status' => '7',
                            'reason' => '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
                            'redirect_3ds_url' => $cardPayment['data']['authUrl'],
                        ];
                    }
                }
            }

            // Token generation fail.
            \Log::info([
                'chakra-token-error' => $token
            ]);

            throw new \Exception('Your transaction could not processed.');
            
        } catch (\Exception $e) {

            \Log::info([
                'chakra-exception' => $e->getMessage()
            ]);

            return [
                'status' => '0',
                'reason' => $e->getMessage(), // 'Your transaction could not processed.',
                'order_id' => $input['order_id']
            ];
        }
    }

    /*
     * For generate aceesst oken
     * */
    private function getAccessToken($input, $check_assign_mid) {

        $err = '';

        $requestData = [
            'merchantId' => $check_assign_mid->merchant_id,
            'apiKey' => $check_assign_mid->api_key,
        ];

        $url = '/credentials/get-token';

        $header = [
            'Content-Type: application/json',
        ];
        $responseData = $this->curlRequest($url,$header,$requestData,$input,$check_assign_mid);

        if ($responseData['responseCode'] != '00') {

            $err = isset ($responseData['responseMessage']) && ! empty($responseData['responseMessage']) ?  $responseData['responseMessage'] : 'Your transaction could not processed.';
        }

        if ($err) {
            throw new \Exception($err);
        }
        
        return $responseData;
    }

    /*
     * For PreInitializePayment action
     * */
    private function preInitializePayment($input, $check_assign_mid) {

        $err = '';
        $data = [
            'currency' => $input['converted_currency'],
            'amount' => round($input['converted_amount']),
            'paymentType' => 1 // 1- card
        ];

        \Log::info([
            'chakra-payment-input-preInitializePayment-action' => $data
        ]);

        $encrypt = $this->encryption($data,$check_assign_mid);

        if (! empty($encrypt)) { 
            
            $requestData = [
                'action' => 'PreInitializePayment',
                'request' => $encrypt
            ];

            $url = $this->getApiUrl($check_assign_mid);
            
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $input['token']
            ];
            $responseData = $this->curlRequest($url,$header,$requestData,$input,$check_assign_mid);
            $responseData = $this->decryption($responseData,$check_assign_mid); 
            if ($responseData['responseCode'] != '00') {

                $err = isset ($responseData['responseMessage']) && ! empty($responseData['responseMessage'])?  $responseData['responseMessage'] : 'Your transaction could not processed.';
            }

            if ($err) {
                throw new \Exception($err);
            }
        
            $transactionID = isset($responseData['data']['transactionRef']) ? $responseData['data']['transactionRef'] : '';

            $this->response['preInitializePayment'] = [ 
                    'session_id' => $input['session_id'],
                    'gateway_id' => $transactionID,
                    'response' => $responseData
            ]; 
            
            return $responseData;
        }
        
    }

      /*
     *  For InitializePayment action
     * */
    private function initializePayment($input, $check_assign_mid) {

        $err = '';
        $data = [
            'transRef' =>  $input['transactionRef'],
            'narration' => $input['order_id'],
            'email' => $input['email'],
            'firstName' => $input['first_name'],
            'lastName' => $input['last_name'],
            'phoneNumber' => $input['phone_no'],
            'address' => $input['address'],
            'city' => $input['city'],
            'stateCode' => $input['state'],
            'postalCode' => $input['zip'],
            'countryCode' => $input['country'],
            'tokenizeCard' => false
        ];

        \Log::info([
            'chakra-payment-input-initializePayment-action' => $data
        ]);
        $encrypt = $this->encryption($data,$check_assign_mid);

        if (! empty($encrypt)) { 

            
            $requestData = [
                'action' => 'InitializePayment',
                'request' => $encrypt
            ];

            $url = $this->getApiUrl($check_assign_mid);
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $input['token']
            ];
            $responseData = $this->curlRequest($url,$header,$requestData,$input,$check_assign_mid);
            $responseData = $this->decryption($responseData,$check_assign_mid); 

            if ($responseData['responseCode'] != '00') {

                $err = isset ($responseData['responseMessage']) && ! empty($responseData['responseMessage'])?  $responseData['responseMessage'] : 'Your transaction could not processed.';
            }

            if ($err) {
                throw new \Exception($err);
            }
        
            $this->response['initializePayment'] =  [
                    'session_id' => $input['session_id'],
                    'gateway_id' => $input['transactionRef'],
                    'response' => $responseData
            ];  
            $this->updateCallback($input,$check_assign_mid);

            return $responseData;
        }
        
    }

    /*
     * For ProcessCardPayment
     * */
    private function processCardPayment($input, $check_assign_mid) {

        $err = '';
        $data = [
            'transRef' => $input['transactionRef'], // transaction reference number 
            'pan' => $input['card_no'],
            'expiredMonth' => $input['ccExpiryMonth'],
            'expiredYear' => $input['ccExpiryYear'],
            'cvv' => $input['cvvNumber']
        ];

        \Log::info([
            'chakra-payment-input-processCardPayment-action' => $data
        ]);

        $encrypt = $this->encryption($data,$check_assign_mid);

        if (! empty($encrypt)) { 

            $requestData = [
                'action' => 'ProcessCardPayment',  
                'request' => $encrypt
            ];
            $url = $this->getApiUrl($check_assign_mid);
            $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $input['token']
            ];
            $responseData = $this->curlRequest($url,$header,$requestData,$input,$check_assign_mid);
            $responseData = $this->decryption($responseData,$check_assign_mid); 
            if ($responseData['responseCode'] != '00') {

                $err = isset ($responseData['responseMessage']) && ! empty($responseData['responseMessage'])?  $responseData['responseMessage'] : 'Your transaction could not processed.';
            }

            if ($err) {
                throw new \Exception($err);
            }
        
            $this->response['processCardPayment'] = [
                'session_id' => $input['session_id'],
                'gateway_id' => $input['transactionRef'],
                'response' => $responseData
            ];

            return $responseData;
        }   
    }


    /*
     * For generate encryption string
     * */
    private function encryption($data, $check_assign_mid) {

        $curl = curl_init();
        $url = 'cryptography/encrypt-req?merchantId='.$check_assign_mid->merchant_id;
       
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
       
        curl_setopt ($curl, CURLOPT_HTTPHEADER,[
            'Content-Type: text/plain'
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
       
        
        $response = curl_exec($curl);
        curl_close($curl);
       
        \Log::info([
            'chakra-encryption-response' => $response
        ]);
       
        return $response;
    }

    /*
     * For decryption string
     * */
    private function decryption($data, $check_assign_mid) {

        $clientData = [
            'client-data' => $check_assign_mid->ivKey . self::DECRYPT_STRING . $check_assign_mid->secretKey
        ];

        $url = 'cryptography/decrypt-req?' . http_build_query($clientData);
        
        $curl = curl_init();
       
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($curl, CURLOPT_POST,1 );
        curl_setopt($curl, CURLOPT_HTTPHEADER,[
            'Content-Type: text/plain'
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data['response']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        $response = curl_exec($curl);
        curl_close($curl);
        $responseData = json_decode($response, 1);

        \Log::info([
            'chakra-decrypt-input' => $data
        ]);
        \Log::info([
            'chakra-decrypt-response' => $responseData
        ]);
        
        return $responseData;
    }

    /*
     * For curl request
     * */
    private function curlRequest($url,$header,$requestData, $input, $check_assign_mid) {

        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
           $header
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $info = curl_getinfo($curl);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        $responseData = json_decode($response, 1);
        
        return $responseData;
        
    }

    /*
     * For UpdateCallback
    * */
    private function updateCallback($input,$check_assign_mid) {
        
        $err = '';
        $requestData = [
            'merchantId' => $check_assign_mid->merchant_id, 
            'callbackUrl' => route('chakra-callback',$input['session_id']),
            'callbackSecret' => Hash::make($check_assign_mid->merchant_id),
            'responseUrl' => route('chakra-returnUrl',$input['session_id']),
        ];
        \Log::info([
                'chakra-callback-update-input' => $requestData
        ]);

        $credential = [
            'chakra-credentials' => base64_encode($check_assign_mid->merchant_id . ':' . $check_assign_mid->api_key)
        ];    
        $credential = http_build_query($credential);
        $url = 'credentials/update-callback?' . $credential;
        $header = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $input['token']
        ];

        $responseData = $this->curlRequest($url,$header,$requestData,$input,$check_assign_mid);

        \Log::info([
                'chakra-callback-update' => $responseData
        ]);
        if ($responseData['responseCode'] != '00') {

            $err = isset ($responseData['responseMessage']) && ! empty($responseData['responseMessage'])?  $responseData['responseMessage'] : 'Your transaction could not processed.';
        }

        if ($err) {
            throw new \Exception($err);
        }
        return 0;
        
    }

    private function getApiUrl($check_assign_mid) {

        $credential = [
            'chakra-credentials' => base64_encode($check_assign_mid->merchant_id . ':' . $check_assign_mid->api_key)
        ];
            
        $credential = http_build_query($credential);
        $url = 'acq/send-request?' . $credential; 

        return $url;
    }

    /*
     * For callbackUrl
    * */
    public function callback(Request $request,$id) {

        // Update callback response
        $header = $request->header('callback-secret');
        $response = $request->all();

        \Log::info([
                'chakra-callback' => $response
        ]);
        
        $transaction_session = DB::table('transaction_session')
            ->where('transaction_id', $id)
            ->first();
        
        if ($transaction_session == null) {

            return abort(404);
        }
        $input = json_decode($transaction_session->request_data, 1);
        $check_assign_mid = checkAssignMID($input['payment_gateway_id']);  

        if (Hash::check($check_assign_mid->merchant_id, $header)) {

            if ($response['responseCode'] == '00') {

                $input['status'] = '1';
                $input['reason'] = 'Your transaction has been processed successfully.';

            } else {

                $input['status'] = '0';
                $input['reason'] = (isset($response['responseMessage']) ? $response['responseMessage'] : 'Your transaction could not processed.');
            }

        } else {

            $input['status'] = '0';
            $input['reason'] = 'Your transaction could not processed.';

            \Log::info([
                    'chakra-invalid-secretkey' => $header
            ]);
        }

        $transaction_response = $this->storeTransaction($input);
        exit();
       
    }

    /*
     * For responseUrl
    * */
    public function returnUrl(Request $request,$id) {   

        $transaction_session = DB::table('transaction_session')
            ->where('transaction_id', $id)
            ->first();

        if ($transaction_session == null) {
            return abort(404);
        }

        $input = json_decode($transaction_session->request_data,true);

        $transactions = DB::table('transactions')
            ->where('order_id', $transaction_session->order_id)
            ->first();
        
        $input['status'] = $transactions->status ?? 0;
        $input['reason'] = $transactions->reason ?? 'Your transaction could not processed.';

        $store_transaction_link = $this->getRedirectLink($input);

        return redirect($store_transaction_link);
    }
}


