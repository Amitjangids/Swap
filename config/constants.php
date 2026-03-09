<?php

define('SITE_TITLE', 'Swap Wallet');
define('TITLE_FOR_LAYOUT', ' : ' . SITE_TITLE);
define('HTTP_PATH', 'https://internal.swap-africa.net');
define('PUBLIC_PATH', 'https://internal.swap-africa.net/public');
define("BASE_PATH", $_SERVER['DOCUMENT_ROOT']);

define('MAIL_FROM', '');

define('CAPTCHA_KEY', '');

define('OTP_API_KEY', '');
define('SMS_URL', '');
define('SMS_FROM_SENDER_NO', '');

define('UNIMTX_SMS_ACCESS_KEY', '');
define('UNIMTX_SMS_SECRET_KEY', '');

define('IMAGE_EXT', 'image/gif, image/jpeg, image/png');
define('DOC_EXT', '.pdf,.doc,.docx');
define('MAX_IMAGE_UPLOAD_SIZE_DISPLAY', '2MB');
define('MAX_IMAGE_UPLOAD_SIZE_VAL', 2048);

define('LOGO_IMAGE_DISPLAY_PATH', PUBLIC_PATH . '/img/swap.png');
define('LOGO_IMAGE_DISPLAY_PATH1', PUBLIC_PATH . '/img/swap1.png');
define('LOGO_PATH', LOGO_IMAGE_DISPLAY_PATH);
define('LOGO_PATH1', LOGO_IMAGE_DISPLAY_PATH1);

define('FAVICON_PATH', PUBLIC_PATH . '/img/favicon.ico.png');

define('CURR','XAF ');
define('SECRET_KEY','');

define('API_BASIC_USER', '');
define('API_BASIC_PASS', '');

/*****Twellio Details*****/
global $sms_from;
$sms_from='00000000';
define('Account_SID', '');
define('Auth_Token', '');


define('SMILE_PATH', '');
define('SMILE_PATH_COMPARE', '');
define('SMILE_API_KEY', '');
define('SMILE_PARTNER_ID', '');


define('VIRTUAL_CARD_FEES', '5000');
define('CARD_FEES', '6000');
define('PHYSICAL_CARD_FEES', '4000');
define('REPLACE_CARD_FEES', '10000');


/*************Production Onafriq Details */
define('ONAFRIQ_PROGRAMID', '');
define('ONAFRIQ_PROGRAMID_PHY', '');
define('ONAFRIQ_SUBCOMPANY', '');
define('ONAFRIQ_SUBCOMPANY_PHY', '');
define('ONAFRIQ_INFO_PROGRAMID', '');
define('ONAFRIQ_VAULTID', '');
define('ONAFRIQ_CARD_URL', '');
define('ONAFRIQ_AUTH', '');



define('CORPORATECODE', '');
define('CORPORATEPASS', '');

define('APIURL', '');

/* ******* profile image path ****** */
define('WALK_FULL_UPLOAD_PATH', 'public/uploads/walks/');
define('WALK_FULL_DISPLAY_PATH', 'public/uploads/walks/');

/* ******* profile image path ****** */
define('PROFILE_FULL_UPLOAD_PATH', 'public/uploads/profile_images/full/');
define('PROFILE_SMALL_UPLOAD_PATH', 'public/uploads/profile_images/small/');
define('PROFILE_FULL_DISPLAY_PATH', 'public/uploads/profile_images/full/');
define('PROFILE_SMALL_DISPLAY_PATH', 'public/uploads/profile_images/small/');
define('PROFILE_MW', 250);
define('PROFILE_MH', 250);

/* ******* identity image path ****** */
define('IDENTITY_FULL_UPLOAD_PATH','public/uploads/identity/full/');
define('IDENTITY_SMALL_UPLOAD_PATH', 'public/uploads/identity/small/');
define('IDENTITY_FULL_DISPLAY_PATH', 'public/uploads/identity/full/');
define('IDENTITY_SMALL_DISPLAY_PATH', 'public/uploads/identity/small/');
define('IDENTITY_MW', 250);
define('IDENTITY_MH', 250);

/* ******* Company image path ****** */
define('COMPANY_FULL_UPLOAD_PATH', 'public/uploads/company/full/');
define('COMPANY_SMALL_UPLOAD_PATH', 'public/uploads/company/small/');
define('COMPANY_FULL_DISPLAY_PATH', 'public/uploads/company/full/');
define('COMPANY_SMALL_DISPLAY_PATH', 'public/uploads/company/small/');
define('COMPANY_MW', 250);
define('COMPANY_MH', 250);

