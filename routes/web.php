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


//Route::get('/', ['middleware' => ['adminAuth'], 'uses' => 'LoginController@index'])->name('index');
/*Route::get('users_report', function () {
  $users = DB::table('users')->get();
  return view('users_report',['users'=>$users]);	
});*/



Route::get('get-logs', function () {
  $logs = \File::get(storage_path('logs/laravel.log'));

  return "<div style='white-space: pre;'>{$logs}</div>";
})->name('get-logs');

Route::get('clear-logs', function () {
  \File::put(storage_path('logs/laravel.log'), '');
  return redirect()->route('get-logs');
});

Route::get('/clear-cache', function () {
  \Artisan::call('cache:clear');
  \Artisan::call('route:clear');
  \Artisan::call('config:clear');
  \Artisan::call('view:clear');
  \Artisan::call('optimize:clear');
  return 'Cache is cleared';
});


Route::get('/tz-test', function () {
  return [
    'php' => date('Y-m-d H:i:s'),
    'laravel' => now()->timezone('Africa/Libreville'),
    'timezone' => config('app.timezone'),
  ];

});


Route::redirect('/', 'login');


Route::any('/register', 'UsersController@register')->name('register');
Route::any('/create-account/{slug}', 'UsersController@createAccount');
Route::any('/email-verify/{slug}', 'UsersController@emailVerify');
Route::any('/login', 'UsersController@login')->name('login');
Route::any('/verify-otp/{slug}', 'UsersController@verifyOtp');
Route::any('/resendOTP', 'UsersController@resendOTP');
Route::any('/resendEmailOtp', 'UsersController@resendEmailOtp');
Route::any('generate-password/{slug}', 'DashboardController@generatePassword');
Route::any('forgot-password', 'UsersController@forgotPassword');
Route::any('/verify-email/{slug}', 'UsersController@verifyEmailForgot');
Route::any('reset-password/{slug}', 'UsersController@resetPassword');
Route::any('/delete-account', 'PagesController@deleteAccount');

/* Route::any('/payment-login', 'DashboardController@paymentLogin');
Route::post('/createConnection', 'DashboardController@createConnection');
Route::any('/initPayment', 'DashboardController@initPayment');
Route::post('makeBulkPayment',  'BulkPaymentController@makeBulkPayment');
Route::any('/payment-return-status', 'DashboardController@paymentReturnStatus');
Route::get('/trackOrder/{orderId}', 'DashboardController@trackOrderTransaction'); */
Route::any('/verify-otp-external/{slug}', 'DashboardController@verifyOtpExternal');
Route::any('getWalletmanagerList', 'DashboardController@getWalletmanagerList');
Route::any('getOldRecord', 'DashboardController@getOldRecord');
Route::any('transComExternal', 'DashboardController@transComExternal');
Route::any('getTransStatus', 'DashboardController@getTransStatus');
Route::any('check-gimac-inquiry', 'DashboardController@checkGimacInquiry');
Route::any('generate-multiple-ibans', 'DashboardController@generateMultipleIBANs');
Route::any('init-payment-externalssss', 'DashboardController@initPaymentExternal')->name('initPaymentExternal');


/* Route::get('create-2fa', 'UsersController@createOneTimePasscode');
Route::get('send-2fa/{appId}/{pincode}', 'UsersController@sendTwoFactorMessage'); 
Route::get('deliver-2fa/{appId}/{messageId}/{to}', 'UsersController@deliverTwoFactorPasscode'); 
Route::get('verify-sms-otp/{pinId}/{pinCode}', 'UsersController@verifyOtpPasscode');  */
Route::get('send-whats-app-otp', 'UsersController@sendWhatsAppOtp');
// Route::any('/{slug}', 'PagesController@index');

Route::any('pages/{slug}', 'PagesController@pageIndex');

Route::any('/lang/{lang}', 'LanguageController@lang');
Route::get('smileid-return-url', 'DashboardController@smileidReturnUrl');

