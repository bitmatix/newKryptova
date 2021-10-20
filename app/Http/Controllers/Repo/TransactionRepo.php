<?php

namespace App\Http\Controllers\Repo;

use App\Helpers\PaymentResponse;
use App\PaymentGateways\PaymentGatewayContract;
use App\User;
use App\Transaction;
use App\TransactionSession;
use App\Traits\CCDetails;
use App\Traits\Mid;
use App\Traits\Cascade;
use App\Traits\RuleCheck;
use App\Traits\BinChecker;
use App\Traits\StoreTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Http\Controllers\Repo\PaymentGateway\TestGateway;

class TransactionRepo extends Controller
{
    use CCDetails, Mid, Cascade, RuleCheck, StoreTransaction,BinChecker;

    // ================================================
    /* method : __construct
    * @param  :
    * @Description : Create a new controller instance.
    */// ==============================================
    public function __construct()
    {
        $this->transaction = new Transaction;
        $this->transactionSession = new TransactionSession;
        $this->user = new User;
        // $this->stripeGateway = new StripeGateway;
        // $this->dixonpay = new Dixonpay;
        // $this->wonderland = new Wonderland;
    }

    // ================================================
    /* method : store
    * @param  :
    * @Description : send $input details to gateway class
    */// ==============================================
    public function store($input)
    {
        
        $input['session_id'] = strtoupper(\Str::random(4)).time();
        $input['order_id'] = time().strtoupper(\Str::random(10));
        //add default state value 'NA' if not provided
        $input['state'] = $input['state'] ?? 'NA';
        $input = $this->secureCardInputs($input);
        $input['amount_in_usd'] = $this->amountInUSD($input);
        $input["bin_country_code"] = "";
        $arrPaymentGateway = ['1','2'];
        if(isset($input["card_no"]) && !empty($input["card_no"])){
            $cardType = $this->getCardType($input["card_no"]);
            if($cardType == false){
                $input['status'] = '5';
                $input['reason'] = 'Your card type is not supported. Please check the card type';
            }
            $input['card_type'] = $cardType;
            $mid = $this->midCard($input,$cardType);
            if(isset($mid["status"])){
                return $mid;
            }
            if($mid != ""){
                $input['payment_gateway_id'] = $mid;
            }
            try {
                $bin_response = $this->binChecking($input);
                \Log::info([
                    'bin_response' => $bin_response['country-code']
                ]);
            } catch (\Exception $e) {
                $bin_response = false;
                \Log::info($e->getMessage());
            }
            if (!in_array($input['payment_gateway_id'], $arrPaymentGateway)){
                if ($bin_response != false) {
                    $input["bin_country_code"] = $bin_response["country-code"];
                    $input['bin_details'] = json_encode($bin_response);
                    \Log::info([
                        'bin_response' => $bin_response['country-code'],
                        'country_input' => $input["country"],
                    ]);
                    if($bin_response["country-code"] != $input["country"]){
                        $input['status'] = '0';
                        $input['reason'] = 'The address in the form does not match that of the cards issuing Country.';
                        return $input;
                    }
                }else{
                    $input['status'] = '0';
                    $input['reason'] = 'The address in the form does not match that of the cards issuing Country.';
                    return $input;
                }
            }
        }
        // check rules and return payment_gateway_id
        // @TODO What is currency rules
        if (!in_array($input['payment_gateway_id'], $arrPaymentGateway) && (isset($input["is_disable_rule"]) && $input["is_disable_rule"] == '0')){
            $rule_gateway_id = $this->checkCurrencyRules($input);
            if ($rule_gateway_id != false) {
                $input['payment_gateway_id'] = $rule_gateway_id;
            }
            // $rule_gateway_user_id = $this->checkCurrencyRulesUser($input);
            // if ($rule_gateway_user_id != false) {
            //     $input['payment_gateway_id'] = $rule_gateway_user_id;
            // }
        }
        // get main User object
        $user = User::where('id', $input['user_id'])->first();        

        // @TODO ways to get payment gateway id
        $check_assign_mid = checkAssignMID($input['payment_gateway_id']);
        
        $blocked_country_response = $this->getCountyBlockCheck($input, $check_assign_mid);
        if (!in_array($input['payment_gateway_id'], $arrPaymentGateway)){
            // return mid limit response
            $mid_limit_response = $this->getMIDLimitResponse($input, $check_assign_mid);
            if ($mid_limit_response != false || $blocked_country_response != false){
                if($mid_limit_response["status"] == 5 || $blocked_country_response["status"] == 5){
                    if(!empty($user->multiple_mid) && $input["card_type"] != '3'){
                        $otherMID = $this->getAnotherMID($input, $check_assign_mid);
                        $input["payment_gateway_id"] = $otherMID;
                        $check_assign_mid = checkAssignMID($input['payment_gateway_id']);
                        $sub_mid_limit_response = $this->getMIDLimitResponse($input, $check_assign_mid);
                        $sub_blocked_country_response = $this->getCountyBlockCheck($input, $check_assign_mid);
                        //echo "<pre>";print_r($sub_mid_limit_response);print_r($sub_blocked_country_response);exit();
                        if ($sub_mid_limit_response != false) {
                            $input['status'] = $sub_mid_limit_response['status'];
                            $input['reason'] = $sub_mid_limit_response['reason'];
                            // store transaction
                            $store_transaction_link = $this->storeTransaction($input);
                            $input['redirect_3ds_url'] = $store_transaction_link;
                            return $input;
                        }else if($sub_blocked_country_response != false){
                            $input['status'] = $sub_blocked_country_response['status'];
                            $input['reason'] = $sub_blocked_country_response['reason'];
                            // store transaction
                            $store_transaction_link = $this->storeTransaction($input);
                            $input['redirect_3ds_url'] = $store_transaction_link;
                            return $input;
                        }
                    }else if(!empty($user->multiple_mid_master) && $input["card_type"] == '3'){
                        $otherMID = $this->getAnotherMIDMaster($input, $check_assign_mid);
                        $input["payment_gateway_id"] = $otherMID;
                        $check_assign_mid = checkAssignMID($input['payment_gateway_id']);
                        $sub_mid_limit_response = $this->getMIDLimitResponse($input, $check_assign_mid);
                        $sub_blocked_country_response = $this->getCountyBlockCheck($input, $check_assign_mid);
                        if ($sub_mid_limit_response != false) {
                            $input['status'] = $sub_mid_limit_response['status'];
                            $input['reason'] = $sub_mid_limit_response['reason'];
                            // store transaction
                            $store_transaction_link = $this->storeTransaction($input);
                            $input['redirect_3ds_url'] = $store_transaction_link;
                            return $input;
                        }else if($sub_blocked_country_response != false){
                            $input['status'] = $sub_blocked_country_response['status'];
                            $input['reason'] = $sub_blocked_country_response['reason'];
                            // store transaction
                            $store_transaction_link = $this->storeTransaction($input);
                            $input['redirect_3ds_url'] = $store_transaction_link;
                            return $input;
                        }
                    }else if($blocked_country_response["status"] == 5){
                        $input['status'] = $blocked_country_response['status'];
                        $input['reason'] = $blocked_country_response['reason'];
                        $store_transaction_link = $this->storeTransaction($input);
                        $input['redirect_3ds_url'] = $store_transaction_link;
                        return $input;
                    }
                    else if($mid_limit_response["status"] == 5){
                        $input['status'] = $mid_limit_response['status'];
                        $input['reason'] = $mid_limit_response['reason'];
                        $store_transaction_link = $this->storeTransaction($input);
                        $input['redirect_3ds_url'] = $store_transaction_link;
                        return $input;
                    }
                }
            }
        }
        // gateway default currency        
        if (!in_array($input['payment_gateway_id'], $arrPaymentGateway)){
            $check_selected_currency = $this->midDefaultCurrencyCheck($input['payment_gateway_id'], $input['currency'], $input['amount']);

            if($check_selected_currency) {
                $input['is_converted'] = '1';
                $input['converted_amount'] = $check_selected_currency['amount'];
                $input['converted_currency'] = $check_selected_currency['currency'];
            } else {
                $input['converted_amount'] = $input['amount'];
                $input['converted_currency'] = $input['currency'];
            }
        }else {
            $input['converted_amount'] = $input['amount'];
            $input['converted_currency'] = $input['currency'];
        }

        // saving to transaction_session
        $this->transactionSession->storeData($input);
        // gateway curl response
        $gateway_curl_response = $this->gatewayCurlResponse($input, $check_assign_mid);
        $input['status'] = $gateway_curl_response['status'];
        $input['reason'] = $gateway_curl_response['reason'];
        // store transaction
        if($gateway_curl_response['status'] != '7') {
            $store_transaction_link = $this->storeTransaction($input);
        }
        // $input['redirect_3ds_url'] = $gateway_curl_response['redirect_3ds_url'] ?? $store_transaction_link;
        return $gateway_curl_response;
    }

