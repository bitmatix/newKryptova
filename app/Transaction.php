<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mail;
use Auth;
use Exception;
use App\User;
use Carbon\Carbon;
use App\Application;
use App\Mail\TransactionMail;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
// use GeneaLabs\LaravelModelCaching\Traits\Cachable;


class Transaction extends Model
{
    // use Cachable;
    use SoftDeletes;
    protected $table = 'transactions';
    protected $guarded = [];
    // protected $fillable = [
    //     'user_id', 'order_id', 'session_id', 'gateway_id', 'first_name', 'last_name', 'address', 'customer_order_id', 'country',
    //     'state', 'city', 'zip', 'ip_address', 'email', 'phone_no', 'card_type', 'amount', 'amount_in_usd', 'currency', 'card_no',
    //     'ccExpiryMonth', 'ccExpiryYear', 'cvvNumber', 'status', 'reason', 'descriptor', 'payment_gateway_id', 'payment_type',
    //     'merchant_discount_rate', 'bank_discount_rate', 'net_profit_amount', 'chargebacks', 'chargebacks_date', 'chargebacks_remove',
    //     'chargebacks_remove_date', 'refund', 'refund_reason', 'refund_date', 'refund_remove', 'refund_remove_date', 'is_flagged', 'flagged_by',
    //     'flagged_date', 'is_flagged_remove', 'flagged_remove_date', 'is_retrieval', 'retrieval_date', 'is_retrieval_remove',
    //     'retrieval_remove_date', 'is_converted', 'converted_amount', 'converted_currency', 'is_converted_user_currency', 'converted_user_amount',
    //     'converted_user_currency', 'website_url_id', 'request_from_ip', 'request_origin', 'is_request_from_vt', 'is_transaction_type',
    //     'is_webhook', 'response_url', 'webhook_url', 'webhook_status', 'webhook_retry', 'transactions_token', 'bin_details', 'transaction_hash',
    //     'is_duplicate_delete', 'transaction_date',
    // ];
    protected $dates = ['created_at', 'updated_at', 'chargebacks_date', 'refund_date', 'flagged_date', 'retrieval_date'];
    public function setCardNoAttribute($value)
    {
        $this->attributes['card_no'] = Crypt::encryptString($value);
    }

    public function setccExpiryMonthAttribute($value)
    {
        $this->attributes['ccExpiryMonth'] = Crypt::encryptString($value);
    }

    public function setccExpiryYearAttribute($value)
    {
        $this->attributes['ccExpiryYear'] = Crypt::encryptString($value);
    }

    public function setcvvNumberAttribute($value)
    {
        $this->attributes['cvvNumber'] = Crypt::encryptString($value);
    }