Route::group(['middleware' => ['auth:web']], function () {
  Route::any('referral', 'DashboardController@referralDashboard')->name('referral');
  Route::any('dashboard', 'DashboardController@dashboard');
  Route::any('create-user', 'DashboardController@creatUser');
  Route::any('get-user-list', 'DashboardController@getUserList');
  Route::any('logout', 'UsersController@logout');
  Route::any('delete-user/{slug}', 'DashboardController@deleteUser');
  Route::any('submitter-dashboard', 'DashboardController@submitterDashboard');
  Route::any('get-excel-list', 'DashboardController@getExcelList');
  Route::any('approver-dashboard', 'DashboardController@approverDashboard');
  Route::any('pending-get-excel-list', 'DashboardController@pendingGetExcelList');
  Route::any('approver-get-excel-list', 'DashboardController@approverGetExcelList');
  Route::any('approve-excel/{slug}', 'DashboardController@approveExcel');
  Route::any('notifications', 'DashboardController@notifications');
  Route::any('pending-approvals', 'DashboardController@pendingapprovals');
  Route::any('transition-history', 'DashboardController@transitionhistory');
  Route::any('operations-month', 'DashboardController@operationsmonth');
  Route::any('operation-of-this-month-list', 'DashboardController@operationofthismonthList');
  Route::any('number_success', 'DashboardController@numberofsuccess');
  Route::any('successfull-transaction-list', 'DashboardController@successfullTransactionList');
  Route::any('failure-transaction', 'DashboardController@failureTransaction');
  Route::any('failure-transaction-list', 'DashboardController@failureTransactionList');
  Route::any('customer-deposits', 'DashboardController@customerDeposits');
  Route::any('get-deposit-list', 'DashboardController@getDepositList');
  Route::any('movement-shipments', 'DashboardController@movementShipments');
  Route::any('gimac-transaction-list', 'DashboardController@gimacTransactionList');
  Route::any('get-notification-list', 'DashboardController@getNotificationList');
  Route::any('reject-request/{slug}', 'DashboardController@rejectRequest');
  Route::any('get-excel-transaction', 'DashboardController@getExcelTransaction');
  Route::any('get-excel-record/{slug}', 'DashboardController@getExcelRecords');
  Route::any('single-transfer', 'DashboardController@singleTransfer');
  Route::any('/get-transaction-details/{slug}/{slug1}', 'DashboardController@getTransactionDetail');

  // Route::any('bulk-transfer', 'DashboardController@bulkTransfer')->name('bulk.transfer');
  Route::any('bulk-transfer', 'DashboardController@bulkTransferSwap')->name('bulk.transfer');
  Route::any('bulk-transfer-gimac', 'DashboardController@bulkTransferGimac');
  Route::any('bulk-transfer-bda', 'DashboardController@bulkTransferBda');
  Route::any('bulk-transfer-onafriq', 'DashboardController@bulkTransferOnafriq');

  Route::any('bulk/check', 'DashboardController@checkDuplicates')->name('bulk.check');

  //Route::any('getWalletmanagerList', 'DashboardController@getWalletmanagerList');
  Route::any('/export_excel', 'DashboardController@ExportExcel')->name('export');
  Route::any('/get-transaction-detail/{slug}/{slug1}', 'AuthController@getTransactionDetail');
  Route::any('beneficiary-list', 'DashboardController@beneficiaryList');
  Route::any('add-beneficiary', 'DashboardController@addBeneficiary');
  Route::any('all-beneficiary-list', 'DashboardController@allBeneficiaryList');
  Route::any('/{id}/toggle-beneficiary-status/{status}', 'DashboardController@toggleBeneficiaryStatus');
  Route::any('change-password', 'DashboardController@changePassword');
  Route::any('/bdapayment', 'DashboardController@bankBDA');
  Route::any('beneficary', 'DashboardController@beneficiarysingleList');
  Route::any('fill-form-data/{selectedBeneficiary}', 'DashboardController@formFill');
  Route::any('/customer_export_excel', 'DashboardController@CustomerExportExcel')->name('CustomerExportExcel');
  Route::any('/failer_export_excel', 'DashboardController@FailerTransactionExcel')->name('FailerTransactionExcel');
  Route::any('/success_transaction_excel', 'DashboardController@SuccessTransactionExcel')->name('SuccessTransactionExcel');
  Route::any('/operation_of_month_excel', 'DashboardController@OperationOfMonthExcel')->name('OperationOfMonthExcel');
});

