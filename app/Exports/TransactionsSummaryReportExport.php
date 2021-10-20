<?php

namespace App\Exports;

use DB;
use App\TxTransaction;
use App\Transaction;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class TransactionsSummaryReportExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $input = request()->all();

        $slave_connection = env('SLAVE_DB_CONNECTION_NAME', '');

        if (!empty($slave_connection)) {
            \DB::setDefaultConnection($slave_connection);
            $getDatabaseName = \DB::connection()->getDatabaseName();
            _WriteLogsInFile($getDatabaseName . " connection from admin transaction summary report", 'slave_connection');
        }

        $payment_gateway_id = (env('PAYMENT_GATEWAY_ID')) ? explode(",", env('PAYMENT_GATEWAY_ID')) : [];

        $data = Transaction::select(
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

        if((isset($input['start_date']) && $input['start_date'] != '') && (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d 00:00:00',strtotime($input['start_date']));
            $end_date = date('Y-m-d 23:59:59',strtotime($input['end_date']));

            $data= $data->where('transactions.transaction_date', '>=', $start_date)
                ->where('transactions.transaction_date', '<=', $end_date);
        }

        if((!isset($input['for']) && !isset($input['currency']) && !isset($input['start_date']) && !isset($input['end_date']) && !isset($input['user_id'])) || (isset($input['for']) && $input['for'] == 'Daily')) {
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
       
        $data = $data->groupBy('currency')->orderBy('success_amount', 'desc')->get();
        return $data;
    }

	public function map($data): array
    {  
        $data = $data->toArray();
        $data['success_percentage'] = (string) round($data['success_percentage'], 2);
        $data['declined_percentage'] = (string) round($data['declined_percentage'], 2);
        $data['retrieval_percentage'] = (string) round($data['retrieval_percentage'], 2);
        $data['refund_percentage'] = (string) round($data['refund_percentage'], 2);
        $data['chargebacks_percentage'] = (string) round($data['chargebacks_percentage'], 2);
        $data['flagged_percentage'] = (string) round($data['flagged_percentage'], 2);
        $data['block_percentage'] = (string) round($data['block_percentage'], 2);
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'Currency',
            'Success Count',
            'Success Amount',
            'Success Percentage',
            'Declined Count',
            'Declined Amount',
            'Declined Percentage',
            'Chargebacks Count',
            'Chargebacks Amount',
            'Chargebacks Percentage',
            'Refund Count',
            'Refund Amount',
            'Refund Percentage',
            'Suspicious Count',
            'Suspicious Amount',
            'Suspicious Percentage',
            'Retrieval Count',
            'Retrieval Amount',
            'Retrieval Percentage'
        ];
    }
}
