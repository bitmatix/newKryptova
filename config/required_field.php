<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Required fields
    |--------------------------------------------------------------------------
    |
    | These fields are visible in MID gateway input
    |
    */
    'total_fields' => [

        'api_key' => [
            'type' => 'string',
            'validate' => 'required'
        ],

        'first_name' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'last_name' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'address' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

		'customer_order_id' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'country' => [
        	'type' => 'string',
        	'validate' => 'required|max:2|min:2|regex:(\b[A-Z]+\b)'
        ],

        'state' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'city' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'zip' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'ip_address' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'email' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'phone_no' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'amount' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'currency' => [
        	'type' => 'string',
        	'validate' => 'required|max:3|min:3|regex:(\b[A-Z]+\b)'
        ],

        'card_no' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'ccExpiryMonth' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'ccExpiryYear' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'cvvNumber' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

        'response_url' => [
        	'type' => 'string',
        	'validate' => 'required'
        ],

    ],

    'required_all_fields' => [
        'api_key',
        'user_id',
        'order_id',
        'first_name',
        'last_name',
        'address',
        'customer_order_id',
        'country',
        'state',
        'city',
        'zip',
        'ip_address',
        'email',
        'phone_no',
        'card_type',
        'amount',
        'currency',
        'card_no',
        'ccExpiryMonth',
        'ccExpiryYear',
        'cvvNumber',
        'payment_gateway_id',
        'descriptor',
        'is_converted',
        'converted_amount',
        'converted_currency',
        'is_converted_user_currency',
        'converted_user_amount',
        'converted_user_currency',
        'website_url_id',
        'request_from_ip',
        'request_origin',
        'is_request_from_vt',
        'response_url',
        'is_transaction_type',
        'webhook_url',
        'payment_type',
        'request_from_type',
        'token',
        'country_code',
        'card_token'
    ],
];
