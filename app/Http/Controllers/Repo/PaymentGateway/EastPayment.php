<?php

namespace App\Http\Controllers\Repo\PaymentGateway;

use DB;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\StoreTransaction;
use App\TransactionSession;

class EastPayment extends Controller
{
    use StoreTransaction;

    public function checkout($input, $check_assign_mid)
    {
        $signSrc = $check_assign_mid->mmid_no.$check_assign_mid->gateway_no.$input['order_id'].$input["converted_currency"].$input["converted_amount"].$input['first_name'].$input['last_name'].$input['card_no'].$input['ccExpiryYear'].$input['ccExpiryMonth'].$input['cvvNumber'].$input['email'].$check_assign_mid->key;

        $signInfo = hash('sha256', trim($signSrc));
        $data = [
            'merNo' => $check_assign_mid->mmid_no,
            'gatewayNo' => $check_assign_mid->gateway_no,
            'orderNo' => $input['order_id'],
            'orderCurrency' => $input["converted_currency"],
            'orderAmount' => $input["converted_amount"],
            'cardNo' => $input['card_no'],
            'cardExpireMonth' => $input['ccExpiryMonth'],
            'cardExpireYear' => $input['ccExpiryYear'],
            'cardSecurityCode' => $input['cvvNumber'],
            'firstName' => $input['first_name'],
            'lastName' => $input['last_name'],
            'issuingBank' => 'Bank of china',
            'ip' => $input['ip_address'],
            'email' => $input['email'],
            'PaymentMethod' => 'Credit Card',
            'webSite' => $check_assign_mid->website,
            'phone' => $input['phone_no'],
            'country' => $input['country'],
            'state' => $input['state'],
            'city' => $input['city'],
            'address' => $input['address'],
            'zip' => $input['zip'],
            'signInfo' => $signInfo,
        ];
        $request_url = 'https://pay.eastpayment.net/interface/WS/TPInterface';
        //$request_url = 'https://pay.eastpayment.net/interface/WSTestTPInterface'; //testing url
        // send curl request to wonderland
        $result = $this->curlPostRequest($request_url, http_build_query($data, '', '&'));
        // response from eastpayment
        \Log::info([
            'Eastpayment_geteway_result' => $result,
        ]);
        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $array = json_decode($json, true);
        \Log::info([
            'Eastpayment_geteway_response' => $array,
        ]);
        if($array['orderStatus'] == '1') {
            $input['status'] = '1';
            $input['reason'] = 'Your transaction was proccessed successfully.';
            $input['descriptor'] = $check_assign_mid->descriptor;
        } else {
            $input['status'] = '0';
            $input['reason'] = $array['orderInfo'] ? $array['orderInfo'] : 'Transaction declined.';
        }
        return $input;
    }

    public function curlPostRequest($url, $data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 90);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $tmpInfo = curl_exec($curl);
        curl_close($curl);
        return $tmpInfo;
    }
}
