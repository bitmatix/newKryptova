<?php

namespace App\Exports;

use DB;
use App\User;
use App\Application;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class AllApplicationsForBankExport implements FromCollection, WithHeadings
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
	            'applications.processing_country',
	        )
            ->join('users', 'users.id', 'applications.user_id')
            ->join('application_assign_to_banks','applications.id','=','application_assign_to_banks.application_id')
            ->where('application_assign_to_banks.bank_user_id', auth()->guard('bankUser')->user()->id);

        if (!empty($this->id)) {
            $data = $data->whereIn('users.id', $this->id);
        }

        if(isset($input['category_id']) && $input['category_id'] != '') {
            $data = $data->where('applications.category_id',$input['category_id']);
        }

        if(isset($input['user_id']) && $input['user_id'] != '') {
            $data = $data->where('applications.user_id',$input['user_id']);
        }

        if(isset($input['status']) && $input['status'] != '') {
            $data = $data->where('application_assign_to_banks.status',$input['status']);
        }

        $data = $data->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Email',
            'Business Category',
            'Accepted Payment Methods',
            'Company Name',
            'Website URL',
            'First Name',
            'Last Name',
            'Company Address',
            'Country Of Incorporation',
            'Phone Number',
            'Contact Details',
            'Processing Currency',
            'Processing Country',
        ];
    }
}
