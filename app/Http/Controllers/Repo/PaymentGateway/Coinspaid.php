<?php

namespace App\Http\Controllers\Repo\PaymentGateway;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Traits\StoreTransaction;
use App\User;
use App\TransactionSession;

class Coinspaid extends Controller
{
    use StoreTransaction;
    
    const BASE_URL = 'https://app.cryptoprocessing.com/api/v2'; // live
    //const BASE_URL = 'https://app.sandbox.cryptoprocessing.com/api/v2'; // test
    
    public function checkout($input, $check_assign_mid)
    {
        if($input['amount_in_usd'] < 10) {
            return [
                'status' => '5',
                'reason' => 'The amount is too small. The minimum amount is 10.00 EUR.', // $e->getMessage(),
                'order_id' => $input['order_id'],
            ];
        }

        try {
            
            $public_key = $check_assign_mid->public_key; // J96Lc06xml6pn4YTwrRuNvfDnTABiONT
            $secret_key = $check_assign_mid->secret_key; // E637DaZMrTg1OH8rxy3Jygt7naST4SSUzsbVE2ZhRwMrOvP3NmcMnpCZvbzRy2da
            
            $data = [
                'timer' => true,
                'title' => 'Payment for ' . $input['first_name'] . ' ' . $input['last_name'],
                'currency' => $input['currency'],
                'amount' => $input['amount'],
                'foreign_id' => $input['order_id'],
                'url_success' => route('coinspaid-success',$input["session_id"]),
                'url_failed' => route('coinspaid-failed',$input["session_id"]),
                'email_user' => $input['email']
            ];
            $requestBody = json_encode($data);
            $signature   = hash_hmac('sha512', $requestBody, $secret_key);
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, self::BASE_URL . '/invoices/create');
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER,[
                'Content-Type: application/json',
                'X-Processing-Key: ' . $public_key,
                'X-Processing-Signature: ' . $signature
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
            $responseData = json_decode($response, 1);
            
            \Log::info([
                'coinspaid-input' => $data
            ]);
            
            \Log::info([
                'coinspaid-response' => $responseData
            ]);
            $input['gateway_id'] = $input["session_id"];
            $this->updateGatewayResponseData($input, $responseData);
            // transaction requires 3DS redirect
            if(! empty($responseData['data']['url'])) {
                return [
                    'status' => '7',
                    'reason' => '3DS link generated successfully, please redirect to \'redirect_3ds_url\'.',
                    'redirect_3ds_url' => $responseData['data']['url'],
                ];
            }
        } catch(\Exception $e) {
            \Log::info([
                'coinspaid-exception' => $e->getMessage()
            ]);
        }
        return [
            'status' => '0',
            'reason' => 'Your transaction could not processed.', // $e->getMessage(),
            'order_id' => $input['order_id'],
        ];
    }

    public function success($id,Request $request){
        \Log::info(['coinspaid_response_success' => $request->toArray(),
            'id'=>$id
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

    public function fail($id,Request $request){
        \Log::info(['coinspaid_response_fail' => $request->toArray(),
            'id'=>$id
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
    
    /**
     * 
     * Call automatically.
     * Set Callback Url here: https://app.sandbox.cryptoprocessing.com/business/merchants/860/edit/api
     * 
     */
    public function webhook(Request $request) {
        
        $body = json_decode(request()->getContent(), true);
        \Log::info([
            'coinspaid-webhook' => $body
        ]);
        $input_json = TransactionSession::where('transaction_id', $id)
            ->orderBy('id', 'desc')
            ->first();
        if ($input_json == null) {
            return abort(404);
        }
        $input = json_decode($input_json['request_data'], true);
        $input['status'] = '2';
        $input['reason'] = 'Your transaction is in Pending.';
        if (! empty($body['foreign_id'])) {
            if (! empty($body['status']) && $body['status'] == 'confirmed') {
                $input['status'] = '1';
                $input['reason'] = 'Your transaction was proccessed successfully.';
            }else if (! empty($body['status']) && $body['status'] == 'cancelled') {
                $input['status'] = '0';
                $input['reason'] = 'Your transaction was Declined.';
            }else if (! empty($body['status']) && $body['status'] == 'not_confirmed') {
                $input['status'] = '0';
                $input['reason'] = 'Your transaction was Declined.';
            }
        }
        $transaction_response = $this->storeTransaction($input);
    }
}