/* ******* Banner image path ****** */
define('BANNER_FULL_UPLOAD_PATH', 'public/uploads/banner/full/');
define('BANNER_SMALL_UPLOAD_PATH', 'public/uploads/banner/small/');
define('BANNER_FULL_DISPLAY_PATH', 'public/uploads/banner/full/');
define('BANNER_SMALL_DISPLAY_PATH', 'public/uploads/banner/small/');
define('BANNER_MW', 150);
define('BANNER_MH', 145);

define('DOCUMENT_UPLOAD_PATH', 'public/uploads/documents/');
define('HELP_TICKET_PATH', 'public/uploads/help_tickets/');
define('PASSPORT_PATH', 'public/uploads/travel/passport/');
define('TICKET_PATH', 'public/uploads/travel/ticket/');
define('VISA_PATH', 'public/uploads/travel/visa/');
define('GABON_VISA_STAMPED', 'public/uploads/gabon_visa_stamped/');


global $documents;
$documents = array(
    array(
        'id' => 'HEALTH_CARD',
        'name' => 'Health Insurance Card & Health Card',
        'isBoth' => false
    ),
    array(
        'id' => 'IDENTITY_CARD',
        'name' => 'National IDs, Consular IDs & Diplomat IDs',
        'isBoth' => true
    ),
    array(
        'id' => 'PASSPORT',
        'name' => 'Passports',
        'isBoth' => false
    ),
    array(
        'id' => 'RESIDENT_ID',
        'name' => 'Residency permits & Residency cards',
        'isBoth' => false
    ),
    array(
        'id' => 'TRAVEL_DOC',
        'name' => 'Border crossing documents, Refugee document & Visas',
        'isBoth' => false
    )
);


global $userType;
$userType  = array(
    'User' => 'User',
    'Agent' => 'Agent',
    'Merchant' => 'Merchant',
);

global $cardType;
$cardType  = array(
    1 => 'Internet Recharge Card',
    2 => 'Mobile Recharge Card',
    3 => 'Online Gift Card',
);

global $tranStatus;
$tranStatus  = array(
    0 => 'Pending',
    1 => 'Success',
    2 => 'Pending',
    3 => 'Failed',
    4 => 'Reject',
    5 => 'Refund',
    6 => 'Refund Completed',
    7 => 'Suspected',
);
global $tranType;
$tranType  = array(
    1 => 'Credit',
    2 => 'Debit',
    3 => 'Topup',
    4 => 'Request',
);
global $categoryType;
$categoryType  = array(
    'Internet Recharge' => 'Internet Recharge',
    'Mobile Recharge' => 'Mobile Recharge',
    'Online Card' => 'Online Card',
    'Deposit' => 'Deposit',
    'Online Shopping' => 'Online Shopping',
    'Transactions' => 'Transactions',
    'Withdraw' => 'Withdraw',
    'Send Money' => 'Send Money',
    'Receive Money' => 'Receive Money',
    'Shop Payment' => 'Shop Payment',
);

global $transFeeType;
$transFeeType  = array(
    'Deposit' => 'Deposit',
    'Shopping' => 'Shopping',
    'Online Shopping' => 'Online Shopping',
    'Merchant Withdraw' => 'Merchant Withdraw',
    'Withdraw' => 'Withdraw',
    'Send Money' => 'Send Money',
    // 'Refund' => 'Refund',
);
global $agentTransFeeType;
$agentTransFeeType  = array(
    'Deposit' => 'Deposit',
    'Withdraw' => 'Withdraw',
    'Send Money' => 'Send Money',
    'Online Shopping' => 'Online Shopping',
    'Merchant Withdraw' => 'Merchant Withdraw',
);
global $merchantTransFeeType;
$merchantTransFeeType  = array(
    'Deposit' => 'Deposit',
    'Shopping' => 'Shopping',
    'Withdraw' => 'Withdraw',
    'Send Money' => 'Send Money',
    'Refund' => 'Refund',
    'Online Shopping' => 'Online Shopping',
    'Merchant Withdraw' => 'Merchant Withdraw',
);

global $offerCards;
$offerCards  = array(
    'Internet Card' => 'Internet Card',
    'Mobile Card' => 'Mobile Card',
    'Online Card' => 'Online Card',
    'Cash Card' => 'Cash Card',
);

