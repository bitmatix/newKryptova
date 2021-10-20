<?php

namespace App\Exports;

use DB;
use App\User;
use App\Application;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class DeletedApplicationsExport implements FromCollection, WithHeadings
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    use Exportable;
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $input = request()->all();
        $data = Application::select(
            'users.name',
            'users.email',
            'applications.business_type',
            'applications.accept_card',
            'applications.business_name',
            'applications.website_url',
            'applications.business_contact_first_name',
            'applications.business_contact_last_name',
            'applications.business_address1',
            'applications.country',
            'applications.phone_no',
            'applications.skype_id',
            'applications.processing_currency',
            'agents.name as agentsName',
            'users.agent_commission'
        )
            ->join('users', 'users.id', 'applications.user_id')
            ->leftjoin('agents', 'agents.id', 'users.agent_id');


        if (!is_null($this->id)) {
            $data = $data->whereIn('users.id', $this->id);
        }

        if (isset($input['country']) && $input['country'] != '') {
            $data = $data->where('applications.country', $input['country']);
        }

        if (isset($input['website_url']) && $input['website_url'] != '') {
            $data = $data->where('applications.website_url',  'like', '%' . $input['website_url'] . '%');
        }

        if (isset($input['name']) && $input['name'] != '') {
            $data = $data->where('users.name',  'like', '%' . $input['name'] . '%');
        }

        if (isset($input['email']) && $input['email'] != '') {
            $data = $data->where('users.email',  'like', '%' . $input['email'] . '%');
        }

        if (isset($input['user_id']) && $input['user_id'] != '') {
            $data = $data->where('applications.user_id', $input['user_id']);
        }

        if (isset($input['technology_partner_id']) && $input['technology_partner_id'] != '') {
            $data = $data->where('applications.technology_partner_id', $input['technology_partner_id']);
        }

        if (isset($input['agent_id']) && $input['agent_id'] != '') {
            if ($input['agent_id'] == 'no-agent') {
                $data = $data->where('users.agent_id', NULL);
            } else {
                $data = $data->where('users.agent_id', $input['agent_id']);
            }
        }

        if ((isset($input['start_date']) && $input['start_date'] != '') &&  (isset($input['end_date']) && $input['end_date'] != '')) {
            $start_date = date('Y-m-d', strtotime($input['start_date']));
            $end_date = date('Y-m-d', strtotime($input['end_date']));

            $data = $data->where(DB::raw('DATE(applications.created_at)'), '>=', $start_date . ' 00:00:00')
                ->where(DB::raw('DATE(applications.created_at)'), '<=', $end_date . ' 00:00:00');
        }

        $data = $data->onlyTrashed()->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Email',
            'Business Type',
            'Accept Credit Or Debit Cards',
            'Business Name',
            'Website URL',
            'Business Contact First Name',
            'Business Contact Last Name',
            'Business Address',
            'Country',
            'Phone Number',
            'Skype ID',
            'Processing Currency',
            'Agent Name',
            'Percentage',
        ];
    }
}
