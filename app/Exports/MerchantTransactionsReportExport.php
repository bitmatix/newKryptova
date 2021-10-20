<?php

namespace App\Exports;

use DB;
use App\Transaction;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class MerchantTransactionsReportExport implements FromCollection, WithHeadings, WithMapping
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
            _WriteLogsInFile('Start slave connection', 'slave_connection');
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

        if((!isset($input['for']) && !isset($input['currency']) && !isset($input['start_date']) && !isset($input['end_date']) && !isset($input['user_id']) && !isset($input['success_per']) && !isset($input['decline_per']) && !isset($input['chargebacks_per']) && !isset($input['refund_per']) && !isset($input['suspicious_per']) && !isset($input['retrieval_per']) && !isset($input['block_per'])) || (isset($input['for']) && $input['for'] == 'Daily')) {

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

        $data = $data->groupBy('transactions.user_id', 'transactions.currency')->orderBy('success_amount', 'desc')->get();        
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
            'Business Name',
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
            'Retrieval Percentage',
            'Block Count',
            'Block Amount',
            'Block Percentage',
        ];
    }
}