global $roles;
$roles  = array(
    1 => 'Configuration',
    2 => 'Manage Individual Users',
    3 => 'Manage Agent Users',
    4 => 'Manage Merchant Users',
    5 => 'Manage Sub Admins',
    6 => 'Manage Scratch Cards',
    7 => 'Manage Cards',
    8 => 'Manage Banners',
    9 => 'Manage Offers',
    10 => 'Manage Transactions',
    11 => 'Manage Inquiries',
    12 => 'Manage Pages',
    13 => 'Manage Transaction Fees',
    14 => 'Manage Balance Management',
    15 => 'Logged In Users',
    16 => 'Manage Total Register Users',
    17 => 'Admin Transaction List',
);

global $agentFeature;
$agentFeature  = array(
    'Buy Balance' => 'Buy Balance',
    'Sell Balance' => 'Sell Balance',
    'Cash Card' => 'Cash Card',
    'Mobile Recharge' => 'Mobile Recharge',
    'Internet Recharge' => 'Internet Recharge',
    'Online Card' => 'Online Card',
    'Transactions' => 'Transactions',
);
global $merchantFeature;
$merchantFeature  = array(
    'Receive Money' => 'Receive Money',
    'Refund Payment' => 'Refund Payment',
    'Withdraw' => 'Withdraw',
    'Mobile Recharge' => 'Mobile Recharge',
    'Internet Recharge' => 'Internet Recharge',
    'Online Card' => 'Online Card',
    'Transactions' => 'Transactions',
);
global $userFeature;
$userFeature  = array(
    'Deposit' => 'Deposit',
    'Withdraw' => 'Withdraw',
    'Send Money' => 'Send Money',
    'Receive Money' => 'Receive Money',
    'Online Shopping' => 'Online Shopping',
    'Shop Payment' => 'Shop Payment',
    'Mobile Recharge' => 'Mobile Recharge',
    'Internet Recharge' => 'Internet Recharge',
    'Online Card' => 'Online Card',
    'Transactions' => 'Transactions',
);

global $paymentFor;
$paymentFor  = array(
    // 'Internet Card' => 'Internet Recharge',
    // 'Mobile Recharge' => 'Mobile Recharge',
    // 'Online Card' => 'Online Card',
    'Deposit' => 'Deposit',
    // 'Online Shopping' => 'Online Shopping',
    // 'Cash Card' => 'Cash Card',
    'Withdraw' => 'Withdraw',
    'send_money' => 'Send Money',
    'Receive Money' => 'Receive Money',
    // 'Shop Payment' => 'Shop Payment',
    'Refund' => 'Refund',
    'wallet2wallet' => 'wallet2wallet',
    'airtelwallet' => 'Airtel Money',
    'CARDPAYMENT' => 'Card Recharge',
    'TRANSAFEROUT' => 'Transfer Out',
);




global $typefor;
$typefor  = array(
  
    'Deposit' => 'Deposit',
    'Airtel Deposit' => 'Airtel Deposit',
    'Request Money' => 'Request Money',
    'Withdraw' => 'Withdraw',
    'Send Money' => 'Send Money',
    'Refund' => 'Refund',
    'Money Transfer Via GIMAC' =>'Money Transfer Via GIMAC',
    'Money Transfer Via BDA'  =>'Money Transfer Via BDA',
    'Money Transfer Via ONAFRIQ'  =>'Money Transfer Via ONAFRIQ',
    'PRECARD'  =>'PRECARD',
   
);



global $status;
$status  = array(
    0 => 'Not Verified',
    1 => 'Verified'
   
);


global $Remitec;
$Remitec  = array(
    'bank_transfer' => 'bank_transfer',
    'wallet_transfe' => 'wallet_transfer'
   
);

global $kycStatus;
$kycStatus  = array(
    'pending' => 'Pending',
    'verify' => 'Verify',
    'completed' => 'Verified',
    'rejected' => 'Rejected',
    'skipped' => 'Skipped',
);


global $months;

$months = [
    'January' => 'Janvier',
    'February' => 'Février',
    'March' => 'Mars',
    'April' => 'Avril',
    'May' => 'Mai',
    'June' => 'Juin',
    'July' => 'Juillet',
    'August' => 'Août',
    'September' => 'Septembre',
    'October' => 'Octobre',
    'November' => 'Novembre',
    'December' => 'Décembre'
];

global $getStateId;

$getStateId = [
    '1' => '1',
    '2' => '8',
    '3' => '2',
    '4' => '9',
    '5' => '3',
    '6' => '4',
    '7' => '5',
    '8' => '6',
    '9' => '7'
];
?>