    public function getCardNoAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getCcExpiryMonthAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getCcExpiryYearAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getCvvNumberAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getRefundsMerchantTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }

        $data = static::select('applications.business_name as userName', 'transactions.*', 'middetails.bank_name')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id)
            ->where('transactions.chargebacks', '0')
            ->where('transactions.refund', '1');
        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        $this->filterTransactionData($input, $data);
        $data = $data->orderBy('transactions.refund_date', 'desc')->paginate($noList);
        return $data;
    }

    public function getChargebacksMerchantTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }
        
        $data =  static::select('applications.business_name as userName', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files', 'middetails.bank_name')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"chargebacks"'));
            })
            ->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id)
            ->where('transactions.chargebacks', '1');
        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        $this->filterTransactionData($input, $data);
        $data = $data->orderBy('transactions.chargebacks_date', 'desc')->paginate($noList);
        return $data;
    }

    public function getFlaggedMerchantTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }

        $data = static::select('applications.business_name as userName', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files', 'middetails.bank_name')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"flagged"'));
            })
            ->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id)
            ->where('transactions.chargebacks', '0')
            ->where('transactions.is_flagged', '1')
            ->where('transactions.is_flagged_remove', '0');

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        $this->filterTransactionData($input, $data);
        //echo $data->toSql();exit();
        $data = $data->orderBy('transactions.flagged_date', 'DESC')->paginate($noList);
        return $data;
    }


    public function getMerchantRemovedFlaggedTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }
        
        $data = static::select('applications.business_name as userName', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"flagged"'));
            })
            ->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id)
            ->where('transactions.is_flagged', '1')
            ->where('transactions.is_flagged_remove', '1');

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        $this->filterTransactionData($input, $data);

        $data = $data->orderBy('transactions.flagged_date', 'DESC')->paginate($noList);

        return $data;
    }

    public function getRetrivalMerchantTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }

        $data = static::select('applications.business_name as userName', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files', 'middetails.bank_name')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"retrieval"'));
            })
            ->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id)
            ->where('transactions.chargebacks', '0')
            ->where('transactions.is_retrieval', '1');

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        $this->filterTransactionData($input, $data);
        $data = $data->orderBy('transactions.flagged_date', 'DESC')->paginate($noList);
        return $data;
    }

    public function getMerchantTestTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }

        $data = static::select('applications.business_name as userName', 'transactions.*')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereIn('transactions.payment_gateway_id', $payment_gateway_id);

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }
        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email', 'like', '%' . $input['email'] . '%');
        }
        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }
        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name', 'like', '%' . $input['first_name'] . '%');
        }
        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name', 'like', '%' . $input['last_name'] . '%');
        }
        if (isset($input['currency']) && $input['currency'] != '') {
            $data = $data->where('transactions.currency', $input['currency']);
        }
        if (isset($input['customer_order_id']) && $input['customer_order_id'] != '') {
            $data = $data->where('transactions.customer_order_id', 'like', '%' . $input['customer_order_id'] . '%');
        }
        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }
        if ((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date =   $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        $data = $data->orderBy('id', 'DESC')->paginate($noList);
        return $data;
    }

    public function getMerchantDeclinedTransactions($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');        

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }

        $data = static::select("transactions.*", "applications.business_name as userName", "middetails.bank_name")
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->where('transactions.status', '0');

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email', 'like', '%' . $input['email'] . '%');
        }
        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }
        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name', 'like', '%' . $input['first_name'] . '%');
        }
        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name', 'like', '%' . $input['last_name'] . '%');
        }
        if (isset($input['currency']) && $input['currency'] != '') {
            $data = $data->where('transactions.currency', $input['currency']);
        }
        if (isset($input['customer_order_id']) && $input['customer_order_id'] != '') {
            $data = $data->where('transactions.customer_order_id', 'like', '%' . $input['customer_order_id'] . '%');
        }
        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }
        if ((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date =   $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        $data = $data->orderBy('id', 'DESC')->paginate($noList);
        return $data;
    }
    public function filterTransactionData($input, $data)
    {
        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name', 'like', '%' . $input['first_name'] . '%');
        }
        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name', 'like', '%' . $input['last_name'] . '%');
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email', 'like', '%' . $input['email'] . '%');
        }
        if (isset($input['currency']) && $input['currency'] != '') {
            $data = $data->where('transactions.currency', $input['currency']);
        }
        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }
        if (isset($input['customer_order_id']) && $input['customer_order_id'] != '') {
            $data = $data->where('transactions.customer_order_id', 'like', '%' . $input['customer_order_id'] . '%');
        }
        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }
        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }
        if ((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        //refund date filter
        if ((isset($input['refund_start_date']) && $input['refund_start_date'] != '') && (isset($input['refund_end_date']) && $input['refund_end_date'] != '')) {
            $start_date = $input['refund_start_date'];
            $end_date = $input['refund_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.refund_date)'), '<=', $end_date);
        } else if ((isset($input['refund_start_date']) && $input['refund_start_date'] != '') || (isset($input['refund_end_date']) && $input['refund_end_date'] == '')) {
            $start_date = $input['refund_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '>=', $start_date);
        } else if ((isset($input['refund_start_date']) && $input['refund_start_date'] == '') || (isset($input['refund_end_date']) && $input['refund_end_date'] != '')) {
            $end_date = $input['refund_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '<=', $end_date);
        }
        //chargebacks date filter
        if ((isset($input['chargebacks_start_date']) && $input['chargebacks_start_date'] != '') && (isset($input['chargebacks_end_date']) && $input['chargebacks_end_date'] != '')) {
            $start_date = $input['chargebacks_start_date'];
            $end_date = $input['chargebacks_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', $end_date);
        } else if ((isset($input['chargebacks_start_date']) && $input['chargebacks_start_date'] != '') || (isset($input['chargebacks_end_date']) && $input['chargebacks_end_date'] == '')) {
            $start_date = $input['chargebacks_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', $start_date);
        } else if ((isset($input['chargebacks_start_date']) && $input['chargebacks_start_date'] == '') || (isset($input['chargebacks_end_date']) && $input['chargebacks_end_date'] != '')) {
            $end_date = $input['chargebacks_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', $end_date);
        }
        //retrieval date filter
        if ((isset($input['retrieval_start_date']) && $input['retrieval_start_date'] != '') && (isset($input['retrieval_end_date']) && $input['retrieval_end_date'] != '')) {
            $start_date = $input['retrieval_start_date'];
            $end_date = $input['retrieval_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.retrieval_date)'), '<=', $end_date);
        } else if ((isset($input['retrieval_start_date']) && $input['retrieval_start_date'] != '') || (isset($input['retrieval_end_date']) && $input['retrieval_end_date'] == '')) {
            $start_date = $input['retrieval_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '>=', $start_date);
        } else if ((isset($input['retrieval_start_date']) && $input['retrieval_start_date'] == '') || (isset($input['retrieval_end_date']) && $input['retrieval_end_date'] != '')) {
            $end_date = $input['retrieval_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '<=', $end_date);
        }
        //flagged date filter
        if ((isset($input['flagged_start_date']) && $input['flagged_start_date'] != '') && (isset($input['flagged_end_date']) && $input['flagged_end_date'] != '')) {
            $start_date = $input['flagged_start_date'];
            $end_date = $input['flagged_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.flagged_date)'), '<=', $end_date);
        } else if ((isset($input['flagged_start_date']) && $input['flagged_start_date'] != '') || (isset($input['flagged_end_date']) && $input['flagged_end_date'] == '')) {
            $start_date = $input['flagged_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '>=', $start_date);
        } else if ((isset($input['flagged_start_date']) && $input['flagged_start_date'] == '') || (isset($input['flagged_end_date']) && $input['flagged_end_date'] != '')) {
            $end_date = $input['flagged_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '<=', $end_date);
        }
        return $data;
    }

    public function getData($input)
    {
        if (\Auth::user()->main_user_id != 0 && \Auth::user()->is_sub_user == '1')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        $data = static::orderBy('id', 'DESC')
            ->where('user_id', $userID);

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['website_url_id']) && $input['website_url_id'] != '') {
            $data = $data->where('transactions.website_url_id', $input['website_url_id']);
        }

        // if(isset($input['card_no']) && $input['card_no'] != '') {
        //     $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        // }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id',  $input['order_id']);
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('payment_gateway_id', '!=', '41')
            ->where('payment_gateway_id', '!=', '16');


        if (isset($input['card_no']) && $input['card_no'] != '') {
            $filteredTransactions = $data->get()->filter(function ($record) use ($input) {
                if (strpos($record->card_no, $input['card_no']) !== false) {
                    return $record;
                }
            });
            $perPage = 10;
            $currentPage = (!empty($input['page'])  ? $input['page'] : 1);
            $pagedData = $filteredTransactions->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $data = new \Illuminate\Pagination\LengthAwarePaginator($pagedData, count($filteredTransactions), $perPage, $currentPage, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]);
        } else {
            $data = $data->paginate(10);
        }

        return $data;
    }

    // ================================================
    /* method : getTestTransactionsData
    * @param  :
    * @Description : get test transaction data
    */ // ==============================================
    public function getTestTransactionsData($input)
    {
        if (\Auth::user()->main_user_id != 0 && \Auth::user()->is_sub_user == '1')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        $data = static::orderBy('id', 'DESC')
            ->where('user_id', $userID);

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where(function ($query) {
            $query->orWhere('transactions.payment_gateway_id', '16')
                ->orWhere('transactions.payment_gateway_id', '41');
        });

        $data = $data->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0');

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $filteredTransactions = $data->get()->filter(function ($record) use ($input) {
                if (strpos($record->card_no, $input['card_no']) !== false) {
                    return $record;
                }
            });
            $perPage = 10;
            $currentPage = (!empty($input['page'])  ? $input['page'] : 1);
            $pagedData = $filteredTransactions->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $data = new \Illuminate\Pagination\LengthAwarePaginator($pagedData, count($filteredTransactions), $perPage, $currentPage, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]);
        } else {
            $data = $data->paginate(10);
        }

        return $data;
    }

    public function productsWiseTransactionData($input, $key)
    {
        $user = User::where('api_key', $key)->first();
        $productID = Product::where('user_id', $user->id)->pluck('id');
        return static::select('transactions.*', 'product.name as productName')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->join('product', 'product.id', '=', 'transactions.product_id')
            ->where('users.api_key', $key)
            ->whereIn('transactions.product_id', $productID);

        return $data;
    }

    public function getLatestTransactionsDash()
    {

        if (\Auth::user()->main_user_id != '0')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        return static::where('user_id', $userID)->whereNotIn('payment_gateway_id', ['1', '2'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function getLatestRefundTransactionsDash()
    {
        if (\Auth::user()->main_user_id != '0')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        return static::where('user_id', $userID)
            ->where('refund', '1')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getLatestChargebackTransactionsDash()
    {
        if (\Auth::user()->main_user_id != '0')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        return static::where('user_id', $userID)
            ->where('chargebacks', '1')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getSubData($input, $id)
    {
        if (\Auth::user()->main_user_id != 0 && \Auth::user()->is_sub_user == '1')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        $data = static::orderBy('id', 'DESC')
            ->where('user_id', $userID);
        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('is_reccuring_transaction_id', $id)
            ->where('is_batch_transaction', '0')
            ->get();

        return $data;
    }

    public function getRecurringTransactionsData($input)
    {
        if (\Auth::user()->main_user_id != 0 && \Auth::user()->is_sub_user == '1')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        $data = static::orderBy('id', 'DESC')
            ->where('user_id', $userID);
        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('is_recurring', '1');

        $data = $data->get();

        return $data;
    }

    public function getSubTransactionsData($input, $id)
    {
        if (\Auth::user()->main_user_id != 0 && \Auth::user()->is_sub_user == '1')
            $userID = \Auth::user()->main_user_id;
        else
            $userID = \Auth::user()->id;

        $data = static::orderBy('id', 'DESC')
            ->where('user_id', $userID);
        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('is_reccuring_transaction_id', $id)
            ->get();

        return $data;
    }

    public function findData($id)
    {
        $data = static::select('middetails.*', 'transactions.*')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->where('transactions.id', $id)
            ->first();
        return $data;
    }

    public function storeData($input)
    {
        // \Log::info($input);
        $user = User::where('id', $input['user_id'])
            ->first();
        if (isset($input['card_no']) && $input['card_no'] != null) {
            $input['card_no'] = substr($input['card_no'], 0, 6) . 'XXXXXX' . substr($input['card_no'], -4);
            $input['cvvNumber'] = 'XXX';
        }

        $input['created_at'] = date('Y-m-d H:i:s');
        $input['updated_at'] = date('Y-m-d H:i:s');
        $input['transaction_date'] = date('Y-m-d H:i:s');
        if (isset($input['is_disable_rule'])) {
            unset($input['is_disable_rule']);
        }

        // check if transaction already completed
        $old_transaction = static::where('session_id', $input['session_id'])
            ->whereNotIn('status', ['2', '7'])
            ->first();

        if ($old_transaction != null) {
            return $old_transaction;
        }

        // send transaction notification
        if ($user != null && $input['status'] == '1' && $user->merchant_transaction_notification == '1') {

            try {
                $mailInput = $input;
                $mailInput['user_name'] = $user->name;
                $mailInput['first_name'] = $input['first_name'];
                $mailInput['last_name'] = $input['last_name'];
                $mailInput['order_id'] = $input['order_id'];
                $mailInput['email'] = $input['email'];
                $mailInput['card_no'] = $input['card_no'];
                $mailInput['amount'] = $input['amount'];
                $mailInput['currency'] = $input['currency'];
                $mailInput['created_at'] = $input['created_at'];

                \Mail::to($user->email)->send(new TransactionMail($mailInput));
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }
        }

        // check if transaction already completed
        $pending_transaction = static::where('session_id', $input['session_id'])
            ->whereIn('status', ['2', '7'])
            ->first();

        if ($pending_transaction != null) {

            static::where('session_id', $input['session_id'])
                ->update([
                    'reason' => $input['reason'],
                    'status' => $input['status']
                ]);
            $transaction = $pending_transaction;
        } else {
            $input['created_at'] = date('Y-m-d H:i:s');
            $transaction = static::insert($input);
        }

        if (isset($input['webhook_url']) && $input['webhook_url'] != null) {
            if (isset($input['status']) && $input['status'] == '1') {
                $transactionStatus = 'success';
            } elseif (isset($input['status']) && $input['status'] == '2') {
                $transactionStatus = 'pending';
            } else {
                $transactionStatus = 'fail';
            }

            $request_data['order_id'] = $input['order_id'];
            $request_data['customer_order_id'] = $input['customer_order_id'] ?? null;
            $request_data['transaction_status'] = $transactionStatus;
            $request_data['reason'] = $input['reason'];
            $request_data['currency'] = $input['currency'];
            $request_data['amount'] = $input['amount'];
            // $request_data['test'] = in_array($input['payment_gateway_id'], ['16', '41']) ? true : false;
            $request_data['transaction_date'] = date('Y-m-d H:i:s');

            // send webhook request
            try {
                // $http_response = postCurlRequestBackUpTwo($input['webhook_url'], $request_data);
                $http_response = postCurlRequest($input['webhook_url'], $request_data);
            } catch (Exception $e) {
                $http_response = 'FAILED';
                \Log::info([
                    'webhook error' => $e->getMessage()
                ]);
            }
        }
        return $transaction;
    }

    public function updateData($id, $input)
    {
        return static::find($id)->update($input);
    }

    public function destroyData($id)
    {
        return static::where('id', $id)->delete();
    }

    public function getLineChartData()
    {
        $start_date = Carbon::now()->subDays(30);
        $end_date = Carbon::now();

        $userDetails = Auth::user();
        ($userDetails->is_sub_user == 1) ? ($user_id = $userDetails->main_user_id) : ($user_id = Auth::user()->id);

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));
        }

        $successTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('user_id', $user_id)
            ->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $refundTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('user_id', $user_id)
            ->where('refund', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $chargebacksTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('user_id', $user_id)
            ->where('chargebacks', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $failTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('user_id', $user_id)
            ->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->whereMonth('created_at', date('m'))
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $flaggedTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('user_id', $user_id)
            ->where('is_flagged', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $data_array = [];
        $i = 0;
        while (strtotime($start_date) <= strtotime($end_date)) {
            $start_date = date("Y-n-j", strtotime($start_date));

            // date
            $data_array[$i][] = date("Y-m-d", strtotime($start_date));

            // success value
            if (isset($successTran[$start_date])) {
                $data_array[$i][] = $successTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // failed value
            if (isset($failTran[$start_date])) {
                $data_array[$i][] = $failTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // chargeback value
            if (isset($chargebacksTran[$start_date])) {
                $data_array[$i][] = $chargebacksTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // refund value
            if (isset($refundTran[$start_date])) {
                $data_array[$i][] = $refundTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // flagged value
            if (isset($flaggedTran[$start_date])) {
                $data_array[$i][] = $flaggedTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            $i++;
            $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
        }
        return $data_array;
    }

    // ================================================
    /*  method : getAdminLineChartData
    * @ param  :
    * @ Description : get line chart transactions data for admin dashboard
    */ // ==============================================
    public function getAdminLineChartData($input)
    {
        $start_date = Carbon::now()->subDays(30);
        $end_date = Carbon::now();
        $date_condition = "";
        $user_condition = "";

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $date_condition = "and created_at between $start_date and $end_date";
        } else {

            $date_condition = "and created_at between date_sub(now() , interval 31 day) and now() ";
        }

        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $user_id = $input['user_id'];
            $user_condition = "and user_id = $user_id";
        }
        $table = '';
        $query = <<<SQL
    select  DATE_FORMAT(created_at, '%Y-%c-%e') as day , sum(tx) as user_count
    from

SQL;

        $where = <<<SQL
    where 1
    $user_condition
    $date_condition
    group by 1
SQL;

        $table = 'tx_success';
        $select = $query . $table . $where;
        $successTran = collect(\DB::select($select))->pluck('user_count', 'day');

        $table = 'tx_refunds';
        $select = $query . $table . $where;
        $refundTran = collect(\DB::select($select))->pluck('user_count', 'day');

        $table = 'tx_chargebacks';
        $select = $query . $table . $where;
        $chargebacksTran = collect(\DB::select($select))->pluck('user_count', 'day');

        $table = 'tx_decline';
        $select = $query . $table . $where;
        $failTran = collect(\DB::select($select))->pluck('user_count', 'day');

        $table = 'tx_flagged';
        $select = $query . $table . $where;
        $flaggedTran = collect(\DB::select($select))->pluck('user_count', 'day');

        $data_array = [];
        $i = 0;
        while (
            strtotime($start_date) <= strtotime($end_date)
        ) {
            $start_date = date("Y-n-j", strtotime($start_date));

            // date
            $data_array[$i][] = date("Y-m-d", strtotime($start_date));

            // success value
            if (isset($successTran[$start_date])) {
                $data_array[$i][] = $successTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // failed value
            if (isset($failTran[$start_date])) {
                $data_array[$i][] = $failTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // chargeback value
            if (isset($chargebacksTran[$start_date])) {
                $data_array[$i][] = $chargebacksTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // refund value
            if (isset($refundTran[$start_date])) {
                $data_array[$i][] = $refundTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // flagged value
            if (isset($flaggedTran[$start_date])) {
                $data_array[$i][] = $flaggedTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            $i++;
            $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
        }

        return $data_array;
    }

    // ================================================
    /*  method : getMonthlyChartDataInTransactionChart
    * @ param  :
    * @ Description : get monthly line chart transactions data for admin dashboard
    */ // ==============================================
    private function getMonthlyTrans($trans)
    {

        $arr = [];
        $old_k = "0";
        $cur_kv = "0";
        $cur_k = "";
        $count = 0;
        $day = '';

        foreach ($trans as $a) {
            foreach ($a as $k => $v) {
                if ($k == 'currency') {
                    $currency = $v;
                }
                if ($k == 'day') {
                    $day = $v;
                }
                if ($k == 'approved_vol')   $approved_vol = $v;
                if ($k == 'declined_vol')   $declined_vol = $v;
                if ($k == 'cb_vol')         $cb_vol = $v;
                if ($k == 'flagged_vol')    $flagged_vol = $v;
                if ($k == 'refund_vol')     $refund_vol = $v;
                if ($k == 'approved_tx')    $approved_tx = $v;
                if ($k == 'declined_tx')    $declined_tx = $v;
                if ($k == 'cb_tx')          $cb_tx = $v;
                if ($k == 'flagged_tx')          $flagged_tx = $v;
                if ($k == 'refund_tx')      $refund_tx = $v;

                if ($k == 'refund_tx') {
                    $tx = $v;
                    $ar = [];
                    $ar['day'] = $day;

                    $ar['approved_vol'] = $approved_vol;
                    $ar['declined_vol'] = $declined_vol;
                    $ar['cb_vol']       = $cb_vol;
                    $ar['flagged_vol']  = $flagged_vol;
                    $ar['refund_vol']   = $refund_vol;

                    $ar['approved_tx'] = $approved_tx;
                    $ar['decline_tx']  = $declined_tx;
                    $ar['cb_tx']       = $cb_tx;
                    $ar['flagged_tx']  = $flagged_tx;
                    $ar['refund_tx']   = $refund_tx;

                    if (!isset($arr[$currency]))
                        $arr[$currency] = [];
                    if (!isset($arday[$day]))
                        $arday[$day] = [];
                    //array_push($arr[$currency], $ar);
                    //array_push($arday[$day], $ar);
                    array_push($arr[$currency], $ar);
                }
            }
        }

        return $arr;
    }


    public function getMonthlyChartDataInTransactionChart2($input)
    {
        $date_condition = "";
        $user_condition = "";
        if ($input['type'] == 'monthly') {
            $date_fmt = "'%Y-%m'";
        } else if ($input['type'] == 'yearly') {
            $date_fmt = "'%Y-%m-%d'";
        } else {
            $date_fmt = "'%Y-%m-%d'";
        }
        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $date_condition = "and created_at between '" . $start_date . "' and '" . $end_date . "' ";
        }


        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $user_id = $input['user_id'];
            //$user_condition = "and user_id = $user_id";
        }
        $table = '';

        // $query = <<<SQL
        //     select  currency, DATE_FORMAT(created_at,"%Y-%m") as day, sum(volume) volume, sum(tx) as tx
        //     from

        // SQL;

        $query = <<<SQL
    select
    currency, day,

    sum(volume) as total_vol,
    sum(tx) as total_tx,

    sum(case when STAT = 'APPROVED' then volume else 0.00 end) as approved_vol,
    sum(case when STAT = 'APPROVED' then tx else 0 end) as approved_tx,
    sum(case when STAT = 'DECLINED' then volume else 0.00 end) as declined_vol,
    sum(case when STAT = 'DECLINED' then tx else 0 end) as declined_tx,
    sum(case when STAT = 'CHARGEBACK' then volume else 0.00 end) as cb_vol,
    sum(case when STAT = 'CHARGEBACK' then tx else 0 end) as cb_tx,
    sum(case when STAT = 'PENDING' then volume else 0.00 end) as pending_vol,
    sum(case when STAT = 'PENDING' then tx else 0 end) as pending_tx,
    sum(case when STAT = 'FLAGGED' then volume else 0.00 end) as flagged_vol,
    sum(case when STAT = 'FLAGGED' then tx else 0 end) as flagged_tx,
    sum(case when STAT = 'REFUND' then volume else 0.00 end) as refund_vol,
    sum(case when STAT = 'REFUND' then tx else 0 end) as refund_tx

from
(
    select  'APPROVED' as STAT, currency, DATE_FORMAT(created_at,       $date_fmt) as day, volume volume, tx as tx, created_at
    from
    tx_success
    union
    select  'DECLINED' as STAT, currency, DATE_FORMAT(created_at,    $date_fmt) as day, volume volume, tx as tx, created_at
    from
    tx_decline
    union
    select  'CHARGEBACK' as STAT, currency, DATE_FORMAT(created_at,     $date_fmt) as day, volume volume, tx as tx, created_at
    from
    tx_chargebacks
    union
    select  'PENDING' as STAT, currency, DATE_FORMAT(created_at,        $date_fmt) as day, volume volume, tx as tx, created_at
    from
    tx_pending
    union
    select  'FLAGGED' as STAT, currency, DATE_FORMAT(created_at,        $date_fmt) as day, volume volume, tx as tx, created_at
    from
    tx_flagged
    union
    select  'REFUND' as STAT, currency, DATE_FORMAT(created_at,         $date_fmt) as day, volume volume, tx as tx, created_at
    from
    tx_refunds
) a

SQL;

        $where = <<<SQL
    where 1
    $user_condition
    $date_condition
    and currency in ('USD', 'GBP', 'EUR')
    group by 1,2
    order by 1, 2
SQL;

        $select = $query . $where;
        $query = \DB::select($select);
        $array = (array) json_decode(json_encode($query), true);

        //dd($input);
        // if(isset($input['type']) && isset($input['filter']))
        // {

        //     $arr = $this->getMonthlyTrans($array);
        // }
        //dd($input);
        if ($input['type'] == 'daily' or (isset($input['filter']) && $input['ftype'] == "fdaily")) {
            $usdcurrencydataarr = array();
            $gbpcurrencydataarr = array();
            $eurcurrencydataarr = array();
            foreach ($array as $arrayusd) {
                if ($arrayusd['currency'] == 'USD') {
                    $usdcurrencydataarr[] = $arrayusd['day'];
                }
                if ($arrayusd['currency'] == 'GBP') {
                    $gbpcurrencydataarr[] = $arrayusd['day'];
                }
                if ($arrayusd['currency'] == 'EUR') {
                    $eurcurrencydataarr[] = $arrayusd['day'];
                }
            }

            $monthstartdate = strtotime($input['sdate_string']);
            $monthenddate = strtotime($input['edate_string']);

            while ($monthstartdate <= $monthenddate) {
                if (!in_array(date('Y-m-d', $monthstartdate), $usdcurrencydataarr)) {
                    $array[] = array('currency' => 'USD', 'day' => date('Y-m-d', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }
                if (!in_array(date('Y-m-d', $monthstartdate), $gbpcurrencydataarr)) {
                    $array[] = array('currency' => 'GBP', 'day' => date('Y-m-d', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }
                if (!in_array(date('Y-m-d', $monthstartdate), $eurcurrencydataarr)) {
                    $array[] = array('currency' => 'EUR', 'day' => date('Y-m-d', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }
                $monthstartdate += 86400;
            }
            usort($array, function ($a, $b) {
                return $a['day'] <=> $b['day'];
            });
            $object = (object) $array;
            $arr = $this->getMonthlyTrans($array);
        } else if ($input['type'] == 'monthly' or (isset($input['filter']) && $input['ftype'] == "fmonthly")) {
            $usdcurrencydataarr = array();
            $gbpcurrencydataarr = array();
            $eurcurrencydataarr = array();

            foreach ($array as $arrayusd) {
                if ($arrayusd['currency'] == 'USD') {
                    $usdcurrencydataarr[] = $arrayusd['day'];
                }
                if ($arrayusd['currency'] == 'GBP') {
                    $gbpcurrencydataarr[] = $arrayusd['day'];
                }
                if ($arrayusd['currency'] == 'EUR') {
                    $eurcurrencydataarr[] = $arrayusd['day'];
                }
            }
            // echo "<pre>";
            // print_r( $usdcurrencydataarr);
            // print_r( $gbpcurrencydataarr);
            // print_r( $eurcurrencydataarr);
            // die;
            $start = $input['sdate_string'];
            $end = $input['edate_string'];
            $monthstartdate = strtotime($start);
            $monthenddate = strtotime($end);

            //dd($start,$end);
            // dd($end);

            while ($monthstartdate <= $monthenddate) {
                if (!in_array(date('Y-m', $monthstartdate), $usdcurrencydataarr)) {
                    $array[] = array('currency' => 'USD', 'day' => date('Y-m', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }
                if (!in_array(date('Y-m', $monthstartdate), $gbpcurrencydataarr)) {
                    $array[] = array('currency' => 'GBP', 'day' => date('Y-m', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }
                if (!in_array(date('Y-m', $monthstartdate), $eurcurrencydataarr)) {

                    $array[] = array('currency' => 'EUR', 'day' => date('Y-m', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }

                $monthstartdate = strtotime("+1 months", $monthstartdate);
            }
            usort($array, function ($a, $b) {
                return $a['day'] <=> $b['day'];
            });
            $object = (object) $array;
            $arr = $this->getMonthlyTrans($array);
        } else if ($input['type'] == 'yearly' or (isset($input['filter']) && $input['ftype'] == "fyearly")) {

            $usdcurrencydataarr = array();
            $gbpcurrencydataarr = array();
            $eurcurrencydataarr = array();
            foreach ($array as $arrayusd) {
                if ($arrayusd['currency'] == 'USD') {
                    $usdcurrencydataarr[] = $arrayusd['day'];
                }
                if ($arrayusd['currency'] == 'GBP') {
                    $gbpcurrencydataarr[] = $arrayusd['day'];
                }
                if ($arrayusd['currency'] == 'EUR') {
                    $eurcurrencydataarr[] = $arrayusd['day'];
                }
            }

            $start = $input['sdate_string'];
            $end = $input['edate_string'];
            $monthstartdate = strtotime($start);
            $monthenddate = strtotime($end);


            while ($monthstartdate <= $monthenddate) {

                if (!in_array(date('Y-m-d', $monthstartdate), $usdcurrencydataarr)) {
                    $array[] = array('currency' => 'USD', 'day' => date('Y-m-d', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }

                if (!in_array(date('Y-m-d', $monthstartdate), $gbpcurrencydataarr)) {
                    $array[] = array('currency' => 'GBP', 'day' => date('Y-m-d', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }

                if (!in_array(date('Y-m-d', $monthstartdate), $eurcurrencydataarr)) {

                    $array[] = array('currency' => 'EUR', 'day' => date('Y-m-d', $monthstartdate), 'total_vol' => '2012.13', 'total_tx' => '0', 'approved_vol' => '0.00', 'approved_tx' => '0', 'declined_vol' => '0.00', 'declined_tx' => '0', 'cb_vol' => '0.00', 'cb_tx' => '0', 'pending_vol' => '0.00', 'pending_tx' => '0', 'flagged_vol' => '0.00', 'flagged_tx' => '0', 'refund_vol' => '0.00', 'refund_tx' => '0');
                }
                $monthstartdate += 86400;
            }

            usort($array, function ($a, $b) {
                return $a['day'] <=> $b['day'];
            });
            $object = (object) $array;
            $arr = $this->getMonthlyTrans($array);
            //dd($arr);
        } else {
            $arr = $this->getMonthlyTrans($array);
        }


        /////

        $query2 = <<<SQL
    select currency,
        sum(approved_vol) approved_vol,
        100*(sum(approved_vol) / sum(total_vol)) as success_percent,

        sum(declined_vol) declined_vol,
        100*(sum(declined_vol) / sum(total_vol)) as declined_percent,

        sum(cb_vol) cb_vol,
        100*(sum(cb_vol) / sum(total_vol)) as cb_percent,

        sum(flagged_vol) flagged_vol,
        100*(sum(flagged_vol) / sum(total_vol)) as flagged_percent,

        sum(refund_vol) refund_vol,
        100*(sum(refund_vol) / sum(total_vol)) as refund_percent,

        sum(total_vol) total_vol
    from
    (
SQL;

        $where2 = <<<SQL
    ) b
    group by currency
    SQL;

        $select2 = $query2 . $select . $where2;
        $query2 = \DB::select($select2);
        $array2 = (array) json_decode(json_encode($query2), true);
        $object2 = (object) $array;
        $arr2 = $array2;

        if (count($arr2) == 0) {
            $defaultArry = ["approved_vol" => "0.00", "success_percent" => "0.000000", "declined_vol" => "0.00", "declined_percent" => "0.000000", "cb_vol" => "0.00", "cb_percent" => "0.000000", "flagged_vol" => "0.00", "flagged_percent" => "0.000000", "refund_vol" => "0.00", "refund_percent" => "0.000000", "total_vol" => "0.00"];
            $curr = ['USD', 'EUR', 'GBP'];
            foreach ($curr as $newcr) {

                $defaultArry['currency'] = $newcr;
                $arr2[] = $defaultArry;
            }
        } else if (count($arr2) < 3) {
            $defaultArry = ["approved_vol" => "0.00", "success_percent" => "0.000000", "declined_vol" => "0.00", "declined_percent" => "0.000000", "cb_vol" => "0.00", "cb_percent" => "0.000000", "flagged_vol" => "0.00", "flagged_percent" => "0.000000", "refund_vol" => "0.00", "refund_percent" => "0.000000", "total_vol" => "0.00"];
            foreach ($arr2 as $newarry) {
                if ($newarry['currency'] !== 'USD') {
                    $defaultArry['currency'] = "USD";
                    $arr2[] = $defaultArry;
                }

                if ($newarry['currency'] !== 'EUR') {
                    $defaultArry['currency'] = "EUR";
                    $arr2[] = $defaultArry;
                }

                if ($newarry['currency'] !== 'GBP') {
                    $defaultArry['currency'] = "GBP";
                    $arr2[] = $defaultArry;
                }
            }
        } else {
            $arr2 = $array2;
        }


        $data_array = array("transactions" => $arr, "totals" => $arr2);
        //dd($data_array);
        return $data_array;
    }

    public function getMonthlyChartDataInTransactionChart($input)
    {
        $start_date = Carbon::now()->subYear();
        $end_date = Carbon::now();

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));
        }

        $successTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c") as day'), \DB::raw('count(*) as user_count'))
            ->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $successTran = $successTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $successTran = $successTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $successTran = $successTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m")'))
            ->pluck('user_count', 'day');

        //dd($successTran);

        $refundTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c") as day'), \DB::raw('count(*) as user_count'))
            ->where('refund', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $refundTran = $refundTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $refundTran = $refundTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $refundTran = $refundTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m")'))
            ->pluck('user_count', 'day');

        $chargebacksTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c") as day'), \DB::raw('count(*) as user_count'))
            ->where('chargebacks', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $chargebacksTran = $chargebacksTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $chargebacksTran = $chargebacksTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $chargebacksTran = $chargebacksTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m")'))
            ->pluck('user_count', 'day');

        $failTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c") as day'), \DB::raw('count(*) as user_count'))
            ->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $failTran = $failTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $failTran = $failTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $failTran = $failTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m")'))
            ->pluck('user_count', 'day');

        $flaggedTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c") as day'), \DB::raw('count(*) as user_count'))
            ->where('is_flagged', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $flaggedTran = $flaggedTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $flaggedTran = $flaggedTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $flaggedTran = $flaggedTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m")'))
            ->pluck('user_count', 'day');

        $data_array = [];
        $i = 0;

        while (strtotime($start_date) <= strtotime($end_date)) {

            $start_date = date("Y-n", strtotime($start_date));

            // date
            $data_array[$i][] = date("Y-m-1", strtotime($start_date));

            // success value
            if (isset($successTran[$start_date])) {
                $data_array[$i][] = $successTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // failed value
            if (isset($failTran[$start_date])) {
                $data_array[$i][] = $failTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // chargeback value
            if (isset($chargebacksTran[$start_date])) {
                $data_array[$i][] = $chargebacksTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // refund value
            if (isset($refundTran[$start_date])) {
                $data_array[$i][] = $refundTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // flagged value
            if (isset($flaggedTran[$start_date])) {
                $data_array[$i][] = $flaggedTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            $i++;
            $start_date = date("Y-m-d", strtotime("+1 month", strtotime($start_date)));
        }

        return $data_array;
    }

    public function getChartData($input)
    {
        $userDetails = Auth::user();
        ($userDetails->is_sub_user == 1) ? ($user_id = $userDetails->main_user_id) : ($user_id = Auth::user()->id);

        $successTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $successTran = $successTran->where('user_id', $user_id)
            ->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->count();

        // Success transaction total amount
        $successTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $successTranAmount = $successTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $successTranAmount = $successTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $successTranAmount = $successTranAmount->where('user_id', $user_id)
            ->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Declined transaction count
        $failTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $failTran = $failTran->where('user_id', $user_id)
            ->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->count();

        // Declined transaction total amount
        $failTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $failTranAmount = $failTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $failTranAmount = $failTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $failTranAmount = $failTranAmount->where('user_id', $user_id)
            ->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->count();

        // Chargebacks transaction count
        $chargebacksTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $chargebacksTran = $chargebacksTran->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $chargebacksTran = $chargebacksTran->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $chargebacksTran = $chargebacksTran->where('user_id', $user_id)
            ->where('chargebacks', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->count();

        // Chargebacks transaction total amount
        $chargebacksTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $chargebacksTranAmount = $chargebacksTranAmount->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $chargebacksTranAmount = $chargebacksTranAmount->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $chargebacksTranAmount = $chargebacksTranAmount->where('user_id', $user_id)
            ->where('chargebacks', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Refund transaction count
        $refundTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $refundTran = $refundTran->where(DB::raw('DATE(transactions.refund_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $refundTran = $refundTran->where(DB::raw('DATE(transactions.refund_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $refundTran = $refundTran->where('user_id', $user_id)
            ->where('refund', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->count();

        // Refund transaction total amount
        $refundTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $refundTranAmount = $refundTranAmount->where(DB::raw('DATE(transactions.refund_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $refundTranAmount = $refundTranAmount->where(DB::raw('DATE(transactions.refund_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $refundTranAmount = $refundTranAmount->where('user_id', $user_id)
            ->where('refund', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Flagged transaction count
        $flaggedTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $flaggedTran = $flaggedTran->where(DB::raw('DATE(transactions.flagged_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $flaggedTran = $flaggedTran->where(DB::raw('DATE(transactions.flagged_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $flaggedTran = $flaggedTran->where('user_id', $user_id)
            ->where('is_flagged', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->count();

        // Flagged transaction total amount
        $flaggedTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $flaggedTranAmount = $flaggedTranAmount->where(DB::raw('DATE(transactions.flagged_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $flaggedTranAmount = $flaggedTranAmount->where(DB::raw('DATE(transactions.flagged_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $flaggedTranAmount = $flaggedTranAmount->where('user_id', $user_id)
            ->where('is_flagged', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Pending transaction count
        $pendingTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $pendingTran = $pendingTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $pendingTran = $pendingTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $pendingTran = $pendingTran->where('user_id', $user_id)
            ->where('status', '2')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->count();

        // Pending transaction total amount
        $pendingTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $pendingTranAmount = $pendingTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $pendingTranAmount = $pendingTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }
        $pendingTranAmount = $pendingTranAmount->where('user_id', $user_id)
            ->where('status', '2')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // total transaction count and amount
        $totalTran = $successTran + $failTran + $chargebacksTran + $refundTran + $flaggedTran + $pendingTran;
        $totalTranAmount = $successTranAmount + $failTranAmount + $chargebacksTranAmount + $refundTranAmount + $flaggedTranAmount + $pendingTranAmount;

        return [
            'success' => $successTran,
            'fail' => $failTran,
            'chargebacks' => $chargebacksTran,
            'refund' => $refundTran,
            'flagged' => $flaggedTran,
            'pending' => $pendingTran,
            'total' => $totalTran,
            'successamount' => $successTranAmount,
            'failamount' => $failTranAmount,
            'chargebacksamount' => $chargebacksTranAmount,
            'refundamount' => $refundTranAmount,
            'flaggedamount' => $flaggedTranAmount,
            'pendingamount' => $pendingTranAmount,
            'totalamount' => $totalTranAmount,
        ];
    }

    public function getAllUserChargeback($user_id, $input)
    {
        $data = static::select('transactions.*', 'transactions_document_upload.files as transactions_document_upload_files')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"chargebacks"'));
            })->orderBy('transactions.id', 'DESC')
            ->where('transactions.user_id', $user_id);

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('chargebacks', '1');

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->get()->filter(function ($record) use ($input) {
                if (strpos($record->card_no, $input['card_no']) !== false) {
                    return $record;
                }
            });
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function getAllUserRefunds($user_id, $input)
    {
        $data = static::orderBy('transactions.id', 'DESC')
            ->where('transactions.user_id', $user_id);

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.refund_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('refund', '1')
            ->orderBy('transactions.refund_date', 'desc');

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->get()->filter(function ($record) use ($input) {
                if (strpos($record->card_no, $input['card_no']) !== false) {
                    return $record;
                }
            });
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function getAllUserFlagged($user_id, $input)
    {
        $data = static::select('transactions.*', 'transactions_document_upload.files as transactions_document_upload_files')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"flagged"'));
            })
            ->where('transactions.user_id', $user_id)
            ->orderBy('transactions.id', 'DESC');

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.flagged_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('is_flagged', '1')
            ->orderBy('transactions.flagged_date', 'desc');

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->get()->filter(function ($record) use ($input) {
                if (strpos($record->card_no, $input['card_no']) !== false) {
                    return $record;
                }
            });
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function getUserTransactionReport($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {
            // Check Transaction in currency
            $chekTransactionInCurrency = static::where('payment_gateway_id', '<>', '16')
                ->where('payment_gateway_id', '<>', '41')
                ->where('currency', $value)
                ->count();

            if ($chekTransactionInCurrency > 0) {
                $total_approve_transaction_amount = static::where('user_id', \Auth::user()->id)
                    ->where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('is_flagged', '0')
                    ->where('chargebacks', '0')
                    ->where('refund', '0')
                    ->where('is_retrieval', '0')
                    ->where('currency', $value)
                    ->where('status', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_approve_transaction_amount = $total_approve_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
                }
                $total_approve_transaction_amount = $total_approve_transaction_amount->sum('amount');

                $total_declined_transaction_amount = static::where('user_id', \Auth::user()->id)
                    ->where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('chargebacks', '<>', '1')
                    ->where('refund', '<>', '1')
                    ->where('currency', $value)
                    ->where('status', '0');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_declined_transaction_amount = $total_declined_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
                }
                $total_declined_transaction_amount = $total_declined_transaction_amount->sum('amount');

                $total_chargebacks_transaction_amount = static::where('user_id', \Auth::user()->id)
                    ->where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('chargebacks', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
                }
                $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->sum('amount');

                $total_refund_transaction_amount = static::where('user_id', \Auth::user()->id)
                    ->where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('refund', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_refund_transaction_amount = $total_refund_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
                }
                $total_refund_transaction_amount = $total_refund_transaction_amount->sum('amount');

                $total_flagged_amount = static::where('user_id', \Auth::user()->id)
                    ->where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('is_flagged', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_flagged_amount = $total_flagged_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
                }
                $total_flagged_amount = $total_flagged_amount->sum('amount');

                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount,
                    'total_flagged_amount' => $total_flagged_amount,
                ];
            }
        }

        return $mainData;
    }

    /*
    |===================================|
    | For A Merchant Dashboard Porpouse |
    |===================================|
    */
    public function getMerchantDashboardData($input, $user_id)
    {
        $data = static::orderBy('id', 'DESC')
            ->where('user_id', $user_id);
        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->get();

        return $data;
    }
    public function getMerchantDashboardChartData($input, $user_id)
    {
        $successTran = \DB::table('transactions');

        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }

        $successTran = $successTran->where('user_id', $user_id)
            ->where('status', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        $failTran = \DB::table('transactions');

        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }

        $failTran = $failTran->where('user_id', $user_id)
            ->where('status', '0')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        $pendingTran = \DB::table('transactions');

        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $pendingTran = $pendingTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $pendingTran = $pendingTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }

        $pendingTran = $pendingTran->where('user_id', $user_id)
            ->where('status', '2')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        $toBeConfTran = \DB::table('transactions');

        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $toBeConfTran = $toBeConfTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $toBeConfTran = $toBeConfTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }

        $toBeConfTran = $toBeConfTran->where('user_id', $user_id)
            ->where('status', '4')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        $canceledTran = \DB::table('transactions');

        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $canceledTran = $canceledTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $canceledTran = $canceledTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 23:59:59');
        }

        $canceledTran = $canceledTran->where('user_id', $user_id)
            ->where('status', '3')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        return [
            'success' => $successTran,
            'fail' => $failTran,
            'pending' => $pendingTran,
            'tobeconf' => $toBeConfTran,
            'canceled' => $canceledTran,
        ];
    }

    public function getTransactionAmountByUserReportDaily($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {


            $total_approve_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->whereDate('created_at', Carbon::today());

            $total_approve_transaction_count = $total_approve_transaction_amount->sum('TXs');
            $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('VOLs');

            $total_declined_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->whereDate('created_at', Carbon::today());

            $total_declined_transaction_count = $total_declined_transaction_amount->sum('TXd');
            $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('VOLd');

            $total_chargebacks_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->whereDate('created_at', Carbon::today());

            $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->sum('CBTX');
            $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('CBV');

            $total_refund_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->whereDate('created_at', Carbon::today());

            $total_refund_transaction_count = $total_refund_transaction_amount->sum('REFTX');
            $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('REFV');

            $total_flagged_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->whereDate('created_at', Carbon::today());

            $total_flagged_count = $total_flagged_amount->sum('FLGV');
            $total_flagged_amount1 = $total_flagged_amount->sum('FLGTX');

            if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0) {
                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                    'total_approve_transaction_count' => $total_approve_transaction_count,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                    'total_declined_transaction_count' => $total_declined_transaction_count,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                    'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                    'total_refund_transaction_count' => $total_refund_transaction_count,
                    'total_flagged_amount' => $total_flagged_amount1,
                    'total_flagged_count' => $total_flagged_count,
                ];
            }
        }

        return $mainData;
    }

    public function getTransactionAmountByUserReportWeekly($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {

            $fromDate = Carbon::now()->subDay()->startOfWeek()->toDateString();
            $tillDate = Carbon::now()->subDay()->endOfWeek()->toDateString();

            $total_approve_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_approve_transaction_count = $total_approve_transaction_amount->sum('TXs');
            $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('VOLs');

            $total_declined_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_declined_transaction_count = $total_declined_transaction_amount->sum('TXd');
            $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('VOLd');

            $total_chargebacks_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->sum('CBTX');
            $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('CBV');

            $total_refund_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_refund_transaction_count = $total_refund_transaction_amount->sum('REFTX');
            $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('REFV');

            $total_flagged_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_flagged_count = $total_flagged_amount->sum('FLGV');
            $total_flagged_amount1 = $total_flagged_amount->sum('FLGTX');

            if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0) {
                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                    'total_approve_transaction_count' => $total_approve_transaction_count,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                    'total_declined_transaction_count' => $total_declined_transaction_count,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                    'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                    'total_refund_transaction_count' => $total_refund_transaction_count,
                    'total_flagged_amount' => $total_flagged_amount1,
                    'total_flagged_count' => $total_flagged_count,
                ];
            }
        }

        return $mainData;
    }

    public function getTransactionAmountByUserReportMonthly($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {
            $fromDate = Carbon::now()->subDay()->startOfMonth()->toDateString();
            $tillDate = Carbon::now()->subDay()->endOfMonth()->toDateString();

            $total_approve_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_approve_transaction_count = $total_approve_transaction_amount->sum('TXs');
            $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('VOLs');

            $total_declined_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_declined_transaction_count = $total_declined_transaction_amount->sum('TXd');
            $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('VOLd');

            $total_chargebacks_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->sum('CBTX');
            $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('CBV');

            $total_refund_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_refund_transaction_count = $total_refund_transaction_amount->sum('REFTX');
            $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('REFV');

            $total_flagged_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_flagged_count = $total_flagged_amount->sum('FLGV');
            $total_flagged_amount1 = $total_flagged_amount->sum('FLGTX');

            if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0) {
                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                    'total_approve_transaction_count' => $total_approve_transaction_count,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                    'total_declined_transaction_count' => $total_declined_transaction_count,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                    'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                    'total_refund_transaction_count' => $total_refund_transaction_count,
                    'total_flagged_amount' => $total_flagged_amount1,
                    'total_flagged_count' => $total_flagged_count,
                ];
            }
        }

        return $mainData;
    }
    /*
    |=============================|
    | For A Admin Porpouse        |
    |=============================|
    */

    public function getAdminChartData($input)
    {
        // $input['start_date'] = "01/01/2018";
        // $input['end_date']   = "12/31/2018";

        // Success transaction count
        /*$successTran = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $successTran = $successTran->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->count();

        // Success transaction total amount
        $successTranAmount = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $successTranAmount = $successTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $successTranAmount = $successTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $successTranAmount = $successTranAmount->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Declined transaction count
        $failTran = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $failTran = $failTran->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->count();

        // Declined transaction total amount
        $failTranAmount = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $failTranAmount = $failTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $failTranAmount = $failTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $failTranAmount = $failTranAmount->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Chargebacks transaction count
        $chargebacksTran = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $chargebacksTran = $chargebacksTran->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $chargebacksTran = $chargebacksTran->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $chargebacksTran = $chargebacksTran->where('chargebacks', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->count();

        // Chargebacks transaction total amount
        $chargebacksTranAmount = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $chargebacksTranAmount = $chargebacksTranAmount->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $chargebacksTranAmount = $chargebacksTranAmount->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $chargebacksTranAmount = $chargebacksTranAmount->where('chargebacks', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Refund transaction count
        $refundTran = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $refundTran = $refundTran->where(DB::raw('DATE(transactions.refund_date)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $refundTran = $refundTran->where(DB::raw('DATE(transactions.refund_date)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $refundTran = $refundTran->where('refund', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->count();

        // Refund transaction total amount
        $refundTranAmount = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $refundTranAmount = $refundTranAmount->where(DB::raw('DATE(transactions.refund_date)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $refundTranAmount = $refundTranAmount->where(DB::raw('DATE(transactions.refund_date)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $refundTranAmount = $refundTranAmount->where('refund', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Flagged transaction count
        $flaggedTran = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $flaggedTran = $flaggedTran->where(DB::raw('DATE(transactions.flagged_date)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $flaggedTran = $flaggedTran->where(DB::raw('DATE(transactions.flagged_date)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $flaggedTran = $flaggedTran->where('is_flagged', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->count();

        // Flagged transaction total amount
        $flaggedTranAmount = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $flaggedTranAmount = $flaggedTranAmount->where(DB::raw('DATE(transactions.flagged_date)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $flaggedTranAmount = $flaggedTranAmount->where(DB::raw('DATE(transactions.flagged_date)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $flaggedTranAmount = $flaggedTranAmount->where('is_flagged', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->sum('amount');

        // Pending transaction count
        $pendingTran = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $pendingTran = $pendingTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $pendingTran = $pendingTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $pendingTran = $pendingTran->where('status', '2')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->count();

        // Pending transaction total amount
        $pendingTranAmount = \DB::table('transactions');
        if(isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $pendingTranAmount = $pendingTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d',strtotime($input['start_date'])).' 00:00:00');
            $pendingTranAmount = $pendingTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d',strtotime($input['end_date'])).' 00:00:00');
        }
        $pendingTranAmount = $pendingTranAmount->where('status', '2')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->sum('amount');
        */
        // total transaction count and amount
        /*$totalTran = $successTran + $failTran + $chargebacksTran + $refundTran + $flaggedTran + $pendingTran;
        $totalTranAmount = $successTranAmount + $failTranAmount + $chargebacksTranAmount + $refundTranAmount + $flaggedTranAmount + $pendingTranAmount;*/


        $date_condition = "";
        $user_condition = "";

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $date_condition = "and created_at between '" . $start_date . "' and '" . $end_date . "' ";
        }
        // else {

        //     $date_condition = "and created_at between date_sub(now() , interval 31 day) and now() ";
        // }

        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $user_id = $input['user_id'];
            $user_condition = "and user_id = $user_id";
        }
        $table = '';

        $query = <<<SQL
    select  sum(volume) volume, sum(tx) as tx
    from

SQL;

        $where = <<<SQL
    where 1
    $user_condition
    $date_condition
SQL;

        $table = 'tx_success';
        $select = $query . $table . $where;
        $successD = \DB::select($select)[0];
        $successD = (array) $successD;

        $table = 'tx_decline';
        $select = $query . $table . $where;
        $failD = \DB::select($select)[0];
        $failD = (array) $failD;

        $table = 'tx_chargebacks';
        $select = $query . $table . $where;
        $chargebacksD = \DB::select($select)[0];
        $chargebacksD = (array) $chargebacksD;

        $table = 'tx_refunds';
        $select = $query . $table . $where;
        $refundD = \DB::select($select)[0];
        $refundD = (array) $refundD;

        $table = 'tx_flagged';
        $select = $query . $table . $where;
        $flaggdD = \DB::select($select)[0];
        $flaggdD = (array) $flaggdD;

        $table = 'tx_pending';
        $select = $query . $table . $where;
        $pendingD = \DB::select($select)[0];
        $pendingD = (array) $pendingD;


        $successTran                = $successD['tx'];
        $failTran                   = $failD['tx'];
        $chargebacksTran            = $chargebacksD['tx'];
        $refundTran                 = $refundD['tx'];
        $flaggedTran                = $flaggdD['tx'];
        $pendingTran                = $pendingD['tx'];
        $totalTran                  = $successTran
            +  $failTran
            +  $chargebacksTran
            +  $refundTran
            +  $flaggedTran
            +  $pendingTran;

        $successTranAmount          = $successD['volume'];
        $failTranAmount             = $failD['volume'];
        $chargebacksTranAmount      = $chargebacksD['volume'];
        $refundTranAmount           = $refundD['volume'];
        $flaggedTranAmount          = $flaggdD['volume'];
        $pendingTranAmount          = $pendingD['volume'];
        $totalTranAmount            = $successTran
            +  $failTranAmount
            +  $chargebacksTranAmount
            +  $refundTranAmount
            +  $flaggedTranAmount
            +  $pendingTranAmount;



        return [
            'success' => $successTran,
            'fail' => $failTran,
            'chargebacks' => $chargebacksTran,
            'refund' => $refundTran,
            'flagged' => $flaggedTran,
            'pending' => $pendingTran,
            'total' => $totalTran,
            'successamount' => $successTranAmount,
            'failamount' => $failTranAmount,
            'chargebacksamount' => $chargebacksTranAmount,
            'refundamount' => $refundTranAmount,
            'flaggedamount' => $flaggedTranAmount,
            'pendingamount' => $pendingTranAmount,
            'totalamount' => $totalTranAmount,
        ];
    }

    public function getUserTotalAmount($user_id, $currency, $input)
    {
        // Success transaction count
        $successTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $successTran = $successTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $successTran = $successTran->where('status', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        // Success transaction total amount
        $successTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {

            $successTranAmount = $successTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $successTranAmount = $successTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $successTranAmount = $successTranAmount->where('status', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('chargebacks', '<>', '1')
            ->where('payment_gateway_id', '<>', '16')
            ->where('payment_gateway_id', '<>', '41')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->sum('amount');

        // Declined transaction count
        $failTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $failTran = $failTran->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $failTran = $failTran->where('status', '0')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        // Declined transaction total amount
        $failTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $failTranAmount = $failTranAmount->where(DB::raw('DATE(transactions.created_at)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $failTranAmount = $failTranAmount->where(DB::raw('DATE(transactions.created_at)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $failTranAmount = $failTranAmount->where('status', '0')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('chargebacks', '<>', '1')
            ->where('payment_gateway_id', '<>', '16')
            ->where('payment_gateway_id', '<>', '41')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->sum('amount');

        // Chargebacks transaction count
        $chargebacksTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $chargebacksTran = $chargebacksTran->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $chargebacksTran = $chargebacksTran->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $chargebacksTran = $chargebacksTran->where('chargebacks', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        // Chargebacks transaction total amount
        $chargebacksTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $chargebacksTranAmount = $chargebacksTranAmount->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $chargebacksTranAmount = $chargebacksTranAmount->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $chargebacksTranAmount = $chargebacksTranAmount->where('chargebacks', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('payment_gateway_id', '<>', '16')
            ->where('payment_gateway_id', '<>', '41')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->sum('amount');

        // Refund transaction count
        $refundTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $refundTran = $refundTran->where(DB::raw('DATE(transactions.refund_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $refundTran = $refundTran->where(DB::raw('DATE(transactions.refund_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $refundTran = $refundTran->where('refund', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        // Refund transaction total amount
        $refundTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $refundTranAmount = $refundTranAmount->where(DB::raw('DATE(transactions.refund_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $refundTranAmount = $refundTranAmount->where(DB::raw('DATE(transactions.refund_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $refundTranAmount = $refundTranAmount->where('refund', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('payment_gateway_id', '<>', '16')
            ->where('payment_gateway_id', '<>', '41')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->sum('amount');

        // Flagged transaction count
        $flaggedTran = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $flaggedTran = $flaggedTran->where(DB::raw('DATE(transactions.flagged_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $flaggedTran = $flaggedTran->where(DB::raw('DATE(transactions.flagged_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $flaggedTran = $flaggedTran->where('is_flagged', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->count();

        // Flagged transaction total amount
        $flaggedTranAmount = \DB::table('transactions');
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $flaggedTranAmount = $flaggedTranAmount->where(DB::raw('DATE(transactions.flagged_date)'), '>=', date('Y-m-d', strtotime($input['start_date'])) . ' 00:00:00');
            $flaggedTranAmount = $flaggedTranAmount->where(DB::raw('DATE(transactions.flagged_date)'), '<=', date('Y-m-d', strtotime($input['end_date'])) . ' 00:00:00');
        }
        $flaggedTranAmount = $flaggedTranAmount->where('is_flagged', '1')
            ->where('user_id', $user_id)
            ->where('currency', $currency)
            ->where('payment_gateway_id', '<>', '16')
            ->where('payment_gateway_id', '<>', '41')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->sum('amount');

        return [
            'success' => $successTran,
            'fail' => $failTran,
            'chargebacks' => $chargebacksTran,
            'refund' => $refundTran,
            'flagged' => $flaggedTran,
            'successamount' => $successTranAmount,
            'failamount' => $failTranAmount,
            'chargebacksamount' => $chargebacksTranAmount,
            'refundamount' => $refundTranAmount,
            'flaggedamount' => $flaggedTranAmount,
        ];
    }

    public function getTransactionAmountReport($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {
            // Check Transaction in currency
            $chekTransactionInCurrency = static::where('payment_gateway_id', '<>', '16');
            if (isset($input['company_name']) && $input['company_name'] != '') {
                $chekTransactionInCurrency = $chekTransactionInCurrency->where('user_id', $input['company_name']);
            }
            if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                $chekTransactionInCurrency = $chekTransactionInCurrency->where('payment_gateway_id', $input['payment_gateway_id']);
            }
            $chekTransactionInCurrency = $chekTransactionInCurrency->where('currency', $value)
                ->count();

            if ($chekTransactionInCurrency > 0) {
                $total_approve_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_retrieval', '0')
                    ->where('currency', $value)
                    ->where('status', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_approve_transaction_amount = $total_approve_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 00:00:00');
                }
                if (isset($input['company_name']) && $input['company_name'] != '') {
                    $total_approve_transaction_amount = $total_approve_transaction_amount->where('user_id', $input['company_name']);
                }
                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_approve_transaction_amount = $total_approve_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }
                $total_approve_transaction_amount = $total_approve_transaction_amount->sum('amount');

                $total_declined_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('chargebacks', '<>', '1')
                    ->where('refund', '<>', '1')
                    ->where('currency', $value)
                    ->where('status', '0');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_declined_transaction_amount = $total_declined_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 00:00:00');
                }
                if (isset($input['company_name']) && $input['company_name'] != '') {
                    $total_declined_transaction_amount = $total_declined_transaction_amount->where('user_id', $input['company_name']);
                }
                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_declined_transaction_amount = $total_declined_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }
                $total_declined_transaction_amount = $total_declined_transaction_amount->sum('amount');

                $total_chargebacks_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('chargebacks', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 00:00:00');
                }
                if (isset($input['company_name']) && $input['company_name'] != '') {
                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->where('user_id', $input['company_name']);
                }
                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }
                $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->sum('amount');

                $total_refund_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('refund', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_refund_transaction_amount = $total_refund_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 00:00:00');
                }
                if (isset($input['company_name']) && $input['company_name'] != '') {
                    $total_refund_transaction_amount = $total_refund_transaction_amount->where('user_id', $input['company_name']);
                }
                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_refund_transaction_amount = $total_refund_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }
                $total_refund_transaction_amount = $total_refund_transaction_amount->sum('amount');

                $total_flagged_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('is_flagged', '1');
                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $start_date = date('Y-m-d', strtotime($input['start_date']));
                    $end_date = date('Y-m-d', strtotime($input['end_date']));

                    $total_flagged_amount = $total_flagged_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                        ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 00:00:00');
                }
                if (isset($input['company_name']) && $input['company_name'] != '') {
                    $total_flagged_amount = $total_flagged_amount->where('user_id', $input['company_name']);
                }
                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_flagged_amount = $total_flagged_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }
                $total_flagged_amount = $total_flagged_amount->sum('amount');

                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount,
                    'total_flagged_amount' => $total_flagged_amount,
                ];
            }
        }

        return $mainData;
    }

    public function getTransactionAmountReportDaily($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {


            $total_approve_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->whereDate('created_at', Carbon::today());

            $total_approve_transaction_count = $total_approve_transaction_amount->sum('TXs');
            $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('VOLs');

            $total_declined_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->whereDate('created_at', Carbon::today());

            $total_declined_transaction_count = $total_declined_transaction_amount->sum('TXd');
            $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('VOLd');

            $total_chargebacks_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->whereDate('created_at', Carbon::today());

            $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->sum('CBTX');
            $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('CBV');

            $total_refund_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->whereDate('created_at', Carbon::today());

            $total_refund_transaction_count = $total_refund_transaction_amount->sum('REFTX');
            $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('REFV');

            $total_flagged_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->whereDate('created_at', Carbon::today());

            $total_flagged_count = $total_flagged_amount->sum('FLGV');
            $total_flagged_amount1 = $total_flagged_amount->sum('FLGTX');

            if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0) {
                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                    'total_approve_transaction_count' => $total_approve_transaction_count,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                    'total_declined_transaction_count' => $total_declined_transaction_count,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                    'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                    'total_refund_transaction_count' => $total_refund_transaction_count,
                    'total_flagged_amount' => $total_flagged_amount1,
                    'total_flagged_count' => $total_flagged_count,
                ];
            }
        }

        return $mainData;
    }

    public function getTransactionAmountReportWeekly($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {

            $fromDate = Carbon::now()->subDay()->startOfWeek()->toDateString();
            $tillDate = Carbon::now()->subDay()->endOfWeek()->toDateString();

            $total_approve_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_approve_transaction_count = $total_approve_transaction_amount->sum('TXs');
            $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('VOLs');

            $total_declined_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_declined_transaction_count = $total_declined_transaction_amount->sum('TXd');
            $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('VOLd');

            $total_chargebacks_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->sum('CBTX');
            $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('CBV');

            $total_refund_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_refund_transaction_count = $total_refund_transaction_amount->sum('REFTX');
            $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('REFV');

            $total_flagged_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_flagged_count = $total_flagged_amount->sum('FLGV');
            $total_flagged_amount1 = $total_flagged_amount->sum('FLGTX');

            if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0) {
                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                    'total_approve_transaction_count' => $total_approve_transaction_count,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                    'total_declined_transaction_count' => $total_declined_transaction_count,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                    'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                    'total_refund_transaction_count' => $total_refund_transaction_count,
                    'total_flagged_amount' => $total_flagged_amount1,
                    'total_flagged_count' => $total_flagged_count,
                ];
            }
        }

        return $mainData;
    }

    public function getTransactionAmountReportMonthly($input)
    {
        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {
            $fromDate = Carbon::now()->subDay()->startOfMonth()->toDateString();
            $tillDate = Carbon::now()->subDay()->endOfMonth()->toDateString();

            $total_approve_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_approve_transaction_count = $total_approve_transaction_amount->sum('TXs');
            $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('VOLs');

            $total_declined_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_declined_transaction_count = $total_declined_transaction_amount->sum('TXd');
            $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('VOLd');

            $total_chargebacks_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->sum('CBTX');
            $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('CBV');

            $total_refund_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_refund_transaction_count = $total_refund_transaction_amount->sum('REFTX');
            $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('REFV');

            $total_flagged_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where(\DB::raw('DATE(created_at)'), '>=', $fromDate . ' 00:00:00')
                ->where(\DB::raw('DATE(created_at)'), '<=', $tillDate . ' 23:59:59');

            $total_flagged_count = $total_flagged_amount->sum('FLGV');
            $total_flagged_amount1 = $total_flagged_amount->sum('FLGTX');

            if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0) {
                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                    'total_approve_transaction_count' => $total_approve_transaction_count,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                    'total_declined_transaction_count' => $total_declined_transaction_count,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                    'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                    'total_refund_transaction_count' => $total_refund_transaction_count,
                    'total_flagged_amount' => $total_flagged_amount1,
                    'total_flagged_count' => $total_flagged_count,
                ];
            }
        }

        return $mainData;
    }
    public function getTransactionSummaryReport($input)
    {
        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $start_date = $start_date . " 00:00:00";
            $end_date = $end_date . " 23:59:59";
        }

        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {

            $total_approve_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id);

            if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                $total_approve_transaction_amount = $total_approve_transaction_amount
                    ->where(\DB::raw('DATE(created_at)'), '>=', $start_date . ' 00:00:00')
                    ->where(\DB::raw('DATE(created_at)'), '<=', $end_date . ' 23:59:59');
            }

            $total_approve_transaction_count = $total_approve_transaction_amount->sum('TXs');
            $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('VOLs');

            $total_declined_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id);

            if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                $total_declined_transaction_amount = $total_declined_transaction_amount
                    ->where(\DB::raw('DATE(created_at)'), '>=', $start_date . ' 00:00:00')
                    ->where(\DB::raw('DATE(created_at)'), '<=', $end_date . ' 23:59:59');
            }

            $total_declined_transaction_count = $total_declined_transaction_amount->sum('TXd');
            $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('VOLd');

            $total_chargebacks_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id);

            if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount
                    ->where(\DB::raw('DATE(created_at)'), '>=', $start_date . ' 00:00:00')
                    ->where(\DB::raw('DATE(created_at)'), '<=', $end_date . ' 23:59:59');
            }

            $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->sum('CBTX');
            $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('CBV');

            $total_refund_transaction_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id);

            if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                $total_refund_transaction_amount = $total_refund_transaction_amount
                    ->where(\DB::raw('DATE(created_at)'), '>=', $start_date . ' 00:00:00')
                    ->where(\DB::raw('DATE(created_at)'), '<=', $end_date . ' 23:59:59');
            }

            $total_refund_transaction_count = $total_refund_transaction_amount->sum('REFTX');
            $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('REFV');

            $total_flagged_amount = \DB::table('tx_payout')
                ->where('currency', $value)
                ->where('user_id', Auth::user()->id);

            if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                $total_flagged_amount = $total_flagged_amount
                    ->where(\DB::raw('DATE(created_at)'), '>=', $start_date . ' 00:00:00')
                    ->where(\DB::raw('DATE(created_at)'), '<=', $end_date . ' 23:59:59');
            }

            $total_flagged_count = $total_flagged_amount->sum('FLGTX');
            $total_flagged_amount1 = $total_flagged_amount->sum('FLGV');

            if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0) {
                $mainData[$value] = [
                    'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                    'total_approve_transaction_count' => $total_approve_transaction_count,
                    'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                    'total_declined_transaction_count' => $total_declined_transaction_count,
                    'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                    'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                    'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                    'total_refund_transaction_count' => $total_refund_transaction_count,
                    'total_flagged_amount' => $total_flagged_amount1,
                    'total_flagged_count' => $total_flagged_count,
                ];
            }
        }

        return $mainData;
    }

    public function getAllMerchantTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }

        $data = static::select("transactions.*", "applications.business_name as userName", "middetails.bank_name")
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereNotIn('payment_gateway_id', $payment_gateway_id);
        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }

        // ->where('transactions.payment_gateway_id', '!=', '1'
        if (isset($input['country']) && $input['country'] != '') {
            $data = $data->where('transactions.country', $input['country']);
        }
        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no', 'like', '%' . $input['card_no'] . '%');
        }
        if (isset($input['amount']) && $input['amount'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['amount']);
        }
        if (isset($input['greater_then']) && $input['greater_then'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['greater_then']);
        }
        if (isset($input['less_then']) && $input['less_then'] != '') {
            $data = $data->where('transactions.amount', '<=', $input['less_then']);
        }
        if (isset($input['user_id']) && $input['user_id'] != '') {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        if (isset($input['session_id']) && $input['session_id'] != '') {
            $data = $data->where('transactions.session_id', 'like', '%' . $input['session_id'] . '%');
        }
        if (isset($input['gateway_id']) && $input['gateway_id'] != '') {
            $data = $data->where('transactions.gateway_id', $input['gateway_id']);
        }
        if (isset($input['is_request_from_vt']) && $input['is_request_from_vt'] != '') {
            if ($input['is_request_from_vt'] == 'iFrame') {
                $data = $data->where('transactions.is_request_from_vt', $input['is_request_from_vt']);
            }

            if ($input['is_request_from_vt'] == 'Pay Button') {
                $data = $data->where('transactions.is_request_from_vt', $input['is_request_from_vt']);
            }

            if ($input['is_request_from_vt'] == 'WEBHOOK') {
                $data = $data->where('transactions.is_request_from_vt', $input['is_request_from_vt']);
            }

            if ($input['is_request_from_vt'] == 'API') {
                $data = $data->where(function ($query) use ($input) {
                    $query->where('transactions.is_request_from_vt', $input['is_request_from_vt'])
                        ->orWhere('transactions.is_request_from_vt', '0');
                });
            }
        }
        if (isset($input['reason']) && $input['reason'] != '') {
            $data = $data->where('transactions.reason', 'like', '%' . $input['reason'] . '%');
        }
        $this->filterTransactionData($input, $data);
        $data = $data->orderBy('id', 'desc')->paginate($noList);

        return $data;
    }

    public function getAllMerchantCryptoTransactionData($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transactions", 'slave_connection');
        }

        $data = static::select("transactions.*", "applications.business_name as userName", "middetails.bank_name")
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->whereNotIn('payment_gateway_id', $payment_gateway_id)
            // ->where('transactions.payment_gateway_id', '!=', '1')
            ->where('transactions.is_transaction_type', 'CRYPTO');
        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }
        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }
        if (isset($input['amount']) && $input['amount'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['amount']);
        }
        if (isset($input['greater_then']) && $input['greater_then'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['greater_then']);
        }
        if (isset($input['less_then']) && $input['less_then'] != '') {
            $data = $data->where('transactions.amount', '<=', $input['less_then']);
        }
        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }
        if (isset($input['user_id']) && $input['user_id'] != '') {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        if (isset($input['customer_order_id']) && $input['customer_order_id'] != '') {
            $data = $data->where('transactions.customer_order_id', 'like', '%' . $input['customer_order_id'] . '%');
        }
        if (isset($input['session_id']) && $input['session_id'] != '') {
            $data = $data->where('transactions.session_id', 'like', '%' . $input['session_id'] . '%');
        }
        if (isset($input['gateway_id']) && $input['gateway_id'] != '') {
            $data = $data->where('transactions.gateway_id', $input['gateway_id']);
        }
        if (isset($input['country']) && $input['country'] != '') {
            $data = $data->where('transactions.country', $input['country']);
        }
        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no', 'like', '%' . $input['card_no'] . '%');
        }
        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email', 'like', '%' . $input['email'] . '%');
        }
        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name', 'like', '%' . $input['first_name'] . '%');
        }
        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name', 'like', '%' . $input['last_name'] . '%');
        }
        if (isset($input['currency']) && $input['currency'] != '') {
            $data = $data->where('transactions.currency', $input['currency']);
        }
        if (isset($input['reason']) && $input['reason'] != '') {
            $data = $data->where('transactions.reason', 'like', '%' . $input['reason'] . '%');
        }
        if (isset($input['is_request_from_vt']) && $input['is_request_from_vt'] != '') {
            if ($input['is_request_from_vt'] == 'iFrame') {
                $data = $data->where('transactions.is_request_from_vt', $input['is_request_from_vt']);
            }

            if ($input['is_request_from_vt'] == 'Pay Button') {
                $data = $data->where('transactions.is_request_from_vt', $input['is_request_from_vt']);
            }

            if ($input['is_request_from_vt'] == 'WEBHOOK') {
                $data = $data->where('transactions.is_request_from_vt', $input['is_request_from_vt']);
            }

            if ($input['is_request_from_vt'] == 'API') {
                $data = $data->where(function ($query) use ($input) {
                    $query->where('transactions.is_request_from_vt', $input['is_request_from_vt'])
                        ->orWhere('transactions.is_request_from_vt', '0');
                });
            }
        }
        if ((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        $data = $data->orderBy('id', 'desc')->paginate($noList);
        return $data;
    }

    public function getSubTransactionData($input, $noList, $id)
    {
        $data = static::select('applications.business_name', 'transactions.*', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->where('transactions.is_reccuring_transaction_id', $id)
            ->orderBy('id', 'DESC');

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if (isset($input['amount']) && $input['amount'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['amount']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }

        if (isset($input['global_search']) && $input['global_search'] != '') {
            $data = $data->where(function ($query) use ($input) {
                $query->orWhere('transactions.id', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.order_id', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.descriptor', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('applications.business_name', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.phone_no', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.email', 'like', '%' . $input['global_search'] . '%');
            });
        }

        if (isset($input['type']) && $input['type'] == 'xlsx') {
            $data = $data->get();
        } else {
            $data = $data->paginate($noList);
        }
        return $data;
    }

    public function getAllMerchantRecurringTransactionData($input, $noList)
    {
        $data = static::select('applications.business_name', 'transactions.*', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->orderBy('id', 'DESC');

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if (isset($input['amount']) && $input['amount'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['amount']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }

        // if(isset($input['card_no']) && $input['card_no'] != '') {
        //     $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        // }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }

        if (isset($input['global_search']) && $input['global_search'] != '') {
            $data = $data->where(function ($query) use ($input) {
                $query->orWhere('transactions.id', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.order_id', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.descriptor', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('applications.business_name', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.phone_no', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.email', 'like', '%' . $input['global_search'] . '%');
            });
        }

        $data = $data->where('transactions.is_recurring', '1');

        if (isset($input['type']) && $input['type'] == 'xlsx') {
            $data = $data->get();
        } else {
            if (isset($input['card_no']) && $input['card_no'] != '') {
                $filteredTransactions = $data->get()->filter(function ($record) use ($input) {
                    if (strpos($record->card_no, $input['card_no']) !== false) {
                        return $record;
                    }
                });
                $perPage = $noList;
                $currentPage = (!empty($input['page'])  ? $input['page'] : 1);
                $pagedData = $filteredTransactions->slice(($currentPage - 1) * $perPage, $perPage)->all();
                $data = new \Illuminate\Pagination\LengthAwarePaginator($pagedData, count($filteredTransactions), $perPage, $currentPage, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]);
            } else {
                $data = $data->paginate($noList);
            }
        }
        return $data;
    }

    public function changeChargebacksStatus($id, $status)
    {
        return static::find($id)->update(['chargebacks' => $status]);
    }

    public function changeRefundStatus($id, $status)
    {
        return static::find($id)->update(['refund' => $status]);
    }

    public function getAllFlaggedTransactionData($input)
    {
        $data = static::select('applications.business_name', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"flagged"'));
            });

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.flagged_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('transactions.is_flagged', '1')
            ->orderBy('transactions.flagged_date', 'desc');

        $data = $data->paginate($input['paginate']);

        return $data;
    }

    public function getAllChargebackTransactionData($input)
    {
        $data = static::select('applications.business_name', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"chargebacks"'));
            });

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', $end_date . ' 23:59:59');
        }

        $data = $data->where('transactions.chargebacks', '1')
            ->orderBy('transactions.chargebacks_date', 'desc');

        return $data->paginate($input['paginate']);
    }

    public function getAllRefundTransactionsData($input)
    {
        $data = static::select('applications.business_name', 'transactions.*')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->orderBy('refund_date', 'desc');
        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }

        // if(isset($input['card_no']) && $input['card_no'] != '') {
        //     $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        // }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.refund_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('transactions.refund', '1')
            ->orderBy('transactions.refund_date', 'desc');

        // if(isset($input['card_no']) && $input['card_no'] != '') {
        //     $data = $data->get()->filter(function($record) use($input) {
        //         if(strpos($record->card_no, $input['card_no']) !== false ) {
        //             return $record;
        //         }
        //     });
        // } else {
        // }
        $data = $data->paginate($input['paginate']);

        return $data;
    }

    public function getAllMerchantTestTransactionData($input, $noList)
    {
        $data = static::select('applications.business_name', 'transactions.*')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->orderBy('id', 'DESC');

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.refund_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where(function ($query) {
            $query->orWhere('transactions.payment_gateway_id', '16')
                ->orWhere('transactions.payment_gateway_id', '41');
        });

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $filteredTransactions = $data->get()->filter(function ($record) use ($input) {
                if (strpos($record->card_no, $input['card_no']) !== false) {
                    return $record;
                }
            });
            $perPage = 10;
            $currentPage = (!empty($input['page'])  ? $input['page'] : 1);
            $pagedData = $filteredTransactions->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $data = new \Illuminate\Pagination\LengthAwarePaginator($pagedData, count($filteredTransactions), $perPage, $currentPage, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]);
        } else {
            // $data = $data->paginate(10);
            if (isset($input['type']) && $input['type'] == 'xlsx') {
                if (isset($input['search_type']) && $input['search_type'] == 'resubmit') {
                    if (isset($input['ids']) && $input['ids'] != '') {
                        $ids = explode(',', $input['ids']);
                        $data = $data->whereIn('transactions.id', $ids);
                    }
                    // $data = $data->where('batch_transaction_counter', 0)
                    $data = $data->groupBy('transactions.email');
                }
                $data = $data->get();
            } else {
                $data = $data->paginate($noList);
            }
        }

        return $data;
    }

    public function getAllBatchTransactionData($input)
    {
        // dd($input);
        $data = static::select('transactions.*', 'middetails.bank_name')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->where('transactions.is_batch_transaction', '1')
            ->orderBy('id', 'DESC');

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }
        if (isset($input['amount']) && $input['amount'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['amount']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->get();
        return $data;
    }

    public function getAllUserRetrieval($user_id, $input)
    {
        $data = static::select('transactions.*', 'transactions_document_upload.files as transactions_document_upload_files')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"retrieval"'));
            })
            ->where('transactions.user_id', $user_id)
            ->orderBy('transactions.id', 'DESC');

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.retrieval_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('is_retrieval', '1');


        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->get()->filter(function ($record) use ($input) {
                if (strpos($record->card_no, $input['card_no']) !== false) {
                    return $record;
                }
            });
        } else {
            $data = $data->get();
        }

        return $data;
    }

    public function getAllRetrievalTransactionData($input)
    {
        $data = static::select('applications.business_name', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"retrieval"'));
            });

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }

        if (isset($input['card_type']) && $input['card_type'] != '') {
            $data = $data->where('transactions.card_type', $input['card_type']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['card_no']) && $input['card_no'] != '') {
            $data = $data->where('transactions.card_no',  'like', '%' . $input['card_no'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.retrieval_date)'), '<=', $end_date . ' 23:59:59');
        }
        $data = $data->where('transactions.is_retrieval', '1')
            ->orderBy('transactions.retrieval_date', 'desc');

        $data = $data->paginate($input['paginate']);

        return $data;
    }

    public function latest10Transactions()
    {
        $data = static::select('applications.business_name', 'transactions.*', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->where('transactions.is_batch_transaction', '0')
            ->where('transactions.payment_gateway_id', '!=', '16')
            ->where('transactions.payment_gateway_id', '!=', '41')
            ->take(10)
            ->orderBy('id', 'DESC')->get();
        return $data;
    }

    public function getLatestRefundTransactionsAdminDash()
    {
        return static::where('refund', '1')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getLatestChargebackTransactionsAdminDash()
    {
        return static::where('chargebacks', '1')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getPayoutSummaryReporttt($input)
    {

        $query = '';

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $query = " and transactions.created_at >= '" . $start_date . " 00:00:00'" . " and " . "transactions.created_at <= '" . $end_date . " 23:59:59'";
        }

        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $query = $query . " and transactions.payment_gateway_id = '" . $input['payment_gateway_id'] . "'";
        }

        return static::select(
            'users.name as usersName',
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction <> '2' and transactions.status = '1'" . $query . ") THEN transactions.amount ELSE 0 END) as success_amount"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction <> '2' and transactions.status = '1'" . $query . ") THEN 1 ELSE 0 END) as success_count"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction <> '2' and transactions.is_batch_transaction = '0' and transactions.chargebacks = '1' and transactions.refund = '1' and transactions.status = '0'" . $query . ") THEN transactions.amount ELSE 0 END) as declined_amount"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction <> '2' and transactions.is_batch_transaction = '0' and transactions.chargebacks = '1' and transactions.refund = '1' and transactions.status = '0'" . $query . ") THEN 1 ELSE 0 END) as declined_count"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction = '2' and transactions.is_batch_transaction = '0' and transactions.chargebacks = '1'" . $query . ") THEN transactions.amount ELSE 0 END) as chargebacks_amount"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction = '2' and transactions.is_batch_transaction = '0' and transactions.chargebacks = '1'" . $query . ") THEN 1 ELSE 0 END) as chargebacks_count"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction = '2' and transactions.is_batch_transaction = '0' and transactions.refund = '1'" . $query . ") THEN transactions.amount ELSE 0 END) as refund_amount"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction = '2' and transactions.is_batch_transaction = '0' and transactions.refund = '1'" . $query . ") THEN 1 ELSE 0 END) as refund_count"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction = '2' and transactions.is_batch_transaction = '0' and transactions.is_flagged = '1'" . $query . ") THEN transactions.amount ELSE 0 END) as flagged_amount"),
            DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction = '2' and transactions.is_batch_transaction = '0' and transactions.is_flagged = '1'" . $query . ") THEN 1 ELSE 0 END) as flagged_count")
        )
            ->join('users', 'users.id', 'transactions.user_id')
            ->orderBy('transactions.created_at', 'DESC')
            ->groupBy('transactions.user_id')
            ->paginate(10);
    }

    public function getPayoutSummaryReport($input)
    {
        $data = static::select('transactions.*', 'users.name as usersName', 'applications.business_name as company_name')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id');
        if (
            (isset($input['start_date']) && $input['start_date'] != '') &&
            (isset($input['end_date']) && $input['end_date'] != '')
        ) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $start_date = $start_date . " 00:00:00";
            $end_date = $end_date . " 23:59:59";

            $data = $data->whereBetween('transactions.created_at', [$start_date, $end_date]);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }
        $data = $data->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->orderBy('transactions.created_at', 'DESC')
            ->groupBy('transactions.user_id')
            ->paginate(30);

        return $data;
    }

    public function getPayoutSummaryReportLastSevenDays($input)
    {
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] != '' && $input['end_date'] != '') {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $start_date = $start_date . " 00:00:00";
            $end_date = $end_date . " 23:59:59";
        } else {
            $start_date = Carbon::now()->subDays(7);
            $end_date = Carbon::now();
        }
        $data = static::select(
            'transactions.amount',
            'transactions.currency',
            'transactions.user_id',
            'users.name as usersName',
            \DB::raw("SUM(CASE WHEN (transactions.payment_gateway_id <> '41' and transactions.payment_gateway_id <> '16' and transactions.resubmit_transaction <> '2' and transactions.status = '1') THEN transactions.amount ELSE 0 END) as success_amount"),
            'applications.business_name as company_name'
        )
            ->join('users', function ($join) use ($input) {
                $join->on('users.id', '=', 'transactions.user_id')
                    ->where('users.main_user_id', '0')
                    ->where('users.is_active', '1');
            })
            ->join('applications', function ($join) use ($input) {
                $join->on('applications.user_id', '=', 'transactions.user_id');
            });
        $data = $data->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->whereBetween('transactions.created_at', [$start_date, $end_date])
            ->orderBy('success_amount', 'DESC')
            ->groupBy('transactions.user_id')
            ->get();

        dd($data);
    }

    public function getReportByMID($input)
    {
        $data = static::select('transactions.*', 'users.name as usersName', 'applications.business_name as company_name')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id');
        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $data = $data->where('payment_gateway_id', $input['payment_gateway_id']);
        }
        $data = $data->orderBy('transactions.created_at', 'DESC')
            ->groupBy('transactions.user_id')
            ->paginate(10);

        return $data;
    }

    public function getReportByTransactionType($input)
    {
        $data = static::select('transactions.*', 'users.name as usersName', 'applications.business_name as company_name')
            ->join('users', 'users.id', 'transactions.user_id')
            ->join('applications', 'applications.user_id', 'transactions.user_id');
        if (isset($input['start_date']) && $input['start_date'] != '') {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        }
        if (isset($input['end_date']) && $input['end_date'] != '') {
            $end_date = date('Y-m-d', strtotime($input['end_date']));
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('company_name',  'like', '%' . $input['company_name'] . '%');
        }
        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }
        $data = $data->where('payment_gateway_id', '<>', '16')
            ->where('payment_gateway_id', '<>', '41')
            ->where('resubmit_transaction', '<>', '2')
            ->orderBy('transactions.created_at', 'DESC')
            ->groupBy('transactions.user_id')
            ->paginate(10);
        return $data;
    }

    public function getCountryTotals($input)
    {

        $date_condition = "";
        $user_condition = "";

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $date_condition = "and created_at between '" . $start_date . "' and '" . $end_date . "' ";
        }
        // else {

        //     $date_condition = "and created_at between date_sub(now() , interval 31 day) and now() ";
        // }

        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $user_id = $input['user_id'];
            $user_condition = "and user_id = $user_id";
        }
        $table = '';

        $query = <<<SQL
    select  if (currency='','N/A',currency) currency, sum(volume) volume, sum(tx) as tx
    from

