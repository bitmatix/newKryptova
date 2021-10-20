<?php

use Illuminate\Database\Seeder;
use App\RequiredFields;
class RequiredFieldsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('required_fields')->insert(array(
	     	array(
		       'field_title' => 'First name',
		       'field' => 'first_name',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Last name',
		       'field' => 'last_name',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Email',
		       'field' => 'email',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Address',
		       'field' => 'address',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Customer Order id',
		       'field' => 'customer_order_id',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Country',
		       'field' => 'country',
		       'field_type' => 'string',
		       'field_validation' => 'required|max:2|min:2|regex:(\\b[A-Z]+\\b)',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'State',
		       'field' => 'state',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'City',
		       'field' => 'city',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Zip',
		       'field' => 'zip',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Ip address',
		       'field' => 'ip_address',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Phone No',
		       'field' => 'phone_no',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Amount',
		       'field' => 'amount',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Currency',
		       'field' => 'currency',
		       'field_type' => 'string',
		       'field_validation' => 'required|max:3|min:3|regex:(\\b[A-Z]+\\b)',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Card No',
		       'field' => 'card_no',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'ccexpiry month',
		       'field' => 'ccexpiry_month',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'ccexpiry year',
		       'field' => 'ccexpiry_year',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'cvv number',
		       'field' => 'cvv_number',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	     	array(
		       'field_title' => 'Response url',
		       'field' => 'response_url',
		       'field_type' => 'string',
		       'field_validation' => 'required',
		       'created_at' => date('Y-m-d H:i:s'),
		       'updated_at' => date('Y-m-d H:i:s')
	     	),
	   	));
    }
}