    public function getCountyBlockCheck($input, $check_assign_mid){        
        \Log::info([
            'check_assign_mid' => $check_assign_mid
        ]);

        if(!empty($check_assign_mid->blocked_country) && is_null($check_assign_mid->blocked_country)){
            $arrBlockedCountry = json_decode($check_assign_mid->blocked_country);
            if(in_array($input["country"],$arrBlockedCountry)){
                return [
                    'status' => '5',
                    'reason' => 'The country you have selected is blocked.'
                ];
            }
            return false;
        }
    }

    // ================================================
    /* method : getMIDLimitResponse
    * @param  :
    * @description : return data from mid
    */// ==============================================
    public function getMIDLimitResponse($input, $check_assign_mid)
    {
        // per transaction limit
        $per_transaction_limit_response = $this->perTransactionLimitCheck($input, $check_assign_mid);
        if ($per_transaction_limit_response != false) {
            return $per_transaction_limit_response;
        }

        // mid daily limit
        $mid_daily_limit = $this->perDayAmountLimitCheck($input, $check_assign_mid);
        if ($mid_daily_limit != false) {
            return $mid_daily_limit;
        }

        // if there is card_no
        if(isset($input['card_no']) && $input['card_no'] != null) {

            // card per-day limit
            $card_per_day_limit = $this->dailyCardLimitCheck($input, $check_assign_mid);
            if ($card_per_day_limit != false) {
                return $card_per_day_limit;
            }

            // card per-week limit
            $card_per_week_limit = $this->weeklyCardLimitCheck($input, $check_assign_mid);
            if ($card_per_week_limit != false) {
                return $card_per_week_limit;
            }

            // card per-month limit
            $card_per_month_limit = $this->monthlyCardLimitCheck($input, $check_assign_mid);
            if ($card_per_month_limit != false) {
                return $card_per_month_limit;
            }
        }

        // if there is email
        if(isset($input['email']) && $input['email'] != null) {

            // email per-day limit
            $email_per_day_limit = $this->dailyEMailLimitCheck($input, $check_assign_mid);
            if ($email_per_day_limit != false) {
                return $email_per_day_limit;
            }

            // email per-week limit
            $email_per_week_limit = $this->weeklyEMailLimitCheck($input, $check_assign_mid);
            if ($email_per_week_limit != false) {
                return $email_per_week_limit;
            }

            // email per-month limit
            $email_per_month_limit = $this->monthlyEMailLimitCheck($input, $check_assign_mid);
            if ($email_per_month_limit != false) {
                return $email_per_month_limit;
            }
        }

        return false;
    }

