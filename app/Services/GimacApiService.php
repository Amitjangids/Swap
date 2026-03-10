<?php
namespace App\Services;
use DB;
use GuzzleHttp\Client;
use App\Services\Service;
use Illuminate\Support\Str;
use App\Models\Issuertrxref;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GimacApiService extends Service
{
    public $client;

    public function __construct()
    {
        $certificate = public_path("MTN Cameroon Issuing CA1.crt");

        $this->client = new Client([
            'verify' => $certificate,
        ]);
    }

    public function getAccessToken()
    {

        try {
            $options = [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => env('GIMAC_CLIENT_ID_TEST'),
                    'client_secret' => env('GIMAC_CLIENT_SECRET_TEST'),
                    'scope' => 'read',
                    'username' => env('GIMAC_USER_NAME_TEST'),
                    'password' => env('GIMAC_PASSWORD_TEST'),
                    'expires_in' => 86400,
                ],
            ];


            // /dd($options['form_params']);
            $response = $this->client->request('POST', env('GIMAC_TOKEN_URL_TEST'), [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => '*/*',
                    'Connection' => 'keep-alive'
                ],
                'form_params' => $options['form_params'],
            ]);

            $body = $response->getBody()->getContents();
            $jsonResponse = json_decode($body);
            $accessToken = $jsonResponse->access_token;
 
            return ['status' => true, 'token' => $accessToken, 'message' => ''];

        } catch (\Throwable $th) {
            //throw $th;
            return ['status' => false, 'token' => null, 'message' => $th->getMessage()];
        }
    }
    public function walletAndAccountInquiry($paymentType, $receiverMobile, $issuertrxref, $tomember, $accessToken, $accountNumber = null)
    {

        try {
            /* Log::channel('GIMAC')->info("walletAndAccountInquiry called with:", [
                'paymentType' => $paymentType,
                'receiverMobile' => $receiverMobile,
                'issuertrxref' => $issuertrxref,
                'tomember' => $tomember,
                'accessToken' => $accessToken,
                'accountNumber' => $accountNumber,
            ]); */
            /*  if ($paymentType == "WALLETTOACCOUNT" || $paymentType == "WALLETTOWALLET" || $paymentType == "MERCHANTPURCHASE" || $paymentType == "MOBILERELOAD" || $paymentType == "PREPAIDCARDRELOAD" ||
                 $paymentType == "PURCHASEVOUCHER" || $paymentType == "REQUESTTOPAY" || $paymentType == "INCACCREMIT"
             )  */
            //  dd($paymentType, $receiverMobile, $issuertrxref, $tomember, $accessToken,$accountNumber);

            /* Log::info("walletAndAccountInquiry called with:", [
                'paymentType' => $paymentType,
                'receiverMobile' => $receiverMobile,
                'issuertrxref' => $issuertrxref,
                'tomember' => $tomember,
                'accessToken' => $accessToken,
                'accountNumber' => $accountNumber,
            ]); */
            if ($paymentType == "WALLETTOWALLET" || $paymentType == "OUTGOINGWALLET" || $paymentType == "MOBILERELOAD" || $paymentType == "REQUESTTOPAY" || $paymentType == "WALLETINCOMMING") {
                $walletDataInquiry = [
                    'intent' => 'account_inquiry',
                    'walletdestination' => $receiverMobile,
                    'issuertrxref' => $issuertrxref,
                    'tomember' => $tomember,
                ];
                Log::info('Request :', ['data' => $walletDataInquiry]);
            } elseif ($paymentType == "WALLETTOACCOUNT" || $paymentType == "ACCOUNTTOWALLET" || $paymentType == "ACCOUNTTOACCOUNT" || $paymentType == "INCACCREMIT" || $paymentType == "PREPAIDCARDRELOAD") {
                $walletDataInquiry = [
                    "intent" => "account_inquiry",
                    "dstaccounts" => [["iden" => $accountNumber, "type" => "ACCOUNT"]],
                    "issuertrxref" => $issuertrxref,
                    "tomember" => $tomember,
                ]; 
                Log::info('Request :', ['data' => $walletDataInquiry]);
            } else {
                return ['status' => true, 'statusCode' => 400, 'data' => null, 'message' => 'Bad Request'];
            }

            
            $responseInquiry = $this->client->request('POST', env('GIMAC_PAYMENT_URL_TEST'), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer $accessToken"
                ],
                'json' => $walletDataInquiry,
            ]); 

            $bodyInquiry = $responseInquiry->getBody()->getContents();
            $jsonInquiry = json_decode($bodyInquiry);
            $statusCodeI = $responseInquiry->getStatusCode();
            Log::info($bodyInquiry);
            // Log::channel('GIMAC')->info("walletAndAccountInquiry called with Response:", ['jsonInquiry' => $jsonInquiry]);

            return ['status' => true, 'statusCode' => $statusCodeI, 'data' => $jsonInquiry, 'message' => ''];
        } catch (\Throwable $th) {
            Log::info($th);
            return ['status' => false, 'statusCode' => null, 'data' => null, 'message' => $th->getMessage()];
        }
    }

    public function walletPayment($timestamp, $sender_mobile, $paymentType, $receviver_mobile, $next_issuertrxref, $total_amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData)
    {

        try {

            if ($paymentType == "WALLETTOWALLET") {
                $data = [
                    "intent" => "mobile_transfer",
                    "createtime" => $timestamp,
                    "walletsource" => $sender_mobile,
                    "walletdestination" => $receviver_mobile,
                    "issuertrxref" => $next_issuertrxref,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember,
                ];
            }

            if (isset($data) && !empty($data)) {

                $response = $this->client->request('POST', env('GIMAC_PAYMENT_URL_TEST'), [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer $accessToken"
                    ],
                    'json' => $data,
                ]);

                $body = $response->getBody()->getContents();
                $jsonResponse2 = json_decode($body);
                $statusCode = $response->getStatusCode();
                return ['status' => true, 'statusCode' => $statusCode, 'data' => $jsonResponse2, 'message' => ''];
            } else {
                return ['status' => false, 'statusCode' => null, 'data' => null, 'message' => 'Enter the valid payment details'];
            }
        } catch (\Exception $e) {
            $response = $e->getResponse();
            $body = $response->getBody();
            $contents = $body->getContents();
            // Now, $contents contains the response body
            $jsonResponse = json_decode($contents, true);

            if ($jsonResponse && isset($jsonResponse['error_description'])) {
                $errorDescription = $jsonResponse['error_description'];
            } else {
                $statusCode = $response->getStatusCode();
                $errorDescription = 'Server Error, Please wait a few minutes before you try again';
                if ($statusCode == '403') {
                    $errorDescription = 'Error Code : 403 Forbidden Error';
                }
            }

            $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
            if ($last_record != "") {
                $next_issuertrxref = $last_record + 1;
            } else {
                $next_issuertrxref = '140061';
            }

            Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);


            return ['status' => false, 'statusCode' => null, 'data' => null, 'message' => $errorDescription];
        } catch (\Throwable $th) {
            return ['status' => false, 'statusCode' => null, 'data' => null, 'message' => $th->getMessage()];
        }

    }

    public function walletPaymentAllType($timestamp, $sender_mobile, $paymentType, $receviver_mobile, $next_issuertrxref, $total_amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData)
    {

        try {
            // dd($next_issuertrxref);
            if ($paymentType == "WALLETTOWALLET") {
                $data = [
                    "intent" => "mobile_transfer",
                    "createtime" => $timestamp,
                    "walletsource" => $sender_mobile,
                    "walletdestination" => $receviver_mobile,
                    "issuertrxref" => $next_issuertrxref,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember,
                ];
            } elseif ($paymentType == "OUTGOINGWALLET") {
                $data = [
                    "intent" => "outg_wal_remit",
                    "createtime" => $timestamp,
                    "walletsource" => $sender_mobile,
                    "walletdestination" => $receviver_mobile,
                    "issuertrxref" => $next_issuertrxref,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember,
                    "sendercustomerdata" => $senderData,
                    "receivercustomerdata" => $receiverData
                ];
            } elseif ($paymentType == "WALLETTOACCOUNT") {
                $data = [
                    "intent" => "wallet_to_account",
                    "creattime" => $timestamp,
                    "walletsource" => $sender_mobile,
                    "dstaccounts" => [
                        [
                            "iden" => $receiverAccount,
                            "type" => "ACCOUNT"
                        ]
                    ],
                    "issuertrxref" => $next_issuertrxref,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember
                ];

            } elseif ($paymentType == "ACCOUNTTOWALLET") {
                $data = [
                    "intent" => "account_to_wallet",
                    "createtime" => $timestamp,
                    "srcaccounts" => [["iden" => $senderAccount, "type" => "ACCOUNT"]],
                    "walletdestination" => $receviver_mobile,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "issuertrxref" => $next_issuertrxref,
                    "tomember" => $tomember
                ];
            } elseif ($paymentType == "ACCOUNTTOACCOUNT") {
                $data = [
                    "intent" => "account_transfer",
                    "createtime" => $timestamp,
                    "issuertrxref" => $next_issuertrxref,
                    "srcaccounts" => [["iden" => $senderAccount, "type" => "ACCOUNT"]],
                    "dstaccounts" => [["iden" => $receiverAccount, "type" => "ACCOUNT"]],
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember
                ];
            }
            /* elseif ($paymentType == "MERCHANTPURCHASE") {
                $data = [
                    "intent" => "merchant_purchase",
                    "createtime" => $timestamp,
                    "walletsource" => $sender_mobile,
                    "walletdestination" => $receviver_mobile,
                    "issuertrxref" => $next_issuertrxref,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember,
                ];
            } */ elseif ($paymentType == "PREPAIDCARDRELOAD") {
                $data = [
                    "intent" => "pp_reload",
                    "createtime" => $timestamp,
                    "issuertrxref" => $next_issuertrxref,
                    "walletsource" => $sender_mobile,
                    "dstaccounts" => [
                            [
                                "iden" => $cardNumber,
                                "type" => "CARD"
                            ]
                        ],
                    "amount" => $total_amount,
                    "currency" => "950"
                ];
            } elseif ($paymentType == "MOBILERELOAD") {
                $data = [
                    "intent" => "mobile_reload",
                    "createtime" => $timestamp,
                    "issuertrxref" => $next_issuertrxref,
                    "sendermobile" => $sender_mobile,
                    "receivermobile" => $receviver_mobile,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember,
                ];
            }
            /* elseif ($paymentType == "PURCHASEVOUCHER") {
                $data = [
                    "intent" => "purchase_voucher",
                    "createtime" => $timestamp,
                    "issuertrxref" => $next_issuertrxref,
                    "walletsource" => $sender_mobile,
                    "receivermobile" => $receviver_mobile,
                    "amount" => $total_amount,
                    "currency" => "950",
                ];
            }  */ elseif ($paymentType == "REQUESTTOPAY") {
                $data = [
                    "intent" => "request_to_pay",
                    "createtime" => $timestamp,
                    "issuertrxref" => $next_issuertrxref,
                    "walletsource" => $sender_mobile,
                    "walletdestination" => $receviver_mobile,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "tomember" => $tomember,
                ]; 
            }elseif ($paymentType == "WALLETINCOMMING") {
                $data = [
                    "intent" => "inc_wal_remit",
                    "createtime" => $timestamp,
                    "issuertrxref" => $next_issuertrxref,
                    "sendermobile" => $sender_mobile,
                    'walletdestination' => $receviver_mobile,
                    "tomember" => $tomember,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "sendercustomerdata" => $senderData,
                    "receivercustomerdata" => $receiverData
                ];
            }elseif ($paymentType == "INCACCREMIT") {
                $data = [
                    "intent" => "inc_acc_remit",
                    "createtime" => $timestamp,
                    "issuertrxref" => $next_issuertrxref,
                    "sendermobile" => $sender_mobile,
                    "dstaccounts" => [
                            [
                                "iden" => $receiverAccount,
                                "type" => "ACCOUNT"
                            ]
                        ],
                    "tomember" => $tomember,
                    "amount" => $total_amount,
                    "currency" => "950",
                    "sendercustomerdata" => $senderData,
                    "receivercustomerdata" => $receiverData
                ];
            } else {
                return ['status' => true, 'statusCode' => null, 'data' => null, 'message' => 'Payment type not found'];
            }

            Log::info($data);
            if (isset($data) && !empty($data)) {
                $response = $this->client->request('POST', env('GIMAC_PAYMENT_URL_TEST'), [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer $accessToken"
                    ],
                    'json' => $data,
                ]);

                $body = $response->getBody()->getContents();
                $jsonResponse2 = json_decode($body);
                $statusCode = $response->getStatusCode();
                // dd($jsonResponse2);
                // Log::channel('GIMAC')->info("walletPayment request :", ['jsonResponse2' => $jsonResponse2, 'statusCode' => $statusCode]);
                return ['status' => true, 'statusCode' => $statusCode, 'data' => $jsonResponse2, 'message' => ''];
            } else {
                return ['status' => false, 'statusCode' => null, 'data' => null, 'message' => 'Enter the valid payment details'];
            }
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody();
                $contents = $body->getContents();
                // Now, $contents contains the response body
                $jsonResponse = json_decode($contents, true);

                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                    $errorDescription = $jsonResponse['error_description'];
                    //Log::channel('GIMAC')->info("Error :", ['statusCode' => $jsonResponse['error_description']]);
                } else {
                    $statusCode = $response->getStatusCode();
                    $errorDescription = 'Server Error, Please wait a few minutes before you try again';
                    if ($statusCode == '403') {
                        $errorDescription = 'Error Code : 403 Forbidden Error';
                    }
                    // Log::channel('GIMAC')->info("Server Error :", ['statusCode' => $statusCode]);
                }

                $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                if ($last_record != "") {
                    $next_issuertrxref = $last_record + 1;
                } else {
                    $next_issuertrxref = '140061';
                }

                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);
                // dd($e->getMessage());
                // Log::channel('GIMAC')->info("Server Error :", ['errorDescription' => $errorDescription]);
                return ['status' => false, 'statusCode' => null, 'data' => null, 'message' => $errorDescription];
            } else {
                // dd($e->getMessage());
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $e->getMessage()]);
                return ['status' => false, 'statusCode' => null, 'data' => null, 'message' => $e->getMessage()];
            }

        }

    }
}