SQL;

        $where = <<<SQL
    where 1
    $user_condition
    $date_condition
    group by 1
SQL;


        $table = 'tx_success';
        $select = $query . $table . $where;
        $successD = \DB::select($select);
        $successD = (array) $successD;

        $table = 'tx_decline';
        $select = $query . $table . $where;
        $failD = \DB::select($select);
        $failD = (array) $failD;

        $table = 'tx_chargebacks';
        $select = $query . $table . $where;
        $chargebacksD = \DB::select($select);
        $chargebacksD = (array) $chargebacksD;

        $table = 'tx_refunds';
        $select = $query . $table . $where;
        $refundD = \DB::select($select);
        $refundD = (array) $refundD;

        $table = 'tx_flagged';
        $select = $query . $table . $where;
        $flaggdD = \DB::select($select);
        $flaggdD = (array) $flaggdD;

        $table = 'tx_pending';
        $select = $query . $table .  $where;
        $pendingD = \DB::select($select);
        $pendingD = (array) $pendingD;

        return [
            'success' => $successD,
            'fail' => $failD,
            'chargebacks' => $chargebacksD,
            'refund' => $refundD,
            'flagged' => $flaggdD,
            'pending' => $pendingD
        ];
    }

    // ================================================
    /*  method : getActiveMerchants
    * @ param  :
    * @ Description :
    */ // ==============================================
    public function getActiveMerchants()
    {
        $start_date = Carbon::now()->subDays(7);
        $end_date = Carbon::now();

        // return DB::table('transactions')
        //         ->select('user_id', DB::raw('sum(amount) as total'))
        //         ->groupBy('user_id')
        //         ->whereNull('transactions.deleted_at')
        //         ->where('status', '1')
        //         ->where('chargebacks', '<>', '1')
        //         ->where('refund', '<>', '1')
        //         ->where('is_flagged', '<>', '1')
        //         ->where('is_retrieval', '<>', '1')
        //         ->whereBetween('created_at', [$start_date, $end_date])
        //         ->whereNotIn('payment_gateway_id', ['16', '41'])
        //         ->get()
        //         ->where('total', '>=', '5000')
        //         ->count();


        $date_condition = "";
        $user_condition = "";


        // $start_date = date('Y-m-d', strtotime($input['start_date']));
        // $end_date = date('Y-m-d', strtotime($input['end_date']));

        $date_condition = "and created_at between '" . $start_date . "' and '" . $end_date . "' ";

        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $user_id = $input['user_id'];
            $user_condition = "and user_id = $user_id";
        }
        $table = '';

        $query = <<<SQL
    select  sum(volume) total_volume, sum(tx) as tx
    from