Route::group(['prefix' => 'admin', 'namespace' => 'Admin'], function () {
  Route::any('/', 'AdminsController@login');
  Route::any('login', 'AdminsController@login');
  Route::any('admins/login', 'AdminsController@login');
  Route::get('admins/getRoles', 'AdminsController@getRoles');
  Route::get('admins/logout', 'AdminsController@logout');
  Route::get('admins/dashboard', 'AdminsController@dashboard');
  Route::any('admins/change-username', 'AdminsController@changeUsername');
  Route::any('admins/change-password', 'AdminsController@changePassword');
  Route::any('admins/change-email', 'AdminsController@changeEmail');
  Route::any('admins/change-referral-bonus', 'AdminsController@changeReferralBonus');
  Route::any('admins/forgot-password', 'AdminsController@forgotPassword');
  Route::any('admins/change-commission', 'AdminsController@changeCommission');
  Route::any('admins/change-limit/{slug}', 'AdminsController@changeLimit');
  Route::get('admins/userchart/{daycount}', 'AdminsController@userchart');
  Route::get('admins/agentchart/{daycount}', 'AdminsController@agentchart');
  Route::get('admins/merchantchart/{daycount}', 'AdminsController@merchantchart');
  Route::get('admins/transchart/{daycount}', 'AdminsController@transchart');
  Route::any('admins/change-service', 'AdminsController@changeService');


  Route::any('admins/department', 'AdminsController@listRole');
  Route::any('admins/add-department', 'AdminsController@addRole');
  Route::any('admins/edit-department/{slug}', 'AdminsController@editRole');

  Route::any('admins/company-list', 'AdminsController@companiesList');
  Route::any('admins/add-company', 'AdminsController@addCompany');
  Route::any('admins/edit-company/{slug}', 'AdminsController@editCompany');
  Route::get('/updateCompanyStatus/{slug}/{slug1}', 'AdminsController@updateCompanyStatus');
  Route::any('/admins/pay-company/{slug}', 'AdminsController@payCompany');
  Route::any('/admins/company-transaction-history/{slug}', 'AdminsController@companyTransactionHistory');



  /*****Sub Admin Routing*****/
  Route::any('/subadmins', 'SubadminsController@index');
  Route::any('/subadmins/add-subadmins', 'SubadminsController@add');
  Route::any('/subadmins/edit-subadmins/{slug}', 'SubadminsController@edit');
  Route::get('/subadmins/activate/{slug}', 'SubadminsController@activate');
  Route::get('/subadmins/deactivate/{slug}', 'SubadminsController@deactivate');
  Route::get('/subadmins/delete-subadmins/{slug}', 'SubadminsController@delete');

  /*****Drivers Routing*****/
  Route::any('/drivers', 'DriversController@index');
  Route::any('/drivers/view-activation-card/{slug}', 'DriversController@viewActivationCard');
  Route::any('/drivers/add-driver', 'DriversController@add');
  Route::any('/drivers/edit-driver/{slug}', 'DriversController@edit');
  Route::get('/driver/activate/{slug}', 'DriversController@activate');
  Route::get('/driver/deactivate/{slug}', 'DriversController@deactivate');
  Route::get('/drivers/delete-driver/{slug}', 'DriversController@delete');

  /*****Locations Routing*****/
  Route::any('/locations', 'LocationController@index');
  Route::any('/locations/add-location', 'LocationController@add');
  Route::any('/locations/edit-location/{slug}', 'LocationController@edit');
  Route::get('/locations/delete-location/{slug}', 'LocationController@delete');


  /*****Helpticket Routing*****/
  Route::any('/help-ticket', 'HelpTicketController@index');
  Route::any('/help-ticket/view-help-ticket/{slug}', 'HelpTicketController@viewTelpTicket');
  Route::any('/help-ticket/update-help-ticket-status/{slug}', 'HelpTicketController@updateHelpTicketStatus');

  /*****Individuals Routing*****/
  Route::any('/users/loginusers', 'UsersController@loginusers');
  Route::any('/users/all', 'UsersController@all');
  Route::any('/users/edit-all/{slug}', 'UsersController@editAll');


  Route::any('/users', 'UsersController@index');
  Route::any('/users/add-users', 'UsersController@add');
  Route::any('/users/importuser', 'UsersController@Importuser');
  Route::any('/users/edit-users/{slug}', 'UsersController@edit');
  Route::get('/users/activate/{slug}', 'UsersController@activate');
  Route::get('/users/deactivate/{slug}', 'UsersController@deactivate');
  Route::get('/users/delete/{slug}', 'UsersController@delete');
  Route::get('/users/deleteimage/{slug}', 'UsersController@deleteimage');
  Route::get('/users/deleteidentity/{slug}', 'UsersController@deleteidentity');
  Route::get('/users/kycdetail/{slug}', 'UsersController@kycdetail');
  Route::any('/users/approvekyc/{slug}', 'UsersController@approvekyc');
  Route::any('/users/declinekyc/{slug}', 'UsersController@declinekyc');

  Route::get('/users/travel-document/{slug}', 'UsersController@travelDocumentList');
  Route::get('/users/approveTravel/{slug}', 'UsersController@approveTravel');
  Route::get('/users/declineTravel/{slug}', 'UsersController@declineTravel');

  Route::get('/users/gabon-visa-stamp/{slug}', 'UsersController@gabonVisaStampDocument');
  Route::get('/users/approveGabonStamp/{slug}', 'UsersController@approveGabonStamp');
  Route::get('/users/declineGabonStamp/{slug}', 'UsersController@declineGabonStamp');

  Route::get('users/getarealist/{slug}', 'UsersController@getarealist');
  Route::any('/users/transactions-limit', 'UsersController@transLimit');
  Route::any('/users/edit-transaction-limit/{slug}', 'UsersController@editTransLimit');

  Route::any('users/payclient/{slug}', 'UsersController@payClient');
  Route::any('users/payclientrebate/{slug}', 'UsersController@payClientRebate');

  /*****Agents Routing*****/
  Route::any('/agents', 'AgentsController@index');
  Route::any('/agents/add-agents', 'AgentsController@add');
  Route::any('/agents/edit-agents/{slug}', 'AgentsController@edit');
  Route::get('/agents/activate/{slug}', 'AgentsController@activate');
  Route::get('/agents/deactivate/{slug}', 'AgentsController@deactivate');
  Route::get('/agents/delete/{slug}', 'AgentsController@delete');
  Route::get('/agents/deleteimage/{slug}', 'AgentsController@deleteimage');
  Route::get('/agents/deleteidentity/{slug}', 'AgentsController@deleteidentity');
  Route::get('/agents/kycdetail/{slug}', 'AgentsController@kycdetail');
  Route::get('/agents/approvekyc/{slug}', 'AgentsController@approvekyc');
  Route::get('/agents/declinekyc/{slug}', 'AgentsController@declinekyc');
  Route::any('/agents/payclient/{slug}', 'AgentsController@payClient');
  Route::get('/agents/remove-agent/{slug}', 'AgentsController@removeAgent');
  Route::get('/agents/remove-agent/{slug}', 'AgentsController@removeAgent');
  Route::any('/agents/reports', 'AgentsController@reports');
  /*****Merchant Routing*****/
  Route::any('/merchants', 'MerchantsController@index');
  Route::any('/merchants/add-merchants', 'MerchantsController@add');
  Route::any('/merchants/edit-merchants/{slug}', 'MerchantsController@edit');
  Route::get('/merchants/activate/{slug}', 'MerchantsController@activate');
  Route::get('/merchants/deactivate/{slug}', 'MerchantsController@deactivate');
  Route::get('/merchants/delete/{slug}', 'MerchantsController@delete');
  Route::get('/merchants/deleteimage/{slug}', 'MerchantsController@deleteimage');
  Route::get('/merchants/deleteidentity/{slug}', 'MerchantsController@deleteidentity');
  Route::get('/merchants/kycdetail/{slug}', 'MerchantsController@kycdetail');
  Route::get('/merchants/approvekyc/{slug}', 'MerchantsController@approvekyc');
  Route::get('/merchants/declinekyc/{slug}', 'MerchantsController@declinekyc');

  Route::any('/merchants/merchantSetting/{slug}', 'MerchantsController@merchantSetting');

  Route::get('/merchants/api-activate/{slug}', 'MerchantsController@apiActivate');
  Route::get('/merchants/api-deactivate/{slug}', 'MerchantsController@apiDeactivate');

  Route::any('/merchants/payclient/{slug}', 'MerchantsController@payClient');

  /***********Bulk Payment Merchant***********/
  Route::any('/bulk-payment-merchants', 'MerchantsController@bulkPaymentMerchantIndex');
  Route::any('/add-bulk-payment-merchants', 'MerchantsController@bulkPaymentMerchantAdd');

  /*****Scratch Card Routing*****/
  Route::any('/scratchcards', 'ScratchcardsController@index');
  Route::any('/scratchcards/usedcard', 'ScratchcardsController@usedcard');
  Route::any('/scratchcards/add', 'ScratchcardsController@add');
  Route::any('/scratchcards/edit/{slug}', 'ScratchcardsController@edit');
  Route::get('/scratchcards/activate/{slug}', 'ScratchcardsController@activate');
  Route::get('/scratchcards/deactivate/{slug}', 'ScratchcardsController@deactivate');
  Route::get('/scratchcards/delete/{slug}', 'ScratchcardsController@delete');

  /*****Card Routing*****/
  Route::any('/cards', 'CardsController@index');
  Route::any('/cards/usedcard', 'CardsController@usedcard');
  Route::any('/cards/add', 'CardsController@add');
  Route::any('/cards/edit/{slug}', 'CardsController@edit');
  Route::get('/cards/activate/{slug}', 'CardsController@activate');
  Route::get('/cards/deactivate/{slug}', 'CardsController@deactivate');
  Route::get('/cards/delete/{slug}', 'CardsController@delete');

  Route::any('/cards/carddetail/{cslug}', 'CardsController@carddetail');
  Route::any('/cards/addcarddetail/{cslug}', 'CardsController@addcarddetail');
  Route::any('/cards/editcarddetail/{cslug}/{slug}', 'CardsController@editcarddetail');
  Route::get('/cards/activatecarddetail/{slug}', 'CardsController@activatecarddetail');
  Route::get('/cards/deactivatecarddetail/{slug}', 'CardsController@deactivatecarddetail');
  Route::get('/cards/deletecarddetail/{cslug}/{slug}', 'CardsController@deletecarddetail');



  Route::any('/cards/importcards/{slug}', 'CardsController@importCards');
  Route::get('/cards/addcards/{slug}', 'CardsController@addcards');

  Route::any('/cards/cardoffers/{cslug}/{cid}', 'CardsController@cardoffers');
  Route::any('/cards/addcardoffer/{cslug}/{cid}', 'CardsController@addcardoffer');
  Route::any('/cards/editcardoffer/{cslug}/{cid}', 'CardsController@editcardoffer');
  Route::get('/cards/activatecarddetail/{slug}', 'CardsController@activatecarddetail');
  Route::get('/cards/deactivatecarddetail/{slug}', 'CardsController@deactivatecarddetail');
  Route::get('/cards/deletecarddetail/{cslug}/{slug}', 'CardsController@deletecarddetail');

  /*****Card Routing*****/
  Route::any('/banners', 'BannersController@index');
  Route::any('/banners/add-banners', 'BannersController@add');
  Route::any('/banners/edit-banners/{slug}', 'BannersController@edit');
  Route::get('/banners/activate/{slug}', 'BannersController@activate');
  Route::get('/banners/deactivate/{slug}', 'BannersController@deactivate');
  Route::get('/banners/delete-banners/{slug}', 'BannersController@delete');


  Route::any('/cardcontents', 'CardcontentController@index');
  Route::any('/cardcontents/add-card-content', 'CardcontentController@add');
  Route::any('/cardcontents/edit-card-content/{slug}', 'CardcontentController@edit');
  Route::get('/cardcontents/activate/{slug}', 'CardcontentController@activate');
  Route::get('/cardcontents/deactivate/{slug}', 'CardcontentController@deactivate');
  Route::get('/cardcontents/delete-card-content/{slug}', 'CardcontentController@delete');

  Route::get('/banners/getcategorylist/{slug}', 'BannersController@getcategorylist');

  Route::any('/transactions', 'TransactionsController@index');
  Route::any('/transactions/adminTrans', 'TransactionsController@adminTrans');
  Route::any('/transactions/transactionHistory/{slug}', 'TransactionsController@transactionHistory');
  Route::any('/transactions/earning', 'TransactionsController@earning');
  Route::any('/transactions/adjustWallet', 'TransactionsController@adjustWallet');
  Route::any('/transactions/getBalance', 'TransactionsController@getBalance');
  Route::get('/transactions/delete/{slug}', 'TransactionsController@delete');
  Route::any('/transactions/export_excel', 'TransactionsController@ExportExcel')->name('report');
  Route::any('/transactions/export_excel_gimac', 'TransactionsController@TransactionsExportGimac')->name('exportexcelgimac');
  Route::any('/transactions/export_excel_bda', 'TransactionsController@TransactionsExportBda')->name('exportexcelbda');
  Route::any('/transactions/export_excel_onafriq', 'TransactionsController@TransactionsExportOnafriq')->name('exportexcelonafriq');
  Route::any('/transactions/export_excel_airtel', 'TransactionsController@TransactionsExportAIRTEL')->name('exportexcelairtel');
  Route::any('/transactions/export_excel_referral', 'TransactionsController@TransactionsExportReferral')->name('exportexcelreferral');
  Route::any('/transactions/export_excel_visa', 'TransactionsController@TransactionsExportVISA')->name('exportexcelvisa');
  Route::any('/transactions/export_excel_external', 'TransactionsController@TransactionsExportEXTERNAL')->name('exportexcelexternal');
  //Route::any('/transactions/export_excel_individual/{slug}','TransactionsController@ExportExcelIndividual')->name('report');	
  Route::any('/transactions/export_excel_individual', 'TransactionsController@ExportExcelIndividual');

  Route::any('/referral-listing', 'TransactionsController@referralListing');
  // Route::any('/remitec-transactions', 'TransactionsController@remitecTransactions');
  Route::any('/onafriq-transactions', 'TransactionsController@onafriqTransactions');
  Route::any('/bda-transactions', 'TransactionsController@bdaTransactions');
  Route::any('/gemic-transation', 'TransactionsController@gimacTransation');
  Route::any('/swaptoswap-transation', 'TransactionsController@swapToswapTransation');
  Route::any('/airtel-transaction', 'TransactionsController@AirtelMoneyTransactions');
  Route::any('/visa-transaction', 'TransactionsController@VisaCardPaymentTransactions');
  Route::any('/external-transaction', 'TransactionsController@ExternalTransactions');


  Route::any('/referral-setting', 'TransactionfeesController@referralSetting');
  Route::any('/referral-setting-edit/{id}', 'TransactionfeesController@referralSettingEdit');

  /****Transaction Fee*****/
  Route::any('/transactionfees', 'TransactionfeesController@index');
  Route::any('/transactionfees/edit-transactionfees/{slug}', 'TransactionfeesController@edit');
  Route::get('/transactionfees/delete/{slug}', 'TransactionfeesController@delete');

  /****User Transaction Fee*****/
  Route::any('/transactionfees/transactionfee/{slug}', 'TransactionfeesController@transactionfee');
  Route::any('/transactionfees/addfee/{tslug}', 'TransactionfeesController@addfee');
  Route::any('/transactionfees/editfee/{slug}/{id}', 'TransactionfeesController@editFee');
  Route::get('/transactionfees/activatefee/{slug}', 'TransactionfeesController@activatefee');
  Route::get('/transactionfees/deactivatefee/{slug}', 'TransactionfeesController@deactivatefee');
  Route::get('/transactionfees/deletefee/{slug}/{id}', 'TransactionfeesController@deleteFee');


  Route::any('/transactionfees/amountSlab', 'TransactionfeesController@amountSlab');
  Route::any('/transactionfees/addSlab', 'TransactionfeesController@addSlab');
  Route::any('/transactionfees/editSlab/{id}', 'TransactionfeesController@editSlab');

  Route::any('/transactionfees/addConfiguration', 'TransactionfeesController@addConfiguration');

  /****Offers For Agents*****/
  Route::any('/offers', 'OffersController@index');
  Route::any('/offers/agentoffers/{slug}', 'OffersController@agentoffers');
  Route::any('/offers/addagentoffer/{uslug}', 'OffersController@addagentoffer');
  Route::any('/offers/editagentoffer/{uslug}/{slug}', 'OffersController@editagentoffer');
  Route::any('/offers/edit/{slug}', 'OffersController@edit');
  Route::get('/offers/activate/{slug}', 'OffersController@activate');
  Route::get('/offers/deactivate/{slug}', 'OffersController@deactivate');
  Route::get('/offers/activateoffer/{slug}', 'OffersController@activateoffer');
  Route::get('/offers/deactivateoffer/{slug}', 'OffersController@deactivateoffer');
  Route::get('/offers/delete/{slug}', 'OffersController@delete');
  Route::get('/offers/deleteagentoffer/{slug}/{id}', 'OffersController@deleteagentoffer');

  Route::any('/pages', 'PagesController@index');
  Route::any('/pages/edit-pages/{slug}', 'PagesController@edit');
  Route::any('/pages/pageimages', 'PagesController@pageimages');

  Route::any('/contacts', 'ContactsController@index');

  Route::any('/homeFeatures', 'PagesController@homeFeatures');
  Route::any('/pages/deactivateFeature/{slug}', 'PagesController@deactivateFeature');
  Route::any('/pages/activateFeature/{slug}', 'PagesController@activateFeature');

  Route::any('/users/homeFeatures/{slug}', 'UsersController@homeFeatures');
  Route::any('/users/deactivateFeature/{userSlug}/{slug}', 'UsersController@deactivateFeature');
  Route::any('/users/activateFeature/{userSlug}/{slug}', 'UsersController@activateFeature');

  Route::any('/transaction/getSummary', 'TransactionsController@getSummary');
  Route::any('/transaction/refund/{slug}', 'TransactionsController@refund');

  Route::any('card-request/list', 'CardRequestController@cardRequestList');
  Route::any('card-assign/{id}', 'CardRequestController@cardAssign');


});

