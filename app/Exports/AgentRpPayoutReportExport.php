<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class AgentRpPayoutReportExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithCustomValueBinder, WithMapping
{

    protected $id;
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() == 'A') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        if ($cell->getColumn() == 'J') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        if(auth()->guard('agentUser')->user()->main_agent_id == 0){
            $agentId = auth()->guard('agentUser')->user()->id;
        }else{
            $agentId = auth()->guard('agentUser')->user()->main_agent_id;
        }

        $data = DB::table("agent_payout_reports")->where('show_agent_side', '1')->where('agent_id', $agentId);

        if (!is_null($this->id)) {
            $data = $data->whereIn('agent_payout_reports.id', $this->id);
        }

        $data = $data->orderBy("agent_payout_reports.id", "DESC")
            ->whereNull("agent_payout_reports.deleted_at")
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'Report Number',
            'Agent Id',
            'Agent Name',
            'Company Name',
            'Generated Date',
            'Start Date',
            'End Date',
            'Make Paid',
            'Show Client Side',
            'Created At'
        ];
    }

    public function map($report): array
    {
        if ($report->is_paid == '1') {
            $report->is_paid = 'Paid';
        } else {
            $report->is_paid = 'Not Paid';
        }
        if ($report->show_agent_side == '1') {
            $report->show_agent_side = 'True';
        } else {
            $report->show_agent_side = 'False';
        }

        return [
            $report->report_no,
            $report->agent_id,
            $report->agent_name,
            $report->company_name,
            $report->date,
            $report->start_date,
            $report->end_date,
            $report->is_paid,
            $report->show_agent_side,
            convertDateToLocal($report->created_at, 'd-m-Y H:i:s'),
        ];
    }
}
