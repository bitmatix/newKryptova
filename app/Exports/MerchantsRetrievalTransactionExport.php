<?php

namespace App\Exports;

use DB;
use App\Transaction;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class MerchantsRetrievalTransactionExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from RP transactions", 'slave_connection');
        }
        
        $input = request()->all();

        if(auth()->guard('agentUser')->user()->main_agent_id == 0){
            $agentId = auth()->guard('agentUser')->user()->id;
        }else{
            $agentId = auth()->guard('agentUser')->user()->main_agent_id;
        }

        $userIds = \DB::table('users')->where('agent_id', $agentId)->pluck('id');

        $data = Transaction::select('applications.business_name','transactions.*')
            ->join('applications','applications.user_id','transactions.user_id');
        if(isset($input['first_name']) && $input['first_name'] != '') {
            $data = $data->where('transactions.first_name',  'like', '%' . $input['first_name'] . '%');
        }
        if(isset($input['last_name']) && $input['last_name'] != '') {
            $data = $data->where('transactions.last_name',  'like', '%' . $input['last_name'] . '%');
        }
        if(isset($input['email']) && $input['email'] != '') {
            $data = $data->where('transactions.email',  'like', '%' . $input['email'] . '%');
        }
        if(isset($input['status']) && $input['status'] != '') {
            $data = $data->where('transactions.status', $input['status']);
        }
        if(isset($input['order_id']) && $input['order_id'] != '') {
            $data = $data->where('transactions.order_id', $input['order_id']);
        }
        if(isset($input['company_name']) && $input['company_name'] != '') {
            $data = $data->where('applications.business_name',  'like', '%' . $input['company_name'] . '%');
        }
        if((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d',strtotime($input['start_date']));
            $end_date = date('Y-m-d',strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date.' 00:00:00')
                ->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date.' 00:00:00');
        }
        else if ((isset($input['start_date']) && $input['start_date'] != '') || (isset($input['end_date']) && $input['end_date'] == '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '>=', $start_date . ' 00:00:00');
        }
        else if ((isset($input['start_date']) && $input['start_date'] == '') || (isset($input['end_date']) && $input['end_date'] != '')) {
            $end_date = date('Y-m-d', strtotime($input['end_date']));
            $data = $data->where(DB::raw('DATE(transactions.created_at)'), '<=', $end_date . ' 00:00:00');
        }
        $data = $data->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id)->whereIn('transactions.user_id',$userIds)
                ->where('transactions.is_retrieval', '1')
                ->orderBy('id', 'DESC')->get();
        return $data;
    }

    public function map($data): array
    {
        if($data->card_type == '1')
            $data->card_type = 'Amex';
        elseif($data->card_type == '2')
            $data->card_type = 'Visa';
        elseif($data->card_type == '3')
            $data->card_type = 'Master Card';
        else
            $data->card_type = '';
        if($data->status == '1') {
            $data->status = 'Success';
        } elseif($data->status == '2') {
            $data->status = 'Pending';
        } elseif($data->status == '3') {
            $data->status = 'Canceled';
        } elseif($data->status == '4') {
            $data->status = 'To Be Confirm';
        } else {
            $data->status = 'Declined';
        }

        if($data->is_retrieval == '1')
            $data->is_retrieval = 'Yes';
        else
            $data->is_retrieval = 'No';

        if($data->is_converted == '1')
            $data->amount = $data->amount.'-'.$data->converted_amount;
        elseif($data->is_converted_user_currency == '1')
            $data->amount = $data->amount.'-'.$data->converted_user_amount;
        else
            $data->amount = $data->amount;

        if($data->is_converted == '1')
            $data->currency = $data->currency.'-'.$data->converted_currency;
        elseif($data->is_converted_user_currency == '1')
            $data->currency = $data->currency.'-'.$data->converted_user_currency;
        else
            $data->currency = $data->currency;

        return [
            $data->order_id,
            $data->first_name,
            $data->last_name,
            $data->address,
            $data->customer_order_id,
            $data->country,
            $data->state,
            $data->city,
            $data->zip,
            $data->birth_date,
            $data->email,
            $data->phone_no,
            $data->card_type,
            $data->amount,
            $data->currency,
            substr($data->card_no, 0, 6) . 'XXXXXX' . substr($data->card_no, -4, 4),
            $data->ccExpiryMonth,
            $data->ccExpiryYear,
            $data->status,
            $data->reason,
            $data->is_retrieval,
            $data->retrieval_date,
            $data->created_at->format('d-m-Y h:i:s')
        ];
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'First Name',
            'Last Name',
            'Address',
            'Sulte APT No.',
            'Country',
            'State',
            'City',
            'Zip',
            'Birth Date',
            'Email',
            'Phone No.',
            'Card Type',
            'Amount',
            'Currency',
            'Card No.',
            'Expiry Month',
            'Expiry Year',
            'Status',
            'Reason',
            'Retrieval',
            'Retrieval date',
            'Transaction Date'
        ];
    }
}