Route::any('/delete-account-user', 'UsersController@deleteAccountUser')->name('delete-account-user');
Route::any('/delete-account-verify/{slug}', 'UsersController@deleteAccountVerify');
Route::any('/delete-success', 'UsersController@deleteSuccess')->name('delete-success');

Route::middleware(['isDriverlogin'])->group(function () {
  Route::any('/login-driver', 'UsersController@loginDriver')->name('login-driver');
  Route::any('/driver-phone-verify/{slug}', 'UsersController@driverPhoneVerify');
});

Route::middleware(['auth:driver-web'])->group(function () {
  Route::any('/driver-dashboard', 'DashboardController@driverDashboard')->name('driver-dashboard');
  Route::any('/verify-card-status', 'DashboardController@verifyCardStatus')->name('verify-card-status');
  Route::get('/logout-driver', 'DashboardController@logoutDriver')->name('driver.logout');
});

//  Route::get('/driver-dashboard', 'DashboardController@driverDashboard')->name('driver-dashboard');
//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('logFile', function () {
  $logs = File::get(storage_path("logs/laravel.log"));
  return "<div style=\"white-space:pre;\">$logs</div>";
});

Route::any('capture-face', 'DashboardController@captureFace');
Route::any('/smileid/signature', 'DashboardController@getSmileIdSignature');