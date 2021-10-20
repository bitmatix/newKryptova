<?php

namespace App\Traits;

use App\User;
use App\Transaction;
use App\TransactionSession;
use Illuminate\Http\Request;

trait StoreTransaction
{
    // ================================================
    /* method : __construct
    * @param  :
    * @description : new class instance
    */// ==============================================
    public function __construct()
    {
        $this->transaction = new Transaction;
    }

    // ================================================
    /* method : storeTransaction
    * @param  :
    * @description : store transaction and return response
    */// ==============================================
    public function storeTransaction($input)
    {
        unset($input['api_key']);
        unset($input['country_code']);
        unset($input['is_disable_rule']);
        unset($input['bin_country_code']);
        unset($input['request_from_type']);
        $transactionReturn = $this->transaction->storeData($input);
        // update transaction_session record if not pending
        if (!in_array($input['status'], ['2', '7'])) {
            \DB::table('transaction_session')
                ->where('transaction_id', $input['session_id'])
                ->update(['is_completed' => '1']);
        }

        // $redirect_link = $this->getRedirectLink($input);

        return $transactionReturn;
    }

    // ================================================
    /* method : getRedirectLink
    * @param  :
    * @description : get redirect link for gateway
    */// ==============================================
    public function getRedirectLink($input)
    {
        // get transaction status
        $status = $this->getStatus($input['status']);

        $domain = parse_url($input['response_url'], PHP_URL_HOST);

        // return to portal.paypound.ltd with session instead of query string
        if ($domain == 'portal.paypound.ltd123') {
            $redirect_url = $input['response_url'];

            if ($input['status'] == '1') {
                \Session::put('status', 'success');
                \Session::put('success', $input['reason']);
            } elseif ($input['status'] == '2') {
                \Session::put('status', 'pending');
                \Session::put('success', $input['reason']);
            } elseif ($input['status'] == '5') {
                \Session::put('status', 'blocked');
                \Session::put('error', $input['reason']);
            } elseif ($input['status'] == '7') {
                // nothing for 3ds status
            } else {
                \Session::put('status', 'fail');
                \Session::put('error', $input['reason']);
            }
        } else {
            $order_id = $input['order_id'] ?? null;
            $customer_order_id = $input['customer_order_id'] ?? null;

            if (parse_url($input['response_url'], PHP_URL_QUERY)) {
                $redirect_url = $input['response_url'] . '&status=' . $status . '&message=' . $input['reason'] . '&order_id=' . $order_id . '&customer_order_id=' . $customer_order_id;
            } else {
                $redirect_url = $input['response_url'] . '?status=' . $status . '&message=' . $input['reason'] . '&order_id=' . $order_id . '&customer_order_id=' . $customer_order_id;
            }
        }

        return $redirect_url;
    }

    // ================================================
    /* method : updateGatewayResponseData
    * @param  :
    * @description : update session response data
    */// ==============================================
    public function updateGatewayResponseData($input, $response_data)
    {
        try {
            // update transaction_session record
            $session_update_data = TransactionSession::where('transaction_id', $input['session_id'])
                ->first();

            $session_request_data = json_decode($session_update_data->request_data, 1);

            $session_request_data['gateway_id'] = $input['gateway_id'];

            $session_update_data->update([
                'request_data' => json_encode($session_request_data),
                'gateway_id' => $input['gateway_id'],
                'response_data' => json_encode($response_data)
            ]);

            $session_update_data->save();
        } catch (\Exception $e) {
            \Log::info([
                'session_update_error' => $e->getMessage()
            ]);

            return true;
        }

        return true;
    }

    // ================================================
    /* method : getStatus
    * @param  :
    * @description : status response by status code
    */// ==============================================
    public function getStatus($code)
    {
        $status = [
            '0' => 'fail',
            '1' => 'success',
            '2' => 'pending',
            '5' => 'blocked',
            '7' => '3d_redirect',
        ];

        if (array_key_exists($code, $status)) {
            return $status[$code];
        } else {
            return '0';
        }
    }
}
