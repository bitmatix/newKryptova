<?php

namespace App\Traits;

use App\MIDDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

trait Mid
{
	// ================================================
	/* method : midDefaultCurrencyCheck
	* @param  :
	* @description : check mid currency
	*/// ==============================================
	public function midDefaultCurrencyCheck($payment_gateway_id, $currency,$amount)
	{
		$check = MIDDetail::select('converted_currency')
            ->where('id', $payment_gateway_id)
            ->first();
	    // return false
	    if ($check == null) {
	        return false;
	    }

	    if ($check->converted_currency != '') {
	        $selected = $check->converted_currency;

			$currency_api = 'https://api.currencylayer.com/live?access_key=da76f4a49243dd37ea7532037f2dde01&currencies='.$currency.'&source='.$selected.'&format=1';

			// initialize CURL:
			$ch = curl_init($currency_api);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$json = curl_exec($ch);
			curl_close($ch);

	        $dd = json_decode($json);
	        $selector = $selected.$currency;

	        if (isset($dd->quotes->$selector)) {
	            return ['amount' => (float) round(($amount / $dd->quotes->$selector), 2), 'currency' => $check->converted_currency];
	        }
	    }

        return false;
	}

	// ================================================
	/* method : perTransactionLimitCheck
	* @param  :
	* @description : per transaction amount limit check
	*/// ==============================================
	public function perTransactionLimitCheck($input, $check_assign_mid)
	{
		if ($input['currency'] == 'USD') {
			$usd_converted_amount = $input['amount'];
		} else {
			$usd_rate = \DB::table('currency_rate')
				->where('currency', $input['currency'])
				->value('converted_amount');
			$usd_converted_amount = $input['amount'] / $usd_rate;
		}

		if ($usd_converted_amount > $check_assign_mid->per_transaction_limit) {
			return [
				'status' => '5',
				'reason' => 'Per transaction amount limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : amountInUSD
	* @param  :
	* @description : return USD amount
	*/// ==============================================
	public function amountInUSD($input)
	{
		if ($input['currency'] == 'USD') {
			return $input['amount'];
		} else {
			$usd_rate = \DB::table('currency_rate')
				->where('currency', $input['currency'])
				->value('converted_amount');

			$usd_converted_amount = $input['amount'] / $usd_rate;

			return $usd_converted_amount;
		}
	}

	// ================================================
	/* method : perDayAmountLimitCheck
	* @param  :
	* @description : per day success amount limit check
	*/// ==============================================
	public function perDayAmountLimitCheck($input, $check_assign_mid)
	{
		$from_date = Carbon::now()->subDays(1)->toDateTimeString();
		$to_date = Carbon::now()->toDateTimeString();

		$daily_amount_processed = $this->successAmountBetweenDates($input['payment_gateway_id'], $from_date, $to_date);

		if ($daily_amount_processed > $check_assign_mid->per_day_limit) {
			return [
				'status' => '5',
				'reason' => 'Per day transaction amount limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : dailyCardLimitCheck
	* @param  :
	* @description : daily check card limit
	*/// ==============================================
	public function dailyCardLimitCheck($input, $check_assign_mid)
	{
		$from_date = Carbon::now()->subDays(1)->toDateTimeString();
		$to_date = Carbon::now()->toDateTimeString();

		$card_daily_transactions = $this->cardLimitBetweenDates($input['card_no'], $input['payment_gateway_id'], $input['user_id'], $from_date, $to_date);

		if ($card_daily_transactions >= $check_assign_mid->per_day_card) {
			return [
				'status' => '5',
				'reason' => 'Per day transactions by card limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : weeklyCardLimitCheck
	* @param  :
	* @description : weekly check card limit
	*/// ==============================================
	public function weeklyCardLimitCheck($input, $check_assign_mid)
	{
		$from_date = Carbon::now()->subDays(7)->toDateTimeString();
		$to_date = Carbon::now()->toDateTimeString();

		$card_weekly_transactions = $this->cardLimitBetweenDates($input['card_no'], $input['payment_gateway_id'], $input['user_id'], $from_date, $to_date);

		if ($card_weekly_transactions >= $check_assign_mid->per_week_card) {
			return [
				'status' => '5',
				'reason' => 'Per week transactions by card limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : monthlyCardLimitCheck
	* @param  :
	* @description : monthly check card limit
	*/// ==============================================
	public function monthlyCardLimitCheck($input, $check_assign_mid)
	{
		$from_date = Carbon::now()->subDays(30)->toDateTimeString();
		$to_date = Carbon::now()->toDateTimeString();

		$card_monthly_transactions = $this->cardLimitBetweenDates($input['card_no'], $input['payment_gateway_id'], $input['user_id'], $from_date, $to_date);

		if ($card_monthly_transactions >= $check_assign_mid->per_month_card) {
			return [
				'status' => '5',
				'reason' => 'Per month transactions by card limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : dailyEMailLimitCheck
	* @param  :
	* @description : daily check EMail limit
	*/// ==============================================
	public function dailyEMailLimitCheck($input, $check_assign_mid)
	{
		$from_date = Carbon::now()->subDays(1)->toDateTimeString();
		$to_date = Carbon::now()->toDateTimeString();

		$email_daily_transactions = $this->emailLimitBetweenDates($input['email'], $input['payment_gateway_id'], $input['user_id'], $from_date, $to_date);

		if ($email_daily_transactions >= $check_assign_mid->per_day_email) {
			return [
				'status' => '5',
				'reason' => 'Per day transactions by email limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : weeklyEMailLimitCheck
	* @param  :
	* @description : weekly check EMail limit
	*/// ==============================================
	public function weeklyEMailLimitCheck($input, $check_assign_mid)
	{
		$from_date = Carbon::now()->subDays(7)->toDateTimeString();
		$to_date = Carbon::now()->toDateTimeString();

		$email_weekly_transactions = $this->emailLimitBetweenDates($input['email'], $input['payment_gateway_id'], $input['user_id'], $from_date, $to_date);

		if ($email_weekly_transactions >= $check_assign_mid->per_week_email) {
			return [
				'status' => '5',
				'reason' => 'Per week transactions by email limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : monthlyEMailLimitCheck
	* @param  :
	* @description : monthly check EMail limit
	*/// ==============================================
	public function monthlyEMailLimitCheck($input, $check_assign_mid)
	{
		$from_date = Carbon::now()->subDays(30)->toDateTimeString();
		$to_date = Carbon::now()->toDateTimeString();

		$email_monthly_transactions = $this->emailLimitBetweenDates($input['email'], $input['payment_gateway_id'], $input['user_id'], $from_date, $to_date);

		if ($email_monthly_transactions >= $check_assign_mid->per_month_card) {
			return [
				'status' => '5',
				'reason' => 'Per month transactions by email limit exceeded.'
			];
		}

		return false;
	}

	// ================================================
	/* method : successAmountBetweenDates
	* @param  :
	* @description : total success amount between dates
	*/// ==============================================
	public function successAmountBetweenDates($payment_gateway_id, $from_date, $to_date)
	{
	    $sum = \DB::table('transactions')
	        ->whereNull('deleted_at')
	        ->where('payment_gateway_id', $payment_gateway_id)
	        ->where('status', '1')
            ->whereBetween('created_at', [$from_date, $to_date])
	        ->sum('amount_in_usd');

	    return $sum;
	}

	// ================================================
	/* method : cardLimitBetweenDates
	* @param  :
	* @description : check limits of card between dates
	*/// ==============================================
	public function cardLimitBetweenDates($card_no, $payment_gateway_id, $user_id, $from_date, $to_date)
	{
		$card_transactions = \DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('status', '<>', '5')
            ->where('card_no', 'like', substr($card_no, 0, 6).'%')
            ->where('card_no', 'like', '%'.substr($card_no, -4))
            ->where('payment_gateway_id', $payment_gateway_id)
            ->where('user_id', $user_id)
            ->whereBetween('created_at', [$from_date, $to_date])
            ->count();

    	return $card_transactions;
	}

	// ================================================
	/* method : emailLimitBetweenDates
	* @param  :
	* @description : check limits of email between dates
	*/// ==============================================
	public function emailLimitBetweenDates($email, $payment_gateway_id, $user_id, $from_date, $to_date)
	{
		$email_transactions = \DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('status', '<>', '5')
            ->where('email', $email)
            ->where('payment_gateway_id', $payment_gateway_id)
            ->where('user_id', $user_id)
            ->whereBetween('created_at', [$from_date, $to_date])
            ->count();

    	return $email_transactions;
	}

	public function midCard($input,$cardType){
		$users = \DB::table('users')->select("amexmid","visamid","mastercardmid","discovermid")->where("id",$input["user_id"])->first();
		$mid = $input["payment_gateway_id"];
		if($cardType == 2 && $users->visamid == "Block"){
			return [
                'status' => '5',
                'reason' => 'VISA Card is blocked.',
                'order_id'=> NULL
            ];
		}
		elseif($cardType == 1 && $users->amexmid == "Block"){
			return [
                'status' => '5',
                'reason' => 'Amex Card is blocked.',
                'order_id'=> NULL
            ];
		}
		elseif($cardType == 3 && $users->mastercardmid == "Block"){
			return [
                'status' => '5',
                'reason' => 'Master Card is blocked.',
                'order_id'=> NULL
            ];
		}
		elseif($cardType == 4 && $users->discovermid == "Block"){
			return [
                'status' => '5',
                'reason' => 'Discover Card is blocked.',
                'order_id'=> NULL
            ];
		}
		if($cardType == 1 && $users->amexmid != "" && $users->amexmid != 0){
			$mid = $users->amexmid;
		}
		elseif($cardType == 2 && $users->visamid != '' && $users->visamid != 0){
			$mid = $users->visamid;
		}
		elseif($cardType == 3 && $users->mastercardmid != '' && $users->mastercardmid != 0){
			$mid = $users->mastercardmid;
		}
		elseif($cardType == 4 && $users->discovermid != '' && $users->discovermid != 0){
			$mid = $users->discovermid;
		}
		return $mid;
	}

	public function countryCheck($payment_gateway_id){

	}
}
