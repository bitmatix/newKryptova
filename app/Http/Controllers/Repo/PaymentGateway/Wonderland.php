<?php

namespace App\Http\Controllers\Repo\PaymentGateway;

use DB;
use Session;
use App\Transaction;
use App\TransactionSession;
use App\Http\Controllers\Controller;
use App\Traits\StoreTransaction;
use Illuminate\Http\Request;

class Wonderland extends Controller
{
    use StoreTransaction;
    
    const BASE_URL = 'https://pay.wonderlandpay.com/TPInterface'; // test
    //const BASE_URL = 'https://pay.wonderlandpay.com/TestTPInterface'; // test

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->transaction = new Transaction;
    }

    // ================================================
    /* method : transaction
     * @param  : 
     * @Description : wonderland api call
     */// ==============================================
    public function checkout($input, $check_assign_mid)
    {
        $input['converted_amount'] = number_format((float)$input['converted_amount'], 2, '.', '');
        $signSrc = $check_assign_mid->mid_number.$check_assign_mid->gateway_no.$input['order_id'].$input["converted_currency"].$input["converted_amount"].$input["card_no"].$input['ccExpiryYear'].$input['ccExpiryMonth'].$input["cvvNumber"].$check_assign_mid->key;

        $signInfo = hash('sha256', trim($signSrc));

        $data = [
            'merNo' => $check_assign_mid->mid_number,
            'gatewayNo' => $check_assign_mid->gateway_no,
            'orderNo' => $input['order_id'],
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
            'webSite' => $check_assign_mid->website,
            'phone' => $input['phone_no'],
            'country' => $input['country'],
            'state' => $input['state'],
            'city' => $input['city'],
            'address' => $input['address'],
            'zip' => $input['zip'],
            'uniqueId' => (string) \Str::uuid(),
            'signInfo' => $signInfo,
        ];
        $request_url = self::BASE_URL;
        $result = $this->curlPostRequest($request_url, http_build_query($data, '', '&'));
        // response from wonderland
        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        try {
            $input['gateway_id'] = $array['tradeNo'] ?? null;
            $this->updateGatewayResponseData($input, $json);
        } catch (\Exception $e) {
            \Log::info(['dixonpay_sesion_update' => $e->getMessage()]);
        }
        if($array['orderStatus'] == '1') {
            $return_data['status'] = '1';
            $return_data['reason'] = 'Your transaction was proccessed successfully.';
            $return_data['order_id'] = $input['order_id'];
        } else {
            $return_data['status'] = '0';
            $return_data['reason'] = $array['orderInfo'] ? $array['orderInfo'] : 'Transaction declined.';
            $return_data['order_id'] = $input['order_id'];
        }
        return $return_data;
    }


    public function curlPostRequest($url, $data) {
        if(strstr(strtolower($url), 'https://')) {
            $port = 443;
        }else {
            $port = 80;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_PORT, $port);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 90);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $tmpInfo = curl_exec($curl);
        curl_close($curl);
        return $tmpInfo;
    }
}