    public function getAnotherMID($input, $check_assign_mid){
        $user = $this->user->findData($input['user_id']);
        $mainMID = '';
        $multiple_mid = [];
        if($input["card_type"] != '3' && !empty($user->multiple_mid)){
            $multiple_mid = json_decode($user->multiple_mid);
            $length = count($multiple_mid);
            foreach ($multiple_mid as $key => $value) {
                $input["payment_gateway_id"] = $value;
                $check_assign_mid = checkAssignMID($input['payment_gateway_id']);
                $mid_limit_response = $this->getMIDLimitResponse($input, $check_assign_mid);
                $blocked_country_response = $this->getCountyBlockCheck($input, $check_assign_mid);
                $mainMID = $value;
                if ($mid_limit_response != false || $blocked_country_response != false) {
                    if($length-1 == $key){
                        break;
                    }else{
                        continue;    
                    }
                }else{
                    break;
                }
            }
        }
        return $mainMID;
    }

    public function getAnotherMIDMaster($input, $check_assign_mid){
        $user = $this->user->findData($input['user_id']);
        $mainMID = '';
        $multiple_mid = [];
        if($input["card_type"] == '3' && !empty($user->multiple_mid_master)){
            $multiple_mid = json_decode($user->multiple_mid_master);
            $length = count($multiple_mid);
            foreach ($multiple_mid as $key => $value) {
                $input["payment_gateway_id"] = $value;
                $check_assign_mid = checkAssignMID($input['payment_gateway_id']);
                $mid_limit_response = $this->getMIDLimitResponse($input, $check_assign_mid);
                $blocked_country_response = $this->getCountyBlockCheck($input, $check_assign_mid);
                $mainMID = $value;
                if ($mid_limit_response != false || $blocked_country_response != false) {
                    if($length-1 == $key){
                        break;
                    }else{
                        continue;    
                    }
                }else{
                    break;
                }

            }
        }
        return $mainMID;
    }

