<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
  |--------------------------------------------------------------------------
  | API Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register API routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | is assigned the "api" middleware group. Enjoy building your API!
  |
 */
Route::any('getCityList', 'AppauthController@getCityList');
Route::any('getAreaList', 'AppauthController@getAreaList');
Route::post('resendOTP', 'AppauthController@resendOTP');
Route::post('getIdType', 'AppauthController@getIdType');
Route::post('kycUpdate', 'AppauthController@kycUpdate');
Route::post('updateKycTwo', 'AppauthController@updateKycTwo');
Route::post('staticPage', 'AppauthController@staticPage');
Route::post('saveerror', 'AppauthController@saveerror');
Route::post('updateAES', 'AppauthController@updateAES');
Route::any('logout', 'AppauthController@logout');

Route::group([
    'prefix' => 'auth'
        ], function () {
    Route::post('isEmailExist', 'AppauthController@isEmailExist');
    Route::post('isMobileExists', 'AppauthController@isMobileExists');
    Route::post('getCountryList', 'AppauthController@getCountryList');

    Route::post('signup', 'AppauthController@signup');
    Route::post('verifyOTP', 'AppauthController@verifyOTP');
    Route::post('login', 'AppauthController@login');
    Route::post('forgotPassword', 'AppauthController@forgotPassword');
    Route::post('resetPassword', 'AppauthController@resetPassword');
    Route::post('changePassword', 'AppauthController@changePassword');
    Route::post('updateProfile', 'AppauthController@updateProfile');
    
    Route::group([
        'middleware' => 'auth:api'
            ], function() {
        Route::post('addCard', 'AppauthController@addCard');
        Route::post('getCard', 'AppauthController@getCard');
        Route::post('removeCard', 'AppauthController@removeCard');
        Route::post('addAccount', 'AppauthController@addAccount');
        Route::post('getAccount', 'AppauthController@getAccount');
        Route::post('removeAccount', 'AppauthController@removeAccount');
        Route::post('getUserByMobile', 'AppauthController@getUserByMobile');
        Route::post('depositByCard', 'AppauthController@depositByCard');
        Route::post('depositByAgent', 'AppauthController@depositByAgent');
        Route::post('withdrawByAgent', 'AppauthController@withdrawByAgent');
        Route::post('selectMobileCard', 'AppauthController@selectMobileCard');
        Route::post('buyMobileCard', 'AppauthController@buyMobileCard');
        Route::post('mobileCardList', 'AppauthController@mobileCardList');
        Route::post('selectOnlineCard', 'AppauthController@selectOnlineCard');
        Route::post('buyOnlineCard', 'AppauthController@buyOnlineCard');
        Route::post('onlineCardList', 'AppauthController@onlineCardList');
        Route::post('selectInternetCard', 'AppauthController@selectInternetCard');
        Route::post('buyInternetCard', 'AppauthController@buyInternetCard');
        Route::post('internetCardList', 'AppauthController@internetCardList');
        Route::post('nearByUser', 'AppauthController@nearByUser');
        Route::post('generateQR', 'AppauthController@generateQR');
        Route::post('merchantList', 'AppauthController@merchantList');
        Route::post('getMerchantByQR', 'AppauthController@getMerchantByQR');
        Route::post('getAgentByQR', 'AppauthController@getAgentByQR');
        Route::post('shopPayment', 'AppauthController@shopPayment');
//        Route::post('updateProfile', 'AppauthController@updateProfile');
        Route::post('userHome', 'AppauthController@user_home');
        Route::post('updateLatLng', 'AppauthController@updateLatLng');
        Route::post('fundTransfer', 'AppauthController@fundTransfer');
        Route::post('myTransactions', 'AppauthController@myTransactions');
        Route::post('getNotification', 'AppauthController@getNotification');
        Route::post('seenNotification', 'AppauthController@seenNotification');
        Route::post('getUserByQR', 'AppauthController@getUserByQR');
        Route::post('scanMerchantQR', 'AppauthController@scanMerchantQR');
        Route::post('shoppingPayment', 'AppauthController@shoppingPayment');
        
        Route::post('requestList', 'AppauthController@requestList');
        Route::post('cancelAcceptRequest', 'AppauthController@cancelAcceptRequest');
        
        Route::post('buyCashCard', 'AppauthController@buyCashCard');
        Route::post('cashCardList', 'AppauthController@cashCardList');
        
        Route::post('merchantTransactions', 'AppauthController@merchantTransactions');
        Route::post('refundPayment', 'AppauthController@refundPayment');
        Route::post('sendRefund', 'AppauthController@sendRefund');
        
        Route::post('checkTransactionFee', 'AppauthController@checkTransactionFee');
        Route::post('checkOnlineShopping', 'AppauthController@checkOnlineShopping');
        
        Route::post('feedback', 'AppauthController@feedback');
        Route::post('merchantSetting', 'AppauthController@merchantSetting');


        
        /* Route::get('logout', 'AppauthController@logout');
          Route::get('user', 'AppauthController@user'); */
    });
});

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
