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
use App\Traits\StoreTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Http\Controllers\Repo\PaymentGateway\TestGateway;

class TestTransactionRepo extends Controller
{
    use CCDetails, Mid, Cascade, RuleCheck, StoreTransaction;

    // ================================================
    /* method : __construct
    * @param  :
    * @Description : Create a new controller instance.
    */// ==============================================
    public function __construct()
    {
        $this->transaction = new Transaction;
        $this->transactionSession = new TransactionSession;

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
        if(isset($input["card_no"]) && !empty($input["card_no"])){
            $cardType = $this->getCardType($input["card_no"]);
            $mid = $this->midCard($input,$cardType);
            if(isset($mid["status"])){
                return $mid;
            }
            if($mid != ""){
                // $input['payment_gateway_id'] = $mid;
            }
        }
        $arrPaymentGateway = ['1','2'];
        // check rules and return payment_gateway_id
        // @TODO What is currency rules
        if (!in_array($input['payment_gateway_id'], $arrPaymentGateway)){
            $rule_gateway_id = $this->checkCurrencyRules($input);
            if ($rule_gateway_id != false) {
                $input['payment_gateway_id'] = $rule_gateway_id;
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
        // get main User object
        $user = User::where('id', $input['user_id'])->first();

        // saving to transaction_session
        $this->transactionSession->storeData($input);

        // @TODO ways to get payment gateway id
        $check_assign_mid = checkAssignMID($input['payment_gateway_id']);
        
        if (!in_array($input['payment_gateway_id'], $arrPaymentGateway)){
            // return mid limit response
            $mid_limit_response = $this->getMIDLimitResponse($input, $check_assign_mid);

            if ($mid_limit_response != false) {
                $input['status'] = $mid_limit_response['status'];
                $input['reason'] = $mid_limit_response['reason'];

                // store transaction
                $store_transaction_link = $this->storeTransaction($input);

                $input['redirect_3ds_url'] = $store_transaction_link;

                return $input;
            }
        }

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
            $gateway_return_data['status'] = '0';
            $gateway_return_data['reason'] = 'Your MID is deactivated, please contact Technical team.';
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
