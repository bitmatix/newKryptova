<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent Routes
|--------------------------------------------------------------------------
*/

//Route::group(['middleware' => ['notification_read_agent', 'check_rp_active']], function () {

	// Dashboard controller
	Route::get('dashboard', 'Agent\AgentUserBaseController@dashboard')->name('rp.dashboard');
	Route::get('profile', 'Agent\AgentUserBaseController@profile')->name('profile-rp');
	Route::post('profile-update', 'Agent\AgentUserBaseController@updateProfile')->name('rp-profile-update');
	Route::get('user-management', 'Agent\AgentUserBaseController@getUserManagement')->name('rp.user-management');
	Route::get('user-management/{id}', 'Agent\AgentUserBaseController@show')->name('user-management-show');
	Route::post('user-deactive', 'Agent\AgentUserBaseController@userActiveDeactive')->name('user-deactive-for-rp');

	Route::get('user-management-create', 'Agent\UserManagementController@create')->name('user-management-agent-create');
	Route::post('user-management-store', 'Agent\UserManagementController@store')->name('user-management-agent-store');
	Route::get('user-management-application-show/{id}', 'Agent\UserManagementController@applicationShow')->name('user-management-application-show');
	Route::get('user-management-application-create/{id}', 'Agent\UserManagementController@applicationCreate')->name('user-management-application-create');
	Route::post('user-management-application-store/{id}','Agent\UserManagementController@applicationsStore')->name('user-management-application-store');
	Route::get('user-management-application-edit/{id}', 'Agent\UserManagementController@applicationEdit')->name('user-management-application-edit');
	Route::put('user-management-application-update/{id}', 'Agent\UserManagementController@applicationsUpdate')->name('user-management-application-update');
	Route::get('downloadDocumentsUploadeUser', 'Agent\UserManagementController@downloadDocumentsUploade')->name('downloadDocumentsUploadeUser');
//});