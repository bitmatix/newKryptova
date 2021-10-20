<?php

namespace App\Traits;

use App\User;
use App\Application;

trait RuleCheck
{
	// ================================================
	/* method : checkCurrenctRules
	* @param  :
	* @description : check rules and get payment_gateway_id
	*/// ==============================================
	public function checkCurrencyRules($input)
	{
		$rules = \DB::table('rules')
            ->where('status', '1')
            ->where('deleted_at', null)
            ->where('user_id', null)
            ->where('rules_type', 'Card')
            ->orderBy('priority', 'desc')
            ->get();
        $payment_gateway_id = false;

        $category = Application::where('user_id', $input['user_id'])
        	->value('category_id');
        foreach ($rules as $key => $value) {
        	$rule_condition = str_replace(['currency', 'amount', 'card_type', 'country', 'category','bin_cou_code'],
	        		['"'.$input['currency'].'"', '"'.$input['amount_in_usd'].'"', $input['card_type'], '"'.$input['country'].'"', '"'.$category.'"', '"'.$input['bin_country_code'].'"'],
	        		$value->rule_condition
        		);
        	$condition = "return ".$rule_condition.";";
            $test = eval($condition);
			if ($test) {
            	$payment_gateway_id = $value->assign_mid;
        	}
        }
        return $payment_gateway_id;
	}

	// ================================================
	/* method : checkCurrenctRules
	* @param  :
	* @description : check rules and get payment_gateway_id
	*/// ==============================================
	public function checkCurrencyRulesForCrypto($input)
	{
		$rules = \DB::table('rules')
            ->where('status', '1')
            ->where('deleted_at', null)
            ->where('user_id', null)
            ->where('rules_type', 'Crypto')
            ->orderBy('priority', 'desc')
            ->get();

        $payment_gateway_id = false;

        $category = Application::where('user_id', $input['user_id'])
        	->value('category_id');
        // \Log::info($category);

        foreach ($rules as $key => $value) {

        	$rule_condition = str_replace(
	        		['currency', 'amount', 'card_type', 'country', 'category'],
	        		['"'.$input['currency'].'"', '"'.$input['amount_in_usd'].'"', $input['card_type'], '"'.$input['country'].'"', '"'.$category.'"'],
	        		$value->rule_condition
        		);
        	$condition = "return ".$rule_condition.";";
			$test = eval($condition);
			// dd($test);
			// \Log::info($rule_condition);
        	if ($test) {
				// \Log::info('Yes');
        		$payment_gateway_id = $value->assign_mid;
        	}
        }

        return $payment_gateway_id;
	}

    public function checkCurrencyRulesForBank($input){
        $rules = \DB::table('rules')
            ->where('status', '1')
            ->where('deleted_at', null)
            ->where('user_id', null)
            ->where('rules_type', 'Bank')
            ->orderBy('priority', 'desc')
            ->get();

        $payment_gateway_id = false;

        $category = Application::where('user_id', $input['user_id'])
            ->value('category_id');
        // \Log::info($category);

        foreach ($rules as $key => $value) {

            $rule_condition = str_replace(
                    ['currency', 'amount', 'card_type', 'country', 'category'],
                    ['"'.$input['currency'].'"', '"'.$input['amount_in_usd'].'"', $input['card_type'], '"'.$input['country'].'"', '"'.$category.'"'],
                    $value->rule_condition
                );
            $condition = "return ".$rule_condition.";";
            $test = eval($condition);
            // dd($test);
            // \Log::info($rule_condition);
            if ($test) {
                // \Log::info('Yes');
                $payment_gateway_id = $value->assign_mid;
            }
        }

        return $payment_gateway_id;
    }

    public function checkCurrencyRulesUser($input){
        $rules = \DB::table('rules')
            ->where('status', '1')
            ->where('deleted_at', null)
            ->where('user_id', $input["user_id"])
            ->where('rules_type', 'Card')
            ->orderBy('priority', 'desc')
            ->get();
        $payment_gateway_id = false;

        $category = Application::where('user_id', $input['user_id'])
            ->value('category_id');
        // \Log::info($category);

        foreach ($rules as $key => $value) {

            $rule_condition = str_replace(
                    ['currency', 'amount', 'card_type', 'country', 'category'],
                    ['"'.$input['currency'].'"', '"'.$input['amount_in_usd'].'"', $input['card_type'], '"'.$input['country'].'"', '"'.$category.'"'],
                    $value->rule_condition
                );
            $condition = "return ".$rule_condition.";";
            $test = eval($condition);
            // dd($test);
            // \Log::info($rule_condition);
            if ($test) {
                // \Log::info('Yes');
                $payment_gateway_id = $value->assign_mid;
            }
        }
        return $payment_gateway_id;
    }

    public function checkCurrencyRulesForCryptoUser($input)
    {
        $rules = \DB::table('rules')
            ->where('status', '1')
            ->where('deleted_at', null)
            ->where('user_id', $input["user_id"])
            ->where('rules_type', 'Crypto')
            ->orderBy('priority', 'desc')
            ->get();

        $payment_gateway_id = false;

        $category = Application::where('user_id', $input['user_id'])
            ->value('category_id');
        // \Log::info($category);

        foreach ($rules as $key => $value) {

            $rule_condition = str_replace(
                    ['currency', 'amount', 'card_type', 'country', 'category'],
                    ['"'.$input['currency'].'"', '"'.$input['amount_in_usd'].'"', $input['card_type'], '"'.$input['country'].'"', '"'.$category.'"'],
                    $value->rule_condition
                );
            $condition = "return ".$rule_condition.";";
            $test = eval($condition);
            // dd($test);
            // \Log::info($rule_condition);
            if ($test) {
                // \Log::info('Yes');
                $payment_gateway_id = $value->assign_mid;
            }
        }

        return $payment_gateway_id;
    }

    public function checkCurrencyRulesForBankUser($input){
        $rules = \DB::table('rules')
            ->where('status', '1')
            ->where('deleted_at', null)
            ->where('user_id', $input["user_id"])
            ->where('rules_type', 'Bank')
            ->orderBy('priority', 'desc')
            ->get();

        $payment_gateway_id = false;

        $category = Application::where('user_id', $input['user_id'])
            ->value('category_id');
        // \Log::info($category);

        foreach ($rules as $key => $value) {

            $rule_condition = str_replace(
                    ['currency', 'amount', 'card_type', 'country', 'category'],
                    ['"'.$input['currency'].'"', '"'.$input['amount_in_usd'].'"', $input['card_type'], '"'.$input['country'].'"', '"'.$category.'"'],
                    $value->rule_condition
                );
            $condition = "return ".$rule_condition.";";
            $test = eval($condition);
            // dd($test);
            // \Log::info($rule_condition);
            if ($test) {
                // \Log::info('Yes');
                $payment_gateway_id = $value->assign_mid;
            }
        }

        return $payment_gateway_id;
    }
}