    // ================================================
    /* method : gatewayCurlResponse
    * @param  :
    * @description : get first response from gateway
    */// ==============================================
    public function gatewayCurlResponse($input, $check_assign_mid)
    {
        try {
            $class_name = 'App\\Http\\Controllers\\Repo\\PaymentGateway\\' . $check_assign_mid->title;
            $gateway_class = new $class_name;
            $gateway_return_data = $gateway_class->checkout($input, $check_assign_mid);
        } catch (\Exception $exception) {
            \Log::info([
                'CardPaymentException' => $exception->getMessage()
            ]);
            $gateway_return_data['status'] = '0';
            $gateway_return_data['reason'] = 'Problem with your transaction data or may be transaction timeout from the bank.';
        }

        return $gateway_return_data;
    }

    // ================================================
    /* method : gatewayView
    * @param  :
    * @description : redirect to gateway list page
    */// ==============================================
    public function gatewayView($session_id)
    {
        // get $input data
        $input_data = TransactionSession::where('transaction_id', $session_id)
            ->orderBy('id', 'desc')
            ->first();

        if ($input_data == null) {
            return abort(404);
        }

        return view('gateway.index', compact('session_id'));
    }

    // ================================================
    /* method : cascadeView
    * @param  :
    * @description : cascade form automatic submit
    */// ==============================================
    public function cascadeView($payment_gateway_id, $session_id)
    {
        // get $input data
        $input_data = TransactionSession::where('transaction_id', $session_id)
            ->orderBy('id', 'desc')
            ->first();

        if ($input_data == null) {
            return abort(404);
        }

        return view('gateway.index', compact('payment_gateway_id', 'session_id'));
    }

    private function secureCardInputs($input)
    {
        // change expiry year to 4 digit
        if(! empty($input['ccExpiryYear'])) {
            $input['ccExpiryYear'] = $this->ccExpiryYearFourDigit($input['ccExpiryYear']);
        }

        //change expiry month to 2 digit
        if(! empty($input['ccExpiryMonth'])) {
            $input['ccExpiryMonth'] = $this->ccExpiryMonthTwoDigit($input['ccExpiryMonth']);
        }

        if(! empty($input['cvvNumber'])) {
            $input['cvvNumber'] = trim($input['cvvNumber']);
        }

        if(! empty($input['card_no'])) {
            $input['card_no'] = str_replace(" ", "", $input['card_no']);
            $input['card_type'] = $this->getCardType($input['card_no']);
        }

        return $input;
    }
}
