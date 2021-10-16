<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
})->name('login');

// Auth Route
Auth::routes();

/*********************User Register Module Start *************************************/
Route::get('registrationform', 'ApplyNowController@index')->name('registrationform');
Route::post('apply-now', 'ApplyNowController@store')->name('applynow-store');
Route::get('user-activate/{id}', 'ApplyNowController@verifyUserEmail')->name('user-activate');
Route::get('user/confirm-mail-active', 'ApplyNowController@confirmMailActive')->name('user/confirm-mail-active');
Route::get('user-email-activate', 'ApplyNowController@verifyUserChangeEmail')->name('user-email-activate');

Route::get('kryptova-otp', 'Auth\LoginController@otpform')->name('kryptova-otp');
Route::get('resend-otp', 'Auth\LoginController@resendotp')->name('resend-otp');
Route::post('kryptova-otp-store', 'Auth\LoginController@checkotp')->name('kryptova-otp-store');

/*********************User Register Module End *************************************/

/*********************Admin Routes Module End *************************************/
Route::get('admin/login', 'Auth\AdminAuthController@getLogin')->name('admin/login');
Route::post('admin/login', 'Auth\AdminAuthController@postLogin')->name('admin/login');
Route::get('admin/kryptova-otp', 'Auth\AdminAuthController@otpform')->name('admin.kryptova-otp');
Route::get('admin/resend-otp', 'Auth\AdminAuthController@resendotp')->name('admin.resend-otp');
Route::post('admin/kryptova-otp-store', 'Auth\AdminAuthController@checkotp')->name('admin.kryptova-otp-store');
Route::get('admin/logout', 'Auth\AdminAuthController@logout')->name('admin/logout');

Route::get('admin/password/reset', 'Auth\AdminAuthController@adminForgetPassword')->name('admin-password-reset');
Route::post('admin/password/email', 'Auth\AdminAuthController@adminForgetEmail')->name('admin-password-email');
Route::get('admin/password/reset/{id}', 'Auth\AdminAuthController@adminForgetPasswordForm')->name('admin-password-reset-form');
Route::post('admin/password/resetForm', 'Auth\AdminAuthController@adminForgetPasswordFormPost')->name('admin-password-resetForm');
Route::get('admin-email-activate', 'AdminController@verifyAdminChangeEmail')->name('admin-email-activate');
// Merchant dashboard login from admin side.
Route::get('/userLogin', 'AdminController@userLoginByAdmin')->name('userLogin');
Route::get('/subUserLogin', 'AdminController@subUserLoginByAdmin')->name('subUserLogin');
// Agent dashboard login from admin side.
Route::get('/agentLogin', 'AdminController@agentLoginByAdmin')->name('agentLogin');
Route::get('/bankLogin', 'AdminController@bankLoginByAdmin')->name('bankLogin');
/*********************Admin Routes Module End *************************************/