SQL;

        $where = <<<SQL
    where 1
    $user_condition
    $date_condition
    group by user_id
    having total_volume >= 5000
SQL;

        $table = 'tx_success';
        $select = $query . $table . $where;

        //dd($select);
        $successD = \DB::select($select);
        $successD = (array) $successD;

        //dd($successD);
        return sizeof($successD);
    }

    public function getActiveMerchantsArray()
    {
        $start_date = Carbon::now()->subDays(7);
        $end_date = Carbon::now();

        return DB::table('transactions')
            ->select('user_id', DB::raw('sum(amount) as total'))
            ->groupBy('user_id')
            ->whereNull('deleted_at')
            ->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('is_retrieval', '<>', '1')
            ->whereBetween('created_at', [$start_date, $end_date])
            ->whereNotIn('payment_gateway_id', ['16', '41'])
            ->get()
            ->where('total', '>=', '5000')
            ->pluck('user_id', 'user_id');
    }

    public function getPayoutSummaryReportByUser($userId, $input)
    {
        $start_date = $end_date = null;
        if (isset($input['start_date']) && $input['start_date'] != '') {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
        }
        if (isset($input['end_date']) && $input['end_date'] != '') {
            $end_date = date('Y-m-d', strtotime($input['end_date']));
        }

        $currencyArray = ['USD', 'HKD', 'GBP', 'JPY', 'EUR', 'AUD', 'CAD', 'SGD', 'NZD', 'TWD', 'KRW', 'DKK', 'TRL', 'MYR', 'THB', 'INR', 'PHP', 'CHF', 'SEK', 'ILS', 'ZAR', 'RUB', 'NOK', 'AED', 'CNY'];

        $mainData = [];
        foreach ($currencyArray as $key => $value) {
            $chekTransactionInCurrency = static::where('payment_gateway_id', '<>', '16');
            $chekTransactionInCurrency = $chekTransactionInCurrency->where('currency', $value)
                ->count();

            if ($chekTransactionInCurrency > 0) {

                $total_approve_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_retrieval', '0')
                    ->where('currency', $value)
                    ->where('status', '1')
                    ->where('user_id', $userId);

                if (!empty($start_date)) {
                    $total_approve_transaction_amount = $total_approve_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
                }

                if (!empty($end_date)) {
                    $total_approve_transaction_amount = $total_approve_transaction_amount->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
                }

                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_approve_transaction_amount = $total_approve_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }
                $total_approve_transaction_count = $total_approve_transaction_amount->get()->count();
                $total_approve_transaction_amount1 = $total_approve_transaction_amount->sum('amount');

                $total_declined_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('chargebacks', '<>', '1')
                    ->where('refund', '<>', '1')
                    ->where('currency', $value)
                    ->where('status', '0')
                    ->where('user_id', $userId);

                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $total_declined_transaction_amount = $total_declined_transaction_amount->whereBetween(DB::raw('date(created_at)'), [$start_date, $end_date]);
                }

                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_declined_transaction_amount = $total_declined_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }

                $total_declined_transaction_count = $total_declined_transaction_amount->get()->count();
                $total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('amount');

                $total_chargebacks_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('chargebacks', '1')
                    ->where('user_id', $userId);

                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->whereBetween(DB::raw('date(chargebacks_date)'), [$start_date, $end_date]);
                }

                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }


                $total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->get()->count();
                $total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('amount');

                $total_refund_transaction_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('refund', '1')
                    ->where('user_id', $userId);

                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $total_refund_transaction_amount = $total_refund_transaction_amount->whereBetween(DB::raw('date(refund_date)'), [$start_date, $end_date]);
                }

                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_refund_transaction_amount = $total_refund_transaction_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }

                $total_refund_transaction_count = $total_refund_transaction_amount->get()->count();
                $total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('amount');

                $total_flagged_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('is_flagged', '1')
                    ->where('user_id', $userId);

                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $total_flagged_amount = $total_flagged_amount->whereBetween(DB::raw('date(flagged_date)'), [$start_date, $end_date]);
                }

                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_flagged_amount = $total_flagged_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }

                $total_flagged_count = $total_flagged_amount->get()->count();
                $total_flagged_amount1 = $total_flagged_amount->sum('amount');

                $total_retrieval_amount = static::where('payment_gateway_id', '<>', '16')
                    ->where('payment_gateway_id', '<>', '41')
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $value)
                    ->where('is_retrieval', '1')
                    ->where('user_id', $userId);

                if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
                    $total_retrieval_amount = $total_retrieval_amount->whereBetween(DB::raw('date(retrieval_date)'), [$start_date, $end_date]);
                }

                if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                    $total_retrieval_amount = $total_retrieval_amount->where('payment_gateway_id', $input['payment_gateway_id']);
                }

                $total_retrieval_count = $total_retrieval_amount->get()->count();
                $total_retrieval_amount1 = $total_retrieval_amount->sum('amount');

                if ($total_approve_transaction_amount1 != 0 || $total_approve_transaction_count != 0 || $total_declined_transaction_amount1 != 0 || $total_declined_transaction_count != 0 || $total_chargebacks_transaction_amount1 != 0 || $total_chargebacks_transaction_count != 0 || $total_refund_transaction_amount1 != 0 || $total_refund_transaction_count != 0 || $total_flagged_amount1 != 0 || $total_flagged_count != 0 || $total_retrieval_count != 0 || $total_retrieval_amount1 != 0) {
                    $total_transaction_count = $total_approve_transaction_count + $total_declined_transaction_count + $total_chargebacks_transaction_count + $total_refund_transaction_count + $total_flagged_count + $total_retrieval_count;
                    $mainData[$value] = [
                        'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                        'total_approve_transaction_count' => $total_approve_transaction_count,
                        'total_approve_transaction_percentage' => (($total_approve_transaction_count / $total_transaction_count) * 100) . '%',
                        'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                        'total_declined_transaction_count' => $total_declined_transaction_count,
                        'total_declined_transaction_percentage' => (($total_declined_transaction_count / $total_transaction_count) * 100) . '%',
                        'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                        'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                        'total_chargebacks_transaction_percentage' => (($total_chargebacks_transaction_count / $total_transaction_count) * 100) . '%',
                        'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                        'total_refund_transaction_count' => $total_refund_transaction_count,
                        'total_refund_transaction_percentage' => (($total_refund_transaction_count / $total_transaction_count) * 100) . '%',
                        'total_flagged_amount' => $total_flagged_amount1,
                        'total_flagged_count' => $total_flagged_count,
                        'total_flagged_transaction_percentage' => (($total_flagged_count / $total_transaction_count) * 100) . '%',
                        'total_retrieval_amount' => $total_retrieval_amount1,
                        'total_retrieval_count' => $total_retrieval_count,
                        'total_retrieval_transaction_percentage' => (($total_retrieval_count / $total_transaction_count) * 100) . '%',
                    ];
                }
            }
        }

        return $mainData;
    }
    public function getPayoutSummaryReportByMid($input)
    {
        $start_date = $end_date = null;
        $mainData = [];
        if (isset($input['start_date']) && $input['start_date'] != '') {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
        }
        if (isset($input['end_date']) && $input['end_date'] != '') {
            $end_date = date('Y-m-d', strtotime($input['end_date']));
        }
        $getCurrency = static::where('payment_gateway_id', $input['payment_gateway_id']);
        if ($start_date !== null) {
            $getCurrency->whereDate('created_at', '>=', $start_date);
        }
        if ($end_date !== null) {
            $getCurrency->whereDate('created_at', '<=', $end_date);
        }

        $myCurrency = $getCurrency->pluck('currency')->toArray();
        $totalTrans = $getCurrency->count();

        $finalCurrency = array_unique($myCurrency);

        if (sizeof($finalCurrency) > 0) {

            foreach ($finalCurrency as $newCurrency) {
                //start approve transactions
                $total_approve_transaction_amount = static::where('payment_gateway_id', '=', $input['payment_gateway_id'])
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_retrieval', '0')
                    ->where('currency', $newCurrency)
                    ->where('status', '1');
                if (!empty($start_date)) {
                    $total_approve_transaction_amount = $total_approve_transaction_amount->whereDate('created_at', '>=', $start_date);
                }
                if (!empty($end_date)) {
                    $total_approve_transaction_amount = $total_approve_transaction_amount->whereDate('created_at', '<=', $end_date);
                }
                //$total_approve_transaction_count = $total_approve_transaction_amount->count();
                //$total_approve_transaction_amount1  = $total_approve_transaction_amount->sum('amount');
                $total_approve_transaction_amount_get = $total_approve_transaction_amount->pluck('amount')->toArray();
                $total_approve_transaction_amount1 = array_sum($total_approve_transaction_amount_get);
                $total_approve_transaction_count   = sizeof($total_approve_transaction_amount_get);

                $total_approve_transaction_percentage = "0%";
                if ($total_approve_transaction_count !== "0") {
                    $math1 = (($total_approve_transaction_count / $totalTrans) * 100) . '%';
                    $total_approve_transaction_percentage = substr($math1, 0, 4);
                }
                //end approve transactions

                //strat declined transactions
                $total_declined_transaction_amount = static::where('payment_gateway_id', '=', $input['payment_gateway_id'])
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('chargebacks', '<>', '1')
                    ->where('refund', '<>', '1')
                    ->where('currency', $newCurrency)
                    ->where('status', '0');
                if ($start_date !== null) {
                    $total_declined_transaction_amount = $total_declined_transaction_amount->whereDate('created_at', '>=', $start_date);
                }
                if ($end_date !== null) {
                    $total_declined_transaction_amount = $total_declined_transaction_amount->whereDate('created_at', '<=', $end_date);
                }
                //$total_declined_transaction_count = $total_declined_transaction_amount->count();
                //$total_declined_transaction_amount1 = $total_declined_transaction_amount->sum('amount');
                $total_declined_transaction_amount_get = $total_declined_transaction_amount->pluck('amount')->toArray();
                $total_declined_transaction_count = sizeof($total_declined_transaction_amount_get);
                $total_declined_transaction_amount1 = array_sum($total_declined_transaction_amount_get);

                $total_declined_transaction_percentage = "0%";
                if ($total_declined_transaction_count !== "0") {
                    $math2 = (($total_declined_transaction_count / $totalTrans) * 100) . '%';
                    $total_declined_transaction_percentage = substr($math2, 0, 4);
                }
                //end declined transactions

                //strat chargebacks transactions
                $total_chargebacks_transaction_amount = static::where('payment_gateway_id', '=', $input['payment_gateway_id'])
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $newCurrency)
                    ->where('chargebacks', '1');
                if ($start_date !== null) {
                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->whereDate('chargebacks_date', '>=', $start_date);
                }
                if ($end_date !== null) {
                    $total_chargebacks_transaction_amount = $total_chargebacks_transaction_amount->whereDate('chargebacks_date', '<=', $end_date);
                }
                //$total_chargebacks_transaction_count = $total_chargebacks_transaction_amount->count();
                //$total_chargebacks_transaction_amount1 = $total_chargebacks_transaction_amount->sum('amount');
                $total_chargebacks_transaction_amount_get = $total_chargebacks_transaction_amount->pluck('amount')->toArray();
                $total_chargebacks_transaction_count = sizeof($total_chargebacks_transaction_amount_get);
                $total_chargebacks_transaction_amount1 = array_sum($total_chargebacks_transaction_amount_get);

                $total_chargebacks_transaction_percentage = "0%";
                if ($total_chargebacks_transaction_count !== "0") {
                    $math3 = (($total_chargebacks_transaction_count / $totalTrans) * 100) . '%';
                    $total_chargebacks_transaction_percentage = substr($math3, 0, 4);
                }
                //end chargebacks transactions

                //start refund transactions
                $total_refund_transaction_amount = static::where('payment_gateway_id', '=', $input['payment_gateway_id'])
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $newCurrency)
                    ->where('refund', '1');
                if ($start_date !== null) {
                    $total_refund_transaction_amount = $total_refund_transaction_amount->whereDate('refund_date', '>=', $start_date);
                }
                if ($end_date !== null) {
                    $total_refund_transaction_amount = $total_refund_transaction_amount->whereDate('refund_date', '<=', $end_date);
                }
                //$total_refund_transaction_count = $total_refund_transaction_amount->count();
                //$total_refund_transaction_amount1 = $total_refund_transaction_amount->sum('amount');
                $total_refund_transaction_amount_get = $total_refund_transaction_amount->pluck('amount')->toArray();
                $total_refund_transaction_count = sizeof($total_refund_transaction_amount_get);
                $total_refund_transaction_amount1 = array_sum($total_refund_transaction_amount_get);

                $total_refund_transaction_percentage = "0%";
                if ($total_refund_transaction_count !== "0") {
                    $math4 = (($total_refund_transaction_count / $totalTrans) * 100) . '%';
                    $total_refund_transaction_percentage = substr($math4, 0, 4);
                }
                //end refund transactions
                //strat flagged transactions
                $total_flagged_amount = static::where('payment_gateway_id', '=', $input['payment_gateway_id'])
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $newCurrency)
                    ->where('is_flagged', '1');
                if ($start_date !== null) {
                    $total_flagged_amount = $total_flagged_amount->whereDate('flagged_date', '>=', $start_date);
                }
                if ($end_date !== null) {
                    $total_flagged_amount = $total_flagged_amount->whereDate('flagged_date', '<=', $end_date);
                }
                //$total_flagged_count = $total_flagged_amount->count();
                //$total_flagged_amount1 = $total_flagged_amount->sum('amount');
                $total_flagged_amount_get = $total_flagged_amount->pluck('amount')->toArray();
                $total_flagged_count = sizeof($total_flagged_amount_get);
                $total_flagged_amount1 = array_sum($total_flagged_amount_get);

                $total_flagged_transaction_percentage = "0%";
                if ($total_flagged_count !== "0") {
                    $math5 = (($total_flagged_count / $totalTrans) * 100) . '%';
                    $total_flagged_transaction_percentage = substr($math5, 0, 4);
                }
                //end flagged transactions

                //strat retrieval transacions
                $total_retrieval_amount = static::where('payment_gateway_id', '=', $input['payment_gateway_id'])
                    ->where('resubmit_transaction', '<>', '2')
                    ->where('is_batch_transaction', '0')
                    ->where('currency', $newCurrency)
                    ->where('is_retrieval', '1');
                if ($start_date !== null) {
                    $total_retrieval_amount = $total_retrieval_amount->whereDate('retrieval_date', '>=', $start_date);
                }
                if ($end_date !== null) {
                    $total_retrieval_amount = $total_retrieval_amount->whereDate('retrieval_date', '<=', $end_date);
                }
                //$total_retrieval_count = $total_retrieval_amount->count();
                //$total_retrieval_amount1 = $total_retrieval_amount->sum('amount');
                $total_retrieval_amount_get = $total_retrieval_amount->pluck('amount')->toArray();
                $total_retrieval_count = sizeof($total_retrieval_amount_get);
                $total_retrieval_amount1 = array_sum($total_retrieval_amount_get);


                $total_retrieval_transaction_percentage = "0%";
                if ($total_retrieval_count !== "0") {
                    $math6 = (($total_retrieval_count / $totalTrans) * 100) . '%';
                    $total_retrieval_transaction_percentage = substr($math6, 0, 4);
                }
                //end retrieval transacions
                if (isset($input['type']) && $input['type'] == "xlsx") {
                    $mainData[] = [
                        'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                        'total_approve_transaction_count' => $total_approve_transaction_count,
                        'total_approve_transaction_percentage' => $total_approve_transaction_percentage,
                        'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                        'total_declined_transaction_count' => $total_declined_transaction_count,
                        'total_declined_transaction_percentage' => $total_declined_transaction_percentage,
                        'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                        'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                        'total_chargebacks_transaction_percentage' => $total_chargebacks_transaction_percentage,
                        'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                        'total_refund_transaction_count' => $total_refund_transaction_count,
                        'total_refund_transaction_percentage' => $total_refund_transaction_percentage,
                        'total_flagged_amount' => $total_flagged_amount1,
                        'total_flagged_count' => $total_flagged_count,
                        'total_flagged_transaction_percentage' => $total_flagged_transaction_percentage,
                        'total_retrieval_amount' => $total_retrieval_amount1,
                        'total_retrieval_count' => $total_retrieval_count,
                        'total_retrieval_transaction_percentage' => $total_retrieval_transaction_percentage
                    ];
                } else {
                    $mainData[$newCurrency] = [
                        'total_approve_transaction_amount' => $total_approve_transaction_amount1,
                        'total_approve_transaction_count' => $total_approve_transaction_count,
                        'total_approve_transaction_percentage' => $total_approve_transaction_percentage,
                        'total_declined_transaction_amount' => $total_declined_transaction_amount1,
                        'total_declined_transaction_count' => $total_declined_transaction_count,
                        'total_declined_transaction_percentage' => $total_declined_transaction_percentage,
                        'total_chargebacks_transaction_amount' => $total_chargebacks_transaction_amount1,
                        'total_chargebacks_transaction_count' => $total_chargebacks_transaction_count,
                        'total_chargebacks_transaction_percentage' => $total_chargebacks_transaction_percentage,
                        'total_refund_transaction_amount' => $total_refund_transaction_amount1,
                        'total_refund_transaction_count' => $total_refund_transaction_count,
                        'total_refund_transaction_percentage' => $total_refund_transaction_percentage,
                        'total_flagged_amount' => $total_flagged_amount1,
                        'total_flagged_count' => $total_flagged_count,
                        'total_flagged_transaction_percentage' => $total_flagged_transaction_percentage,
                        'total_retrieval_amount' => $total_retrieval_amount1,
                        'total_retrieval_count' => $total_retrieval_count,
                        'total_retrieval_transaction_percentage' => $total_retrieval_transaction_percentage
                    ];
                }
            }
            return $mainData;
        } else {
            return $mainData;
        }
        return $mainData;
    }
    public function mostFlaggedChargebacksReport($input)
    {
        $start_date = Carbon::parse($input['start_date'])->format('Y-m-d');
        $end_date   = Carbon::parse($input['end_date'])->format('Y-m-d');
        $type       = $input['type'];
        $finalMostData = [];
        if ($type == "is_flagged") {
            $data1 = static::whereDate('flagged_date', '>=', $start_date)
                ->whereDate('flagged_date', '<=', $end_date)
                ->where('is_flagged', '1');
            if (!empty($input['payment_gateway_id'])) {
                $data1 = $data1->where('payment_gateway_id', $input['payment_gateway_id']);
            }
            $data1 = $data1->pluck('user_id')->toArray();
            $mytest1 = array_count_values($data1);

            $finalMostData = ['data' => $mytest1, 'type' => $type];
        } else if ($type == "chargebacks") {
            $data2 = static::where('resubmit_transaction', '<>', '2')
                ->where('is_batch_transaction', '0')
                ->whereDate('chargebacks_date', '>=', $start_date)
                ->whereDate('chargebacks_date', '<=', $end_date)
                ->where('chargebacks', '1');
            if (!empty($input['payment_gateway_id'])) {
                $data2 = $data2->where('payment_gateway_id', $input['payment_gateway_id']);
            }
            $data2 = $data2->pluck('user_id')->toArray();
            $mytest2 = array_count_values($data2);

            $finalMostData = ['data' => $mytest2, 'type' => $type];
        } else if ($type == "is_retrieval") {
            $data3 = static::where('resubmit_transaction', '<>', '2')
                ->where('is_batch_transaction', '0')
                ->whereDate('retrieval_date', '>=', $start_date)
                ->whereDate('retrieval_date', '<=', $end_date)
                ->where('is_retrieval', '1');
            if (!empty($input['payment_gateway_id'])) {
                $data3 = $data3->where('payment_gateway_id', $input['payment_gateway_id']);
            }
            $data3 = $data3->pluck('user_id')->toArray();
            $mytest3 = array_count_values($data3);
            $finalMostData = ['data' => $mytest3, 'type' => $type];
        } else {
            $data4 = static::where('resubmit_transaction', '<>', '2')
                ->where('is_batch_transaction', '0')
                ->whereDate('refund_date', '>=', $start_date)
                ->whereDate('refund_date', '<=', $end_date)
                ->where('refund', '1');
            if (!empty($input['payment_gateway_id'])) {
                $data4 = $data4->where('payment_gateway_id', $input['payment_gateway_id']);
            }
            $data4 = $data4->pluck('user_id')->toArray();
            $mytest4 = array_count_values($data4);
            $finalMostData = ['data' => $mytest4, 'type' => $type];
        }
        return $finalMostData;
    }
    public function getAllBatchTransactionSearch($input)
    {
        $start_date = date('Y-m-d', strtotime($input['start_date']));
        $end_date = date('Y-m-d', strtotime($input['end_date']));

        $data = static::select('transactions.*')
            ->where('user_id', $input['company_name'])
            ->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00')
            ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 23:59:59');

        if ((isset($input['select_from_mid']) && $input['select_from_mid'] != '')) {
            $data = $data->where('payment_gateway_id', $input['select_from_mid']);
        }

        if ((isset($input['card_type']) && $input['card_type'] != '')) {
            $data = $data->where('card_type', $input['card_type']);
        }

        $data = $data->get();

        return $data;
    }

    // ================================================
    /* method : getSingleTransaction
    * @param  : $input array
    * @Description : get transaction API
    */ // ==============================================
    public function getSingleTransaction($input)
    {
        $transaction = static::select('transactions.*')
            ->leftJoin('users', 'transactions.user_id', 'users.id')
            ->where('users.api_key', $input['api_key'])
            ->where('users.is_active', '1');
        if ((isset($input['order_id']) && $input['order_id'] != null)) {
            $transaction = $transaction->where('transactions.order_id', $input['order_id']);
        }
        if ((isset($input['customer_order_id']) && $input['customer_order_id'] != null)) {
            $transaction = $transaction->where('transactions.customer_order_id', $input['customer_order_id']);
        }
        $transaction = $transaction->orderBy('transactions.id', 'desc')
            ->first();

        return $transaction;
    }

    /*
    |=============================|
    | For A Agent Porpouse        |
    |=============================|
    */

    public function getAgentChartData($input)
    {
        // Success transaction count and amount
        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');
        $successTran = \DB::table('transactions')->select(DB::raw("SUM(amount) as successTranAmount"), DB::raw("count(*) as successcount"))->whereIn('user_id', $userIds);
        $successTran = $successTran->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->first();
        //echo "<pre>";print_r($successTran);exit();
        // Declined transaction count and amount
        $failTran = \DB::table('transactions')->select(DB::raw("SUM(amount) as failTranAmount"), DB::raw("count(*) as failCount"))->whereIn('user_id', $userIds);
        $failTran = $failTran->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->whereNull('transactions.deleted_at')
            ->first();
        // Chargebacks transaction count and amount
        $chargebacksTran = \DB::table('transactions')->select(DB::raw("SUM(amount) as chargebacksTranAmount"), DB::raw("count(*) as chargebacksCount"))->whereIn('user_id', $userIds);
        $chargebacksTran = $chargebacksTran->where('chargebacks', '1')
            ->whereNull('transactions.deleted_at')
            ->first();
        // Refund transaction count and amount
        $refundTran = \DB::table('transactions')->select(DB::raw("SUM(amount) as refundTranAmount"), DB::raw("count(*) as refundCount"))->whereIn('user_id', $userIds);
        $refundTran = $refundTran->where('refund', '1')
            ->whereNull('transactions.deleted_at')
            ->first();
        // Flagged transaction count and amount
        $flaggedTran = \DB::table('transactions')->select(DB::raw("SUM(amount) as flaggedTranAmount"), DB::raw("count(*) as flaggedCount"))->whereIn('user_id', $userIds);
        $flaggedTran = $flaggedTran->where('is_flagged', '1')
            ->whereNull('transactions.deleted_at')
            ->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->first();
        // total transaction count and amount
        $totalTran = $successTran->successcount + $failTran->failCount +  $chargebacksTran->chargebacksCount + $refundTran->refundCount + $flaggedTran->flaggedCount;
        $totalTranAmount = $successTran->successTranAmount + $failTran->failTranAmount + $chargebacksTran->chargebacksTranAmount + $refundTran->refundTranAmount + $flaggedTran->flaggedTranAmount;
        return [
            'success' => $successTran->successcount,
            'fail' => $failTran->failCount,
            'chargebacks' => $chargebacksTran->chargebacksCount,
            'refund' => $refundTran->refundCount,
            'flagged' => $flaggedTran->flaggedCount,
            'total' => $totalTran,
            'successamount' => $successTran->successTranAmount,
            'failamount' => $failTran->failTranAmount,
            'chargebacksamount' => $chargebacksTran->chargebacksTranAmount,
            'refundamount' => $refundTran->refundTranAmount,
            'flaggedamount' => $flaggedTran->flaggedTranAmount,
            'totalamount' => $totalTranAmount,
        ];
    }

    public function getAgentLineChartData($input)
    {
        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');
        $start_date = Carbon::now()->subDays(30);
        $end_date = Carbon::now();

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));
        }

        $successTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('status', '1')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->where('transactions.is_retrieval', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $successTran = $successTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $successTran = $successTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $successTran = $successTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->whereIn('user_id', $userIds)
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $refundTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('refund', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $refundTran = $refundTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $refundTran = $refundTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $refundTran = $refundTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->whereIn('user_id', $userIds)
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $chargebacksTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('chargebacks', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $chargebacksTran = $chargebacksTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $chargebacksTran = $chargebacksTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $chargebacksTran = $chargebacksTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->whereIn('user_id', $userIds)
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $failTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('status', '0')
            ->where('chargebacks', '<>', '1')
            ->where('refund', '<>', '1')
            ->where('is_flagged', '<>', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $failTran = $failTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $failTran = $failTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $failTran = $failTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->whereIn('user_id', $userIds)
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $flaggedTran = \DB::table('transactions')
            ->select(\DB::raw('DATE_FORMAT(created_at,"%Y-%c-%e") as day'), \DB::raw('count(*) as user_count'))
            ->where('is_flagged', '1')
            ->where('resubmit_transaction', '<>', '2')
            ->where('is_batch_transaction', '0')
            ->whereNull('transactions.deleted_at')
            ->whereBetween('created_at', [$start_date, $end_date]);
        if ((isset($input['user_id']) && $input['user_id'] != '')) {
            $flaggedTran = $flaggedTran->where('transactions.user_id', $input['user_id']);
        }
        if ((isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '')) {
            $flaggedTran = $flaggedTran->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        $flaggedTran = $flaggedTran->whereNotIn('transactions.payment_gateway_id', ['16', '41'])
            ->whereIn('user_id', $userIds)
            ->groupBy(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d")'))
            ->pluck('user_count', 'day');

        $data_array = [];
        $i = 0;
        while (strtotime($start_date) <= strtotime($end_date)) {
            $start_date = date("Y-n-j", strtotime($start_date));

            // date
            $data_array[$i][] = date("Y-m-d", strtotime($start_date));

            // success value
            if (isset($successTran[$start_date])) {
                $data_array[$i][] = $successTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // failed value
            if (isset($failTran[$start_date])) {
                $data_array[$i][] = $failTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // chargeback value
            if (isset($chargebacksTran[$start_date])) {
                $data_array[$i][] = $chargebacksTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // refund value
            if (isset($refundTran[$start_date])) {
                $data_array[$i][] = $refundTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            // flagged value
            if (isset($flaggedTran[$start_date])) {
                $data_array[$i][] = $flaggedTran[$start_date];
            } else {
                $data_array[$i][] = 0;
            }

            $i++;
            $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
        }
        return $data_array;
    }

    public function latest10TransactionsForAgent()
    {
        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');
        $data = static::select('applications.business_name', 'transactions.*', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->whereIn('transactions.user_id', $userIds)
            ->take(10)
            ->whereNull('transactions.deleted_at')
            ->orderBy('id', 'DESC')->get();

        return $data;
    }

    public function getAllMerchantTransactionDataAgent($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from RP transactions", 'slave_connection');
        }

        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');

        $data = static::select('applications.business_name', 'transactions.id', 'transactions.email', 'transactions.order_id', 'transactions.amount', 'transactions.currency', 'transactions.status', 'transactions.card_type', 'middetails.bank_name', 'transactions.first_name', 'transactions.last_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id)
            ->whereIn('transactions.user_id', $userIds)
            ->orderBy('transactions.id', 'DESC');

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('applications.business_name',  'like', '%' . $input['company_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = date('Y-m-d', strtotime($input['end_date']));
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        if (isset($input['global_search']) && $input['global_search'] != '') {
            $data = $data->where(function ($query) use ($input) {
                $query->orWhere('transactions.id', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.order_id', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.descriptor', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('applications.business_name', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.phone_no', 'like', '%' . $input['global_search'] . '%')
                    ->orWhere('transactions.email', 'like', '%' . $input['global_search'] . '%');
            });
        }

        $data = $data->paginate($noList);

        return $data;
    }

    public function getAllMerchantRefundTransactionDataAgent($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from RP transactions", 'slave_connection');
        }

        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');

        $data = static::select('applications.business_name', 'transactions.*', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->orderBy('id', 'DESC');

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        } else {
            $data = $data->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('applications.business_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        //Refund date filter
        if ((isset($input['refund_start_date']) && $input['refund_start_date'] != '') &&  (isset($input['refund_end_date']) && $input['refund_end_date'] != '')) {
            $start_date = $input['refund_start_date'];
            $end_date = $input['refund_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.refund_date)'), '<=', $end_date);
        } else if ((isset($input['refund_start_date']) && $input['refund_start_date'] != '') || (isset($input['refund_end_date']) && $input['refund_end_date'] == '')) {
            $start_date = $input['refund_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '>=', $start_date);
        } else if ((isset($input['refund_start_date']) && $input['refund_start_date'] == '') || (isset($input['refund_end_date']) && $input['refund_end_date'] != '')) {
            $end_date = $input['refund_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.refund_date)'), '<=', $end_date);
        }
        $data = $data->where('transactions.refund', '1')
            ->whereIn('transactions.user_id', $userIds);

        $data = $data->paginate($noList);

        return $data;
    }

    public function getAllMerchantFlaggedTransactionDataAgent($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from RP transactions", 'slave_connection');
        }

        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');

        $data = static::select('applications.business_name', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"flagged"'));
            })->orderBy('id', 'DESC');

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        } else {
            $data = $data->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('applications.business_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        //flagged date filter
        if ((isset($input['flagged_start_date']) && $input['flagged_start_date'] != '') && (isset($input['flagged_end_date']) && $input['flagged_end_date'] != '')) {
            $start_date = $input['flagged_start_date'];
            $end_date = $input['flagged_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.flagged_date)'), '<=', $end_date);
        } else if ((isset($input['flagged_start_date']) && $input['flagged_start_date'] != '') || (isset($input['flagged_end_date']) && $input['flagged_end_date'] == '')) {
            $start_date = $input['flagged_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '>=', $start_date);
        } else if ((isset($input['flagged_start_date']) && $input['flagged_start_date'] == '') || (isset($input['flagged_end_date']) && $input['flagged_end_date'] != '')) {
            $end_date = $input['flagged_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.flagged_date)'), '<=', $end_date);
        }
        $data = $data->where('transactions.is_flagged', '1')
            ->whereIn('transactions.user_id', $userIds);

        $data = $data->paginate($noList);

        return $data;
    }

    public function getAllMerchantRetrievalTransactionDataAgent($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from RP transactions", 'slave_connection');
        }

        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');

        $data = static::select('applications.business_name', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"retrieval"'));
            })->orderBy('id', 'DESC');

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        } else {
            $data = $data->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('applications.business_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }
        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        //retrieval date filter
        if ((isset($input['retrieval_start_date']) && $input['retrieval_start_date'] != '') && (isset($input['retrieval_end_date']) && $input['retrieval_end_date'] != '')) {
            $start_date = $input['retrieval_start_date'];
            $end_date = $input['retrieval_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.retrieval_date)'), '<=', $end_date);
        } else if ((isset($input['retrieval_start_date']) && $input['retrieval_start_date'] != '') || (isset($input['retrieval_end_date']) && $input['retrieval_end_date'] == '')) {
            $start_date = $input['retrieval_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '>=', $start_date);
        } else if ((isset($input['retrieval_start_date']) && $input['retrieval_start_date'] == '') || (isset($input['retrieval_end_date']) && $input['retrieval_end_date'] != '')) {
            $end_date = $input['retrieval_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.retrieval_date)'), '<=', $end_date);
        }
        $data = $data->where('transactions.is_retrieval', '1')
            ->whereIn('transactions.user_id', $userIds);

        $data = $data->paginate($noList);

        return $data;
    }

    public function getAllMerchantChargebacksTransactionDataAgent($input, $noList)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from RP transactions", 'slave_connection');
        }

        $userIds = \DB::table('users')->where('agent_id', auth()->guard('agentUser')->user()->id)->pluck('id');

        $data = static::select('applications.business_name', 'transactions.*', 'transactions_document_upload.files as transactions_document_upload_files', 'middetails.bank_name')
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->join('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->leftjoin('transactions_document_upload', function ($join) {
                $join->on('transactions_document_upload.transaction_id', '=', 'transactions.id')
                    ->on('transactions_document_upload.files_for', '=', \DB::raw('"chargebacks"'));
            });

        if (isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }

        if (isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }

        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        } else {
            $data = $data->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id);
        }

        if (isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('applications.business_name',  'like', '%' . $input['company_name'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }

        if (isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = $input['start_date'];
            $end_date = $input['end_date'];

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        } else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = $input['start_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date);
        } else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = $input['end_date'];
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);
        }
        //chargebacks date filter
        if ((isset($input['chargebacks_start_date']) && $input['chargebacks_start_date'] != '') && (isset($input['chargebacks_end_date']) && $input['chargebacks_end_date'] != '')) {
            $start_date = $input['chargebacks_start_date'];
            $end_date = $input['chargebacks_end_date'];

            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', $start_date)
                ->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', $end_date);
        } else if ((isset($input['chargebacks_start_date']) && $input['chargebacks_start_date'] != '') || (isset($input['chargebacks_end_date']) && $input['chargebacks_end_date'] == '')) {
            $start_date = $input['chargebacks_start_date'];
            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '>=', $start_date);
        } else if ((isset($input['chargebacks_start_date']) && $input['chargebacks_start_date'] == '') || (isset($input['chargebacks_end_date']) && $input['chargebacks_end_date'] != '')) {
            $end_date = $input['chargebacks_end_date'];
            $data = $data->where(DB::raw('DATE(transactions.chargebacks_date)'), '<=', $end_date);
        }
        $data = $data->where('transactions.chargebacks', '1')
            ->whereIn('transactions.user_id', $userIds)
            ->orderBy('transactions.chargebacks_date', 'desc');
        $data = $data->paginate($noList);

        return $data;
    }

    // ================================================
    /* method : getDataToMarkFlag
    * @param  :
    * @description : get data to mark flag
    */ // ==============================================
    public function getDataToMarkFlag($input, $noList, $finalId)
    {
        $start_date = date('Y-m-d 00:00:00', strtotime($input['start_date']));
        $end_date = date('Y-m-d 23:59:59', strtotime($input['end_date']));
        if (
            isset($input['include_email']) && $input['include_email'] == 'yes' &&
            isset($input['nos_email']) && $input['nos_email'] > 0
        ) {

            $email_array = static::select(
                'transactions.*',
                DB::raw('COUNT(transactions.email) as email_count'),
                DB::raw('COUNT(transactions.card_no) as card_count')
            )
                ->where('status', '1')
                ->where(DB::raw('DATE(created_at)'), '>=', $start_date)
                ->where(DB::raw('DATE(created_at)'), '<=', $end_date);


            if (isset($input['country']) && $input['country'] != '') {
                $email_array = $email_array->where('country', $input['country']);
            }
            if (isset($input['currency']) && $input['currency'] != '') {
                $email_array = $email_array->where('currency', $input['currency']);
            }
            if (isset($input['gateway_id']) && $input['gateway_id'] != '') {
                $email_array = $email_array->where('gateway_id', $input['gateway_id']);
            }
            if (isset($input['greater_then']) && $input['greater_then'] != '') {
                $email_array = $email_array->where('amount', '>=', $input['greater_then']);
            }
            if (isset($input['less_then']) && $input['less_then'] != '') {
                $email_array = $email_array->where('amount', '<=', $input['less_then']);
            }
            if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
                $email_array = $email_array->where('payment_gateway_id', $input['payment_gateway_id']);
            }
            if (isset($input['user_id']) && $input['user_id'] != '') {
                $email_array = $email_array->where('user_id', $input['user_id']);
            }

            $email_array = $email_array->groupBy('email')
                ->having('email_count', '>=', $input['nos_email'])
                ->pluck('email')
                ->toArray();
        } else {
            $email_array = [];
            $cardList    = [];
        }
        $data = static::select(
            'applications.business_name',
            'transactions.*',
            'middetails.bank_name'
        )
            ->join('applications', 'applications.user_id', 'transactions.user_id')
            ->leftJoin('middetails', 'middetails.id', 'transactions.payment_gateway_id')
            ->where('transactions.status', '1')
            ->where('transactions.chargebacks', '0')
            ->where('transactions.is_retrieval', '0')
            ->where('transactions.refund', '0')
            ->where('transactions.is_flagged', '0')
            ->whereNull('transactions.flagged_date')
            ->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date)
            ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date);

        if (isset($input['country']) && $input['country'] != '') {
            $data = $data->where('transactions.country', $input['country']);
        }
        if (isset($input['currency']) && $input['currency'] != '') {
            $data = $data->where('transactions.currency', $input['currency']);
        }
        if (isset($input['gateway_id']) && $input['gateway_id'] != '') {
            $data = $data->where('transactions.gateway_id', $input['gateway_id']);
        }
        if (isset($input['greater_then']) && $input['greater_then'] != '') {
            $data = $data->where('transactions.amount', '>=', $input['greater_then']);
        }
        if (isset($input['less_then']) && $input['less_then'] != '') {
            $data = $data->where('transactions.amount', '<=', $input['less_then']);
        }
        if (isset($input['payment_gateway_id']) && $input['payment_gateway_id'] != '') {
            $data = $data->where('transactions.payment_gateway_id', $input['payment_gateway_id']);
        }
        if (isset($input['user_id']) && $input['user_id'] != '') {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }

        // card and email filter
        if (isset($input['include_card']) && $input['include_card'] == 'yes' && isset($input['nos_card']) && $input['nos_card'] > 0 && isset($input['include_email']) && $input['include_email'] == 'yes' && isset($input['nos_email']) && $input['nos_email'] > 0) {
            $data = $data->whereIn('transactions.id', $finalId)->whereIn('transactions.email', $email_array);
        } elseif (
            isset($input['include_email']) && $input['include_email'] == 'yes' && isset($input['nos_email']) && $input['nos_email'] > 0
        ) {
            $data = $data->whereIn('transactions.email', $email_array);
        } elseif (
            isset($input['include_card']) && $input['include_card'] == 'yes' && isset($input['nos_card']) && $input['nos_card'] > 0
        ) {
            $data = $data->whereIn('transactions.id', $finalId);
        }
        $dataId = $data->pluck('id')->toArray();
        $data = $data->orderBy('transactions.id', 'desc')->paginate($noList);
        $finalArray = ['ids' => $dataId, 'data' => $data];
        return $finalArray;
    }


    // Created by Hws Team
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    // Created by Hws Team

    public function getMerchantTransactionReport($input)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin merchant transaction report", 'slave_connection');
        }
        $data = static::select(
            'transactions.user_id',
            'transactions.currency',
            'applications.business_name',
            DB::raw("SUM(IF(transactions.status = '1', 1, 0)) as success_count"),
            DB::raw("SUM(IF(transactions.status = '1', transactions.amount, 0.00)) AS success_amount"),
            DB::raw("(SUM(IF(transactions.status = '1', 1, 0)*100)/SUM(IF(transactions.status = '1' OR transactions.status = '0', 1, 0))) AS success_percentage"),
            DB::raw("SUM(IF(transactions.status = '0', 1, 0)) as declined_count"),
            DB::raw("SUM(IF(transactions.status = '0' , transactions.amount,0.00 )) AS declined_amount"),
            DB::raw("(SUM(IF(transactions.status = '0', 1, 0)*100)/SUM(IF(transactions.status = '1' OR transactions.status = '0', 1, 0))) AS declined_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.chargebacks = '1' AND transactions.chargebacks_remove = '0', 1, 0)) chargebacks_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.chargebacks = '1' AND transactions.chargebacks_remove = '0', amount, 0)) AS chargebacks_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.chargebacks = '1' AND transactions.chargebacks_remove = '0', 1, 0))*100/SUM(IF(transactions.status = '1', 1, 0))) AS chargebacks_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.refund = '1' AND transactions.refund_remove='0', 1, 0)) refund_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.refund = '1' AND transactions.refund_remove='0', amount, 0)) AS refund_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.refund = '1' AND transactions.refund_remove='0', 1, 0))/SUM(IF(transactions.status = '1', 1, 0))) AS refund_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_flagged = '1' AND transactions.is_flagged_remove= '0', 1, 0)) AS flagged_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_flagged = '1' AND transactions.is_flagged_remove= '0', amount, 0)) AS flagged_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.is_flagged = '1' AND transactions.is_flagged_remove= '0', 1, 0))/SUM(IF(transactions.status = '1', 1, 0))) AS flagged_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_retrieval  = '1' AND transactions.is_retrieval_remove= '0', 1, 0)) retrieval_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_retrieval  = '1' AND transactions.is_retrieval_remove= '0', amount, 0)) AS retrieval_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.is_retrieval = '1' AND transactions.is_retrieval_remove= '0', 1, 0)*100)/SUM(IF(transactions.status = '1', 1, 0))) retrieval_percentage"),

            DB::raw("SUM(IF(transactions.status = '5', 1, 0)) AS block_count"),
            DB::raw("SUM(IF(transactions.status = '5', transactions.amount, 0.00)) AS block_amount"),
            DB::raw("(SUM(IF(transactions.status = '5', 1, 0))/SUM(IF(transactions.status = '1', 1, 0))) AS block_percentage"),

        )->leftJoin('applications', 'applications.user_id', '=', 'transactions.user_id')
        ->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id);

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }

        if (isset($input['currency']) && $input['currency'] != null) {
            $data = $data->where('transactions.currency', $input['currency']);
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d 00:00:00', strtotime($input['start_date']));
            $end_date = date('Y-m-d 23:59:59', strtotime($input['end_date']));

            $data = $data->where('transactions.transaction_date', '>=', $start_date)
                ->where('transactions.transaction_date', '<=', $end_date);
        }

        if ((!isset($_GET['for']) && !isset($_GET['end_date'])) || (isset($_GET['for']) && $_GET['for'] == 'Daily')) {

            $data = $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00'))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        if (isset($input['for']) && $input['for'] == 'Weekly') {
            $data = $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00', strtotime('-6 days')))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        if (isset($input['for']) && $input['for'] == 'Monthly') {
            $data = $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00', strtotime('-30 days')))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        if (isset($input['success_per']) && $input['success_per'] != null) {
            $data = $data->having('success_percentage', '>', $input['success_per']);
        }

        if (isset($input['decline_per']) && $input['decline_per'] != null) {
            $data = $data->having('declined_percentage', '>', $input['decline_per']);
        }

        if (isset($input['chargebacks_per']) && $input['chargebacks_per'] != null) {
            $data = $data->having('chargebacks_percentage', '>', $input['chargebacks_per']);
        }

        if (isset($input['refund_per']) && $input['refund_per'] != null) {
            $data = $data->having('refund_percentage', '>', $input['refund_per']);
        }

        if (isset($input['suspicious_per']) && $input['suspicious_per'] != null) {
            $data = $data->having('flagged_percentage', '>', $input['suspicious_per']);
        }

        if (isset($input['retrieval_per']) && $input['retrieval_per'] != null) {
            $data = $data->having('retrieval_percentage', '>', $input['retrieval_per']);
        }

        if (isset($input['block_per']) && $input['block_per'] != null) {
            $data = $data->having('block_percentage', '>', $input['block_per']);
        }

        $data = $data->groupBy('transactions.user_id', 'transactions.currency')->orderBy('success_amount', 'desc')->get()->toArray();
        // ->toSql();
        // echo $data;exit();
        //->get()->toArray();

        return $data;
    }

    public function getTransactionSummaryRP($input)
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transaction summary report", 'slave_connection');
        }

        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        $data = static::select(
            'currency',
            DB::raw("SUM(IF(transactions.status = '1', 1, 0)) as success_count"),
            DB::raw("SUM(IF(transactions.status = '1', transactions.amount, 0.00)) AS success_amount"),
            DB::raw("(SUM(IF(transactions.status = '1', 1, 0)*100)/SUM(IF(transactions.status = '1' OR transactions.status = '0', 1, 0))) AS success_percentage"),
            DB::raw("SUM(IF(transactions.status = '0', 1, 0)) as declined_count"),
            DB::raw("SUM(IF(transactions.status = '0' , transactions.amount,0.00 )) AS declined_amount"),
            DB::raw("(SUM(IF(transactions.status = '0', 1, 0)*100)/SUM(IF(transactions.status = '1' OR transactions.status = '0', 1, 0))) AS declined_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.chargebacks = '1' AND transactions.chargebacks_remove = '0', 1, 0)) chargebacks_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.chargebacks = '1' AND transactions.chargebacks_remove = '0', amount, 0)) AS chargebacks_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.chargebacks = '1' AND transactions.chargebacks_remove = '0', 1, 0))*100/SUM(IF(transactions.status = '1', 1, 0))) AS chargebacks_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.refund = '1' AND transactions.refund_remove='0', 1, 0)) refund_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.refund = '1' AND transactions.refund_remove='0', amount, 0)) AS refund_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.refund = '1' AND transactions.refund_remove='0', 1, 0))/SUM(IF(transactions.status = '1', 1, 0))) AS refund_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_flagged = '1' AND transactions.is_flagged_remove= '0', 1, 0)) AS flagged_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_flagged = '1' AND transactions.is_flagged_remove= '0', amount, 0)) AS flagged_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.is_flagged = '1' AND transactions.is_flagged_remove= '0', 1, 0))/SUM(IF(transactions.status = '1', 1, 0))) AS flagged_percentage"),

            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_retrieval  = '1' AND transactions.is_retrieval_remove= '0', 1, 0)) retrieval_count"),
            DB::raw("SUM(IF(transactions.status = '1' AND transactions.is_retrieval  = '1' AND transactions.is_retrieval_remove= '0', amount, 0)) AS retrieval_amount"),
            DB::raw("(SUM(IF(transactions.status = '1' AND transactions.is_retrieval = '1' AND transactions.is_retrieval_remove= '0', 1, 0)*100)/SUM(IF(transactions.status = '1', 1, 0))) retrieval_percentage"),

            DB::raw("SUM(IF(transactions.status = '5', 1, 0)) AS block_count"),
            DB::raw("SUM(IF(transactions.status = '5', transactions.amount, 0.00)) AS block_amount"),
            DB::raw("(SUM(IF(transactions.status = '5', 1, 0))/SUM(IF(transactions.status = '1', 1, 0))) AS block_percentage")
        )->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id);

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('user_id', $input['user_id']);
        }

        if (isset($input['currency']) && $input['currency'] != null) {
            $data = $data->where('currency', $input['currency']);
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d 00:00:00', strtotime($input['start_date']));
            $end_date = date('Y-m-d 23:59:59', strtotime($input['end_date']));

            $data = $data->where('transactions.transaction_date', '>=', $start_date)
                ->where('transactions.transaction_date', '<=', $end_date);
        }

        if ((!isset($_GET['for']) && !isset($_GET['end_date'])) || (isset($_GET['for']) && $_GET['for'] == 'Daily')) {
            $data = $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00'))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        if (isset($input['for']) && $input['for'] == 'Weekly') {
            $data = $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00', strtotime('-6 days')))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        if (isset($input['for']) && $input['for'] == 'Monthly') {
            $data = $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00', strtotime('-30 days')))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        $data = $data->groupBy('currency')->orderBy('success_amount', 'desc')->get()->toArray();
        // ->toSql();
        // echo $data;exit();
        //->get()->toArray();

        return $data;
    }
}
