<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Merchant Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'notification_read_user'], function () {

    Route::get('pagenotfound', ['as' => 'notfound', 'uses' => 'HomeController@pagenotfound']);
    Route::group(['middleware' => 'checkUserProfile'], function () {
        Route::get('/dashboard', 'HomeController@home')->name('dashboardPage');
        Route::get('get-user-dashbaord-data', 'HomeController@getDashboardData')->name('get-user-dashbaord-data');
        Route::get('transaction-summary', 'HomeController@transactionSummary')->name('transaction-summary');
        Route::get('transaction-summary-reports', 'HomeController@transactionSummaryReport')->name('transaction-summary-reports');
        Route::post('get-transaction-break-up', 'HomeController@getTransactionBreakUp')->name('get-transaction-break-up');

        /************************Merchant Payout Route Start ***************************************/
        Route::get('payout-report', 'PayoutScheduleController@getPayoutReport')->name('payout-report');
        Route::get('payout-schedule', 'PayoutScheduleController@getPayoutSchedule')->name('payout-schedule');
        Route::get('payout_report/pdf/{id}','Admin\PayoutReportController@generatePDF')->name('payout_report.pdf');
  		Route::get('payout_report/{id}', 'PayoutScheduleController@show')->name('payout_report.show');
        /************************Merchant Payout Route End ***************************************/
        /******************************** MID Details Start **********************************************/
        Route::get('mid-rate', 'MIDDetailsController@index')->name('mid-rate');
        Route::post('mid-rate-agree', 'MIDDetailsController@midRateAgree')->name('mid-rate-agree');
        /******************************** MID Details End **********************************************/
    });






    /******************************* User API Module Start **************************************/    
    Route::get('whitelist-ip', 'UserAPIController@userApiKey')->name('whitelist-ip');
    Route::get('whitelist-ip-add', 'UserAPIController@userApiKeyAdd')->name('whitelist-ip-add');
    Route::post('generate-apy-key', 'UserAPIController@generateAPIKey')->name('generate-apy-key');
    Route::delete('deleteWebsiteUrl/{id}', 'UserAPIController@deleteWebsiteUrl')->name('deleteWebsiteUrl');
    /******************************* User API Module Start **************************************/

    /*********************send firebase device token to database Start******************************/
    Route::get('notifications', 'NotificationController@notifications')->name('notifications');
    Route::get('read-notifications/{id}', 'NotificationController@readNotifications')->name('read-notifications');
    Route::post('send-firebase-token', 'NotificationController@sendFirebaseToken')->name('send-firebase-token');
    Route::post('send-firebase-notification', 'NotificationController@sendFirebaseNotification')->name('send-firebase-notification');
    //****************** send firebase device token to database End *************************//

    //****************** Merchant Profile modules Start *************************//
    Route::get('setting', 'HomeController@profile')->name('setting');
    Route::get('user-bank-details', 'HomeController@userBankdetails')->name('user.bank.details');
    Route::post('update-user-bank-details', 'HomeController@updateUserBankDetail')->name('update.user.bank.details');
    Route::patch('update-user-profile/{id}', 'HomeController@updateProfile')->name('update-user-profile');
    Route::post('user-change-password', 'HomeController@changePass')->name('user-change-password');
    //****************** Merchant Profile modules End *************************//

    //****************** Merchant Tickets modules Start *************************//
    Route::get('ticket', 'TicketController@index')->name('ticket');
    Route::get('get-ticket', 'TicketController@getTickets')->name('get-ticket');
    Route::get('ticket/create', 'TicketController@create')->name('ticket.create');
    Route::post('ticket/store', 'TicketController@store')->name('ticket.store');
    Route::get('ticket/{id}', 'TicketController@show')->name('ticket.show');
    Route::delete('ticket/{id}', 'TicketController@destroy')->name('ticket.destroy');

    //Route::post('ticket/reply','TicketReplyController@store')->name('ticket-reply.store');
    Route::resource('ticket/reply', 'TicketReplyController', ['as' => 'ticket']);
    Route::get('ticket/close/{id}', 'TicketController@close')->name('ticket.close');
    Route::get('ticket/reopen/{id}', 'TicketController@reopen')->name('ticket.reopen');

    Route::get('ticket/download/{id}/{number}', 'TicketController@downloadTicketFiles')->name('downloadTicketFiles.user');
    Route::get('ticket/reply-download/{id}/{number}', 'TicketReplyController@downloadTicketReplyFiles')->name('downloadTicketReplyFiles.user');
    //****************** Merchant Tickets modules End *************************//

    //****************** Merchant Application modules Start *************************//
    // Application controller
    Route::get('start-my-application', 'ApplicationController@index')->name('start-my-application');
    Route::post('start-my-application-store', 'ApplicationController@startApplicationStore')->name('start-my-application-store');
    Route::get('my-application', 'ApplicationController@status')->name('my-application');
    Route::get('edit-my-application/{id}', 'ApplicationController@applicationsEdit')->name('edit-my-application');
    Route::put('applications-update/{id}', 'ApplicationController@applicationsUpdate')->name('applications-update');
    Route::get('downloadDocumentsUploadeUser', 'ApplicationController@downloadDocumentsUploade')->name('downloadDocumentsUploadeUser');
    Route::get('viewAppImage', 'ApplicationController@viewAppImage')->name('viewAppImage');

    // Access routes without login
    Route::group(['excluded_middleware' => ['auth']], function () {
        // Articles route
        Route::get('articles/{id?}', 'ArticleController@index')->name('articles');
        Route::get('articles/view/{slug}', 'ArticleController@view');

        // Admin route
        Route::get('save-local-timezone', 'AdminController@saveLocalTimezone');
    });

    //****************** Merchant Application modules End *************************//


    Route::group(['middleware' => 'authcheck'], function () {

        Route::group(['middleware' => 'trim'], function () {
            Route::get('transactions', 'TransactionsController@index')->name('gettransactions');
            //Refund
            Route::get('refunds', 'TransactionsController@refunds')->name('refunds');
            //Chargebacks
            Route::get('chargebacks', 'TransactionsController@chargebacks')->name('chargebacks');
            // Flagged
            Route::get('suspicious', 'TransactionsController@flagged')->name('suspicious');
            // Retrieval
            Route::get('retrieval', 'TransactionsController@retrieval')->name('retrieval');
            //Test Transactions
            Route::get('test-transactions', 'TransactionsController@testTransactions')->name('getTestTransactions');
        });

        //****************** Merchant Transactions modules Start *************************//

        Route::post('transactions/export', 'TransactionsController@exportAllTransactions')->name('transactions.exportAllTransactions');

        Route::post('refunds/export', 'TransactionsController@exportRefunds')->name('refunds.export');

        Route::post('chargebacks/export', 'TransactionsController@exportChargebacks')->name('chargebacks.export');
        Route::post('chargebacks-showdocument', 'TransactionsController@showDocumentChargebacks')->name('chargebacks-showdocument');
        Route::get('chargebacks-document-delete/{id}', 'TransactionsController@deleteDocumentChargebacks')->name('chargebacks-document-delete');

        Route::post('flagged/export', 'TransactionsController@exportFlagged')->name('flagged.export');
        Route::post('flagged-showdocument', 'TransactionsController@showDocumentFlagged')->name('flagged-showdocument');
        Route::get('flagged-document-delete/{id}', 'TransactionsController@deleteDocumentFlagged')->name('flagged-document-delete');
        Route::get('transactions/{id}', 'TransactionsController@show')->name('transaction.show');
        Route::post('merchant-transactions-details', 'TransactionsController@transactionDetails')->name('merchant-transactions-details');

        Route::post('test-transactions/export', 'TransactionsController@exportTestTransactions')->name('testTransactions.export');

        Route::post('retrieval/export', 'TransactionsController@exportRetrieval')->name('retrieval.export');
        Route::post('retrieval-showdocument', 'TransactionsController@showDocumentRetrieval')->name('retrieval-showdocument');
        Route::get('retrieval-document-delete/{id}', 'TransactionsController@deleteDocumentRetrieval')->name('retrieval-document-delete');
        Route::post('transactions-refund', 'TransactionsController@refund')->name('transactions-refund');
        Route::post('transactions-sendmail', 'TransactionsController@sendmail')->name('transactions-sendmail');
        //****************** Merchant Transactions modules End *************************//

        //****************** Merchant Sub Users modules Start *************************//
        Route::get('user-management', 'SubUsersController@index')->name('user-management');
        Route::get('user-management/create', 'SubUsersController@create')->name('user-management.create');
        Route::post('user-management/store', 'SubUsersController@store')->name('user-management.store');
        Route::get('user-management/edit/{id}', 'SubUsersController@edit')->name('user-management.edit');
        Route::patch('user-management/update/{id}', 'SubUsersController@update')->name('user-management.update');
        Route::delete('user-management-delete/{id}', 'SubUsersController@delete')->name('user-management.delete');
        //****************** Merchant Sub Users modules End *************************//

        //****************** iframe generate modules Start *************************//
        Route::get('iframe', 'UserIframeController@index')->name('iframe');
        Route::post('iframe/generate', 'UserIframeController@createIframe')->name('iframe.generate');
        //****************** iframe generate modules End *************************//

        Route::get('resend/profile', 'HomeController@resendEmailProfile')->name('resend.profile');
    });
});
