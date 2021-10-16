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
});