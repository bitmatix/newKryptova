<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
  return redirect('admin/dashboard');
})->name('admin.dashboard');

Route::group(['middleware' => 'notification_read'], function () {
	/**************Admin Dashboard module Start ****************************/
	Route::get('dashboard', 'AdminController@dashboard')->name('dashboard');
	Route::post('dashboard/transaction-summary', 'AdminController@transactionSummaryFilter')->name('dashboard.transactionSummary');
	/**************Admin Dashboard module End   ****************************/

	Route::get('technical', 'Admin\AdminsController@technical')->name('admin.technical');

	/************************Profile Details Route Start **********************/
  	Route::get('profile', 'AdminController@profile')->name('admin-profile');
	Route::post('user-change-pass', 'AdminController@changePass')->name('user-change-pass');
	Route::patch('update-profile/{id}', 'AdminController@updateProfile')->name('update-profile');
	/************************Profile Details Route End **********************/

	/**************** User Management Resources start ******************/
  	Route::get('users-management', 'Admin\UserManagementController@index')->name('users-management');
  	Route::post('users-management/export', 'Admin\UserManagementController@export')->name('users-management.export');
  	Route::post('show-user-details', 'Admin\UserManagementController@showUserDetails')->name('show-user-details');
  	Route::post('change-password', 'Admin\UserManagementController@changePassword')->name('change-password');
  	Route::get('merchant-user-masstransactions', 'Admin\UserManagementController@massremove')->name('merchant-user-masstransactions');
  	Route::get('merchant-user-create', 'Admin\UserManagementController@merchantUserCreate')->name('merchant-user-create');
  	Route::post('merchant-user-store', 'Admin\UserManagementController@merchantUserStore')->name('merchant-user-store');
  	Route::get('merchant-user-edit/{id}', 'Admin\UserManagementController@merchantUserEdit')->name('merchant-user-edit');
  	Route::put('merchant-user-update/{id}', 'Admin\UserManagementController@merchantUserUpdate')->name('merchant-user-update');
  	Route::get('merchant-user/bank-details/{id}', 'Admin\UserManagementController@getUserBankDetails')->name('admin.merchant.bankDetails');

  	Route::get('assign-mid/{id}', 'Admin\UserManagementController@assignMID')->name('assign-mid');
  	Route::post('assign-mid', 'Admin\UserManagementController@assignMIDStore')->name('assign-mid-store');
  	Route::get('card-email-limit/{id}', 'Admin\UserManagementController@cardEmailLimit')->name('card-email-limit');
  	Route::get('merchant-rate-fee/{id}', 'Admin\UserManagementController@merchantRateFee')->name('merchant-rate-fee');
  	Route::get('additional-mail/{id}', 'Admin\UserManagementController@additionalMail')->name('additional-mail');
  	Route::get('merchant-rules/{id}', 'Admin\UserManagementController@merchantRules')->name('merchant-rules');
  	Route::get('personal-info/{id}', 'Admin\UserManagementController@merchantPersonalInfo')->name('personal-info');

  	Route::get('sub-user/{id}', 'Admin\UserManagementController@subUser')->name('sub-user');
  	Route::get('sub-users-management', 'Admin\UserManagementController@subUsersMngt')->name('sub-users-management');
  	Route::get('sub-users-edit/{id}', 'Admin\UserManagementController@subUserEdit')->name('sub-users-edit');
  	Route::get('sub-users-list-edit/{id}', 'Admin\UserManagementController@subUserListEdit')->name('sub-users-list-edit');
  	Route::patch('sub-users-update/{id}', 'Admin\UserManagementController@subUserUpdate')->name('sub-users-update');
  	Route::patch('sub-users-list-update/{id}', 'Admin\UserManagementController@subUserListUpdate')->name('sub-users-list-update');
  	Route::delete('sub-users-delete/{id}', 'Admin\UserManagementController@subUserDelete')->name('sub-users-delete');
  	Route::delete('users-management/{id}', 'Admin\UserManagementController@destroy')->name('users-management-delete');
  	Route::get('merchant-sub-user-masstransactions', 'Admin\UserManagementController@massremoveSubUser')->name('merchant-sub-user-masstransactions');

  	Route::post('send-user-multi-mail', 'Admin\UserManagementController@sendMultiMail')->name('send-user-multi-mail');
  	Route::post('get-user-total-amount', 'Admin\UserManagementController@getUserTotalAmount')->name('get-user-total-amount');
  	Route::post('user-set-agent', 'Admin\UserManagementController@setAgent')->name('user-set-agent');
  	Route::post('user-deactive', 'Admin\UserManagementController@userActiveDeactive')->name('user-deactive');
  	Route::post('user-otp-required', 'Admin\UserManagementController@userOTPRequired')->name('user-otp-required');
  	Route::post('user-ip-remove', 'Admin\UserManagementController@userIPRemove')->name('user-ip-remove');
  	Route::post('user-disable-rules', 'Admin\UserManagementController@userDisableRule')->name('user-disable-rules');
  	Route::get('send-password/{id}', 'Admin\UserManagementController@sendPassword')->name('send-password');
  	Route::get('user-otp-reset/{id}', 'Admin\UserManagementController@userOtpReset')->name('user-otp-reset');
  	Route::post('make-refund-status', 'Admin\UserManagementController@makeRefundStatus')->name('make-refund-status');
  	Route::post('make-active-status', 'Admin\UserManagementController@makeActiveStatus')->name('make-active-status');
  	Route::get('api-key-generate/{id}', 'Admin\UserManagementController@apiKeyGenerate')->name('api-key-generate');
  	Route::get('get-template-data', 'Admin\UserManagementController@getTemplateData')->name('get-template-data');
  	/**************** User Management Resources End  ******************/

  	/****************Admin Ticket module Start ***********************************/
  Route::get('ticket', 'Admin\TicketController@index')->name('admin.ticket');
  Route::get('ticket/{id}', 'Admin\TicketController@show')->name('admin.ticket.show');
  Route::get('ticket/close/{id}', 'Admin\TicketController@close')->name('admin.ticket.close');
  Route::get('ticket/reopen/{id}', 'Admin\TicketController@reopen')->name('admin.ticket.reopen');
  Route::delete('ticket/{id}', 'Admin\TicketController@destroy')->name('admin.ticket.destroy');


  Route::resource('ticket/reply', 'Admin\TicketReplyController', ['as' => 'admin.ticket']);
  //Route::post('ticket/reply','Admin\TicketReplyController@store')->name('admin.ticket-reply.store');
  /****************Admin Ticket module End ***********************************/

});