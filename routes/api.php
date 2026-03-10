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
Route::middleware('throttle:1000,1')->group(function () {
Route::any('getWalkthroughList', 'AuthController@getWalkthroughList');
Route::post('staticPage', 'AuthController@staticPage');
Route::any('checkKycStatus', 'AuthController@checkKycStatus');
Route::post('bankTransfer', 'ThirdPartyApiController@bankTransfer');
Route::post('walletTransfer', 'ThirdPartyApiController@walletTransfer');
Route::post('getTransactionStatus', 'ThirdPartyApiController@getTransactionStatus');
Route::post('bdaTransfer', 'ThirdPartyApiController@bdaTransfer');
Route::post('bdaTransferTest', 'ThirdPartyApiController@bdaTransferTest');
Route::get('bdaTransferStatus', 'ThirdPartyApiController@bdaTransferStatus');
Route::post('remittance',  'AuthController@processRemittance');
Route::post('accountRequest',  'AuthController@accountRequest');



Route::post('checkConnection',  'CardController@checkConnection');
Route::any('registerVirtualCard',  'CardController@registerVirtualCard');
Route::get('getCountryListNew', 'AuthController@getCountryListNew');

Route::get('provinceList', 'AuthController@provinceList');
Route::post('provinceCity', 'AuthController@provinceCity');
Route::post('provinceDistrict', 'AuthController@provinceDistrict');
Route::get('getCardContent', 'AuthController@getCardContent');

Route::get('getAppInfo', 'AuthController@getAppInfo');
Route::post('getStateListNew', 'AuthController@getStateListNew');
Route::get('smileidCallback','AuthController@handleCallback');
Route::any('getReqInfo','AuthController@getReqInfo');

// Route::get('checkVerifyKycStatus','AuthController@checkVerifyKycStatus');
Route::middleware('verify.api.token')->group(function () {
Route::any('checkAccountInquiry','AuthController@checkAccountInquiry');
Route::any('getSendReqInfo','AuthController@getSendReqInfo');
Route::post('checkPaymentStatus','AuthController@checkPaymentStatus');
});
Route::post('initAirtelPayment','AuthController@initAirtelPayment');
Route::post('refundAirtelPayment','AuthController@refundAirtelPayment');
Route::post('transCallback','AuthController@transCallback');

Route::group([
    'prefix' => 'auth'
], function () {
    
    Route::post('checkCurl',  'AuthController@checkCurl');
    Route::post('verifyOTP', 'AuthController@verifyOTP');
    Route::post('loginRegisterOTP', 'AuthController@loginRegisterOTP');
    Route::post('resendOTP', 'AuthController@resendOTP');
    Route::post('verifyLoginRegisterOTP', 'AuthController@verifyLoginRegisterOTP');
    Route::any('getDocumentTypes', 'AuthController@getDocumentTypes');
    Route::any('convertEtoD', 'AuthController@convertEtoD');
    Route::any('makeEncrypt', 'AuthController@makeEncrypt');
    Route::post('completeProfileFirstStep', 'AuthController@completeProfileFirstStep');
    Route::post('saveRegisterInfo', 'AuthController@saveRegisterInfo');
    Route::any('getTransLimit', 'AuthController@getTransLimit');
    Route::post('loginRegisterOTPNew', 'AuthController@loginRegisterOTPNew');
    Route::post('verifyLoginRegisterOTPNew', 'AuthController@verifyLoginRegisterOTPNew');
    Route::group([
        'middleware' => 'auth:api'
    ], function() {
        
        Route::post('updatePin','AuthController@createUpdateSecurityPin');
        Route::get('forgotPin','AuthController@forgotPin');
        Route::post('verifyOtp','AuthController@verifyOtp');
        Route::post('checkSecurityPin','AuthController@checkSecurityPin');
        Route::post('saveAddressInfo', 'AuthController@saveAddressInfo');
        Route::post('completeProfileSecondStep', 'AuthController@completeProfileSecondStep');
        Route::get('checkVerifyKyc', 'AuthController@checkVerifyKyc');
        Route::get('getCard', 'AuthController@getCard');
        Route::get('createPhysicalCard', 'AuthController@createPhysicalCard');
        Route::get('changeCardPin', 'AuthController@changeCardPin');
        Route::get('lockUnlockCard', 'AuthController@lockUnlockCard');
        Route::get('replaceCard', 'AuthController@replaceCard');
        Route::post('cardAmountAdded', 'AuthController@cardAmountAdded');
        Route::any('getWalletBalance', 'AuthController@getWalletBalance');
        Route::post('profile', 'AuthController@profile');
        Route::get('getReferralData', 'AuthController@getReferralData');

        Route::any('updateBasicProfile', 'AuthController@updateBasicProfile');
        Route::any('updatePhoneNumber', 'AuthController@updatePhoneNumber');
        Route::post('updateProfileImage', 'AuthController@updateProfileImage');
        Route::post('transactionDetail', 'AuthController@transactionDetail');
        Route::post('depositByAgent', 'AuthController@depositByAgent');
        Route::post('withdrawByAgent', 'AuthController@withdrawByAgent');

        Route::post('kycUpdate', 'AuthController@kycUpdate');
        Route::post('fundTransfer', 'AuthController@fundTransfer');
        Route::post('fundTransferGimac', 'AuthController@fundTransferGimac');
        Route::post('sendMoneyRequest', 'AuthController@sendMoneyRequest');

        // Route::post('checkPayeeWallet', 'AuthController@checkPayeeWallet');
        Route::post('transactionSummery', 'AuthController@transactionSummery');
        Route::post('sendRequestFromCemac', 'AuthController@sendRequestFromCemac');
        Route::post('verifyWalletIbanCard', 'AuthController@verifyWalletIbanCard');
        Route::post('sendMoneyFromCemac', 'AuthController@sendMoneyFromCemac');
        Route::post('prepaidCardreloadFromCemac', 'AuthController@prepaidCardreloadFromCemac');
        Route::post('getTransactionDetail', 'AuthController@getTransactionDetailById');
        Route::post('sendMoneyForOutSideCemac', 'AuthController@sendMoneyForOutSideCemac');
        Route::post('transactionListNew', 'AuthController@transactionListNew');
        Route::post('resendPayment', 'AuthController@resendPayment');
        Route::post('resendNewInitPayment', 'AuthController@resendNewInitPayment');
        Route::post('depositByAirtel', 'AuthController@depositByAirtel');
        Route::post('rechargeAndTransfer', 'AuthController@rechargeAndTransfer');
        Route::get('getCardNew', 'AuthController@getCardNew');
        Route::get('checkCardInfoNew', 'AuthController@checkCardInfoNew');
        Route::get('createPhysicalCardNew', 'AuthController@createPhysicalCardNew');
        Route::post('cardOtpVerifyNew', 'AuthController@cardOtpVerifyNew');
        Route::post('rechargeAndTransferNew', 'AuthController@rechargeAndTransferNew');
        Route::get('replaceCardNew', 'AuthController@replaceCardNew');
        Route::post('smartSelfieCompare', 'AuthController@smartSelfieCompare');
        Route::get('ecobankLocationList', 'ApiController@ecobankLocationList');
        Route::any('profileDocumentDetail', 'ApiController@profileDocumentDetail');
        Route::post('profileDocumentUpload', 'ApiController@profileDocumentUpload');
        Route::post('kycUserJobSubmit', 'AuthController@kycUserJobSubmit');





        Route::get('checkCardInfo', 'AuthController@checkCardInfo');
        Route::post('cardOtpVerify', 'AuthController@cardOtpVerify');

        Route::post('cancelAcceptMoneyRequest', 'AuthController@cancelAcceptMoneyRequest');
        Route::post('myTransactions', 'AuthController@myTransactions');
        Route::post('feedback', 'AuthController@feedback');
        
        Route::post('requestList', 'AuthController@requestList');
        Route::post('cancelAcceptRequest', 'AuthController@cancelAcceptRequest');
        
        Route::post('buyCashCard', 'AuthController@buyCashCard');
        Route::post('cashCardList', 'AuthController@cashCardList');
        
        Route::post('merchantTransactions', 'AuthController@merchantTransactions');
        Route::post('refundPayment', 'AuthController@refundPayment');
        Route::post('sendRefund', 'AuthController@sendRefund');
        
        Route::post('checkTransactionFee', 'AuthController@checkTransactionFee');
        
        Route::post('getNotification', 'AuthController@getNotification');
        Route::post('seenNotification', 'AuthController@seenNotification');
        
        Route::post('nearByUser', 'AuthController@nearByUser');
        Route::post('merchantList', 'AuthController@merchantList');
        Route::post('generateQR', 'AuthController@generateQR');
        Route::post('generateQRForAgent', 'AuthController@generateQRForAgent');
        Route::post('getUserByQR', 'AuthController@getUserByQR');
        Route::post('getUserByPhone', 'AuthController@getUserByPhone');
        Route::post('scanMerchantQR', 'AuthController@scanMerchantQR');

        Route::post('walletHistory', 'AuthController@walletHistory');
        Route::post('senderRequestList', 'AuthController@senderRequestList');
        Route::post('receiverRequestList', 'AuthController@receiverRequestList');
        Route::post('/generatePdf', 'AuthController@generatePdf');
        Route::post('/recentPayment', 'AuthController@recentPayment');
        Route::post('/getSwapContactsList', 'AuthController@getSwapContactsList');
        Route::post('/getAirtelContactsList', 'AuthController@getAirtelContactsList');
        Route::any('logout', 'AuthController@logout');

        //for gimac
        Route::post('getCountryList', 'AuthController@getCountryList');    
        Route::post('getWalletManagerList', 'AuthController@getWalletManagerList'); 
        Route::any('walletIncomingRemittance', 'AuthController@walletIncomingRemittance'); 
        Route::get('IdTypeList', 'AuthController@IdTypeList'); 
        Route::post('fundTransferBda',  'AuthController@fundTransferBda');
        Route::post('fundTransferOnafriq',  'AuthController@fundTransferOnafriq');
        Route::get('getRecipientCountry',  'AuthController@getRecipientCountry');
        Route::get('getSenderCountry',  'AuthController@getSenderCountry');
        Route::get('getOnafriqWalletManager',  'AuthController@getOnafriqWalletManager');
        Route::post('accountInquiryWithPayment',  'AuthController@accountInquiryWithPayment');
        Route::post('accountInquiryWithPaymentAllType',  'AuthController@accountInquiryWithPaymentAllType');
        Route::get('generate-iban','AuthController@generateIBAN');
        Route::get('helpCategoryList','AuthController@helpCategoryList');
        Route::post('createHelpTicket','AuthController@createHelpTicket');
        Route::post('helpTicketList','AuthController@helpTicketList');
        Route::get('helpTicketDetail','AuthController@helpTicketDetail');

        // Route::post('transactionDetailBda', 'AuthController@transactionDetailBDA');
    });
    
});
});