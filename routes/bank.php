<?php


use Illuminate\Support\Facades\Route;





Route::get('application-view/{id}', 'BankFrontController@applicationView')->name('bank-application-view');
//Route::get('bank/applications', ['uses' => 'BankFrontController@applicationList', 'as' => 'bank.applications.list']);
// Route::get('application-status/{id}', 'BankFrontController@changeStatus')->name('bank-application-status');
Route::post('bank-application-status', 'BankFrontController@changeStatus')->name('bank-application-status');
Route::post('application-status', 'BankFrontController@changeRejectStatus')->name('bank-application-rejectstatus');
Route::get('downloadDocumentsUploadeBank', 'BankFrontController@downloadDocumentsUploade')->name('downloadDocumentsUploadeBank');

Route::post('bank-get-application-note', 'BankFrontController@getApplicationNote')->name('bank-get-application-note');
Route::post('bank-store-application-note', 'BankFrontController@storeApplicationNote')->name('bank-store-application-note');
