<?php

namespace App\Exports;

use DB;
use App\Transaction;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class PaymentStatusReportExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $input = request()->all();
        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');

        if(!empty($slave_connection))
        {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin payment status report", 'slave_connection');
        }
        
        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        $data = Transaction::select(
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
            DB::raw("(SUM(IF(transactions.status = '5', 1, 0))/SUM(IF(transactions.status = '1', 1, 0))) AS block_percentage")
        )->leftJoin('applications', 'applications.user_id', '=', 'transactions.user_id')->whereNotIn('transactions.payment_gateway_id', $payment_gateway_id);

        if (isset($input['user_id']) && $input['user_id'] != null) {
            $data = $data->where('transactions.user_id', $input['user_id']);
        }

        if (isset($input['currency']) && $input['currency'] != null) {
            $data = $data->where('transactions.currency', $input['currency']);
        }

        if((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d 00:00:00',strtotime($input['start_date']));
            $end_date = date('Y-m-d 23:59:59',strtotime($input['end_date']));

            $data= $data->where('transactions.transaction_date', '>=', $start_date)
                ->where('transactions.transaction_date', '<=', $end_date);
        }

        if((!isset($input['for']) && !isset($input['currency']) && !isset($input['start_date']) && !isset($input['end_date']) && !isset($input['user_id']) && !isset($input['status'])) || (isset($input['for']) && $input['for'] == 'Daily')) {

            $data= $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00'))
            ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        if(isset($input['for']) && $input['for'] == 'Weekly'){ 
            $data= $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00', strtotime('-6 days')))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        if(isset($input['for']) && $input['for'] == 'Monthly'){ 
            $data= $data->where('transactions.transaction_date', '>=', date('Y-m-d 00:00:00', strtotime('-30 days')))
                ->where('transactions.transaction_date', '<=', date('Y-m-d 23:59:59'));
        }

        $data = $data->groupBy('transactions.user_id', 'transactions.currency')->orderBy('success_amount', 'desc')->get();        
        return $data;
    }

	public function map($data): array
    {
        $input = request()->all();
        $data->toArray();
        
        $_data['currency'] = $data['currency'];
        $_data['business_name'] = $data['business_name'];

        if($_GET['status'] == 1)
        {
            $_data['success_count'] = $data['success_count'];
            $_data['success_amount'] = $data['success_amount'];
            $_data['success_percentage'] = $data['success_percentage'];

        } else if($_GET['status'] == 2){
            $_data['declined_count'] = $data['declined_count'];
            $_data['declined_amount'] = $data['declined_amount'];
            $_data['declined_percentage'] = $data['declined_percentage'];
        } else if($_GET['status'] == 3){
            $_data['chargebacks_count'] = $data['chargebacks_count'];
            $_data['chargebacks_amount'] = $data['chargebacks_amount'];
            $_data['chargebacks_percentage'] = $data['chargebacks_percentage'];
        } else if($_GET['status'] == 4){
            $_data['refund_count'] = $data['refund_count'];
            $_data['refund_amount'] = $data['refund_amount'];
            $_data['refund_percentage'] = $data['refund_percentage'];
        } else if($_GET['status'] == 5){
            $_data['flagged_count'] = $data['flagged_count'];
            $_data['flagged_amount'] = $data['flagged_amount'];
            $_data['flagged_percentage'] = $data['flagged_percentage'];
        } else if($_GET['status'] == 6){
            $_data['retrieval_count'] = $data['retrieval_count'];
            $_data['retrieval_amount'] = $data['retrieval_amount'];
            $_data['retrieval_percentage'] = $data['retrieval_percentage'];
        } else if($_GET['status'] == 7){
            $_data['block_count'] = $data['block_count'];
            $_data['block_amount'] = $data['block_amount'];
            $_data['block_percentage'] = $data['block_percentage'];
        }
        return $_data;
    }

    public function headings(): array
    {
        $input = request()->all();

        if($_GET['status'] == 1)
        {
            $column = [
                'Currency',
                'Business Name',
                'Success Count',
                'Success Amount',
                'Success Percentage'
            ];
        } else if($_GET['status'] == 2){
            $column = [
                'Currency',
                'Business Name',
                'Declined Count',
                'Declined Amount',
                'Declined Percentage',
            ];
        } else if($_GET['status'] == 3){
            $column = [
                'Currency',
                'Business Name',
                'Chargebacks Count',
                'Chargebacks Amount',
                'Chargebacks Percentage'
            ];

        } else if($_GET['status'] == 4){
            $column = [
                'Currency',
                'Business Name',
                'Refund Count',
                'Refund Amount',
                'Refund Percentage'
            ];
        } else if($_GET['status'] == 5){
            $column = [
                'Currency',
                'Business Name',
                'Suspicious Count',
                'Suspicious Amount',
                'Suspicious Percentage',
            ];
        } else if($_GET['status'] == 6){
            $column = [
                'Currency',
                'Business Name',
                'Retrieval Count',
                'Retrieval Amount',
                'Retrieval Percentage'
            ];
        } else if($_GET['status'] == 7){
            $column = [
                'Currency',
                'Business Name',
                'Block Count',
                'Block Amount',
                'Block Percentage',
            ];
        }
        return $column;
    }
}
