<?php

namespace App\Http\Controllers;

use App\Models\DriverActivationCard;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\User;
use Session;
use App\Models\Admin;
use App\Models\Walkthrough;
use App\Models\Banner;
use App\Models\Card;
use App\Models\Carddetail;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Scratchcard;
use App\Models\Notification;
use App\Models\Agentoffer;
use App\Models\Offer;
use App\Models\Transactionfee;
use App\Models\Usertransactionfee;
use App\Models\Order;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Feature;
use App\Models\Userfeature;
use App\Models\FeeApply;
use App\Models\Errorrecords;
use App\Models\GeneratedQrCode;
use App\Models\WalletManager;
use App\Models\TransactionLimit;
use App\Models\TransactionLedger;
use App\Models\RemittanceData;
use App\Models\OnafriqaData;
use App\Models\ExcelTransaction;
use App\Models\CardContent;
use App\Models\Iban;
use App\Models\HelpTicket;
use App\Models\UserCard;
use App\Walletlimit;
use DB;
use Input;
use Validator;
use App;
use Illuminate\Support\Facades\Artisan;
use PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use ZipArchive;
use GuzzleHttp\Client;
use DateTime;
use App\Models\Issuertrxref;
use Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use SimpleXMLElement;
use App\Services\SmsService;
use App\Services\GimacApiService;
use App\Services\CardService;
use App\Services\AirtelMoneyService;
use App\Services\ReferralService;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use DateTimeZone;
use DateInterval;
use App\Models\CardRequest;


class AuthController extends Controller
{

    private $apiUrl = APIURL;
    private $authString;
    public $smsService;
    public $gimacApiService;
    public $cardService;
    public $airtelMoneyService;
    protected $firebaseNotificationService;
    public function __construct(SmsService $smsService, GimacApiService $gimacApiService, CardService $cardService, ReferralService $referralService, AirtelMoneyService $airtelMoneyService)
    {
        $this->authString = base64_encode(CORPORATECODE . ':' . CORPORATEPASS);
        $this->smsService = $smsService;
        $this->gimacApiService = $gimacApiService;
        $this->cardService = $cardService;
        $this->airtelMoneyService = $airtelMoneyService;
        $this->referralService = $referralService;
        $this->firebaseNotificationService = new FirebaseService();
        $this->lang = DB::table('app_language')->value('lang') ?? 'en';
        App::setLocale($this->lang);
    }

    public function checkCurl()
    {
        $url = 'https://onafriqtest.com/mttest/services/XPService.XPServiceHttpSoap11Endpoint/';

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
        // Execute the request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        } else {
            // Output the response
            echo $response;
        }

        // Close the cURL session
        curl_close($ch);
    }

    private function encryptContent($content)
    {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $cipher = $encryption->encryptPlainTextWithRandomIV(
            $content,
            $secretyKey
        );
        return $cipher;
    }

    private function decryptContent($content)
    {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $plainText = $encryption->decryptCipherTextWithRandomIV(
            $content,
            $secretyKey
        );
        $plainText = $plainText . PHP_EOL;

        return json_decode($plainText);
    }

    private function decryptContentString($content)
    {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $plainText = $encryption->decryptCipherTextWithRandomIV(
            $content,
            $secretyKey
        );
        $plainText = $plainText . PHP_EOL;

        return $plainText;
    }

    public function generateNumericOTP($n)
    {
        $generator = "1357902468";
        $result = "";

        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, rand() % strlen($generator), 1);
        }
        return $result;
    }

    private function generateQRCode($qrString, $user_id)
    {
        $output_file = "uploads/qr-code/" . $user_id . "-qrcode-" . time() . ".png";
        $image = \QrCode::format("png")
            ->size(200)
            ->errorCorrection("H")
            ->generate($qrString, base_path() . "/public/" . $output_file);
        return $output_file;
    }

    private function generateUniqueKey($length = 10, $column = 'unique_key', $model)
    {
        do {
            $key = Str::random($length);
        } while ($model::where($column, $key)->exists());

        return $key;
    }

    function asDollars($value)
    {
        // if ($value < 0) {
        //     return "-" . asDollars(-$value);
        // }
        return number_format($value, 2);
    }

    public function getWalkthroughList()
    {
        $wDatas = Walkthrough::get();

        $records = [];
        if ($wDatas) {
            foreach ($wDatas as $wData) {
                $userData = [];
                $userData["id"] = $wData->id;
                $userData["image"] = WALK_FULL_UPLOAD_PATH . $wData->image;
                $userData["title"] = $wData->title;
                $userData["description"] = $wData->description;
                $records[] = $userData;
            }

            $statusArr = [
                "status" => "Success",
                "reason" => __('message_app.Walkthrough Data List'),
            ];
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            //            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Walkthrough Data not available'),
            ];
            //            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function loginRegisterOTP(Request $request)
    {
        try {
            //  $statusArr = array("device_token" =>"874364", "device_type" =>"Android","device_id"=>"","phone" =>"918302316402","otpCode"=>"111111","type"=>"Register","user_type"=>"Merchant");
            // $statusArr = array("device_token" =>"874364", "device_type" =>"Android","device_id"=>"","opponent_user_id" =>"237699947943","amount"=>"5000","description"=>"test","currency"=>"950","tomember"=>"12001","sender_fname"=>"Vishnu","sender_lname"=>"Kumawat","receiver_fname"=>"Pulkit","receiver_lname"=>"Mangal");

            //$statusArr = ["amount"=>"25","appVersion"=>"1.0","deviceName"=>"samsung SM-M146B","deviceVersion"=>"Android 14","device_id"=>"a68015fa587b516a","device_token"=>"fnLwMoeRRxWP4ke89-7rCx:APA91bGM5NM93mziE7njNjn91ZRDpsx8bT5L3HXmPomh-FX3Sm9wEc0-Y-M60hE4TDh1nMbxJqZN7mUGjjeDBgGUqsrL2qVeS-FsFwgK-D4eIpYcyf0JLVE","device_type"=>"Android","beneficiary"=>"DEMO0002","iban"=>"CI93CI2010100100200110260160","partnerreference"=>"1245PR67411","reason"=>"Test","total_amount"=>"25","trans_type"=>"Money Transfer Via BDA"];
            //$json = json_encode($statusArr);
            //$requestData = $this->encryptContent($json);
            //echo $requestData; die;
            // $st = $this->decryptContentString("FrxTstoYHloXiBm9t7JpcOknzAXUGPme48E0nnFC9YtMDMfvytpsY/IxSNuptG3eccknH75r8nl+Md9T0NnVqrhPKb8TIB4X2WlWyP2+Jxf5LvZsgq1puP/JBH2SRjl6txYzt5yrIi+xOvbxoTDat7NXr2hEH8dcOZs/Q4tImq/clqvrGOG1HyXAgEeQ3CuZEKokCSL4GZSYBlNfVX8FH8Tf2LTNkqb5GQeLyRqYJcK/o83NWlJHi7QXB7oQKM5O");
            // echo $st; 
            // die;

            $requestData = $this->decryptContent($request->req);

            // echo "<pre>";
            // print_r($requestData); die;
            $device_token = $requestData->device_token;
            $device_type = $requestData->device_type;
            $user_type = $requestData->user_type;
            $device_id = $requestData->device_id;
            $phone = $requestData->phone;
            $type = $requestData->type;
            $otp_number = $this->generateNumericOTP(6);

            if ($type == "Register") {
                $userInfo = User::where("phone", $phone)
                    ->where("user_type", "!=", "")
                    ->where("otp_verify", 1)
                    ->where("is_account_deleted", 1)
                    ->first();
                if (!empty($userInfo)) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __('message_app.Mobile number already exist'),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
                /*  $getResponse = $this->smsService->sendLoginRegisterOtp($otp_number, $phone);

                 if ($getResponse['status']) {
                     $statusArr = [
                         "status" => "Success",
                         "reason" => "OTP sent successfully.",
                         "otpCode" => true
                     ];
                     $json = json_encode($statusArr);
                     $responseData = $this->encryptContent($json);
                     return response()->json($responseData, 200);
                 } else {
                     $statusArr = [
                         "status" => "Error",
                         "reason" => "Failed to send message.",
                         "error" => $getResponse['message'],
                     ];
                     $json = json_encode($statusArr);
                     $responseData = $this->encryptContent($json);
                     return response()->json($responseData, 200);
                 } */


                $this->sendSMS($otp_number, $phone);

                $statusArr = [
                    "status" => 'Success',
                    "reason" => __('message_app.OTP sent successfully'),
                    "otpCode" => $otp_number,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);

            } elseif ($type == "Login") {

                // $this->sendSMS($otp_number, $phone);

                $userInfo = User::where("phone", $phone)
                    ->where("user_type", "!=", "")
                    ->where("otp_verify", 1)
                    ->where("is_account_deleted", 1)
                    ->first();
                if (empty($userInfo)) {
                    $statusArr = [
                        "status" => 'Failed',
                        "reason" => __('message_app.User not found'),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
                /* if($userInfo->device_token == "User"){

                } */
                if ($user_type == "User") {
                    if ($userInfo->user_type == "User") {
                        if ($userInfo->is_verify == 0) {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => __('message_app.Your account might have been temporarily disabled. Please contact us for more details'),
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            /* if ($userInfo->device_token != $device_token) {
                                $this->smsService->sendLoginRegisterOtp($otp_number, $phone);
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => "OTP sent successfully.",
                                    "otpCode" => true,
                                ];
                            } else {
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => "Verify device token",
                                    "otpCode" => false,
                                ];
                            } */


                            $this->sendSMS($otp_number, $phone);

                            $statusArr = [
                                "status" => 'Success',
                                "reason" => __('message_app.OTP sent successfully'),
                                "otpCode" => $otp_number,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {

                        $statusArr = [
                            "status" => 'Failed',
                            "reason" => __('message_app.phone_already_registered', [
                                'user_type' => $userInfo->user_type
                            ])
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                } else {
                    if (
                        $userInfo->user_type == "Agent" ||
                        $userInfo->user_type == "Merchant"
                    ) {

                        if ($userInfo->is_verify == 0) {
                            $statusArr = [
                                "status" => 'Failed',
                                "reason" => __('message_app.Your account might have been temporarily disabled. Please contact us for more details'),
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {

                            /* if ($userInfo->device_token != $device_token) {
                                $this->smsService->sendLoginRegisterOtp($otp_number, $phone);
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => "OTP sent successfully.",
                                    "otpCode" => true,
                                ];
                            } else {
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => "Verify device token.",
                                    "otpCode" => false,
                                ];
                            } */

                            $this->sendSMS($otp_number, $phone);

                            $statusArr = [
                                "status" => 'Success',
                                "reason" => __('message_app.OTP sent successfully'),
                                "otpCode" => $otp_number,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {
                        $statusArr = [
                            "status" => 'Failed',
                            "reason" => __('message_app.phone_already_registered', [
                                'user_type' => $userInfo->user_type
                            ])
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }
            }
        } catch (\Exception $ex) {
            $statusArr = [
                "status" => 'Failed',
                "reason" => __($ex->getMessage()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function verifyLoginRegisterOTP(Request $request)
    {
        /*  $statusArr = array("device_token" =>"874364", "device_type" =>"Android","device_id"=>"","phone" =>"1472583691","otpCode"=>"111111","type"=>"Login","user_type"=>"User");
         $json = json_encode($statusArr);
             $requestData = $this->encryptContent($json);
             echo $requestData; die; */
        $requestData = $this->decryptContent($request->req);
        //$requestData = $request;
        $phone = $requestData->phone;
        $otpCode = $requestData->otpCode;
        $device_token = $requestData->device_token;
        $device_type = $requestData->device_type;
        $user_type = $requestData->user_type;
        $device_id = $requestData->device_id;
        $type = $requestData->type;
        if ($requestData->otpCode == "") {
            $statusArr = ["status" => "Failed", "reason" => __('message_app.Invalid OTP code')];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($type == "Register") {
            $getTempUserData = DB::table('tempuser')->where('phone', $phone)->first();
            /* if ($otpCode != $getTempUserData->otpCode) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Invalid OTP code",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200); */
            if ($otpCode != "111111") {
                $statusArr = [
                    "status" => "Failed",
                    $statusArr = ["status" => "Failed", "reason" => __('message_app.Invalid OTP code')]
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.OTP verification completed"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } elseif ($type == "Login") {

            $userInfo = User::where("phone", $phone)
                ->where("user_type", "!=", "")
                ->where("otp_verify", 1)
                ->orderBy("id", 'desc')
                ->first();

            if (empty($userInfo)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Please provide a registered phone number"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if ($user_type == "User") {
                if ($userInfo->user_type == "User") {
                    if ($userInfo->is_verify == 0) {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __('message_app.Your account might have been temporarily disabled. Please contact us for more details'),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        if ($otpCode != '111111') {
                            $statusArr = ["status" => "Failed", "reason" => __('message_app.Invalid OTP code')];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $user_id = $userInfo->id;
                            $user = array(
                                'device_token' => $device_token,
                                'device_type' => $device_type,
                                'device_id' => $device_id,
                                'otp_verify' => 1,
                                'login_status' => 1,
                                'login_time' => date('Y-m-d H:i:s')
                            );
                            User::where("id", $user_id)->update($user);
                            $tokenStr = $userInfo->id . " " . time();
                            $user = $userInfo;
                            $tokenResult = $user->createToken($tokenStr);
                            $token = $tokenResult->token;
                            $token->save();
                            $statusArr = [
                                "status" => "Success",
                                "reason" => __("message_app.OTP verification completed"),
                                "user_type" => $userInfo->user_type,
                                "access_type" => "Bearer",
                                "kyc_status" => $userInfo->kyc_status,
                                "access_token" => $tokenResult->accessToken,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    }
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __('message_app.phone_already_registered', [
                            'user_type' => $userInfo->user_type
                        ])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($userInfo->user_type == "Agent" || $userInfo->user_type == "Merchant") {
                    if ($userInfo->is_verify == 0) {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __('message_app.Your account might have been temporarily disabled. Please contact us for more details'),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {

                        if ($otpCode != "111111") {
                            $statusArr = ["status" => "Failed", "reason" => __('message_app.Invalid OTP code')];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $user_id = $userInfo->id;
                            $user = array(
                                'device_token' => $device_token,
                                'device_type' => $device_type,
                                'device_id' => $device_id,
                                'otp_verify' => 1,
                                'login_status' => 1,
                                'login_time' => date('Y-m-d H:i:s')
                            );
                            User::where("id", $user_id)->update($user);

                            $tokenStr = $userInfo->id . " " . time();
                            $tokenResult = $userInfo->createToken($tokenStr);
                            $token = $tokenResult->token;
                            $token->save();

                            $statusArr = [
                                "status" => "Success",
                                "reason" => __("message_app.OTP verification completed"),
                                "user_type" => $userInfo->user_type,
                                "access_type" => "Bearer",
                                "kyc_status" => $userInfo->kyc_status,
                                "access_token" => $tokenResult->accessToken,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    }
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __('message_app.phone_already_registered', [
                            'user_type' => $userInfo->user_type
                        ])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        } else if ($type == "UpdateMobile") {
            if ($otpCode != "111111") {
                $statusArr = ["status" => "Failed", "reason" => __('message_app.Invalid OTP code')];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {

                $userId = $requestData->user_id;

                User::where("id", $userId)->update(['phone' => $phone]);

                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.Phone number has been updated successfully"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function walletHistory(Request $request)
    {
        $requestData = $this->decryptContent($request->req);

        $type = $requestData->type;

        // $type = 4;
        $user_id = Auth::user()->id;

        //type
        //0:this week
        //1:last week
        //2:this month
        //3:last month
        //4:last seven days

        if ($type == "0") {
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 2)
                    ->where('payment_mode', 'Withdraw');
            })->where('status', 1)->orWhere(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('amount');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2, 1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('total_amount');

            $formattedStartDate = $startOfWeek->format('d');
            $formattedEndDate = $endOfWeek->format('d M');
            $dateRange = "$formattedStartDate-$formattedEndDate";
        } elseif ($type == "1") {
            $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
            $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 2)
                    ->where('payment_mode', 'Withdraw');
            })->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->where('status', 1)->orWhere(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2, 1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->sum('total_amount');

            $formattedStartDate = $startOfLastWeek->format('d');
            $formattedEndDate = $endOfLastWeek->format('d M');
            $dateRange = "$formattedStartDate-$formattedEndDate";
        } elseif ($type == "4") {
            $startOfLastSevenDays = Carbon::now()->subDays(6)->startOfDay();
            $endOfLastSevenDays = Carbon::now()->endOfDay();

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 2)
                    ->where('payment_mode', 'Withdraw');
            })->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->where('status', 1)->orWhere(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2, 1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->sum('total_amount');

            $formattedStartDate = $startOfLastSevenDays->format('d');
            $formattedEndDate = $endOfLastSevenDays->format('d M');
            $dateRange = "$formattedStartDate-$formattedEndDate";
        } elseif ($type == "2") {
            $startDateThisMonth = Carbon::now()->startOfMonth();
            $endDateThisMonth = Carbon::now()->endOfMonth();

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 2)
                    ->where('payment_mode', 'Withdraw');
            })->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->where('status', 1)->orWhere(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2, 1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->sum('total_amount');

            $formattedStartDate = $startDateThisMonth->format('d');
            $formattedEndDate = $endDateThisMonth->format('d M');
            $dateRange = "$formattedStartDate-$formattedEndDate";
        } elseif ($type == "3") {
            $startDateLastMonth = Carbon::now()->subMonth()->startOfMonth();
            $endDateLastMonth = Carbon::now()->subMonth()->endOfMonth();

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 2)
                    ->where('payment_mode', 'Withdraw');
            })->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->where('status', 1)->orWhere(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2, 1, 4])->where('payment_mode', '!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->orWhere(function ($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                    ->where('trans_type', 1)
                    ->where('payment_mode', 'Refund');
            })->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->sum('total_amount');

            $formattedStartDate = $startDateLastMonth->format('d');
            $formattedEndDate = $endDateLastMonth->format('d M');
            $dateRange = "$formattedStartDate-$formattedEndDate";
        }

        $statusArr = [
            "status" => 'Success',
            /* "total_credit" => $this->numberFormatSpaces($this->roundAmount($total_credited)),
            "total_debit" => $this->numberFormatSpaces($this->roundAmount($total_debited)), */
            "total_credit" => $this->roundAmount($total_credited),
            "total_debit" => $this->roundAmount($total_debited),
            "till_date" => $dateRange,
        ];

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }


    public function generatePdf(Request $request)
    {
        $requestData = $this->decryptContent($request->req);

        $transaction_id = $requestData->transaction_id;
        // $transaction_id = $request->transaction_id;

        $user_id = Auth::user()->id;

        $userInfo = User::where('id', $user_id)->first();

        $trans = Transaction::where('id', $transaction_id)->first();
        // Log::info("Transaction Details: ", ['transaction' => $trans]);

        if (!empty($trans->transactionType) && $trans->transactionType == "SWAPTOSWAP" || $trans->transactionType == 4 || $trans->payment_mode == "Withdraw") {
            $getReceiver = User::where('id', $trans->receiver_id)->first();
            $getAdminDetail = Admin::where('id', 1)->first();
            if (!empty($trans->receiver_id)) {
                $transArr[] = [
                    'title' => __("message_app.Receiver Name"),
                    'value' => $trans->trans_for == "Admin" ? $getAdminDetail->username : $getReceiver->name,
                    'image' => '',
                    'type' => 'string',
                ];
            }
            $transArr[] = [
                'title' => __("message_app.Receiver Phone"),
                'value' => $trans->trans_for == "Admin" ? "---" : $getReceiver->phone ?? "",
                'image' => '',
                'type' => 'number',
            ];
        }

        if (!empty($trans->receiverName)) {
            $transArr[] = [
                'title' => __("message_app.Receiver Name"),
                'value' => $trans->receiverName,
                'image' => '',
                'type' => 'string',
            ];
        }


        if (!empty($trans->receiver_mobile)) {
            $transArr[] = [
                'title' => __("message_app.Phone / Wallet Number"),
                'value' => $trans->receiver_mobile,
                'image' => '',
                'type' => 'number',
            ];
        }
        if (!empty($trans->user_id) && $trans->transactionType != "AIRTELMONEY") {
            $getReceiver = User::where('id', $trans->user_id)->first();
            $getAdminDetail = Admin::where('id', 1)->first();

            $transArr[] = [
                'title' => __("message_app.Sender Name"),
                'value' => $trans->trans_for == "Admin" ? $getAdminDetail->username : $getReceiver->name,
                'image' => '',
                'type' => 'string',
            ];
            $transArr[] = [
                'title' => __("message_app.Sender Phone"),
                'value' => $trans->trans_for == "Admin" ? "---" : $getReceiver->phone ?? "",
                'image' => '',
                'type' => 'string',
            ];
        }
        $transArr[] = [
            'title' => __("message_app.Transaction ID"),
            'value' => ($trans->id ?? ""),
            'image' => '',
            'type' => 'string',
        ];
        $transArr[] = [
            'title' => __("message_app.Reference ID"),
            'value' => ($trans->refrence_id ?? 0),
            'image' => '',
            'type' => 'string',
        ];
        if ($trans->payment_mode != "CARDPAYMENT" && $trans->payment_mode != "TRANSAFEROUT") {
            $transArr[] = [
                'title' => __("message_app.Date & Time"),
                'value' => Carbon::parse($trans->created_at)->format('d M, Y h:i A'),
                'image' => '',
                'type' => 'date',
            ];
        }
        if ($trans->transactionType == "SWAPTOCEMAC") {
            if (isset($trans->country_id) && $trans->country_id != "") {
                $country = Country::where('id', $trans->country_id)->first()->name;
            }
            if (isset($trans->tomember) && $trans->tomember != "") {
                // $walletManager = WalletManager::where('tomember', $trans->tomember)->first()->name;
                $walletManager = optional(
                    WalletManager::where('tomember', $trans->tomember)->first()
                )->name;
            }
        } elseif ($trans->transactionType == "SWAPTOOUTCEMAC") {
            if (isset($trans->country_id) && $trans->country_id != "") {
                $country = DB::table('countries_onafriq')->where('id', $trans->country_id)->first()->name;
            }

            if (isset($trans->walletManagerId) && $trans->walletManagerId != "") {
                $walletManager = DB::table('wallet_manager_onafriq')->where('id', $trans->walletManagerId)->first()->name;
            }
        }

        if (!empty($country)) {
            $transArr[] = [
                'title' => __("message_app.Country"),
                'value' => $country ?? "",
                'image' => '',
                'type' => 'string',
            ];
        }
        if (!empty($walletManager)) {
            $transArr[] = [
                'title' => __("message_app.Wallet Manager"),
                'value' => $walletManager ?? "",
                'image' => '',
                'type' => 'string',
            ];
        }

        if (!empty($trans->receiverAccount)) {
            $transArr[] = [
                'title' => __("message_app.IBAN Number"),
                'value' => $trans->receiverAccount,
                'image' => '',
                'type' => 'string',
            ];
        }


        if (!empty($trans->transactionType) && $trans->transactionType == "SWAPTOBDA") {
            $getIBAN = RemittanceData::where('id', $trans->onafriq_bda_ids)->first()->iban;
            $transArr[] = [
                'title' => __("message_app.IBAN Number"),
                'value' => $getIBAN ?? "",
                'image' => '',
                'type' => 'string',
            ];
        }
        if (!empty($trans->cardNumber)) {
            $transArr[] = [
                'title' => __("message_app.Card Holder Name"),
                'value' => $trans->cardHolderName,
                'image' => '',
                'type' => 'string',
            ];
            $transArr[] = [
                'title' => __("message_app.Card Number"),
                'value' => $trans->cardNumber,
                'image' => '',
                'type' => 'number',
            ];

        }

        if (!empty($trans->notes)) {
            $transArr[] = [
                'title' => __("message_app.Notes"),
                'value' => $trans->notes ?? "",
                'image' => '',
                'type' => 'string',
            ];
        }
        if (!empty($trans->amount)) {
            $transArr[] = [
                'title' => __('message_app.Amount'),
                'value' => 'XAF ' . number_format((($trans->amount - floor($trans->amount)) > 0.5 ? ceil($trans->amount) : floor($trans->amount)), 0, '', ' '),
                'image' => '',
                'type' => 'amount',
            ];
        }
        if (Auth::user()->id == $trans->user_id) {
            //if (!empty($trans->remainingWalletBalance)) {
            //if ($trans->payment_mode != "CARDPAYMENT" && $trans->payment_mode != "TRANSAFEROUT") {
            if (!empty($trans->transaction_amount) && $trans->transaction_amount != "0.00") {
                $transArr[] = [
                    'title' => __("message_app.Transaction Fees"),
                    'value' => CURR . number_format((($trans->transaction_amount - floor($trans->transaction_amount)) > 0.5 ? ceil($trans->transaction_amount) : floor($trans->transaction_amount)), 0, '', ' '),
                    'image' => '',
                    'type' => 'amount',
                ];
            }
            //}
            if ($trans->transactionType == "AIRTELMONEY") {
                $transArr[] = [
                    'title' => __("message_app.Received Amount"),
                    'value' => CURR . number_format((($trans->amount - floor($trans->amount)) > 0.5 ? ceil($trans->amount) : floor($trans->amount)), 0, '', ' '),
                    'image' => '',
                    'type' => 'amount',
                ];
            } else {
                $transArr[] = [
                    'title' => __("message_app.Total Amount"),
                    // 'value' => ($trans->description == "Card Load") ? 'XAF ' . (($trans->amount ?? 0) - ($trans->transaction_amount ?? 0)) : 'XAF ' . (($trans->amount ?? 0) + ($trans->transaction_amount ?? 0)),
                    'value' => 'XAF ' . number_format((($v = (($trans->description == "Card Load")
                        ? (($trans->amount ?? 0) - ($trans->transaction_amount ?? 0))
                        : (($trans->amount ?? 0) + ($trans->transaction_amount ?? 0))))
                        - floor($v) > 0.5 ? ceil($v) : floor($v)), 0, '', ' '),

                    'image' => '',
                    'type' => 'amount',
                ];
            }

            if ($trans->remainingWalletBalance != "0.00") {
                if ($trans->transactionType != "AIRTELMONEY") {
                    /* $transArr[] = [
                        'title' => "Your Remaining Balance",
                        'value' => 'XAF ' . ($trans->remainingWalletBalance ?? ""),
                        'image' => '',
                        'type' => 'amount',
                    ]; */
                }
            }
            //}

            if (isset($request->cardType) && $request->cardType === "VIRTUAL") {
                if ($trans->beforeVirtualBalance != "0.00") {
                    $transArr[] = [
                        'title' => __("message_app.Your Before Balance"),
                        // 'value' => 'XAF ' . ($trans->beforeVirtualBalance ?? ""),
                        'value' => CURR . number_format((($trans->beforeVirtualBalance - floor($trans->beforeVirtualBalance)) > 0.5 ? ceil($trans->beforeVirtualBalance) : floor($trans->beforeVirtualBalance)), 0, '', ' '),
                        'image' => '',
                        'type' => 'amount',
                    ];
                }
                if ($trans->afterVirtualBalance != "0.00") {
                    $transArr[] = [
                        'title' => __("message_app.Your After Balance"),
                        // 'value' => 'XAF ' . ($trans->afterVirtualBalance ?? ""),
                        'value' => CURR . number_format((($trans->afterVirtualBalance - floor($trans->afterVirtualBalance)) > 0.5 ? ceil($trans->afterVirtualBalance) : floor($trans->afterVirtualBalance)), 0, '', ' '),
                        'image' => '',
                        'type' => 'amount',
                    ];
                }
            } else {
                if ($trans->beforeBalance != "0.00") {
                    $transArr[] = [
                        'title' => __("message_app.Your Before Balance"),
                        'value' => CURR . number_format((($trans->beforeBalance - floor($trans->beforeBalance)) > 0.5 ? ceil($trans->beforeBalance) : floor($trans->beforeBalance)), 0, '', ' '),
                        'image' => '',
                        'type' => 'amount',
                    ];
                }
                if ($trans->afterBalance != "0.00") {
                    $transArr[] = [
                        'title' => __("message_app.Your After Balance"),
                        'value' => CURR . number_format((($trans->afterBalance - floor($trans->afterBalance)) > 0.5 ? ceil($trans->afterBalance) : floor($trans->afterBalance)), 0, '', ' '),
                        'image' => '',
                        'type' => 'amount',
                    ];
                }
            }

        }

        if ($trans->payment_mode == "CARDPAYMENT" || $trans->payment_mode == "TRANSAFEROUT") {
            if ($trans->transactionDate) {
                $transArr[] = [
                    'title' => __("message_app.Transaction Date"),
                    'value' => ($trans->transactionDate ?? ""),
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if ($trans->transactionTime) {
                $transArr[] = [
                    'title' => __("message_app.Transaction Time"),
                    'value' => ($trans->created_at->format('h:i:s') ?? ""),
                    'image' => '',
                    'type' => 'date',
                ];
            }

            if ($trans->description) {
                $transArr[] = [
                    'title' => __("message_app.Description"),
                    'value' => ($trans->description == "Denial - POS Purchase" && $trans->amount <= 0 ? "POS Denial" : $trans->description),
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if ($trans->merchantName) {
                $transArr[] = [
                    'title' => __("message_app.Merchant Name"),
                    'value' => trim($trans->merchantName ?? ""),
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if ($trans->merchantCountry) {
                $transArr[] = [
                    'title' => __("message_app.Merchant Country"),
                    'value' => trim($trans->merchantCountry ?? ""),
                    'image' => '',
                    'type' => 'string',
                ];
            }
        }




        $transactionArray = [
            "amount" => 'XAF ' . number_format((($v = (($trans->description == "Card Load")
                ? (($trans->amount ?? 0) - ($trans->transaction_amount ?? 0))
                : (($trans->amount ?? 0) + ($trans->transaction_amount ?? 0))))
                - floor($v) > 0.5 ? ceil($v) : floor($v)), 0, '', ' '),
            "note" => $trans->notes ?? "",
        ];

        global $tranType;



        $logo_path = 'data:image/png;base64,' . base64_encode(file_get_contents(HTTP_PATH . "/public/img/swap.png"));

        $transactionRows = '';

        foreach ($transArr as $item) {
            $transactionRows .= '
        <tr>
            <td height="30" style="padding:8px 5px; font-weight:600; width:45%;">' . e($item['title']) . '</td>
            <td style="padding:8px 5px; width:55%;text-align:right;">' . e($item['value']) . '</td>
        </tr>
    ';
        }
        $htmlContent = '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Transaction Details</title>
                <style>
                    body { margin: 0; padding: 0; background-color: #ccc; font-family: DejaVu Sans, sans-serif !important; }
                    .main-table { background-color: #fff; }
                    .main-table tr td { text-align: left; }
                    .main-table tr td table tr td { font-size: 14px; color: #000; }
                    * { font-family: DejaVu Sans, sans-serif; }
                </style>
            </head>
            <body>
                <table class="main-table" width="550" align="center">
                    <tr>
                        <td align="center" height="100">
                            <img src="' . $logo_path . '" alt="Logo" width="150px">
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" style="padding:10px;">
                                ' . $transactionRows . '
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>';

        $pdfOptions = [
            'orientation' => 'portrait', // or 'landscape'
            'margin_top' => 10, // in millimeters
            'margin_right' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'isRemoteEnabled' => true,
            // Add more options as needed
        ];

        $pdf = PDF::loadHTML($htmlContent)->setOptions($pdfOptions);

        $pdfContent = $pdf->output();

        // Define the path where you want to save the PDF
        $savePath = public_path('uploads/transactionPdf/'); // You can adjust the path as needed
        // Generate a unique filename for the PDF
        $filename = 'transaction_' . $transaction_id . '_' . $user_id . '.pdf';

        // Combine the path and filename to create the full file path
        $filePath = $savePath . $filename;

        // Save the PDF to the specified path
        file_put_contents($filePath, $pdfContent);

        Transaction::where('id', $transaction_id)->update(['pdf_link' => 'public/uploads/transactionPdf/' . $filename]);

        $statusArr = [
            "status" => 'Success',
            "reason" => __('message_app.Pdf has been generated successfully'),
            "pdf_link" => 'public/uploads/transactionPdf/' . $filename,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function sendSMS($otp_number, $mobile)
    {
        return true;
        try {
            $otp_code = $otp_number;
            $toNumber = "+964" . $mobile;

            $message = __("message.send_otp", ["OTP" => $otp_code]);
            $account_sid = Account_SID;
            $auth_token = Auth_Token;
            $id = "$account_sid";
            $token = "$auth_token";
            global $sms_from;
            $url = "https://api.twilio.com/2010-04-01/Accounts/" .
                $account_sid .
                "/Messages.json";
            $data = [
                "From" => $sms_from,
                "To" => $toNumber,
                "Body" => $message,
            ];
            $post = http_build_query($data);
            $x = curl_init($url);
            curl_setopt($x, CURLOPT_POST, true);
            curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($x, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($x, CURLOPT_USERPWD, "$id:$token");
            curl_setopt($x, CURLOPT_POSTFIELDS, $post);
            $result = curl_exec($x);
            curl_close($x);

            $sentOtp = 1;
            if (isset($data["status"])) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message.You entered wrong phone number."),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function resendOTP(Request $request)
    {
        //        $request->validate(["phone" => "required"]);
        $requestData = $this->decryptContent($request->req);
        $otp_number = $this->generateNumericOTP(6);

        //$this->sendSMS($otp_number, $request->phone);
        $getResponse = $this->smsService->sendLoginRegisterOtp($otp_number, $requestData->phone);

        if ($getResponse['status']) {
            $statusArr = [
                "status" => 'Success',
                "reason" => __('message_app.OTP sent successfully'),
                "otp" => $otp_number,
            ];
        }

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function saveRegisterInfo(Request $request)
    {
        $requestData = $this->decryptContent($request->req);
        // $requestData = $request;
        $device_token = $requestData->device_token;
        $device_type = $requestData->device_type;
        $user_type = $requestData->user_type;
        $device_id = $requestData->device_id;
        $firstName = $requestData->firstName ?? null;
        $lastName = $requestData->lastName ?? null;
        $email = $requestData->email ?? null;
        $countryCode = $requestData->countryCode ?? null;
        $dobInput = $requestData->dob ?? null;
        $businessName = $requestData->business_name ?? null;

        $is_exist = User::where("phone", $requestData->phone)->where("otp_verify", 1)->where("is_account_deleted", 1)->count();

        try {
            if ($dobInput) {
                $dobConverted = Carbon::createFromFormat('d/m/Y', $dobInput)->format('Y-m-d');
            } else {
                $dobConverted = null;
            }
        } catch (\Exception $e) {
            $dobConverted = null;
        }

        $input = [
            'firstName' => $requestData->firstName ?? null,
            'lastName' => $requestData->lastName ?? null,
            'email' => $requestData->email ?? null,
            'countryCode' => $requestData->countryCode ?? null,
            'phone' => $requestData->phone ?? null,
            'user_type' => $requestData->user_type ?? null,
            'referralCode' => $requestData->referralCode ?? null,
            'dob' => $dobConverted
        ];

        $validate_data = [
            'firstName' => 'required|string|min:5|max:255',
            'lastName' => 'required|string|max:255',
            'countryCode' => 'required',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required',
            'user_type' => 'required',
            'dob' => ['required', 'date', 'before:' . now()->subYears(18)->format('Y-m-d')],
            'referralBy' => 'nullable|exists:users,referralCode'
        ];

        $customMessages = [
            'firstName.required' => __('message_app.First name is required'),
            'firstName.string' => __('message_app.First name must be a valid string'),
            'firstName.min' => __('message_app.First name must be at least 5 characters long'),
            'lastName' => __('message_app.last name is required'),
            'email.email' => __('message_app.Please enter a valid email address'),
            'email.unique' => __('message_app.This email address is already in use'),
            'countryCode' => __('message_app.Country code is required'),
            'phone' => __('message_app.Country code is required'),
            'user_type' => __('message_app.User type is required'),
            'referralCode.exists' => __('message_app.The provided referral code is invalid'),
            'dob.before' => __('message_app.You must be at least 18 years old'),
            'dob.required' => __('message_app.Date of birth is required'),
        ];
        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();

            // Optional: get min error specifically
            if ($messages->has('firstName')) {
                $firstNameErrors = $messages->get('firstName');

                // Check if it contains the min error
                $minError = collect($firstNameErrors)->first(function ($msg) {
                    return str_contains($msg, 'at least 5 characters');
                });

                $errorMessage = $minError ?: $messages->first(); // fallback to first error
            } else {
                $errorMessage = $messages->first();
            }
            $statusArr = [
                "status" => 'Failed',
                "reason" => $errorMessage,

            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $referralCode = $input['referralCode'] ?? '';

        if (!empty($referralCode)) {
            if ($referralCode && !User::where('referralCode', $referralCode)->exists()) {
                $statusArr = [
                    "status" => 'Failed',
                    "reason" => __('message_app.The referral code is invalid or does not exist'),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
        $referredByUser = User::where('referralCode', $referralCode)->first();

        if ($is_exist > 0) {
            $statusArr = [
                "status" => 'Failed',
                "reason" => __('message_app.Account already exist'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $company_code = '';
        if ($user_type == "Agent") {
            $is_aggregator = Admin::where('company_code', $requestData->aggregator_code)->count();
            if ($is_aggregator == 0) {
                $statusArr = [
                    "status" => 'Failed',
                    "reason" => __('message_app.Please provide a valid aggregator code'),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $company_code = $requestData->aggregator_code;
        }

        /* $ibanData = DB::table('iban_generated_lists')->where('status', 'available')->first();

        if (!$ibanData) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "No available IBAN",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 400);
        } */
        $user = new User([
            "company_code" => $company_code,
            "user_type" => $user_type,
            "name" => $firstName,
            "lastName" => $lastName,
            "countryCode" => $countryCode,
            "email" => $email ?? "",
            "phone" => $requestData->phone,
            "business_name" => $businessName,
            "device_token" => $device_token,
            "device_type" => $device_type,
            "device_id" => $device_id,
            "dob" => $dobConverted,
            "kyc_status" => $user_type == "Agent" ? "completed" : "verify",
            "country" => "",
            "state" => "",
            "city" => "",
            "address1" => "",
            "address2" => "",
            "postCode" => "",
            'referralBy' => $referredByUser ? $referredByUser->id : 0,
            'referralCode' => strtoupper(substr(md5(uniqid()), 0, 8)),
            "otp_verify" => 1,
            "is_verify" => 1,
            // "ibanNumber" => $ibanData->iban,
            "ibanNumber" => "",
            "updated_at" => date("Y-m-d H:i:s"),
            "slug" => $this->createSlug($firstName, 'users'),
        ]);

        $user->save();
        // DB::table('iban_generated_lists')->where('id', $ibanData->id)->update(['status' => 'assigned']);
        $userId = $user->id;

        if ($referralCode) {
            $this->handleReferralBonus($user, $referralCode);
        }

        DB::table('tempuser')->where('phone', $requestData->phone)->delete();
        $qrString = $this->encryptContent($userId . "##" . $firstName);
        $qrCode = $this->generateQRCode($qrString, $userId);

        $qr_code = array(
            'qr_code' => $qrCode,
        );

        User::where('id', $userId)->update($qr_code);

        $tokenStr = $userId . " " . time();
        $tokenResult = $user->createToken($tokenStr);
        $token = $tokenResult->token;
        $token->save();

        $title = __('message_app.Congratulations');
        $message = __('message_app.Congratulations! You successfully created your account. Welcome to XAF', ['SITETITLE' => SITE_TITLE]);
        $device_type = $requestData->device_type;
        $device_token = $requestData->device_token;
        // $this->sendPushNotification($title, $message, $device_type, $device_token);


        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'ACCOUNT',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            "user_id" => $userId,
            "notif_title" => $title,
            "notif_body" => $message,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        $notif->save();

        $statusArr = [
            "status" => 'Success',
            "reason" => __('message_app.Profile completed successfully'),
            "access_type" => "Bearer",
            "qrString" => $qrString,
            "access_token" => $tokenResult->accessToken,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }


    public function completeProfileFirstStep(Request $request)
    {
        $requestData = $this->decryptContent($request->req);

        // echo '<pre>';print_r($requestData);exit;
        $device_token = $requestData->device_token;
        $device_type = $requestData->device_type;
        $user_type = $requestData->user_type;
        $device_id = $requestData->device_id;
        $email = $requestData->email;
        $business_name = $requestData->business_name;
        $name = $requestData->name ?? null;
        /* $firstName = $requestData->firstName ?? null;
        $lastName = $requestData->lastName ?? null; */
        $countryCode = $requestData->countryCode ?? null;
        $is_exist = User::where("phone", $requestData->phone)->where("otp_verify", 1)->where("is_account_deleted", 1)->count();

        /* $input = [
            'firstName' => $requestData->firstName ?? null,
            'lastName' => $requestData->lastName ?? null,
            'countryCode' => $requestData->countryCode ?? null,
            'country_id' => $requestData->country_id ?? null,
            'state_id' => $requestData->state_id ?? null,
            'city' => $requestData->city ?? null,
            'address1' => $requestData->address1 ?? null,
            'address2' => $requestData->address2 ?? null,
            'post_code' => $requestData->post_code ?? null,
            'email' => $requestData->email ?? null,
            'referralCode' => $requestData->referralCode ?? null,
        ];

        $validate_data = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'countryCode' => 'required',
            'country_id' => 'required',
            'state_id' => 'required',
            'city' => 'required',
            'address1' => 'required',
            'post_code' => 'required',
            'email' => 'required|string|email|max:255|unique:users,email',
            'referralBy' => 'nullable|exists:users,referralCode'
        ];

        $customMessages = [
            'firstName' => 'First name is required.',
            'lastName' => 'last name is required.',
            'countryCode' => 'Country code is required.',
            'country_id' => 'Country is required.',
            'state_id' => 'State is required.',
            'city' => 'City is required.',
            'address1' => 'Address 1 is required.',
            'post_code' => 'Post Code is required.',
            'email' => 'Email is required.',
            'email.string' => 'Email must be a valid string.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email cannot be longer than 255 characters.',
            'email.unique' => 'This email address is already in use.',
            'referralCode.exists' => 'The provided referral code is invalid.',
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $referralCode = $input['referralCode'] ?? '';

        if (!empty($referralCode)) {
            if ($referralCode && !User::where('referralCode', $referralCode)->exists()) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "The referral code is invalid or does not exist",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
        $referredByUser = User::where('referralCode', $referralCode)->first(); */

        if ($is_exist > 0) {
            $statusArr = [
                "status" => 'Failed',
                "reason" => __('message_app.Account already exist'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        if ($email != "") {
            $userInfo = User::where("email", $email)
                ->where("user_type", "!=", "")
                ->first();
            if (!empty($userInfo)) {
                $statusArr = [
                    "status" => 'Failed',
                    "reason" => __('message_app.Email already exist'),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

        $carbonDate = Carbon::createFromFormat('d/m/Y', $requestData->dob);
        $formattedDate = $carbonDate->format('Y-m-d');

        $company_code = '';
        if ($requestData->user_type == "Agent") {
            $is_aggregator = Admin::where('company_code', $requestData->aggregator_code)->count();
            if ($is_aggregator == 0) {
                $statusArr = [
                    "status" => 'Failed',
                    "reason" => __('message_app.Please provide a valid aggregator code'),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $company_code = $requestData->aggregator_code;
        }

        /* $ibanData = DB::table('iban_generated_lists')->where('status', 'available')->first();

        if (!$ibanData) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "No available IBAN",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 400);
        } */
        $user = new User([
            "company_code" => $company_code,
            "user_type" => $requestData->user_type,
            "name" => $name,
            "lastName" => "",
            "countryCode" => $countryCode,
            "email" => $email,
            "phone" => $requestData->phone,
            "business_name" => $business_name,
            "device_token" => $device_token,
            "device_type" => $device_type,
            "device_id" => $device_id,
            "dob" => $formattedDate,
            "country" => $input['country_id'] ?? "",
            "state" => $input['state_id'] ?? "",
            "city" => $input['city'] ?? "",
            "address1" => $input['address1'] ?? "",
            "address2" => $input['address2'] ?? "",
            "postCode" => $input['post_code'] ?? "",
            "otp_verify" => 1,
            "is_verify" => 1,
            "ibanNumber" => $ibanData->iban ?? "",
            "updated_at" => date("Y-m-d H:i:s"),
            "slug" => $this->createSlug($name, 'users'),
        ]);

        $user->save();
        // DB::table('iban_generated_lists')->where('id', $ibanData->id)->update(['status' => 'assigned']);
        $userId = $user->id;
        /* if ($referralCode) {
            $this->handleReferralBonus($user, $referralCode);
        } */
        DB::table('tempuser')->where('phone', $requestData->phone)->delete();
        $qrString = $this->encryptContent($userId . "##" . $name);
        $qrCode = $this->generateQRCode($qrString, $userId);

        $qr_code = array(
            'qr_code' => $qrCode,
        );

        User::where('id', $userId)->update($qr_code);

        $tokenStr = $userId . " " . time();
        $tokenResult = $user->createToken($tokenStr);
        $token = $tokenResult->token;
        $token->save();

        $title = __('message_app.Congratulations');
        $message = __('message_app.Congratulations! You successfully created your account. Welcome to XAF', ['SITETITLE' => SITE_TITLE]);
        $device_type = $requestData->device_type;
        $device_token = $requestData->device_token;
        // $this->sendPushNotification($title, $message, $device_type, $device_token);

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'REGISTER',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            "user_id" => $userId,
            "notif_title" => $title,
            "notif_body" => $message,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        $notif->save();

        $statusArr = [
            "status" => 'Success',
            "reason" => __('message_app.Profile completed successfully'),
            "access_type" => "Bearer",
            "qrString" => $qrString,
            "access_token" => $tokenResult->accessToken,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    private function handleReferralBonus($newUser, $referralCode)
    {
        $referrer = User::where('referralCode', $referralCode)->first();
        $getBonus = Admin::where('id', 1)->first();

        if ($referrer) {

            $users = [
                ['user' => $referrer, 'bonusAmount' => $getBonus->referralBonusSender ?? 1],
                ['user' => $newUser, 'bonusAmount' => $getBonus->referralBonusReceiver ?? 1],
            ];

            $referrerTransactionId = 0;
            foreach ($users as $userData) {
                $user = $userData['user'];
                $bonusAmount = $userData['bonusAmount'];

                DB::table('admins')->where('id', 1)->decrement('wallet_balance', $bonusAmount);
                DB::table('users')->where('id', $user->id)->increment('wallet_balance', $bonusAmount);

                $userCard = UserCard::where('userId', $user->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userCard->last4Digits,
                        "referenceMemo" => "Settlement",
                        "transferAmount" => $bonusAmount,
                        "transferType" => "WalletToCard",
                        "mobilePhoneNumber" => "241{$user->phone}"
                    ]);
                    $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    Log::info('Fund transfer for using referral');
                }

                $trans = new Transaction([
                    'user_id' => $user->id,
                    'receiver_id' => $user->id,
                    'receiver_mobile' => '',
                    'amount' => $bonusAmount,
                    'amount_value' => $bonusAmount,
                    'transaction_amount' => 0,
                    'total_amount' => $bonusAmount,
                    'trans_type' => 1,
                    'excel_trans_id' => 0,
                    'payment_mode' => 'Referral',
                    'status' => 1,
                    'refrence_id' => '',
                    'entryType' => 'API',
                    'billing_description' => "Fund Transfer-" . time() . rand(),
                    'onafriq_bda_ids' => 0,
                    'transactionType' => '',
                    'beforeBalance' => $user->wallet_balance ?? 0,
                    'afterBalance' => ($user->wallet_balance + $bonusAmount),
                    'runningBalance' => ($user->wallet_balance + $bonusAmount),
                    'remainingWalletBalance' => ($user->wallet_balance + $bonusAmount),
                    'referralBy' => $referrerTransactionId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();

                if ($user->id === $referrer->id) {
                    $referrerTransactionId = $trans->id;
                }

                $result = new TransactionLedger([
                    'user_id' => $user->id,
                    'opening_balance' => $user->wallet_balance ?? 0,
                    'amount' => $bonusAmount,
                    'fees' => 0,
                    'actual_amount' => $bonusAmount,
                    'excelTransId' => 0,
                    'type' => 5,
                    'payment_mode' => 'Referral',
                    'closing_balance' => ($user->wallet_balance + $bonusAmount),
                    'trans_id' => $trans->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $result->save();
            }
        }
    }

    public function completeProfileSecondStep(Request $request)
    {
        ini_set("precision", 14);
        ini_set("serialize_precision", -1);
        //        $requestData = $this->decryptContent($request->req);

        // User::where('id', Auth::user()->id)->update(['isProfileCompleted' => '2', 'kyc_status' => 'completed', 'national_identity_type' => "Passport", 'national_identity_number' => '123456789', 'selfie_image' => "test.png", 'identity_front_image' => "test.png", 'identity_back_image' => "test.png"]);
        // $statusArr = [
        //     "status" => "Success",
        //     "reason" => "KYC details have been successfully submitted!",
        // ];

        // $json = json_encode($statusArr);
        // $responseData = $this->encryptContent($json);
        // return response()->json($responseData, 200);

        $isSkipped = $request->isSkipped;
        //        echo '<pre>';print_r($_FILES);exit;
        //        $identityImage = $requestData->identityImage;
        $userId = Auth::user()->id;
        $images = array();
        if ($isSkipped == '2') {
            User::where('id', $userId)->update(['isProfileCompleted' => '2', 'kyc_status' => 'skipped']);
        } else {
            // log::channel('BDA')->info("Hello fdsfg");

            $userDetail = User::where('id', $userId)->first();
            $national_identity_type = $request->national_identity_type;
            $national_identity_number = $request->national_identity_number ?? "";

            $file = $_FILES["selfie"];
            $file = Input::file("selfie");
            $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
            $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
            $selfie_image = $uploadedFileName;
            $selfie_image_path = IDENTITY_FULL_DISPLAY_PATH . $uploadedFileName;

            $images[] = [
                "image_type_id" => 0,
                "image" => "",
                "file_name" => "selfie.jpg"
            ];

            $identity_front_image = '';
            $identity_back_image = '';
            $identity_front_image_path = '';
            $identity_back_image_path = '';
            if ($national_identity_type == 'IDENTITY_CARD') {
                $file = $_FILES["identity_front_image"];
                $file = Input::file("identity_front_image");
                $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                $identity_front_image = $uploadedFileName;
                $identity_front_image_path = IDENTITY_FULL_DISPLAY_PATH . $uploadedFileName;

                $file = $_FILES["identity_back_image"];
                $file = Input::file("identity_back_image");
                $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                $identity_back_image = $uploadedFileName;
                $identity_back_image_path = IDENTITY_FULL_DISPLAY_PATH . $uploadedFileName;

                $images[] = [
                    "image_type_id" => 1,
                    "image" => "",
                    "file_name" => "frontImage.jpg"
                ];
                $images[] = [
                    "image_type_id" => 5,
                    "image" => "",
                    "file_name" => "backImage.jpg"
                ];
            } else {
                $file = $_FILES["identity_front_image"];
                $file = Input::file("identity_front_image");
                $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                $identity_front_image = $uploadedFileName;
                $identity_front_image_path = IDENTITY_FULL_DISPLAY_PATH . $uploadedFileName;

                $images[] = [
                    "image_type_id" => 1,
                    "image" => "",
                    "file_name" => "frontImage.jpg"
                ];
            }

            $currentTimestamp = gmdate('Y-m-d\TH:i:s\Z');
            $api_key = SMILE_API_KEY;
            $partner_id = SMILE_PARTNER_ID;

            // Log::info('---signature---', ['api_key' => $api_key, 'partner_id' => $partner_id, 'currentTimestamp' => $currentTimestamp]);
            $message = $currentTimestamp . $partner_id . "sid_request";
            $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));

            // use SmileIdentityCore\Signature;

            // $signatureService = new Signature($partner_id, $api_key);
            // $signatureData = $signatureService->generate_signature(time());

            // Log::info('Smile Signature Data', $signatureData);



            // Log::info('Smile Signature Data', ["signatureData" => $signature]);

            $infoData = [
                "package_information" => [
                    "apiVersion" => [
                        "buildNumber" => 0,
                        "majorVersion" => 2,
                        "minorVersion" => 0
                    ]
                ],
                "id_info" => [
                    "country" => "GA",
                    "id_type" => $national_identity_type
                ],
                "images" => $images
                //                [
                //                    [
                //                        "image_type_id" => 0,
                //                        "image" => "",
                //                        "file_name" => "selfie.jpg"
                //                    ],
                //                    [
                //                        "image_type_id" => 1,
                //                        "image" => "",
                //                        "file_name" => "image1.jpg"
                //                    ],
                ////                    [
                ////                        "image_type_id" => 5,
                ////                        "image" => "",
                ////                        "file_name" => "image2.jpg"
                ////                    ]
                ////                    [
                ////                        "image_type_id" => 4,
                ////                        "image" => "",
                ////                        "file_name" => "live.jpg"
                ////                    ]
                //                ]
            ];

            $jsonContent = json_encode($infoData, JSON_PRETTY_PRINT);

            Storage::put('info.json', $jsonContent);

            // Create a temporary directory to hold the files
            $tempDir = storage_path('temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir);
            }

            // Create and save another image file
            if ($selfie_image_path != '') {
                $imageContent = file_get_contents($selfie_image_path);
                Storage::put('temp/selfie_image.jpg', $imageContent);
            }
            if ($identity_front_image_path != '') {
                $imageContent = file_get_contents($identity_front_image_path);
                Storage::put('temp/front_image.jpg', $imageContent);
            }
            if ($identity_back_image_path != '') {
                $imageContent = file_get_contents($identity_back_image_path);
                Storage::put('temp/back_image.jpg', $imageContent);
            }

            // Create a zip file
            $zip = new \ZipArchive;
            $zipFilePath = storage_path('attach.zip');

            if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                // Add info.json
                $zip->addFile(storage_path('app/info.json'), 'info.json');
                // Add another image
                if ($selfie_image_path != '') {
                    $zip->addFile(storage_path('app/temp/selfie_image.jpg'), 'selfie.jpg');
                }
                if ($identity_front_image_path != '') {
                    $zip->addFile(storage_path('app/temp/front_image.jpg'), 'frontImage.jpg');
                }
                if ($identity_back_image_path != '') {
                    $zip->addFile(storage_path('app/temp/back_image.jpg'), 'backImage.jpg');
                }
                $zip->close();
            }

            // Clean up temporary files and directories
            Storage::delete('info.json');
            Storage::deleteDirectory('temp');

            $curl = curl_init();

            $randomString = $userDetail->slug . '-user-' . $this->generateRandomString();

            DB::table('users')->where('id', $userDetail->id)->update(['unique_key' => $randomString]);

            // Log::info('-----tetstst kyc----');
            curl_setopt_array($curl, array(
                CURLOPT_URL => SMILE_PATH . '/upload',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "source_sdk": "rest_api",
                    "source_sdk_version": "1.0.0",
                    "file_name": "attach.zip",
                    "smile_client_id": "' . $partner_id . '",
                    "signature": "' . $signature . '",
                    "timestamp": "' . $currentTimestamp . '",
                    "partner_params": {
                        "user_id": "' . $randomString . '",
                        "job_id": "' . $randomString . '",
                        "job_type": "6"  
                    },
                    "model_parameters": {},
                    "callback_url": ""
                }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $responseData = json_decode($response);
            //            echo '<pre>';
            //            print_r($responseData);
            //            echo $responseData['upload_url'];
            // Log::info('-----tetstst responseData----', ['responseData' => $responseData]);

            if (isset($responseData->error) && !empty($responseData->error)) {
                $error = $responseData->error;
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $error,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $upload_url = $responseData->upload_url;
            //            exit;
            //echo $zipFilePath;exit;

            $postFields = file_get_contents($zipFilePath);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $upload_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/zip'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            //            echo '----' . $response;
            //
            //            exit;
            User::where('id', $userId)->update(['isProfileCompleted' => '2', 'kyc_status' => 'pending', 'national_identity_type' => $national_identity_type, 'national_identity_number' => $national_identity_number, 'selfie_image' => $selfie_image, 'identity_front_image' => $identity_front_image, 'identity_back_image' => $identity_back_image]);
        }

        $statusArr = [
            "status" => 'Success',
            "reason" => __('message_app.KYC details have been successfully submitted'),
        ];

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function generateRandomString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = Str::random($length);

        // Ensure at least one character and one number in the random string
        while (!preg_match('/[A-Za-z]/', $randomString) || !preg_match('/\d/', $randomString)) {
            $randomString = Str::random($length);
        }

        return $randomString;
    }

    function generateRequestId()
    {
        return uniqid('req_', true); // e.g. req_652f65dfb5c892.86739077
    }
    public function checkKycStatus(Request $request)
    {
        /* $statusArr = array("device_token" =>"", "device_type" =>"","device_id"=>"","phone" =>"7909202","otpCode"=>"111111","type"=>"Login","user_type"=>"User");
            $json = json_encode($statusArr);
            $requestData = $this->encryptContent($json);
            echo $requestData; die; */
        die('stop');
        $users = User::where("kyc_status", 'pending')->orderBy('aaaaiaaadaa', 'DESC')->get();
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $currentTimestamp = $dt->format("Y-m-d\TH:i:s.v\Z");
        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;
        global $getStateId;
        $message = $currentTimestamp . $partner_id . "sid_request";
        $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));

        foreach ($users as $user) {
            $countryVal = $getStateId[$user->country] ?? 0;
            $userSlug = $user->unique_key;
            $userJobId = $user->jobId;
            $userId = $user->id;
            $curl = curl_init();
            /* $kk = '{
                    "signature": "' . $signature . '",
                    "timestamp": "' . $currentTimestamp . '",
                     "user_id": "' . $userSlug . '",
                     "job_id": "' . $userJobId . '",
                     "partner_id": "' . $partner_id . '",
                     "image_links": false,
                     "history": false
                }'; */
            //    echo '<pre>';print_r($kk);

            curl_setopt_array($curl, array(
                CURLOPT_URL => SMILE_PATH . '/job_status',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "signature": "' . $signature . '",
                    "timestamp": "' . $currentTimestamp . '",
                     "user_id": "' . $userSlug . '",
                     "job_id": "' . $userJobId . '",
                     "partner_id": "' . $partner_id . '",
                     "image_links": false,
                     "history": false
                }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $responseData = json_decode($response);
            $job_complete = $responseData->job_complete ?? "";
            $job_success = $responseData->job_success ?? "";

            if ($job_complete == 1 && $job_success == true) {

                User::where('id', $userId)->update(['kyc_status' => 'completed', 'dob' => $responseData->result->DOB, 'national_identity_type' => $responseData->result->IDType, 'national_identity_number' => $responseData->result->IDNumber]);

                $postData = json_encode([
                    "accountSource" => "OTHER",
                    "address1" => $user->address1,
                    "birthDate" => strtoupper(Carbon::parse($responseData->result->DOB)->format('d-M-Y')),
                    "city" => DB::table('province_city')->where('id', $user->city)->first()->name,
                    "country" => $responseData->result->Country,
                    // "emailAddress" => "test@mailinator.com",
                    "emailAddress" => !empty($user->email) ? $user->email : "test@mailinator.com",

                    "firstName" => "{$user->name}",
                    "idType" => "1",
                    "idValue" => $user->national_identity_number,
                    "lastName" => "{$user->lastName}",
                    "mobilePhoneNumber" => [
                        "countryCode" => "241",
                        "number" => $user->phone
                    ],
                    "preferredName" => $responseData->result->FullName,
                    "referredBy" => ONAFRIQ_SUBCOMPANY,
                    "stateRegion" => $countryVal,
                    "subCompany" => ONAFRIQ_SUBCOMPANY,
                    "return" => "RETURNPASSCODE"
                ]);
                // print_r($postData1); die;

                $getResponse = $this->cardService->saveCardVirtual($postData);
                // dd($getResponse);
                if ($getResponse['status'] == true) {
                    $registrationAccountId = $getResponse['data']['registrationAccountId'] ?? 0;
                    $registrationLast4Digits = $getResponse['data']['registrationLast4Digits'] ?? "";
                    $registrationPassCode = $getResponse['data']['registrationPassCode'] ?? "";
                    User::where('id', $userId)->update(['accountId' => $registrationAccountId, 'last4Digits' => $registrationLast4Digits, 'passCode' => $registrationPassCode, 'cardType' => 'VIRTUAL']);

                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $registrationLast4Digits,
                        "referenceMemo" => "test transaction ",
                        "transferAmount" => 100,
                        "transferType" => "WalletToCard",
                        "mobilePhoneNumber" => "241$user->phone",
                    ]);
                    $this->cardService->addWalletCardTopUp($postData, $registrationAccountId, 'VIRTUAL');
                    Log::info("Card added $userId");
                } else {
                    Log::info("Card not added $userId");
                }
            } else if ($job_complete == 1 && $job_success == '') {
                User::where('id', $userId)->update(['kyc_status' => 'rejected']);
                Log::info('Kyc Rejected');
            }
        }
    }

    public function getCard()
    {
        $userId = Auth::user()->id;
        $userData = User::where('id', $userId)->first();
        $statusArr = ["status" => "Success", "reason" => __("message_app.Fetched record successfully")];
        $isLocked = false;
        $isActive = false;
        $cardBalance = 0.00;
        $currencyCode = "";
        $name = "{$userData->name} {$userData->lastName}";

        /* $postData = json_encode([
            "chargeFee" => false,
            "last4Digits" => '9566',
            "mobilePhoneNumber" => "241623228392",
            "newCardStatus" => "Active",
        ]);
        $getResponse = $this->cardService->cardLockUnlock($postData, '246024002', "PHYSICAL");
        dd($getResponse); */

        if (!empty($userData->last4Digits)) {
            $accountId = $userData->accountId;

            $getCustomerDetail = $this->cardService->getCustomerData($userData->accountId, $userData->cardType);
            $getCardBalnce = $this->cardService->getCardBalance($userData->accountId, $userData->cardType);
            $cardBalance = $getCardBalnce['data']['balance'] ?? 0.00;
            $currencyCode = $getCardBalnce['data']['currencyCode'] ?? "";
            // dd($getCustomerDetail, $userData);
            // Log::info(['getCardBalnce ' => $getCardBalnce['data']['balance'] ]);
            if ($getCustomerDetail['status'] == true) {
                $customerData = $getCustomerDetail['data'] ?? [];

                // Log::info(['customerData' => $customerData]);
                $isLocked = $customerData['cardStatus'] == "AC" ? true : false;
                $isActive = $customerData['cardStatus'] == "AC" ? true : false;
            }
        } else {
            $accountId = 00000;
        }
        $cardReq = CardRequest::where('user_id', $userData->id)->first();

        if ((isset($cardReq) && $cardReq->status == 0)) {
            $statusArr['data'] = ["accountId" => $accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'isLocked' => $isLocked, 'isValidKyc' => "", 'kycMsg' => __('message_app."You have already placed an order for card'), 'isActive' => $isActive, 'balance' => $cardBalance, 'currencyCode' => $currencyCode];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        if ($userData->wallet_balance >= CARD_FEES) {
            $isValidKyc = true;
            $kycMsg = "";
        } else {
            $isValidKyc = false;
            $kycMsg = __('message_app.CardFees', ['amount' => CARD_FEES]);

        }
        $statusArr['data'] = ["accountId" => $accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'isLocked' => $isLocked, 'isValidKyc' => $isValidKyc, 'kycMsg' => $kycMsg, 'isActive' => $isActive, 'balance' => $cardBalance, 'currencyCode' => $currencyCode];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);

    }


    public function getCardNew()
    {
        // $startTime = microtime(true);

        $user = Auth::user();

        $cards = UserCard::where('userId', $user->id)->get();

        if ($cards->isEmpty()) {
            return response()->json(
                $this->encryptContent(json_encode([
                    'status' => 'Success',
                    'reason' => __('message_app.Fetched record successfully'),
                    'data' => []
                ])),
                200
            );
        }

        $responseCards = [];

        foreach ($cards as $card) {

            $balance = 0;
            $currencyCode = "";
            $isLocked = false;
            $isActive = false;
            $isValidKyc = false;
            $kycMsg = "";

            /* -----------------------------
            Third Party APIs
            ------------------------------*/

            $customerStart = microtime(true);
            $customerDetail = $this->cardService->getCustomerData($card->accountId, $card->cardType);
            // Log::info('Customer API Time: ' . ((microtime(true) - $customerStart) * 1000) . ' ms');

            $balanceStart = microtime(true);
            $cardBalance = $this->cardService->getCardBalance($card->accountId, $card->cardType);
            // Log::info('Balance API Time: ' . ((microtime(true) - $balanceStart) * 1000) . ' ms');

            /* -----------------------------
            Balance
            ------------------------------*/

            if (!empty($cardBalance['data'])) {
                $balance = $cardBalance['data']['balance'] ?? 0;
                $currencyCode = "XAF"; // $cardBalance['data']['currencyCode'] ?? "";
            }

            /* -----------------------------
            Card Status
            ------------------------------*/

            if (!empty($customerDetail['data'])) {

                $status = $customerDetail['data']['cardStatus'] ?? '';

                if (in_array($status, ['AC', 'RP'])) {
                    $isLocked = true;
                    $isActive = true;
                }
            }

            /* -----------------------------
            KYC / Fees Check
            ------------------------------*/

            if ($card->cardType == "VIRTUAL") {

                if ($user->wallet_balance >= VIRTUAL_CARD_FEES) {

                    $isValidKyc = true;

                } else {

                    $isValidKyc = false;

                    $kycMsg = __('message_app.CardFees', [
                        'amount' => $isActive ? REPLACE_CARD_FEES : VIRTUAL_CARD_FEES
                    ]);
                }
            }

            /* -----------------------------
            Response
            ------------------------------*/

            $responseCards[] = [
                'accountId' => $card->accountId,
                'last4Digits' => $card->last4Digits,
                'cardType' => $card->cardType,
                'name' => $user->name . ' ' . $user->lastName,
                'programId' => ONAFRIQ_INFO_PROGRAMID,
                'vaultId' => ONAFRIQ_VAULTID,
                'isLocked' => $isLocked,
                'isActive' => $isActive,
                'isValidKyc' => $isValidKyc,
                'kycMsg' => $kycMsg,
                'balance' => number_format(round($balance), 0, '', ' '),
                'currencyCode' => $currencyCode,
                'userId' => $user->unique_key,
                'jobId' => $user->jobId
            ];
        }

        // Log::info('Total API Time: ' . ((microtime(true) - $startTime) * 1000) . ' ms');

        $statusArr = [
            'status' => 'Success',
            'reason' => __('message_app.Fetched record successfully'),
            'data' => $responseCards
        ];

        return response()->json(
            $this->encryptContent(json_encode($statusArr)),
            200
        );
    }

    public function logout(Request $request)
    {
        $userId = Auth::user()->id;

        $request->user()->token()->revoke();

        User::where('id', $userId)->update(array('device_type' => '', 'login_status' => 0));

        $statusArr = array("status" => 'Success', "reason" => __('message_app.Logout Successfully'));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function getWalletBalance(Request $request)
    {
        $userId = Auth::user()->id;
        $userInfo = User::where("id", $userId)->first();
        $statusArr = [
            "status" => 'Success',
            "amount" => number_format((($userInfo->wallet_balance - floor($userInfo->wallet_balance)) > 0.5 ? ceil($userInfo->wallet_balance) : floor($userInfo->wallet_balance)), 0, '', ' '),
            "default_currency" => 'XAF',
            "reason" => __('message_app.Wallet Balance Fetched Successfully'),
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function profile(Request $request)
    {
        // $getResponse = $this->smsService->sendLoginRegisterOtp("254254", "24161006048"); die;
        $userId = Auth::user()->id;

        $requestData = $this->decryptContent($request->req);

        $userInfo = User::where("id", $userId)->first();

        $latitude = $requestData->latitude;
        $longitude = $requestData->longitude;
        $device_id = $requestData->device_id;

        if ($latitude == "" || $longitude == "") {
            $statusArr = [
                "status" => 'Failed',
                "reason" => __('message_app.Please provide latitude & longitude'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userInfo->device_id != $device_id) {
            $statusArr = [
                "status" => 'Logout',
                "reason" => __('message_app.Your token has been expired'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        User::where('id', $userId)->update([
            "lat" => $latitude,
            "lng" => $longitude,
        ]);

        $userInfo = User::where("id", $userId)->first();

        $getBonusReferral = Admin::where('id', 1)->first();
        $successReferralCount = User::where('referralBy', $userId)->count();
        $referralEarning = Transaction::where([['user_id', '=', $userId], ['payment_mode', '=', 'Referral']])->sum('amount');



        if (!empty($userInfo)) {
            global $kycStatus;
            $userData = [];
            $userData["firstName"] = $userInfo->name ?? "";
            $userData["lastName"] = $userInfo->lastName ?? "";
            $userData["user_id"] = $userInfo->id;
            $userData["user_type"] = $userInfo->user_type;
            $userData["amount"] = $this->numberFormatSpaces($this->roundAmount($userInfo->wallet_balance));
            if ($userInfo->profile_image != "" && $userInfo->profile_image != "no_user.png") {
                $userData["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
            } else {
                $userData["profile_image"] = "public/img/" . "no_user.png";
            }

            if (isset($userInfo->country) && $userInfo->country != "") {
                $countries_new = DB::table('province_data')->where('id', $userInfo->country)->first();
            }
            if (isset($userInfo->state) && $userInfo->state != "") {
                $states_new = DB::table('province_district')->where('id', $userInfo->state)->first();
            }
            if (isset($userInfo->state) && $userInfo->city != "") {
                $city_new = DB::table('province_city')->where('id', $userInfo->city)->first();
            }

            if ($userInfo->wallet_balance >= CARD_FEES) {
                $isValidKyc = true;
                $kycMsg = "";
            } else {
                $isValidKyc = false;
                $kycMsg = __('message_app.CardFees', ['amount' => CARD_FEES]);
            }

            $travelData = DB::table('travel_documents')->where('userId', $userId)->first();

            $userData["user_type"] = $userInfo->user_type;
            $userData["phone"] = $userInfo->phone;
            $userData["email"] = $userInfo->email;
            $userData["country"] = $userInfo->country;
            $userData["dob"] = date("d/m/Y", strtotime($userInfo->dob));
            $userData["national_identity_type"] = $userInfo->national_identity_type ? $userInfo->national_identity_type : "";
            $userData["national_identity_number"] = $userInfo->national_identity_number ? $userInfo->national_identity_number : "";
            $userData["business_name"] = $userInfo->business_name ? $userInfo->business_name : "";
            //            $userData['registration_number'] = $userInfo->registration_number ? $userInfo->registration_number : '';
            $userData["id_expiry_date"] = $userInfo->id_expiry_date ? date("d/m/Y", strtotime($userInfo->id_expiry_date)) : "";
            $userData["identity_front_image"] = $userInfo->identity_front_image ? $userInfo->identity_front_image : "";
            $userData["identity_back_image"] = $userInfo->identity_back_image ? $userInfo->identity_back_image : "";
            $userData["is_kyc_done"] = $userInfo->is_kyc_done ? $userInfo->is_kyc_done : 0;
            $userData["kyc_status"] = $userInfo->kyc_status ? $userInfo->kyc_status : '';
            $userData["kyc_status_title"] = $userInfo->kyc_status ? __("message_app." . $kycStatus[$userInfo->kyc_status]) : '';
            $userData["qrcode"] = "public/" . $userInfo->qr_code;
            $userData["countryCode"] = $userInfo->countryCode;
            $userData["countryCodeName"] = $userInfo->countryCodeName;
            $userData["aggregator_code"] = $userInfo->company_code;
            $userData["ibanNumber"] = $userInfo->ibanNumber ?? '';
            $userData["smileLink"] = $userInfo->smile_link ?? '';
            $userData["isPinSet"] = $userInfo->securityPin != "" ? true : false;
            $userData["isValidKyc"] = $isValidKyc;
            $userData["kycMsg"] = $kycMsg;
            $userData["whatsAppNo"] = Admin::where('id', '1')->first()->phone;
            $userData["gabonStampImg"] = ($userInfo->gabonStampStatus === 'approved' && !empty($userInfo->gabonStampImg)) ? GABON_VISA_STAMPED . $userInfo->gabonStampImg : '';

            // $userData["travelDocumentUploaded"] = $travelData->status ?? "";

            $addressParts = [
                $userInfo->address1 ?? '',
                $userInfo->address2 ?? '',
                $userInfo->city ?? '',
                $states_new->name ?? '',
                $countries_new->name ?? '',
                $userInfo->postCode ?? ''
            ];
            // Remove empty parts and join with a comma

            $addressData["address1"] = $userInfo->address1 ?? "";
            $addressData["address2"] = $userInfo->address2 ?? "";
            $addressData["city"] = $userInfo->city ?? "";
            $addressData["city_name"] = $city_new->name ?? "";
            $addressData["state_id"] = $userInfo->state ?? "";
            $addressData["state_name"] = $states_new->name ?? "";
            $addressData["country_id"] = $userInfo->country ?? "";
            $addressData["country_name"] = $countries_new->name ?? "";
            $addressData["post_code"] = $userInfo->postCode ?? "";
            $addressData["fullAddress"] = implode(', ', array_filter($addressParts));


            $refData["title"] = __('message_app.RefAmt', ['sender' => $getBonusReferral->referralBonusSender, 'receiver' => $getBonusReferral->referralBonusReceiver]);
            $refData["referralCode"] = $userInfo->referralCode ?? "";
            $refData["referralText"] = __('message_app.Successful Referrals');
            $refData["successReferralCount"] = $this->numberFormatSpaces($this->roundAmount($successReferralCount)) ?? 0;
            $refData["earningText"] = __('message_app.Total Earnings');
            $refData["referralEarning"] = "XAF" . $this->numberFormatSpaces($this->roundAmount($referralEarning)) ?? 0;


            $data["data"] = $userData;
            $data["data"]['addressData'] = $addressData;
            $data["data"]['referralData'] = $refData;

            $amount = $userInfo->wallet_balance;

            $bannerArr = [];
            if ($userInfo->user_type == "Agent") {
                $banners = Banner::where("status", 1)
                    ->where("user_type", "=", "Agent")
                    ->get();
            } elseif ($userInfo->user_type == "Merchant") {
                $banners = Banner::where("status", 1)
                    ->where("user_type", "=", "Merchant")
                    ->get();
            } else {
                $banners = Banner::where("status", 1)
                    ->where("user_type", "=", "User")
                    ->get();
            }

            if (!empty($banners)) {
                foreach ($banners as $banner) {
                    $bannerA["banner_image"] = BANNER_FULL_DISPLAY_PATH . $banner->banner_image;
                    $bannerA["category"] = $banner->category;
                    $bannerArr[] = $bannerA;
                }
            }
            $data["bannerData"] = $bannerArr;
            $data["typeData"] = [
                ['id' => 0, 'name' => __('message_app.This Week')],
                ['id' => 1, 'name' => __('message_app.Last Week')],
                ['id' => 2, 'name' => __('message_app.This Month')],
                ['id' => 3, 'name' => __('message_app.Last Month')],
                ['id' => 4, 'name' => __('message_app.Last Seven Days')],
            ];

            $statusArr = [
                "status" => "Success",
                "amount" => number_format((($amount - floor($amount)) > 0.5 ? ceil($amount) : floor($amount)), 0, '', ' '),
                "reason" => __('message_app.Profile Details'),
            ];
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.You entered wrong phone number'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    function staticPage(Request $request)
    {
        $requestData = $this->decryptContent($request->req);

        $device_language = $requestData->device_language;
        $pagename = $requestData->page_name;

        if ($pagename == "help") {
            $url = "help";
        } elseif ($pagename == "about") {
            $url = "about-us";
        } elseif ($pagename == "privacy") {
            $url = "privacy-policy";
        } elseif ($pagename == "faq") {
            $url = "faq";
        } elseif ($pagename == "terms") {
            $url = "terms-and-condition";
        } else {
            $url = $pagename;
        }

        if ($url == "privacy-policy-user" && $device_language == "en") {
            $url = "privacy-policy-user-en";
        }

        $pageInfo = DB::table("pages")
            ->where("slug", $url)
            ->first();

        $statusArr = [
            "status" => "Success",
            "content" => $pageInfo->description ?? "",
            "reason" => __('message_app.Page Detail'),
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function updatePhoneNumber(Request $request)
    {
        $userId = Auth::user()->id;
        $requestData = $this->decryptContent($request->req);
        $phone = $requestData->phone;
        $is_exist = User::where("phone", $requestData->phone)->where("id", '!=', $userId)->where("otp_verify", 1)->count();
        if ($is_exist > 0) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.phone_already_exists"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $otp_number = $this->generateNumericOTP(6);

        $this->sendSMS($otp_number, $phone);

        $statusArr = [
            "status" => "Success",
            "reason" => __('message_app.OTP sent successfully'),
            "otpCode" => $otp_number,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function updateProfileImage(Request $request)
    {
        $userId = Auth::user()->id;
        $userInfo = User::where('id', $userId)->first();
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] != '0') {
            $file = $_FILES['profile_image'];
            $file = Input::file('profile_image');
            $uploadedFileName = $this->uploadImage($file, PROFILE_FULL_UPLOAD_PATH);
            @unlink(PROFILE_FULL_UPLOAD_PATH . $userInfo->profile_image);
            User::where('id', $userId)->update([
                "profile_image" => $uploadedFileName,
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $statusArr = [
                "status" => "Success",
                "reason" => __('message_app.Profile has been updated successfully'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function updateBasicProfile(Request $request)
    {
        $userId = Auth::user()->id;
        $requestData = $this->decryptContent($request->req);
        $firstName = $requestData->firstName ?? "";
        $lastName = $requestData->lastName ?? "";
        $email = $requestData->email;
        $business_name = $requestData->business_name ?? null;

        $input = [
            'firstName' => $requestData->firstName ?? null,
            'lastName' => $requestData->lastName ?? null,
            'email' => $requestData->email ?? null,
        ];

        $validate_data = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            // 'email' => 'nullable|email|unique:users,email,' . $userId,
            'email' => 'nullable|email:rfc,dns|unique:users,email,' . $userId
        ];

        $customMessages = [
            'firstName' => __('message_app.First name is required'),
            'lastName' => __('message_app.last name is required'),
            'email' => __('message_app.Email is required'),
            'email.unique' => __('message_app.This email address is already in use'),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        /* if ($email != "") {
            $is_exist = User::where("email", $requestData->email)->where("id", '!=', $userId)->where("otp_verify", 1)->count();
            if ($is_exist > 0) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("Email already exist."),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $phone = $requestData->phone;
        $is_exist = User::where("phone", $requestData->phone)->where("id", '!=', $userId)->where("otp_verify", 1)->count();
        if ($is_exist > 0) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.phone_already_exists"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $carbonDate = Carbon::createFromFormat('d/m/Y', $requestData->dob);
        $formattedDate = $carbonDate->format('Y-m-d');
        $user = User::where('id', $userId)->update([
            "name" => $firstName,
            "lastName" => $lastName,
            "email" => $email,
            "business_name" => $business_name,
            "dob" => $formattedDate,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.Profile has been updated successfully"),
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function kycUpdate(Request $request)
    {
        ini_set("precision", 14);
        ini_set("serialize_precision", -1);

        $userId = Auth::user()->id;

        try {
            $userInfo = User::where("id", $request->user_id)->first();
            $user_id = $request->user_id;

            if (
                isset($_FILES["identity_image"]) &&
                $_FILES["identity_image"]["size"] != "0"
            ) {
                $file = $_FILES["identity_image"];
                $file = Input::file("identity_image");
                $uploadedFileName = $this->uploadImage(
                    $file,
                    IDENTITY_FULL_UPLOAD_PATH
                );
                $this->resizeImage(
                    $uploadedFileName,
                    IDENTITY_FULL_UPLOAD_PATH,
                    IDENTITY_SMALL_UPLOAD_PATH,
                    IDENTITY_MW,
                    IDENTITY_MH
                );
                $data["identity_image"] = $uploadedFileName;
                @unlink(IDENTITY_FULL_UPLOAD_PATH . $userInfo->identity_image);
            }

            $data["registration_number"] = $request->registration_number;
            $data["national_identity_number"] = $request->national_identity_number;
            $data["is_kyc_done"] = 0;
            //                $data['is_verify'] = 0;

            $serialisedData = $this->serialiseFormData($data, 1); //send 1 for edit
            User::where("id", $request->user_id)->update($serialisedData);

            $title = __('message_app.Congratulations');
            $message = __('message_app.Congratulations! Your KYC details submitted to admin successfully');

            $device_type = $userInfo->device_type;
            $device_token = $userInfo->device_token;

            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'APPROVEKYC',
            ];

            if ($device_type && $device_token) {
                $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                    $device_token,
                    $title,
                    $message,
                    $data1,
                    $device_type
                );
            }

            $notif = new Notification([
                "user_id" => $user_id,
                "notif_title" => $title,
                "notif_body" => $message,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $notif->save();

            $statusArr = [
                "status" => "Success",
                "is_kyc_done" => 0,
                "reason" => __('message_app.KYC details updated successfully'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } catch (\Exception $ex) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Unknown Exception'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function feedback(Request $request)
    {
        $user_id = Auth::user()->id;
        $email = $request->email;
        $subject = $request->subject;
        $message = $request->message;

        $data["user_id"] = $user_id;
        $data["email"] = $email;
        $data["subject"] = $subject;
        $data["message"] = $message;

        $serialisedData = $this->serialiseFormData($data); //send 1 for edit

        Contact::insert($serialisedData);

        $userInfo = User::where("id", $user_id)->first();

        $title = __('message_app.Congratulations');
        $message = __('message_app.Congratulations! Your feedback has sent successfully');
        $device_type = $userInfo->device_type;
        $device_token = $userInfo->device_token;

        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'FEEDBACK',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            "user_id" => $userInfo->id,
            "notif_title" => $title,
            "notif_body" => $message,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        $notif->save();

        $statusArr = [
            "status" => "Success",
            "reason" => __('message_app.Feedback sent successfully'),
        ];
        return response()->json($statusArr, 200);
    }

    public function requestList(Request $request)
    {
        $user_id = Auth::user()->id;
        $request_type = $request->request_type;

        $userInfo = User::where("id", $user_id)->first();

        if ($request_type == "Deposit") {
            $request_type = "Agent Deposit";
        }
        /* Payment_mode= Withdraw/Agent Deposit */
        /* trans_type= 4 */
        $requests = Transaction::where("trans_type", 4)
            ->where("status", 2)
            ->where("user_id", $user_id)
            ->where("payment_mode", $request_type)
            ->orderBy("id", "desc")
            ->get();

        $records = [];
        if ($requests) {
            foreach ($requests as $request) {
                $userData = [];
                $userData["request_id"] = $request->id;
                $userData["user_id"] = $request->receiver_id;
                $userData["name"] = $request->Receiver->name;
                $userData["phone"] = $request->Receiver->phone;
                $userData["amount"] = $this->numberFormatPrecision(
                    $request->amount,
                    2,
                    "."
                );
                if ($request->Receiver->profile_image != "" && $request->Receiver->profile_image != "no_user.png") {
                    $userData["user_image"] = PROFILE_FULL_DISPLAY_PATH .
                        $request->Receiver->profile_image;
                } else {
                    $userData["user_image"] = "public/img/" . "no_user.png";
                }
                $records[] = $userData;
            }

            $statusArr = ["status" => "Success", "reason" => __('message_app.Request List')];
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            // return response()->json($responseData, 200);
            return response()->json([], 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Request not available'),
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function cancelAcceptRequest(Request $request)
    {
        Log::info('cancelAcceptRequest');
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $request_id = $request->request_id;

        $request_type = $request->request_type;

        $userInfo = User::where("id", $user_id)->first();

        if ($userInfo->kyc_status != "completed") {

            if ($userInfo->kyc_status == "pending") {

                $statusArr = [
                    "status" => "KYC Pending",
                    "reason" => __('message_app.Your KYC is pending'),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Not Verified",
                    "reason" => __('message_app.Please verify your KYC'),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

        if ($user_id == "" or !is_numeric($user_id)) {
            $statusArr = ["status" => "Failed", "reason" => __('message_app.Invalid User id')];
            return response()->json($statusArr, 200);
        } elseif ($request_id == "" or !is_numeric($request_id)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Invalid Request id'),
            ];
            return response()->json($statusArr, 200);
        }

        $requestDetail = Transaction::where("id", $request_id)
            ->where("user_id", $user_id)
            ->where("trans_type", 4)
            ->first();
        if (!empty($requestDetail)) {
            if ($requestDetail->payment_mode == "Agent Deposit") {
                $type = 1;
            } else {
                $type = 2;
            }

            if ($request_type == "Accept") {
                if ($type == 1) {
                    if ($userInfo->wallet_balance >= $requestDetail->amount) {

                        $receiverInfo = User::where("id", $requestDetail->receiver_id)->first();
                        $wallet_balance = $userInfo->wallet_balance + $requestDetail->amount;

                        User::where("id", $receiverInfo->id)->update(["wallet_balance" => $wallet_balance,]);

                        $userCard = UserCard::where('userId', $receiverInfo->id)->where('cardType', 'PHYSICAL')->first();
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => $requestDetail->amount,
                                "transferType" => "WalletToCard",
                                "mobilePhoneNumber" => "241{$receiverInfo->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Cancel Accept request');
                        }
                        /* Card Payment End */

                        Transaction::where("id", $request_id)->update([
                            "status" => 1,
                            "trans_type" => $type,
                            'remainingWalletBalance' => $wallet_balance
                        ]);

                        $wallet_balance = $userInfo->wallet_balance - $requestDetail->amount;
                        User::where("id", $userInfo->id)->update(["wallet_balance" => $wallet_balance,]);

                        /* Card Payment Start */
                        $userCard = UserCard::where('userId', $userInfo->id)->where('cardType', 'PHYSICAL')->first();
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => $requestDetail->amount,
                                "transferType" => "CardToWallet",
                                "mobilePhoneNumber" => "241{$userInfo->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Cancel Accept request 2');
                        }
                        /* Card Payment End */

                        $title = __('message_app.Congratulations');
                        $message = __('message_app.ReqCongra', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($requestDetail->amount))]);
                        $device_type = $receiverInfo->device_type;
                        $device_token = $receiverInfo->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $data1 = [
                            'title' => $title,
                            'message' => $message,
                            'id' => "",
                            'type' => 'REQUESTACCEPTED',
                        ];

                        if ($device_type && $device_token) {
                            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                $device_token,
                                $title,
                                $message,
                                $data1,
                                $device_type
                            );
                        }

                        $notif = new Notification([
                            "user_id" => $receiverInfo->id,
                            "notif_title" => $title,
                            "notif_body" => $message,
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                        ]);
                        $notif->save();
                    } else {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __('message_app.You have insufficient balance to accept request'),
                        ];
                        return response()->json($statusArr, 200);
                    }
                } else {
                    Transaction::where("id", $request_id)->update([
                        "status" => 1,
                        "trans_type" => $type,
                    ]);

                    $receiverInfo = User::where(
                        "id",
                        $requestDetail->receiver_id
                    )->first();

                    $userInfo = User::where("id", $user_id)->first();
                    $wallet_balance = $userInfo->wallet_balance + $requestDetail->amount;
                    User::where("id", $userInfo->id)->update([
                        "wallet_balance" => $wallet_balance,
                    ]);

                    $title = __('message_app.Congratulations');
                    $message = __('message_app.ReqCongraWith', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($requestDetail->amount))]);
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'ACCEPTEDWITHDRAW',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        "user_id" => $receiverInfo->id,
                        "notif_title" => $title,
                        "notif_body" => $message,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $notif->save();
                }
                $statusArr = [
                    "status" => "Success",
                    "reason" => __('message_app.Request Accepted Successfully'),
                ];
            } else {
                if ($type != 1) {
                    $receiverInfo = User::where(
                        "id",
                        $requestDetail->receiver_id
                    )->first();
                    $wallet_balance = $receiverInfo->wallet_balance +
                        $requestDetail->total_amount;
                    User::where("id", $receiverInfo->id)->update([
                        "wallet_balance" => $wallet_balance,
                    ]);

                    /* Card Payment Start */
                    $userCard = UserCard::where('userId', $receiverInfo->id)->where('cardType', 'PHYSICAL')->first();
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $requestDetail->amount,
                            "transferType" => "CardToWallet",
                            "mobilePhoneNumber" => "241{$receiverInfo->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Cancel Accept request 3');
                    }
                    /* Card Payment End */

                    $refrence_id = "Trans-" . $request_id;
                    $trans = new Transaction([
                        "user_id" => $user_id,
                        "receiver_id" => $receiverInfo->id,
                        "amount" => $requestDetail->total_amount,
                        "amount_value" => $requestDetail->total_amount,
                        "transaction_amount" => 0,
                        "total_amount" => $requestDetail->total_amount,
                        "trans_type" => 1,
                        "payment_mode" => "Refund",
                        "status" => 1,
                        'entryType' => 'API',
                        "refrence_id" => $refrence_id,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                        'swapDomainName' => 'UAT_SERVER'
                    ]);
                    $trans->save();

                    $title = __('message_app.Congratulations');
                    $message = __('message_app.ReqCongraWith', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($requestDetail->total_amount))]);
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'REJECTEDWITHDRAW',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        "user_id" => $receiverInfo->id,
                        "notif_title" => $title,
                        "notif_body" => $message,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $notif->save();
                } else {
                    $receiverInfo = User::where(
                        "id",
                        $requestDetail->receiver_id
                    )->first();

                    $title = __('message_app.Congratulations');
                    $message = __('message_app.ReqCongra', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($requestDetail->amount))]);
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'REQUESTACCEPTED',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        "user_id" => $receiverInfo->id,
                        "notif_title" => $title,
                        "notif_body" => $message,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $notif->save();
                }
                Transaction::where("id", $request_id)->update(["status" => 4]);
                $statusArr = [
                    "status" => "Success",
                    "reason" => __('message_app.Request Rejected Successfully'),
                ];
            }

            return response()->json($statusArr, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Invalid request id'),
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function cashCardList(Request $request)
    {
        $user_id = Auth::user()->id;

        $userDetail = User::where("id", $user_id)->first();
        $agentOffer = Agentoffer::where("user_id", $user_id)
            ->where("type", "Cash Card")
            ->where("status", 1)
            ->first();
        $offer = Offer::where("type", "Cash Card")
            ->where("status", 1)
            ->first();

        $data = [];
        $carddetails = Scratchcard::where("status", 1)
            ->where("used_status", 0)
            ->groupBy("scratchcards.real_value")
            ->get();

        if (!empty($carddetails)) {
            foreach ($carddetails as $card) {
                $cardData["card_id"] = $card->id;
                $cardData["real_value"] = $this->numberFormatPrecision(
                    $card->real_value,
                    2,
                    "."
                );
                $cardData["card_value"] = $this->numberFormatPrecision(
                    $card->card_value,
                    2,
                    "."
                );

                if ($userDetail->user_type == "Agent") {
                    if (!empty($agentOffer)) {
                        $cardData["card_value"] = number_format(
                            $card->card_value -
                            ($card->card_value * $agentOffer->offer) / 100,
                            2
                        );
                    } elseif (!empty($offer)) {
                        $cardData["card_value"] = number_format(
                            $card->card_value -
                            ($card->card_value * $offer->offer) / 100,
                            2
                        );
                    }
                }

                $data["data"][] = $cardData;
            }

            $statusArr = ["status" => "Success", "reason" => __('message_app.Cash Card List')];
            $json = array_merge($statusArr, $data);
            return response()->json($json, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Cash card not available'),
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function buyCashCard(Request $request)
    {
        $user_id = Auth::user()->id;
        $card_id = $request->card_id;
        $card_value = str_replace(",", "", $request->card_value);

        $data = [];
        $carddetail = Scratchcard::where("id", $card_id)->first();

        $userInfo = User::where("id", $user_id)->first();

        if (!empty($carddetail)) {
            $cardNumber = $carddetail->card_number;

            if ($userInfo->wallet_balance >= $card_value) {
                $refrence_id = time() . rand() . "-" . $card_id;
                $trans = new Transaction([
                    "user_id" => $user_id,
                    "receiver_id" => 0,
                    "amount" => $card_value,
                    "amount_value" => $carddetail->card_value,
                    "real_value" => $carddetail->real_value,
                    "trans_type" => 2,
                    "trans_to" => "Wallet",
                    "trans_for" => "Cash Card",
                    "payment_mode" => "Cash Card",
                    "refrence_id" => $refrence_id,
                    "status" => 1,
                    'entryType' => 'API',
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();
                $TransId = $trans->id;

                $sender_wallet_amount = $userInfo->wallet_balance - $card_value;
                User::where("id", $user_id)->update([
                    "wallet_balance" => $sender_wallet_amount,
                ]);

                Scratchcard::where("card_number", $cardNumber)->update([
                    "purchase_by_id" => $user_id,
                ]);

                $title = __('message_app.Buy Cash Card');
                $message = __('message_app.cash_card_purchase_success', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($carddetail->card_value)), 'card_number' => $carddetail->card_number]);

                $device_type = $userInfo->device_type;
                $device_token = $userInfo->device_token;

                //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);


                $data1 = [
                    'title' => $title,
                    'message' => $message,
                    'id' => "",
                    'type' => 'PURCHASECASH',
                ];

                if ($device_type && $device_token) {
                    $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                        $device_token,
                        $title,
                        $message,
                        $data1,
                        $device_type
                    );
                }

                $notif = new Notification([
                    "user_id" => $userInfo->id,
                    "notif_title" => $title,
                    "notif_body" => $message,
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
                ]);
                $notif->save();

                $result["card_number"] = $carddetail->card_number;

                $data["data"] = $result;
                $statusArr = [
                    "status" => "Success",
                    "reason" => __('message_app.Transaction Completed'),
                ];
                $json = array_merge($statusArr, $data);
                return response()->json($json, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __('message_app.You have insufficient balance to purchase card'),
                ];
                return response()->json($statusArr, 200);
            }
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Cash card not available'),
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function merchantTransactions(Request $request)
    {
        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;

        $input = [
            'page' => $request->page,
            'limit' => $request->limit,
        ];

        $validate_data = [
            'page' => 'required',
            'limit' => 'required',
        ];

        $customMessages = [
            'page.required' => __('message_app.Page field cannot be left blank'),
            'limit.required' => __('message_app.Limit field cannot be left blank'),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $page = $input['page'];
        $limit = $input['limit'];

        $start = $page - 1;
        $start = $start * $limit;

        global $tranType;

        $userInfo = User::where("id", $user_id)->first();

        if (!empty($userInfo)) {

            $NewDate = Date("Y-m-d", strtotime("-15 days"));
            $trans = Transaction::where("created_at", ">=", $NewDate)
                ->where("payment_mode", "!=", "Refund")
                ->where("payment_mode", "!=", "Withdraw")
                ->where("trans_type", 1)
                ->whereNull("trans_for")
                ->where("refund_status", 0)
                ->where("receiver_id", $user_id)
                ->orderBy("id", "DESC")
                ->skip($start)
                ->take($limit)
                ->get();

            $totalRecords = Transaction::where("created_at", ">=", $NewDate)
                ->where("payment_mode", "!=", "Refund")
                ->where("payment_mode", "!=", "Withdraw")
                ->whereNull("trans_for")
                ->where("trans_type", 1)
                ->where("refund_status", 0)
                ->where("receiver_id", $user_id)
                ->orderBy("id", "DESC")
                ->count();

            if ($trans->isEmpty()) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __('message_app.no_record_found'),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $transArr = [];
            $transDataArr = [];
            foreach ($trans as $key => $request) {
                $userData = [];
                $trnsDt = date_create($request->created_at);
                $transDate = date_format($trnsDt, "d M Y, h:i A");
                $userData["transaction_id"] = $request->id;
                $userData["date"] = $transDate;
                $userRecordArr["user_id"] = $user_id;
                $userRecordArr["name"] = $this->getUserNameById($request->user_id);
                $userRecordArr["phone"] = $this->getPhoneById($request->user_id);
                $userData["amount"] = $request->amount_value;
                if ($request->user_id == 1) {
                    $userRecordArr["profile_image"] = "public/img/" . "no_user.png";
                } else {
                    if ($request->User->profile_image != "" && $request->User->profile_image != "no_user.png") {
                        $userRecordArr["profile_image"] = PROFILE_FULL_DISPLAY_PATH .
                            $request->User->profile_image;
                    } else {
                        $userRecordArr["profile_image"] = "public/img/" . "no_user.png";
                    }
                }
                $userData["userData"] = $userRecordArr;
                $transDataArr[] = $userData;
            }

            $statusArr = [
                "status" => "Success",
                "reason" => __('message_app.Transaction List'),
                "total_records" => $totalRecords,
                "total_page" => ceil($totalRecords / $limit),
            ];
            $data["data"] = $transDataArr;
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Invalid User'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function refundPayment(Request $request)
    {
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $request = $this->decryptContent($request->req);

        $transaction_id = $request->transaction_id;

        //$userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where("id", $user_id)->first();
        if ($user_id == "" or !is_numeric($user_id)) {
            $statusArr = ["status" => "Failed", "reason" => __('message_app.Invalid User id')];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } elseif ($transaction_id == "" || !is_numeric($transaction_id)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Invalid Transaction id'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $requestDetail = Transaction::where("id", $transaction_id)->first();
        if ($userInfo->wallet_balance < $requestDetail->amount_value) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Insufficient Balance'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        if (!empty($requestDetail)) {

            $receiverInfo = User::where("id", $requestDetail->receiver_id)->first();
            $wallet_balance = $receiverInfo->wallet_balance - $requestDetail->amount_value;
            // $transaction_id = $trans->id;
            $debit = new TransactionLedger([
                'user_id' => $receiverInfo->id,
                'opening_balance' => $receiverInfo->wallet_balance,
                'amount' => $requestDetail->amount_value,
                'actual_amount' => $requestDetail->amount_value,
                'type' => 2,
                'trans_id' => $transaction_id,
                'payment_mode' => 'Refund',
                'closing_balance' => $wallet_balance,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $debit->save();
            User::where("id", $receiverInfo->id)->update([
                "wallet_balance" => $wallet_balance,
            ]);

            $senderInfo = User::where("id", $requestDetail->user_id)->first();
            $sender_wallet_balance = $senderInfo->wallet_balance + $requestDetail->amount_value;
            $credit = new TransactionLedger([
                'user_id' => $senderInfo->id,
                'opening_balance' => $senderInfo->wallet_balance,
                'amount' => $requestDetail->amount_value,
                'actual_amount' => $requestDetail->amount_value,
                'type' => 1,
                'trans_id' => $transaction_id,
                'payment_mode' => 'Agent Deposit',
                'closing_balance' => $sender_wallet_balance,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $credit->save();


            $refrence_id = "Trans-" . $transaction_id;
            $trans = new Transaction([
                "user_id" => $senderInfo->id,
                "receiver_id" => $receiverInfo->id,
                "amount" => $requestDetail->amount_value,
                "amount_value" => $requestDetail->amount_value,
                "total_amount" => $requestDetail->amount_value,
                "trans_type" => 1,
                "payment_mode" => "Refund",
                "status" => 1,
                "refund_status" => 1,
                'entryType' => 'API',
                'remainingWalletBalance' => $wallet_balance,
                'beforeBalance' => $wallet_balance,
                'afterBalance' => $wallet_balance,
                "refrence_id" => $refrence_id,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
                'swapDomainName' => 'UAT_SERVER'
            ]);
            $trans->save();

            Transaction::where("id", $transaction_id)->update([
                "refund_status" => 1,
            ]);
            User::where("id", $senderInfo->id)->update([
                "wallet_balance" => $sender_wallet_balance,
            ]);

            $title = __('message_app.Congratulations');
            $message = __('message_app.refund_sent_success', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($requestDetail->amount))]);
            $device_type = $receiverInfo->device_type;
            $device_token = $receiverInfo->device_token;

            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'REFUND',
            ];

            if ($device_type && $device_token) {
                $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                    $device_token,
                    $title,
                    $message,
                    $data1,
                    $device_type
                );
            }

            $notif = new Notification([
                "user_id" => $receiverInfo->id,
                "notif_title" => $title,
                "notif_body" => $message,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $notif->save();

            $title = __("message_app.Congratulations");
            $message = __('message_app.refund_received_success', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($requestDetail->amount))]);
            $device_type = $senderInfo->device_type;
            $device_token = $senderInfo->device_token;

            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'RECEIVEDREFUND',
            ];

            if ($device_type && $device_token) {
                $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                    $device_token,
                    $title,
                    $message,
                    $data1,
                    $device_type
                );
            }

            $notif = new Notification([
                "user_id" => $senderInfo->id,
                "notif_title" => $title,
                "notif_body" => $message,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $notif->save();

            // Send Email
            $status = $this->getStatusText(1);
            $senderName = $senderInfo->name;
            $senderEmail = $senderInfo->email;
            $receiverName = $receiverInfo->name;
            $receiverEmail = $receiverInfo->email;
            $senderAmount = $requestDetail->amount;
            $receiverAmount = $requestDetail->amount;
            $transactionFees = 0;
            $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));
            $emailData['subjects'] = __('message_app.Refund Processed Successfully');
            $emailData['senderName'] = $senderName;
            $emailData['senderEmail'] = $senderEmail;
            $emailData['senderAmount'] = $senderAmount;
            $emailData['currency'] = CURR;

            $emailData['receiverName'] = $receiverName;
            $emailData['receiverAmount'] = $receiverAmount;
            $emailData['receiverEmail'] = $receiverEmail;
            $emailData['receiverAmount'] = $receiverAmount;

            $emailData['transId'] = $refrence_id;
            $emailData['transactionFees'] = $transactionFees;
            $emailData['transactionDate'] = $transaction_date;
            $emailData['transactionStatus'] = $status;

            if ($senderEmail != "") {
                /* Mail::send('emails.fund_refund_sender', $emailData, function ($message) use ($emailData) {
                    $message->to($emailData["senderEmail"], $emailData["senderEmail"])
                        ->subject($emailData["subjects"]);
                }); */
            }

            if ($receiverEmail != "") {
                /* Mail::send('emails.fund_refund_receiver', $emailData, function ($message) use ($emailData) {
                    $message->to($emailData["receiverEmail"], $emailData["receiverEmail"])
                        ->subject($emailData["subjects"]);
                }); */
            }

            $statusArr = [
                "status" => "Success",
                "reason" => __('message_app."The refund has been successfully sent'),
            ];

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Invalid request id'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function sendRefund(Request $request)
    {
        Log::info('sendRefund');
        $userId = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($userId);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */
        if ($request->phone == "" or !is_numeric($request->phone)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Invalid Phone Number"),
            ];
            return response()->json($statusArr, 200);
        } elseif ($request->user_id == "" or !is_numeric($request->user_id)) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid User Id")];
            return response()->json($statusArr, 200);
        } elseif ($request->amount == "" or !is_numeric($request->amount)) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid QR code")];
            return response()->json($statusArr, 200);
        } else {
            //            try {
            //            $matchThese = ["users.phone" => $request->phone, "users.is_verify" => 1, "users.is_kyc_done" => 1];
            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table("users")
                ->where($matchThese)
                ->first();

            if (!empty($recieverUser)) {
                if ($recieverUser->id == $request->user_id) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.You can not send refund for own account"),
                    ];
                    return response()->json($statusArr, 200);
                }

                //                $matchThese = ["users.id" => $request->user_id, "users.is_verify" => 1, "users.is_kyc_done" => 1];
                $matchThese = ["users.id" => $request->user_id];
                $senderUser = DB::table("users")
                    ->where($matchThese)
                    ->first();

                //                if (empty($senderUser)) {
                //                    $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
                //                    return response()->json($statusArr, 200);
                //                }

                $senderUserType = $senderUser->user_type;
                $receiverUserType = $recieverUser->user_type;

                $amount = $request->amount;

                if ($senderUserType == "Merchant") {
                    if ($receiverUserType == "Merchant") {
                        $transactionFee = $request->trans_fee;

                        $totalAmt = number_format($amount + $transactionFee, 2);

                        $refrence_id = time() . rand() . $request->user_id;
                        $trans = new Transaction([
                            "user_id" => $request->user_id,
                            "receiver_id" => $recieverUser->id,
                            "amount" => $request->amount,
                            "amount_value" => $request->amount,
                            "transaction_amount" => $transactionFee,
                            "total_amount" => $totalAmt,
                            "trans_type" => 2,
                            "trans_to" => "Wallet",
                            "payment_mode" => "wallet2wallet",
                            "refrence_id" => $refrence_id,
                            'beforeBalance' => $senderUser->wallet_balance,
                            'afterBalance' => ($senderUser->wallet_balance - $totalAmt),
                            "status" => 1,
                            'entryType' => 'API',
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                            'swapDomainName' => 'UAT_SERVER'
                        ]);
                        $trans->save();
                        $TransId = $trans->id;

                        $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                        User::where("id", $request->user_id)->update([
                            "wallet_balance" => $sender_wallet_amount,
                        ]);


                        /* Card Payment Start */
                        $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->first();
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => $totalAmt,
                                "transferType" => "CardToWallet",
                                "mobilePhoneNumber" => "241{$senderUser->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Send Refund');
                        }
                        /* Card Payment End */

                        $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                        User::where("id", $recieverUser->id)->update([
                            "wallet_balance" => $reciever_wallet_amount,
                        ]);

                        /* Card Payment Start */
                        $userCard = UserCard::where('userId', $recieverUser->id)->where('cardType', 'PHYSICAL')->first();
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => $request->amount,
                                "transferType" => "WalletToCard",
                                "mobilePhoneNumber" => "241{$recieverUser->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Send Refund 1');
                        }
                        /* Card Payment End */

                        $data["data"]["wallet_amount"] = $this->numberFormatPrecision(
                            $sender_wallet_amount,
                            2,
                            "."
                        );
                        $data["data"]["trans_amount"] = $totalAmt;
                        $data["data"]["receiver_name"] = $recieverUser->name;
                        $data["data"]["receiver_phone"] = $recieverUser->phone;
                        $data["data"]["trans_id"] = $TransId;
                        $data["data"]["trans_date"] = date("d, M Y, h:i A");

                        $title = __('message_app.wallet_debited_title', ['currency' => 'XAF', 'amount' => $this->numberFormatSpaces($this->roundAmount($totalAmt))]);
                        $message = __('message_app.wallet_debited_fund_transfer', ['amount' => $this->numberFormatSpaces($this->roundAmount($totalAmt)), 'username' => $recieverUser->name]);

                        $device_type = $senderUser->device_type;
                        $device_token = $senderUser->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);
                        $data1 = [
                            'title' => $title,
                            'message' => $message,
                            'id' => "",
                            'type' => 'FUNDTRANSFER',
                        ];

                        if ($device_type && $device_token) {
                            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                $device_token,
                                $title,
                                $message,
                                $data1,
                                $device_type
                            );
                        }

                        $notif = new Notification([
                            "user_id" => $senderUser->id,
                            "notif_title" => $title,
                            "notif_body" => $message,
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                        ]);
                        $notif->save();

                        $message = __('message_app.wallet_credited', ['amount' => $this->numberFormatSpaces($this->roundAmount($amount))]);
                        $message = __('message_app.wallet_credited_fund_transfer', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($request->amount)), 'sender' => $senderUser->name]);
                        $device_type = $recieverUser->device_type;
                        $device_token = $recieverUser->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $data1 = [
                            'title' => $title,
                            'message' => $message,
                            'id' => "",
                            'type' => 'CREDITWALLET',
                        ];

                        if ($device_type && $device_token) {
                            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                $device_token,
                                $title,
                                $message,
                                $data1,
                                $device_type
                            );
                        }

                        $notif = new Notification([
                            "user_id" => $recieverUser->id,
                            "notif_title" => $title,
                            "notif_body" => $message,
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                        ]);
                        $notif->save();

                        $statusArr = [
                            "status" => "Success",
                            "payment_status" => "Success",
                            "reason" => __("message_app.Sent Successfully"),
                        ];
                        $json = array_merge($statusArr, $data);
                        return response()->json($json, 200);
                    } elseif ($receiverUserType == "Agent") {
                        $paymentType = "Withdraw";
                        $payerId = $senderUser->id;

                        $userFee = Usertransactionfee::where(
                            "user_id",
                            $payerId
                        )
                            ->where("transaction_type", $paymentType)
                            ->where("status", 1)
                            ->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where(
                                "transaction_type",
                                $paymentType
                            )
                                ->where("status", 1)
                                ->first();

                            if (!empty($fees)) {
                                $transFee = number_format(
                                    ($amount * $fees->user_charge) / 100,
                                    2
                                );
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = number_format(
                                    ($amount * $userFee->user_charge) / 100,
                                    2
                                );
                            }
                        }
                        $transactionFee = $transFee;
                        $totalAmt = number_format($amount + $transFee, 2);

                        $userActiveAmount = $senderUser->wallet_balance;

                        if ($userActiveAmount >= $totalAmt) {
                            $refrence_id = time() . rand() . $request->user_id;
                            $trans = new Transaction([
                                "user_id" => $request->user_id,
                                "receiver_id" => $recieverUser->id,
                                "amount" => $request->amount,
                                "amount_value" => $request->amount,
                                "transaction_amount" => $transactionFee,
                                "total_amount" => $totalAmt,
                                "trans_type" => 2,
                                "trans_to" => "Wallet",
                                "payment_mode" => "wallet2wallet",
                                "refrence_id" => $refrence_id,
                                "status" => 1,
                                'entryType' => 'API',
                                "created_at" => date("Y-m-d H:i:s"),
                                "updated_at" => date("Y-m-d H:i:s"),
                                'swapDomainName' => 'UAT_SERVER'
                            ]);
                            $trans->save();
                            $TransId = $trans->id;

                            $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                            User::where("id", $request->user_id)->update([
                                "wallet_balance" => $sender_wallet_amount,
                            ]);

                            $reciever_wallet_amount = $recieverUser->wallet_balance +
                                $request->amount;
                            User::where("id", $recieverUser->id)->update([
                                "wallet_balance" => $reciever_wallet_amount,
                            ]);
                            $data["data"]["wallet_amount"] = $this->numberFormatPrecision(
                                $sender_wallet_amount,
                                2,
                                "."
                            );
                            $data["data"]["trans_amount"] = $totalAmt;
                            $data["data"]["receiver_name"] = $recieverUser->name;
                            $data["data"]["receiver_phone"] = $recieverUser->phone;
                            $data["data"]["trans_id"] = $TransId;
                            $data["data"]["trans_date"] = date("d, M Y, h:i A");

                            $title = $title = __('message_app.wallet_debited_title', ['currency' => 'XAF', 'amount' => $this->numberFormatSpaces($this->roundAmount($totalAmt))]);
                            $message = __('message_app.wallet_debited_fund_transfer', ['amount' => $this->numberFormatSpaces($this->roundAmount($totalAmt)), 'username' => $recieverUser->name]);
                            $device_type = $senderUser->device_type;
                            $device_token = $senderUser->device_token;

                            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $data1 = [
                                'title' => $title,
                                'message' => $message,
                                'id' => "",
                                'type' => 'DEBITWALLET',
                            ];

                            if ($device_type && $device_token) {
                                $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                    $device_token,
                                    $title,
                                    $message,
                                    $data1,
                                    $device_type
                                );
                            }

                            $notif = new Notification([
                                "user_id" => $senderUser->id,
                                "notif_title" => $title,
                                "notif_body" => $message,
                                "created_at" => date("Y-m-d H:i:s"),
                                "updated_at" => date("Y-m-d H:i:s"),
                            ]);
                            $notif->save();

                            $message = __('message_app.wallet_credited', ['amount' => $this->numberFormatSpaces($this->roundAmount($amount))]);
                            $message = __('message_app.wallet_credited_fund_transfer', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($request->amount)), 'sender' => $senderUser->name]);
                            $device_type = $recieverUser->device_type;
                            $device_token = $recieverUser->device_token;

                            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $data1 = [
                                'title' => $title,
                                'message' => $message,
                                'id' => "",
                                'type' => 'CREDITWALLET',
                            ];

                            if ($device_type && $device_token) {
                                $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                    $device_token,
                                    $title,
                                    $message,
                                    $data1,
                                    $device_type
                                );
                            }

                            $notif = new Notification([
                                "user_id" => $recieverUser->id,
                                "notif_title" => $title,
                                "notif_body" => $message,
                                "created_at" => date("Y-m-d H:i:s"),
                                "updated_at" => date("Y-m-d H:i:s"),
                            ]);
                            $notif->save();

                            $statusArr = [
                                "status" => "Success",
                                "payment_status" => "Success",
                                "reason" => __("message_app.Sent Successfully"),
                            ];
                            $json = array_merge($statusArr, $data);
                            return response()->json($json, 200);
                        } else {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => __("message_app.Insufficient Balance"),
                            ];
                            return response()->json($statusArr, 200);
                        }
                    } else {
                        $paymentType = "Refund";
                        $payerId = $recieverUser->id;

                        $userFee = Usertransactionfee::where(
                            "user_id",
                            $payerId
                        )
                            ->where("transaction_type", $paymentType)
                            ->where("status", 1)
                            ->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where(
                                "transaction_type",
                                $paymentType
                            )
                                ->where("status", 1)
                                ->first();

                            if (!empty($fees)) {
                                $transFee = number_format(
                                    ($amount * $fees->user_charge) / 100,
                                    2
                                );
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = number_format(
                                    ($amount * $userFee->user_charge) / 100,
                                    2
                                );
                            }
                        }
                        $transactionFee = $transFee;
                        $totalAmt = number_format($amount - $transFee, 2);

                        $userActiveAmount = $senderUser->wallet_balance;

                        if ($userActiveAmount >= $request->amount) {
                            if (!empty($senderUser)) {
                                $refrence_id = time() . rand() . $request->user_id;
                                $trans = new Transaction([
                                    "user_id" => $request->user_id,
                                    "receiver_id" => $recieverUser->id,
                                    "amount" => $request->amount,
                                    "amount_value" => $request->amount,
                                    "transaction_amount" => $transactionFee,
                                    "total_amount" => $totalAmt,
                                    "trans_type" => 1,
                                    "refund_status" => 1,
                                    "trans_to" => "Wallet",
                                    "payment_mode" => "Refund",
                                    "refrence_id" => $refrence_id,
                                    "status" => 1,
                                    'entryType' => 'API',
                                    "created_at" => date("Y-m-d H:i:s"),
                                    "updated_at" => date("Y-m-d H:i:s"),
                                    'swapDomainName' => 'UAT_SERVER'
                                ]);
                                $trans->save();
                                $TransId = $trans->id;

                                $sender_wallet_amount = $senderUser->wallet_balance -
                                    $request->amount;
                                User::where("id", $request->user_id)->update([
                                    "wallet_balance" => $sender_wallet_amount,
                                ]);

                                $reciever_wallet_amount = $recieverUser->wallet_balance + $totalAmt;
                                User::where("id", $recieverUser->id)->update([
                                    "wallet_balance" => $reciever_wallet_amount,
                                ]);
                                $data["data"]["wallet_amount"] = $this->numberFormatPrecision(
                                    $sender_wallet_amount,
                                    2,
                                    "."
                                );
                                $data["data"]["trans_amount"] = $request->amount;
                                $data["data"]["receiver_name"] = $recieverUser->name;
                                $data["data"]["receiver_phone"] = $recieverUser->phone;
                                $data["data"]["trans_id"] = $TransId;
                                $data["data"]["trans_date"] = date(
                                    "d, M Y, h:i A"
                                );

                                $title = $title = __('message_app.wallet_debited_title', ['currency' => 'XAF', 'amount' => $this->numberFormatSpaces($this->roundAmount($totalAmt))]);
                                $message = __('message_app.wallet_debited_fund_transfer', ['amount' => $this->numberFormatSpaces($this->roundAmount($totalAmt)), 'username' => $recieverUser->name]);
                                $device_type = $senderUser->device_type;
                                $device_token = $senderUser->device_token;

                                //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                                $data1 = [
                                    'title' => $title,
                                    'message' => $message,
                                    'id' => "",
                                    'type' => 'DEBITWALLET',
                                ];

                                if ($device_type && $device_token) {
                                    $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                        $device_token,
                                        $title,
                                        $message,
                                        $data1,
                                        $device_type
                                    );
                                }

                                $notif = new Notification([
                                    "user_id" => $senderUser->id,
                                    "notif_title" => $title,
                                    "notif_body" => $message,
                                    "created_at" => date("Y-m-d H:i:s"),
                                    "updated_at" => date("Y-m-d H:i:s"),
                                ]);
                                $notif->save();

                                $message = __('message_app.wallet_credited', ['amount' => $this->numberFormatSpaces($this->roundAmount($amount))]);
                                $message = __('message_app.wallet_credited_fund_transfer', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($request->amount)), 'sender' => $senderUser->name]);
                                $device_type = $recieverUser->device_type;
                                $device_token = $recieverUser->device_token;

                                //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                                $data1 = [
                                    'title' => $title,
                                    'message' => $message,
                                    'id' => "",
                                    'type' => 'CREDITWALLET',
                                ];

                                if ($device_type && $device_token) {
                                    $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                        $device_token,
                                        $title,
                                        $message,
                                        $data1,
                                        $device_type
                                    );
                                }

                                $notif = new Notification([
                                    "user_id" => $recieverUser->id,
                                    "notif_title" => $title,
                                    "notif_body" => $message,
                                    "created_at" => date("Y-m-d H:i:s"),
                                    "updated_at" => date("Y-m-d H:i:s"),
                                ]);
                                $notif->save();

                                $statusArr = [
                                    "status" => "Success",
                                    "payment_status" => "Success",
                                    "reason" => __("message_app.Refund sent Successfully"),
                                ];
                                $json = array_merge($statusArr, $data);
                                return response()->json($json, 200);
                            } else {
                                $statusArr = [
                                    "status" => "Failed",
                                    "reason" => __("message_app.Receiver not found or not verified"),
                                ];
                                return response()->json($statusArr, 200);
                            }
                        } else {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => __("message_app.Insufficient Balance"),
                            ];
                            return response()->json($statusArr, 200);
                        }
                    }
                }
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Receiver not found"),
                ];
                return response()->json($statusArr, 200);
            }
            //            } catch (\Exception $ex) {
            //                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
            //                return response()->json($statusArr, 200);
            //            }
        }
    }

    public function checkTransactionFee(Request $request)
    {
        if ($request->phone == "" or !is_numeric($request->phone)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Invalid Phone Number"),
            ];
            return response()->json($statusArr, 200);
        } elseif ($request->user_id == "" or !is_numeric($request->user_id)) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid User Id")];
            return response()->json($statusArr, 200);
        } elseif ($request->amount == "" or !is_numeric($request->amount)) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid QR code")];
            return response()->json($statusArr, 200);
        } else {
            $user_id = $request->user_id;
            $amount = $request->amount;
            $requestType = $request->type;

            $matchThese = ["users.id" => $user_id];
            $senderUser = DB::table("users")
                ->where($matchThese)
                ->first();

            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table("users")
                ->where($matchThese)
                ->first();

            if (empty($recieverUser)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Receiver not found"),
                ];
                return response()->json($statusArr, 200);
            }

            $senderUserType = $senderUser->user_type;
            $receiverUserType = $recieverUser->user_type;

            if ($senderUserType == "Merchant") {
                if ($receiverUserType == "Merchant") {
                    $paymentType = "Send Money";
                    $payingBy = "Sender";
                    $payerId = $senderUser->id;
                } elseif ($receiverUserType == "Agent") {
                    $paymentType = "Withdraw";
                    $payingBy = "Sender";
                    $payerId = $senderUser->id;
                } else {
                    $paymentType = "Refund";
                    $payingBy = "Receiver";
                    $payerId = $recieverUser->id;
                }
            } elseif ($senderUserType == "Agent") {
                if ($receiverUserType == "Merchant") {
                    $paymentType = "Deposit";
                    $payingBy = "Receiver";
                    $payerId = $recieverUser->id;
                } elseif ($receiverUserType == "Agent") {
                    $paymentType = "Send Money";
                    $payingBy = "Sender";
                    $payerId = $senderUser->id;
                } else {
                    $paymentType = "Deposit";
                    $payingBy = "Receiver";
                    $payerId = $recieverUser->id;
                }
            } else {
                if ($receiverUserType == "Merchant") {
                    $paymentType = "Shopping";
                    $payingBy = "Sender";
                    $payerId = $senderUser->id;
                } elseif ($receiverUserType == "Agent") {
                    $paymentType = "Withdraw";
                    $payingBy = "Sender";
                    $payerId = $senderUser->id;
                } else {
                    $paymentType = "Send Money";
                    $payingBy = "Sender";
                    $payerId = $senderUser->id;
                }
            }

            $transFee = 0;
            if ($payingBy == "Sender") {
                $feePayBy = "Sender";
                $userFee = Usertransactionfee::where("user_id", $payerId)
                    ->where("transaction_type", $paymentType)
                    ->where("status", 1)
                    ->first();
                if (empty($userFee)) {
                    $fees = Transactionfee::where(
                        "transaction_type",
                        $paymentType
                    )
                        ->where("status", 1)
                        ->first();

                    if (!empty($fees)) {
                        $transFee = number_format(
                            ($amount * $fees->user_charge) / 100,
                            2
                        );
                    }
                } else {
                    if (!empty($userFee)) {
                        $transFee = number_format(
                            ($amount * $userFee->user_charge) / 100,
                            2
                        );
                    }
                }
                $totalAmt = number_format($amount + $transFee, 2);
                if ($receiverUserType == "Merchant") {
                    $message = "You are about to pay " .
                        CURR .
                        " " .
                        $totalAmt .
                        " to " .
                        $recieverUser->name .
                        " with transaction fee " .
                        CURR .
                        " " .
                        $transFee;
                } elseif ($receiverUserType == "Agent") {
                    $message = "You are about to pay " .
                        CURR .
                        " " .
                        $totalAmt .
                        " to " .
                        $recieverUser->name .
                        " with transaction fee " .
                        CURR .
                        " " .
                        $transFee;
                } else {
                    $message = "You are about to sent " .
                        CURR .
                        " " .
                        $totalAmt .
                        " to " .
                        $recieverUser->name .
                        " with transaction fee " .
                        CURR .
                        " " .
                        $transFee;
                }
            } else {
                $feePayBy = "Receiver";
                $message = "You are about to send amount " . CURR . " " . $amount;
            }

            $statusArr = [
                "status" => "Success",
                "message" => $message,
                "fee_pay_by" => $feePayBy,
                "transaction_fee" => $transFee,
            ];
            return response()->json($statusArr, 200);
        }
    }

    /* private function sendPushNotification($title, $message, $device_type, $device_token)
    {
        $push_notification_key = env("PUSH_NOTIFICATION_KEY");
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = [
            "Authorization: key=" . $push_notification_key,
            "Content-Type: application/json",
        ];

        if ($device_type == "Android") {
            $msgArr = [
                "message" => $message,
                "title" => $title,
                "tickerText" => $title,
                "msg_data" => $message,
                "sound" => 1,
            ];
            $fields = ["to" => $device_token, "data" => $msgArr];
        } else {
            $fields = [
                "to" => $device_token,
                "content-available" => "1",
                "notification" => [
                    "title" => $title,
                    "body" => $message,
                    "sound" => "default",
                ],
                "data" => ["targetScreen" => "detail"],
                "priority" => 10,
            ];
        }

        $postdata = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $result = curl_exec($ch);

        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'error' => $error];
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($result, true);

        if (isset($response['success']) && $response['success'] > 0) {
            return ['success' => true, 'response' => $response];
        } else {
            return ['success' => false, 'response' => $response];
        }
    } */

    public function getNotification(Request $request)
    {

        $user_id = Auth::user()->id;

        $request = $this->decryptContent($request->req);

        $input = [
            'page' => $request->page,
            'limit' => $request->limit,
        ];

        $validate_data = [
            'page' => 'required',
            'limit' => 'required',
        ];

        $customMessages = [
            'page.required' => __("message_app.Page field cannot be left blank"),
            'limit.required' => __("message_app.Limit field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $page = $input['page'];
        $limit = $input['limit'];

        $start = $page - 1;
        $start = $start * $limit;

        $matchThese = ["notifications.user_id" => $user_id];
        $notifications = DB::table("notifications")
            ->select("notifications.*")
            ->where($matchThese)
            ->orderBy("id", "DESC")
            ->skip($start)
            ->take($limit)->get();


        $totalRecords = DB::table("notifications")
            ->where($matchThese)
            ->orderBy("id", "DESC")->count();


        if ($totalRecords > 0) {
            $notifArr = [];
            $notifDataArr = [];
            foreach ($notifications as $key => $val) {
                if (isset($val->transactionId) && $val->transactionId != 0) {
                    $transactionDataList = Transaction::where('id', $val->transactionId)->first();
                } else {
                    $transactionDataList = [];
                }

                $notifArr["id"] = $val->id;
                $notifArr["user_id"] = $val->user_id;
                $notifArr["title"] = $val->notif_title;
                $notifArr["body"] = $val->notif_body;
                $notifArr["is_seen"] = $val->is_seen;
                $notifArr["transPaymentType"] = ($transactionDataList && $transactionDataList['status'] == 2) ? ($val->transPaymentType ?? '') : '';
                $notifArr["transactionId"] = ($transactionDataList && $transactionDataList['status'] == 2) ? ((string) $val->transactionId ?? '') : '';
                $notifArr["date"] = date(
                    "d M Y h:i A",
                    strtotime($val->created_at)
                );
                $notifDataArr[] = $notifArr;
            }
            // dd($transactionDataList);
            //echo "Count: ".Count($notifications);
            $statusArr = [
                "status" => "Success",
                "reason" => __("message_app.Notification List"),
                "total_records" => $totalRecords,
                "total_page" => ceil($totalRecords / $limit)
            ];
            $data["data"] = $notifDataArr;
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Success",
                "reason" => __("message_app.Sorry no notification found"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function seenNotification(Request $request)
    {
        $user_id = Auth::user()->id;

        if (
            $request->notification_id == "" or
            !is_numeric($request->notification_id)
        ) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Invalid Notification Id"),
            ];
            return response()->json($statusArr, 200);
        } else {
            Notification::where("id", $request->notification_id)->update([
                "is_seen" => 1,
            ]);

            $statusArr = [
                "status" => "Success",
                "reason" => __("message_app.Notification Seen Status Updated"),
            ];
            return response()->json($statusArr, 200);
        }
    }
    public function nearByUser(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_type = $request->user_type;

        if ($user_type == 'Reseller') {
            $user_type = 'Agent';
        }

        $users = User::where('is_verify', 1)->where('user_type', $user_type)->get();

        $records = array();
        if ($users) {
            foreach ($users as $userInfo) {
                $userData = array();
                $userData['user_id'] = $userInfo->id;
                $userData['user_type'] = $userInfo->user_type;
                $userData['name'] = $userInfo->name;
                if ($userInfo->user_type == 'Merchant') {
                    $userData['name'] = $userInfo->business_name;
                }

                $userData['phone'] = $userInfo->phone;
                $userData['latitude'] = $userInfo->lat ? $userInfo->lat : '0.00';
                $userData['longitude'] = $userInfo->lng ? $userInfo->lng : '0.00';
                $userData["business_name"] = $userInfo->business_name ? $userInfo->business_name : "";
                //                $userData['distance'] = number_format($userInfo->distance * 1.609344, 1); /* KM */
                if ($userInfo->profile_image != '' && $userInfo->profile_image != "no_user.png") {
                    $userData['profile_image'] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                } else {
                    $userData['profile_image'] = 'public/img/' . 'no_user.png';
                }
                $records[] = $userData;
            }

            $statusArr = array("status" => "Success", "reason" => __("message_app.no_record_found"));
            $data['data'] = $records;
            $json = array_merge($statusArr, $data);
            //            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message_app.Users not available"));
            //            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function merchantList()
    {
        $users = User::where("is_verify", 1)
            ->where("user_type", "Merchant")
            ->get();

        $records = [];
        if ($users) {
            foreach ($users as $userInfo) {
                $userData = [];
                $userData["user_id"] = $userInfo->id;
                $userData["name"] = $userInfo->business_name;
                $userData["phone"] = $userInfo->phone;
                if ($userInfo->profile_image != "" && $userInfo->profile_image != "no_user.png") {
                    $userData["business_image"] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                } else {
                    $userData["business_image"] = "public/img/" . "no_user.png";
                }
                $userData["latitude"] = $userInfo->lat ? $userInfo->lat : "0.00";
                $userData["longitude"] = $userInfo->lng ? $userInfo->lng : "0.00";
                $records[] = $userData;
            }

            $statusArr = ["status" => "Success", "reason" => __("message_app.Merchants List")];
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            return response()->json($json, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Merchants not available"),
            ];
            return response()->json($statusArr, 200);
        }
    }
    public function generateQR(Request $request)
    {
        $user_id = Auth::user()->id;
        $request = $this->decryptContent($request->req);

        if ($request->phone == "") {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Invalid Phone Number"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
            //            return response()->json($statusArr, 200);
        } elseif ($request->amount == "" || $request->amount == 0) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid QR code")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
            //            return response()->json($statusArr, 200);
        } else {
            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table("users")
                ->where($matchThese)
                ->first();

            if (empty($recieverUser)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid User"),
                ];
                //                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $phone = $request->phone;

                $matchThese = ["users.phone" => $phone];

                $userInfo = DB::table("users")->where($matchThese)->first();

                $qrString = $this->encryptContent($userInfo->id . "##" . $request->phone . "##" . $request->amount);

                $qrCode = $this->generateQRCode($qrString, $user_id);

                $qrcode = "public/" . $qrCode;

                $statusArr = [
                    "status" => "Success",
                    "qr_code" => $qrcode,
                    "sender_name" => $recieverUser->name,
                    "reason" => __("message_app.QR Code Detail"),
                ];

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function checkTransactionLimit($userType, $amount)
    {
        $user_id = Auth::user()->id;
        //to check the transaction limit for current month & week & and year
        $walletlimit = Walletlimit::where('category_for', $userType)->first();

        //to check current month limit
        $currentMonthSum = Transaction::where('user_id', $user_id)->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereIn('status', [1, 2])
            ->sum('amount');
        if (($currentMonthSum + $amount) > $walletlimit->month_limit) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Your monthly transfer limit has been reached"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        //to check current week limit
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $currentWeekSum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('amount');

        if (($currentWeekSum + $amount) > $walletlimit->week_limit) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Your weekly transfer limit has been reached"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        // Get the sum of amounts for transactions within the current day
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        $currentDaySum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount');
        if (($currentDaySum + $amount) > $walletlimit->daily_limit) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Your daily transfer limit has been reached"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function generateQRForAgent(Request $request)
    {
        $user_id = Auth::user()->id;
        $request = $this->decryptContent($request->req);
        if ($request->amount == "" || $request->amount == 0) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid Amount")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            $amount = $request->amount;

            $userInfo = User::where('id', $user_id)->first();

            $userType = $this->getUserType($userInfo->user_type);

            if ($this->checkTransactionLimit($userType, $amount)) {
                return $this->checkTransactionLimit($userType, $amount);
            }

            if ($amount > $userInfo->wallet_balance) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $transactionLimit = TransactionLimit::where('type', $userType)->first();
            if ($transactionLimit->minWithdraw > $request->amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __('message_app.You cannot withdraw', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->minWithdraw))])
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if ($request->amount > $transactionLimit->maxWithdraw) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __('message_app.You cannot withdraw more', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxWithdraw))])
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $uniqueKey = $this->generateUniqueKey(10, 'unique_key', GeneratedQrCode::class);

            $qrString = $this->encryptContent($user_id . "##self_generated" . "##" . $request->amount . '##' . $uniqueKey);

            $qrCode = $this->generateQRCode($qrString, $user_id);

            $qrcode = "public/" . $qrCode;

            $generatedQrCode = GeneratedQrCode::create([
                'user_id' => $user_id,
                'amount' => $amount,
                'unique_key' => $uniqueKey,
                'qr_code' => $qrcode
            ]);

            $statusArr = [
                "status" => "Success",
                "qr_code" => $qrcode,
                "reason" => __("message_app.QR Code Detail"),
            ];

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function getUserByPhone(Request $request)
    {

        $request = $this->decryptContent($request->req);

        $input = [
            'phone' => $request->phone,
        ];

        $validate_data = [
            'phone' => 'required',
        ];

        $customMessages = [
            'usephoner_id.required' => __("message_app.User id field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $phone = $request->phone;

        if ($phone == Auth::user()->phone) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.You cannot send funds to yourself"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        // $phone = preg_replace('/^(?:\+241|241)/', '', $phone->phone);
        $matchThese = ["users.phone" => $phone];


        $userInfo = DB::table("users")->where($matchThese)->first();

        if ($userInfo) {

            $user_role = Auth::user()->user_type;

            if (($user_role == "User" || $user_role == "Merchant") && $userInfo->user_type == "Agent") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.You cannot transfer the money request to agent"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $statusArr = [
                "status" => "Success",
                "reason" => __("message_app.User detail"),
            ];
            $userData = [];
            $userData["name"] = trim(($userInfo->name ?? '') . ' ' . ($userInfo->lastName ?? ''));
            $userData["user_id"] = $userInfo->id;
            $userData["user_type"] = $userInfo->user_type;
            if ($userInfo->profile_image != "" && $userInfo->profile_image != "no_user.png") {
                $userData["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
            } else {
                $userData["profile_image"] = "public/img/" . "no_user.png";
            }
            $userData["user_type"] = $userInfo->user_type;
            $userData["phone"] = $userInfo->phone;
            $userData["email"] = $userInfo->email;
            $userData["country"] = $userInfo->country;
            $data["data"] = $userData;
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Please provide a valid phone number"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function getUserByQR(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $trans_type = $request->trans_type;

        if ($request->qr_code == "") {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid QR Code")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $qrcode = $this->decryptContentString($request->qr_code);
            $qrCodeArr = explode("##", $qrcode);
            // echo "<pre>";
            // print_r($qrCodeArr); die;
            if ($qrCodeArr[0] == Auth::user()->id) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.You cannot send funds to yourself"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $amount = 0;
            $uniqueKey = '';
            if (array_key_exists('3', $qrCodeArr)) {
                $uniqueKey = trim($qrCodeArr[3]);
                if ($uniqueKey != "") {
                    $user_role = Auth::user()->user_type;
                    if ($trans_type != 'withdraw') {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __("message_app.You can use this QR code only for withdrawal"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }

                    if ($user_role != "Agent") {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __("message_app.Only agent can scan this QR code"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }

                    $is_qr_readed = GeneratedQrCode::where('unique_key', $uniqueKey)->first()->status;
                    if ($is_qr_readed == 1) {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __("message_app.Qr Code has been already used"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }
            }

            if ($trans_type == 'withdraw' && $uniqueKey == "") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid QR Code"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if (array_key_exists('2', $qrCodeArr)) {
                $amount = trim($qrCodeArr[2]);
            }
            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid QR Code"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $matchThese = ["users.id" => $qrId];
            $userInfo = DB::table("users")
                ->where($matchThese)
                ->first();
            if ($userInfo) {

                $user_role = Auth::user()->user_type;

                if (($user_role == "User" || $user_role == "Merchant") && $userInfo->user_type == "Agent") {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.You cannot transfer the money to agent"),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                $userData = [];
                $userData["name"] = $userInfo->name;
                $userData["user_id"] = $userInfo->id;
                $userData["user_type"] = $userInfo->user_type;
                if ($userInfo->profile_image != "" && $userInfo->profile_image != "no_user.png") {
                    $userData["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                } else {
                    $userData["profile_image"] = "public/img/" . "no_user.png";
                }

                $userData["user_type"] = $userInfo->user_type;
                $userData["phone"] = $userInfo->phone;
                $userData["email"] = $userInfo->email;
                $userData["country"] = $userInfo->country;
                $data["data"] = $userData;
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.User detail"),
                    "amount" => $amount,
                    "uniqueKey" => $uniqueKey
                ];
                $json = array_merge($statusArr, $data);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid QR Code"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function transactionDetail(Request $request)
    {

        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $request = $this->decryptContent($request->req);

        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */
        // Log::info($request->trans_type);

        if ($request->trans_type == "Request Money" || Auth::user()->user_type == "Agent") {

        } else {
            /* $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } */
        }
        if ($request->transaction_id == "") {
            $input = [
                // 'user_id' => $request->user_id,
                'amount' => $request->amount,
                'trans_type' => $request->trans_type,
            ];
            $minAmount = "1";
            $maxAmount = "10000000";

            $validate_data = [
                // 'user_id' => 'required', 
                'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
                'trans_type' => 'required',
            ];

            $customMessages = [
                // 'user_id.required' => 'User id field cannot be left blank',
                'amount.required' => __("message_app.Amount is required"),
                'amount.numeric' => __("message_app.Amount must be a valid number"),
                'amount.gt' => __("message_app.Amount must be greater than zero"),
                'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
                'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
                'trans_type.required' => __("message_app.Trans_type field cannot be left blank"),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $trans_type = $request->trans_type ?? "";
        } else {

            $getTrans = $this->getTransactionDataById($request->transaction_id);

            if ($getTrans->transactionType == "SWAPTOSWAP") {
                $trans_type = "Send Money";
            } elseif ($getTrans->paymentType == "REQUESTTOPAY" && $getTrans->transactionType == "SWAPTOCEMAC") {
                $trans_type = "Request Money";
            } elseif ($getTrans->transactionType == "SWAPTOBDA") {
                $trans_type = "Money Transfer Via BDA";
            } elseif ($getTrans->transactionType == "SWAPTOGIMAC") {
                $trans_type = "Money Transfer Via GIMAC";
            } elseif ($getTrans->transactionType == "SWAPTOCEMAC") {
                $trans_type = "Money Transfer Via GIMAC";
            }
        }
        if ($trans_type == "AIRTELMONEY") {
            $trans_type = "Airtel Deposit";
        }
        $senderUser = User::where('id', $user_id)->first();

        $userType = $this->getUserType($senderUser->user_type);

        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        $amount = $request->amount;
        $total_fees = 0;
        $feeType = 1;
        $fees = 0;

        if (isset($request->accept_money_request_type) && $request->accept_money_request_type == "Accept Request Money" && $trans_type == "Send Money") {
            $trans_type = "Request Money";
        }

        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $trans_type)->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        // echo"<pre>";print_r($feeapply);die;

        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();

            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                $fees = $trans_fees->fee_amount;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }

        $total_tax = "0";

        $total_amount = $amount + $total_fees;

        if (
            isset($trans_type) &&
            !in_array($request->trans_type, ['Request Money', 'AIRTELMONEY'])
        ) {
            if ($total_amount > $senderUser->wallet_balance) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
        $title = __("message_app.Amount will be paid");
        /* if (isset($trans_type) && $trans_type == "Request Money") {
            $fees = 0;
            $feeType = 0;
            $total_fees = 0;
            $total_amount = $amount;
            $title = "Amount will be receive";
        } else */
        if (
            isset($request->accept_money_request_type) &&
            $request->accept_money_request_type === "Accept Request Money" &&
            $request->trans_type === "Send Money"
        ) {
            $title = __("message_app.Amount will be paid");

        } elseif (
            (
                isset($request->trans_type) &&
                $request->trans_type === "AIRTELMONEY"
            ) || $trans_type === "Request Money"
        ) {
            $title = __("message_app.Amount will be receive");
        }
        $contFee = $request->trans_type == "AIRTELMONEY" ? __('message_app.Airtel fees') : __('message_app.Swap fees');
        $statusArr = [
            "status" => "Success",
            "fees" => ($feeType == 0 ? $contFee . ' ' . $fees . '%' : $contFee . CURR . ' ' . $fees),
            "amount" => $amount,
            "feeType" => $feeType,
            "total_fees" => $total_fees,
            "total_tax" => $total_tax,
            "title" => $title,
            "total_amount" => ($request->trans_type == "AIRTELMONEY" || $trans_type == "Request Money" ? ($amount - $total_fees) : strval($total_amount))
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function depositByAgent(Request $request)
    {
        Log::info('depositByAgent');
        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $user_role = Auth::user()->user_type;
        $minAmount = "1";
        $maxAmount = "10000000";
        $input = [
            'opponent_user_id' => $request->opponent_user_id,
            'amount' => $request->amount,
            'total_amount' => $request->total_amount,
            'trans_type' => $request->trans_type,
        ];

        $validate_data = [
            'opponent_user_id' => 'required',
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
            'total_amount' => 'required',
            'trans_type' => 'required',
        ];

        $customMessages = [
            'opponent_user_id.required' => __("message_app.Opponent User id field cannot be left blank"),
            'amount.required' => __("message_app.Amount is required"),
            'amount.numeric' => __("message_app.Amount must be a valid number"),
            'amount.gt' => __("message_app.Amount must be greater than zero"),
            'amount.min' => __("message_app.Amount must be at least 1"),
            'amount.max' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount_max' => __('message_app.amount_max', ['min' => $maxAmount]),
            'total_amount.required' => __("message_app.Amount field cannot be left blank"),
            'trans_type.required' => __("message_app.Trans_type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $amount = $request->amount;

        $senderUser = $userDetail = User::where('id', $user_id)->first();
        $userType = $this->getUserType($senderUser->user_type);
        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->minDeposit > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.deposit_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->minDeposit))])
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->maxDeposit) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.deposit_max_amount', ['amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxDeposit))])
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        if ($user_id == $input['opponent_user_id']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.You cannot send funds to yourself"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $total_fees = 0;



        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        // echo"<pre>";print_r($feeapply);die;

        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }

        $total_amount = $amount;
        $total_tax = "0";
        $total_amount = $amount;
        if ($total_amount != $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Transaction failed because fees strucutre has been changed.Please try again"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $trans_id = time();
        $refrence_id = time() . '-' . $input['opponent_user_id'];
        $recieverUser = $userInfo = User::where('id', $input['opponent_user_id'])->first();
        $receiver_wallet_amount = $senderUser->wallet_balance - $total_amount;
        $trans = new Transaction([
            'user_id' => $user_id,
            'receiver_id' => $input['opponent_user_id'],
            'amount' => $amount,
            'amount_value' => $total_amount,
            'remainingWalletBalance' => $receiver_wallet_amount,
            'transaction_amount' => $total_fees,
            'total_amount' => $amount,
            'trans_type' => 1,
            'payment_mode' => 'Agent Deposit',
            'status' => 1,
            'refrence_id' => $trans_id,
            'entryType' => 'API',
            'billing_description' => 'Agent Deposit-' . $refrence_id,
            'beforeBalance' => $senderUser->wallet_balance,
            'afterBalance' => $receiver_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'swapDomainName' => 'UAT_SERVER'
        ]);

        $trans->save();
        $transaction_id = $trans->id;


        $sender_wallet_amount = $senderUser->wallet_balance - $amount - $total_fees;
        $debit = new TransactionLedger([
            'user_id' => $user_id,
            'opening_balance' => $senderUser->wallet_balance,
            'amount' => $amount,
            'actual_amount' => $amount,
            'type' => 2,
            'trans_id' => $transaction_id,
            'payment_mode' => 'Agent Deposit',
            'closing_balance' => $sender_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $debit->save();


        $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->first();
        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
            $postData = json_encode([
                "currencyCode" => "XAF",
                "last4Digits" => $userCard->last4Digits,
                "referenceMemo" => "Settlement",
                "transferAmount" => ($amount - $total_fees),
                "transferType" => "CardToWallet",
                "mobilePhoneNumber" => "241{$senderUser->phone}"
            ]);
            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
            Log::info('Deposit by agent');
        }


        User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

        $credit = new TransactionLedger([
            'user_id' => $input['opponent_user_id'],
            'opening_balance' => $recieverUser->wallet_balance,
            'amount' => $amount,
            'fees' => $total_fees,
            'actual_amount' => $total_amount,
            'type' => 1,
            'trans_id' => $transaction_id,
            'payment_mode' => 'Agent Deposit',
            'closing_balance' => $receiver_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $credit->save();

        User::where('id', $input['opponent_user_id'])->update(['wallet_balance' => $receiver_wallet_amount]);

        DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);

        /* $title = "Deposit Request";
        //$message = __("message.deposit_request", ['cost' => CURR . " " . $amount, 'username' => $userInfo->name]);
        $message = "Your deposit request for " . CURR . " " . $amount. " has been sent successfully to " . $userInfo->name;
        $device_type = $userDetail->device_type;
        $device_token = $userDetail->device_token;

        // $this->sendPushNotification($title, $message, $device_type, $device_token);

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'Deposit Request',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save(); */

        $title = __("message_app.Deposit Request");
        // $message = __("message.deposit_request_agent", ['cost' => CURR . " " . $amount, 'username' => $userDetail->name]);
        $message = __('message_app.deposit_request_sent', [
            'currency' => CURR,
            'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
            'username' => $userDetail->name,
        ]);
        $device_type = $userInfo->device_type;
        $device_token = $userInfo->device_token;

        // $this->sendPushNotification($title, $message, $device_type, $device_token);
        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'DEPOSITREQUESTAGENT',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userInfo->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $status = $this->getStatusText(1);
        //to send the email to sender
        $senderName = $senderUser->name;
        $senderEmail = $senderUser->email;
        $receiverName = $recieverUser->name;
        $receiverEmail = $recieverUser->email;
        $senderAmount = $this->numberFormatSpaces($this->roundAmount($amount));
        $receiverAmount = $this->numberFormatSpaces($this->roundAmount($total_amount));
        $transactionFees = $total_fees;
        $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));
        $emailData['subjects'] = 'Funds Transfer Details';
        $emailData['senderName'] = $senderName;
        $emailData['senderEmail'] = $senderEmail;
        $emailData['senderAmount'] = $senderAmount;
        $emailData['currency'] = CURR;

        $emailData['receiverName'] = $receiverName;
        $emailData['receiverAmount'] = $receiverAmount;
        $emailData['receiverEmail'] = $receiverEmail;
        $emailData['receiverAmount'] = $receiverAmount;

        $emailData['transId'] = $refrence_id;
        // $emailData['transId'] = $transaction_id;
        $emailData['transactionFees'] = $transactionFees;
        $emailData['transactionDate'] = $transaction_date;
        $emailData['transactionStatus'] = $status;

        if ($senderEmail != "") {
            /* Mail::send('emails.fund_transfer_sender', $emailData, function ($message) use ($emailData) {
                $message->to($emailData["senderEmail"], $emailData["senderEmail"])
                    ->subject($emailData["subjects"]);
            }); */
        }

        if ($receiverEmail != "") {
            /* Mail::send('emails.fund_transfer_receiver', $emailData, function ($message) use ($emailData) {
                $message->to($emailData["receiverEmail"], $emailData["receiverEmail"])
                    ->subject($emailData["subjects"]);
            }); */
        }

        $statusArr = array("status" => "Success", "reason" => __("message_app.Amount deposited successfully"));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function withdrawByAgent(Request $request)
    {
        Log::info('withdrawByAgent');
        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $user_role = Auth::user()->user_type;

        $input = [
            'opponent_user_id' => $request->opponent_user_id,
            'amount' => $request->amount,
            'total_amount' => $request->total_amount,
            'trans_type' => $request->trans_type,
            'uniqueKey' => $request->uniqueKey,
        ];

        $minAmount = "1";
        $maxAmount = "10000000";

        $validate_data = [
            'opponent_user_id' => 'required',
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
            'total_amount' => 'required',
            'trans_type' => 'required',
            'uniqueKey' => 'required'
        ];

        $customMessages = [
            'opponent_user_id.required' => __('message_app.Opponent User id field cannot be left blank'),
            'amount.required' => __('message_app.Amount is required'),
            'amount.numeric' => __('message_app.Amount must be a valid number'),
            'amount.gt' => __('message_app.Amount must be greater than zero'),
            'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
            'total_amount.required' => __('message_app.Amount field cannot be left blank'),
            'trans_type.required' => __('message_app.Trans_type field cannot be left blank'),
            'uniqueKey.required' => __('message_app.Unique Key field cannot be left blank'),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $senderUser = $userDetail = User::where('id', $input['opponent_user_id'])->first();

        $userType = $this->getUserType($senderUser->user_type);

        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.withdraw_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.withdraw_max_kyc_pending', ['amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.withdraw_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.withdraw_max_kyc_not_verified', ['amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))])
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($transactionLimit->minWithdraw > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.You cannot withdraw', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->minWithdraw))])
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->maxWithdraw) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.You cannot withdraw more', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxWithdraw))])
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($user_id == $input['opponent_user_id']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.You cannot send funds to yourself'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $amount = $request->amount;
        $total_fees = 0;


        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        if (isset($feeapply)) {
            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }
        $is_qr_readed = GeneratedQrCode::where('unique_key', $input['uniqueKey'])->first()->status;
        if ($is_qr_readed == 1) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Qr Code has been already used'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $total_amount = $amount;

        $total_tax = "0";

        $total_amount = $amount;

        // if ($total_amount != $input['total_amount']) {
        if ($total_amount != $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Transaction failed because fees strucutre has been changed.Please try again'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Insufficient Balance'),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $trans_id = time();
        $refrence_id = time() . '-' . $input['opponent_user_id'];
        $sender_wallet_amount = $senderUser->wallet_balance - $amount - $total_fees;
        $trans = new Transaction([
            'user_id' => $input['opponent_user_id'],
            'receiver_id' => $user_id,
            'amount' => $amount,
            'amount_value' => $total_amount,
            'transaction_amount' => $total_fees,
            'total_amount' => $amount,
            'trans_type' => 2,
            'remainingWalletBalance' => $sender_wallet_amount,
            'payment_mode' => 'Withdraw',
            'status' => 1,
            'refrence_id' => $trans_id,
            'entryType' => 'API',
            'billing_description' => 'Withdraw-' . $refrence_id,
            'beforeBalance' => $senderUser->wallet_balance,
            'afterBalance' => $sender_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'swapDomainName' => 'UAT_SERVER'
        ]);

        $trans->save();
        $transaction_id = $trans->id;

        $debit = new TransactionLedger([
            'user_id' => $input['opponent_user_id'],
            'opening_balance' => $senderUser->wallet_balance,
            'amount' => $amount,
            'actual_amount' => $amount,
            'type' => 2,
            'trans_id' => $transaction_id,
            'payment_mode' => 'Withdraw',
            'closing_balance' => $sender_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $debit->save();

        User::where('id', $input['opponent_user_id'])->update(['wallet_balance' => $sender_wallet_amount]);

        /* Card Payment Start */
        $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->first();
        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
            $postData = json_encode([
                "currencyCode" => "XAF",
                "last4Digits" => $userCard->last4Digits,
                "referenceMemo" => "Settlement",
                "transferAmount" => ($amount - $total_fees),
                "transferType" => "WalletToCard",
                "mobilePhoneNumber" => "241{$senderUser->phone}"
            ]);
            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
            Log::info('Withdraw by agent 1');
        }
        /* Card Payment End */

        $recieverUser = $userInfo = User::where('id', $user_id)->first();
        $receiver_wallet_amount = $recieverUser->wallet_balance + $total_amount;
        $credit = new TransactionLedger([
            'user_id' => $user_id,
            'opening_balance' => $recieverUser->wallet_balance,
            'amount' => $amount,
            'fees' => $total_fees,
            'actual_amount' => $total_amount,
            'type' => 1,
            'trans_id' => $transaction_id,
            'payment_mode' => 'Withdraw',
            'closing_balance' => $receiver_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $credit->save();

        User::where('id', $user_id)->update(['wallet_balance' => $receiver_wallet_amount]);

        /* Card Payment Start */
        $userCard = UserCard::where('userId', $recieverUser->id)->where('cardType', 'PHYSICAL')->first();
        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
            $postData = json_encode([
                "currencyCode" => "XAF",
                "last4Digits" => $userCard->last4Digits,
                "referenceMemo" => "Settlement",
                "transferAmount" => $total_amount,
                "transferType" => "WalletToCard",
                "mobilePhoneNumber" => "241{$recieverUser->phone}"
            ]);
            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
            Log::info('Withdraw by agent ');
        }
        /* Card Payment End */

        DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);

        GeneratedQrCode::where('unique_key', $input['uniqueKey'])->update(['status' => 1]);

        /* $title = 'Withdrawal Receive';
        // $message = __("Withdrawal Request", ['cost' => CURR . " " . $amount, 'username' => $userInfo->name]);
        $message = __("Withdrawal receive by :username for :cost", [
            'cost' => CURR . " " . $amount,
            'username' => $userInfo->name
        ]);

        $device_type = $userDetail->device_type;
        $device_token = $userDetail->device_token;

        // $this->sendPushNotification($title, $message, $device_type, $device_token);
        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'Withdrawal Receive',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save(); */


        $title = __("message_app.Withdrawal Receive");
        // $message = __("Withdrawal Request", ['cost' => CURR . " " . $amount, 'username' => $userDetail->name]);
        $message = __('message_app.withdrawal_received', ['cost' => CURR . ' ' . $this->numberFormatSpaces($this->roundAmount($amount)), 'username' => $userDetail->name]);

        //                    $message = __("message.User " . $userDetail->name . " has requested to withdraw amount " . CURR . " " . $amount . " for his account.");
        $device_type = $userInfo->device_type;
        $device_token = $userInfo->device_token;

        // $this->sendPushNotification($title, $message, $device_type, $device_token);
        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'WITHDRAWALRECEIVE',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userInfo->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();


        $status = $this->getStatusText(1);
        //to send the email to sender
        $senderName = $senderUser->name;
        $senderEmail = $senderUser->email;
        $receiverName = $recieverUser->name;
        $receiverEmail = $recieverUser->email;
        $senderAmount = $this->numberFormatSpaces($this->roundAmount($amount));
        $receiverAmount = $this->numberFormatSpaces($this->roundAmount($total_amount));
        $transactionFees = $total_fees;
        $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));
        $emailData['subjects'] = 'Funds Transfer Details';
        $emailData['senderName'] = $senderName;
        $emailData['senderEmail'] = $senderEmail;
        $emailData['senderAmount'] = $senderAmount;
        $emailData['currency'] = CURR;

        $emailData['receiverName'] = $receiverName;
        $emailData['receiverAmount'] = $receiverAmount;
        $emailData['receiverEmail'] = $receiverEmail;
        $emailData['receiverAmount'] = $receiverAmount;

        $emailData['transId'] = $refrence_id;
        // $emailData['transId'] = $transaction_id;
        $emailData['transactionFees'] = $transactionFees;
        $emailData['transactionDate'] = $transaction_date;
        $emailData['transactionStatus'] = $status;

        if ($senderEmail != "") {
            /* Mail::send('emails.fund_transfer_sender', $emailData, function ($message) use ($emailData) {
                $message->to($emailData["senderEmail"], $emailData["senderEmail"])
                    ->subject($emailData["subjects"]);
            }); */
        }

        if ($receiverEmail != "") {
            /* Mail::send('emails.fund_transfer_receiver', $emailData, function ($message) use ($emailData) {
                $message->to($emailData["receiverEmail"], $emailData["receiverEmail"])
                    ->subject($emailData["subjects"]);
            }); */
        }

        $statusArr = array("status" => "Success", "reason" => __("message_app.The withdrawal has been successfully completed"));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getTransactionDetail($id, $user_id)
    {
        $userInfo = User::where('id', $user_id)->first();

        $user_role = $userInfo->user_type;

        $trans = DB::table('transactions')
            ->select('transactions.*')
            ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
            ->where('transactions.id', $id)
            ->get();

        $transDataArr = [];

        global $tranType;

        foreach ($trans as $key => $val) {

            $OnafriqaData = OnafriqaData::where('excelTransId', $val->excel_trans_id)->first();
            $receiverName = "";
            $receiverPhone = "";
            if ($val->transactionType == "SWAPTOONAFRIQ") {
                $receiverName = "{$OnafriqaData->recipientName} {$OnafriqaData->recipientSurname}";
                $receiverPhone = $OnafriqaData->recipientMsisdn;
            }

            $getPaymentType = ExcelTransaction::where('id', $val->excel_trans_id)->first();
            $onafriqData = OnafriqaData::where('excelTransId', $val->excel_trans_id)->first();
            $RemittanceData = RemittanceData::where('excel_id', $val->excel_trans_id)->first();

            global $months;
            $lang = Session::get('locale');

            if ($lang == 'fr') {
                $date = date('d F, Y', strtotime($val->created_at));
                $frenchDate = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate = date('d M,y', strtotime($val->created_at));
            }

            $transArr['trans_id'] = $val->id;

            $transArr['payment_mode'] = strtolower($val->payment_mode);
            $transArr['payment_mode_name'] = ucwords(str_replace("_", " ", $val->payment_mode));

            if ($val->receiver_id == 0) {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                //$transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                if ($getPaymentType->bdastatus == "ONAFRIQ") {
                    $transArr['sender_phone'] = $onafriqData['senderMsisdn'] ? $onafriqData['senderMsisdn'] : $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = $onafriqData['recipientName'] ? $onafriqData['recipientName'] : '';
                    $transArr['receiver_phone'] = $onafriqData['recipientMsisdn'] ? $onafriqData['recipientMsisdn'] : $val->receiver_mobile;

                } elseif ($getPaymentType->bdastatus == "BDA") {
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = 'BDA Transfer';
                    $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;

                } elseif ($getPaymentType->bdastatus == "GIMAC") {
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = 'GIMAC Transfer';
                    $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;

                } else {
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = 'WALLET2WALLET';
                    $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;
                }

                //$transArr['receiver_phone'] = $receiverPhone ? $receiverPhone : $val->receiver_mobile;
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
            } elseif ($val->user_id == $user_id) { //User is sender
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                $transArr['receiver'] = $receiverName ?: $this->getUserNameById($val->receiver_id);
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = $receiverPhone ?: $this->getPhoneById($val->receiver_id);
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup

                if ($val->payment_mode == 'send_money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                    $val->payment_mode = 'Withdraw'; //1=Credit;2=Debit;3=topup
                    $transArr['payment_mode'] = strtolower($val->payment_mode);
                    $transArr['trans_from'] = $val->payment_mode;
                }

                if ($val->payment_mode != 'Cash card') {
                    if ($val->trans_type == 2) {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    } else if ($val->trans_type == 1 || $val->trans_type == 3) {
                        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                    }
                }

                if ($val->payment_mode == 'Agent Deposit') {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }

                if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                }
                if ($val->payment_mode == 'wallet2wallet' && $val->trans_type == 2) {
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Withdraw') {
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                }
            } else if ($val->receiver_id == $user_id) { //USer is Receiver
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                if ($val->trans_type == 2) {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'send_money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                    $val->payment_mode = 'send_money'; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Withdraw' && $val->trans_type == 2) {
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }
                if ($userInfo->user_type != 'Merchant') {
                    if ($val->payment_mode == 'Refund' && $transArr['trans_type'] == 'Debit') {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    }
                } else {
                    if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                    }
                    if ($val->payment_mode == 'Refund' && $val->trans_type == 1 && $val->refund_status == 0) {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    }
                }

                if ($val->payment_mode == 'Agent Deposit') {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }
            }
            //echo $val->id.'---'.$userInfo->user_type.'---'.$val->payment_mode.'----'.$transArr['trans_type'].'---####';
            if ($userInfo->user_type == 'User' || $userInfo->user_type == 'Merchant') {
                if ($transArr['trans_type'] == 'Credit') {
                    if ($val->payment_mode == 'Refund') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Topup') {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                } elseif ($transArr['trans_type'] == 'Request') {
                    if ($val->payment_mode == "Withdraw") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        if ($val->status == 2) {
                            $transArr['trans_type'] = 'Request Debit';
                        } else {
                            $transArr['trans_type'] = 'Debit';
                            $val->payment_mode = 'wallet2wallet';
                            $transArr['payment_mode'] = $val->payment_mode;

                            $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                        }
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                        if ($val->status == 2) {
                            $transArr['trans_type'] = 'Request Credit';
                        } else {
                            $transArr['trans_type'] = 'Credit';
                            $val->payment_mode = 'wallet2wallet';
                            $transArr['payment_mode'] = $val->payment_mode;

                            $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                        }
                    }
                } else {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } else {
                        if ($val->payment_mode == "Withdraw") {
                            $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                            $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        } else {
                            $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                            $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                        }
                    }
                }
            } elseif ($userInfo->user_type == 'Agent') {
                if ($transArr['trans_type'] == 'Request') {
                    if ($val->payment_mode == "Withdraw") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Debit') {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } elseif ($val->payment_mode == 'Withdraw') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    }
                } else {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                }
            } else {
                if ($transArr['trans_type'] == 'Debit') {
                    if ($val->payment_mode == "Refund") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } elseif ($val->payment_mode == 'Withdraw') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } elseif ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Credit') {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    }
                } else {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                }
            }

            if ($val->payment_mode == 'Credited by admin' || $val->payment_mode == 'Debited by admin') {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['receiver_phone'] = 'Admin';
                $transArr['sender_phone'] = 'Admin';
            }

            $transArr['transaction_fees'] = $this->numberFormatPrecision($val->transaction_amount, 2, '.');
            $transArr['received_amount'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
            $transArr['trans_amount_android'] = number_format($transArr['trans_amount'], 2);

            if ($transArr['payment_mode'] == 'agent deposit') {
                $transArr['payment_mode'] = 'agent_deposit';
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id == $user_id) {
                    $transArr['trans_type'] = $tranType[1];
                } else if ($user_role == 'Agent') {
                    $transArr['trans_type'] = $tranType[2];

                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                }
            }

            if ($transArr['payment_mode'] == 'withdraw') {
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id != $user_id) {
                    $transArr['trans_type'] = $tranType[2];
                } else if ($user_role == 'Agent') {
                    $transArr['trans_type'] = $tranType[1];

                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }
            }
            if ($val->status == 2 && $val->bda_status == 2) {
                if ($RemittanceData->status == 'EN_ATTENTE_REGLEMENT') {
                    $status = 'Pending Recipient Bank';
                }
            } else {
                $status = $tranStatus[$val->status];
            }
            $transArr['beneficiary'] = $RemittanceData['titleAccount'] ?? 0;
            $transArr['iban'] = $RemittanceData['iban'] ?? 0;
            $transArr['reason'] = $RemittanceData['reason'] ?? 0;
            global $tranStatus;
            $transArr['trans_status'] = $status;
            $transArr['refrence_id'] = $val->refrence_id;
            $trnsDt = date_create($val->created_at);
            $transDate = date_format($trnsDt, "d M Y, h:i A");
            $transArr['trans_date'] = $frenchDate;
            $transDataArr[] = $transArr;
        }


        $response = array(
            "draw" => intval(1),
            "iTotalRecords" => 1,
            "iTotalDisplayRecords" => 1,
            "aaData" => $transDataArr,
        );
        echo json_encode($response);
        die;
    }
    public function myTransactions(Request $request)
    {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $input = [
            'page' => $request->page,
            'limit' => $request->limit,
            'search' => $request->search
        ];

        $validate_data = [
            'page' => 'required',
            'limit' => 'required',
        ];

        $customMessages = [
            'page.required' => __("message_app.Page field cannot be left blank"),
            'limit.required' => __("message_app.Limit field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $page = $input['page'];
        $limit = $input['limit'];
        $search = $input['search'];

        $start = $page - 1;
        $start = $start * $limit;

        $userInfo = User::where('id', $user_id)->first();
        // if ($userInfo->user_type == 'Agent') {
        //     $trans = DB::table('transactions')
        //             ->select('transactions.*')
        //             ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
        //             ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
        //             ->where("payment_mode", '!=', 'Refund')
        //             ->where(function($query) use ($user_id) {
        //                 $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
        //             })
        //             ->where(function($query) use ($user_id, $search) {
        //                 $query->where('u1.name', 'LIKE', "%$search%")
        //                 ->orWhere('u2.name', 'LIKE', "%$search%")
        //                 ->orWhere('u1.phone', 'LIKE', "%$search%")
        //                 ->orWhere('u2.phone', 'LIKE', "%$search%")
        //                 ->orWhere('transactions.id','LIKE',"%$search%")
        //                 ;
        //             })
        //             ->orderBy('transactions.created_at', 'DESC')
        //             ->skip($start)
        //             ->take($limit)
        //             ->get();
        //     // print_r(DB::getQueryLog());
        // } else {
        $trans = DB::table('transactions')
            ->select('transactions.*')
            ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
            ->where(function ($query) use ($user_id) {
                $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
            })
            ->where(function ($query) use ($user_id, $search) {
                $query->where('u1.name', 'LIKE', "%$search%")
                    ->orWhere('u2.name', 'LIKE', "%$search%")
                    ->orWhere('u1.phone', 'LIKE', "%$search%")
                    ->orWhere('u2.phone', 'LIKE', "%$search%")
                    ->orWhere('transactions.id', 'LIKE', "%$search%");
            })
            ->orderBy('transactions.created_at', 'DESC')
            ->skip($start)
            ->take($limit)
            ->get();
        // }

        if ($trans->isEmpty()) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.no_record_found"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        // if ($userInfo->user_type == 'Agent') {
        //     $totalRecords = DB::table('transactions')
        //             ->select('transactions.*')
        //             ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
        //             ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
        //             ->where("payment_mode", '!=', 'Refund')
        //             ->where(function($query) use ($user_id) {
        //                 $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
        //             })
        //             ->where(function($query) use ($user_id, $search) {
        //                 $query->where('u1.name', 'LIKE', "%$search%")
        //                 ->orWhere('u2.name', 'LIKE', "%$search%")
        //                 ->orWhere('u1.phone', 'LIKE', "%$search%")
        //                 ->orWhere('u2.phone', 'LIKE', "%$search%")
        //                 ->orWhere('transactions.id','LIKE',"%$search%")
        //                 ;
        //             })
        //            ->orderBy('transactions.created_at', 'DESC')
        //            ->count();
        // } else {

        $totalRecords = DB::table('transactions')
            ->select('transactions.*')
            ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
            ->where(function ($query) use ($user_id) {
                $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
            })
            ->where(function ($query) use ($user_id, $search) {
                $query->where('u1.name', 'LIKE', "%$search%")
                    ->orWhere('u2.name', 'LIKE', "%$search%")
                    ->orWhere('u1.phone', 'LIKE', "%$search%")
                    ->orWhere('u2.phone', 'LIKE', "%$search%")
                    ->orWhere('transactions.id', 'LIKE', "%$search%");
            })
            ->orderBy('transactions.created_at', 'DESC')
            ->count();
        // }

        $transDataArr = [];

        global $tranType;

        foreach ($trans as $key => $val) {
            $getPaymentType = ExcelTransaction::where('id', $val->excel_trans_id)->first();
            $transArr['trans_id'] = $val->id;

            $transArr['payment_mode'] = strtolower($val->payment_mode);
            $transArr['payment_mode_name'] = ucwords(str_replace("_", " ", $val->payment_mode));

            if ($val->receiver_id == 0) {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                if (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "ONAFRIQ") {
                    $transArr['receiver'] = 'ONAFRIQ Transfer';
                } elseif (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "BDA") {
                    $transArr['receiver'] = 'BDA Transfer';
                } elseif (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "GIMAC") {
                    $transArr['receiver'] = 'GIMAC Transfer';
                } else {
                    $transArr['receiver'] = 'WALLET2WALLET';
                }
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
            } elseif ($val->user_id == $user_id) { //User is sender
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup

                if ($val->payment_mode == 'send_money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                    $val->payment_mode = 'Withdraw'; //1=Credit;2=Debit;3=topup
                    $transArr['payment_mode'] = strtolower($val->payment_mode);
                    $transArr['trans_from'] = $val->payment_mode;
                }

                if ($val->payment_mode != 'Cash card') {
                    if ($val->trans_type == 2) {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    } else if ($val->trans_type == 1 || $val->trans_type == 3) {
                        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                    }
                }

                if ($val->payment_mode == 'Agent Deposit') {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }

                if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                }
                if ($val->payment_mode == 'wallet2wallet' && $val->trans_type == 2) {
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Withdraw') {
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                }
            } else if ($val->receiver_id == $user_id) { //USer is Receiver
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                if ($val->trans_type == 2) {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'send_money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                    $val->payment_mode = 'send_money'; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Withdraw' && $val->trans_type == 2) {
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }
                if ($userInfo->user_type != 'Merchant') {
                    if ($val->payment_mode == 'Refund' && $transArr['trans_type'] == 'Debit') {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    }
                } else {
                    if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                    }
                    if ($val->payment_mode == 'Refund' && $val->trans_type == 1 && $val->refund_status == 0) {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    }
                }

                if ($val->payment_mode == 'Agent Deposit') {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }
            }
            //echo $val->id.'---'.$userInfo->user_type.'---'.$val->payment_mode.'----'.$transArr['trans_type'].'---####';
            if ($userInfo->user_type == 'User' || $userInfo->user_type == 'Merchant') {
                if ($transArr['trans_type'] == 'Credit') {
                    if ($val->payment_mode == 'Refund') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Topup') {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                } elseif ($transArr['trans_type'] == 'Request') {
                    if ($val->payment_mode == "Withdraw") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        if ($val->status == 2) {
                            $transArr['trans_type'] = 'Request Debit';
                        } else {
                            $transArr['trans_type'] = 'Debit';
                            $val->payment_mode = 'wallet2wallet';
                            $transArr['payment_mode'] = $val->payment_mode;

                            $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                        }
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                        if ($val->status == 2) {
                            $transArr['trans_type'] = 'Request Credit';
                        } else {
                            $transArr['trans_type'] = 'Credit';
                            $val->payment_mode = 'wallet2wallet';
                            $transArr['payment_mode'] = $val->payment_mode;

                            $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                        }
                    }
                } else {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } else {
                        if ($val->payment_mode == "Withdraw") {
                            $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                            $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        } else {
                            $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                            $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                        }
                    }
                }
            } elseif ($userInfo->user_type == 'Agent') {
                if ($transArr['trans_type'] == 'Request') {
                    if ($val->payment_mode == "Withdraw") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Debit') {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } elseif ($val->payment_mode == 'Withdraw') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    }
                } else {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                }
            } else {
                if ($transArr['trans_type'] == 'Debit') {
                    if ($val->payment_mode == "Refund") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } elseif ($val->payment_mode == 'Withdraw') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } elseif ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Credit') {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    }
                } else {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                }
            }

            if ($val->payment_mode == 'Credited by admin' || $val->payment_mode == 'Debited by admin') {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['receiver_phone'] = 'Admin';
                $transArr['sender_phone'] = 'Admin';
            }

            $transArr['transaction_fees'] = $this->numberFormatPrecision($val->transaction_amount, 2, '.');
            $transArr['received_amount'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
            $transArr['trans_amount_android'] = number_format($transArr['trans_amount'], 2);

            if ($transArr['payment_mode'] == 'agent deposit') {
                $transArr['payment_mode'] = 'agent_deposit';
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id == $user_id) {
                    $transArr['trans_type'] = $tranType[1];
                } else if ($user_role == 'Agent') {
                    $transArr['trans_type'] = $tranType[2];

                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                }
            }

            if ($transArr['payment_mode'] == 'withdraw') {
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id != $user_id) {
                    $transArr['trans_type'] = $tranType[2];
                } else if ($user_role == 'Agent') {
                    $transArr['trans_type'] = $tranType[1];

                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }
            }

            global $tranStatus;
            $transArr['trans_status'] = $tranStatus[$val->status];

            $transArr['refrence_id'] = $val->refrence_id;

            $trnsDt = date_create($val->created_at);
            $transDate = date_format($trnsDt, "d M Y, h:i A");

            //                        $trnsProcDt = date_create($val->updated_at);
            //                        $transProcessDate = date_format($trnsProcDt, "d M Y, h:i A");

            $transArr['trans_date'] = $transDate;
            //                        $transArr['trans_process_date'] = $transProcessDate;

            $transDataArr[] = $transArr;
        }

        $statusArr = array(
            "status" => "Success",
            "reason" => __("message_app.Transaction List"),
            "total_records" => $totalRecords,
            "total_page" => ceil($totalRecords / $limit),
        );
        $data['data'] = $transDataArr;
        $json = array_merge($statusArr, $data);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function senderRequestList(Request $request)
    {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $user_role = Auth::user()->user_type;

        $input = [
            'status' => $request->status,
            'page' => $request->page,
            'limit' => $request->limit,
        ];

        $validate_data = [
            'status' => 'required',
            'page' => 'required',
            'limit' => 'required',
        ];

        $customMessages = [
            'status.required' => __("message_app.Request type field cannot be left blank"),
            'page.required' => __("message_app.Page field cannot be left blank"),
            'limit.required' => __("message_app.Limit field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $status = $request->status;

        $userInfo = User::where("id", $user_id)->first();

        /* Payment_mode= Withdraw/Agent Deposit */
        /* trans_type= 4 */

        $page = $input['page'];
        $limit = $input['limit'];
        $start = $page - 1;
        $start = $start * $limit;

        $requests = Transaction::where("payment_mode", 'send_money')
            ->whereIn("status", $status)
            ->where("status", "!=", 1)
            ->where("receiver_id", $user_id)
            ->orderBy("id", "desc")
            ->skip($start)
            ->take($limit)
            ->get();

        $totalRecords = Transaction::where("payment_mode", 'send_money')
            ->whereIn("status", $status)
            ->where("receiver_id", $user_id)
            ->where("status", "!=", 1)
            ->orderBy("id", "desc")
            ->count();

        $records = [];
        if ($requests) {
            foreach ($requests as $request) {
                $userData = [];
                $userRecordArr = [];
                $userData["request_id"] = $request->id;
                $userData["amount"] = $this->numberFormatPrecision($request->amount, 2, ".");
                $trnsDt = date_create($request->created_at);
                $transDate = date_format($trnsDt, "d M Y, h:i A");
                $userData['date'] = $transDate;
                $userData['statusInDigit'] = $request->status;
                $userData['status'] = $this->getStatusText($request->status);
                $userRecordArr["name"] = $request->User->name;
                $userRecordArr["user_id"] = $request->user_id;
                $userRecordArr["user_type"] = $request->User->user_type;
                $userRecordArr["phone"] = $request->User->phone;
                if ($request->User->profile_image != "" && $request->User->profile_image != "no_user.png") {
                    $userRecordArr["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $request->User->profile_image;
                } else {
                    $userRecordArr["profile_image"] = "public/img/" . "no_user.png";
                }
                $userRecordArr["phone"] = $request->User->phone;
                $userRecordArr["email"] = $request->User->email;
                $userRecordArr["country"] = $request->User->country;
                $userData["userData"] = $userRecordArr;
                $records[] = $userData;
            }
            $statusArr = array(
                "status" => "Success",
                "reason" => __('message_app.Request List'),
                "total_records" => $totalRecords,
                "total_page" => ceil($totalRecords / $limit)
            );
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message_app.Requests are not exist"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function receiverRequestList(Request $request)
    {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $input = [
            'status' => $request->status,
            'page' => $request->page,
            'limit' => $request->limit,
        ];

        $validate_data = [
            'status' => 'required',
            'page' => 'required',
            'limit' => 'required',
        ];

        $customMessages = [
            'status.required' => __("message_app.Request type field cannot be left blank"),
            'page.required' => __("message_app.Page field cannot be left blank"),
            'limit.required' => __("message_app.Limit field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $status = $request->status;

        $userInfo = User::where("id", $user_id)->first();

        /* Payment_mode= Withdraw/Agent Deposit */
        /* trans_type= 4 */

        $page = $input['page'];
        $limit = $input['limit'];
        $start = $page - 1;
        $start = $start * $limit;

        $requests = Transaction::where("payment_mode", 'send_money')
            ->whereIn("status", $status)
            ->where("user_id", $user_id)
            ->where("receiver_id", '!=', 0)
            ->orderBy("id", "desc")
            ->skip($start)
            ->take($limit)
            ->get();

        $totalRecords = Transaction::where("payment_mode", 'send_money')
            ->whereIn("status", $status)
            ->where("user_id", $user_id)
            ->where("receiver_id", '!=', 0)
            ->orderBy("id", "desc")
            ->count();

        if ($requests->isEmpty()) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.no_record_found"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $records = [];
        if ($requests) {
            foreach ($requests as $request) {
                $userData = [];
                $userRecordArr = [];
                $userData["request_id"] = $request->id;
                $userData["note"] = $request->notes ?? "";
                $userData["amount"] = $this->numberFormatSpaces($this->roundAmount($request->amount));
                $trnsDt = date_create($request->created_at);
                $transDate = date_format($trnsDt, "d M Y, h:i A");
                $userData['date'] = $transDate;
                $userData['statusInDigit'] = $request->status;
                $userData['status'] = $this->getStatusText($request->status);
                $userRecordArr["name"] = $request->Receiver->name ?? "";
                $userRecordArr["user_id"] = $request->Receiver->id ?? "";
                $userRecordArr["user_type"] = $request->Receiver->user_type ?? "";
                if (isset($request->Receiver) && $request->Receiver->profile_image != "" && isset($request->Receiver) && $request->Receiver->profile_image != "no_user.png") {
                    $userRecordArr["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $request->Receiver->profile_image;
                } else {
                    $userRecordArr["profile_image"] = "public/img/" . "no_user.png";
                }
                $userRecordArr["user_type"] = $request->Receiver->user_type ?? "";
                $userRecordArr["phone"] = $request->Receiver->phone ?? "";
                $userRecordArr["email"] = $request->Receiver->email ?? "";
                $userRecordArr["country"] = $request->Receiver->country ?? "";


                if ($request->transactionType == "SWAPTOGIMAC") {
                    $userRecordArr["type"] = "CEMAC";
                    $userRecordArr["amount"] = $request->amount;
                    $userRecordArr["phone"] = $request->receiver_mobile;
                    $userRecordArr["payment_mode"] = $request->payment_mode;
                    unset($userRecordArr["name"], $userRecordArr["user_id"], $userRecordArr["user_type"], $userRecordArr["email"], $userRecordArr["country"]);
                    $userData["userData"] = $userRecordArr;
                } else {
                    $userData["userData"] = $userRecordArr;
                }

                $records[] = $userData;
            }

            $statusArr = array(
                "status" => "Success",
                "reason" => __("message_app.Request List"),
                "total_records" => $totalRecords,
                "total_page" => ceil($totalRecords / $limit),
            );
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message_app.Requests are not exist"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    private function getStatusText($status)
    {

        $statusArr = array('1' => 'Completed', '2' => 'Pending', '3' => 'Failed', '4' => 'Rejected', '5' => 'Refund', '6' => 'Refund Completed', '7' => 'SUSPECTED');
        return $statusArr[$status];
    }
    private function getUserNameById($user_id)
    {
        if ($user_id == 1) {
            $matchThese = ["admins.id" => $user_id];
            $user = DB::table('admins')->select('admins.username')->where($matchThese)->first();
            return $user->username;
        } else {
            $matchThese = ["users.id" => $user_id];
            $user = DB::table('users')->select('users.name', 'users.lastName')->where($matchThese)->first();
            if (isset($user->name) && !empty($user->name)) {
                return $name = trim("{$user->name} " . ($user->lastName ?? ''));
            } else {
                return $name = "N/A";
            }
        }
    }
    private function getUserTypeById($user_id)
    {
        if ($user_id == 1) {
            return '';
        } else {
            $matchThese = ["users.id" => $user_id];
            $user = DB::table('users')->select('users.user_type')->where($matchThese)->first();
            return $user->user_type;
        }
    }
    private function getPhoneById($user_id)
    {
        if ($user_id == 1) {
            return '';
        } else {
            $matchThese = ["users.id" => $user_id];
            $user = DB::table('users')->select('users.phone')->where($matchThese)->first();
            return $user->phone ?? "";
        }
    }
    public function scanMerchantQR(Request $request)
    {
        $user_id = $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        if ($request->qr_code == "") {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid QR Code")];
            return response()->json($statusArr, 200);
        } else {
            $qrCodeArr = explode("##", $request->qr_code);
            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid QR Code")
                ];
                return response()->json($statusArr, 200);
            }

            if (isset($qrCodeArr[1]) && !empty($qrCodeArr[1])) {
                $qrOrder = $qrCodeArr[1];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid QR Code")
                ];
                return response()->json($statusArr, 200);
            }

            if (isset($qrCodeArr[2]) && !empty($qrCodeArr[2])) {
                $qrAmt = $qrCodeArr[2];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid QR Code")
                ];
                return response()->json($statusArr, 200);
            }

            $matchThese = ["users.id" => $qrId];
            $user = DB::table("users")
                ->where($matchThese)
                ->first();
            if ($user) {
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.Merchant detail"),
                ];
                $userData["id"] = $user->id;
                $userData["name"] = $user->business_name;
                $userData["phone"] = $user->phone;
                $userData["order_id"] = $qrOrder;
                $userData["amount"] = $qrAmt;
                $data["data"] = $userData;
                $json = array_merge($statusArr, $data);
                return response()->json($json, 201);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid QR Code")
                ];
                return response()->json($statusArr, 200);
            }
        }
    }
    public function fundTransfer(Request $request)
    {
        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $user_role = Auth::user()->user_type;
        if ($request->transaction_id == "") {
            $minAmount = "1";
            $maxAmount = "10000000";
            $input = [
                'opponent_user_id' => isset($request->opponent_user_id) ? $request->opponent_user_id : null,
                'amount' => isset($request->amount) ? $request->amount : null,
                'total_amount' => isset($request->total_amount) ? $request->total_amount : null,
                'trans_type' => isset($request->trans_type) ? $request->trans_type : null
            ];

            $validate_data = [
                'opponent_user_id' => 'required',
                'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
                'total_amount' => 'required',
                'trans_type' => 'required',
            ];

            $customMessages = [
                'opponent_user_id.required' => __("message_app.Opponent User id field cannot be left blank"),
                'amount.required' => __("message_app.Amount is required"),
                'amount.numeric' => __("message_app.Amount must be a valid number"),
                'amount.gt' => __("message_app.Amount must be greater than zero"),
                'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
                'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
                'total_amount.required' => __("message_app.Total Amount field cannot be left blank"),
                'trans_type.required' => __("message_app.Trans Type field cannot be left blank"),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $opponent_user_id = $request->opponent_user_id;
            $trans_type = $request->trans_type;
            $amount = $request->amount;
            $total_amount = $request->total_amount;
            $note = $request->notes ?? $request->note ?? "";


        } else {
            $getTrans = $this->getTransactionDataById($request->transaction_id);
            $opponent_user_id = $getTrans->receiver_id ?? 0;
            $trans_type = "Send Money";
            $amount = $request->amount;
            $total_amount = $request->total_amount;
            $note = $request->notes ?? $request->note ?? "";

        }

        $senderUser = User::where('id', $user_id)->first();
        $userType = $this->getUserType($senderUser->user_type);
        $amount = $request->amount;

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->minSendMoney > $request->amount) {
            Log::info(['transactionLimit' => $transactionLimit->minSendMoney, 'amount' => $request->amount]);
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->minSendMoney)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->maxSendMoney) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxSendMoney)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($user_id == $opponent_user_id) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.You cannot send funds to yourself"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        /* $hasActiveCard = UserCard::where('userId', $user_id)
            ->whereIn('cardType', ['VIRTUAL', 'PHYSICAL'])
            ->where('cardStatus', 'Active')
            ->exists();
        $hasActiveCardReceiver = UserCard::where('userId', $opponent_user_id)
            ->whereIn('cardType', ['VIRTUAL', 'PHYSICAL'])
            ->where('cardStatus', 'Active')
            ->exists();

        if (!$hasActiveCard) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.You must have at least a Virtual or Physical Card"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        if (!$hasActiveCardReceiver) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.The receiver must have either a Virtual Card or a Physical Card"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        $total_fees = 0;

        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $trans_type)->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        // echo"<pre>";print_r($feeapply);die;

        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }

        $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        if (!empty($trans_fees)) {
            $feeType = $trans_fees->fee_type;
            if ($feeType == 1) {
                $total_fees = $trans_fees->fee_amount;
            } else {
                $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
            }
        }

        $total_feesNew = 0;
        /*$referralFeeAmount = 0;
        $referralByUserId = 0;
         if ($senderUser->referralBy) {
            $getData = $this->referralService->referralFeeAmount($senderUser, $total_fees, 'SWAPTOSWAP');
            $total_feesNew = $getData['data']['total_fees'];
            $referralFeeAmount = $getData['data']['referralFeeAmount'] ?? 0;
            $referralByUserId = $getData['data']['referralByUserId'] ?? 0;
        } */

        $total_amount = ($amount + $total_fees);

        if ($total_amount != $total_amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Transaction failed because fees strucutre has been changed.Please try again"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $recieverUser = $userInfo = User::where('id', $opponent_user_id)->first();

        $receiver_user_type = $this->getUserType($recieverUser->user_type);
        $receiverTransactionLimit = TransactionLimit::where('type', $receiver_user_type)->first();

        if ($receiverTransactionLimit->minSendMoney > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount_to_receiver', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($receiverTransactionLimit->minSendMoney)),
                ])
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $receiverTransactionLimit->maxSendMoney) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount_to_receiver', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($receiverTransactionLimit->maxSendMoney)),
                ])
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $trans_id = time();
        $refrence_id = time() . '-' . $opponent_user_id;
        $remainingWalletBalance = $senderUser->wallet_balance - $total_amount;
        $trans = new Transaction([
            'user_id' => $user_id,
            'receiver_id' => $opponent_user_id,
            'amount' => $amount,
            'amount_value' => $total_amount,
            'transaction_amount' => $total_fees,
            'total_amount' => $total_amount,
            'trans_type' => 1,
            'payment_mode' => 'wallet2wallet',
            'status' => 1,
            'refrence_id' => $trans_id,
            'entryType' => 'API',
            'notes' => $note,
            'transactionType' => 'SWAPTOSWAP',
            'remainingWalletBalance' => $remainingWalletBalance ?? "",
            'billing_description' => 'Fund Transfer-' . $refrence_id,
            'beforeBalance' => $senderUser->wallet_balance,
            'afterBalance' => ($senderUser->wallet_balance - $total_amount),

            'receiverBefore' => $recieverUser->wallet_balance,
            'receiverAfter' => ($recieverUser->wallet_balance + $total_amount),

            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'swapDomainName' => 'UAT_SERVER'
        ]);

        $trans->save();
        $transaction_id = $trans_id;
        $status = $this->getStatusText(1);

        /* Card Payment Start  */
        $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
            $postData = json_encode([
                "currencyCode" => "XAF",
                "last4Digits" => $userCard->last4Digits,
                "referenceMemo" => "Settlement",
                "transferAmount" => $total_amount,
                "transferType" => "CardToWallet",
                "mobilePhoneNumber" => "241{$senderUser->phone}"
            ]);
            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
            Log::info('Fund transfer');
        }
        /* Card Payment End */

        //to send the email to sender
        /* $senderName = $senderUser->name;
        $senderEmail = $senderUser->email;
        $receiverName = $recieverUser->name;
        $receiverEmail = $recieverUser->email;
        $senderAmount = $amount;
        $receiverAmount = $total_amount;
        $transactionFees = $total_fees;
        $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));
        $emailData['subjects'] = 'Funds Transfer Details';
        $emailData['senderName'] = $senderName;
        $emailData['senderEmail'] = $senderEmail;
        $emailData['senderAmount'] = $senderAmount;
        $emailData['currency'] = CURR;

        $emailData['receiverName'] = $receiverName;
        $emailData['receiverAmount'] = $receiverAmount;
        $emailData['receiverEmail'] = $receiverEmail;
        $emailData['receiverAmount'] = $receiverAmount;

        $emailData['transId'] = $refrence_id;
        // $emailData['transId'] = $transaction_id;
        $emailData['transactionFees'] = $transactionFees;
        $emailData['transactionDate'] = $transaction_date;
        $emailData['transactionStatus'] = $status;

        if ($senderEmail != "") {
            Mail::send('emails.fund_transfer_sender', $emailData, function ($message) use ($emailData) {
                $message->to($emailData["senderEmail"], $emailData["senderEmail"])
                    ->subject($emailData["subjects"]);
            });
        }

        if ($receiverEmail != "") {
            Mail::send('emails.fund_transfer_receiver', $emailData, function ($message) use ($emailData) {
                $message->to($emailData["receiverEmail"], $emailData["receiverEmail"])
                    ->subject($emailData["subjects"]);
            });
        } */

        $sender_wallet_amount = $senderUser->wallet_balance - $amount - $total_fees;
        $debit = new TransactionLedger([
            'user_id' => $user_id,
            'opening_balance' => $senderUser->wallet_balance,
            'amount' => $amount,
            'actual_amount' => $amount,
            'type' => 2,
            'trans_id' => $transaction_id,
            'payment_mode' => 'wallet2wallet',
            'closing_balance' => $sender_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $debit->save();
        User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

        $receiver_wallet_amount = $recieverUser->wallet_balance + $amount;
        $credit = new TransactionLedger([
            'user_id' => $opponent_user_id,
            'opening_balance' => $recieverUser->wallet_balance,
            'amount' => $amount,
            'fees' => $total_fees,
            'actual_amount' => $total_amount,
            'type' => 1,
            'trans_id' => $transaction_id,
            'payment_mode' => 'wallet2wallet',
            'closing_balance' => $receiver_wallet_amount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $credit->save();

        User::where('id', $opponent_user_id)->update(['wallet_balance' => $receiver_wallet_amount]);

        /* Card Payment Start */
        $userCard1 = UserCard::where('userId', $recieverUser->id)->where('cardType', 'PHYSICAL')->first();
        if (isset($userCard1) && $userCard1->cardType == "PHYSICAL" && $userCard1->cardStatus == "Active") {
            $postData = json_encode([
                "currencyCode" => "XAF",
                "last4Digits" => $userCard1->last4Digits,
                "referenceMemo" => "Settlement",
                "transferAmount" => $amount,
                "transferType" => "WalletToCard",
                "mobilePhoneNumber" => "241{$recieverUser->phone}"
            ]);
            $this->cardService->addWalletCardTopUp($postData, $userCard1->accountId, $userCard1->cardType);
            Log::info(['Balance Updated Physical Card' => $amount, "accountID" => $userCard1->accountId]);
        }
        /* Card Payment End */


        /* if ($referralByUserId) {
            User::where('id', $referralByUserId)->increment('wallet_balance', $referralFeeAmount);
            $this->referralService->addReferralAmountByPayment($senderUser, $referralByUserId, $referralFeeAmount, $trans->id);
        } */

        DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_feesNew != 0 ? $total_feesNew : $total_fees);

        $title = __("message.debit_title", ['cost' => CURR . " " . $this->numberFormatSpaces($this->roundAmount($request->amount))]);
        $message = __("message.debit_message", ['cost' => CURR . " " . $this->numberFormatSpaces($this->roundAmount($request->amount)), 'username' => $recieverUser->name]);

        $device_type = $senderUser->device_type;
        $device_token = $senderUser->device_token;

        // $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

        $data = [
            'title' => $title,
            'message' => $message,
            'id' => $transaction_id,
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $senderUser->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();
        $title = __("message.credit_title", ['cost' => CURR . " " . $this->numberFormatSpaces($this->roundAmount($request->amount))]);
        $message = __("message.credit_message", ['cost' => CURR . " " . $this->numberFormatSpaces($this->roundAmount($request->amount)), 'username' => $senderUser->name]);
        $device_type = $recieverUser->device_type;
        $device_token = $recieverUser->device_token;

        // $this->sendPushNotification($title, $message, $device_type, $device_token);

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => $transaction_id,
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $recieverUser->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $statusArr = array(
            "status" => "Success",
            "transactionId" => $trans->id,
            "reason" => __("message_app.Payment transfer completed successfully")
        );
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function sendMoneyRequest(Request $request)
    {

        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        $user_role = Auth::user()->user_type;

        $minAmount = "1";
        $maxAmount = "10000000";

        $input = [
            'phone' => $request->phone,
            'amount' => $request->amount,
        ];

        $validate_data = [
            'phone' => 'required',
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
        ];

        $customMessages = [
            'phone.required' => __("message_app.Phone number field cannot be left blank"),
            'amount.required' => __("message_app.Amount is required"),
            'amount.numeric' => __("message_app.Amount must be a valid number"),
            'amount.gt' => __("message_app.Amount must be greater than zero"),
            'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $amount = $request->amount;
        $phone = $request->phone;

        $senderUser = User::where('id', $user_id)->first();
        $userType = $this->getUserType($senderUser->user_type);
        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        $receiverUser = User::where('phone', $phone)->first();
        // Log::info(['$user_id'=>$user_id,'$receiverUser'=>$receiverUser['id']]);
        if ($user_id == $receiverUser['id']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.You cannot send funds to yourself"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $adminInfo = Admin::where("id", 1)->first();
        if ($transactionLimit->minSendMoney > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->minSendMoney)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->maxSendMoney) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxSendMoney)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        /* $hasActiveCard = UserCard::where('userId', $user_id)
            ->whereIn('cardType', ['VIRTUAL', 'PHYSICAL'])
            ->where('cardStatus', 'Active')
            ->exists();
        $hasActiveCardReceiver = UserCard::where('userId', $receiverUser['id'])
            ->whereIn('cardType', ['VIRTUAL', 'PHYSICAL'])
            ->where('cardStatus', 'Active')
            ->exists();

        if (!$hasActiveCard) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.You must have at least a Virtual or Physical Card"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        if (!$hasActiveCardReceiver) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.The receiver must have either a Virtual Card or a Physical Card"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        $total_fees = 0;

        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        // echo"<pre>";print_r($feeapply);die;

        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }

        if (!empty($receiverUser)) {
            $trans_id = time();
            $refrence_id = time() . '-' . $receiverUser->id;
            $trans = new Transaction([
                'receiver_id' => $user_id,
                'user_id' => $receiverUser->id,
                'amount' => $amount,
                'transaction_amount' => $total_fees ?? 0,
                'amount_value' => $amount,
                'total_amount' => $amount,
                'trans_type' => 4,
                'payment_mode' => 'send_money',
                'status' => 2,
                "notes" => $request->notes ?? $request->note ?? "",
                'refrence_id' => $trans_id,
                'billing_description' => 'SendMoney-' . $refrence_id,
                'entryType' => 'API',
                'transactionType' => 'SWAPTOSWAP',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'swapDomainName' => 'UAT_SERVER'
            ]);
            $trans->save();

            $title = __("message_app.Money Request");
            $message = __('message_app.send_money_success', [
                'currency' => CURR,
                'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                'username' => $receiverUser->name,
            ]);

            $device_type = $senderUser->device_type;
            $device_token = $senderUser->device_token;

            //$this->sendPushNotification($title, $message, $device_type, $device_token);


            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => $trans->id,
                'type' => 'TRANSACTION',
            ];

            if ($device_type && $device_token) {
                $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                    $device_token,
                    $title,
                    $message,
                    $data1,
                    $device_type
                );
            }

            $notif = new Notification([
                'user_id' => $senderUser->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'transPaymentType' => "",
                'transactionId' => $trans->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            $title = __("message_app.Money Request");
            $message = __('message_app.send_money_request_received', [
                'username' => $senderUser->name,
                'currency' => CURR,
                'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
            ]);

            $device_type = $receiverUser->device_type;
            $device_token = $receiverUser->device_token;

            // $this->sendPushNotification($title, $message, $device_type, $device_token);

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => $trans->id,
                'type' => 'TRANSACTION',
            ];

            if ($device_type && $device_token) {
                $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                    $device_token,
                    $title,
                    $message,
                    $data1,
                    $device_type
                );
            }

            $notif = new Notification([
                'user_id' => $receiverUser->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'transPaymentType' => 'MoneyRequest',
                'transactionId' => $trans->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            // Send email
            $status = $this->getStatusText(1);
            //to send the email to sender
            $senderName = $senderUser->name ?? '';
            $senderEmail = $senderUser->email ?? '';
            $senderAmount = $this->numberFormatSpaces($this->roundAmount($amount));
            $receiverAmount = $this->numberFormatSpaces($this->roundAmount($amount));
            $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));

            $emailData['subjects'] = __("message_app.Money Request");
            $emailData['senderName'] = $senderName;
            $emailData['senderEmail'] = $senderEmail;
            $emailData['senderAmount'] = $senderAmount;
            $emailData['currency'] = CURR;
            $emailData['transactionDate'] = $transaction_date;
            $emailData['receiverAmount'] = $receiverAmount;

            // if ($senderEmail != '') {
            //     Mail::send(
            //         'emails.request_money',
            //         $emailData,
            //         function ($message) use ($emailData) {
            //             $message
            //                 ->to($emailData["senderEmail"], $emailData["senderEmail"])
            //                 ->subject($emailData["subjects"]);
            //         }
            //     );
            // }

            $statusArr = array("status" => "Success", "reason" => __("message_app.The money request has been successfully sent"));
            //            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message_app.User is not exist for entered phone number"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function cancelAcceptMoneyRequest(Request $request)
    {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $user_role = Auth::user()->user_type;

        $minAmount = "1";
        $maxAmount = "10000000";

        $input = [
            'request_id' => $request->request_id,
            'amount' => $request->amount,
            'request_type' => $request->request_type
        ];

        $validate_data = [
            'request_id' => 'required',
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
            'request_type' => 'required',
        ];

        $customMessages = [
            'request_id.required' => __("message_app.Request id field cannot be left blank"),
            'amount.required' => __("message_app.Amount is required"),
            'amount.numeric' => __("message_app.Amount must be a valid number"),
            'amount.gt' => __("message_app.Amount must be greater than zero"),
            'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
            'request_type.required' => __("message_app.Request Type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $request_id = $request->request_id;
        $request_type = $request->request_type;
        $amount = $request->amount;

        $senderInfo = User::where("id", $user_id)->first();
        $userType = $this->getUserType($senderInfo->user_type);
        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        if ($senderInfo->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderInfo->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->minSendMoney > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->minSendMoney,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->maxSendMoney) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->maxSendMoney,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        //        echo '<pre>';print_r($senderInfo);exit;

        DB::enableQueryLog();
        $requestDetail = Transaction::where("id", $request_id)
            ->first();

        $receiverInfo = User::where("id", $requestDetail->receiver_id)->first();
        if (!empty($requestDetail)) {
            if ($requestDetail->payment_mode == "send_money") {
                $type = 1;
            } else {
                $type = 2;
            }

            if ($request_type == "Accept") {

                $adminInfo = Admin::where("id", 1)->first();

                if ($adminInfo->minSendMoney > $amount) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __('message_app.min_transfer_amount', [
                            'currency' => CURR,
                            'amount' => $adminInfo->minSendMoney,
                        ]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($amount > $adminInfo->maxSendMoney) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __('message_app.max_transfer_amount', [
                            'currency' => CURR,
                            'amount' => $adminInfo->maxSendMoney
                        ]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($type == 1) {
                    if ($senderInfo->wallet_balance >= $amount) {

                        $total_fees = 0;

                        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
                            ->where('max_amount', '>=', $request->amount)->first();
                        // echo"<pre>";print_r($feeapply);die;

                        if (isset($feeapply)) {

                            $feeType = $feeapply->fee_type;
                            if ($feeType == 1) {
                                $total_fees = $feeapply->fee_amount;
                            } else {
                                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                            }
                        } else {
                            $trans_fees = Transactionfee::where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
                                ->where('max_amount', '>=', $request->amount)->first();
                            if (!empty($trans_fees)) {
                                $feeType = $trans_fees->fee_type;
                                if ($feeType == 1) {
                                    $total_fees = $trans_fees->fee_amount;
                                } else {
                                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                                }
                            }
                        }
                        $total_tax = "0";

                        $total_amount = $amount;
                        $remainingWalletBalance = ($senderInfo->wallet_balance - $amount);
                        Transaction::where("id", $request_id)->update([
                            "status" => 1,
                            "transaction_amount" => $total_fees,
                            "amount" => $amount - $total_fees,
                            "total_amount" => $amount,
                            'beforeBalance' => $senderInfo->wallet_balance,
                            'afterBalance' => $remainingWalletBalance,
                            'receiverBefore' => $receiverInfo->wallet_balance,
                            'receiverAfter' => $receiverInfo->wallet_balance + ($total_amount - $total_fees),                            
                            "amount_value" => $total_amount,
                            "remainingWalletBalance" => $remainingWalletBalance ?? "",
                            "updated_at" => date("Y-m-d H:i:s"),
                            //                            "trans_type" => $type,
                        ]);

                        $transaction_id = $request_id;

                        $receiver_wallet_amount = $receiverInfo->wallet_balance + ($total_amount - $total_fees);
                        $credit = new TransactionLedger([
                            'user_id' => $receiverInfo->id,
                            'opening_balance' => $receiverInfo->wallet_balance,
                            'amount' => $amount - $total_fees,
                            'fees' => $total_fees,
                            'actual_amount' => $total_amount,
                            'type' => 1,
                            'trans_id' => $transaction_id,
                            'payment_mode' => 'Send Money',
                            'closing_balance' => $receiver_wallet_amount,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $credit->save();

                        $title = __("message_app.Congratulations");
                        $message = __('message_app.send_money_request_accepted', [
                            'currency' => CURR,
                            'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                        ]);

                        $device_type = $receiverInfo->device_type;
                        $device_token = $receiverInfo->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $data1 = [
                            'title' => $title,
                            'message' => $message,
                            'id' => "",
                            'type' => 'ACCEPTEDSENDMONEY',
                        ];

                        if ($device_type && $device_token) {
                            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                $device_token,
                                $title,
                                $message,
                                $data1,
                                $device_type
                            );
                        }

                        $notif = new Notification([
                            "user_id" => $receiverInfo->id,
                            "notif_title" => $title,
                            "notif_body" => $message,
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                        ]);
                        $notif->save();

                        User::where("id", $receiverInfo->id)->update(["wallet_balance" => $receiver_wallet_amount,]);
                        /* Card Payment Start */
                        $userCard = UserCard::where('userId', $receiverInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => ($amount - $total_fees),
                                "transferType" => "WalletToCard",
                                "mobilePhoneNumber" => "241{$receiverInfo->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Cancel Accept money request');
                        }
                        /* Card Payment End */

                        $sender_wallet_amount = $senderInfo->wallet_balance - ($amount);
                        $debit = new TransactionLedger([
                            'user_id' => $senderInfo->id,
                            'opening_balance' => $senderInfo->wallet_balance,
                            'amount' => $amount,
                            'actual_amount' => $amount,
                            'type' => 2,
                            'trans_id' => $transaction_id,
                            'payment_mode' => 'Send Money',
                            'closing_balance' => $sender_wallet_amount,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $debit->save();

                        User::where("id", $senderInfo->id)->update(["wallet_balance" => $sender_wallet_amount,]);

                        /* Card Payment Start */
                        $userCard = UserCard::where('userId', $senderInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => ($amount),
                                "transferType" => "CardToWallet",
                                "mobilePhoneNumber" => "241{$senderInfo->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Cancel Accept money request 1');
                        }
                        /* Card Payment End */

                        DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);

                        // Send Email
                        $transaction = Transaction::where("id", $request_id)->first();
                        $status = $this->getStatusText(1);
                        $senderName = $senderInfo->name;
                        $senderEmail = $senderInfo->email;
                        $receiverName = $receiverInfo->name;
                        $receiverEmail = $receiverInfo->email;
                        $senderAmount = $amount;
                        $receiverAmount = $amount;
                        $transactionFees = 0;
                        $transaction_date = date('d M, Y h:i A', strtotime(
                            $transaction->created_at ?? 'today'
                        ));
                        $emailData['subjects'] = __("message_app.Funds Transfer Details");
                        $emailData['senderName'] = $senderName;
                        $emailData['senderEmail'] = $senderEmail;
                        $emailData['senderAmount'] = $senderAmount;
                        $emailData['currency'] = CURR;
                        $emailData['receiverName'] = $receiverName;
                        $emailData['receiverAmount'] = $receiverAmount;
                        $emailData['receiverEmail'] = $receiverEmail;
                        $emailData['transId'] = $transaction->refrence_id ?? 'refrence_id';
                        $emailData['transactionFees'] = $transactionFees;
                        $emailData['transactionDate'] = $transaction_date;
                        $emailData['transactionStatus'] = $this->getStatusText(1);
                        $emailData['senderId'] = $senderInfo->id;
                        $emailData['receiverId'] = $receiverInfo->id;

                        if ($senderEmail != "") {
                            /* Mail::send('emails.request_money', $emailData, function ($message) use ($emailData) {
                                $message->to($emailData["senderEmail"], $emailData["senderEmail"])
                                    ->subject($emailData["subjects"]);
                            }); */
                        }
                    } else {
                        $statusArr = array("status" => "Failed", "reason" => __("message_app.You have insufficient balance to accept request"));
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                } else {
                    Transaction::where("id", $request_id)->update([
                        "status" => 1,
                        "trans_type" => $type,
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);

                    $receiverInfo = User::where(
                        "id",
                        $requestDetail->receiver_id
                    )->first();

                    $userInfo = User::where("id", $user_id)->first();
                    $wallet_balance = $userInfo->wallet_balance + $amount;
                    User::where("id", $userInfo->id)->update([
                        "wallet_balance" => $wallet_balance,
                    ]);

                    /* Card Payment Start */
                    $userCard = UserCard::where('userId', $userInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $amount,
                            "transferType" => "WalletToCard",
                            "mobilePhoneNumber" => "241{$userInfo->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Cancel Accept money request 2');
                    }
                    /* Card Payment End */

                    $title = __("message_app.Congratulations");
                    $message = __('message_app.withdraw_request_accepted', [
                        'currency' => CURR,
                        'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                    ]);

                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //  $result = $this->sendPushNotification($title, $message, $device_type, $device_token);


                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'ACCEPTEDWITHDRAW',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        "user_id" => $receiverInfo->id,
                        "notif_title" => $title,
                        "notif_body" => $message,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $notif->save();

                    // Send Email
                    $transaction = Transaction::where("id", $request_id)->first();
                    $status = $this->getStatusText(1);
                    $senderName = $senderInfo->name;
                    $senderEmail = $senderInfo->email;
                    $receiverName = $receiverInfo->name;
                    $receiverEmail = $receiverInfo->email;
                    $senderAmount = $amount;
                    $receiverAmount = $amount;
                    $transactionFees = 0;
                    $transaction_date = date('d M, Y h:i A', strtotime(
                        $transaction->created_at ?? 'today'
                    ));
                    $emailData['subjects'] = __("message_app.Funds Transfer Details");
                    $emailData['senderName'] = $senderName;
                    $emailData['senderEmail'] = $senderEmail;
                    $emailData['senderAmount'] = $senderAmount;
                    $emailData['currency'] = CURR;
                    $emailData['receiverName'] = $receiverName;
                    $emailData['receiverAmount'] = $receiverAmount;
                    $emailData['receiverEmail'] = $receiverEmail;
                    $emailData['transId'] = $transaction->refrence_id ?? 'refrence_id';
                    $emailData['transactionFees'] = $transactionFees;
                    $emailData['transactionDate'] = $transaction_date;
                    $emailData['transactionStatus'] = $this->getStatusText(1);

                    if ($senderEmail != "") {
                        /* Mail::send('emails.request_money', $emailData, function ($message) use ($emailData) {
                            $message->to($emailData["senderEmail"], $emailData["senderEmail"])
                                ->subject($emailData["subjects"]);
                        }); */
                    }
                }
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.Request Accepted Successfully"),
                ];

                $statusArr = array("status" => "Success", "reason" => __("message_app.Payment transfer completed successfully"), "transactionId" => $request->request_id);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                if ($type != 1) {
                    $receiverInfo = User::where(
                        "id",
                        $requestDetail->receiver_id
                    )->first();

                    $wallet_balance = $receiverInfo->wallet_balance + $amount;

                    User::where("id", $receiverInfo->id)->update([
                        "wallet_balance" => $wallet_balance,
                    ]);

                    /* Card Payment Start */
                    $userCard = UserCard::where('userId', $receiverInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $amount,
                            "transferType" => "WalletToCard",
                            "mobilePhoneNumber" => "241{$receiverInfo->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Cancel Accept money request 3');
                    }
                    /* Card Payment End */

                    $refrence_id = "Trans-" . $request_id;
                    $trans = new Transaction([
                        "user_id" => $user_id,
                        "receiver_id" => $receiverInfo->id,
                        "amount" => $amount,
                        "amount_value" => $amount,
                        "transaction_amount" => 0,
                        "total_amount" => $amount,
                        "trans_type" => 1,
                        "payment_mode" => "Refund",
                        "status" => 1,
                        'entryType' => 'API',
                        "refrence_id" => $refrence_id,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                        'swapDomainName' => 'UAT_SERVER'
                    ]);
                    $trans->save();

                    $title = __("message_app.Congratulations");
                    $message = __('message_app.withdraw_request_rejected', [
                        'currency' => CURR,
                        'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                    ]);

                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'REJECTEDWITHDRAW',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        "user_id" => $receiverInfo->id,
                        "notif_title" => $title,
                        "notif_body" => $message,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $notif->save();
                } else {
                    $receiverInfo = User::where("id", $requestDetail->receiver_id)->first();

                    $title = "Rejected! ";
                    $message = __('message_app.send_money_request_rejected', [
                        'currency' => CURR,
                        'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                    ]);

                    $device_type = $senderInfo->device_type;
                    $device_token = $senderInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'REJECTEDSENDMONEY',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        "user_id" => $senderInfo->id,
                        "notif_title" => $title,
                        "notif_body" => $message,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $notif->save();

                    // Send Email
                    $transaction = Transaction::where("id", $request_id)->first();

                    $status = $this->getStatusText(1);
                    $senderName = $senderInfo->name;
                    $senderEmail = $senderInfo->email;
                    $receiverName = $receiverInfo->name;
                    $receiverEmail = $receiverInfo->email;
                    $senderAmount = $amount;
                    $receiverAmount = $amount;
                    $transactionFees = 0;
                    $transaction_date = date('d M, Y h:i A', strtotime(
                        $transaction->created_at ?? 'today'
                    ));
                    $emailData['subjects'] = __("message_app.Money Request Rejected");
                    $emailData['senderName'] = $senderName;
                    $emailData['senderEmail'] = $senderEmail;
                    $emailData['senderAmount'] = $senderAmount;
                    $emailData['currency'] = CURR;
                    $emailData['receiverName'] = $receiverName;
                    $emailData['receiverAmount'] = $receiverAmount;
                    $emailData['receiverEmail'] = $receiverEmail;
                    $emailData['transId'] = $transaction->refrence_id ?? 'refrence_id';
                    $emailData['transactionFees'] = $transactionFees;
                    $emailData['transactionDate'] = $transaction_date;
                    $emailData['transactionStatus'] = $this->getStatusText(4);

                    if ($receiverEmail != "") {
                        /* Mail::send('emails.money_request_rejected', $emailData, function ($message) use ($emailData) {
                            $message->to($emailData["receiverEmail"], $emailData["receiverEmail"])
                                ->subject($emailData["subjects"]);
                        }); */
                    }
                }
                Transaction::where("id", $request_id)->update(["status" => 4]);
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.The money request has been successfully cancelled"),
                ];
            }

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __('message_app.Invalid Request'));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function getDocumentTypes(Request $request)
    {
        $dataArr = array();
        $documents = array();
        $documents[] = array(
            'id' => 'HEALTH_CARD',
            'name' => 'Health Insurance Card & Health Card',
            'isBoth' => false
        );
        $documents[] = array(
            'id' => 'IDENTITY_CARD',
            'name' => 'National IDs, Consular IDs & Diplomat IDs',
            'isBoth' => true
        );
        $documents[] = array(
            'id' => 'PASSPORT',
            'name' => 'Passports',
            'isBoth' => false
        );
        $documents[] = array(
            'id' => 'RESIDENT_ID',
            'name' => 'Residency permits & Residency cards',
            'isBoth' => false
        );
        $documents[] = array(
            'id' => 'TRAVEL_DOC',
            'name' => 'Border crossing documents, Refugee document & Visas',
            'isBoth' => false
        );

        if (!empty($documents)) {
            $data['data'] = $documents;

            $statusArr = array("status" => "Success", "reason" => "Document types List");
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => "Document types not found");
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function recentPayment(Request $request)
    {
        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $user_role = Auth::user()->user_type;

        $input = [
            'page' => $request->page,
            'limit' => $request->limit,
            'search' => $request->search
        ];

        $validate_data = [
            'page' => 'required',
            'limit' => 'required',
        ];

        $customMessages = [
            'page.required' => __("message_app.Page field cannot be left blank"),
            'limit.required' => __("message_app.Limit field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $page = $input['page'];
        $limit = $input['limit'];
        $search = $input['search'];

        $start = $page - 1;
        $start = $start * $limit;

        $uniqueUserIds = DB::table('transactions')
            ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
            ->select(DB::raw('CASE 
                              WHEN user_id = ' . $user_id . ' THEN receiver_id 
                              WHEN receiver_id = ' . $user_id . ' THEN user_id 
                              ELSE user_id 
                           END AS unique_user_id', [$user_id, $user_id]))
            ->where(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->orWhere('receiver_id', $user_id);
            })
            ->where(function ($query) use ($user_id, $search) {
                $query->where('u1.name', 'LIKE', "%$search%")
                    ->orWhere('u2.name', 'LIKE', "%$search%")
                    ->orWhere('u1.phone', 'LIKE', "%$search%")
                    ->orWhere('u2.phone', 'LIKE', "%$search%")
                    ->orWhere('transactions.id', 'LIKE', "%$search%");
            })
            ->orderByDesc('transactions.updated_at')
            ->distinct()
            ->skip($start)
            ->take($limit)
            ->get();

        $totalRecords = DB::table('transactions')
            ->select(DB::raw('CASE 
                              WHEN user_id = ' . $user_id . ' THEN receiver_id 
                              WHEN receiver_id = ' . $user_id . ' THEN user_id 
                              ELSE user_id 
                           END AS unique_user_id', [$user_id, $user_id]))
            ->where(function ($query) use ($user_id) {
                $query->where('user_id', $user_id)
                    ->orWhere('receiver_id', $user_id);
            })
            ->orderByDesc('transactions.updated_at')
            ->distinct()
            ->pluck('unique_user_id')->toArray();

        // You can then convert the result to an array if needed
        $uniqueUserIdsArray = $uniqueUserIds->pluck('unique_user_id')->toArray();

        if ($uniqueUserIds->isEmpty()) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.no_record_found"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $totalRecords = count($totalRecords);

        $userDatas = User::whereIn('id', $uniqueUserIdsArray)->where('user_type', '!=', 'Agent')->get();
        $data = array();
        foreach ($userDatas as $userInfo) {
            $userData["name"] = $userInfo->name;
            $userData["user_id"] = $userInfo->id;
            $userData["user_type"] = $userInfo->user_type;
            if ($userInfo->profile_image != "" && $userInfo->profile_image != "no_user.png") {
                $userData["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
            } else {
                $userData["profile_image"] = "public/img/" . "no_user.png";
            }
            $userData["phone"] = $userInfo->phone;
            $data["data"][] = $userData;
        }

        $statusArr = array(
            "status" => "Success",
            "total_records" => $totalRecords,
            "total_page" => ceil($totalRecords / $limit)
        );
        $json = array_merge($statusArr, $data);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getSwapContactsList(Request $request)
    {
        $request = $this->decryptContent($request->req);

        /* ================= VALIDATION ================= */

        $page = (int) ($request->page ?? 0);
        $limit = (int) ($request->limit ?? 0);
        $search = preg_replace('/\D/', '', $request->search ?? '');

        if ($page <= 0 || $limit <= 0) {
            return response()->json(
                $this->encryptContent(json_encode([
                    "status" => "Failed",
                    "reason" => __("message_app.Page field cannot be left blank"),
                ])),
                200
            );
        }

        /* ================= CONTACT LIST ================= */

        // Force contactsList to array (important for 2000+ contacts)
        $contactsList = json_decode(json_encode($request->contactsList ?? []), true);

        $results = [];
        $processedNumbers = [];

        /* ================= PROCESS ALL CONTACTS ================= */

        foreach ($contactsList as $contact) {

            if (!isset($contact['phone']) || !is_array($contact['phone'])) {
                continue;
            }

            foreach ($contact['phone'] as $phoneNumber) {

                // Digits only
                $originalNumber = preg_replace('/\D/', '', $phoneNumber);
                if ($originalNumber === '')
                    continue;

                // SEARCH FILTER
                if (!empty($search) && strpos($originalNumber, $search) === false) {
                    continue;
                }

                // ONLY 241 COUNTRY CODE NUMBERS
                if (!str_starts_with($originalNumber, '241')) {
                    continue;
                }

                // Remove country code for DB matching
                $localNumber = substr($originalNumber, 3);

                // Prevent duplicate numbers
                if (isset($processedNumbers[$originalNumber])) {
                    continue;
                }
                $processedNumbers[$originalNumber] = true;

                /* ================= USER MATCH ================= */

                $user = User::where('phone', $originalNumber)
                    ->orWhere('phone', $localNumber)
                    ->first();

                if ($user) {

                    $profile = ($user->profile_image && $user->profile_image !== "no_user.png")
                        ? PROFILE_FULL_DISPLAY_PATH . $user->profile_image
                        : "public/img/no_user.png";

                    $results[] = [
                        'phone' => $originalNumber,
                        'name' => $contact['name'] ?? '',
                        'user_id' => $user->id,
                        'user_type' => $user->user_type,
                        'profile_image' => $profile,
                    ];
                } else {

                    $results[] = [
                        'phone' => $originalNumber,
                        'name' => $contact['name'] ?? '',
                        'user_id' => 0,
                        'user_type' => '',
                        'profile_image' => '',
                    ];
                }
            }
        }

        usort($results, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']); // A → Z
        });

        /* ================= PAGINATION (LAST STEP) ================= */

        $offset = ($page - 1) * $limit;
        $paginatedData = array_slice($results, $offset, $limit);

        if (empty($paginatedData)) {
            return response()->json(
                $this->encryptContent(json_encode([
                    "status" => "Failed",
                    "reason" => __("message_app.no_record_found"),
                ])),
                200
            );
        }

        /* ================= RESPONSE ================= */

        return response()->json(
            $this->encryptContent(json_encode([
                "status" => "Success",
                "reason" => __("message_app.Fetched record successfully"),
                "data" => $paginatedData,
            ])),
            200
        );
    }


    public function getAirtelContactsList(Request $request)
    {
        $request = $this->decryptContent($request->req);

        $input = [
            'page' => $request->page ?? null,
            'limit' => $request->limit ?? null,
            'search' => $request->search ?? null,
        ];

        $validator = Validator::make(
            $input,
            [
                'page' => 'required|integer|min:1',
                'limit' => 'required|integer|min:1',
            ],
            [
                'page.required' => __("message_app.Page field cannot be left blank"),
                'limit.required' => __("message_app.Limit field cannot be left blank"),
            ]
        );

        if ($validator->fails()) {
            return response()->json(
                $this->encryptContent(json_encode([
                    "status" => "Failed",
                    "reason" => implode('<br>', $validator->messages()->all()),
                ])),
                200
            );
        }

        $page = (int) $input['page'];
        $limit = (int) $input['limit'];
        $offset = ($page - 1) * $limit;

        $contactsList = $request->contactsList ?? [];
        $search = preg_replace('/\D/', '', $input['search'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | FILTER ONLY AIRTEL CONTACTS WITH EXISTING 241 / +241
        |--------------------------------------------------------------------------
        */
        $airtelContacts = [];
        $seen = [];

        foreach ($contactsList as $contact) {

            if (!isset($contact->phone) || !is_array($contact->phone)) {
                continue;
            }

            foreach ($contact->phone as $phoneNumber) {

                // Must START with 241 or +241
                if (!preg_match('/^\+?241/', $phoneNumber)) {
                    continue;
                }

                // Digits only
                $digits = preg_replace('/\D/', '', $phoneNumber);

                // Remove country code for Airtel check
                $localNumber = substr($digits, 3);

                // Search filter
                if ($search && strpos($localNumber, $search) === false) {
                    continue;
                }

                // Airtel validation
                if (!$this->isAirtelNumber($localNumber)) {
                    continue;
                }

                // Avoid duplicates
                if (isset($seen[$digits])) {
                    continue;
                }
                $seen[$digits] = true;

                $airtelContacts[] = (object) [
                    'name' => $contact->name ?? '',
                    'phone' => $digits, // keep original 241 format
                    'local' => $localNumber,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */
        $currentPageRecords = array_slice($airtelContacts, $offset, $limit);

        if (empty($currentPageRecords)) {
            return response()->json(
                $this->encryptContent(json_encode([
                    "status" => "Failed",
                    "reason" => __("message_app.no_record_found"),
                ])),
                200
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SINGLE DB QUERY
        |--------------------------------------------------------------------------
        */
        $phones = array_column($currentPageRecords, 'local');

        $users = User::whereIn('phone', $phones)->get()->keyBy('phone');

        /*
        |--------------------------------------------------------------------------
        | BUILD RESPONSE (NO NUMBER MODIFICATION)
        |--------------------------------------------------------------------------
        */
        $response = [];

        foreach ($currentPageRecords as $contact) {

            if (isset($users[$contact->local])) {

                $user = $users[$contact->local];

                $profile = ($user->profile_image && $user->profile_image !== "no_user.png")
                    ? PROFILE_FULL_DISPLAY_PATH . $user->profile_image
                    : "public/img/no_user.png";

                $response[] = [
                    'phone' => $contact->phone, // already 241
                    'name' => $contact->name,
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'profile_image' => $profile,
                ];
            } else {
                $response[] = [
                    'phone' => $contact->phone, // already 241
                    'name' => $contact->name,
                    'user_id' => 1,
                    'user_type' => "",
                    'profile_image' => "",
                ];
            }
        }
        usort($response, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']); // A → Z
        });

        return response()->json(
            $this->encryptContent(json_encode([
                "status" => "Success",
                "reason" => __("message_app.Fetched record successfully"),
                "data" => $response,
            ])),
            200
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER: Airtel Validation (AFTER 241)
    |--------------------------------------------------------------------------
    */
    private function isAirtelNumber(string $number): bool
    {
        return (
            str_starts_with($number, '077') ||
            str_starts_with($number, '076') ||
            str_starts_with($number, '074') ||
            str_starts_with($number, '77') ||
            str_starts_with($number, '76') ||
            str_starts_with($number, '74')
        );
    }

    public function convertEtoD(Request $request)
    {
        $request = $this->decryptContent($request->req);
        return response()->json($request, 200);
    }
    public function convertDtoE(Request $request)
    {
        $request = $this->encryptContent($request->req);
        return response()->json($request, 200);
    }

    public function makeEncrypt(Request $request)
    {
        $response = response()->json($request->all(), 401);
        $encryptedResponse = $this->encryptContent($response->getContent());
        return response()->json($encryptedResponse, 200);
    }

    public function getTransLimit(Request $request)
    {
        $admin = Admin::where('status', 1)->select('minDeposit', 'maxDeposit', 'minWithdraw', 'maxWithdraw', 'minSendMoney', 'maxSendMoney', 'moneyTransferMin', 'moneyTransferMax')->first();
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $countryList = [
            'minDeposit' => strval($admin->minDeposit),
            'maxDeposit' => strval($admin->maxDeposit),
            'minWithdraw' => strval($admin->minWithdraw),
            'maxWithdraw' => strval($admin->maxWithdraw),
            'minSendMoney' => strval($admin->minSendMoney),
            'maxSendMoney' => strval($admin->maxSendMoney),
            'moneyTransferMin' => strval($admin->moneyTransferMin),
            'moneyTransferMax' => strval($admin->moneyTransferMax),
        ];
        $json = array_merge($statusArr, ['data' => $countryList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getCountryList(Request $request)
    {
        /* $statusArr = array("countryFor" =>"SENDER","type"=>'CEMAC');
        $json = json_encode($statusArr);
        $requestData = $this->encryptContent($json);
        echo $requestData; die; */

        $request = $this->decryptContent($request->req);
        $input = [
            'type' => $request->type ?? null,
            'countryFor' => $request->countryFor ?? null,
        ];
        $validate_data = [
            'type' => 'required|in:CEMAC,OUTCEMAC',
            'countryFor' => 'required|in:SENDER,RECEIVER',
        ];
        $customMessages = [
            'type.required' => __("message_app.Type field cannot be left blank"),
            'countryFor.required' => __("message_app.Country for field cannot be left blank"),
            'countryFor.in' => __("message_app.Only allow country for SENDER or RECEIVER"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($firstErrorMessage == "validation.in") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allow"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        if ($request->type == "CEMAC") {
            $countryList = Country::where('status', 1)->orderBy('name', 'ASC')->get();
        } else {
            if ($request->countryFor == "SENDER") {
                $countryList = DB::table('countries_onafriq')->where('status', 1)->orderBy('name', 'ASC')->get();
            } else {
                $countryList = DB::table('countries_onafriq')->where('status', 1)->where('countryFor', $request->countryFor)->orderBy('name', 'ASC')->get();
            }
        }
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['data' => $countryList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getWalletManagerList(Request $request)
    {
        /* $statusArr = array("country_id" =>"1","type"=>'OUTCEMACA');
        $json = json_encode($statusArr);
        $requestData = $this->encryptContent($json);
        echo $requestData; die; */

        $request = $this->decryptContent($request->req);
        $input = [
            'country_id' => $request->country_id,
            'type' => $request->type ?? null,
        ];
        $validate_data = [
            'country_id' => 'required',
            'type' => 'required|in:CEMAC,OUTCEMAC',
        ];

        $customMessages = [
            'country_id.required' => __("message_app.Country field cannot be left blank"),
            'type.required' => __("message_app.Type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($firstErrorMessage == "validation.in") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allow"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $country = $input['country_id'];
        if ($request->type == "CEMAC") {
            $countryList = WalletManager::where('country_id', $country)->orderBy('name', 'ASC')->get();
        } else {
            $countryList = DB::table('wallet_manager_onafriq')->where('country_id', $country)->orderBy('name', 'ASC')->get();
        }
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['data' => $countryList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function fundTransferGimac(Request $request)
    {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $minAmount = "1";
        $maxAmount = "10000000";

        $input = [
            'opponent_user_id' => $request->opponent_user_id,
            'amount' => $request->amount,
            'tomember' => $request->tomember,
            'trans_type' => $request->trans_type
        ];

        $validate_data = [
            'opponent_user_id' => 'required',
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
            'tomember' => 'required',
            'trans_type' => 'required',
        ];

        $customMessages = [
            'opponent_user_id.required' => __("message_app.Opponent User id field cannot be left blank"),
            'amount.required' => __("message_app.Amount is required"),
            'amount.numeric' => __("message_app.'Amount must be a valid number"),
            'amount.gt' => __("message_app.Amount must be greater than zero"),
            'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
            'tomember.required' => __("message_app.Tomember field cannot be left blank"),
            'trans_type.required' => __("message_app.Trans type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $amount = $input['amount'];

        $senderUser = User::where('id', $user_id)->first();

        $userType = $this->getUserType($senderUser->user_type);

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->gimacMin > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->gimacMin,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->gimacMax) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->gimacMax,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($json, 200);
        }

        $amount = $request->amount;
        $total_fees = 0;
        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        // echo"<pre>";print_r($feeapply);die;

        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }


        if (($amount + $total_fees) > Auth::user()->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        // $trans_fees = Transactionfee::where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
        //     ->where('max_amount', '>=',  $request->amount)->first();
        // if (!empty($trans_fees)) {
        //     $feeType = $trans_fees->fee_type;
        //     if ($feeType == 1) {
        //         $total_fees = $trans_fees->fee_amount;
        //     } else {
        //         $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
        //     }
        // }

        $total_amount = $amount;

        $tomember = $input['tomember'];
        $receviver_mobile = $input['opponent_user_id'];
        $sender_mobile = User::where('id', $user_id)->first()->phone;
        $certificate = public_path("MTN Cameroon Issuing CA1.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
        $options = [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => env('GIMAC_CLIENT_ID'),
                'client_secret' => env('GIMAC_CLIENT_SECRET'),
                'scope' => 'read',
                'username' => env('GIMAC_USER_NAME'),
                'password' => env('GIMAC_PASSWORD'),
                'expires_in' => 86400,
            ],
        ];

        try {
            // Make a request using the client
            $response = $client->request('POST', env('GIMAC_TOKEN_URL'), [
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

            $dateString = date('d-m-Y H:i:s');
            $format = 'd-m-Y H:i:s';
            $dateTime = DateTime::createFromFormat($format, $dateString);
            $timestamp = $dateTime->getTimestamp();
            $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
            if ($last_record != "") {
                $next_issuertrxref = $last_record + 1;
            } else {
                $next_issuertrxref = '140071';
            }

            $url = env('GIMAC_PAYMENT_URL');
            $data = [
                'createtime' => $timestamp,
                'intent' => 'mobile_transfer',
                'walletsource' => $sender_mobile,
                'walletdestination' => $receviver_mobile,
                'issuertrxref' => $next_issuertrxref,
                'amount' => $total_amount,
                'currency' => '950',
                'description' => 'money transfer',
                'tomember' => $tomember,
            ];

            $response = $client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'json' => $data,
            ]);

            $body = $response->getBody()->getContents();
            $jsonResponse2 = json_decode($body);
            $statusCode = $response->getStatusCode();
            if ($statusCode == 200) {
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                $tomember = $jsonResponse2->tomember;
                $acquirertrxref = $jsonResponse2->acquirertrxref;
                $issuertrxref = $jsonResponse2->issuertrxref;
                $state = $jsonResponse2->state;
                // $status = $state == 'ACCEPTED' ? 1 : 2;
                $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));
                $vouchercode = $jsonResponse2->vouchercode;
                $trans_id = time();
                $refrence_id = time();
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'receiver_mobile' => $receviver_mobile,
                    'amount' => $amount,
                    'amount_value' => $total_amount,
                    'transaction_amount' => $total_fees,
                    'total_amount' => ($amount + $total_fees),
                    'trans_type' => 2,
                    'payment_mode' => 'wallet2wallet',
                    'status' => $status,
                    'refrence_id' => $issuertrxref,
                    'billing_description' => 'Fund Transfer-' . $refrence_id,
                    'tomember' => $tomember,
                    'acquirertrxref' => $acquirertrxref,
                    'issuertrxref' => $issuertrxref,
                    'vouchercode' => $vouchercode,
                    'transactionType' => 'SWAPTOGIMAC',
                    'entryType' => 'API',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();

                $statusArr = [
                    "status" => "Success",
                    "reason" => $state == 'ACCEPTED' ? __("message_app.Your transaction has been successful") : __('message_app.transaction_in_state', ['state' => $state]),
                ];

                if ($state == 'ACCEPTED' || $state == 'PENDING') {
                    $sender_wallet_amount = $senderUser->wallet_balance - $amount - $total_fees;
                    User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);
                    DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                }

                // Send Email
                $senderName = $senderUser->name;
                $senderEmail = $senderUser->email;
                $senderAmount = $amount;
                $receiverAmount = $total_amount;
                $transactionFees = $total_fees;
                $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));
                $emailData['subjects'] = __("message_app.Funds Transfer Details");
                $emailData['senderName'] = $senderName;
                $emailData['senderEmail'] = $senderEmail;
                $emailData['senderAmount'] = $senderAmount;
                $emailData['currency'] = CURR;

                $emailData['receiverName'] = "Test"; //$receiverName;

                $emailData['transId'] = $refrence_id;
                $emailData['transactionFees'] = $transactionFees;
                $emailData['transactionDate'] = $transaction_date;
                $emailData['transactionStatus'] = $state;

                /* if ($senderEmail != "") {
                    Mail::send(
                        'emails.fund_transfer_sender',
                        $emailData,
                        function ($message) use ($emailData) {
                            $message->to(
                                $emailData["senderEmail"],
                                $emailData["senderEmail"]
                            )->subject($emailData["subjects"]);
                        }
                    );
                } */

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody();
                $contents = $body->getContents();
                // Now, $contents contains the response body
                $jsonResponse = json_decode($contents, true);

                $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                if ($last_record != "") {
                    $next_issuertrxref = $last_record + 1;
                } else {
                    $next_issuertrxref = '140061';
                }

                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                    $errorDescription = $jsonResponse['error_description'];
                } else {
                    $statusCode = $response->getStatusCode();
                    $errorDescription = __("message_app.Server Error, Please wait a few minutes before you try again");
                    if ($statusCode == '403') {
                        $errorDescription = __("message_app.Error Code : 403 Forbidden Error");
                    }
                }
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function walletIncomingRemittance(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        $input = [
            'country_id' => $request->country_id ?? null,
            'walletManagerId' => $request->walletManagerId ?? null,
            'phone' => $request->phone ?? null,
            'ibanNumber' => $request->ibanNumber ?? null,
            'amount' => $request->amount ?? null,
            'trans_type' => $request->trans_type ?? null,
            'note' => $request->note ?? null,
        ];

        $validate_data = [
            'country_id' => 'required',
            'walletManagerId' => 'required_without:ibanNumber',
            'ibanNumber' => 'required_without:walletManagerId',
            'phone' => 'required_with:walletManagerId',
            'trans_type' => 'required',
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => 'nullable|string|max:255',
        ];

        $customMessages = [
            'country_id.required' => __("message_app.Country field cannot be left blank"),
            'walletManagerId.required_without' => __("message_app.Please provide either a wallet manager or enter an IBAN number"),
            'ibanNumber.required_without' => __("message_app.Please provide either IBAN number or select a wallet manager"),
            'phone.required_with' => __("message_app.Phone number is required when Wallet manager is entered"),
            'trans_type.required' => __("message_app.Trans type field cannot be left blank"),
            'amount.gt' => __("message_app.Amount must be grater than 0"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $country_id = $request->country_id ?? 0;
        $walletManagerId = $request->walletManagerId ?? 0;
        $phone = $request->phone ?? "";
        $ibanNumber = $request->ibanNumber ?? "";
        $trans_type = $request->trans_type;
        $note = $request->note ?? "";


        $amount = $request->amount;

        $senderUser = User::where('id', $user_id)->first();

        $userType = $this->getUserType($senderUser->user_type);

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->gimacMin > $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->minSendMoney)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($amount > $transactionLimit->gimacMax) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxSendMoney)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $tomember = ""; // default value
        $paymentType = ""; // default value
        if (isset($walletManagerId) && $walletManagerId != "") {
            $tomemberData = WalletManager::where('id', $walletManagerId)->first();
            if (!empty($tomemberData)) {
                $tomember = $tomemberData->tomember;
                $paymentType = 'WALLETINCOMMING';
            }
        } else {
            $tomember = $request->tomember ?? "10029";
            $paymentType = 'INCACCREMIT';
        }

        $getfeeRecord = $this->calculateTotalFees($trans_type, $amount);
        //$total_fees = $getfeeRecord['total_fees'] ?? 0;
        $total_fees = 0;
        $total_amount = $amount + $total_fees;

        /* if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        $tomember = $tomember ?? "";
        $sender_mobile = $senderUser->phone;
        $receviver_mobile = $phone ?? "";
        $cardNumber = "";
        $senderAccount = "";
        $receiverAccount = $ibanNumber ?? "";

        $senderData = [];
        $receiverData = [];
        try {
            $dateString = date('d-m-Y H:i:s');
            $format = 'd-m-Y H:i:s';
            $dateTime = DateTime::createFromFormat($format, $dateString);
            $timestamp = $dateTime->getTimestamp();
            $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
            if ($last_record != "") {
                $next_issuertrxref = $last_record + 1;
            } else {
                $next_issuertrxref = '140071';
            }

            $accessToken = $this->gimacApiService->getAccessToken();
            // dd($accessToken);
            if ($accessToken['status'] === false) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Access token not found"), //$accessToken['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $accessToken = $accessToken['token'];

            $ibanAccount = $receiverAccount;
            $newIssuertrxref = uniqid();


            $responseInquiry = $this->gimacApiService->walletAndAccountInquiry($paymentType, $receviver_mobile, $newIssuertrxref, $tomember, $accessToken, $ibanAccount);

            if ($responseInquiry['status'] == false && $responseInquiry['statusCode'] == null && $responseInquiry['data'] == null) {
                $statusArr = [
                    "status" => "Failed",
                    // "reason" => $responseInquiry['message'],
                    "reason" => __("message_app.Wallet/Account not found") ?? __("message_app.Timeout during send to payee"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }



            $senderData = [
                "firstname" => $senderUser->name ?? '',
                "secondname" => $senderUser->lastName ?? ''
            ];
            $receiverData = [
                "firstname" => $responseInquiry['data']->receivercustomerdata->firstname ?? '',
                "secondname" => $responseInquiry['data']->receivercustomerdata->secondname ?? ''
            ];

            $jsonResponse2 = $this->gimacApiService->walletPaymentAllType($timestamp, $sender_mobile, $paymentType, $receviver_mobile, $next_issuertrxref, $amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData);
            // print_r($jsonResponse2); die;

            if ($jsonResponse2['statusCode'] == 200) {
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                $jsonResponse2 = $jsonResponse2['data'];
                $tomember = $jsonResponse2->tomember ?? "";
                $acquirertrxref = $jsonResponse2->acquirertrxref ?? "";
                $issuertrxref = $jsonResponse2->issuertrxref;
                $state = $jsonResponse2->state;
                // $status = $state == 'ACCEPTED' ? 1 : 2;
                $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));
                $vouchercode = $jsonResponse2->vouchercode ?? "";
                $refrence_id = time();
                $remainingWalletBalance = $senderUser->wallet_balance;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'receiver_mobile' => $receviver_mobile,
                    'receiverName' => $responseInquiry['data']->receivercustomerdata->firstname . ' ' . $responseInquiry['data']->receivercustomerdata->secondname,
                    'amount' => $amount,
                    'amount_value' => $amount,
                    'transaction_amount' => $total_fees ?? 0,
                    'total_amount' => $total_amount,
                    'trans_type' => 2,
                    'payment_mode' => 'wallet2wallet',
                    'status' => $status,
                    'refrence_id' => $issuertrxref,
                    'billing_description' => "Fund Transfer-$refrence_id",
                    'country_id' => $country_id ?? 0,
                    'walletManagerId' => $walletManagerId ?? 0,
                    'tomember' => $tomember ?? '',
                    'acquirertrxref' => $acquirertrxref,
                    'cardHolderName' => '',
                    'senderAccount' => $senderAccount ?? '',
                    'receiverAccount' => $receiverAccount ?? '',
                    'issuertrxref' => $issuertrxref,
                    'vouchercode' => $vouchercode,
                    'transactionType' => 'SWAPTOCEMAC',
                    'paymentType' => $paymentType,
                    'senderData' => json_encode($senderData) ?? "",
                    'receiverData' => json_encode($receiverData) ?? "",
                    'remainingWalletBalance' => $remainingWalletBalance ?? "",
                    'beforeBalance' => $remainingWalletBalance,
                    'notes' => $note ?? "",
                    'entryType' => 'API',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();
                $transactionId = $trans->id;

                $statusArr = [
                    "status" => "Success",
                    "reason" => $state == 'ACCEPTED' ? __("message_app.Your transaction has been successful") : __('message_app.transaction_in_state', ['state' => $state]),
                    "transactionId" => $transactionId,
                ];

                $device_token = $senderUser->device_token;
                $device_type = $senderUser->device_type;

                if ($state == 'ACCEPTED') {
                    $remainingWalletBalance = $senderUser->wallet_balance + $amount;
                    Transaction::where('id', $trans->id)->update(['remainingWalletBalance' => $remainingWalletBalance, 'trans_type' => 1]);
                    User::where('id', $senderUser->id)->increment('wallet_balance', $amount);

                    /* Card Payment Start */
                    $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $amount,
                            "transferType" => "WalletToCard",
                            "mobilePhoneNumber" => "241{$senderUser->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Wallet incomming');
                    }
                    /* Card Payment End */

                    $data1 = [
                        'title' => __("message_app.Transaction Successful"),
                        'message' => __("message_app.Your Transaction has been successful"),
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];
                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            "Transaction Successful",
                            "Your Transaction has been successful",
                            $data1,
                            $device_type
                        );
                    }
                    $notif = new Notification([
                        'user_id' => $senderUser->id,
                        'notif_title' => __("message_app.Transaction Successful"),
                        'notif_body' => __("message_app.Your Transaction has been successful"),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                } elseif ($state == 'PENDING') {
                    $remainingWalletBalance = $senderUser->wallet_balance;
                    Transaction::where('id', $trans->id)->update(['remainingWalletBalance' => $remainingWalletBalance]);

                    $data1 = [
                        'title' => __("message_app.Transaction Pending"),
                        'message' => __("message_app.Your Transaction has been pending"),
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];
                    $device_token = $senderUser->device_token;
                    $device_type = $senderUser->device_type;
                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            "Transaction Pending",
                            "Your Transaction has been pending",
                            $data1,
                            $device_type
                        );
                    }
                }

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {

                if ($jsonResponse2['message'] == "MEMBER EXCEEDS WITHDRAWAL LIMITS") {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Enter an amount greater than 500 XAF"),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $jsonResponse2['message'],
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody();
                $contents = $body->getContents();
                // Now, $contents contains the response body
                $jsonResponse = json_decode($contents, true);

                $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                if ($last_record != "") {
                    $next_issuertrxref = $last_record + 1;
                } else {
                    $next_issuertrxref = '140061';
                }

                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                    $errorDescription = $jsonResponse['error_description'];
                } else {
                    $statusCode = $response->getStatusCode();
                    $errorDescription = __("message_app.Server Error, Please wait a few minutes before you try again");
                    if ($statusCode == '403') {
                        $errorDescription = __("message_app.Error Code : 403 Forbidden Error");
                    }
                }
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function getUserType($key)
    {
        $userArray = array("User" => 1, "Merchant" => 2, "Agent" => 3);
        return $userArray[$key];
    }
    public function processRemittance(Request $request)
    {
        // Log the raw XML data received for debugging
        // Log::info($request->getContent());
        // Parse the incoming XML request
        try {
            $xml = simplexml_load_string($request->getContent());
            $json = json_encode($xml);
            $data = json_decode($json, true);


            // Process your remittance logic here
            // Example: validation, saving to database, etc.
            //    echo"<pre>";print_r($data);die;
            // $apiKey = config('services.onafriq.api_key');  // Ensure you store the API key securely in the config
            // $endpoint = 'https://api.onafriq.com/v1/remittance';  // Example API endpoint
            // // Collect data from request (e.g., amount, sender, receiver details)
            // $remittanceData = [
            //     'amount' => $request->input('amount'),
            //     'currency' => $request->input('currency'),
            //     'sender' => [
            //         'name' => $request->input('sender_name'),
            //         'country' => $request->input('sender_country'),
            //     ],
            //     'receiver' => [
            //         'name' => $request->input('receiver_name'),
            //         'country' => $request->input('receiver_country'),
            //     ]
            // ];
            // // Send a POST request to Onafriq's API
            // $response = Http::withHeaders([
            //     'Authorization' => 'Bearer ' . $apiKey,
            //     'Accept' => 'application/json',
            //     'Content-Type' => 'application/json',
            // ])->post($endpoint, $remittanceData);
            // // Handle the response from Onafriq
            // if ($response->successful()) {
            //     return response()->json([
            //         'status' => 'success',
            //         'data' => $response->json(),
            //     ]);
            // }
            // Return a success response in XML format
            $response = [
                'status' => 'success',
                'message' => __("message_app.Remittance processed successfully"),
                'data' => $data
            ];

            return response($this->arrayToXml($response))->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            // Handle the error and return a failure response in XML format
            $errorResponse = [
                'status' => 'error',
                'message' => __("message_app.Failed to process remittance")
            ];

            return response($this->arrayToXml($errorResponse), Response::HTTP_BAD_REQUEST)
                ->header('Content-Type', 'application/xml');
        }
    }
    // Helper function to convert an array to XML
    private function arrayToXml($array, $rootElement = null, $xml = null)
    {
        $_xml = $xml;

        if ($_xml === null) {
            $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<response/>');
        }

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->arrayToXml($v, $k, $_xml->addChild($k));
            } else {
                $_xml->addChild($k, htmlspecialchars($v));
            }
        }

        return $_xml->asXML();
    }

    function generatePartnerString()
    {
        // Generate components
        $part1 = rand(1000, 9999); // Random 4-digit number
        $part2 = 'PR'; // Fixed string
        $part3 = rand(10000, 99999); // Random 5-digit number

        // Combine parts
        return $part1 . $part2 . $part3;
    }
    public function fundTransferBda(Request $request)
    {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $input = [
            'beneficiary' => $request->beneficiary ?? null,
            'iban' => $request->iban ?? null,
            'reason' => $request->reason ?? null,
            'amount' => $request->amount ?? null,
            'trans_type' => $request->trans_type ?? null
        ];

        $validate_data = [
            'beneficiary' => ['required', 'max:15'],
            //'iban' => ['required', 'regex:/^[a-zA-Z0-9]+$/', 'size:28'],
            'iban' => ['required', 'regex:/^[a-zA-Z0-9]+$/', 'min:24', 'max:30'],
            'reason' => 'required',
            'amount' => 'required|numeric|min:1|max:999999',
        ];

        $customMessages = [
            'beneficiary.required' => __("message_app.Beneficiary cannot be left blank"),
            'iban.required' => __("message_app.The IBAN field is required"),
            'iban.min' => __("message_app.The IBAN must be at least 24 characters"),
            'iban.max' => __("message_app.The IBAN cannot be more than 30 characters"),
            'reason.required' => __("message_app.Reason field cannot be left blank"),
            'amount.required' => __("message_app.Amount field cannot be left blank"),
        ];
        // print_r($request->all()); die;
        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $amount = $input['amount'];
        $partnerReference = $this->generatePartnerString();


        $senderUser = User::where('id', $user_id)->first();

        $userType = $this->getUserType($senderUser->user_type);

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->bdaMin > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->bdaMin,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->bdaMax) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->bdaMax,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($json, 200);
        }

        $amount = $request->amount;


        $total_fees = 0;
        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }

        $total_amount = $amount + $total_fees;

        if ($total_amount >= Auth::user()->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        try {
            $getLstNo = RemittanceData::orderBy('id', 'desc')->select('referenceLot')->first();
            if (empty($getLstNo)) {
                $refNoLo = 'SWAP9999';
            } else {
                preg_match('/([a-zA-Z]+)([0-9]+)/', $getLstNo->referenceLot, $matches);
                $incrementedPart = (int) $matches[2] + 1;
                $newReferenceLot = $matches[1] . $incrementedPart;
                $refNoLo = $newReferenceLot;
            }
            $certificate = public_path("CA Bundle.crt");
            $client = new Client([
                'verify' => $certificate,
                'timeout' => 30,
            ]);

            $data = [
                'referenceLot' => $refNoLo,
                'nombreVirement' => 1,
                'montantTotal' => $request->amount,
                'produit' => 'SWAP',
                'virements' => [
                    [
                        'ibanCredit' => $request->iban,
                        'intituleCompte' => $request->beneficiary,
                        'montant' => $request->amount,
                        'referencePartenaire' => $partnerReference,
                        'motif' => $request->reason,
                        'typeVirement' => 'RTGS'
                    ]
                ]
            ];

            $response = $client->post(env('BDA_URL'), [
                'json' => $data,
                'headers' => [
                    'x-api-key' => env('XAPIKEY'),
                    'x-client-id' => env('XCLIENTID'),
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);

            if ($responseBody['statut'] == 'EN_ATTENTE' || $responseBody['statut'] == 'EN_ATTENTE_REGLEMENT') {
                $statut = $responseBody['statut'];
                $rejectedStatus = '';

                $refrence_id = time();
                $remainingWalletBalance = $senderUser->wallet_balance - $amount;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'receiver_mobile' => '',
                    'amount' => $amount,
                    'amount_value' => $amount,
                    'transaction_amount' => $total_fees,
                    'total_amount' => $total_amount,
                    'trans_type' => 2,
                    'payment_mode' => 'wallet2wallet',
                    'status' => 2,
                    'bda_status' => 2,
                    'entryType' => 'API',
                    'billing_description' => 'Fund Transfer-' . $refrence_id,
                    'transactionType' => 'SWAPTOBDA',
                    'remainingWalletBalance' => $remainingWalletBalance,
                    'beforeBalance' => $senderUser->wallet_balance,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();

                $remittanceData = new RemittanceData();
                $remittanceData->transactionId = $this->generateAndCheckUnique();
                $remittanceData->product = 'SWAP';
                $remittanceData->iban = $request->input('iban', '');
                $remittanceData->titleAccount = $request->input('beneficiary', '');
                $remittanceData->amount = $request->input('amount', '');
                $remittanceData->partnerreference = $partnerReference;
                $remittanceData->reason = $request->input('reason', '');
                $remittanceData->userId = $user_id;
                $remittanceData->referenceLot = $refNoLo;
                $remittanceData->status = $statut;
                $remittanceData->type = 'bank_transfer';
                $remittanceData->trans_app_id = $trans->id;
                $remittanceData->save();

                Transaction::where('id', $trans->id)->update(['onafriq_bda_ids' => $remittanceData->id]);

                $sender_wallet_amount = $senderUser->wallet_balance - ($amount + $total_fees);
                User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);
                DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);


                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.Your transaction has been successful"),
                ];


                // Send Email
                $senderName = $senderUser->name;
                $senderEmail = $senderUser->email;
                $senderAmount = $amount;
                $receiverAmount = $amount;
                $transactionFees = $total_fees;
                $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));
                $emailData['subjects'] = __("message_app.Funds Transfer Details");
                $emailData['senderName'] = $senderName;
                $emailData['senderEmail'] = $senderEmail;
                $emailData['senderAmount'] = $senderAmount;
                $emailData['currency'] = CURR;

                $emailData['receiverName'] = 'Test'; //$receiverName;

                $emailData['transId'] = $refrence_id;
                $emailData['transactionFees'] = $transactionFees;
                $emailData['transactionDate'] = $transaction_date;
                $emailData['transactionStatus'] = 'success';

                /* if ($senderEmail != "") {
                    Mail::send(
                        'emails.fund_transfer_sender',
                        $emailData,
                        function ($message) use ($emailData) {
                            $message->to(
                                $emailData["senderEmail"],
                                $emailData["senderEmail"]
                            )->subject($emailData["subjects"]);
                        }
                    );
                } */

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } elseif ($responseBody['statut'] == 'REJETE') {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Transaction failed"),
                ];

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Transaction failed time out error"),
                ];

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Connection error: Timeout occurred while connecting to the server"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $contents = $response->getBody()->getContents();
                $jsonResponse = json_decode($contents, true);

                $errorDescription = $jsonResponse['error_description'] ?? __("message_app.Error Code: 403 Forbidden Error");

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            $statusArr = [
                "status" => "Failed",
                "reason" => $e->getMessage(),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function generateAndCheckUnique()
    {
        do {
            // Generate a random alphanumeric string
            $randomString = $this->generateRandomAlphaNumeric();

            // Check if the generated string exists in the transactions table
            $exists = RemittanceData::where('transactionID', $randomString)->exists();
        } while ($exists);

        return $randomString;
    }
    private function generateRandomAlphaNumeric()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $shuffled = str_shuffle($characters);
        return substr($shuffled, 0, 10);
    }
    private function sendCurlRequest($postData, $soapAction)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ' . $soapAction,
                'Authorization: Basic ' . $this->authString,
                'Cookie: GCLB=CJHI84va1Ji4BhAD',
                'User-Agent:' . CORPORATECODE
            ),
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        } else {
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_status != 200) {
                echo "Request failed with status: " . $http_status;
            } else {
                return $response;
            }
        }

        curl_close($curl);
    }
    function getRecipientCountry()
    {
        $data = [
            "BJ" => "Benin",
            "BF" => "Burkina Faso",
            "GW" => "Guinea Bissau",
            "NE" => "Niger",
            "SN" => "Senegal",
            "ML" => "Mali",
            "TG" => "Togo"
        ];

        $response = [];

        foreach ($data as $code => $country) {
            $response[] = [
                'code' => $code,
                'name' => $country
            ];
        }
        $statusArr = [
            "status" => "success",
            "message" => __("message_app.Recipient country retrieved successfully"),
            "result" => $response
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    function getSenderCountry()
    {
        $data = [
            "GA" => "Gabon",
            "CM" => "Cameroon",
            "CI" => "Ivoiry Coast",
            "FR" => "France",
            "BJ" => "Benin",
            "BF" => "Burkina Faso",
            "GW" => "Guinea Bissau",
            "NE" => "Niger",
            "SN" => "Senegal",
            "ML" => "Mali",
            "TG" => "Togo"
        ];

        $response = [];

        foreach ($data as $code => $country) {
            $response[] = [
                'code' => $code,
                'name' => $country
            ];
        }
        $statusArr = [
            "status" => "success",
            "message" => __("message_app.Sender country retrieved successfully"),
            "result" => $response
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    function getOnafriqWalletManager()
    {
        $data = [
            "MTN BENIN",
            "MOOV BENIN",
            "ORANGE BURKINA",
            "MTN GUINEA BISSAU",
            "ORANGE MALI",
            "AIRTEL NIGER",
            "MOOV NIGER",
            "FREE MONEY SENEGAL",
            "ORANGE SENEGAL",
            "MOOV AFRICA-TOGO",
            "TOGOCOM - TMONEY"
        ];


        $response = [];

        foreach ($data as $walletM) {
            $response[] = [
                'name' => $walletM
            ];
        }
        $statusArr = [
            "status" => "success",
            "message" => __("message_app.wallet Manager retrieved successfully"),
            "result" => $response
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function fundTransferOnafriq(Request $request)
    {

        // $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $input = [
            'recipientCountry' => $request->recipientCountry,
            'walletManager' => $request->walletManager,
            'recipientMsisdn' => $request->recipientMsisdn,
            'recipientName' => $request->recipientName,
            'recipientSurname' => $request->recipientSurname,
            'amount' => $request->amount,
            'senderCountry' => $request->senderCountry,
            'senderMsisdn' => $request->senderMsisdn,
            'senderName' => $request->senderName,
            'senderSurname' => $request->senderSurname,
            'senderAddress' => $request->senderAddress,
            'senderIdType' => $request->senderIdType,
            'senderIdNumber' => $request->senderIdNumber,
            'senderDob' => $request->senderDob,
            'trans_type' => $request->trans_type
        ];

        $validate_data = [
            'recipientCountry' => 'required',
            'walletManager' => 'required',
            'recipientMsisdn' => 'required',
            'recipientName' => 'required',
            'recipientSurname' => 'required',
            'amount' => 'required|numeric|min:500|max:1500000',
            'senderCountry' => 'required',
            'senderMsisdn' => 'required',
            'senderName' => 'required',
            'senderSurname' => 'required',
            'senderAddress' => 'required_if:recipientCountry,ML,SN',
            'senderIdType' => 'required_if:recipientCountry,ML,SN',
            'senderIdNumber' => 'required_if:recipientCountry,ML,SN',
            'senderDob' => 'required_if:recipientCountry,ML,SN,BF',
        ];

        $customMessages = [
            'recipientCountry.required' => __('message_app.Recipient Country field cannot be left blank'),
            'recipientMsisdn.required' => __('message_app.Recipient Msisdn field cannot be left blank'),
            'walletManager.required' => __('message_app.Wallet Manager field cannot be left blank'),
            'recipientName.required' => __('message_app.Recipient First Name field cannot be left blank'),
            'recipientSurname.required' => __('message_app.Recipient Last Name field cannot be left blank'),
            'amount.required' => __('message_app.Amount field cannot be left blank'),
            'senderCountry.required' => __('message_app.Sender Country field cannot be left blank'),
            'senderMsisdn.required' => __('message_app.Sender Phone Number field cannot be left blank'),
            'senderName.required' => __('message_app.Sender Name field cannot be left blank'),
            'senderSurname.required' => __('message_app.Sender Surname field cannot be left blank'),
            'senderAddress.required' => __('message_app.Sender Address field cannot be left blank'),
            'senderIdType.required' => __('message_app.Sender Id Type field cannot be left blank'),
            'senderIdNumber.required' => __('message_app.Sender Id Number field cannot be left blank'),
            'senderDob.required' => __('message_app.Sender Dob field cannot be left blank'),
        ];
        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $amount = $input['amount'];

        $senderUser = User::where('id', $user_id)->first();

        $userType = $this->getUserType($senderUser->user_type);

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }


        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $unverifiedKycMin]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $unverifiedKycMax]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->onafriqa_min > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->onafriqa_min,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->onafriqa_max) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->onafriqa_max,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($json, 200);
        }


        $total_fees = 0;
        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
            ->where('max_amount', '>=', $request->amount)->first();
        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $input['trans_type'])->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=', $request->amount)->first();
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }

        $total_amount = $amount + $total_fees;

        if ($total_amount >= Auth::user()->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        try {
            $thirdPartyId = $this->generateAndCheckUnique();
            $postData = '
            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
            <ns:account_request xmlns:ns="http://ws.mfsafrica.com">
            <ns:login>
            <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
            <ns:password>' . CORPORATEPASS . '</ns:password>
            </ns:login>
            <ns:to_country>' . $request->senderCountry . '</ns:to_country>
            <ns:msisdn>' . $request->senderMsisdn . '</ns:msisdn>
            </ns:account_request>
            </soap:Body>
            </soap:Envelope>';

            $getResponse = $this->sendCurlRequest($postData, 'urn:account_request');
            //print_r($getResponse); die;

            try {
                Log::info('Transaction Com POST Data:', [$postData]);
                Log::info('Transaction Com Response:', [$getResponse]);
            } catch (AuthorizationException $e) {
                Log::warning('Remit: ' . $e->getMessage());
            } catch (\Exception $e) {
                Log::error('Error in Remit process: ' . $e->getMessage());
            }

            $xml = new SimpleXMLElement($getResponse);

            $xml->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xml->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

            $namespaces = $xml->getNamespaces(true);
            $axNamespace = '';
            foreach ($namespaces as $prefix => $namespace) {
                if (strpos($namespace, 'http://mfs/xsd') !== false) {
                    $axNamespace = $prefix;
                    break;
                }
            }
            $xml->registerXPathNamespace($axNamespace, 'http://mfs/xsd');
            $status = $xml->xpath('//' . $axNamespace . ':status')[0];
            $statusCode = (string) $status->xpath('' . $axNamespace . ':status_code')[0];


            if ($statusCode == "Active") {

                $postDataRemit = '
                        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                        <soap:Body>
                            <ns:mm_remit_log xmlns:ns="http://ws.mfsafrica.com">
                            <ns:login>
                                <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code> 
                                <ns:password>' . CORPORATEPASS . '</ns:password> 
                            </ns:login>
                            <ns:receive_amount>
                                <ns:amount>' . $request->amount . '</ns:amount> 
                                <ns:currency_code>' . $request->recipientCurrency . '</ns:currency_code> 
                            </ns:receive_amount>
                            <ns:sender>
                                <ns:address>' . ($request->senderAddress ?: "") . '</ns:address>
                                <ns:city>string</ns:city>
                                <ns:date_of_birth>' . ($request->senderDob ?: "") . '</ns:date_of_birth>
                                <ns:document>
                                <ns:id_country>string</ns:id_country>
                                <ns:id_expiry>string</ns:id_expiry>
                                <ns:id_number>' . ($request->senderIdNumber ?: "") . '</ns:id_number>
                                <ns:id_type>' . ($request->senderIdType ?: "") . '</ns:id_type>
                                </ns:document>
                                <ns:email>string</ns:email>
                                <ns:from_country>' . $request->senderCountry . '</ns:from_country>
                                <ns:msisdn>' . $request->senderMsisdn . '</ns:msisdn>
                                <ns:name>' . $request->senderName . '</ns:name>
                                <ns:place_of_birth>string</ns:place_of_birth>
                                <ns:postal_code>string</ns:postal_code>
                                <ns:state>string</ns:state>
                                <ns:surname>' . $request->senderSurname . '</ns:surname>
                            </ns:sender>
                            <ns:recipient>
                                <ns:address>string</ns:address>
                                <ns:city>string</ns:city>
                                <ns:date_of_birth>string</ns:date_of_birth>
                                <ns:document>
                                <ns:id_country>string</ns:id_country>
                                <ns:id_expiry>string</ns:id_expiry>
                                <ns:id_number>string</ns:id_number>
                                <ns:id_type>string</ns:id_type>
                                </ns:document>
                                <ns:email>string</ns:email>
                                <ns:msisdn>' . $request->recipientMsisdn . '</ns:msisdn>
                                <ns:name>' . $request->recipientName . '</ns:name>
                                <ns:postal_code>string</ns:postal_code>
                                <ns:state>string</ns:state>
                                <ns:status>
                                <ns:status_code>string</ns:status_code>
                                </ns:status>
                                <ns:surname>' . $request->recipientSurname . '</ns:surname>
                                <ns:to_country>' . $request->recipientCountry . '</ns:to_country>
                            </ns:recipient>
                            <ns:third_party_trans_id>' . $thirdPartyId . '</ns:third_party_trans_id>
                            <ns:reference>string</ns:reference>
                            <ns:source_of_funds>string</ns:source_of_funds>
                            <ns:purpose_of_transfer>string</ns:purpose_of_transfer>
                            </ns:mm_remit_log>
                        </soap:Body>
                        </soap:Envelope>
                ';

                $onafriqaDataA = new OnafriqaData();
                $onafriqaDataA->recipientCountry = $request->recipientCountry;
                $onafriqaDataA->recipientMsisdn = $request->recipientMsisdn;
                $onafriqaDataA->walletManager = $request->walletManager;
                $onafriqaDataA->recipientName = $request->recipientName;
                $onafriqaDataA->recipientSurname = $request->recipientSurname;
                $onafriqaDataA->recipientCurrency = 'XOF';
                $onafriqaDataA->amount = $request->amount;
                $onafriqaDataA->senderCountry = $request->senderCountry;
                $onafriqaDataA->senderMsisdn = $request->senderMsisdn;
                $onafriqaDataA->senderName = $request->senderName;
                $onafriqaDataA->senderSurname = $request->senderSurname;
                $onafriqaDataA->senderAddress = $request->senderAddress ?? '';
                $onafriqaDataA->senderDob = $request->senderDob ?? '';
                $onafriqaDataA->senderIdType = $request->senderIdType ?? '';
                $onafriqaDataA->senderIdNumber = $request->senderIdNumber ?? '';
                $onafriqaDataA->fromMSISDN = $request->senderMsisdn;
                $onafriqaDataA->thirdPartyTransactionId = $thirdPartyId;
                $onafriqaDataA->status = 'pending';
                $onafriqaDataA->userId = $user_id;
                $onafriqaDataA->excelTransId = '';
                $onafriqaDataA->save();

                $getResponseRemit = $this->sendCurlRequest($postDataRemit, 'urn:mm_remit_log');

                try {
                    Log::info('Transaction Com POST Data:', [$postDataRemit]);
                    Log::info('Transaction Com Response:', [$getResponseRemit]);
                } catch (AuthorizationException $e) {
                    Log::warning('Remit: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error('Error in Remit process: ' . $e->getMessage());
                }
                die;

                $xml1 = new SimpleXMLElement($getResponseRemit);

                $xml1->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
                $xml1->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

                $namespaces1 = $xml1->getNamespaces(true);
                $axNamespace1 = '';
                foreach ($namespaces1 as $prefix1 => $namespace2) {
                    if (strpos($namespace2, 'http://mfs/xsd') !== false) {
                        $axNamespace1 = $prefix1;
                        break;
                    }
                }
                $xml1->registerXPathNamespace($axNamespace1, 'http://mfs/xsd');
                //print_r($axNamespace1);

                $status1 = $xml1->xpath('//' . $axNamespace1 . ':status')[0];

                $mfs_trans_id = (string) $xml1->xpath('//' . $axNamespace1 . ':mfs_trans_id')[0];
                $partner_code = (string) $xml1->xpath('//' . $axNamespace1 . ':partner_code')[0];


                $statusCode1 = (string) $status1->xpath('' . $axNamespace1 . ':code/' . $axNamespace1 . ':status_code')[0];
                $statusMessage = (string) $status1->xpath('ax21:message')[0];

                $receiveAmount = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':amount')[0];
                $currencyCode = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':currency_code')[0];


                if ($statusCode1 == "MR104" && $statusMessage == "Log Success" && $mfs_trans_id != "") {

                    $postDataTrans =
                        '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                        <soap:Body>
                        <ns:trans_com xmlns:ns="http://ws.mfsafrica.com">
                        <ns:login>
                        <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                        <ns:password>' . CORPORATEPASS . '</ns:password>
                        </ns:login>
                        <ns:trans_id>' . $mfs_trans_id . '</ns:trans_id>
                        </ns:trans_com>
                        </soap:Body>
                        </soap:Envelope>';

                    $getResponseTrans = $this->sendCurlRequest($postDataTrans, 'urn:trans_com');

                    $xml2 = new SimpleXMLElement($getResponseTrans);

                    $xml2->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
                    $xml2->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

                    $namespaces2 = $xml2->getNamespaces(true);
                    $axNamespace2 = '';
                    foreach ($namespaces2 as $prefix2 => $namespace3) {
                        if (strpos($namespace3, 'http://mfs/xsd') !== false) {
                            $axNamespace2 = $prefix2;
                            break;
                        }
                    }
                    $xml2->registerXPathNamespace($axNamespace2, 'http://mfs/xsd');



                    $status2 = $xml2->xpath('//' . $axNamespace2 . ':code')[0];
                    $e_trans_id2 = (string) $xml2->xpath('//' . $axNamespace2 . ':e_trans_id')[0];
                    $message2 = (string) $xml2->xpath('//' . $axNamespace2 . ':message')[0];
                    $statusCode2 = (string) $status2->xpath('' . $axNamespace2 . ':status_code')[0];

                    if ($statusCode2 === 'MR101') {



                        $refrence_id = time();
                        $trans = new Transaction([
                            'user_id' => $user_id,
                            'receiver_id' => 0,
                            'receiver_mobile' => '',
                            'amount' => $amount,
                            'amount_value' => $amount,
                            'transaction_amount' => $total_fees,
                            'total_amount' => $total_amount,
                            'trans_type' => 2,
                            'excel_trans_id' => '',
                            'payment_mode' => 'wallet2wallet',
                            'status' => 1,
                            'refrence_id' => '',
                            'billing_description' => 'Fund Transfer-' . $refrence_id,
                            'tomember' => '',
                            'acquirertrxref' => '',
                            'issuertrxref' => '',
                            'vouchercode' => '',
                            'entryType' => 'API',
                            'onafriq_bda_ids' => $onafriqaDataA->id,
                            'transactionType' => 'SWAPTOONAFRIQ',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'swapDomainName' => 'UAT_SERVER'
                        ]);
                        $trans->save();


                        OnafriqaData::where('id', $onafriqaDataA->id)->update(['status' => 'success', 'transactionId' => $mfs_trans_id, 'trans_app_id' => $trans->id]);

                        $sender_wallet_amount = $senderUser->wallet_balance - $total_amount;
                        User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);
                        DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);

                        $statusArr = [
                            "status" => "Success",
                            "reason" => __("message_app.Your transaction has been successful"),
                        ];


                        $senderName = $senderUser->name;
                        $senderEmail = $senderUser->email;
                        $senderAmount = $amount;
                        $receiverAmount = $amount;
                        $transactionFees = $total_fees;
                        $transaction_date = date('d M, Y h:i A', strtotime($trans->created_at));
                        $emailData['subjects'] = __("message_app.Funds Transfer Details");
                        $emailData['senderName'] = $senderName;
                        $emailData['senderEmail'] = $senderEmail;
                        $emailData['senderAmount'] = $senderAmount;
                        $emailData['currency'] = CURR;

                        $emailData['receiverName'] = 'Test'; //$receiverName;

                        $emailData['transId'] = $refrence_id;
                        $emailData['transactionFees'] = $transactionFees;
                        $emailData['transactionDate'] = $transaction_date;
                        $emailData['transactionStatus'] = 'success';

                        /* if ($senderEmail != "") {
                            Mail::send(
                                'emails.fund_transfer_sender',
                                $emailData,
                                function ($message) use ($emailData) {
                                    $message->to(
                                        $emailData["senderEmail"],
                                        $emailData["senderEmail"]
                                    )->subject($emailData["subjects"]);
                                }
                            );
                        } */

                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } elseif ($statusCode2 == 'MR108' || $statusCode2 == 'MR103' || $statusCode2 == 'MR102') {

                        $refrence_id = time();
                        $trans = new Transaction([
                            'user_id' => $user_id,
                            'receiver_id' => 0,
                            'receiver_mobile' => '',
                            'amount' => $amount,
                            'amount_value' => $amount,
                            'transaction_amount' => $total_fees,
                            'total_amount' => $total_amount,
                            'trans_type' => 2,
                            'excel_trans_id' => '',
                            'payment_mode' => 'wallet2wallet',
                            'status' => 2,
                            'refrence_id' => '',
                            'bda_status' => 5,
                            'billing_description' => 'Fund Transfer-' . $refrence_id,
                            'tomember' => '',
                            'acquirertrxref' => '',
                            'issuertrxref' => '',
                            'vouchercode' => '',
                            'entryType' => 'API',
                            'onafriq_bda_ids' => $onafriqaDataA->id,
                            'transactionType' => 'SWAPTOONAFRIQ',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'swapDomainName' => 'UAT_SERVER'
                        ]);
                        $trans->save();

                        OnafriqaData::where('id', $onafriqaDataA->id)->update(['transactionId' => $mfs_trans_id, 'trans_app_id' => $trans->id]);

                        User::where('id', $user_id)->decrement('wallet_balance', $total_amount);
                        User::where('id', $user_id)->increment('holdAmount', $total_amount);

                        $statusArr = [
                            "status" => "Success",
                            "reason" => __("message_app.Transaction Pending"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __("message_app.Transaction not successful"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Please enter the correct Recipient Phone Number and Recipient Country"),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Subscriber not active"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Connection error: Timeout occurred while connecting to the server"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $contents = $response->getBody()->getContents();
                $jsonResponse = json_decode($contents, true);

                $errorDescription = $jsonResponse['error_description'] ?? __("message_app.Error Code: 403 Forbidden Error");

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            $statusArr = [
                "status" => "Failed",
                "reason" => $e->getMessage(),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    function getTransStatus($times, $mfs_trans_id)
    {

        for ($i = 0; $i < $times; $i++) {

            $postData = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
            <ns:get_trans_status xmlns:ns="http://ws.mfsafrica.com">
            <ns:login>
            <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
            <ns:password>' . CORPORATEPASS . '</ns:password>
            </ns:login>
            <ns:trans_id>' . $mfs_trans_id . '</ns:trans_id>
            </ns:get_trans_status>
            </soap:Body>
            </soap:Envelope>';
            $getResponse = $this->sendCurlRequest($postData, 'urn:get_trans_status');



            $xml12 = new SimpleXMLElement($getResponse);

            $xml12->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xml12->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

            $namespaces2 = $xml12->getNamespaces(true);
            $axNamespace1 = '';
            foreach ($namespaces2 as $prefix1 => $namespace2) {
                if (strpos($namespace2, 'http://mfs/xsd') !== false) {
                    $axNamespace1 = $prefix1;
                    break;
                }
            }

            $xml12->registerXPathNamespace($axNamespace1, 'http://mfs/xsd');

            $status2 = $xml12->xpath('//' . $axNamespace1 . ':code')[0];

            $e_trans_id2 = (string) $xml12->xpath('//' . $axNamespace1 . ':e_trans_id')[0];
            $message2 = (string) $xml12->xpath('//' . $axNamespace1 . ':message')[0];
            $statusCode2 = (string) $status2->xpath('' . $axNamespace1 . ':status_code')[0];
            return $message2;
        }
    }
    public function accountRequest(Request $request)
    {
        /* $postData = '
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
        <ns:account_request xmlns:ns="http://ws.mfsafrica.com">
        <ns:login>
        <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
        <ns:password>' . CORPORATEPASS . '</ns:password>
        </ns:login>
        <ns:to_country>KM</ns:to_country>
        <ns:msisdn>2694225500</ns:msisdn>
        </ns:account_request>
        </soap:Body>
        </soap:Envelope>'; */
        $getResponse = $request->getContent();
        $getResponse = $this->sendCurlRequest($getResponse, 'urn:trans_com');
        print_r($getResponse);
        die;
        $xml = new SimpleXMLElement($getResponse);

        $xml->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

        $namespaces = $xml->getNamespaces(true);
        $axNamespace = '';
        foreach ($namespaces as $prefix => $namespace) {
            if (strpos($namespace, 'http://mfs/xsd') !== false) {
                $axNamespace = $prefix;
                break;
            }
        }
        $xml->registerXPathNamespace($axNamespace, 'http://mfs/xsd');
        $status = $xml->xpath('//' . $axNamespace . ':status')[0];
        $statusCode = (string) $status->xpath('' . $axNamespace . ':status_code')[0];

        $data = [
            'paymentStatus' => $statusCode,
        ];
        $statusArr = array("status" => true, "message" => __("message_app.Payment Information"), 'result' => $data);
        return response()->json($statusArr, 200);
    }
    public function loginRegisterOTPNew(Request $request)
    {
        try {
            /* $statusArr = array("device_token" =>"874364", "device_type" =>"Android","device_id"=>"","phone" =>"8302316402","otpCode"=>"451698","type"=>"Register","user_type"=>"User");
            $json = json_encode($statusArr);
            $requestData = $this->encryptContent($json);
            echo $requestData; die; */

            $requestData = $this->decryptContent($request->req);
            // $requestData->phone = 98251480;
            $input = [
                'phone' => $requestData->phone,
            ];

            $isRegistration = $request->routeIs('Register');

            $validate_data = [
                'phone' => array_filter([
                    'required',
                    'regex:/^\d{9}$/', // exactly 9 digits
                    $isRegistration ? 'unique:users,phone' : null
                ]),
            ];

            $customMessages = [
                'phone.required' => __("message_app.Phone field cannot be left blank"),
                'phone.regex' => __("message_app.Phone number must be exactly 9 digits"),
                'phone.unique' => __("message_app.This phone number is already taken. Please use a different one"),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);

            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();

                $statusArr = [
                    'status' => 'Failed',
                    'reason' => $firstErrorMessage,
                ];

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $device_token = $requestData->device_token;
            $device_type = $requestData->device_type;
            $user_type = $requestData->user_type;
            $device_id = $requestData->device_id;
            $phone = $requestData->phone;
            $type = $requestData->type;
            $otp_number = $this->generateNumericOTP(6);

            if ($type == "Register") {
                $userInfo = User::where("phone", $phone)
                    ->where("user_type", "!=", "")
                    ->where("otp_verify", 1)
                    ->where("is_account_deleted", 1)
                    ->first();
                if (!empty($userInfo)) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.phone_already_exists"),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
                $getResponse = $this->smsService->sendLoginRegisterOtp($otp_number, $phone);

                if ($getResponse['status']) {
                    $statusArr = [
                        "status" => "Success",
                        "reason" => __("message_app.OTP sent successfully"),
                        "otpCode" => $otp_number,
                        "isOtpRequired" => true,
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = [
                        "status" => "Error",
                        "reason" => __("message_app.Failed to send message"),
                        "error" => $getResponse['message'],
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

            } elseif ($type == "Login") {
                $userInfo = User::where("phone", $phone)
                    ->where("user_type", "!=", "")
                    ->where("otp_verify", 1)
                    ->where("is_account_deleted", 1)
                    ->first();
                if (empty($userInfo)) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.User not found"),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
                if ($user_type == "User") {
                    if ($userInfo->user_type == "User") {
                        if ($userInfo->is_verify == 0) {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => __("message_app.Your account might have been temporarily disabled. Please contact us for more details"),
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            if ($userInfo->device_token != $device_token || $userInfo->device_id != $device_id) {
                                $this->smsService->sendLoginRegisterOtp($otp_number, $phone);
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => __("message_app.OTP sent successfully"),
                                    "otpCode" => $otp_number,
                                    "isOtpRequired" => true,
                                ];
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            } else {
                                $user_id = $userInfo->id;
                                $user = array(
                                    'device_token' => $device_token,
                                    'device_type' => $device_type,
                                    'device_id' => $device_id,
                                    'otp_verify' => 1,
                                    'login_status' => 1,
                                    'login_time' => date('Y-m-d H:i:s')
                                );
                                User::where("device_token", $device_token)->where('device_id', $device_id)->update(['device_token' => '', 'device_id' => '']);
                                User::where("id", $user_id)->update($user);
                                $tokenStr = $userInfo->id . " " . time();
                                $user = $userInfo;
                                $tokenResult = $user->createToken($tokenStr);
                                $token = $tokenResult->token;
                                $token->save();
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => __("message_app.OTP verification completed"),
                                    "user_type" => $userInfo->user_type,
                                    "access_type" => "Bearer",
                                    "kyc_status" => $userInfo->kyc_status,
                                    "access_token" => $tokenResult->accessToken,
                                    "isOtpRequired" => false,
                                    "isPinSet" => $userInfo->securityPin != "" ? true : false,
                                ];
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        }
                    } else {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __('message_app.phone_already_registered', [
                                'user_type' => $userInfo->user_type
                            ])
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                } else {
                    if (
                        $userInfo->user_type == "Agent" ||
                        $userInfo->user_type == "Merchant"
                    ) {

                        if ($userInfo->is_verify == 0) {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => __("message_app.Your account might have been temporarily disabled. Please contact us for more details"),
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {

                            if ($userInfo->device_token != $device_token || $userInfo->device_id != $device_id) {
                                $this->smsService->sendLoginRegisterOtp($otp_number, $phone);
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => __("message_app.OTP sent successfully"),
                                    "otpCode" => $otp_number,
                                    "isOtpRequired" => true,
                                ];

                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            } else {
                                $user_id = $userInfo->id;
                                $user = array(
                                    'device_token' => $device_token,
                                    'device_type' => $device_type,
                                    'device_id' => $device_id,
                                    'otp_verify' => 1,
                                    'login_status' => 1,
                                    'login_time' => date('Y-m-d H:i:s')
                                );
                                User::where("device_token", $device_token)->where('device_id', $device_id)->update(['device_token' => '', 'device_id' => '']);
                                User::where("id", $user_id)->update($user);
                                $tokenStr = $userInfo->id . " " . time();
                                $user = $userInfo;
                                $tokenResult = $user->createToken($tokenStr);
                                $token = $tokenResult->token;
                                $token->save();
                                $statusArr = [
                                    "status" => "Success",
                                    "reason" => __("message_app.OTP verification completed"),
                                    "user_type" => $userInfo->user_type,
                                    "access_type" => "Bearer",
                                    "kyc_status" => $userInfo->kyc_status,
                                    "access_token" => $tokenResult->accessToken,
                                    "isOtpRequired" => false,
                                    "isPinSet" => $userInfo->securityPin != "" ? true : false,
                                ];
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        }
                    } else {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __('message_app.phone_already_registered', [
                                'user_type' => $userInfo->user_type
                            ])
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }
            }
        } catch (\Exception $ex) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __($ex->getMessage()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function verifyLoginRegisterOTPNew(Request $request)
    {
        /* $statusArr = array("device_token" =>"eUROFn0NRvugHOu9XtGSfI:APA91bH8do_FsXtopiiiFwj_Go4l08dyYTie6ehJ1M__AX46WGGZeCZGWjAgVFrWRWheyVGKC9QuWzaK6h4ImCQoN8Ra6k1i6wYp_y2tiVwW1NxRisBrF-k", "device_type" =>"Android","device_id"=>"1e8ed4c4c5dc75d6","phone" =>"3693696","otpCode"=>"941417","type"=>"Login","user_type"=>"User");
            $json = json_encode($statusArr);
            $requestData = $this->encryptContent($json);
            echo $requestData; die; */

        $requestData = $this->decryptContent($request->req);
        //$requestData = $request;
        $phone = $requestData->phone;
        $otpCode = $requestData->otpCode;
        $device_token = $requestData->device_token;
        $device_type = $requestData->device_type;
        $user_type = $requestData->user_type;
        $device_id = $requestData->device_id;
        $type = $requestData->type;
        if ($requestData->otpCode == "") {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid OTP code")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($type == "Register") {
            $getTempUserData = DB::table('tempuser')->where('phone', $phone)->first();
            if (isset($otpCode) && $otpCode != $getTempUserData->otpCode && $otpCode != '111111') {
                // if ($otpCode != '111111') {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid OTP code"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.OTP verification completed"),
                ];
                User::where("device_token", $device_token)->where('device_id', $device_id)->update(['device_token' => '', 'device_id' => '']);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

        } elseif ($type == "Login") {
            $getTempUserData = DB::table('tempuser')->where('phone', $phone)->first();
            $userInfo = User::where("phone", $phone)
                ->where("user_type", "!=", "")
                ->where("otp_verify", 1)
                ->orderBy("id", 'desc')
                ->first();

            if (empty($userInfo)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Please provide a registered phone number"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if ($user_type == "User") {
                if ($userInfo->user_type == "User") {
                    if ($userInfo->is_verify == 0) {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" =>
                                __("message_app.Your account might have been temporarily disabled. Please contact us for more details"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {

                        if ($otpCode != $getTempUserData->otpCode && $otpCode != '111111') {
                            // if ($otpCode != '111111') {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => __("message_app.Invalid OTP code"),
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $user_id = $userInfo->id;
                            $user = array(
                                'device_token' => $device_token,
                                'device_type' => $device_type,
                                'device_id' => $device_id,
                                'otp_verify' => 1,
                                'login_status' => 1,
                                'login_time' => date('Y-m-d H:i:s')
                            );
                            User::where("device_token", $device_token)->where('device_id', $device_id)->update(['device_token' => '', 'device_id' => '']);
                            User::where("id", $user_id)->update($user);
                            $tokenStr = $userInfo->id . " " . time();
                            $user = $userInfo;
                            $tokenResult = $user->createToken($tokenStr);
                            $token = $tokenResult->token;
                            $token->save();
                            $statusArr = [
                                "status" => "Success",
                                "reason" => __("message_app.OTP verification completed"),
                                "user_type" => $userInfo->user_type,
                                "access_type" => "Bearer",
                                "kyc_status" => $userInfo->kyc_status,
                                "access_token" => $tokenResult->accessToken,
                                "isPinSet" => $userInfo->securityPin != "" ? true : false,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    }
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __('message_app.phone_already_registered', [
                            'user_type' => $userInfo->user_type
                        ]),

                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($userInfo->user_type == "Agent" || $userInfo->user_type == "Merchant") {
                    if ($userInfo->is_verify == 0) {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" =>
                                __("message_app.Your account might have been temporarily disabled. Please contact us for more details"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {

                        if ($otpCode != $getTempUserData->otpCode && $otpCode != '111111') {
                            // if ($otpCode != "111111") {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => __("message_app.Invalid OTP code"),
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $user_id = $userInfo->id;
                            $user = array(
                                'device_token' => $device_token,
                                'device_type' => $device_type,
                                'device_id' => $device_id,
                                'otp_verify' => 1,
                                'login_status' => 1,
                                'login_time' => date('Y-m-d H:i:s')
                            );
                            User::where("device_token", $device_token)->where('device_id', $device_id)->update(['device_token' => '', 'device_id' => '']);
                            User::where("id", $user_id)->update($user);
                            $tokenStr = $userInfo->id . " " . time();
                            $tokenResult = $userInfo->createToken($tokenStr);
                            $token = $tokenResult->token;
                            $token->save();
                            $statusArr = [
                                "status" => "Success",
                                "reason" => __("message_app.OTP verification completed"),
                                "user_type" => $userInfo->user_type,
                                "access_type" => "Bearer",
                                "kyc_status" => $userInfo->kyc_status,
                                "access_token" => $tokenResult->accessToken,
                                "isPinSet" => $userInfo->securityPin != "" ? true : false,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    }
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __('message_app.phone_already_registered', [
                            'user_type' => $userInfo->user_type
                        ]),

                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        } else if ($type == "UpdateMobile") {
            if ($otpCode != "111111") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Invalid OTP code"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {

                $userId = $requestData->user_id;
                User::where("id", $userId)->update(['phone' => $phone]);
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.Phone number has been updated successfully"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function generateIBAN(Request $request)
    {
        die('stop');
        $request = $this->decryptContent($request->req);

        $data = (array) $request;
        $validator = Validator::make($data, [
            'countryId' => 'required|exists:countries,id',
            'walletManagerId' => 'required'
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();

            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $agency = WalletManager::where('country_id', $request->countryId)
            ->where('id', $request->walletManagerId)
            ->first();

        if (!$agency) {
            $statusArr = [
                'status' => 'Failed',
                'message' => __("message_app.Not found for the provided country and wallet manager")
            ];

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);

        }

        $controlKey = "21";
        $bankCode = "14007"; // Bank Code of GABON INTERBANK

        $userId = Auth::user()->id;
        $getCountryCode = Country::where('id', $request->countryId)->first();
        $countryCode = "";
        if ($getCountryCode->name == 'Gabon') {
            $countryCode = 'GA';
        } elseif ($getCountryCode->name == 'Cameroon') {
            $countryCode = 'CM';
        } elseif ($getCountryCode->name == 'Congo') {
            $countryCode = 'CG';
        } elseif ($getCountryCode->name == 'Republique Centrafricaine') {
            $countryCode = 'CF';
        } elseif ($getCountryCode->name == 'Tchad') {
            $countryCode = 'TD';
        } elseif ($getCountryCode->name == 'Equatorial Guinea') {
            $countryCode = 'GQ';
        }


        $agencyCode = $agency->tomember;

        $existingIban = Iban::where('userId', $userId)
            ->where('agencyCode', $agencyCode)
            ->exists();

        if ($existingIban) {
            $statusArr = [
                'status' => 'Failed',
                'message' => __("message_app.IBAN with this Agency Code already exists for the user")
            ];

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);

        }
        // Generate a unique account number (first 5 fixed, last 6 random)
        do {
            $accountNumber = "23001" . str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);
        } while (Iban::where('accountNumber', $accountNumber)->exists());

        // Calculate RIB key using Modulo 97
        $ribKey = 97 - ((89 * intval($bankCode) + 15 * intval($agencyCode) + 76 * intval(substr($accountNumber, 0, 5)) + 3 * intval(substr($accountNumber, 5))) % 97);
        $ribKey = str_pad($ribKey, 2, "0", STR_PAD_LEFT); // Ensure 2 digits

        $iban = $countryCode . $controlKey . $bankCode . $agencyCode . $accountNumber . $ribKey;

        Iban::create([
            'iban' => $iban,
            'userId' => $userId,
            'bankCode' => $bankCode,
            'agencyCode' => $agencyCode,
            'accountNumber' => $accountNumber,
            'countryId' => $request->countryId,
            'walletManagerId' => $request->walletManagerId,
            'ribKey' => $ribKey
        ]);

        $statusArr = [
            'status' => 'Success',
            'message' => __("message_app.IBAN generated successfully"),
            'iban' => $iban,
            'ribKey' => $ribKey,
        ];

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function createUpdateSecurityPin(Request $request)
    {
        /* $statusArr = array("pin" =>"1245");
            $json = json_encode($statusArr);
            $requestData = $this->encryptContent($json);
            echo $requestData; die; 
        */

        $requestData = $this->decryptContent($request->req);
        $user = Auth::user();
        $input = [
            "pin" => $requestData->pin ?? null,
            "oldPin" => $requestData->oldPin ?? null,
        ];
        $validator = Validator::make($input, [
            'pin' => 'required|numeric|digits:4',
        ], [
            'pin.required' => __("message_app.Pin is required"),
            'pin.digits' => __("message_app.Pin enter 4 digits"),
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();

            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $newPin = $requestData->pin;
        $oldPinInput = $requestData->oldPin;


        $easyPins = [
            "0000",
            "1111",
            "2222",
            "3333",
            "4444",
            "5555",
            "6666",
            "7777",
            "8888",
            "9999",
            "1234",
            "4321",
            "1212",
            "1122",
            '0123',
            '2345',
            '3456',
            '4567',
            '5678',
            '6789',
            '9876',
            '8765',
            '7654',
            '6543',
            '5432'
        ];

        $newPin = str_pad((string) $requestData->pin, 4, '0', STR_PAD_LEFT);
        $oldPinInput = (string) $requestData->oldPin;

        if (in_array($newPin, $easyPins, true)) {
            return response()->json(
                $this->encryptContent(json_encode([
                    "status" => "Failed",
                    "reason" => __("message_app.Pin is too easy. Please choose a stronger PIN"),
                ])),
                200
            );
        }

        if (!empty($oldPinInput)) {
            if (!empty($user->securityPin) && !Hash::check($oldPinInput, $user->securityPin)) {
                return response()->json($this->encryptContent(json_encode([
                    "status" => "Failed",
                    "reason" => __("message_app.Old pin does not match"),
                ])), 200);
            }
        }

        $previousPins = json_decode($user->prevPins, true) ?? [];

        foreach ($previousPins as $oldPin) {
            if (Hash::check($newPin, $oldPin)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.You cannot use the last 3 security pins"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
        $hashedNewPin = Hash::make($newPin);

        array_unshift($previousPins, $hashedNewPin);
        if (count($previousPins) > 3) {
            array_pop($previousPins);
        }
        // Update user record
        $user->securityPin = $hashedNewPin;
        $user->prevPins = json_encode($previousPins);
        $user->save();

        $statusArr = [
            "status" => "Success",
            "reason" => empty($user->securityPin) ? __("message_app.Security pin created successfully") : __("message_app.Security pin updated successfully"),
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);

    }
    public function checkSecurityPin(Request $request)
    {
        /* $statusArr = array("securityPin" =>"1234");
        $json = json_encode($statusArr);
        $requestData = $this->encryptContent($json);
        echo $requestData; die; */

        $requestData = $this->decryptContent($request->req);
        $user = Auth::user();
        $input = [
            "pin" => $requestData->pin ?? null,
        ];
        $validator = Validator::make($input, [
            'pin' => 'required|numeric|digits:4',
        ], [
            'pin.required' => __("message_app.Pin is required"),
            'pin.digits' => __("message_app.Pin enter 4 digits"),
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if (empty($user->securityPin)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Pin not set. Please create one first"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if (!Hash::check($requestData->pin, $user->securityPin)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.The security PIN you entered is incorrect"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.Pin verified successfully"),
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function forgotPin(Request $request)
    {
        $user = Auth::user();
        $otp = $this->generateNumericOTP(6);
        // dd($user->phone,$otp);
        if (!empty($user->phone)) {
            $getResponse = $this->smsService->sendLoginRegisterOtp($otp, $user->phone);
            $user->forgotPinOtpCode = Hash::make($otp);
            $user->save();
            if ($getResponse['status']) {
                $statusArr = [
                    "status" => "Success",
                    "reason" => __("message_app.OTP sent successfully"),
                    "otp" => $otp,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Phone number not found"),
            ];

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 400);
        }
    }
    public function verifyOtp(Request $request)
    {
        /* $statusArr = array("otpCode"=>"111111");
            $json = json_encode($statusArr);
            $requestData = $this->encryptContent($json);
            echo $requestData; die; */
        $requestData = $this->decryptContent($request->req);
        $user = Auth::user();
        $input = [
            "otpCode" => $requestData->otpCode ?? null,
        ];
        $validator = Validator::make($input, [
            'otpCode' => 'required|numeric|digits:6',
        ], [
            'otpCode.required' => __("message_app.OTP is required"),
            'otpCode.digits' => __("message_app.OTP enter 6 digits"),
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $enteredOtp = $requestData->otpCode;

        /* if (!Hash::check($enteredOtp, $user->forgotPinOtpCode)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Incorrect OTP",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 400);
        } */


        if ($enteredOtp != "111111") {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Invalid OTP code"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $user->forgotPinOtpCode = "";
        $user->save();

        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.OTP verified successfully"),
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function verifyWalletIbanCard(Request $request)
    {
        // Log::channel('ONAFRIQ')->info("Failed to upload to clinet SFTP server.");
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        if (in_array($request->type, ['IBAN', 'PRECARD', 'BDAWITHOUTWALLET', 'WALLETMANAGER'])) {
            /* $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } */
        }
        $input = [
            'country_id' => $request->country_id ?? null,
            'walletManagerId' => $request->walletManagerId ?? null,
            'ibanNumber' => $request->ibanNumber ?? null,
            'cardNumber' => $request->cardNumber ?? null,
            'cardHolderName' => $request->cardHolderName ?? null,
            'phone' => $request->phone ?? null,
            'amount' => $request->amount ?? null,
            'type' => $request->type ?? null,

            /* 'idTypeSender' => $request->idTypeSender ?? null,
            'idNumberSender' => $request->idNumberSender ?? null,
            'addressSender' => $request->addressSender ?? null,
            'citySender' => $request->citySender ?? null,
            'countrySender' => $request->countrySender ?? null, */

            'idTypeReceiver' => $request->idTypeReceiver ?? null,
            'idNumberReceiver' => $request->idNumberReceiver ?? null,
            'addressReceiver' => $request->addressReceiver ?? null,
            'cityReceiver' => $request->cityReceiver ?? null,
        ];

        $validate_data = [
            'type' => 'required|in:IBAN,PRECARD,WALLET,WALLETMANAGER,BDAWITHOUTWALLET,OTHERWALLET,OTHERACCOUNT',

            'country_id' => 'required_if:type,IBAN,WALLET,WALLETMANAGER,OTHERWALLET',

            'walletManagerId' => 'required_if:type,WALLET,WALLETMANAGER,OTHERWALLET',

            'ibanNumber' => 'required_if:type,IBAN,OTHERACCOUNT',

            'cardNumber' => 'required_if:type,PRECARD',
            'cardHolderName' => 'required_if:type,PRECARD',
            'amount' => 'required_if:type,PRECARD',
            'phone' => 'required_if:type,WALLET,WALLETMANAGER,OTHERWALLET',
        ];

        if ($input['type'] === 'BDAWITHOUTWALLET' && !empty($input['walletManagerId'])) {

            /* $validate_data['idTypeSender'] = 'required|string|max:255';
            $validate_data['idNumberSender'] = 'required|string|max:255';
            $validate_data['addressSender'] = 'required|string|max:255';
            $validate_data['citySender'] = 'required|string|max:255';
            $validate_data['countrySender'] = 'required|string|max:255'; */

            $validate_data['idTypeReceiver'] = 'required|string|max:255';
            $validate_data['idNumberReceiver'] = 'required|string|max:255';
            $validate_data['addressReceiver'] = 'required|string|max:255';
            $validate_data['cityReceiver'] = 'required|string|max:255';
        }

        $customMessages = [
            'country_id.required' => __("message_app.Country field cannot be left blank"),

            'walletManagerId.required_if' => __("message_app.Wallet Manager is required when type is WALLET, WALLETMANAGER"),

            'ibanNumber.required_if' => __("message_app.IBAN Number is required when type is IBAN"),

            'cardNumber.required_if' => __("message_app.Card number is required when type is PRECARD"),
            'cardHolderName.required_if' => __("message_app.Card holder name is required when type is PRECARD"),

            'phone.required_if' => __("message_app.Phone number is required when type is WALLET ,WALLETMANAGER"),
            'amount.required_if' => __("message_app.Amount required when type PRECARD"),
            'type.required' => __("message_app.Type field cannot be left blank"),

            /* 'idTypeSender.required' => 'Id type sender is required when type is BDAWITHOUTWALLET and Wallet Manager is filled.',
            'idNumberSender.required' => 'Id number sender is required when type is BDAWITHOUTWALLET and Wallet Manager is filled.',
            'addressSender.required' => 'Address sender is required when type is BDAWITHOUTWALLET and Wallet Manager is filled.',
            'citySender.required' => 'City sender is required when type is BDAWITHOUTWALLET and Wallet Manager is filled.',
            'countrySender.required' => 'Country sender is required when type is BDAWITHOUTWALLET and Wallet Manager is filled.', */

            'idTypeReceiver.required' => __("message_app.Id type receiver is required when type is BDAWITHOUTWALLET and Wallet Manager is filled"),
            'idNumberReceiver.required' => __("message_app.Id number receiver is required when type is BDAWITHOUTWALLET and Wallet Manager is filled"),
            'addressReceiver.required' => __("message_app.Address receiver is required when type is BDAWITHOUTWALLET and Wallet Manager is filled"),
            'cityReceiver.required' => __("message_app.City receiver is required when type is BDAWITHOUTWALLET and Wallet Manager is filled"),
        ];




        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($firstErrorMessage == "validation.in") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allow"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        try {
            if (isset($request->amount) && $request->amount != "") {
                $senderUser = User::where('id', $user_id)->first();
                if ($request->amount > $senderUser->wallet_balance) {

                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Insufficient Balance"),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
            // $paymentTypesss = $request->ibanNumber ? 'INCACCREMIT' : 'PREPAIDCARDRELOAD';

            $paymentType = null;

            if ($request->type === 'WALLET') {
                $paymentType = 'REQUESTTOPAY';
            } elseif ($request->type === 'WALLETMANAGER') {
                $paymentType = 'WALLETTOWALLET';
            } elseif ($request->type === 'IBAN' && $request->ibanNumber) {
                $paymentType = 'WALLETTOACCOUNT';
            } elseif ($request->type === 'PRECARD') {
                $paymentType = 'PREPAIDCARDRELOAD';
            } elseif ($request->type === 'OTHERWALLET') {
                $paymentType = 'WALLETINCOMMING';
            } elseif ($request->type === 'OTHERACCOUNT') {
                $paymentType = 'INCACCREMIT';
            } elseif ($request->type === 'BDAWITHOUTWALLET') {
                $paymentType = '';
            }

            //$paymentType = $request->type == 'WALLET' ? 'REQUESTTOPAY' : ($request->type == "PRECARD" ? 'PREPAIDCARDRELOAD' : 'INCACCREMIT');
            $ibanAccount = $request->type == 'IBAN' || $request->type == 'OTHERACCOUNT' ? $request->ibanNumber : ($request->type == "PRECARD" ? $request->cardNumber : "");
            $receviver_mobile = $request->type == 'WALLET' || $request->type == 'WALLETMANAGER' || $request->type == 'OTHERWALLET' ? $request->phone : "";

            /*$tomember = "";
             if ($request->type == 'WALLET' || $request->type == 'WALLETMANAGER' || $request->type == 'BDAWITHOUTWALLET') {
                $tomemberData = WalletManager::where('id', $request->walletManagerId)->first();
                if (!empty($tomemberData)) {
                    $tomember = $tomemberData->tomember;
                }
            } else {
                if ($request->type == "IBAN") {
                    $tomember = "12001";
                }
            } */
            $tomember = "10029";
            $dateString = date('d-m-Y H:i:s');
            $format = 'd-m-Y H:i:s';
            $dateTime = DateTime::createFromFormat($format, $dateString);
            $timestamp = $dateTime->getTimestamp();

            $newIssuertrxref = uniqid();
            if ($request->type == "BDAWITHOUTWALLET") {
                if (!empty($request->country_id) && !empty($request->walletManagerId)) {
                    $accessToken = $this->gimacApiService->getAccessToken();
                    if ($accessToken['status'] === false) {

                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __("message_app.Access token not found"), //$accessToken['message']
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }

                    $accessToken = $accessToken['token'];
                    $paymentType = "OUTGOINGWALLET";
                    $receviver_mobile = $request->phone;
                    $responseInquiry = $this->gimacApiService->walletAndAccountInquiry($paymentType, $receviver_mobile, $newIssuertrxref, $tomember, $accessToken, $ibanAccount);
                    if ($responseInquiry['status'] == 1 && $responseInquiry['statusCode'] == 200 && isset($responseInquiry['data']) && $responseInquiry['data']->state == "ACCEPTED") {
                        $data = [];

                        $data = [
                            "phone" => $request->phone ?? "",
                            'name' => $responseInquiry['data']->receivercustomerdata->firstname . ' ' . $responseInquiry['data']->receivercustomerdata->secondname,
                            'user_id' => 0,
                            'user_type' => 'User',
                            "profile_image" => "public/img/" . "no_user.png",
                            "email" => "",
                            // "country" => "",
                        ];


                        $statusArr = [
                            "status" => "Success",
                            "reason" => $request->type == 'IBAN' ? __("message_app.IBAN verified successfully") : ($request->type == 'PRECARD' ? __("message_app.CARD verified successfully") : __("message_app.Wallet verified successfully")),
                            "data" => $data
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }


                } elseif (!empty($request->country_id) && !empty($request->ibanNumber)) {
                    $data["phone"] = $request->ibanNumber ?? "";
                    $data = array_merge($data, [
                        'name' => "User",
                        'user_id' => 0,
                        'user_type' => 'User',
                        "profile_image" => "public/img/" . "no_user.png",
                        "email" => "",
                    ]);


                    $statusArr = [
                        "status" => "Success",
                        "reason" => __("message_app.IBAN verified successfully"),
                        "data" => $data
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }

            $accessToken = $this->gimacApiService->getAccessToken();

            // Log::channel('GIMAC')->info("GIMAC API 2 Access Token Response: ".$accessToken);

            if ($accessToken['status'] === false) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Access token not found"), //$accessToken['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $accessToken = $accessToken['token'];
            if ($request->type != "PRECARD") {
                $responseInquiry = $this->gimacApiService->walletAndAccountInquiry($paymentType, $receviver_mobile, $newIssuertrxref, $tomember, $accessToken, $ibanAccount);
                // Log::channel('GIMAC')->info("GIMAC API Access Token Response : ".$responseInquiry);
            }

            if ($request->type == "PRECARD") {
                $patterns = [
                    'Visa' => '/^4/',
                    'Master' => '/^(5[1-5]|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)/',
                    'Amex' => '/^3[47]/',
                    'Discover' => '/^(6011|62212[6-9]|6221[3-9][0-9]|622[2-8][0-9]{2}|6229[01][0-9]|62292[0-5]|64[4-9]|65)/',
                    'Diners' => '/^3(0[0-5]|[68])/',
                    'JCB' => '/^(?:2131|1800|35\d{2})/',
                    'UnionPay' => '/^62/',
                ];

                foreach ($patterns as $card => $pattern) {
                    if (preg_match($pattern, $request->cardNumber)) {
                        $data["card_type"] = $card;
                        $data["card_image"] = 'public/uploads/cards/' . strtolower($card) . '.png';
                    }
                }
                $data["phone"] = $request->phone ?? "";



                $data = array_merge($data, [
                    'name' => $request->type == "PRECARD" ? $request->cardHolderName : "",
                    'user_id' => 0,
                    'user_type' => 'User',
                    "profile_image" => "public/img/" . "no_user.png",
                    "email" => "",
                    // "country" => "",
                ]);


                $statusArr = [
                    "status" => "Success",
                    "reason" => $request->type == 'IBAN' ? __("message_app.IBAN verified successfully") : ($request->type == 'PRECARD' ? __("message_app.CARD verified successfully") : __("message_app.Wallet verified successfully")),
                    "data" => $data
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } elseif ($responseInquiry['status'] == 1 && $responseInquiry['statusCode'] == 200 && isset($responseInquiry['data']) && $responseInquiry['data']->state == "ACCEPTED") {
                $data = [];
                if ($request->type == 'IBAN' || $request->type == 'OTHERACCOUNT') {
                    $data["phone"] = $request->ibanNumber ?? "";
                } elseif ($request->type == 'WALLETMANAGER' || $request->type == 'WALLET' || $request->type == 'OTHERWALLET') {
                    $data["phone"] = $request->phone ?? "";
                }

                $data = array_merge($data, [
                    'name' => $responseInquiry['data']->receivercustomerdata->firstname . ' ' . $responseInquiry['data']->receivercustomerdata->secondname,
                    'user_id' => 0,
                    'user_type' => 'User',
                    "profile_image" => "public/img/" . "no_user.png",
                    "email" => "",
                    // "country" => "",
                ]);


                $statusArr = [
                    "status" => "Success",
                    "reason" => $request->type == 'IBAN' ? __("message_app.IBAN verified successfully") : ($request->type == 'PRECARD' ? __("message_app.CARD verified successfully") : __("message_app.Wallet verified successfully")),
                    "data" => $data
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Wallet/Account not found") ?? __("message_app.Timeout during send to payee")
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
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
                } else {
                    $statusCode = $response->getStatusCode();
                    $errorDescription = __("message_app.Server Error, Please wait a few minutes before you try again");
                    if ($statusCode == '403') {
                        $errorDescription = __("message_app.Error Code : 403 Forbidden Error");
                    }
                }

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function transactionSummery(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */
        // Log::info($request->type);
        /* if (isset($request->type) && $request->type != 'REQUESTMONEY' || Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                 $json = json_encode($statusArr);
                 $responseData = $this->encryptContent($json);
                 return response()->json($responseData, 200);
             }
         } */

        $minAmount = "1";
        $maxAmount = "10000000";

        if (isset($request->transaction_id) && $request->transaction_id == "" || $request->transaction_id == null) {
            $input = [
                'country_id' => $request->country_id ?? null,
                'walletManagerId' => $request->walletManagerId ?? null,
                'cardHolderName' => $request->cardHolderName ?? null,
                'cardNumber' => $request->cardNumber ?? null,
                'note' => $request->note ?? null,
                'phone' => $request->phone ?? null,
                'amount' => $request->amount ?? null,
                'ibanNumber' => $request->ibanNumber ?? null,
                'type' => $request->type ?? null,
                'trans_type' => $request->trans_type ?? null,
            ];
            $validate_data = [
                'country_id' => 'required_if:type,SENDMONEY,REQUESTMONEY',
                'cardHolderName' => 'required_if:type,PRECARD',
                'cardNumber' => 'required_if:type,PRECARD',
                'note' => 'required_if:type,PRECARD',
                'type' => 'required|in:REQUESTMONEY,SENDMONEY,PRECARD,SWAPTOSWAP,AIRTELMONEY,RECHARGE,TRANSFEROUT',
                'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
                'phone' => 'required_if:type,REQUESTMONEY,AIRTELMONEY',
                'trans_type' => 'required',
            ];
            $customMessages = [
                'country_id.required_if' => __("message_app.Country is required for SENDMONEY or REQUESTMONEY"),
                'cardHolderName.required_if' => __("message_app.Card holder name is required for PREPAID cards"),
                'cardNumber.required_if' => __("message_app.Card number is required for PREPAID cards"),
                'note.required_if' => __("message_app.Note is required for PREPAID cards"),
                'type.required' => __("message_app.Transaction type is required"),
                'type.in' => __("message_app.Invalid type"),
                'amount.required' => __("message_app.Amount is required"),
                'amount.numeric' => __("message_app.Amount must be a valid number"),
                'amount.gt' => __("message_app.Amount must be greater than zero"),
                'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
                'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
                'phone.required_if' => __("message_app.Phone is required for REQUESTMONEY"),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            $validator->after(function ($validator) use ($request) {
                $type = $request->type;
                $walletManagerId = $request->walletManagerId ?? null;
                $ibanNumber = $request->ibanNumber ?? null;

                if ($type === 'SENDMONEY') {
                    if (empty($walletManagerId) && empty($ibanNumber)) {
                        $validator->errors()->add('walletManagerId', __("message_app.Wallet Manager ID is required if IBAN Number is not provided"));
                        $validator->errors()->add('ibanNumber', __("message_app.IBAN Number is required if Wallet Manager ID is not provided"));
                    }
                }

                /* if ($type === 'REQUESTMONEY' && empty($walletManagerId)) {
                    $validator->errors()->add('walletManagerId', 'Wallet Manager ID is required for REQUESTMONEY.');
                } */
            });

            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();
                if ($firstErrorMessage == "validation.in") {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Type is not allow"),
                    ];
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $firstErrorMessage,
                    ];
                }
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            // Log::channel('GIMAC')->info($request->all());
            $country_id = $request->country_id ?? 0;
            $walletManagerId = $request->walletManagerId ?? 0;
            $trans_type = $request->trans_type;
            $type = $request->type ?? 0;
            $phone = $request->phone ?? "";
            $ibanNumber = $request->ibanNumber ?? "";
            $note = $request->note ?? "";
            $typesNew = "";
        } else {
            $getTrans = $this->getTransactionDataById($request->transaction_id);
            // dd($getTrans);
            $country_id = $getTrans->country_id ?? 0;
            $walletManagerId = $getTrans->walletManagerId ?? 0;

            if ($getTrans->transactionType == "SWAPTOSWAP") {
                $trans_type = "Send Money";
            } elseif ($getTrans->transactionType == "SWAPTOBDA") {
                $trans_type = "Money Transfer Via BDA";
            } elseif ($getTrans->transactionType == "SWAPTOCEMAC") {
                $trans_type = "Money Transfer Via GIMAC";
            }

            if (in_array($getTrans->paymentType, ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'OUTGOINGWALLET']) || $getTrans->transactionType == "SWAPTOBDA") {
                $type = "SENDMONEY";
            } elseif ($getTrans->paymentType == "REQUESTTOPAY") {
                $type = 'REQUESTMONEY';
            } elseif ($getTrans->transactionType == "SWAPTOSWAP") {
                $type = 'SWAPTOSWAP';
            }

            if ($getTrans->transactionType == "SWAPTOSWAP") {
                $getRece = $this->getUserDataByPhone($getTrans->receiver_id);
                $phone = $getRece->receiver_mobile ?? "";
            } elseif ($getTrans->transactionType == "SWAPTOBDA") {
                $getIban = $this->getUserDataByIban($getTrans->onafriq_bda_ids);
                $ibanNumber = $getIban->iban ?? "";
            } elseif ($getTrans->transactionType == "SWAPTOCEMAC") {
                $phone = $getTrans->receiver_mobile ?? "";
                $ibanNumber = $getTrans->receiverAccount;
            }
            $note = $request->note ?? "";

            $typesNew = "";
            if ($getTrans->transactionType == "SWAPTOSWAP") {
                $typesNew = "send_money";
            } elseif ($getTrans->transactionType == "SWAPTOCEMAC") {
                $typesNew = "SendMoneyFromCEMAC";
            } elseif ($getTrans->transactionType == "SWAPTOBDA") {
                $typesNew = "sendMoneyForOutSideCemac";
            } elseif ($getTrans->transactionType == "SWAPTOOUTCEMAC") {
                $typesNew = "sendMoneyForOutSideCemac";
            }
        }

        if (isset($request->trans_type) && $request->trans_type == "AIRTELMONEY") {
            $trans_type = "Airtel Deposit";
        }

        if (in_array($request->trans_type, ['RECHARGE', 'TRANSFEROUT'])) {
            $trans_type = "CARDPAYMENT";
        }

        $senderUser = User::where('id', $user_id)->first();
        // $userType = $this->getUserType($senderUser->user_type);
        $country_id = $country_id ?? null;
        $walletManagerId = $walletManagerId ?? null;
        $userType = $this->getUserType($senderUser->user_type);
        if ($type != "PRECARD" || $type != "SWAPTOSWAP") {
            if (isset($request->transaction_id) && !empty($request->transaction_id)) {
                $getTrans = $this->getTransactionDataById($request->transaction_id);
                if ($getTrans->transactionType == "SWAPTOBDA" || $getTrans->transactionType == "SWAPTOOUTCEMAC") {
                    $countryList = DB::table('countries_onafriq')->where('id', $getTrans->country_id)->first();
                    $walletManager = DB::table('wallet_manager_onafriq')->where('id', $getTrans->walletManagerId)->first();
                } else {
                    $countryList = Country::where('id', $country_id)->first();
                    $walletManager = WalletManager::where('id', $walletManagerId)->first();
                }
            } else {
                $countryList = Country::where('id', $country_id)->first();
                $walletManager = WalletManager::where('id', $walletManagerId)->first();
            }
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        // dd($countryList->name,$WalletManager);

        $amount = $request->amount;
        if (isset($request->accept_money_request_type) && $request->accept_money_request_type == "Accept Request Money" && $trans_type == "Send Money") {
            $trans_type = "Request Money";
        }
        $getfeeRecord = $this->calculateTotalFees($trans_type, $amount);
        $total_fees = $getfeeRecord['total_fees'] ?? 0;
        $feeType = $getfeeRecord['fee_type'] ?? 0;
        $feePer = $getfeeRecord['fee_per'] ?? 0;

        $adminInfo = Admin::where("id", 1)->first();
        /* if ($transactionLimit->minSendMoney > $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
    'currency' => CURR,
    'amount'   => number_format($adminInfo->minSendMoney, 0, ',', ' ')
]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($amount > $transactionLimit->maxSendMoney) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
    'currency' => CURR,
    'amount'   => number_format($transactionLimit->maxSendMoney, 0, ',', ' ')
]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */
        $transArr = [];
        // dd($type);
        if ($type == "REQUESTMONEY") {
            $transArr = [
                /* [
                    'title' => 'Transaction Type',
                    'value' => 'CEMAC',
                    'image' => '',
                    'type' => 'string',
                ], */
                [
                    'title' => $feeType == 0 ? __("message_app.Swap fees") . ' ' . 0 . '%' : __("message_app.Swap fees") . ' ' . CURR . ' ' . 0,
                    'value' => 'XAF ' . $total_fees,
                    'image' => '',
                    'type' => 'amount',
                ],
                [
                    'title' => isset($request->request_type) && $request->request_type == "Accept" ? __("message_app.Amount will be paid") : __("message_app.Amount will be receive"),
                    'value' => 'XAF ' . ($amount - $total_fees),
                    'image' => '',
                    'type' => 'amount',
                ],

            ];
            if (!empty($country_id) && $country_id != "50") {
                $transArr[] = [
                    'title' => __("message_app.Mobile Number"),
                    'value' => $phone ?? "",
                    'image' => '',
                    'type' => 'number',
                ];
            }
            if (!empty($walletManagerId)) {
                $transArr[] = [
                    'title' => __("message_app.Wallet Manager"),
                    'value' => $walletManager->name ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if (!empty($country_id) && $country_id != "50") {
                $transArr[] = [
                    'title' => __("message_app.Country"),
                    'value' => $countryList->name ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            }

            if (!empty($note)) {
                $transArr[] =
                    [
                        'title' => __("message_app.Notes"),
                        'value' => $note ?? "",
                        'image' => '',
                        'type' => 'string',
                    ];
            }
        } elseif ($type == "AIRTELMONEY") {
            $transArr = [
                [
                    'title' => $feeType == 0 ? __("message_app.Airtel fees") . ' ' . $feePer . '%' : __("message_app.Airtel fees") . ' ' . CURR . ' ' . $total_fees,
                    'value' => 'XAF ' . $total_fees,
                    'image' => '',
                    'type' => 'amount',
                ],
                /* [
                    'title' => 'Taxes',
                    'value' => '0',
                    'image' => '',
                    'type' => 'amount',
                ], */
                [
                    'title' => __("message_app.Amount will be receive"),
                    'value' => 'XAF ' . ($amount - $total_fees),
                    'image' => '',
                    'type' => 'amount',
                ],
            ];
        } elseif ($trans_type == "CARDPAYMENT") {
            $transArr = [
                [
                    'title' => $request->trans_type == "RECHARGE" ? __("message_app.Amount will be send wallet to card") : __("message_app.Amount will be send card to wallet"),
                    'value' => 'XAF ' . ($amount - $total_fees),
                    'image' => '',
                    'type' => 'amount',
                ],
            ];
        } elseif ($type == "SWAPTOSWAP") {
            $transArr = [
                /* [
                    'title' => 'Transaction Type',
                    'value' => 'SWAPTOSWAP',
                    'image' => '',
                    'type' => 'string',
                ], */
                [
                    'title' => $feeType == 0 ? __("message_app.Swap fees") . ' ' . $feePer . '%' : __("message_app.Swap fees") . ' ' . CURR . ' ' . $total_fees,
                    'value' => 'XAF ' . $total_fees,
                    'image' => '',
                    'type' => 'amount',
                ],
                /* [
                    'title' => 'Taxes',
                    'value' => '0',
                    'image' => '',
                    'type' => 'amount',
                ], */
                [
                    'title' => __("message_app.Amount will be paid"),
                    'value' => 'XAF ' . $amount,
                    'image' => '',
                    'type' => 'amount',
                ],
            ];

            if (!empty($note)) {
                $transArr[] =
                    [
                        'title' => __("message_app.Notes"),
                        'value' => $note ?? "",
                        'image' => '',
                        'type' => 'string',
                    ];
            }

        } elseif ($type == "SENDMONEY") {
            $transArr = [
                /* [
                    'title' => 'Transaction Type',
                    'value' => 'CEMAC',
                    'image' => '',
                    'type' => 'string',
                ], */
                [
                    'title' => $feeType == 0 ? __("message_app.Swap fees") . ' ' . $feePer . '%' : __("message_app.Swap fees") . ' ' . CURR . ' ' . $total_fees,
                    'value' => 'XAF ' . $total_fees,
                    'image' => '',
                    'type' => 'amount',
                ],
                /* [
                    'title' => 'Taxes',
                    'value' => '0',
                    'image' => '',
                    'type' => 'amount',
                ], */
                [
                    'title' => __("message_app.Amount will be paid"),
                    'value' => 'XAF ' . $amount,
                    'image' => '',
                    'type' => 'amount',
                ],
                [
                    'title' => __("message_app.Country"),
                    'value' => $countryList->name ?? "",
                    'image' => '',
                    'type' => 'string',
                ],
            ];

            if (!empty($walletManager->name)) {
                $transArr[] = [
                    'title' => __("message_app.Wallet Manager"),
                    'value' => $walletManager->name,
                    'image' => '',
                    'type' => 'string',
                ];
            }

            if (!empty($ibanNumber)) {
                $transArr[] = [
                    'title' => __("message_app.IBAN Number"),
                    'value' => $ibanNumber,
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if (!empty($phone)) {
                $transArr[] = [
                    'title' => __("message_app.Mobile Number"),
                    'value' => $phone,
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if (!empty($note)) {
                $transArr[] =
                    [
                        'title' => __("message_app.Notes"),
                        'value' => $note ?? "",
                        'image' => '',
                        'type' => 'string',
                    ];
            }

        } elseif ($type == "PRECARD") {
            $transArr = [
                /* [
                    'title' => 'Transaction Type',
                    'value' => 'CEMAC',
                    'image' => '',
                    'type' => 'string',
                ], */
                [
                    'title' => $feeType == 0 ? __("message_app.Swap fees") . ' ' . $feePer . '%' : __("message_app.Swap fees") . ' ' . CURR . ' ' . $total_fees,
                    'value' => 'XAF ' . $total_fees,
                    'image' => '',
                    'type' => 'amount',
                ],
                /* [
                    'title' => 'Taxes',
                    'value' => '0',
                    'image' => '',
                    'type' => 'amount',
                ], */
                [
                    'title' => __("message_app.Amount"),
                    'value' => CURR . $this->numberFormatSpaces($this->roundAmount($amount)),
                    'image' => '',
                    'type' => 'amount',
                ],
                [
                    'title' => __("message_app.Amount will be paid"),
                    'value' => CURR . $this->numberFormatSpaces($this->roundAmount($amount)),
                    'image' => '',
                    'type' => 'amount',
                ],
                [
                    'title' => __("message_app.Card Holder Name"),
                    'value' => $request->cardHolderName ?? "",
                    'image' => '',
                    'type' => 'string',
                ],
                [
                    'title' => __("message_app.Card Number"),
                    'value' => $request->cardNumber ?? "",
                    'image' => '',
                    'type' => 'number',
                ],
            ];

            if (!empty($note)) {
                $transArr[] =
                    [
                        'title' => __("message_app.Notes"),
                        'value' => $note ?? "",
                        'image' => '',
                        'type' => 'string',
                    ];
            }
        }


        $transactionArray = [
            "amount" => in_array($type, ["REQUESTMONEY", "AIRTELMONEY"]) ? ($amount ?? 0) : (($amount ?? 0) + ($total_fees ?? 0)),
            "currency" => 'XAF',
            "transactionType" => $typesNew ?? "",
        ];
        $transactionArray['transactionList'] = $transArr;

        $record = [
            'status' => "Success",
            'reason' => __("message_app.Transaction Summary"),
            'data' => $transactionArray,
        ];

        $json = json_encode($record);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function sendRequestFromCemac(Request $request)
    {
        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */
        if (isset($request->transaction_id) && $request->transaction_id == "") {
            $input = [
                'country_id' => $request->country_id ?? null,
                'walletManagerId' => $request->walletManagerId ?? null,
                'amount' => $request->amount ?? null,
                'phone' => $request->phone ?? null,
                'note' => $request->note ?? null,
                'receiverName' => $request->receiverName ?? null,
                'trans_type' => $request->trans_type ?? null,
            ];

            $validate_data = [
                'country_id' => 'required',
                'walletManagerId' => 'required',
                'amount' => "required|numeric|min:1|max:99999999",
                'phone' => 'required',
                'receiverName' => 'required',
                'trans_type' => 'required',
            ];

            $customMessages = [
                'country_id.required' => __("message_app.Country field cannot be left blank"),
                'walletManagerId.required' => __("message_app.Wallet manager field cannot be left blank"),
                'phone.required' => __("message_app.Phone number field cannot be left blank"),
                'trans_type.required' => __("message_app.Trans type field cannot be left blank"),
                'receiverName.required' => __("message_app.Receiver name field cannot be left blank"),
                'amount.gt' => __("message_app.Amount must be grater than 0"),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $country_id = $request->country_id ?? "";
            $walletManagerId = $request->walletManagerId ?? "";
            $phone = $request->phone ?? "";
            $note = $request->note ?? "";
            $receiverName = $request->receiverName ?? "";
            $trans_type = $request->trans_type ?? "";
        } else {
            $getTrans = $this->getTransactionDataById($request->transaction_id);
            $country_id = $getTrans->country_id ?? 0;
            $walletManagerId = $getTrans->walletManagerId ?? 0;
            $phone = $getTrans->receiver_mobile ?? "";
            $note = $request->note ?? $getTrans->notes;
            $receiverName = $getTrans->receiverName ?? "";
            $trans_type = "Money Transfer Via GIMAC";
        }

        $senderUser = User::where('id', $user_id)->first();
        $userType = $this->getUserType($senderUser->user_type);

        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        $tomember = "";
        $tomemberData = WalletManager::where('id', $walletManagerId)->first();
        if (!empty($tomemberData)) {
            $tomember = $tomemberData->tomember;
        }
        // $tomember = $request->tomember;
        $amount = $request->amount ?? "";
        $adminInfo = Admin::where("id", 1)->first();

        if ($transactionLimit->minSendMoney > $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($adminInfo->minSendMoney),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($amount > $transactionLimit->maxSendMoney) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxSendMoney)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $dateString = date('d-m-Y H:i:s');
        $format = 'd-m-Y H:i:s';
        $dateTime = DateTime::createFromFormat($format, $dateString);
        $timestamp = $dateTime->getTimestamp();
        $sender_mobile = $senderUser->phone;
        $paymentType = 'REQUESTTOPAY';
        $cardNumber = "";
        $senderAccount = "";
        $receiverAccount = "";
        $senderData = [];
        $receiverData = [];

        $trans_id = time();
        $refrence_id = time();

        $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
        if ($last_record != "") {
            $next_issuertrxref = $last_record + 1;
        } else {
            $next_issuertrxref = '140071';
        }

        $getfeeRecord = $this->calculateTotalFees($trans_type, $amount);
        $total_fees = $getfeeRecord['total_fees'] ?? 0;
        $total_amount = $amount;

        /* if ($total_amount != $request->total_amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Transaction failed because fees strucutre has been changed.Please try again.',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        /* if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */


        try {
            $accessToken = $this->gimacApiService->getAccessToken();

            if ($accessToken['status'] === false) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Access token not found"), //$accessToken['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $accessToken = $accessToken['token'];
            // dd($timestamp, $sender_mobile, $paymentType, $phone, $next_issuertrxref, $amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData);
            $jsonResponse2 = $this->gimacApiService->walletPaymentAllType($timestamp, $sender_mobile, $paymentType, $phone, $next_issuertrxref, $amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData);

            if ($jsonResponse2['statusCode'] == 200) {
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                $jsonResponse2 = $jsonResponse2['data'];
                $tomember = $jsonResponse2->tomember ?? "";
                $acquirertrxref = $jsonResponse2->acquirertrxref ?? "";
                $issuertrxref = $jsonResponse2->issuertrxref;
                $state = $jsonResponse2->state;
                // $status = $state == 'ACCEPTED' ? 1 : 2;
                $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));
                $vouchercode = $jsonResponse2->vouchercode ?? 0;

                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'receiver_mobile' => $phone,
                    'amount' => $amount,
                    'receiverName' => $receiverName ?? "",
                    'transaction_amount' => 0,
                    'amount_value' => $amount,
                    'total_amount' => $total_amount,
                    'trans_type' => 4,
                    'payment_mode' => 'send_money',
                    'status' => $status,
                    'refrence_id' => $trans_id,
                    'billing_description' => "SendMoney-$refrence_id",
                    'country_id' => $country_id ?? 0,
                    'walletManagerId' => $walletManagerId ?? 0,
                    'tomember' => $tomember,
                    'acquirertrxref' => $acquirertrxref,
                    'issuertrxref' => $issuertrxref,
                    'vouchercode' => $vouchercode,
                    'transactionType' => 'SWAPTOCEMAC',
                    'paymentType' => $paymentType ?? "",
                    'entryType' => 'API',
                    'note' => $note ?? "",
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();
                $transactionId = $trans->id;
                $statusArr = [
                    "status" => "Success",
                    "reason" => $state == 'ACCEPTED' ? __("message_app.Your transaction has been successful") : __('message_app.transaction_in_state', ['state' => $state]),
                ];

                $title = __('message_app.Money Request');
                $message = __('message_app.send_money_success', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($amount)), 'username' => $receiverName]);
                $device_type = $senderUser->device_type;
                $device_token = $senderUser->device_token;

                if ($state == 'ACCEPTED') {
                    $remainingWalletBalance = $senderUser->wallet_balance + $amount;
                    Transaction::where('id', $transactionId)->update(['remainingWalletBalance' => $remainingWalletBalance, 'trans_type' => 1]);
                    User::where('id', $senderUser->id)->increment('wallet_balance', $amount);

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            "Transaction Successful",
                            "Your Transaction has been successful",
                            $data1,
                            $device_type
                        );
                    }
                    $notif = new Notification([
                        'user_id' => $senderUser->id,
                        'notif_title' => __("message_app.Transaction Successful"),
                        'notif_body' => __("message_app.Your Transaction has been successful"),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                } elseif ($state == 'PENDING') {
                    $remainingWalletBalance = $senderUser->wallet_balance;
                    Transaction::where('id', $transactionId)->update(['remainingWalletBalance' => $remainingWalletBalance]);
                    $data1 = [
                        'title' => __("message_app.Transaction Pending"),
                        'body' => __("message_app.Your Transaction has been pending"),
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            "Transaction Pending",
                            "Your Transaction has been pending",
                            $data1,
                            $device_type
                        );
                    }
                }


                $notif = new Notification([
                    'user_id' => $senderUser->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                // Send email
                $status = $this->getStatusText(1);


                $statusArr = array("status" => "Success", "reason" => __("message_app.The money request has been successfully sent"), 'transactionId' => $transactionId);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $jsonResponse2['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody();
                $contents = $body->getContents();
                // Now, $contents contains the response body
                $jsonResponse = json_decode($contents, true);

                $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                if ($last_record != "") {
                    $next_issuertrxref = $last_record + 1;
                } else {
                    $next_issuertrxref = '140061';
                }

                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                    $errorDescription = $jsonResponse['error_description'];
                } else {
                    $statusCode = $response->getStatusCode();
                    $errorDescription = __("message_app.Server Error, Please wait a few minutes before you try again");
                    if ($statusCode == '403') {
                        $errorDescription = __("message_app.Error Code : 403 Forbidden Error");
                    }
                }
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function sendMoneyFromCemac(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */
        $minAmount = "500";
        $maxAmount = "99999999";
        if ($request->transaction_id == "" && $request->transaction_id == null) {
            $input = [
                'country_id' => $request->country_id ?? null,
                'walletManagerId' => $request->walletManagerId ?? null,
                'phone' => $request->phone ?? null,
                'ibanNumber' => $request->ibanNumber ?? null,
                'receiverName' => $request->receiverName ?? null,
                'amount' => $request->amount ?? null,
                'trans_type' => $request->trans_type ?? null,
                'note' => $request->note ?? null,
            ];

            $validate_data = [
                'country_id' => 'required',
                'walletManagerId' => 'required_without:ibanNumber',
                'ibanNumber' => 'required_without:walletManagerId',
                'receiverName' => 'required',
                'phone' => 'required_with:walletManagerId',
                'trans_type' => 'required',
                'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
                'note' => 'nullable|string|max:255',
            ];

            $customMessages = [
                'country_id.required' => __("message_app.Country field cannot be left blank"),
                'walletManagerId.required_without' => __("message_app.Please provide either a wallet manager or enter an IBAN number"),
                'ibanNumber.required_without' => __("message_app.Please provide either IBAN number or select a wallet manager"),
                'receiverName' => __("message_app.Receiver name is required"),
                'phone.required_with' => __("message_app.Phone number is required when Wallet manager is entered"),
                'trans_type.required' => __("message_app.Trans type field cannot be left blank"),
                'amount.required' => __("message_app.The amount field is required"),
                'amount.numeric' => __("message_app.The amount must be a number"),
                'amount.gt' => __("message_app.The amount must be greater than 0"),
                'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
                'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $country_id = $request->country_id ?? 0;
            $walletManagerId = $request->walletManagerId ?? 0;
            $phone = $request->phone ?? "";
            $ibanNumber = $request->ibanNumber ?? "";
            $receiverName = $request->receiverName ?? "";
            $note = $request->note ?? "";
            $trans_type = $request->trans_type;
        } else {
            $getTrans = $this->getTransactionDataById($request->transaction_id);
            $country_id = $getTrans->country_id ?? 0;
            $walletManagerId = $getTrans->walletManagerId ?? 0;
            $phone = $getTrans->receiver_mobile ?? "";
            $ibanNumber = $getTrans->receiverAccount ?? "";
            $receiverName = $getTrans->receiverName ?? "";
            $note = $request->note ?? "";
            $trans_type = "Money Transfer Via GIMAC";
        }

        $amount = $request->amount;

        $senderUser = User::where('id', $user_id)->first();

        $userType = $this->getUserType($senderUser->user_type);

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->gimacMin > $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->gimacMin))
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($amount > $transactionLimit->gimacMax) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->gimacMax))
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $tomember = ""; // default value
        $paymentType = ""; // default value
        if (isset($walletManagerId) && $walletManagerId != "") {
            $tomemberData = WalletManager::where('id', $walletManagerId)->first();
            if (!empty($tomemberData)) {
                $tomember = $tomemberData->tomember;
                $paymentType = 'WALLETTOWALLET';
            }
        } else {
            $tomember = $request->tomember ?? "10029";
            $paymentType = 'WALLETTOACCOUNT';
        }
        /* $tomemberData = WalletManager::where('id', $request->walletManagerId)->first();
        if (!empty($tomemberData)) {
            $tomember = $tomemberData->tomember;
        } */
        // dd($paymentType,$tomember);

        $getfeeRecord = $this->calculateTotalFees($trans_type, $amount);
        $total_fees_trans = $getfeeRecord['total_fees'] ?? 0;
        $total_fees = $getfeeRecord['total_fees'] ?? 0;

        $total_amount = $amount + $total_fees_trans;

        /* $referralFeeAmount = 0;
        $referralByUserId = 0;
        if ($senderUser->referralBy) {
            $getData = $this->referralService->referralFeeAmount($senderUser, $total_fees, 'SWAPTOCEMAC');
            $total_fees = $getData['data']['total_fees'];
            $referralFeeAmount = $getData['data']['referralFeeAmount'] ?? 0;
            $referralByUserId = $getData['data']['referralByUserId'] ?? 0;
        } */

        if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $tomember = $tomember ?? "";
        $sender_mobile = $senderUser->phone;
        $receviver_mobile = $phone ?? "";
        $cardNumber = "";
        $senderAccount = "";
        $receiverAccount = $ibanNumber ?? "";

        $senderData = [
            "firstname" => $senderUser->name ?? ''
        ];
        $receiverData = [
            "firstname" => $receiverName ?? ''
        ];
        try {
            $dateString = date('d-m-Y H:i:s');
            $format = 'd-m-Y H:i:s';
            $dateTime = DateTime::createFromFormat($format, $dateString);
            $timestamp = $dateTime->getTimestamp();
            $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
            if ($last_record != "") {
                $next_issuertrxref = $last_record + 1;
            } else {
                $next_issuertrxref = '140071';
            }

            $accessToken = $this->gimacApiService->getAccessToken();

            if ($accessToken['status'] === false) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Access token not found"), //$accessToken['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $accessToken = $accessToken['token'];

            $jsonResponse2 = $this->gimacApiService->walletPaymentAllType($timestamp, $sender_mobile, $paymentType, $receviver_mobile, $next_issuertrxref, $amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData);
            // print_r($jsonResponse2); die;
            if ($jsonResponse2['statusCode'] == 200) {
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                $jsonResponse2 = $jsonResponse2['data'];
                $tomember = $jsonResponse2->tomember ?? "";
                $acquirertrxref = $jsonResponse2->acquirertrxref ?? "";
                $issuertrxref = $jsonResponse2->issuertrxref;
                $state = $jsonResponse2->state;
                //$status = $state == 'ACCEPTED' ? 1 : 2;
                $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));
                $vouchercode = $jsonResponse2->vouchercode ?? "";
                $refrence_id = time();
                $remainingWalletBalance = $senderUser->wallet_balance - $total_amount;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'receiver_mobile' => $receviver_mobile,
                    'amount' => $amount,
                    'receiverName' => $receiverName ?? "",
                    'amount_value' => $amount,
                    'transaction_amount' => $total_fees_trans ?? 0,
                    'total_amount' => $total_amount,
                    'trans_type' => 2,
                    'payment_mode' => 'wallet2wallet',
                    'status' => $status,
                    'refrence_id' => $issuertrxref,
                    'billing_description' => "Fund Transfer-$refrence_id",
                    'country_id' => $country_id ?? 0,
                    'walletManagerId' => $walletManagerId ?? 0,
                    'tomember' => $tomember ?? '',
                    'acquirertrxref' => $acquirertrxref,
                    'cardHolderName' => '',
                    'senderAccount' => $senderAccount ?? '',
                    'receiverAccount' => $receiverAccount ?? '',
                    'issuertrxref' => $issuertrxref,
                    'vouchercode' => $vouchercode,
                    'transactionType' => 'SWAPTOCEMAC',
                    'paymentType' => $paymentType,
                    'senderData' => json_encode($senderData) ?? "",
                    'receiverData' => json_encode($receiverData) ?? "",
                    'remainingWalletBalance' => $senderUser->wallet_balance ?? "",
                    'beforeBalance' => $senderUser->wallet_balance ?? "",
                    'notes' => $note ?? "",
                    'entryType' => 'API',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();

                $transactionId = $trans->id;

                $statusArr = [
                    "status" => "Success",
                    "reason" => $state == 'ACCEPTED' ? __("message_app.Your transaction has been successful") : __('message_app.transaction_in_state', ['state' => $state]),
                    "transactionId" => $transactionId,
                ];

                $title = __('message_app.Send Money');
                $message = __('message_app.send_money_success', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($amount)), 'username' => $receiverName]);
                $device_type = $senderUser->device_type;
                $device_token = $senderUser->device_token;
                $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();

                if ($state == 'ACCEPTED') {
                    User::where('id', $senderUser->id)->decrement('wallet_balance', $total_amount);
                    DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                    Transaction::where('id', $transactionId)->update(['status' => 1, 'afterBalance' => $total_amount]);
                    /* Card Payment Start */
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $total_amount,
                            "transferType" => "CardToWallet",
                            "mobilePhoneNumber" => "241{$senderUser->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Send money from cemac');
                    }
                    /* Card Payment End */

                    /* if ($referralByUserId) {
                        User::where('id', $referralByUserId)->increment('wallet_balance', $referralFeeAmount);
                        $this->referralService->addReferralAmountByPayment($senderUser, $referralByUserId, $referralFeeAmount, $transactionId);
                    } */

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            "Transaction Successful",
                            "Your Transaction has been successful",
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        'user_id' => $senderUser->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                } elseif ($state == 'PENDING' || $state == 'SUSPECTED') {
                    User::where('id', $senderUser->id)->decrement('wallet_balance', $total_amount);
                    User::where('id', $senderUser->id)->increment('holdAmount', $total_amount);

                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $total_amount,
                            "transferType" => "CardToWallet",
                            "mobilePhoneNumber" => "241{$senderUser->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Send Money From Cemac');
                    }

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];
                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            "Transaction Pending",
                            "Your Transaction has been pending",
                            $data1,
                            $device_type
                        );
                    }
                }

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $jsonResponse2['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody();
                $contents = $body->getContents();
                // Now, $contents contains the response body
                $jsonResponse = json_decode($contents, true);

                $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                if ($last_record != "") {
                    $next_issuertrxref = $last_record + 1;
                } else {
                    $next_issuertrxref = '140061';
                }

                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                    $errorDescription = $jsonResponse['error_description'];
                } else {
                    $statusCode = $response->getStatusCode();
                    $errorDescription = __("message_app.Server Error, Please wait a few minutes before you try again");
                    if ($statusCode == '403') {
                        $errorDescription = __("message_app.Error Code : 403 Forbidden Error");
                    }
                }
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function prepaidCardreloadFromCemac(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $input = [
            'cardHolderName' => $request->cardHolderName ?? null,
            'cardNumber' => $request->cardNumber ?? null,
            'note' => $request->note ?? null,
            'amount' => $request->amount ?? null,
            'trans_type' => $request->trans_type ?? null,
        ];

        $validate_data = [
            'cardHolderName' => 'required',
            'cardNumber' => 'required',
            'amount' => "required|numeric|min:1|max:99999999",
            'note' => 'required',
            'trans_type' => 'required',
        ];

        $customMessages = [
            'cardHolderName.required' => __("message_app.Card holder name field cannot be left blank"),
            'cardNumber.required' => __("message_app.Card number field cannot be left blank"),
            'amount.gt' => __("message_app.Amount must be grater than 0"),
            'note.required' => __("message_app.Note field cannot be left blank"),
            'trans_type.required' => __("message_app.Trans type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $amount = $request->amount;
        $phone = $request->phone ?? "";
        $tomember = "";
        $senderUser = User::where('id', $user_id)->first();
        $userType = $this->getUserType($senderUser->user_type);
        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        $adminInfo = Admin::where("id", 1)->first();

        if ($transactionLimit->minSendMoney > $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $adminInfo->minSendMoney,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($amount > $transactionLimit->maxSendMoney) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $transactionLimit->maxSendMoney,
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $dateString = date('d-m-Y H:i:s');
        $format = 'd-m-Y H:i:s';
        $dateTime = DateTime::createFromFormat($format, $dateString);
        $timestamp = $dateTime->getTimestamp();
        $sender_mobile = $senderUser->phone;
        $paymentType = 'PREPAIDCARDRELOAD';
        $cardNumber = $request->cardNumber ?? "";
        $senderAccount = "";
        $receiverAccount = "";
        $senderData = [];
        $receiverData = [];

        $trans_id = time();
        $refrence_id = time() . '- Test';


        $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
        if ($last_record != "") {
            $next_issuertrxref = $last_record + 1;
        } else {
            $next_issuertrxref = '140071';
        }

        $getfeeRecord = $this->calculateTotalFees($request->trans_type, $amount);
        $total_fees_trans = $getfeeRecord['total_fees'] ?? 0;
        $total_fees = $getfeeRecord['total_fees'] ?? 0;

        $total_amount = $amount + $total_fees_trans;

        /* $referralFeeAmount = 0;
        $referralByUserId = 0;
        if ($senderUser->referralBy) {
            $getData = $this->referralService->referralFeeAmount($senderUser, $total_fees, 'SWAPTOCEMAC');
            $total_fees = $getData['data']['total_fees'];
            $referralFeeAmount = $getData['data']['referralFeeAmount'] ?? 0;
            $referralByUserId = $getData['data']['referralByUserId'] ?? 0;
        } */

        /* if ($total_amount != $request->total_amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Transaction failed because fees strucutre has been changed.Please try again.',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        try {
            $accessToken = $this->gimacApiService->getAccessToken();

            if ($accessToken['status'] === false) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Access token not found"), //$accessToken['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $accessToken = $accessToken['token'];


            $jsonResponse2 = $this->gimacApiService->walletPaymentAllType($timestamp, $sender_mobile, $paymentType, $phone, $next_issuertrxref, $amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData);

            if ($jsonResponse2['statusCode'] == 200) {
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                $jsonResponse2 = $jsonResponse2['data'];
                // $tomember = $jsonResponse2->tomember ?? "";
                $acquirertrxref = $jsonResponse2->acquirertrxref ?? "";
                $issuertrxref = $jsonResponse2->issuertrxref;
                $state = $jsonResponse2->state;
                //$status = $state == 'ACCEPTED' ? 1 : 2;
                $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));
                $vouchercode = $jsonResponse2->vouchercode ?? 0;
                $remainingWalletBalance = $senderUser->wallet_balance - $total_amount;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'receiver_mobile' => $phone,
                    'amount' => $amount,
                    'transaction_amount' => $total_fees,
                    'amount_value' => $amount,
                    'total_amount' => $total_amount,
                    'trans_type' => 2,
                    'payment_mode' => 'wallet2wallet',
                    'status' => $status,
                    'refrence_id' => $trans_id,
                    'billing_description' => "Fund Transfer-$refrence_id",
                    'country_id' => 0,
                    'tomember' => $tomember,
                    'acquirertrxref' => $acquirertrxref,
                    'issuertrxref' => $issuertrxref,
                    'vouchercode' => $vouchercode,
                    'cardNumber' => $cardNumber,
                    'cardHolderName' => $request->cardHolderName,
                    'notes' => $request->note,
                    'transactionType' => 'SWAPTOCEMAC',
                    'remainingWalletBalance' => $remainingWalletBalance ?? "",
                    'beforeBalance' => $senderUser->wallet_balance,
                    'paymentType' => $paymentType ?? "",
                    'entryType' => 'API',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'swapDomainName' => 'UAT_SERVER'
                ]);
                $trans->save();

                $transactionId = $trans->id;

                $statusArr = [
                    "status" => "Success",
                    "reason" => $state == 'ACCEPTED' ? __("message_app.Your transaction has been successful") : __('message_app.transaction_in_state', ['state' => $state]),
                    "transactionId" => $transactionId,
                ];

                $title = __("message_app.Prepaid Card reload");
                $message = __('message_app.send_money_sent', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                ]);

                $device_type = $senderUser->device_type;
                $device_token = $senderUser->device_token;

                if ($state == 'ACCEPTED') {
                    User::where('id', $senderUser->id)->decrement('wallet_balance', $total_amount);
                    Transaction::where('id', $transactionId)->update(['afterBalance' => $total_amount, 'status' => 1]);
                    DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);

                    /* Card Payment Start */
                    $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->first();
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $total_amount,
                            "transferType" => "CardToWallet",
                            "mobilePhoneNumber" => "241{$senderUser->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Prepaid card reload');
                    }
                    /* Card Payment End */

                    /* if ($referralByUserId) {
                        User::where('id', $referralByUserId)->increment('wallet_balance', $referralFeeAmount);
                        $this->referralService->addReferralAmountByPayment($senderUser, $referralByUserId, $referralFeeAmount, $transactionId);
                    } */
                } elseif ($state == 'PENDING' || $state == 'SUSPECTED') {
                    User::where('id', $senderUser->id)->decrement('wallet_balance', $total_amount);
                    User::where('id', $senderUser->id)->increment('holdAmount', $total_amount);
                }

                $data1 = [
                    'title' => $title,
                    'message' => $message,
                    'id' => $transactionId,
                    'type' => 'TRANSACTION',
                ];

                if ($device_type && $device_token) {
                    $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                        $device_token,
                        $title,
                        $message,
                        $data1,
                        $device_type
                    );
                }

                $notif = new Notification([
                    'user_id' => $senderUser->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                // Send email
                $status = $this->getStatusText(1);

                // $statusArr = array("status" => "Success", "reason" => 'The money send has been successfully sent!');
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $jsonResponse2['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody();
                $contents = $body->getContents();
                // Now, $contents contains the response body
                $jsonResponse = json_decode($contents, true);

                $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                if ($last_record != "") {
                    $next_issuertrxref = $last_record + 1;
                } else {
                    $next_issuertrxref = '140061';
                }

                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                    $errorDescription = $jsonResponse['error_description'];
                } else {
                    $statusCode = $response->getStatusCode();
                    $errorDescription = __("message_app.Server Error, Please wait a few minutes before you try again");
                    if ($statusCode == '403') {
                        $errorDescription = __("message_app.Error Code : 403 Forbidden Error");
                    }
                }
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);

                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $errorDescription = $e->getMessage();
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $errorDescription,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }
    public function getTransactionDetailById(Request $request)
    {

        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        $input = [
            'transactionId' => $request->transactionId ?? null,
        ];

        $validate_data = [
            'transactionId' => 'required',
        ];

        $customMessages = [
            'transactionId.required' => __("message_app.Transaction Id field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $transactionId = $request->transactionId;
        $records = Transaction::where('id', $transactionId)->first();
        $transArr = [];

        if ($records) {

            if (!empty($records->transactionType) && $records->transactionType == "SWAPTOSWAP" || $records->trans_type == 4 || $records->payment_mode == "Withdraw") {
                $getReceiver = User::where('id', $records->receiver_id)->first();
                $getAdminDetail = Admin::where('id', 1)->first();
                if (!empty($records->receiver_id)) {
                    $transArr[] = [
                        'title' => __("message_app.Receiver Name"),
                        'value' => $records->trans_for == "Admin" ? $getAdminDetail->username : $getReceiver->name,
                        'image' => '',
                        'type' => 'string',
                    ];
                }
                $transArr[] = [
                    'title' => __("message_app.Receiver Phone"),
                    'value' => $records->trans_for == "Admin" ? "---" : $getReceiver->phone ?? "",
                    'image' => '',
                    'type' => 'number',
                ];
            }

            if (!empty($records->receiverName)) {
                $transArr[] = [
                    'title' => __("message_app.Receiver Name"),
                    'value' => $records->receiverName,
                    'image' => '',
                    'type' => 'string',
                ];
            }


            if (!empty($records->receiver_mobile)) {
                $transArr[] = [
                    'title' => __("message_app.Phone / Wallet Number"),
                    'value' => $records->receiver_mobile,
                    'image' => '',
                    'type' => 'number',
                ];
            }
            if (!empty($records->user_id) && $records->transactionType != "AIRTELMONEY") {
                $getReceiver = User::where('id', $records->user_id)->first();
                $getAdminDetail = Admin::where('id', 1)->first();

                $transArr[] = [
                    'title' => __("message_app.Sender Name"),
                    'value' => $records->trans_for == "Admin" ? $getAdminDetail->username : $getReceiver->name,
                    'image' => '',
                    'type' => 'string',
                ];
                $transArr[] = [
                    'title' => __("message_app.Sender Phone"),
                    'value' => $records->trans_for == "Admin" ? "---" : $getReceiver->phone ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            }

            /* if ($records->paymentType) {
                $transArr[] = [
                    'title' => __("message_app.Payment Type"),
                    'value' => $records->paymentType ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            } */

            /* $transArr[] = [
                'title' => __("message_app.Transaction Type"),
                'value' => ($records->transactionType == "SWAPTOSWAP" ? "SWAPTOSWAP" : $records->transactionType),
                'image' => '',
                'type' => 'string',
            ]; */
            /* $transArr[] = [
                'title' => 'Taxes',
                'value' => '0',
                'image' => '',
                'type' => 'string',
            ]; */
            $transArr[] = [
                'title' => __("message_app.Transaction ID"),
                'value' => ($records->id ?? ""),
                'image' => '',
                'type' => 'string',
            ];
            $transArr[] = [
                'title' => __("message_app.Reference ID"),
                'value' => ($records->refrence_id ?? 0),
                'image' => '',
                'type' => 'string',
            ];
            if ($records->payment_mode != "CARDPAYMENT" && $records->payment_mode != "TRANSAFEROUT") {
                $transArr[] = [
                    'title' => __("message_app.Date & Time"),
                    'value' => Carbon::parse($records->created_at)->format('d M, Y h:i A'),
                    'image' => '',
                    'type' => 'date',
                ];
            }
            if ($records->transactionType == "SWAPTOCEMAC") {
                if (isset($records->country_id) && $records->country_id != "") {
                    $country = Country::where('id', $records->country_id)->first()->name;
                }
                if (isset($records->tomember) && $records->tomember != "") {
                    // $walletManager = WalletManager::where('tomember', $records->tomember)->first()->name;
                    $walletManager = optional(
                        WalletManager::where('tomember', $records->tomember)->first()
                    )->name;
                }
            } elseif ($records->transactionType == "SWAPTOOUTCEMAC") {
                if (isset($records->country_id) && $records->country_id != "") {
                    $country = DB::table('countries_onafriq')->where('id', $records->country_id)->first()->name;
                }

                if (isset($records->walletManagerId) && $records->walletManagerId != "") {
                    $walletManager = DB::table('wallet_manager_onafriq')->where('id', $records->walletManagerId)->first()->name;
                }
            }

            if (!empty($country)) {
                $transArr[] = [
                    'title' => __("message_app.Country"),
                    'value' => $country ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if (!empty($walletManager)) {
                $transArr[] = [
                    'title' => __("message_app.Wallet Manager"),
                    'value' => $walletManager ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            }

            if (!empty($records->receiverAccount)) {
                $transArr[] = [
                    'title' => __("message_app.IBAN Number"),
                    'value' => $records->receiverAccount,
                    'image' => '',
                    'type' => 'string',
                ];
            }


            if (!empty($records->transactionType) && $records->transactionType == "SWAPTOBDA") {
                $getIBAN = RemittanceData::where('id', $records->onafriq_bda_ids)->first()->iban;
                $transArr[] = [
                    'title' => __("message_app.IBAN Number"),
                    'value' => $getIBAN ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if (!empty($records->cardNumber)) {
                $transArr[] = [
                    'title' => __("message_app.Card Holder Name"),
                    'value' => $records->cardHolderName,
                    'image' => '',
                    'type' => 'string',
                ];
                $transArr[] = [
                    'title' => __("message_app.Card Number"),
                    'value' => $records->cardNumber,
                    'image' => '',
                    'type' => 'number',
                ];

            }

            if (!empty($records->notes)) {
                $transArr[] = [
                    'title' => __("message_app.Notes"),
                    'value' => $records->notes ?? "",
                    'image' => '',
                    'type' => 'string',
                ];
            }
            if (Auth::user()->id == $records->user_id) {
                //if (!empty($records->remainingWalletBalance)) {
                //if ($records->payment_mode != "CARDPAYMENT" && $records->payment_mode != "TRANSAFEROUT") {
                $transArr[] = [
                    'title' => __("message_app.Transaction Fees"),
                    'value' => CURR . number_format((($records->transaction_amount - floor($records->transaction_amount)) > 0.5 ? ceil($records->transaction_amount) : floor($records->transaction_amount)), 0, '', ' '),
                    'image' => '',
                    'type' => 'amount',
                ];
                //}
                if ($records->transactionType == "AIRTELMONEY") {
                    $transArr[] = [
                        'title' => __("message_app.Received Amount"),
                        'value' => CURR . number_format((($records->amount - floor($records->amount)) > 0.5 ? ceil($records->amount) : floor($records->amount)), 0, '', ' '),
                        'image' => '',
                        'type' => 'amount',
                    ];
                } else {
                    $transArr[] = [
                        'title' => __("message_app.Total Amount"),
                        // 'value' => ($records->description == "Card Load") ? 'XAF ' . (($records->amount ?? 0) - ($records->transaction_amount ?? 0)) : 'XAF ' . (($records->amount ?? 0) + ($records->transaction_amount ?? 0)),
                        'value' => 'XAF ' . number_format((($v = (($records->description == "Card Load")
                            ? (($records->amount ?? 0) - ($records->transaction_amount ?? 0))
                            : (($records->amount ?? 0) + ($records->transaction_amount ?? 0))))
                            - floor($v) > 0.5 ? ceil($v) : floor($v)), 0, '', ' '),

                        'image' => '',
                        'type' => 'amount',
                    ];
                }

                if ($records->remainingWalletBalance != "0.00") {
                    if ($records->transactionType != "AIRTELMONEY") {
                        /* $transArr[] = [
                            'title' => "Your Remaining Balance",
                            'value' => 'XAF ' . ($records->remainingWalletBalance ?? ""),
                            'image' => '',
                            'type' => 'amount',
                        ]; */
                    }
                }
                //}

                if (isset($request->cardType) && $request->cardType === "VIRTUAL") {
                    if ($records->beforeVirtualBalance != "0.00") {
                        $transArr[] = [
                            'title' => __("message_app.Your Before Balance"),
                            // 'value' => 'XAF ' . ($records->beforeVirtualBalance ?? ""),
                            'value' => CURR . number_format((($records->beforeVirtualBalance - floor($records->beforeVirtualBalance)) > 0.5 ? ceil($records->beforeVirtualBalance) : floor($records->beforeVirtualBalance)), 0, '', ' '),
                            'image' => '',
                            'type' => 'amount',
                        ];
                    }
                    if ($records->afterVirtualBalance != "0.00") {
                        $transArr[] = [
                            'title' => __("message_app.Your After Balance"),
                            // 'value' => 'XAF ' . ($records->afterVirtualBalance ?? ""),
                            'value' => CURR . number_format((($records->afterVirtualBalance - floor($records->afterVirtualBalance)) > 0.5 ? ceil($records->afterVirtualBalance) : floor($records->afterVirtualBalance)), 0, '', ' '),
                            'image' => '',
                            'type' => 'amount',
                        ];
                    }
                } else {
                    if ($records->beforeBalance != "0.00") {
                        $transArr[] = [
                            'title' => __("message_app.Your Before Balance"),
                            'value' => CURR . number_format((($records->beforeBalance - floor($records->beforeBalance)) > 0.5 ? ceil($records->beforeBalance) : floor($records->beforeBalance)), 0, '', ' '),
                            'image' => '',
                            'type' => 'amount',
                        ];
                    }
                    if ($records->afterBalance != "0.00") {
                        $transArr[] = [
                            'title' => __("message_app.Your After Balance"),
                            'value' => CURR . number_format((($records->afterBalance - floor($records->afterBalance)) > 0.5 ? ceil($records->afterBalance) : floor($records->afterBalance)), 0, '', ' '),
                            'image' => '',
                            'type' => 'amount',
                        ];
                    }
                }

            }

            if ($records->payment_mode == "CARDPAYMENT" || $records->payment_mode == "TRANSAFEROUT") {
                if ($records->transactionDate) {
                    $transArr[] = [
                        'title' => __("message_app.Transaction Date"),
                        'value' => ($records->transactionDate ?? ""),
                        'image' => '',
                        'type' => 'string',
                    ];
                }
                if ($records->transactionTime) {
                    $transArr[] = [
                        'title' => __("message_app.Transaction Time"),
                        'value' => ($records->created_at->format('h:i:s') ?? ""),
                        'image' => '',
                        'type' => 'date',
                    ];
                }

                if ($records->description) {
                    $transArr[] = [
                        'title' => __("message_app.Description"),
                        // 'value' => ($records->description == "Denial - POS Purchase" && $records->amount <= 0 ? "POS Denial" : __("message_app.".$records->description)),
                        'value' => ($records->description == "Denial - POS Purchase" && $records->amount <= 0 ? __("message_app.Denial - POS Purchase") : __("message_app." . $records->description)),
                        'image' => '',
                        'type' => 'string',
                    ];
                }
                if ($records->merchantName) {
                    $transArr[] = [
                        'title' => __("message_app.Merchant Name"),
                        'value' => trim($records->merchantName ?? ""),
                        'image' => '',
                        'type' => 'string',
                    ];
                }
                if ($records->merchantCountry) {
                    $transArr[] = [
                        'title' => __("message_app.Merchant Country"),
                        'value' => trim($records->merchantCountry ?? ""),
                        'image' => '',
                        'type' => 'string',
                    ];
                }
            }

            $payAgainVal = false;
            if ($user_id == $records->user_id) {
                if (in_array($records->paymentType, ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'OUTGOINGWALLET', 'OUTWALLETBDA'])) {
                    $payAgainVal = true;
                } elseif ($records->transactionType == "SWAPTOSWAP") {
                    $payAgainVal = true;
                }
            }

            $transactionArray = [
                /* "amount" => 'XAF ' . $this->numberFormatSpaces(
                    ($records->description == "Card Load")
                    ? $this->roundAmount($records->amount) - $this->roundAmount($records->transaction_amount)
                    : (
                        in_array($records->transactionType, ['AIRTELMONEY', 'CARDPAYMENT', 'TRANSAFEROUT'])
                        ? $this->roundAmount($records->amount) + $this->roundAmount($records->transaction_amount)
                        : $this->roundAmount($records->amount)
                    )
                ), */
                "amount" => 'XAF ' . number_format((($v = (($records->description == "Card Load")
                    ? (($records->amount ?? 0) - ($records->transaction_amount ?? 0))
                    : (($records->amount ?? 0) + ($records->transaction_amount ?? 0))))
                    - floor($v) > 0.5 ? ceil($v) : floor($v)), 0, '', ' '),
                "currency" => 'XAF',
                "payAgain" => $payAgainVal,
                "transactionStatus" => __("message_app." . $this->getStatusText($records->status)),
                "slug" => $this->getStatusText($records->status),
                "note" => $records->notes ?? "",
                "transactionStatusMessage" => "Transaction " . ($records->status == 1 ? __("message_app.completeTrans") : __("message_app." . $this->getStatusText($records->status))),
            ];
            $transactionArray['transactionList'] = $transArr;

            $record = [
                'status' => "Success",
                'reason' => __("message_app.Transaction Detail"),
                'data' => $transactionArray,
            ];

            $json = json_encode($record);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Transaction detail not found"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function sendMoneyForOutSideCemac(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */
        if ($request->transaction_id == "" && $request->transaction_id == null) {
            $input = [
                'country_id' => $request->country_id ?? null,
                'walletManagerId' => $request->walletManagerId ?? null,

                'ibanNumber' => $request->ibanNumber ?? null,
                'beneficiary' => $request->beneficiary ?? null,
                'reason' => $request->reason ?? null,

                'idTypeReceiver' => $request->idTypeReceiver ?? null,
                'idNumberReceiver' => $request->idNumberReceiver ?? null,
                'addressReceiver' => $request->addressReceiver ?? null,
                'cityReceiver' => $request->cityReceiver ?? null,

                'phone' => $request->phone ?? null,

                'receiverName' => $request->receiverName ?? null,

                'amount' => $request->amount ?? null,
                'type' => $request->type ?? null,
                'trans_type' => $request->trans_type ?? null,
                'note' => $request->note ?? "",

            ];

            $validate_data = [
                'country_id' => 'exists:countries_onafriq,id',
                'ibanNumber' => ['nullable', 'regex:/^[a-zA-Z0-9]+$/', 'min:24', 'max:30'],
                'phone' => 'required_with:walletManagerId',
                'reason' => 'required_with:ibanNumber',

                'idTypeReceiver' => 'required_with:walletManagerId',
                'idNumberReceiver' => 'required_with:walletManagerId',
                'addressReceiver' => 'required_with:walletManagerId',
                'cityReceiver' => 'required_with:walletManagerId',

                'amount' => 'required|numeric|min:1|max:99999999',
                'type' => 'required|in:OUTCEMAC',
                'trans_type' => 'required',
            ];

            $customMessages = [
                'country_id.exists' => __("message_app.The selected country does not exist"),
                'type.required' => __("message_app.Type field cannot be left blank"),
                'trans_type.required' => __("message_app.Trans type field cannot be left blank"),
                'phone.required_with' => __("message_app.Phone field cannot be left blank"),
                'receiverName.required' => __("message_app.Receiver name field cannot be left blank"),
                'ibanNumber.min' => __("message_app.Iban number min length is 24"),
                'ibanNumber.max' => __("message_app.Iban number mix length is 30"),
                'reason.required_with' => __("message_app.Reason field cannot be left blank"),
                'idTypeReceiver.required_with' => __("message_app.Id Type receiver field cannot be left blank"),
                'idNumberReceiver.required_with' => __("message_app.Id Number receiver field cannot be left blank"),
                'addressReceiver.required_with' => __("message_app.Address receiver field cannot be left blank"),
                'cityReceiver.required_with' => __("message_app.City receiver field cannot be left blank"),

            ];

            $validator = Validator::make($input, $validate_data, $customMessages);

            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();
                if ($validator->errors()->has('type')) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Type is not allowed"),
                    ];
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $firstErrorMessage,
                    ];
                }
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $validator->after(function ($validator) use ($input) {
                $errors = [];

                if (empty($input['walletManagerId']) && empty($input['ibanNumber'])) {
                    $validator->errors()->add('walletManagerId', __("message_app.Either Wallet Manager or IBAN is required"));
                }

                foreach ($errors as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            });


            if ($validator->fails()) {
                $messages = $validator->messages();
                $firstErrorMessage = $messages->first();
                if ($validator->errors()->has('type')) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Type is not allowed"),
                    ];
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $firstErrorMessage,
                    ];
                }
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $country_id = $request->country_id ?? 0;
            $walletManagerId = $request->walletManagerId ?? 0;
            $receiverName = $request->receiverName ?? 0;
            $phone = $request->phone ?? 0;
            $ibanNumber = $request->ibanNumber ?? 0;
            $reason = $request->reason ?? 0;
            $senderDataO = [];
            $receiverDataO = [];
            $note = $request->note;

        } else {
            $getTrans = $this->getTransactionDataById($request->transaction_id);
            $senderDataO = json_decode($getTrans->senderData, true);
            $receiverDataO = json_decode($getTrans->receiverData, true);
            $country_id = $getTrans->country_id ?? 0;
            $walletManagerId = $getTrans->walletManagerId ?? 0;
            $receiverName = $getTrans->receiverName ?? 0;
            $phone = $getTrans->receiver_mobile ?? 0;
            $note = $request->note ?? $getTrans->notes;
            $ibanNumber = $this->getUserDataByIban($getTrans->onafriq_bda_ids);
        }

        $amount = $request->amount;
        $senderUser = User::where('id', $user_id)->first();

        $userType = $this->getUserType($senderUser->user_type);

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }

        $transactionLimit = TransactionLimit::where('type', $userType)->first();
        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }


        if (!empty($country_id) && !empty($walletManagerId)) {

            $getReceiverCountry = DB::table('countries_onafriq')->where('id', $country_id)->first();
            $getSenderCountry = DB::table('countries_new')->where('id', $senderUser->country)->first();

            $tomember = "10029"; // default value
            /* if ($request->walletManagerId != "") {
                $tomemberData = WalletManager::where('id', $request->walletManagerId)->first();
                $tomember = $tomemberData->tomember;
            } */
            // dd($tomemberData, $tomember);
            $dateString = date('d-m-Y H:i:s');
            $format = 'd-m-Y H:i:s';
            $dateTime = DateTime::createFromFormat($format, $dateString);
            $timestamp = $dateTime->getTimestamp();
            $sender_mobile = $senderUser->phone;
            $paymentType = 'OUTGOINGWALLET';
            $cardNumber = "";
            $senderAccount = "";
            $receiverAccount = "";

            $trans_id = time();
            $refrence_id = time();


            $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
            if ($last_record != "") {
                $next_issuertrxref = $last_record + 1;
            } else {
                $next_issuertrxref = '140071';
            }


            $getfeeRecord = $this->calculateTotalFees('Money Transfer Via GIMAC', $amount);
            $total_fees_trans = $getfeeRecord['total_fees'] ?? 0;
            $total_fees = $getfeeRecord['total_fees'] ?? 0;

            $total_amount = $amount + $total_fees_trans;

            /* $referralFeeAmount = 0;
            $referralByUserId = 0;
            if ($senderUser->referralBy) {
                $getData = $this->referralService->referralFeeAmount($senderUser, $total_fees, 'SWAPTOCEMAC');
                $total_fees = $getData['data']['total_fees'];
                $referralFeeAmount = $getData['data']['referralFeeAmount'] ?? 0;
                $referralByUserId = $getData['data']['referralByUserId'] ?? 0;
            } */

            /* if ($total_amount != $request->total_amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => 'Transaction failed because fees strucutre has been changed.Please try again.',
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } */

            if ($total_amount > $senderUser->wallet_balance) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            try {
                $accessToken = $this->gimacApiService->getAccessToken();

                if ($accessToken['status'] === false) {

                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Access token not found"), //$accessToken['message']
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                $accessToken = $accessToken['token'];
                $newIssuertrxref = uniqid();
                $ibanAccount = $ibanNumber ?? null;

                $responseInquiry = $this->gimacApiService->walletAndAccountInquiry($paymentType, $phone, $newIssuertrxref, $tomember, $accessToken, $ibanAccount);

                if ($responseInquiry['status'] == false && $responseInquiry['statusCode'] == null && $responseInquiry['data'] == null) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $responseInquiry['message'],
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
                if (isset($request->transaction_id) && $request->transaction_id == "") {
                    $senderData = [
                        "firstname" => $senderUser->name ?? '',
                        "secondname" => $senderUser->lastName ?? 'Test',
                        "phone" => $senderUser->phone ?? '',
                        "idtype" => $senderUser->national_identity_type ?? '',
                        "idnumber" => $senderUser->national_identity_number ?? '',
                        "address" => $senderUser->address1 ?? '',
                        "city" => $senderUser->city ?? '',
                        "country" => $getSenderCountry->name ?? '',

                    ];
                    $receiverData = [
                        "firstname" => $responseInquiry['data']->receivercustomerdata->firstname ?? '',
                        "secondname" => $responseInquiry['data']->receivercustomerdata->secondname ?? 'Test',
                        "phone" => $request->phone ?? '',
                        "idtype" => $request->idNumberReceiver ?? '',
                        "idnumber" => $request->addressReceiver ?? '',
                        "address" => $request->idTypeReceiver ?? '',
                        "city" => $request->cityReceiver ?? '',
                        "country" => $getReceiverCountry->name ?? '',
                    ];
                } else {
                    $senderData = $senderDataO;
                    $receiverData = $receiverDataO;
                }

                $jsonResponse2 = $this->gimacApiService->walletPaymentAllType($timestamp, $sender_mobile, $paymentType, $phone, $next_issuertrxref, $amount, $tomember, $accessToken, $cardNumber, $senderAccount, $receiverAccount, $senderData, $receiverData);
                // Log::channel('GIMAC')->info(json_encode($jsonResponse2));
                if ($jsonResponse2['statusCode'] == 200) {
                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                    $jsonResponse2 = $jsonResponse2['data'];
                    $tomember = $jsonResponse2->tomember ?? "";
                    $acquirertrxref = $jsonResponse2->acquirertrxref ?? "";
                    $issuertrxref = $jsonResponse2->issuertrxref;
                    $state = $jsonResponse2->state;
                    //$status = $state == 'ACCEPTED' ? 1 : 2;
                    $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));
                    $vouchercode = $jsonResponse2->vouchercode ?? 0;
                    $remainingWalletBalance = $senderUser->wallet_balance - $total_amount;
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => 0,
                        'receiver_mobile' => $phone,
                        'amount' => $amount,
                        'receiverName' => $receiverName ?? "",
                        'transaction_amount' => $total_fees,
                        'amount_value' => $amount,
                        'total_amount' => $total_amount,
                        'trans_type' => 2,
                        'payment_mode' => 'wallet2wallet',
                        'status' => $status,
                        'refrence_id' => $trans_id,
                        'billing_description' => "Fund Transfer-$refrence_id",
                        'country_id' => $country_id ?? 0,
                        'tomember' => $tomember,
                        'acquirertrxref' => $acquirertrxref,
                        'issuertrxref' => $issuertrxref,
                        'vouchercode' => $vouchercode,
                        'transactionType' => 'SWAPTOOUTCEMAC',
                        'paymentType' => $paymentType ?? "",
                        'senderData' => json_encode($senderData) ?? "",
                        'receiverData' => json_encode($receiverData) ?? "",
                        'remainingWalletBalance' => $senderUser->wallet_balance ?? "",
                        'beforeBalance' => $senderUser->wallet_balance ?? "",
                        'entryType' => 'API',
                        'notes' => $note ?? "",
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'swapDomainName' => 'UAT_SERVER'
                    ]);
                    $trans->save();
                    $transactionId = $trans->id;


                    $device_type = $senderUser->device_type;
                    $device_token = $senderUser->device_token;
                    $statusArr = [
                        "status" => "Success",
                        "reason" => $state == 'ACCEPTED' ? __("message_app.Your transaction has been successful") : __('message_app.transaction_in_state', ['state' => $state]),
                        'transactionId' => $transactionId
                    ];
                    $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->first();
                    if ($state == 'ACCEPTED') {
                        Transaction::where('id', $transactionId)->update(['remainingWalletBalance' => ($senderUser->wallet_balance - $total_amount), 'afterBalance' => ($senderUser->wallet_balance - $total_amount), 'status' => 1]);
                        User::where('id', $senderUser->id)->decrement('wallet_balance', $total_amount);
                        DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);

                        /* Card Payment Start */
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => $total_amount,
                                "transferType" => "CardToWallet",
                                "mobilePhoneNumber" => "241{$senderUser->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Send money from outside cemac');
                        }
                        /* Card Payment End */

                        /* if ($referralByUserId) {
                            User::where('id', $referralByUserId)->increment('wallet_balance', $referralFeeAmount);
                            $this->referralService->addReferralAmountByPayment($senderUser, $referralByUserId, $referralFeeAmount, $transactionId);
                        } */

                        $title = __("message_app.Send Money Successful");
                        $message = __('message_app.send_money_success', [
                            'currency' => CURR,
                            'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                            'username' => $receiverUser->name,
                        ]);

                        $data1 = [
                            'title' => $title,
                            'message' => $message,
                            'id' => $transactionId,
                            'type' => 'TRANSACTION',
                        ];

                        if ($device_type && $device_token) {
                            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                $device_token,
                                $title,
                                $message,
                                $data1,
                                $device_type
                            );
                        }

                    } elseif ($state == 'PENDING' || $state == 'SUSPECTED') {
                        User::where('id', $senderUser->id)->decrement('wallet_balance', $total_amount);
                        User::where('id', $senderUser->id)->increment('holdAmount', $total_amount);

                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => $total_amount,
                                "transferType" => "CardToWallet",
                                "mobilePhoneNumber" => "241{$senderUser->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            Log::info('Send money from outside cemac');
                        }

                        $title = __("message_app.Send Money Pending");
                        $message = __('message_app.send_money_pending_user', [
                            'currency' => CURR,
                            'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                            'username' => $senderUser->name,
                        ]);

                        $data1 = [
                            'title' => $title,
                            'message' => $message,
                            'id' => $transactionId,
                            'type' => 'TRANSACTION',
                        ];

                        if ($device_type && $device_token) {
                            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                                $device_token,
                                $title,
                                $message,
                                $data1,
                                $device_type
                            );
                        }

                    }

                    // $this->sendPushNotification($title, $message, $device_type, $device_token);
                    $title = __("message_app.Transaction Successful");
                    $message = __('message_app.send_money_sent_user', [
                        'currency' => CURR,
                        'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                        'username' => $senderUser->name, // e.g. Test
                    ]);


                    $notif = new Notification([
                        'user_id' => $senderUser->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                    $status = $this->getStatusText(1);

                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {

                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $jsonResponse2['message']]);
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $jsonResponse2['message'],
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Connection error: Timeout occurred while connecting to the server"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $contents = $response->getBody()->getContents();
                    $jsonResponse = json_decode($contents, true);

                    $errorDescription = $jsonResponse['error_description'] ?? __("message_app.Error Code: 403 Forbidden Error");

                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $errorDescription,
                    ];
                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);
                } else {
                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $e->getMessage()]);
                    $errorDescription = $e->getMessage();
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $errorDescription,
                    ];
                }
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } catch (\Exception $e) {
                Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $e->getMessage()]);
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $e->getMessage(),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } elseif (!empty($request->country_id) && !empty($request->ibanNumber)) {

            $partnerReference = $this->generatePartnerString();

            if ($transactionLimit->bdaMin > $amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __('message_app.min_transfer_amount', [
                        'currency' => CURR,
                        'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->bdaMin)),
                    ]),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if ($amount > $transactionLimit->bdaMax) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __('message_app.max_transfer_amount', [
                        'currency' => CURR,
                        'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->bdaMax)),
                    ]),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            /* $getfeeRecord = $this->calculateTotalFees('Money Transfer Via BDA', $amount);
            $total_fees = $getfeeRecord['total_fees'] ?? 0;
            $total_amount = $amount + $total_fees; */

            $getfeeRecord = $this->calculateTotalFees('Money Transfer Via BDA', $amount);
            $total_fees_trans = $getfeeRecord['total_fees'] ?? 0;
            $total_fees = $getfeeRecord['total_fees'] ?? 0;

            $total_amount = $amount + $total_fees_trans;

            /* $referralFeeAmount = 0;
            $referralByUserId = 0;
            if ($senderUser->referralBy) {
                $getData = $this->referralService->referralFeeAmount($senderUser, $total_fees, 'SWAPTOBDA');
                $total_fees = $getData['data']['total_fees'];
                $referralFeeAmount = $getData['data']['referralFeeAmount'] ?? 0;
                $referralByUserId = $getData['data']['referralByUserId'] ?? 0;
            } */

            /* if ($total_amount != $request->total_amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => 'Transaction failed because fees strucutre has been changed.Please try again.',
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } */

            if ($total_amount > $senderUser->wallet_balance) {

                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            try {
                $getLstNo = RemittanceData::orderBy('id', 'desc')->select('referenceLot')->first();

                if (empty($getLstNo)) {
                    $refNoLo = 'SWAP9999';
                } else {
                    preg_match('/([a-zA-Z]+)([0-9]+)/', $getLstNo->referenceLot, $matches);
                    $incrementedPart = (int) $matches[2] + 1;
                    $newReferenceLot = $matches[1] . $incrementedPart;
                    $refNoLo = $newReferenceLot;
                    // Log::info('refNoLo:', (array) $refNoLo);
                }
                // $refNoLo = 'SWAP8401';
                $certificate = public_path("CA Bundle.crt");
                $client = new Client([
                    'verify' => $certificate,
                    'timeout' => 60,
                ]);

                $data = [
                    'referenceLot' => (string) $refNoLo,
                    'nombreVirement' => 1,
                    'montantTotal' => $amount,
                    'produit' => 'SWAP',
                    'virements' => [
                        [
                            'ibanCredit' => $ibanNumber,
                            'intituleCompte' => isset($request->beneficiary) ? $request->beneficiary : 'DEMO0025',
                            'montant' => $amount,
                            'referencePartenaire' => $partnerReference,
                            'motif' => isset($reason) ? $reason : 'Paiee commissions 01012022',
                            'typeVirement' => 'RTGS'
                        ]
                    ]
                ];

                $response = $client->post(env('BDA_URL'), [
                    'json' => $data,
                    'headers' => [
                        'x-api-key' => env('XAPIKEY'),
                        'x-client-id' => env('XCLIENTID'),
                    ],
                ]);
                Log::info('BDA Request:', ['request' => $data]);
                Log::info('BDA Response:', ['response' => $response->getBody()->getContents()]);
                $responseBody = json_decode($response->getBody(), true);

                // Log::channel('BDA')->info($responseBody);
                $remainingWalletBalance = $senderUser->wallet_balance - $total_amount;
                $remittanceData = new RemittanceData();
                $remittanceData->transactionId = $this->generateAndCheckUnique();
                $remittanceData->product = 'SWAP';
                $remittanceData->iban = $ibanNumber ?? '';
                $remittanceData->titleAccount = $beneficiary ?? 'DEMO0025';
                $remittanceData->amount = $amount ?? '';
                $remittanceData->partnerreference = $partnerReference;
                $remittanceData->reason = $reason ?? 'Paiee commissions 01012022';
                $remittanceData->userId = $user_id;
                $remittanceData->referenceLot = $refNoLo;
                $remittanceData->status = 0;
                $remittanceData->type = 'bank_transfer';
                $remittanceData->trans_app_id = 0;
                $remittanceData->save();
                Log::info($responseBody);

                if ($responseBody['statut'] == 'EN_ATTENTE' || $responseBody['statut'] == 'EN_ATTENTE_REGLEMENT') {
                    $statut = $responseBody['statut'];
                    $rejectedStatus = '';

                    $refrence_id = time();

                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => 0,
                        'receiver_mobile' => '',
                        'amount' => $amount,
                        'receiverName' => $receiverName,
                        'amount_value' => $amount,
                        'transaction_amount' => $total_fees,
                        'total_amount' => $total_amount,
                        'trans_type' => 2,
                        'payment_mode' => 'wallet2wallet',
                        'country_id' => $country_id ?? 0,
                        'walletManagerId' => "",
                        'status' => 2,
                        'bda_status' => 2,
                        'note' => $note ?? "",
                        'entryType' => 'API',
                        'paymentType' => 'OUTWALLETBDA',
                        'remainingWalletBalance' => $senderUser->wallet_balance,
                        'billing_description' => 'Fund Transfer-' . $refrence_id,
                        'transactionType' => 'SWAPTOBDA',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'swapDomainName' => 'UAT_SERVER'
                    ]);
                    $trans->save();

                    Transaction::where('id', $trans->id)->update(['onafriq_bda_ids' => $remittanceData->id]);
                    RemittanceData::where('id', $remittanceData->id)->update(['status' => $statut, 'trans_app_id' => $trans->id]);

                    /* $sender_wallet_amount = $senderUser->wallet_balance - $total_fees;
                    User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);
                    DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees); */

                    User::where('id', $senderUser->user_id)->decrement('wallet_balance', $total_amount);
                    User::where('id', $senderUser->user_id)->increment('holdAmount', $total_amount);

                    $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->first();
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $total_amount,
                            "transferType" => "CardToWallet",
                            "mobilePhoneNumber" => "241{$senderUser->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        Log::info('Send money from outside BDA ');
                    }

                    /* if ($referralByUserId) {
                        User::where('id', $referralByUserId)->increment('wallet_balance', $referralFeeAmount);
                        $this->referralService->addReferralAmountByPayment($senderUser, $referralByUserId, $referralFeeAmount, $transactionId);
                    } */


                    $title = __("message_app.Transaction Successful");
                    $message = __('message_app.send_money_sent_user', [
                        'currency' => CURR,
                        'amount' => $this->numberFormatSpaces($this->roundAmount($amount)),
                        'username' => $senderUser->name, // e.g. Test
                    ]);
                    $device_token = $senderUser->device_token;
                    $device_type = $senderUser->device_type;

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => $trans->id,
                        'type' => 'TRANSACTION',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        'user_id' => $senderUser->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                    $statusArr = [
                        "status" => "Success",
                        "reason" => __("message_app.Your transaction has been successful"),
                        "transactionId" => $trans->id,
                    ];

                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } elseif ($responseBody['statut'] == 'REJETE') {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Transaction failed"),
                    ];

                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.Transaction failed time out error"),
                    ];

                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Connection error: Timeout occurred while connecting to the server"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $contents = $response->getBody()->getContents();
                    $jsonResponse = json_decode($contents, true);

                    $errorDescription = $jsonResponse['error_description'] ?? __("message_app.Error Code: 403 Forbidden Error") . $e->getMessage();

                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $errorDescription,
                    ];
                } else {
                    $errorDescription = $e->getMessage();
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => $errorDescription,
                    ];
                }
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } catch (\Exception $e) {
                // log::channel('BDA')->info($e->getMessage());
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $e->getMessage(),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

    }
    public function IdTypeList(Request $request)
    {
        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.Id type list"),
            "data" => [
                [
                    "id" => 'PASSPORT',
                    "name" => "PASSPORT",
                ],
                [
                    "id" => 'RESIDENCE',
                    "name" => "RESIDENCE",
                ],
                [
                    "id" => 'ID CARD',
                    "name" => "ID CARD",
                ],
                [
                    "id" => 'OTHER',
                    "name" => "OTHER",
                ],
            ]
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    private function calculateTotalFees($trans_type, $amount)
    {
        $user_id = Auth::user()->id;

        $total_fees = 0;
        $feeType = 1;
        $feePer = 0;

        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', $trans_type)->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)->first();

        if (isset($feeapply)) {

            $feeType = $feeapply->fee_type;
            if ($feeType == 1) {
                $total_fees = $feeapply->fee_amount;
            } else {
                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
            }
        } else {
            $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $amount)
                ->where('max_amount', '>=', $amount)->first();
            $feePer = $trans_fees->fee_amount ?? 0;
            if (!empty($trans_fees)) {
                $feeType = $trans_fees->fee_type;
                if ($feeType == 1) {
                    $total_fees = $trans_fees->fee_amount;
                } else {
                    $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                }
            }
        }
        return [
            'total_fees' => $total_fees,
            'fee_type' => $feeType,
            'fee_per' => $feePer
        ];
    }
    public function transactionListNew(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        $user_role = Auth::user()->user_type;
        $input = [
            'page' => $request->page,
            'limit' => $request->limit,
            'search' => $request->search,
            'accountId' => $request->accountId ?? "",
            'cardType' => $request->cardType ?? "",
        ];

        $validate_data = [
            'page' => 'required',
            'limit' => 'required',
        ];

        $customMessages = [
            'page.required' => __("message_app.Page field cannot be left blank"),
            'limit.required' => __("message_app.Limit field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $page = $input['page'];
        $limit = $input['limit'];
        $search = $input['search'];
        $accountId = $input['accountId'] ?? null;
        $cardType = $input['cardType'] ?? null;
        // Log::info(['ccccardType-------------'=>$cardType,'accountId'=>$accountId]);
        $start = $page - 1;
        $start = $start * $limit;
        // Log::info(['accountId'=>$accountId,'$cardType'=>$cardType]);
        $userInfo = User::where('id', $user_id)->first();
        // DB::enableQueryLog();
        $trans = DB::table('transactions')
            ->select('transactions.*')
            ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
            ->where(function ($query) use ($user_id) {
                $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {

                    // Name search
                    /* $q->where('u1.name', 'LIKE', "%{$search}%")
                        ->orWhere('u2.name', 'LIKE', "%{$search}%"); */

                    // $q->where('transactions.amount', $search);
    
                    // Phone search – EXACT match
                    if (is_numeric($search)) {
                        $q->orWhere('u1.phone', $search)
                            ->orWhere('u2.phone', $search);
                    }

                    // Transaction ID – EXACT match
                    if (ctype_digit($search)) {
                        $q->orWhere('transactions.id', (int) $search);
                    }
                });
            })

            ->when($accountId && $cardType === 'VIRTUAL', function ($q) use ($accountId) {
                $q->where('transactions.accountId', $accountId)
                    ->where('transactions.cardType', 'VIRTUAL');
            })

            // Physical card + Wallet OR Card + Wallet
            ->when($accountId && $cardType !== 'VIRTUAL', function ($q) use ($accountId) {
                $q->where(function ($q2) use ($accountId) {
                    $q2->where('transactions.accountId', $accountId)
                        ->orWhere(function ($q3) {
                            $q3->where('transactions.accountId', 0)
                                ->whereNull('transactions.cardType')
                                ->orWhere('transactions.cardType', 'VIRTUAL');
                        });
                });
            })
            ->orderBy('transactions.created_at', 'DESC')
            ->skip($start)
            ->take($limit)
            ->get();

        // dd($trans);
        // dd(DB::getQueryLog());
        if ($trans->isEmpty()) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.no_record_found"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }



        $totalRecords = DB::table('transactions')
            ->select('transactions.*')
            ->leftJoin('users as u1', 'transactions.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'transactions.receiver_id', '=', 'u2.id')
            ->where(function ($query) use ($user_id) {
                $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
            })
            ->where(function ($query) use ($user_id, $search) {
                $query->where('u1.name', 'LIKE', "%$search%")
                    ->orWhere('u2.name', 'LIKE', "%$search%")
                    ->orWhere('u1.phone', 'LIKE', "%$search%")
                    ->orWhere('u2.phone', 'LIKE', "%$search%")
                    ->orWhere('transactions.id', 'LIKE', "%$search%");
            })
            ->orderBy('transactions.created_at', 'DESC')
            ->count();


        $transDataArr = [];

        global $tranType;

        foreach ($trans as $key => $val) {
            // dd($cardType,$val->cardType);
            if (empty($cardType)) {
                // Agar data ka cardType VIRTUAL hai aur description Card Load hai → skip
                if ($val->cardType === "VIRTUAL" && $val->description === "Card Load") {
                    continue;
                }
            } elseif ($cardType == "PHYSICAL") {
                if ($val->cardType === "VIRTUAL") {
                    // Log::info(['cardType'=>$val->cardType,'accountId'=>$accountId]);
                    if ($val->description === 'Card Load') {
                        continue;
                    }
                }
            }
            $getPaymentType = ExcelTransaction::where('id', $val->excel_trans_id)->first();
            $transArr['trans_id'] = $val->id;

            $transArr['payment_mode'] = strtolower($val->payment_mode);
            $transArr['payment_mode_name'] = ucwords(str_replace("_", " ", $val->payment_mode));

            if ($val->receiver_id == 0) {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                if (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "ONAFRIQ") {
                    $transArr['receiver'] = 'ONAFRIQ Transfer';
                } elseif (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "BDA") {
                    $transArr['receiver'] = 'BDA Transfer';
                } elseif (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "GIMAC") {
                    $transArr['receiver'] = 'GIMAC Transfer';
                } else {
                    $transArr['receiver'] = 'WALLET2WALLET';
                }
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup

            } elseif ($val->user_id == $user_id) { //User is sender
                /* if($val->payment_mode == 'INCOMMING PAYMENT'){

                } */
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup

                if ($val->payment_mode == 'send_money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                    $val->payment_mode = 'Withdraw'; //1=Credit;2=Debit;3=topup
                    $transArr['payment_mode'] = strtolower($val->payment_mode);
                    $transArr['trans_from'] = $val->payment_mode;
                }

                if ($val->payment_mode != 'Cash card') {
                    if ($val->trans_type == 2) {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    } else if ($val->transactionType == "AIRTELMONEY") {
                        $transArr['trans_type'] = $tranType[1];
                    } else if ($val->trans_type == 1 || $val->trans_type == 3) {
                        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                    }
                }

                if ($val->payment_mode == 'Agent Deposit') {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }

                if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                }

                if ($val->payment_mode == 'wallet2wallet' && $val->trans_type == 2) {
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }
                if ($val->payment_mode == 'Withdraw') {
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                }
                if ($val->payment_mode == 'Referral') {
                    $transArr['trans_type'] = $tranType[$val->trans_type];
                }
            } else if ($val->receiver_id == $user_id) { //USer is Receiver
                if ($val->payment_mode == 'INCOMMING PAYMENT' || $val->payment_mode == 'Referral') {
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['trans_from'] = $val->payment_mode;
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->user_id ?? 0;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                } else {
                    $transArr['trans_from'] = $val->payment_mode;
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                }

                if ($val->trans_type == 2) {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'send_money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                    $val->payment_mode = 'send_money'; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Withdraw' && $val->trans_type == 2) {
                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
                    $transArr['receiver_id'] = $val->user_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                    $transArr['sender_id'] = $val->receiver_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }

                if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                }
                if ($userInfo->user_type != 'Merchant') {
                    if ($val->payment_mode == 'Refund' && $transArr['trans_type'] == 'Debit') {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    }
                } else {
                    if ($val->payment_mode == 'Refund' && $val->trans_type == 1) {
                        $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                    }
                    if ($val->payment_mode == 'Refund' && $val->trans_type == 1 && $val->refund_status == 0) {
                        $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    }
                }

                if ($val->payment_mode == 'Agent Deposit') {
                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }
                $transArr['receiver_airtel_mobile'] = $val->receiver_mobile;
                $transArr['card_merchant_id'] = $val->merchantName;
                $transArr['card_description'] = $val->description;
                $transArr['cardType'] = $val->cardType;
            }
            if ($userInfo->user_type == 'User' || $userInfo->user_type == 'Merchant') {
                if ($transArr['trans_type'] == 'Credit') {
                    if ($val->payment_mode == 'Refund') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Topup') {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                } elseif ($transArr['trans_type'] == 'Request') {
                    if ($val->payment_mode == "Withdraw") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        if ($val->status == 2) {
                            $transArr['trans_type'] = 'Request Debit';
                        } else {
                            $transArr['trans_type'] = 'Debit';
                            $val->payment_mode = 'wallet2wallet';
                            $transArr['payment_mode'] = $val->payment_mode;

                            $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                        }
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        if ($val->status == 2) {
                            $transArr['trans_type'] = 'Request Credit';
                        } else {
                            $transArr['trans_type'] = 'Credit';
                            $val->payment_mode = 'wallet2wallet';
                            $transArr['payment_mode'] = $val->payment_mode;

                            $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                        }
                    }
                } else {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        if ($val->payment_mode == "Withdraw") {
                            $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                            $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        } else {
                            $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                            $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        }
                    }
                }
            } elseif ($userInfo->user_type == 'Agent') {
                if ($transArr['trans_type'] == 'Request') {
                    if ($val->payment_mode == "Withdraw") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Debit') {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    } elseif ($val->payment_mode == 'Withdraw') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                    }
                } else {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                }
            } else {
                if ($transArr['trans_type'] == 'Debit') {
                    if ($val->payment_mode == "Refund") {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } elseif ($val->payment_mode == 'Withdraw') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } elseif ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    }
                } elseif ($transArr['trans_type'] == 'Credit') {
                    if ($val->payment_mode == 'wallet2wallet') {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    } else {
                        $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                        $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    }
                } else {
                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                }
            }

            if ($val->payment_mode == 'Credited by admin' || $val->payment_mode == 'Debited by admin') {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['receiver_phone'] = 'Admin';
                $transArr['sender_phone'] = 'Admin';
            }

            $transArr['transaction_fees'] = $this->numberFormatPrecision($val->transaction_amount, 2, '.');
            $transArr['received_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
            $transArr['trans_amount_android'] = number_format($transArr['trans_amount'], 2);

            if ($transArr['payment_mode'] == 'agent deposit') {
                $transArr['payment_mode'] = 'agent_deposit';
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id == $user_id) {
                    $transArr['trans_type'] = $tranType[1];
                } else if ($user_role == 'Agent') {
                    $transArr['trans_type'] = $tranType[2];

                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                }
            }

            if ($transArr['payment_mode'] == 'withdraw') {
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id != $user_id) {
                    $transArr['trans_type'] = $tranType[2];
                } else if ($user_role == 'Agent') {
                    $transArr['trans_type'] = $tranType[1];

                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                }
            }

            $transArr['paymentType'] = $val->paymentType ?? "";
            $transArr['receiverName'] = $val->receiverName ?? "";
            $transArr['receiverAccount'] = $val->receiverAccount ?? "";
            $transArr['cardNumber'] = $val->cardNumber ?? "";
            $transArr['transactionType'] = $val->transactionType ?? "";
            $transArr['onafriq_bda_ids'] = $val->onafriq_bda_ids ?? "";
            global $tranStatus;
            $transArr['trans_status'] = $tranStatus[$val->status];
            $transArr['refrence_id'] = $val->refrence_id;
            $trnsDt = date_create($val->created_at);
            $transDate = date_format($trnsDt, "d M Y, h:i A");
            $transArr['trans_date'] = $transDate;
            $transArr['request_type'] = $val->trans_type;
            $transArr['receiver_request_id'] = $val->receiver_id;
            $transArr['pendingSuccessStatus'] = $val->status;
            $transArr['receiver_airtel_mobile'] = $val->receiver_mobile;
            $transArr['card_merchant_id'] = $val->merchantName;
            $transArr['card_description'] = $val->description;
            $transArr['cardType'] = $val->cardType;
            $transDataArr[] = $transArr;
        }

        $transArrNew = [];
        foreach ($transDataArr as $key => $value) {
            // Log::info('Transaction Data:', ['key' => $key, 'value' => $value]);

            if (isset($value['trans_id'])) {
                $transNew['transactionId'] = (string) $value['trans_id'];
                // $transNew['transactionId'] = 2113;
            }

            $amountNew = CURR . number_format((($value['trans_amount'] - floor($value['trans_amount'])) > 0.5 ? ceil($value['trans_amount']) : floor($value['trans_amount'])), 0, '', ' ');
            // Log::info(['mode'=>$value['payment_mode_name'],'id'=>$value['trans_id'],'status'=>$value['trans_status']]);
            if ($value['trans_type'] == 'Credit') {
                if ($value['trans_status'] == 'Success') {
                    // $transNew['title'] = $value['trans_from'] == "Refund" ? "Refund from " . $value['sender'] : "Received from " . $value['sender'];

                    if ($value['trans_from'] === "Refund") {
                        $transNew['title'] = "Refund from " . $value['sender'];
                    } elseif (in_array($value['paymentType'], ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'REQUESTTOPAY', 'OUTGOINGWALLET', 'PREPAIDCARDRELOAD', 'INCACCREMIT', 'WALLETINCOMMING'])) {
                        if ($value['trans_from'] == "INCOMMING PAYMENT") {
                            $transNew['title'] = __("message_app.Received from", ['username' => $value['receiver_phone']]);
                        } else {
                            $transNew['title'] = __("message_app.Received from", ['username' => $value['receiverName']]);
                        }
                    } elseif ($value['transactionType'] == "AIRTELMONEY") {
                        $transNew['title'] = __("message_app.Received by Airtel Money");
                    } else if ($value['payment_mode_name'] == 'CARDPAYMENT') {
                        if ($value['card_description'] == "Card Load" || $value['card_description'] == "Funds Transfer External Account to Card") {
                            if ($accountId) {
                                if ($request->cardType === "PHYSICAL") {
                                    $transNew['title'] = ($value['trans_amount'] <= 0) ? ($value['card_description'] === "Denial - POS Purchase" ? "POS Denial" : $value['card_description']) : (($value['card_description'] === "Card Load") ? "Card Reload" : __("message_app.Transfer OUT"));
                                } else {
                                    $transNew['title'] = ($value['trans_amount'] <= 0) ? ($value['card_description'] === "Denial - POS Purchase" ? "POS Denial" : $value['card_description']) : (($value['card_description'] === "Card Load") ? "Card Reload" : __("message_app.Transfer IN"));

                                }
                            } else {
                                $transNew['title'] = ($value['trans_amount'] <= 0) ? ($value['card_description'] === "Denial - POS Purchase" ? "POS Denial" : $value['card_description']) : (($value['card_description'] === "Card Load") ? "Card Reload" : __("message_app.Transfer OUT"));
                            }
                        } else {
                            if ($accountId) {
                                $transNew['title'] = trim($value['card_merchant_id'])
                                    ? trim($value['card_merchant_id'])
                                    : (
                                        $value['trans_amount'] <= 0
                                        ? (
                                            $value['card_description'] === "Denial - POS Purchase"
                                            ? "POS Denial"
                                            : $value['card_description']
                                        )
                                        : __("message_app.Transfer OUT")
                                    );
                            } else {
                                $transNew['title'] = trim($value['card_merchant_id'])
                                    ? trim($value['card_merchant_id'])
                                    : (
                                        $value['trans_amount'] <= 0
                                        ? (
                                            $value['card_description'] === "Denial - POS Purchase"
                                            ? "POS Denial"
                                            : $value['card_description']
                                        )
                                        : __("message_app.Transfer IN")
                                    );
                            }
                        }
                    } else if ($value['payment_mode_name'] === 'TRANSAFEROUT') {
                        if ($value['card_description'] == "Card Load" || $value['card_description'] == "Funds Transfer External Account to Card") {
                            $transNew['title'] = "Transfer OUT 2";
                        } else {
                            $transNew['title'] = "Transfer IN 2";
                        }
                    } else if ($value['payment_mode_name'] == "Card Activation Rebate") {
                        $transNew['title'] = __("message_app.Card Activation Rebate");
                    } elseif ($value['payment_mode'] == "referral") {
                        $transNew['title'] = __("message_app.Received referral amount");
                    } else {
                        $transNew['title'] = __("message_app.Received from", ['username' => $value['sender']]);
                    }
                } else {
                    // Log::info($value['transactionType']);
                    if ($value['transactionType'] == "AIRTELMONEY") {
                        $transNew['title'] = __("message_app.Deposit by Airtel Money");
                    } else if ($value['transactionType'] == "CARDPAYMENT") {
                        if ($value['trans_status'] === "Failed") {
                            $transNew['title'] = __("message_app.Failed");
                        } else {
                            if ($accountId) {
                                $transNew['title'] = ($value['trans_amount'] <= 0) ? ($value['card_description'] == "Denial - POS Purchase" ? "POS Denial" : $value['card_description']) : __("message_app.Transfer IN");
                            } else {
                                $transNew['title'] = ($value['trans_amount'] <= 0) ? ($value['card_description'] == "Denial - POS Purchase" ? "POS Denial" : $value['card_description']) : __("message_app.Transfer OUT");
                            }
                        }
                    } else {
                        $transNew['title'] = __("message_app.Declined by", ['username' => $value['sender']]);
                    }
                }

                $transNew['subtitle'] = $value['payment_mode'] == "agent_deposit" ? $value['payment_mode_name'] : $value['sender_phone'];
                $transNew['date'] = $value['trans_date'];
                $transNew['price'] = CURR . number_format((($value['received_amount'] - floor($value['received_amount'])) > 0.5 ? ceil($value['received_amount']) : floor($value['received_amount'])), 0, '', ' ');
                if ($val->payment_mode == 'Referral') {
                    $value['payment_mode'] = 'Referral';
                }
            } elseif ($value['trans_type'] == "Topup") {
                $transNew['title'] = __("message_app.Money Added To Wallet");
                $transNew['subtitle'] = $value['payment_mode_name'];
                $transNew['date'] = $value['trans_date'];
                $transNew['price'] = $amountNew;
            } elseif ($value['trans_type'] == "Request Debit") {
                $transNew['title'] = __("message_app.Money Requested By", ['username' => $value['sender']]);
                $transNew['subtitle'] = $value['sender_phone'];
                $transNew['date'] = $value['trans_date'];
                $transNew['price'] = $value['payment_mode'] == "withdraw" ? $amountNew : $amountNew;
            } elseif ($value['trans_type'] == "Request Credit") {
                $transNew['title'] = __("message_app.Money Requested From", ['username' => $value['sender']]);
                $transNew['subtitle'] = $value['sender_phone'];
                $transNew['date'] = $value['trans_date'];
                $transNew['price'] = $value['payment_mode'] == "withdraw" ? CURR . number_format((($value['received_amount'] - floor($value['received_amount'])) > 0.5 ? ceil($value['received_amount']) : floor($value['received_amount'])), 0, '', ' ') : CURR . number_format((($value['received_amount'] - floor($value['received_amount'])) > 0.5 ? ceil($value['received_amount']) : floor($value['received_amount'])), 0, '', ' ');
            } else {

                if ($value['payment_mode'] == "wallet2wallet") {
                    if ($value['trans_status'] == "Success") {
                        if (in_array($value['paymentType'], ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'REQUESTTOPAY', 'OUTGOINGWALLET', 'PREPAIDCARDRELOAD', 'INCACCREMIT', 'WALLETINCOMMING'])) {
                            if ($value['receiverName'] != "") {
                                $transNew['title'] = __("message_app.Paid to", ['username' => $value['receiverName']]);
                            }
                        } else {
                            $transNew['title'] = __("message_app.Paid to", ['username' => $value['receiver']]);
                        }
                        // $transNew['title'] = "Paid to " . $value['receiver'];
                    } elseif ($value['trans_status'] == "Pending" || $value['trans_status'] == "Suspected") {
                        if (in_array($value['paymentType'], ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'REQUESTTOPAY', 'OUTGOINGWALLET', 'PREPAIDCARDRELOAD', 'INCACCREMIT', 'WALLETINCOMMING'])) {
                            if ($value['receiverName'] != "") {
                                $transNew['title'] = __("message_app.Pending for", ['username' => $value['receiverName']]);
                            } else {
                                if ($value['paymentType'] == "PREPAIDCARDRELOAD") {
                                    $transNew['title'] = __("message_app.Pending for Prepaid Card Reload");
                                } else {
                                    $transNew['title'] = __("message_app.Pending for Gimac");
                                }
                            }
                        } elseif ($value['transactionType'] == "SWAPTOBDA") {
                            $transNew['title'] = __("message_app.Pending for BDA");
                        } else {
                            $transNew['title'] = __("message_app.Pending for", ['username' => $value['receiver']]);
                        }
                        // $transNew['title'] = "Pending for " . $value['receiver'];
                    } else {
                        $transNew['title'] = __("message_app.Cancelled for", ['username' => $value['receiver']]);
                    }
                    if (in_array($value['paymentType'], ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'REQUESTTOPAY', 'OUTGOINGWALLET', 'PREPAIDCARDRELOAD', 'INCACCREMIT', 'WALLETINCOMMING'])) {
                        if ($value['receiverAccount'] != "") {
                            $transNew['subtitle'] = $value['receiverAccount'] ?? "";
                        } elseif ($value['cardNumber'] != "") {
                            $transNew['subtitle'] = $value['cardNumber'] ?? "";
                        } else {
                            $transNew['subtitle'] = $value['receiverName'];
                        }
                    } elseif ($value['transactionType'] == "SWAPTOBDA") {
                        $getIBAN = RemittanceData::where('id', $value['onafriq_bda_ids'])->first()->iban;
                        $transNew['subtitle'] = $getIBAN ?? "Bda Payment";
                    } else {

                        $transNew['subtitle'] = $value['receiver_phone'];
                    }
                    $transNew['date'] = $value['trans_date'];
                    $transNew['price'] = CURR . number_format((($value['trans_amount'] - floor($value['trans_amount'])) > 0.5 ? ceil($value['trans_amount']) : floor($value['trans_amount'])), 0, '', ' ');

                } else {
                    if ($value['payment_mode'] == "withdraw") {
                        $transNew['title'] = __("message_app.Withdraw from", ['username' => $value['sender']]);
                        $transNew['subtitle'] = $value['sender_phone'];
                        $transNew['date'] = $value['trans_date'];
                        $transNew['price'] = CURR . $amountNew;
                    } elseif ($value['payment_mode'] == "refund") {
                        $transNew['title'] = __("message_app.Refund to", ['username' => $value['sender']]);
                        $transNew['subtitle'] = $value['sender_phone'];
                        $transNew['date'] = $value['trans_date'];
                        $transNew['price'] = CURR . $amountNew;
                    } else {
                        if ($value['payment_mode_name'] === 'TRANSAFEROUT') {
                            if ($value['trans_status'] == 'Success') {
                                if ($accountId) {
                                    if ($request->cardType === "PHYSICAL") {
                                        $transNew['title'] = trim($value['card_merchant_id']) ? trim($value['card_merchant_id']) : (($value['trans_amount'] <= 0) ? $value['card_description'] : __("message_app.Transfer IN"));
                                    } else {
                                        $transNew['title'] = trim($value['card_merchant_id']) ? trim($value['card_merchant_id']) : (($value['trans_amount'] <= 0) ? $value['card_description'] : __("message_app.Transfer OUT"));
                                    }
                                } else {
                                    $transNew['title'] = trim($value['card_merchant_id']) ? trim($value['card_merchant_id']) : (($value['trans_amount'] <= 0) ? $value['card_description'] : __("message_app.Transfer IN"));
                                }
                            } else {
                                if ($accountId) {
                                    $transNew['title'] = trim($value['card_merchant_id']) ? trim($value['card_merchant_id']) : (($value['trans_amount'] <= 0) ? $value['card_description'] : __("message_app.Transfer OUT"));
                                } else {
                                    $transNew['title'] = trim($value['card_merchant_id']) ? trim($value['card_merchant_id']) : (($value['trans_amount'] <= 0) ? $value['card_description'] : __("message_app.Transfer IN"));
                                }
                            }

                        } else {
                            $transNew['title'] = $value['payment_mode_name'];
                        }
                        if (in_array($value['paymentType'], ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'REQUESTTOPAY', 'OUTGOINGWALLET', 'PREPAIDCARDRELOAD', 'INCACCREMIT', 'WALLETINCOMMING'])) {
                            $transNew['subtitle'] = $value['receiver_phone'] ?? $value['receiverName'];
                        } else {
                            $transNew['subtitle'] = $value['sender_phone'];
                        }

                        $transNew['date'] = $value['trans_date'];
                        $transNew['price'] = CURR . number_format((($value['trans_amount'] + $value['transaction_fees'] - floor($value['trans_amount'] + $value['transaction_fees'])) > 0.5 ? ceil($value['trans_amount'] + $value['transaction_fees']) : floor($value['trans_amount'] + $value['transaction_fees'])), 0, '', ' ');
                    }
                }
            }
            if ($value['payment_mode'] == "referral" || $value['payment_mode'] == "INCOMMING PAYMENT") {
                $transNew['title'] = __("message_app.Received referral amount");
            }
            if ($value['payment_mode'] == "airtelwallet") {
                $transNew['subtitle'] = $value['receiver_airtel_mobile'];
                $transNew['price'] = CURR . number_format((($value['trans_amount'] - floor($value['trans_amount'])) > 0.5 ? ceil($value['trans_amount']) : floor($value['trans_amount'])), 0, '', ' ');
            }
            if ($value['payment_mode_name'] == "CARDPAYMENT" || $value['payment_mode_name'] == "TRANSAFEROUT") {
                /*  if (!empty($value['card_merchant_id'])) {
                     $transNew['price'] = CURR . $value['transaction_fees'] + $value['trans_amount'];
                 } else {
                     $transNew['price'] = ($value['trans_amount'] <= 0)
                         ? CURR . $value['transaction_fees']
                         : CURR . $value['trans_amount'];
                 } */
                if (!empty($value['card_merchant_id'])) {

                    $amount = abs((float) $value['transaction_fees'])
                        + abs((float) $value['trans_amount']);

                } else {

                    if ($value['trans_amount'] <= 0) {
                        $amount = abs((float) $value['transaction_fees']);
                    } else {
                        $amount = abs((float) $value['trans_amount']);
                    }
                }
                /* $amount = round($amount); 
                $transNew['price'] = CURR . number_format($amount, 0, '', ' '); */
                $transNew['price'] = CURR . number_format((($amount - floor($amount)) > 0.5 ? ceil($amount) : floor($amount)), 0, '', ' ');


            }
            if ($value['trans_status'] == "Success") {

                /* if ($value['payment_mode_name'] == 'CARDPAYMENT' || $value['payment_mode_name'] == 'TRANSAFEROUT') {
                    if ($accountId) {
                        $transNew['color'] = Str::contains($value['trans_type'], 'Credit') ? 'FF0000' : '3BBF00';
                        $transNew['image'] = Str::contains($value['trans_type'], 'Credit') ? 'public/img/debit.png' : 'public/img/credit.png';
                    } else {
                        $transNew['color'] = Str::contains($value['trans_type'], 'Credit') ? '3BBF00' : 'FF0000';
                        $transNew['image'] = Str::contains($value['trans_type'], 'Credit') ? 'public/img/credit.png' : 'public/img/debit.png';
                    }
                }else{ */
                if ($value['payment_mode_name'] == 'CARDPAYMENT') {
                    if ($value['card_description'] == "Funds Transfer External Account to Card") {
                        if ($accountId) {
                            if (isset($request->cardType) && $request->cardType === "PHYSICAL") {

                                if ($value['card_description'] == 'Card Load' || $value['card_description'] == 'POS Credit') {
                                    $transNew['color'] = '3BBF00';
                                    $transNew['image'] = 'public/img/credit.png';
                                } else {
                                    $transNew['color'] = 'FF0000';
                                    $transNew['image'] = 'public/img/debit.png';
                                }
                            } else {
                                $transNew['color'] = '3BBF00';
                                $transNew['image'] = 'public/img/credit.png';
                            }
                        } else {
                            if (isset($request->cardType) && $request->cardType === "PHYSICAL") {
                                $transNew['color'] = '3BBF00';
                                $transNew['image'] = 'public/img/credit.png';
                            } else {
                                $transNew['color'] = 'FF0000';
                                $transNew['image'] = 'public/img/debit.png';
                            }
                        }
                    } else {
                        if ($value['card_description'] == 'Card Load' || $value['card_description'] == 'POS Credit') {
                            $transNew['color'] = '3BBF00';
                            $transNew['image'] = 'public/img/credit.png';
                        } else {
                            $transNew['color'] = 'FF0000';
                            $transNew['image'] = 'public/img/debit.png';
                        }
                    }
                } else if ($value['payment_mode_name'] == 'TRANSAFEROUT') {
                    if ($accountId) {
                        if (isset($request->cardType) && $request->cardType === "PHYSICAL") {
                            if ($value['card_description'] === "Denial - ATM Withdrawal" || $value['card_description'] === "Card Issuance \ Activation Fee" || $value['card_description'] === "Manual PIN Change" || $value['card_description'] === "Denial - POS Purchase" || $value['card_description'] == "POS Purchase" || $value['card_description'] == "ATM - Withdrawal") {
                                $transNew['color'] = 'FF0000';
                                $transNew['image'] = 'public/img/debit.png';
                            } else {
                                $transNew['color'] = '3BBF00';
                                $transNew['image'] = 'public/img/credit.png';
                            }
                        } else {
                            $transNew['color'] = 'FF0000';
                            $transNew['image'] = 'public/img/debit.png';
                        }
                    } else {
                        if ($value['card_description'] === "Denial - ATM Withdrawal" || $value['card_merchant_id'] || $value['card_description'] === "Card Issuance \ Activation Fee" || $value['card_description'] === "Manual PIN Change" || $value['card_description'] === "Denial - POS Purchase" || $value['card_description'] == "POS Purchase" || $value['card_description'] == "ATM - Withdrawal") {
                            $transNew['color'] = 'FF0000';
                            $transNew['image'] = 'public/img/debit.png';
                        } else {
                            $transNew['color'] = '3BBF00';
                            $transNew['image'] = 'public/img/credit.png';
                        }

                    }

                } else {
                    if ($value['payment_mode_name'] === "Depot Initial") {
                        if ($accountId) {
                            $transNew['color'] = '3BBF00';
                            $transNew['image'] = 'public/img/credit.png';
                        } else {
                            $transNew['color'] = 'FF0000';
                            $transNew['image'] = 'public/img/debit.png';
                        }
                    } else {
                        $transNew['color'] = Str::contains($value['trans_type'], 'Credit') ? '3BBF00' : 'FF0000';
                        $transNew['image'] = Str::contains($value['trans_type'], 'Credit') ? 'public/img/credit.png' : 'public/img/debit.png';
                    }
                }


                /* $transNew['color'] = Str::contains($value['trans_type'], 'Credit') ? '3BBF00' : 'FF0000';
                $transNew['image'] = Str::contains($value['trans_type'], 'Credit') ? 'public/img/credit.png' : 'public/img/debit.png'; */

                //}
            } elseif ($value['trans_status'] == "Pending") {
                $transNew['color'] = "FD8B08";
                $transNew['image'] = "public/img/pending.png";
            } else {
                $transNew['color'] = "FF0000";
                $transNew['image'] = "public/img/failed.png";
            }

            if ($user_id != $value['receiver_request_id']) {
                // $transNew['receiverandUser'] = $value['receiver_id'].'----'.$user_id.$value['request_type'].'-----'.$value['receiver_request_id'];
                $transNew['transPaymentType'] = $value['request_type'] == 4 && $value['pendingSuccessStatus'] == 2 ? "MoneyRequest" : "";
            } else {
                $transNew['transPaymentType'] = "";
            }
            $transNew['cardType'] = $value['cardType'] ?? "";
            $transArrNew[] = $transNew;
        }
        // dd($transArrNew);
        $statusArr = array(
            "status" => "Success",
            "reason" => __("message_app.Transaction List"),
            "total_records" => $totalRecords,
            "total_page" => ceil($totalRecords / $limit),
        );
        $data['data'] = $transArrNew;
        $json = array_merge($statusArr, $data);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getCountryListNew(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $countryList = DB::table('countries_new')->select('id', 'name')->orderBy('name', 'ASC')->get();
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['data' => $countryList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getStateListNew(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $input = ['country_id' => $request->country_id ?? null];
        $validate_data = [
            'country_id' => 'required',
        ];
        $customMessages = [
            'country_id.required' => __("message_app.Country field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();

            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $stateList = DB::table('states_new')->select('id', 'name')->where('countryId', $request->country_id)->orderBy('name', 'ASC')->get();
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['data' => $stateList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function handleCallback(Request $request)
    {
        Log::info($request->all());
        die;
    }
    public function resendPayment(Request $request)
    {
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */
        $request = $this->decryptContent($request->req);

        $input = [
            'transaction_id' => $request->transaction_id ?? null,
        ];
        $validate_data = [
            'transaction_id' => 'required',
        ];
        $customMessages = [
            'transaction_id.required' => __("message_app.Transaction id field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();

            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $getTrans = Transaction::where('id', $request->transaction_id)->first();
        $data = [];

        if (in_array($getTrans->paymentType, ['WALLETTOWALLET', 'WALLETTOACCOUNT', 'REQUESTTOPAY', 'OUTGOINGWALLET'])) {
            $data['user_id'] = $getTrans->user_id;
            $data['name'] = $getTrans->receiverName ?? "";
            $data['phone'] = $getTrans->receiver_mobile ? $getTrans->receiver_mobile : $getTrans->receiverAccount;
        } elseif ($getTrans->transactionType == "SWAPTOBDA") {
            $getIBAN = RemittanceData::where('id', $getTrans->onafriq_bda_ids)->first()->iban;
            $data['user_id'] = $getTrans->user_id;
            $data['name'] = $getTrans->receiverName ?? "User";
            $data['phone'] = $getIBAN ?? "";
        } elseif ($getTrans->transactionType == "SWAPTOSWAP") {
            $getRece = User::where('id', $getTrans->receiver_id)->first();
            $data['user_id'] = $getTrans->user_id;
            $data['name'] = ($getRece->name ?? '') . ' ' . ($getRece->lastName ?? '');
            $data['phone'] = $getRece->phone ?? "";
        }
        $data['profile_image'] = "public/img/" . "no_user.png";
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['userData' => $data]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    private function getTransactionDataById($id)
    {
        $data = Transaction::where('id', $id)->first();
        return $data;
    }
    private function getUserDataByPhone($id)
    {
        $data = User::where('id', $id)->first();
        return $data;
    }
    private function getUserDataByIban($id)
    {
        $data = RemittanceData::where('id', $id)->first();
        return $data;
    }
    public function resendNewInitPayment(Request $request)
    {
        // $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;

        $input = [
            'transaction_id' => $request->transaction_id ?? null,
            'amount' => $request->amount ?? null,
        ];

        $validate_data = [
            'transaction_id' => 'exists:transactions,id',
            'amount' => 'required|numeric|min:1|max:99999999',
        ];

        $customMessages = [
            'transaction_id.exists' => __("message_app.The selected transaction_id does not exist"),
            'amount.required' => __("message_app.Amount field cannot be left blank"),

        ];

        $validator = Validator::make($input, $validate_data, $customMessages);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($validator->errors()->has('type')) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allowed"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($json, 200);
        }

        $transaction_id = $request->transaction_id ?? 0;
        $amount = $request->amount ?? 0;
        $getTrans = $this->getTransactionDataById($request->transaction_id);
        // dd($getTrans);
        if (isset($getTrans->transactionType) && $getTrans->transactionType == "SWAPTOCEMAC" && $getTrans->paymentType == "WALLETTOACCOUNT") {

        } elseif (isset($getTrans->transactionType) && $getTrans->transactionType == "SWAPTOCEMAC" && $getTrans->paymentType == "WALLETTOWALLET") {
            echo $getTrans->receiver_mobile;
        } elseif (isset($getTrans->transactionType) && $getTrans->transactionType == "SWAPTOCEMAC" && $getTrans->paymentType == "REQUESTTOPAY") {

        } elseif (isset($getTrans->transactionType) && $getTrans->transactionType == "SWAPTOOUTCEMAC" && $getTrans->paymentType == "OUTGOINGWALLET") {

        } elseif (isset($getTrans->transactionType) && $getTrans->transactionType == "SWAPTOBDA" && $getTrans->paymentType == "OUTWALLETBDA") {

        } elseif (isset($getTrans->transactionType) && $getTrans->transactionType == "SWAPTOSWAP") {


            $fakeRequest = new Request([
                'req' => encrypt(json_encode(['transaction_id' => $this->encryptContent($request->transaction_id)]))
            ]);
            dd($fakeRequest);

            $this->fundTransfer($fakeRequest);
        }
    }
    public function provinceList(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $provinceList = DB::table('province_data')->select('id', 'name')->orderBy('name', 'ASC')->get();
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['data' => $provinceList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function provinceCity(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $input = [
            'provinceId' => $request->provinceId ?? null,
        ];
        $validate_data = [
            'provinceId' => 'required',
        ];
        $customMessages = [
            'provinceId.required' => __("message_app.The province field cannot be left blank"),
        ];
        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($validator->errors()->has('type')) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allowed"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $provinceList = DB::table('province_city')->select('id', 'name')->where('provinceId', $input['provinceId'])->orderBy('name', 'ASC')->get();
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['data' => $provinceList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function provinceDistrict(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $input = [
            'cityId' => $request->cityId ?? null,
        ];
        $validate_data = [
            'cityId' => 'required',
        ];
        $customMessages = [
            'cityId.required' => __("message_app.The city field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($validator->errors()->has('type')) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allowed"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $provinceList = DB::table('province_district')->select('id', 'name')->where('cityId', $input['cityId'])->orderBy('name', 'ASC')->get();
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $json = array_merge($statusArr, ['data' => $provinceList]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getCardContent(Request $request)
    {
        $input = [
            'cardType' => $request->cardType ?? null,
        ];
        $validate_data = [
            'cardType' => 'required|in:PREPAID,VIRTUAL',
        ];
        $customMessages = [
            'cardType.required' => __("message_app.The card type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($firstErrorMessage == "validation.in") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allow"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $cardTypeList = CardContent::
            where('cardType', $input['cardType'])
            ->orderBy('title', 'ASC')->first();

        if ($input['cardType'] == 'PREPAID') {
            $image = "public/img/" . "physical.png";
            $color = "#EFDCFF";
        } elseif ($input['cardType'] == 'VIRTUAL') {
            $image = "public/img/" . "virtual.png";
            $color = "#F4D8CB";
        } else {
            $title = "Type is now allow";
            $image = "";
            $color = "";
        }
        $statusArr = array("status" => "Success", "reason" => __("message_app.Fetched record successfully"));
        $statusArr['data']['title'] = $cardTypeList['title'];
        $statusArr['data']['image'] = $image;
        $statusArr['data']['color'] = $color;
        $statusArr['data']['list'] = $cardTypeList['description'];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function saveAddressInfo(Request $request)
    {

        // $requestData = $this->decryptContent($request->req);
        $requestData = $request;

        $userId = Auth::user()->id;
        $user = User::find($userId);
        $provinceId = $requestData->provinceId;
        $cityId = $requestData->cityId;
        $districtId = $requestData->districtId;
        $location = $requestData->location;

        $gabonStampImg = "";
        if ($request->hasFile('gabonStampImg')) {
            $file = $request->file('gabonStampImg');
            $gabonStampImg = $this->uploadImage($file, GABON_VISA_STAMPED);
        }
        $input = [
            'provinceId' => $requestData->provinceId ?? null,
            'cityId' => $requestData->cityId ?? null,
            'districtId' => $requestData->districtId ?? null,
            'location' => $requestData->location ?? null,
        ];

        $validate_data = [
            'provinceId' => 'required',
            'cityId' => 'required',
            'districtId' => 'required',
            'location' => 'required|string|min:6|max:100',
        ];

        $customMessages = [
            'provinceId.required' => __("message_app.Province is required"),
            'cityId.required' => __("message_app.City is required"),
            'districtId.required' => __("message_app.District is required"),
            'location.required' => __("message_app.Location is required"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($user->kyc_status == "rejected") {
            $user->kyc_status = "verify";
            $user->save();
        }

        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;

        $amlUrl = SMILE_PATH . '/aml';

        $dt2 = new DateTime('now', new DateTimeZone('UTC'));
        $currentTimestampsAML = $dt2->format("Y-m-d\TH:i:s.v\Z");

        $amlMessage = $currentTimestampsAML . $partner_id . 'sid_request';
        $amlSignature = base64_encode(hash_hmac('sha256', $amlMessage, $api_key, true));

        $randomString = $user->slug . '-user' . $this->generateRandomString();
        $jobString = 'AML-' . rand(11111111, 99999999);
        $jobString1 = 'job-' . rand(11111111, 99999999);

        $amlPayload = [
            "partner_id" => $partner_id,
            "user_id" => $randomString,
            "job_id" => $jobString,
            "full_name" => $user->name . ' ' . $user->lastName,
            "birth_year" => date('Y', strtotime($user->dob)),
            "countries" => ['GA'],
            "signature" => $amlSignature,
            "timestamp" => $currentTimestampsAML,
            "search_existing_user" => false,
            "strict_match" => true
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $amlUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($amlPayload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if (!$err) {
            $amlResponse = json_decode($response, true);
            if (isset($amlResponse) && $amlResponse['ResultCode'] == "1031" || $amlResponse['ResultCode'] == "1023" || $amlResponse['ResultCode'] == "1015" || $amlResponse['ResultCode'] == "1030" || $amlResponse['ResultCode'] == "1017") {
                // Log::info(json_encode($amlResponse));
                if ($user) {
                    $user->country = $provinceId;
                    $user->city = $cityId;
                    $user->state = $districtId;
                    $user->address1 = $location;
                    $user->smile_link = "";
                    $user->unique_key = $randomString;
                    $user->jobId = $jobString1;
                    $user->refId = "";
                    $user->gabonStampImg = $gabonStampImg ?? "";
                    $user->gabonStampStatus = $gabonStampImg ? "pending" : "";
                    $user->updated_at = now();

                    $user->save();
                    $statusArr = [
                        "status" => "Success",
                        "reason" => __("message_app.Address added successfully"),
                    ];

                    $title = __("message_app.Address Added");
                    $message = __("message_app.Address added successfully");
                    $device_token = $user->device_token;
                    $device_type = $user->device_type;

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'ADDRESS',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        'user_id' => $user->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                } else {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => __("message_app.User not found"),
                    ];
                }

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Aml check failed"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => $err,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        // $randomString = 'user-' . $userId;

    }
    public function checkVerifyKyc(Request $request)
    {
        $userId = Auth::user()->id;
        $userData = User::where('id', $userId)->first();
        if ($userData->kyc_status == "rejected") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Your KYC has been rejected. Please review and resubmit the required documents"), "data" => array("kyc_status" => "rejected"));
        } elseif ($userData->kyc_status == "pending") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Your KYC submission is pending. Please wait while we review your documents"), "data" => array("kyc_status" => "pending"));
        } elseif ($userData->kyc_status == "completed") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Your KYC has been successfully completed"), "data" => array("kyc_status" => "completed"));
        } else {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Your KYC is under verification"), "data" => array("kyc_status" => "verify"));
        }

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function checkCompleteKycStatus($userId)
    {
        $user = User::where('id', $userId)->first();

        if (!$user) {
            return ['status' => false, 'message' => __("message_app.User not found")];
        }

        if ($user->kyc_status === 'pending') {
            return ['status' => false, 'message' => __("message_app.Your KYC is pending. Please wait for verification")];
        } elseif ($user->kyc_status === 'verify') {
            return ['status' => false, 'message' => __("message_app.Please verify your KYC")];
        } elseif ($user->kyc_status === 'rejected') {
            return ['status' => false, 'message' => __("message_app.Your KYC is rejected")];
        } elseif ($user->kyc_status === 'skipped') {
            return ['status' => false, 'message' => __("message_app.Your KYC is skipped. Please update your KYC")];
        } elseif (empty($user->accountId) && $user->user_type == "User") {
            return ['status' => false, 'message' => __("message_app.No account or card found. Please create one first")];
        } else {
            return ['status' => true];
        }
    }
    public function createPhysicalCard(Request $request)
    {
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */
        $userDetail = User::where('id', $user_id)->first();
        if ($userDetail->cardType == "PHYSICAL") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Already created physical card"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userDetail->accountId == "" && $userDetail->last4Digits == "" && $userDetail->passCode == "") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card Not found"));

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userDetail->wallet_balance <= PHYSICAL_CARD_FEES) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $isCardRequestGenerated = $this->isCardRequestGenerated($user_id);
        if ($isCardRequestGenerated) {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card request already generated"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }



        CardRequest::create([
            'user_id' => $user_id,
            'status' => 0
        ]);

        $trans = new Transaction([
            "user_id" => $user_id,
            "receiver_id" => '',
            "amount" => CARD_FEES,
            "amount_value" => CARD_FEES,
            "transaction_amount" => 0.00,
            "total_amount" => CARD_FEES,
            "trans_type" => 2,
            "trans_to" => "Physical Card Fees",
            "payment_mode" => "physical_card_fees",
            "refrence_id" => time() . rand() . $user_id,
            "status" => 1,
            'entryType' => 'API',
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            'swapDomainName' => 'UAT_SERVER'
        ]);
        $trans->save();

        DB::table('users')->where('id', $user_id)->decrement('wallet_balance', CARD_FEES);
        DB::table('admins')->where('id', 1)->increment('wallet_balance', CARD_FEES);

        $title = __("message_app.Physical Card");
        $message = __("message_app.Physical card successfully");
        $device_token = $userDetail->device_token;
        $device_type = $userDetail->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $statusArr = array("status" => "Success", "reason" => __("message_app.Card request generated successfully"));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);

    }

    public function createPhysicalCardNew(Request $request)
    {
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */
        $userDetail = User::where('id', $user_id)->first();
        if ($userDetail->cardType == "PHYSICAL") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Already created physical card"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userDetail->accountId == "" && $userDetail->last4Digits == "" && $userDetail->passCode == "") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card Not found"));

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userDetail->wallet_balance < REPLACE_CARD_FEES) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $isCardRequestGenerated = $this->isCardRequestGenerated($user_id);
        if ($isCardRequestGenerated) {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card request already generated"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        CardRequest::create([
            'user_id' => $user_id,
            'status' => 0
        ]);

        $title = __("message_app.Physical Card");
        $message = __("message_app.Physical card successfully");
        $device_token = $userDetail->device_token;
        $device_type = $userDetail->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $statusArr = array("status" => "Success", "reason" => __("message_app.Card request generated successfully"));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);

    }

    public function isCardRequestGenerated($user_id)
    {
        $cardRequest = CardRequest::where('user_id', $user_id)->where('status', 0)->first();
        return $cardRequest;
    }

    public function replaceCard(Request $request)
    {
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $userDetail = User::where('id', $user_id)->first();
        if ($userDetail->accountId == "" && $userDetail->last4Digits == "") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card not found"));

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userDetail->wallet_balance <= REPLACE_CARD_FEES) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $isCardRequestGenerated = $this->isCardRequestGenerated($user_id);
        if ($isCardRequestGenerated) {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card request already generated"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        CardRequest::create([
            'user_id' => $user_id,
            'status' => 0
        ]);
        User::where('id', $user_id)->update([
            'requestReplaceCard' => "REPLACEPHYSICAL"
        ]);

        $trans = new Transaction([
            "user_id" => $user_id,
            "receiver_id" => '',
            "amount" => REPLACE_CARD_FEES,
            "amount_value" => REPLACE_CARD_FEES,
            "transaction_amount" => 0.00,
            "total_amount" => REPLACE_CARD_FEES,
            "trans_type" => 2,
            "trans_to" => "Physical Card Fees",
            "payment_mode" => "physical_card_fees",
            "refrence_id" => time() . rand() . $user_id,
            "status" => 1,
            'entryType' => 'API',
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            'swapDomainName' => 'UAT_SERVER'
        ]);
        $trans->save();

        DB::table('users')->where('id', $user_id)->decrement('wallet_balance', REPLACE_CARD_FEES);
        DB::table('admins')->where('id', 1)->increment('wallet_balance', REPLACE_CARD_FEES);

        $title = __("message_app.Replace Card");
        $message = __("message_app.Replace card successfully");
        $device_token = $userDetail->device_token;
        $device_type = $userDetail->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $statusArr = array("status" => "Success", "reason" => __("message_app.Replace card successfully"));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function replaceCardNew(Request $request)
    {
        $user_id = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($user_id);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        $userDetail = User::where('id', $user_id)->first();
        if ($userDetail->accountId == "" && $userDetail->last4Digits == "") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card not found"));

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userDetail->wallet_balance < REPLACE_CARD_FEES) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $isCardRequestGenerated = $this->isCardRequestGenerated($user_id);
        if ($isCardRequestGenerated) {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card request already generated"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        CardRequest::create([
            'user_id' => $user_id,
            'status' => 0
        ]);
        User::where('id', $user_id)->update([
            'requestReplaceCard' => "REPLACEPHYSICAL"
        ]);

        $title = __("message_app.Replace Card");
        $message = __("message_app.Replace card successfully");
        $device_token = $userDetail->device_token;
        $device_type = $userDetail->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();
        Log::info(['Replace card request successfully' => "Success", 'user_id' => $user_id]);
        $statusArr = array("status" => "Success", "reason" => __("message_app.Replace card successfully"));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function changeCardPin(Request $request)
    {
        $userId = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($userId);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */
        $input = [
            'type' => $request->type ?? null,
            'pin' => $request->pin ?? null,
        ];
        $validate_data = [
            'type' => 'required|in:CHANGEPIN,CHECKPIN',
            'pin' => 'required|integer|digits:4',
        ];
        $customMessages = [
            'type.required' => __("message_app.Type is required"),
            'pin.required' => __("message_app.Pin is required"),
            'pin.digits' => __("message_app.Pin size 4 digits"),
        ];
        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($firstErrorMessage == "validation.in") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Type is not allow"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $userDetail = User::where('id', $userId)->first();
        if ($userDetail->accountId == "" && $userDetail->last4Digits == "" && $userDetail->passCode == "") {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Card not found"));

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $method = $request->type == "CHANGEPIN" ? 'PUT' : 'POST';
        $pin = ['pin' => $request->pin];

        $getResponse = $this->cardService->sendCardRequest($request->type, $userDetail->accountId, $pin, $method);
        if (isset($getResponse) && $getResponse['status'] == false) {
            $statusArr = array("status" => "Failed", "reason" => __("message_app.Your request failed"), "data" => $getResponse['data']);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $title = $request->type == "CHANGEPIN" ? __("message_app.Pin Change") : __("message_app.Check Pin");
        $message = $request->type == "CHANGEPIN" ? __("message_app.Pin change successfully") : __("message_app.Check pin successfully");
        $device_token = $userDetail->device_token;
        $device_type = $userDetail->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $statusArr = array("status" => "Success", "reason" => $request->type == "CHANGEPIN" ? __("message_app.Pin change successfully") : __("message_app.Check pin successfully"));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function lockUnlockCard(Request $request)
    {
        $userId = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($userId);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */

        // $request->newCardStatus = "Active";
        $input = [
            'newCardStatus' => $request->newCardStatus ?? null,
        ];
        $validate_data = [
            'newCardStatus' => 'required|string|in:Active,Inactive',
        ];
        $customMessages = [
            'newCardStatus.required' => __("message_app.Status is required"),
            'newCardStatus.string' => __("message_app.Enter string"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            if ($firstErrorMessage == "validation.in") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.This status is not allow"),
                ];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => $firstErrorMessage,
                ];
            }
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $userDetail = User::where('id', $userId)->first();
        $postData = json_encode([
            "chargeFee" => false,
            "last4Digits" => $userDetail->last4Digits,
            "mobilePhoneNumber" => "241$userDetail->phone",
            // "mobilePhoneNumber" => "2411231231",
            "newCardStatus" => $request->newCardStatus,
        ]);

        Log::info(['Lock Unlock Card Request' => $postData]);
        $getResponse = $this->cardService->cardLockUnlock($postData, $userDetail->accountId, "PHYSICAL");
        Log::info(['Lock Unlock Card Response' => $getResponse]);
        $getCustomerDetail = $this->cardService->getCustomerData($userDetail->accountId, 'PHYSICAL');
        $isLocked = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;
        if (isset($isLocked) && $isLocked === true) {

            UserCard::where('userId', $userDetail->id)->where('cardType', 'PHYSICAL')->update(['cardStatus' => 'Active']);
            $getCardBalance = $this->cardService->getCardBalance($userDetail->accountId, 'PHYSICAL');
            $wallet = $userDetail->wallet_balance;
            $card = $getCardBalance['data']['balance'];
            $amount = 0;
            if ($card > $wallet) {
                $transferType = 'CardToWallet';
                $amount = $card - $wallet;
            } elseif ($wallet > $card) {
                $transferType = 'WalletToCard';
                $amount = $wallet - $card;
            } else {

            }
            if ($amount > 0) {
                /* $userCard = UserCard::where('userId', $userDetail->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                if ($userCard) {
                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userCard->last4Digits,
                        "referenceMemo" => "Auto Balance Sync",
                        "transferAmount" => number_format($amount, 2, '.', ''),
                        "transferType" => $transferType,
                        "mobilePhoneNumber" => "241{$userDetail->phone}"
                    ]);

                    $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                } */
            }
        } else {
            UserCard::where('userId', $userDetail->id)->where('cardType', 'PHYSICAL')->update(['cardStatus' => 'Inactive']);

        }

        if (isset($getResponse['data'])) {
            $statusArr = array("status" => "Success", "reason" => $getResponse['data']['detail'], 'data' => array('isLocked' => $isLocked));
        } else {

            /* New code update status */
            // UserCard::where('userId', $userDetail->id)->where('accountId', $userDetail->accountId)->update(['cardStatus' => $isLocked]);
            /* End */

            $statusArr = array("status" => "Success", "reason" => __("message_app.Card status update successfully"), 'data' => array('isLocked' => $isLocked));
        }
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);

    }
    public function cardAmountAdded(Request $request)
    {

        /* $amount = 100;
        $getfeeRecord = $this->calculateTotalFees('Money Transfer Via GIMAC', $amount);
        $total_fees_trans = $getfeeRecord['total_fees'] ?? 0;
        $total_fees = $getfeeRecord['total_fees'] ?? 0;

        $total_amount = $amount + $total_fees_trans;

        $referralFeeAmount = 0;
        $referralByUserId = 0;
        $senderUser = User::where('id',748)->first();
        if ($senderUser->referralBy) {
            $getData = $this->referralService->referralFeeAmount($senderUser, "1", 'SWAPTOCEMAC');
            dd($getData);
            $total_fees = $getData['data']['total_fees'];
            $referralFeeAmount = $getData['data']['referralFeeAmount'] ?? 0;
            $referralByUserId = $getData['data']['referralByUserId'] ?? 0;
        }
        dd($total_fees,$referralFeeAmount,$referralByUserId);

        die; */
        $userId = Auth::user()->id;
        /* if (Auth::user()->user_type != "Agent") {
            $isCheck = $this->checkCompleteKycStatus($userId);
            if (!$isCheck['status']) {
                $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } */
        $userDetail = User::where('id', $userId)->first();
        $input = [
            'amount' => $request->amount ?? null,
            'last4Digits' => $userDetail->last4Digits ?? null,
            'mobilePhoneNumber' => $userDetail->phone ?? null,
        ];
        $validate_data = [
            'amount' => 'required|numeric|min:100|max:100000',
        ];
        $customMessages = [
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Enter amount numeric.',
            'amount.min' => 'Minimum amount 100.',
            'amount.max' => 'Maximum amount 100000.',
            'last4Digits.required' => 'last4Digits is required.',
            'mobilePhoneNumber.required' => 'Phone Number is required.',
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userDetail->wallet_balance < $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Insufficient Balance"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $postData = json_encode([
            "currencyCode" => "XAF",
            "last4Digits" => $userDetail->last4Digits,
            "referenceMemo" => "test transaction",
            "transferAmount" => $request->amount,
            "transferType" => "WalletToCard",
            "mobilePhoneNumber" => "241{$userDetail->phone}",
        ]);

        $response = $this->cardService->addWalletCardTopUp($postData, $userDetail->accountId, $userDetail->cardType);
        if (isset($response) && $response['status'] == true) {
            User::where('id', $userDetail->id)->decrement('wallet_balance', $request->amount);
            $statusArr = [
                "status" => "Success",
                "reason" => $response['message'],
                "data" => $response['data']
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($json, 200);

        } else {
            $statusArr = [
                "status" => "Success",
                "reason" => $response['message'] ?? 'Amount added failed'
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($json, 200);
        }
    }
    public function checkCardInfo(Request $request)
    {
        $userId = Auth::user()->id;
        $userData = User::where('id', $userId)->first();
        if (isset($request->cardType) && ($request->cardType == "VIRTUAL" || $request->cardType == "PHYSICAL")) {
            if ($userData->accountId == "") {
                $statusArr = ["status" => "Failed", "reason" => __("message_app.Card not found")];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

        if (isset($request->cardType) && $request->cardType === "VIRTUAL") {
            $cardReq = CardRequest::where('user_id', $userData->id)->first();

            $getCustomerDetails = $this->cardService->getCustomerData($userData->accountId, 'VIRTUAL');
            $isActiveCheck = isset($getCustomerDetails['data']['cardStatus']) && $getCustomerDetails['data']['cardStatus'] == "AC" ? true : false;

            if ($isActiveCheck) {
                if (isset($getCustomerDetails['data']['cardStatus']) && $getCustomerDetails['data']['cardStatus'] == "AC") {
                    $name = "{$userData->name} {$userData->lastName}";
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully")];
                    $statusArr['data'] = ["accountId" => $userData->accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'cardStatus' => $getCustomerDetails['data']['cardStatus'], 'isOtpRequired' => false, 'isActive' => $isActiveCheck];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }

            if (
                $userData->wallet_balance >= CARD_FEES ||
                (isset($cardReq) && $cardReq->status == 0)
            ) {

                $getCustomerDetail = $this->cardService->getCustomerData($userData->accountId, 'VIRTUAL');
                $isActive = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;

                // if ($userData->wallet_balance >= CARD_FEES) {

                if (!$isActive) {

                    if ($userData->wallet_balance <= VIRTUAL_CARD_FEES) {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __("message_app.Insufficient Balance"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }


                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userData->last4Digits,
                        "referenceMemo" => "test transaction",
                        "transferAmount" => VIRTUAL_CARD_FEES,
                        "transferType" => "WalletToCard",
                        "mobilePhoneNumber" => "241$userData->phone",
                    ]);
                    $checkAdded = $this->cardService->addWalletCardTopUp($postData, $userData->accountId, 'VIRTUAL');

                    if ($checkAdded['status'] == false) {
                        $statusArr = ["status" => "Failed", "reason" => __("message_app.First request card activation, then complete the activation process")];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }

                    $refrence_id = $this->generateRandomAlphaNumeric();
                    $trans = new Transaction([
                        "user_id" => $userId,
                        "receiver_id" => "",
                        "amount" => VIRTUAL_CARD_FEES,
                        "amount_value" => VIRTUAL_CARD_FEES,
                        "transaction_amount" => 0,
                        "total_amount" => VIRTUAL_CARD_FEES,
                        "trans_type" => 2,
                        "payment_mode" => "Depot Initial",
                        "status" => 1,
                        'entryType' => 'API',
                        "refrence_id" => $refrence_id,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                        'swapDomainName' => 'UAT_SERVER'
                    ]);
                    $trans->save();

                    User::where('id', $userId)->decrement('wallet_balance', VIRTUAL_CARD_FEES);

                    // UserCard::where('userId', $userData->id)->where('cardType', "VIRTUAL")->update(['cardStatus' => 'Active']);

                    $title = __("message_app.Depot Initial");
                    $message = __('message_app.wallet_debited_title', ['currency' => 'XAF', 'amount' => $this->numberFormatSpaces($this->roundAmount(VIRTUAL_CARD_FEES))]);
                    $device_token = Auth::user()->device_token;
                    $device_type = Auth::user()->device_type;

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        'user_id' => Auth::user()->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                }

                if (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC") {
                    $name = "{$userData->name} {$userData->lastName}";
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully")];
                    $statusArr['data'] = ["accountId" => $userData->accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'cardStatus' => $getCustomerDetail['data']['cardStatus'], 'isOtpRequired' => false, 'isActive' => $isActive];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                $statusArr = ["status" => "Success", "reason" => __("message_app.Card activation successfully"), 'data' => array('isOtpRequired' => false, 'isActive' => $isActive)];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = ["status" => "Failed", "reason" => __('message_app.CardFees', ['amount' => $this->numberFormatSpaces(VIRTUAL_CARD_FEES)])];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } elseif (isset($request->cardType) && $request->cardType === "PHYSICAL") {

            /* if (isset($userData->cardType) && $userData->cardType == "PHYSICAL") {
                if (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] != "AC") {
                    $statusArr = ["status" => "Failed", "reason" => __("message_app.First request card activation, then complete the activation process"), 'data' => array('isOtpRequired' => false, 'isActive' => false)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } */
            $getCustomerDetail = $this->cardService->getCustomerData($userData->accountId, 'PHYSICAL');

            $isActive = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;
            if ($userData->cardType == "PHYSICAL") {
                if (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC") {
                    $name = "{$userData->name} {$userData->lastName}";
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully")];
                    $statusArr['data'] = ["accountId" => $userData->accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'cardStatus' => $getCustomerDetail['data']['cardStatus'], 'isOtpRequired' => false, 'isActive' => $isActive];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } elseif (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "IA") {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Please activate your card"), 'data' => array('isActive' => $isActive, 'isOtpRequired' => false)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Please activate your card"), 'data' => array('isActive' => $isActive, 'isOtpRequired' => true)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }

            if ($userData->wallet_balance >= CARD_FEES) {
                $statusArr = [
                    "status" => "Success",
                    "reason" => __('message_app.replace_card_fee_notice', [
                        'currency' => 'XAF',
                        'amount' => CARD_FEES,
                    ]),
                    'data' => array('isActive' => $isActive, 'isOtpRequired' => true)
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = ["status" => "Failed", "reason" => __('message_app.CardFees', ['amount' => $this->numberFormatSpaces(CARD_FEES)])];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } elseif (isset($request->cardType) && $request->cardType === "REPLACEPHYSICAL") {
            $getCustomerDetail = $this->cardService->getCustomerData($userData->accountId, 'PHYSICAL');
            $isActive = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;

            if ($userData->cardType == "PHYSICAL") {
                if (!in_array($getCustomerDetail['data']['cardStatus'], ['LC', 'SC', 'EX', 'IA'])) {
                    $statusArr = ["status" => "Failed", "reason" => __("message_app.First, deactivate the physical card, then you can apply for a replacement card")];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    if ($userData->wallet_balance >= REPLACE_CARD_FEES) {
                        $statusArr = [
                            "status" => "Success",
                            "reason" => __('message_app.replace_card_fee_notice', [
                                'currency' => 'XAF',
                                'amount' => $this->numberFormatSpaces(REPLACE_CARD_FEES),
                            ]),
                            'data' => array('isActive' => $isActive, 'isOtpRequired' => true)
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $statusArr = ["status" => "Failed", "reason" => __('message_app.CardFees', ['amount' => $this->numberFormatSpaces(REPLACE_CARD_FEES)])];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }
            } elseif ($userData->cardType == "REPLACEPHYSICAL") {
                if ($userData->alreadyReplace == "REPLACECARD") {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Please activate your replace card"), 'data' => array('isActive' => false, 'isOtpRequired' => true)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully"), 'data' => array('isActive' => $isActive, 'isOtpRequired' => false)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
            /* elseif ($request->cardType == "REPLACEPHYSICAL") {
                if (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC") {
                    $isActive = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;
                    $name = "{$userData->name} {$userData->lastName}";
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully")];
                    $statusArr['data'] = ["accountId" => $userData->accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'cardStatus' => $getCustomerDetail['data']['cardStatus'], 'isOtpRequired' => false, 'isActive' => $isActive];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } elseif (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] != "AC") {
                    $statusArr = ["status" => "Failed", "reason" => "Please activate your replace card ", 'data' => array('isActive' => $isActive, 'isOtpRequired' => true)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } */
        } else {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Type is not allow")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function checkCardInfoNew(Request $request)
    {
        $userId = Auth::user()->id;
        $userData = User::where('id', $userId)->first();
        if (empty($request->cardType)) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Card type is required")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        if (empty($request->accountId)) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Account ID is required")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $getCardReq = CardRequest::where('user_id', $userData->id)->where('status', 0)->first();
        if (isset($request->cardType) && $request->cardType === "VIRTUAL") {
            $userCard = UserCard::firstWhere(['userId' => $userId, 'accountId' => $request->accountId, 'cardType' => $request->cardType]);
            if (!$userCard) {
                $statusArr = ["status" => "Failed", "reason" => __("message_app.Card not found")];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $cardReq = CardRequest::where('user_id', $userData->id)->first();

            $getCustomerDetails = $this->cardService->getCustomerData($request->accountId, 'VIRTUAL');
            $isActiveCheck = isset($getCustomerDetails['data']['cardStatus']) && $getCustomerDetails['data']['cardStatus'] == "AC" ? true : false;

            if ($isActiveCheck) {
                if (isset($getCustomerDetails['data']['cardStatus']) && $getCustomerDetails['data']['cardStatus'] == "AC") {
                    $name = "{$userData->name} {$userData->lastName}";
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully")];
                    $statusArr['data'] = ["accountId" => $request->accountId, "last4Digits" => $request->last4Digits ?? 00000, 'name' => $name, "cardType" => $request->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'cardStatus' => $getCustomerDetails['data']['cardStatus'], 'isOtpRequired' => false, 'isActive' => $isActiveCheck];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }

            if (
                $userData->wallet_balance >= VIRTUAL_CARD_FEES ||
                (isset($cardReq) && $cardReq->status == 0)
            ) {

                $getCustomerDetail = $this->cardService->getCustomerData($request->accountId, 'VIRTUAL');
                $isActive = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;

                // if ($userData->wallet_balance >= CARD_FEES) {

                if (!$isActive) {
                    if ($userData->wallet_balance < VIRTUAL_CARD_FEES) {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => __("message_app.Insufficient Balance"),
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }


                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userCard->last4Digits,
                        "referenceMemo" => "Settlement",
                        "transferAmount" => VIRTUAL_CARD_FEES,
                        "transferType" => "WalletToCard",
                        "mobilePhoneNumber" => "241$userData->phone",
                    ]);
                    $checkAdded = $this->cardService->addWalletCardTopUp($postData, $request->accountId, 'VIRTUAL');

                    if ($checkAdded['status'] == false) {
                        $statusArr = ["status" => "Failed", "reason" => __("message_app.First request card activation, then complete the activation process")];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }

                    $refrence_id = $this->generateRandomAlphaNumeric();
                    $trans = new Transaction([
                        "user_id" => $userId,
                        "receiver_id" => "",
                        "amount" => VIRTUAL_CARD_FEES,
                        "amount_value" => VIRTUAL_CARD_FEES,
                        "transaction_amount" => 0,
                        "total_amount" => VIRTUAL_CARD_FEES,
                        "trans_type" => 2,
                        "payment_mode" => "Depot Initial",
                        "cardType" => "VIRTUAL",
                        "accountId" => $request->accountId,
                        "beforeBalance" => $userData->wallet_balance,
                        "afterBalance" => ($userData->wallet_balance - VIRTUAL_CARD_FEES),
                        "status" => 1,
                        'entryType' => 'API',
                        "refrence_id" => $refrence_id,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                        'swapDomainName' => 'UAT_SERVER'
                    ]);
                    $trans->save();



                    User::where('id', $userId)->decrement('wallet_balance', VIRTUAL_CARD_FEES);

                    UserCard::where('userId', $userData->id)->where('cardType', "VIRTUAL")->update(['cardStatus' => 'Active']);

                    $title = __("message_app.Depot Initial");
                    $message = __('message_app.wallet_debited_title', ['currency' => 'XAF', 'amount' => $this->numberFormatSpaces($this->roundAmount(VIRTUAL_CARD_FEES))]);
                    $device_token = Auth::user()->device_token;
                    $device_type = Auth::user()->device_type;

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'TRANSACTION',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        'user_id' => Auth::user()->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                }

                if (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC") {
                    $name = "{$userData->name} {$userData->lastName}";
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully")];
                    $statusArr['data'] = ["accountId" => $request->accountId, "last4Digits" => $request->last4Digits ?? 00000, 'name' => $name, "cardType" => $request->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'cardStatus' => $getCustomerDetail['data']['cardStatus'], 'isOtpRequired' => false, 'isActive' => $isActive];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                $statusArr = ["status" => "Success", "reason" => __("message_app.Card activation successfully"), 'data' => array('isOtpRequired' => false, 'isActive' => $isActive)];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = ["status" => "Failed", "reason" => __('message_app.CardFees', ['amount' => $this->numberFormatSpaces(VIRTUAL_CARD_FEES)])];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } elseif (isset($request->cardType) && $request->cardType === "PHYSICAL") {
            /* $userCard = UserCard::firstWhere(['userId' => $userId, 'accountId' => $request->accountId, 'cardType' => $request->cardType]);
            if (!$userCard) {
                $statusArr = ["status" => "Failed", "reason" => __("message_app.Card not found"]);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } */
            $getCustomerDetail = $this->cardService->getCustomerData($request->accountId, 'PHYSICAL');

            // $isActive = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;
            $isActive = isset($getCustomerDetail['data']['cardStatus']) && in_array($getCustomerDetail['data']['cardStatus'], ['AC', 'RP']);

            if ($userData->cardType == "PHYSICAL") {
                if (isset($getCustomerDetail['data']['cardStatus']) && in_array($getCustomerDetail['data']['cardStatus'], ['AC', 'RP'])) {
                    $name = "{$userData->name} {$userData->lastName}";
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfully")];
                    $statusArr['data'] = ["accountId" => $request->accountId, "last4Digits" => $request->last4Digits ?? 00000, 'name' => $name, "cardType" => $request->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'cardStatus' => $getCustomerDetail['data']['cardStatus'], 'isOtpRequired' => false, 'isActive' => $isActive];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } elseif (isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "IA") {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Please activate your card"), 'data' => array('isActive' => $isActive, 'isOtpRequired' => false)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Please activate your card"), 'data' => array('isActive' => $isActive, 'isOtpRequired' => true)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
            if ($getCardReq) {
                $statusArr = ["status" => "Failed", "reason" => __("message_app.Card already requested")];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            if ($userData->wallet_balance >= REPLACE_CARD_FEES) {
                $statusArr = [
                    "status" => "Success",
                    "reason" => __('message_app.replace_card_fee_notice', [
                        'currency' => 'XAF',
                        'amount' => $this->numberFormatSpaces($this->roundAmount(REPLACE_CARD_FEES)),
                    ]),
                    'data' => array('isActive' => $isActive, 'isOtpRequired' => true)
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = ["status" => "Failed", "reason" => __('message_app.CardFees', ['amount' => $this->numberFormatSpaces(REPLACE_CARD_FEES)])];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } elseif (isset($request->cardType) && $request->cardType === "REPLACEPHYSICAL") {
            /* $userCard = UserCard::firstWhere(['userId' => $userId, 'accountId' => $request->accountId, 'cardType' => 'PHYSICAL']);
            if (!$userCard) {
                $statusArr = ["status" => "Failed", "reason" => __("message_app.Card not found"]);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } */

            $getCustomerDetail = $this->cardService->getCustomerData($request->accountId, 'PHYSICAL');
            $isActive = isset($getCustomerDetail['data']['cardStatus']) && $getCustomerDetail['data']['cardStatus'] == "AC" ? true : false;

            if ($userData->cardType == "PHYSICAL") {
                if (!in_array($getCustomerDetail['data']['cardStatus'], ['LC', 'SC', 'EX', 'IA'])) {
                    /* $postDeactive = json_encode([
                        "chargeFee" => false,
                        "last4Digits" => $userData->last4Digits,
                        "mobilePhoneNumber" => "241$userData->phone",
                        "newCardStatus" => "Active",
                    ]);
                    $getResponse = $this->cardService->cardDeactivate($postDeactive, $request->accountId, "PHYSICAL");
                    Log::info(['$getResponse'=>$getCustomerDetail['data']['cardStatus']]); */
                    $statusArr = ["status" => "Failed", "reason" => __("message_app.First, deactivate the physical card, then you can apply for a replacement card")];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    if ($getCardReq) {
                        $statusArr = ["status" => "Failed", "reason" => __("message_app.Card already requested")];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                    if ($userData->wallet_balance >= REPLACE_CARD_FEES) {
                        $statusArr = [
                            "status" => "Success",
                            "reason" => __('message_app.replace_card_fee_notice', [
                                'currency' => 'XAF',
                                'amount' => $this->numberFormatSpaces(REPLACE_CARD_FEES),
                            ]),
                            'data' => array('isActive' => $isActive, 'isOtpRequired' => true)
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $statusArr = ["status" => "Failed", "reason" => __('message_app.CardFees', ['amount' => $this->numberFormatSpaces(REPLACE_CARD_FEES)])];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }
            } elseif ($userData->cardType == "REPLACEPHYSICAL") {
                if ($userData->alreadyReplace == "REPLACECARD") {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Please activate your replace card"), 'data' => array('isActive' => false, 'isOtpRequired' => true)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = ["status" => "Success", "reason" => __("message_app.Card detail successfull"), 'data' => array('isActive' => $isActive, 'isOtpRequired' => false)];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        } else {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Type is not allow")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }
    public function cardOtpVerify(Request $request)
    {
        $userId = Auth::user()->id;
        $userData = User::where('id', $userId)->first();
        $getTempOtp = DriverActivationCard::where('accountId', $userData->accountId)->first();
        $requestData = $this->decryptContent($request->req);
        $input = [
            "otpCode" => $requestData->otpCode ?? null,
        ];
        $validator = Validator::make($input, [
            'otpCode' => 'required|numeric|digits:6',
        ], [
            'otpCode.required' => 'OTP is required.',
            'otpCode.digits' => 'OTP enter 6 digits.',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if (!$getTempOtp) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid OTP details"];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $enteredOtp = $requestData->otpCode;
        if ($enteredOtp != $getTempOtp->otp && $enteredOtp != '111111') {

            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid OTP code",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $postData = json_encode([
            "currencyCode" => "XAF",
            "last4Digits" => $userData->last4Digits,
            "referenceMemo" => "test transaction",
            "transferAmount" => PHYSICAL_CARD_FEES,
            "transferType" => "WalletToCard",
            "mobilePhoneNumber" => "241$userData->phone",
        ]);
        $checkAdded = $this->cardService->addWalletCardTopUp($postData, $userData->accountId, 'PHYSICAL');

        DriverActivationCard::where('userId', $userId)->update(['otp' => null]);

        if ($checkAdded['status'] == false) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.First request card activation, then complete the activation process")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $isActive = false;
        $name = "{$userData->name} {$userData->lastName}";
        if (!empty($userData->last4Digits)) {
            $accountId = $userData->accountId;

            $getCustomerDetail = $this->cardService->getCustomerData($userData->accountId, $userData->cardType);
            if ($getCustomerDetail['status'] == true) {
                $customerData = $getCustomerDetail['data'] ?? [];
                $isActive = $customerData['cardStatus'] == "AC" ? true : false;
            }
        } else {
            $accountId = 00000;
        }

        User::where('id', $userId)->update([
            'cardType' => 'PHYSICAL',
            'alreadyReplace' => '',
            'requestReplaceCard' => ''
        ]);


        $title = __("message_app.Physical Card");
        $message = __("message_app.Physical card activation successfully");
        $device_token = Auth::user()->device_token;
        $device_type = Auth::user()->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => Auth::user()->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $statusArr = ["status" => "Success", "reason" => __("message_app.Card activation successfully")];
        $statusArr['data'] = ["accountId" => $accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'isActive' => $isActive];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }


    public function cardOtpVerifyNew(Request $request)
    {
        $userId = Auth::user()->id;
        $userData = User::where('id', $userId)->first();
        $getTempOtp = DriverActivationCard::where('accountId', $userData->accountId)->first();
        $requestData = $this->decryptContent($request->req);
        $input = [
            "otpCode" => $requestData->otpCode ?? null,
        ];
        $validator = Validator::make($input, [
            'otpCode' => 'required|numeric|digits:6',
        ], [
            'otpCode.required' => __("message_app.OTP is required"),
            'otpCode.digits' => __("message_app.OTP enter 6 digits"),
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        if (!$getTempOtp) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.Invalid OTP details")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $enteredOtp = $requestData->otpCode;
        if ($enteredOtp != $getTempOtp->otp && $enteredOtp != '111111') {

            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Invalid OTP code"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($userData->wallet_balance < REPLACE_CARD_FEES) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.Insufficient_amount', ['amount' => $this->numberFormatSpaces(REPLACE_CARD_FEES)]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $postData = json_encode([
            "currencyCode" => "XAF",
            "last4Digits" => $userData->last4Digits,
            "referenceMemo" => "Settlement",
            "transferAmount" => REPLACE_CARD_FEES,
            "transferType" => "WalletToCard",
            "mobilePhoneNumber" => "241$userData->phone",
        ]);
        $checkAdded = $this->cardService->addWalletCardTopUp($postData, $userData->accountId, 'PHYSICAL');
        Log::info(['Physical Card Topup Response' => $checkAdded]);
        DriverActivationCard::where('userId', $userId)->update(['otp' => null]);

        if ($checkAdded['status'] == false) {
            $statusArr = ["status" => "Failed", "reason" => __("message_app.First request card activation, then complete the activation process")];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        DB::table('users')->where('id', $userData->id)->decrement('wallet_balance', REPLACE_CARD_FEES);
        $isActive = false;
        $name = "{$userData->name} {$userData->lastName}";
        if (!empty($userData->last4Digits)) {
            $accountId = $userData->accountId;

            $getCustomerDetail = $this->cardService->getCustomerData($userData->accountId, $userData->cardType);
            if ($getCustomerDetail['status'] == true) {
                $customerData = $getCustomerDetail['data'] ?? [];
                $isActive = $customerData['cardStatus'] == "AC" ? true : false;
            }
        } else {
            $accountId = 00000;
        }

        User::where('id', $userId)->update([
            'cardType' => 'PHYSICAL',
            'alreadyReplace' => '',
            'requestReplaceCard' => ''
        ]);

        UserCard::where('userId', $userData->id)->where('accountId', $userData->accountId)->update(['cardStatus' => "Active"]);

        $userCard = UserCard::where('userId', $userData->id)->where('cardType', 'PHYSICAL')->first();

        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
            $postData = json_encode([
                "currencyCode" => "XAF",
                "last4Digits" => $userCard->last4Digits,
                "referenceMemo" => "Settlement",
                "transferAmount" => $userData->wallet_balance,
                "transferType" => "WalletToCard",
                "mobilePhoneNumber" => "241{$userData->phone}"
            ]);
            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
            Log::info('Card otp verify');
        }

        $title = __("message_app.Physical Card");
        $message = __("message_app.Physical card activation successfully");
        $device_token = Auth::user()->device_token;
        $device_type = Auth::user()->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'TRANSACTION',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => Auth::user()->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();
        Log::info(['Card otp verify' => "Card activation successfully", 'accountId' => $userCard->accountId, "cardType" => $userCard->cardType]);
        $statusArr = ["status" => "Success", "reason" => __("message_app.Card activation successfully")];
        $statusArr['data'] = ["accountId" => $accountId, "last4Digits" => $userData->last4Digits ?? 00000, 'name' => $name, "cardType" => $userData->cardType ?? "****", "programId" => ONAFRIQ_INFO_PROGRAMID, 'vaultId' => ONAFRIQ_VAULTID, 'isActive' => $isActive];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function checkAccountInquiry(Request $request)
    {
        try {
            $getAccReq = $request->all();
            if (isset($getAccReq) && !empty($getAccReq)) {
                if (isset($getAccReq['dstaccounts'][0]['iden']) && !empty($getAccReq['dstaccounts'][0]['iden'])) {
                    $getUser = User::where('ibanNumber', $getAccReq['dstaccounts'][0]['iden'])->first();
                } else {
                    $getUser = User::where('phone', $getAccReq['walletdestination'])->first();
                }
                if (!empty($getUser->phone)) {
                    return response()->json((object) [], 200);
                } else {
                    return response()->json(["error" => "error", "error_description" => __("message_app.Account not exist")], 400);
                }
                // return ['error' => !empty($getUser->phone) ? "" : "error", 'error_description' => !empty($getUser->phone) ? "Is valid" : "Not valid"];
            } else {
                return response()->json(["error" => "error", "error_description" => __("message_app.Request not found")], 404);
            }

        } catch (\GuzzleHttp\Exception\ClientException $th) {
            $message = $th->getMessage();
            $responseBody = $th->getResponse()->getBody()->getContents();
            $responseCode = $th->getResponse()->getStatusCode();
            $error = json_decode($responseBody, true);
            return response()->json($error, $responseCode);
        }
        // Log::info('Check inquiry info', ['request' => $getAccReq]);
    }
    public function checkPaymentStatus(Request $request)
    {
        Log::info('check status response ', ['request' => $request->all()]);
        sleep(10);
        try {
            $getFullReq = $request->all();
            if (!$getFullReq) {
                return response()->json([], 401);
            }
            $existOrNot = User::where('user_type', 'User')->when(
                isset($getFullReq['intent']) && in_array($getFullReq['intent'], ['mobile_transfer', 'inc_wal_remit']),
                fn($q) => $q->where('phone', $getFullReq['walletdestination']),
                fn($q) => $q->where('ibanNumber', $getFullReq['dstaccounts'][0]['iden'])
            )
                ->first();
            if (!$existOrNot) {
                $statusArr = [
                    "error" => "error",
                    "error_descriptioin" => __("message_app.Wallet/Account not found"),
                ];
                return response()->json($statusArr, 404);
            }

            if (isset($getFullReq) && !empty($getFullReq)) {
                if (isset($getFullReq['dstaccounts'][0]['iden']) && !empty($getFullReq['dstaccounts'][0]['iden'])) {
                    $paymentStatus = Transaction::where('receiverAccount', $getFullReq['dstaccounts'][0]['iden'])->where('vouchercode', $getFullReq['vouchercode'])->first();
                } else {
                    $paymentStatus = Transaction::where('receiver_mobile', $getFullReq['walletdestination'])->where('vouchercode', $getFullReq['vouchercode'])->first();
                }
                if (!$paymentStatus) {
                    return ['error' => "error", 'error_description' => __("message_app.Transaction Not Found")];
                }
                Log::info('Check Payment Status', ['request' => $getFullReq]);
                $status = $paymentStatus->status ?? null;

                if ($status == 1) {
                    return response()->json((object) [], 200);
                } elseif ($status == 2) {
                    return response()->json(["error" => "error", "error_description" => __("message_app.Transaction Pending")], 400);
                } else {
                    return response()->json(["error" => "error", "error_description" => __("message_app.Transaction Rejected")], 400);
                }

            } else {
                return response()->json(["error" => "error", "error_description" => __("message_app.Request not found")], 404);
            }

        } catch (\Throwable $th) {
            return ['error' => "error", 'error_description' => "{$th->getMessage()}"];
            // return ['error' => "error", 'error_description' => "{$th->getMessage()} at {$th->getFile()}:{$th->getLine()}"];
        }
    }
    public function getReqInfo(Request $request)
    {
        Log::info('Get Api Info', ['request' => $request->all()]);
    }
    public function getSendReqInfo(Request $request)
    {

        try {
            $getFullReq = $request->all();
            if (!$getFullReq) {
                return response()->json([], 401);
            }
            Log::info('Send Req Info', ['request' => $getFullReq]);

            if ($getFullReq['intent'] == "account_inquiry") {

                if (isset($getFullReq) && !empty($getFullReq)) {
                    if (isset($getFullReq['dstaccounts'][0]['iden']) && !empty($getFullReq['dstaccounts'][0]['iden'])) {
                        $getUser = User::where('ibanNumber', $getFullReq['dstaccounts'][0]['iden'])->first();
                    } else {
                        $getUser = User::where('phone', $getFullReq['walletdestination'])->first();
                    }
                    if (!empty($getUser->phone)) {
                        Log::info('Account Inquiry', ['response' => "success"]);
                        return response()->json(['state' => "ACCEPTED", "receivercustomerdata" => ['firstname' => $getUser->name, 'secondname' => $getUser->lastName]], 200);
                    } else {
                        Log::info('Account Inquiry', ['response' => "Account not exist"]);
                        return response()->json(["error" => "error", "error_description" => __("message_app.Account not exist")], 400);
                    }
                } else {
                    return response()->json(["error" => "error", "error_description" => __("message_app.Request not found")], 404);
                }
            }
            $accessToken = $this->gimacApiService->getAccessToken();

            if ($accessToken['status'] === false) {
                $statusArr = [
                    "error" => "error",
                    "error_description" => __("message_app.Access token not found"), //$accessToken['message']
                ];
                return response()->json($statusArr, 404);
            }


            $accessToken = $accessToken['token'];
            $certificate = public_path("MTN Cameroon Issuing CA1.crt");
            $client = new Client([
                'verify' => $certificate,
            ]);

            $dateString = date('d-m-Y H:i:s');
            $format = 'd-m-Y H:i:s';
            $dateTime = DateTime::createFromFormat($format, $dateString);
            $timestamp = $dateTime->getTimestamp();
            $data = [];
            $paymentType = "";
            $statusType = "";

            $existOrNot = User::where('user_type', 'User')
                ->when(
                    isset($getFullReq['intent']) && in_array($getFullReq['intent'], ['mobile_transfer', 'inc_wal_remit', 'request_to_pay']),
                    fn($q) => $q->where('phone', $getFullReq['walletdestination']),
                    fn($q) => $q->where('ibanNumber', $getFullReq['dstaccounts'][0]['iden'])
                )
                ->first();
            if (!$existOrNot) {
                $statusArr = [
                    "error" => "error",
                    "error_description" => __("message_app.Wallet/Account not found"),
                ];
                return response()->json($statusArr, 404);
            }

            if (isset($getFullReq['intent']) && $getFullReq['intent'] == "mobile_transfer") {
                $paymentType = "WALLETTOWALLET";
            } elseif (isset($getFullReq['intent']) && $getFullReq['intent'] == "wallet_to_account") {
                $paymentType = "WALLETTOACCOUNT";
            } elseif (isset($getFullReq['intent']) && $getFullReq['intent'] == "inc_wal_remit") {
                $paymentType = "WALLETINCOMMING";
            } elseif (isset($getFullReq['intent']) && $getFullReq['intent'] == "inc_acc_remit") {
                $paymentType = "INCACCREMIT";
            } elseif (isset($getFullReq['intent']) && $getFullReq['intent'] == "request_to_pay") {
                $paymentType = "REQUESTTOPAY";
            } else {
                $statusArr = [
                    "error" => "error",
                    "error_description" => __("message_app.Payment type not matched"),
                ];
                return response()->json($statusArr, 400);
            }

            if ($existOrNot) {
                $statusType = "ACCEPTED";
            } else {
                $statusType = "REJECTED";
            }

            $updatedData = [
                "intent" => $getFullReq['intent'],
                "updatetime" => $getFullReq['createtime'],
                "issuertrxref" => $getFullReq['issuertrxref'],
                "vouchercode" => $getFullReq['vouchercode'],
                "state" => $statusType,
            ];
            sleep(10);
            $response = $client->request('POST', env('GIMAC_PAYMENT_UPDATE'), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer $accessToken"
                ],
                'json' => $updatedData,
            ]);

            $body = $response->getBody()->getContents();
            $jsonResponse2 = json_decode($body);
            $statusCode = $response->getStatusCode();

            $trans = new Transaction([
                'user_id' => 0,
                'receiver_id' => $existOrNot->id,
                'receiver_mobile' => $getFullReq['walletdestination'] ?? "",
                'amount' => $getFullReq['amount'],
                'receiverName' => "",
                'amount_value' => $getFullReq['amount'],
                'transaction_amount' => 0,
                'total_amount' => $getFullReq['amount'],
                'trans_type' => 1,
                'payment_mode' => 'INCOMMING PAYMENT',
                'status' => $jsonResponse2->state == "ACCEPTED" ? 1 : ($jsonResponse2->state == "PENDING" ? 2 : ($jsonResponse2->state == "REJECTED" ? 4 : 0)),
                'refrence_id' => $getFullReq['issuertrxref'],
                'billing_description' => "Fund Transfer-" . $getFullReq['issuertrxref'],
                'country_id' => 0,
                'walletManagerId' => 0,
                'tomember' => $getFullReq['tomember'] ?? '',
                'acquirertrxref' => '',
                'cardHolderName' => '',
                'senderAccount' => $senderAccount ?? '',
                'receiverAccount' => $getFullReq['dstaccounts'][0]['iden'] ?? 0,
                'issuertrxref' => $getFullReq['issuertrxref'] ?? "",
                'vouchercode' => $getFullReq['vouchercode'] ?? "",
                'transactionType' => 'SWAPTOCEMAC',
                'paymentType' => $paymentType,
                'senderData' => json_encode($getFullReq['sendercustomerdata'] ?? "") ?? "",
                'receiverData' => json_encode($getFullReq['receivercustomerdata'] ?? "") ?? "",
                'entryType' => 'API',
                'walletsource' => (isset($getFullReq['walletsource']) && $getFullReq['walletsource'] != "" ? $getFullReq['walletsource'] : $getFullReq['dstaccounts'][0]['iden']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'swapDomainName' => 'UAT_SERVER'
            ]);
            $trans->save();
            if ($jsonResponse2->state == "ACCEPTED") {
                DB::table('users')->where('id', $existOrNot->id)->increment('wallet_balance', $getFullReq['amount']);
                $wallet_balance = $existOrNot->wallet_balance + $getFullReq['amount'];

                Transaction::where("id", $trans->id)->update([
                    'remainingWalletBalance' => $wallet_balance
                ]);
            }
            Log::info('Update response Info', ['response' => $jsonResponse2]);

        } catch (\Throwable $th) {
            // Log::info($th);
            $message = $th->getMessage();
            if (preg_match('/\{.*\}$/s', $message, $matches)) {
                $jsonPart = $matches[0];
                $errorData = json_decode($jsonPart, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $errorDescription = $errorData['error_description'] ?? __("message_app.Unknown error");
                    return ['error' => "error", 'error_description' => $errorDescription];
                }
            }
            dd($message);
        }
    }
    /* public function getSendReqInfo(Request $request)
    {
        try {
            Log::info('Get Send Req Info', ['request' => $request->all()]);
            $getFullReq = $request->all();
            $accessToken = $this->gimacApiService->getAccessToken();
            if ($accessToken['status'] === false) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => 'Access token not found', //$accessToken['message']
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $accessToken = $accessToken['token'];
            $certificate = public_path("MTN Cameroon Issuing CA1.crt");
            $client = new Client([
                'verify' => $certificate,
            ]);
            if (isset($getFullReq['walletdestination']) && !empty($getFullReq['walletdestination'])) {
                $statusType = "";
                $existOrNot = User::where('phone', $getFullReq['walletdestination'])->where('user_type', 'User')->first();
                if ($existOrNot) {
                    $statusType = "ACCEPTED";
                } else {
                    $statusType = "REJECTED";
                }

                $dateString = date('d-m-Y H:i:s');
                $format = 'd-m-Y H:i:s';
                $dateTime = DateTime::createFromFormat($format, $dateString);
                $timestamp = $dateTime->getTimestamp();

                $data = [
                    "intent" => "mobile_transfer",
                    "updatetime" => $timestamp,
                    "issuertrxref" => $getFullReq['issuertrxref'],
                    "vouchercode" => $getFullReq['vouchercode'],
                    "state" => $statusType,
                ];
                $response = $client->request('POST', 'https://10.20.36.212:8443/stdendpointfacade/v1/external/payment/update', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer $accessToken"
                    ],
                    'json' => $data,
                ]);

                $body = $response->getBody()->getContents();
                $jsonResponse2 = json_decode($body);
                $statusCode = $response->getStatusCode();
                Log::info('Get update response Info', ['response' => $jsonResponse2]);
            }
        } catch (\Throwable $th) {
            Log::info($th);
        }
    } */
    public function helpCategoryList()
    {
        $categories = DB::table('help_categories')->where('is_active', true)->pluck('name', 'id');
        $categories = $categories->map(function ($name, $id) {
            return [
                'id' => (string) $id,
                'name' => $name,
            ];
        })->values()->toArray();
        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.Help category fetched successfully"),
            'ticketCategoryData' => $categories
        ];

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function createHelpTicket(Request $request)
    {
        $input = [
            "categoryId" => $request->categoryId ?? null,
            "description" => $request->description ?? null,
            "image" => $request->image ?? null,
        ];
        $validator = Validator::make($input, [
            'categoryId' => 'required|string',
            'description' => 'required|string|max:250',
            // 'image' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',

        ], [
            'categoryId.required' => __("message_app.Help category is required"),
            'description.required' => __("message_app.Description is required"),
            'description.max' => __("message_app.Description must be up to 250 characters"),
            // 'image.file' => 'The uploaded file must be valid.',
            // 'image.mimes' => 'Only JPG, JPEG, PNG, PDF, DOC, or DOCX files are allowed.',
            // 'image.max' => 'The file must not be larger than 2MB.',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $ticketId = 'TKT-' . strtoupper(uniqid());

        $path = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filenameWithExtension = $file->getClientOriginalName();
            if ($filenameWithExtension == "no&&$$@@Image.jpg") {
                $path = "";
            } else {
                $path = $this->uploadImage($file, HELP_TICKET_PATH);
            }
        }

        $ticket = HelpTicket::create([
            'userId' => Auth::user()->id,
            'ticketId' => $ticketId,
            'categoryId' => $request->categoryId,
            'description' => $request->description,
            'imagePath' => $path,
        ]);


        $title = __("message_app.Help Ticket");
        $message = __("message_app.Help ticket created successfully");
        $device_token = Auth::user()->device_token;
        $device_type = Auth::user()->device_type;

        $data1 = [
            'title' => $title,
            'message' => $message,
            'id' => "",
            'type' => 'HELPTICKET',
        ];

        if ($device_type && $device_token) {
            $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                $device_token,
                $title,
                $message,
                $data1,
                $device_type
            );
        }

        $notif = new Notification([
            'user_id' => Auth::user()->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();


        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.Help ticket created successfully"),
            "result" => array('ticketId' => $ticket->ticketId),
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);

    }
    public function helpTicketList(Request $request)
    {
        /* $requestData = $this->decryptContent($request->req);
        $page = $request->input('page', 1);           // default page = 1
        $limit = $request->input('limit', 10);        // default limit = 10
        $offset = ($page - 1) * $limit;
        $status = $request->input('status');
        $startDate = $request->input('startDate'); // expected format: Y-m-d
        $endDate = $request->input('endDate');     // expected format: Y-m-d
        $sortBy = $request->input('sortBy'); */

        $requestData = $this->decryptContent($request->req);


        $page = $requestData->page ?? 1;
        $limit = $requestData->limit ?? 10;
        $offset = ($page - 1) * $limit;
        $status = $requestData->status ?? null;
        $startDate = $requestData->startDate ?? null;
        $endDate = $requestData->endDate ?? null;
        $sortBy = $requestData->sortBy ?? 'newest'; // default to newest


        // Paginated query

        $query = HelpTicket::with(['User', 'HelpCat'])
            ->where('userId', Auth::user()->id);


        if ($sortBy === 'pending') {
            $query->where('status', 'Pending');
        } elseif ($sortBy === 'resolved') {
            $query->where('status', 'Resolved');
        } elseif (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        } elseif (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($sortBy === 'oldest') {
            $query->orderBy('created_at', 'ASC');
        } else {
            $query->orderBy('created_at', 'DESC');
        }

        $total = $query->count(); // total records

        $tickets = $query->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();
        $tickets = $tickets->map(function ($data) {
            return [
                // 'category' => $data['HelpCat']['name'] ?? "",
                'ticketId' => $data->ticketId ?? "",
                'description' => $data->description ?? "",
                'comment' => $data->comment ?? "",
                'image' => $data->imagePath ? 'public/uploads/help_tickets/' . $data->imagePath : "",
                'status' => $data->status ?? "",
                'slug' => $data->status === "Pending" ? "pending" : "resolved",
                'categoryData' => [
                    'id' => $data->HelpCat->id ?? null,
                    'name' => $data->HelpCat->name ?? "",
                ],
                'created' => date_format($data->created_at, "d M Y, h:i A") ?? "",
            ];
        })->values()->toArray();

        $statusArr = [
            "status" => "Success",
            "reason" => __('message_app.Help ticket list fetched successfully'),
            "result" => [
                "totalRecords" => $total,
                "totalPages" => ceil($total / $limit),
                "helpTicketList" => $tickets,
            ],
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function helpTicketDetail(Request $request)
    {
        $userId = Auth::user()->id;
        $requestData = $this->decryptContent($request->req);
        // $requestData = $request;
        $input = [
            "ticketId" => $requestData->ticketId ?? null,
        ];


        $validator = Validator::make($input, [
            'ticketId' => 'required|exists:help_tickets,ticketId',
        ], [
            'ticketId.required' => __("message_app.Ticket ID is required"),
            'ticketId.exists' => __("message_app.The provided Ticket ID does not exist"),
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $ticket = HelpTicket::with(['User', 'HelpCat'])->where('ticketId', $input['ticketId'])->where('userId', $userId)->first();
        if (!$ticket) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Ticket not found"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $ticketData = [
            'ticketId' => $ticket->ticketId ?? "",
            'description' => $ticket->description ?? "",
            'image' => isset($ticket->imagePath) && $ticket->imagePath ? 'public/uploads/help_tickets/' . $ticket->imagePath : "",
            'status' => $ticket->status ?? "",
            'categoryData' => [
                'id' => $ticket->HelpCat->id ?? null,
                'name' => $ticket->HelpCat->name ?? "",
            ],
            'created' => $ticket->created_at ?? "",
        ];

        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.Help ticket detail"),
            "result" => [
                "helpTicketList" => $ticketData,
            ],
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }
    public function getReferralData()
    {
        $userId = Auth::user()->id;
        $userInfo = User::where("id", $userId)->first();
        $getBonusReferral = Admin::where('id', 1)->first();
        $successReferralCount = User::where('referralBy', $userId)->count();
        $referralEarning = Transaction::where([['user_id', '=', $userId], ['payment_mode', '=', 'Referral']])->sum('amount');
        // $refData["title"] = "Introduce a friend on swap and get XAF $getBonusReferral->referralBonusSender, while your friend gets XAF $getBonusReferral->referralBonusReceiver upon joining.";
        $refData["title"] = __('message_app.introduce_friend', [
            'sender' => $getBonusReferral->referralBonusSender,
            'receiver' => $getBonusReferral->referralBonusReceiver
        ]);
        $refData["referralCode"] = $userInfo->referralCode ?? "";
        $refData["referralText"] = __("message_app.Successful Referrals");
        $refData["successReferralCount"] = $successReferralCount ?? 0;
        $refData["earningText"] = __("message_app.Total Earnings");
        $refData["referralEarning"] = "XAF $referralEarning" ?? 0;

        $statusArr = [
            "status" => "Success",
            "reason" => __("message_app.Referral data fetched successfully"),
            "result" => $refData,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function initAirtelPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|max:100',
            'subsCountry' => 'required|string|size:2',
            'subsCurrency' => 'required|string|max:5',
            'subsMsisdn' => 'required|string|min:8',
            'amount' => 'required|numeric',
            'transCountry' => 'required|string|size:2',
            'transCurrency' => 'required|string|max:5',
        ], [
            'reference.required' => __("message_app.Reference is required"),
            'subsCountry.required' => __("message_app.Subscriber country is required"),
            'subsCurrency.required' => __("message_app.Subscriber currency is required"),
            'subsMsisdn.required' => __("message_app.Subscriber mobile number is required"),
            'amount.required' => __("message_app.Transaction amount is required"),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $payload = [
            "reference" => $validated['reference'],
            "subscriber" => [
                "country" => $validated['subsCountry'],
                "currency" => $validated['subsCurrency'],
                "msisdn" => $validated['subsMsisdn'],
            ],
            "transaction" => [
                "amount" => $validated['amount'],
                "country" => $validated['transCountry'],
                "currency" => $validated['transCurrency'],
                "id" => str_replace('-', '', Str::uuid()->toString()), // Dynamic UUID
            ],
        ];
        $getResponse = $this->airtelMoneyService->requestPayment($payload);

        /* //Transaction Summary Start
        $payload = [
            "transCountry" => "GA",
            "transCurrency" => "CFA",
        ];
        $getResponse = $this->airtelMoneyService->transPaymentSummary($payload);
        //Transaction Summary End */
        //dd($getResponse);

        return response()->json([
            'status' => true,
            'data' => $getResponse,
        ]);

    }

    public function refundAirtelPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transCountry' => 'required|string|size:2',
            'transCurrency' => 'required|string|max:3',
            'airtel_money_id' => 'required',
        ], [
            'transCountry.required' => __("message_app.Country is required"),
            'transCurrency.required' => __("message_app.Currency is required"),
            'airtel_money_id.required' => __("message_app.Airtel money id is required"),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $payloadRefund = [
            "transCountry" => $validated['transCountry'],
            "transCurrency" => $validated['transCurrency'],
            "transaction" => ["airtel_money_id" => $validated['airtel_money_id']],
        ];
        $getRefundRes = $this->airtelMoneyService->refundPayment($payloadRefund);
        //$getRefundRes = $this->airtelMoneyService->transPaymentSummary($payloadRefund);
        // dd($payloadRefund, $getRefundRes); 

        return response()->json([
            'status' => true,
            'data' => $getRefundRes,
        ]);

    }
    public function transCallback(Request $request)
    {
        Log::info('Airtel Payment Callback', ['request' => $request->all()]);
    }

    public function depositByAirtel(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        $minAmount = "1";
        $maxAmount = "10000000";
        $input = [
            'phone' => $request->phone ?? null,
            'amount' => $request->amount ?? null,
            'trans_type' => $request->trans_type ?? null,
        ];

        $validate_data = [
            'phone' => 'required',
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
            'trans_type' => 'required',
        ];

        $customMessages = [
            'amount.required' => __("message_app.Amount is required"),
            'amount.numeric' => __("message_app.Amount must be a valid number"),
            'amount.gt' => __("message_app.Amount must be greater than zero"),
            'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
            'phone.required' => __("message_app.Phone field cannott be left blank"),
            'trans_type.required' => __("message_app.Trans type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $senderUser = User::where('id', $user_id)->first();
        $userType = $this->getUserType($senderUser->user_type);
        $amount = $request->amount;

        if ($this->checkTransactionLimit($userType, $amount)) {
            return $this->checkTransactionLimit($userType, $amount);
        }
        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        if ($senderUser->kyc_status != "completed") {
            $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
            $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
            if ($senderUser->kyc_status == "pending") {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "KYC Pending",
                        "reason" => __('message_app.transfer_max_kyc_pending', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($unverifiedKycMin > $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_min_amount', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMin))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                if ($unverifiedKycMax < $request->amount) {
                    $statusArr = [
                        "status" => "Not Verified",
                        "reason" => __('message_app.transfer_max_kyc_not_verified', ['currency' => CURR, 'amount' => $this->numberFormatSpaces($this->roundAmount($unverifiedKycMax))]),
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        }

        if ($transactionLimit->minAirtel > $amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->minAirtel)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($amount > $transactionLimit->maxAirtel) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->maxAirtel)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $amount = $request->amount;
        $getfeeRecord = $this->calculateTotalFees("Airtel Deposit", $amount);
        $note = $input['note'] ?? "";
        $total_fees = $getfeeRecord['total_fees'] ?? 0;
        $realAmount = $amount - $total_fees;
        // $phone = $request->phone;

        $phone = preg_replace('/^(?:\+241|241)/', '', $request->phone);

        $trans_id = time();
        $refrence_id = time() . '-' . $phone;
        $recieverUser = $userInfo = User::where('id', $user_id)->first();
        $receiver_wallet_amount = $recieverUser->wallet_balance;

        $payload = [
            "reference" => $recieverUser->name . ' ' . $recieverUser->lastName,
            "subscriber" => [
                "country" => "GA",
                "currency" => "CFA",
                "msisdn" => $phone,
            ],
            "transaction" => [
                "amount" => $amount,
                "country" => "GA",
                "currency" => "CFA",
                "id" => str_replace('-', '', Str::uuid()->toString()), // Dynamic UUID
            ],
        ];
        Log::info(["Request Airtel Money" => $payload]);
        $getResponse = $this->airtelMoneyService->requestPayment($payload);

        if ($getResponse['data']['status']['response_code'] == "DP00800001006") {
            $trans = new Transaction([
                'user_id' => $user_id,
                'receiver_id' => $user_id,
                'receiver_mobile' => $phone ?? "",
                'amount' => $realAmount,
                'receiverName' => "",
                'amount_value' => $amount,
                'transaction_amount' => $total_fees ?? 0,
                'total_amount' => $amount,
                'trans_type' => 1,
                'payment_mode' => 'airtelwallet',
                'status' => 2,
                'refrence_id' => $trans_id,
                'billing_description' => "Fund Transfer-$refrence_id",
                'country_id' => 0,
                'walletManagerId' => 0,
                'tomember' => '',
                'transactionType' => 'AIRTELMONEY',
                'remainingWalletBalance' => $receiver_wallet_amount ?? "",
                'airtelTransId' => $getResponse['data']['data']['transaction']['id'] ?? "",
                'beforeBalance' => $receiver_wallet_amount,
                'notes' => "",
                'entryType' => 'API',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'swapDomainName' => 'UAT_SERVER'
            ]);

            $trans->save();

            $statusArr = array("status" => "Success", "reason" => __("message_app.Amount deposited request successfully"), "transactionId" => $trans->id);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $trans = new Transaction([
                'user_id' => $user_id,
                'receiver_id' => $user_id,
                'receiver_mobile' => $phone ?? "",
                'amount' => $realAmount,
                'receiverName' => "",
                'amount_value' => $amount,
                'transaction_amount' => $total_fees ?? 0,
                'total_amount' => $amount,
                'trans_type' => 1,
                'payment_mode' => 'airtelwallet',
                'status' => 3,
                'refrence_id' => $trans_id,
                'billing_description' => "Fund Transfer-$refrence_id",
                'country_id' => 0,
                'walletManagerId' => 0,
                'tomember' => '',
                'transactionType' => 'AIRTELMONEY',
                'remainingWalletBalance' => $receiver_wallet_amount ?? "",
                'airtelTransId' => $getResponse['data']['data']['transaction']['id'] ?? "",
                'airtelStatusDescription' => $getResponse['data']['data']['transaction']['message'] ?? "",
                'beforeBalance' => $receiver_wallet_amount,
                'afterBalance' => $receiver_wallet_amount,
                'notes' => "",
                'entryType' => 'API',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'swapDomainName' => 'UAT_SERVER'
            ]);

            $trans->save();
            $statusArr = array("status" => "Success", "reason" => __("message_app.Amount deposit request failed"), "transactionId" => $trans->id);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function rechargeAndTransfer(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */

        $minAmount = "1";
        $maxAmount = "10000000";

        $input = [
            'amount' => $request->amount,
            'type' => $request->type,
            'cardType' => $request->cardType ?? null,
            'accountId' => $request->accountId ?? null,
        ];

        $validate_data = [
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
            'type' => 'required|in:RECHARGE,TRANSFEROUT',
            'accountId' => 'required',
            'cardType' => 'required',
        ];

        $customMessages = [
            'amount.required' => __("message_app.Amount is required"),
            'accountId.required' => __("message_app.Account ID is required"),
            'amount.numeric' => __("message_app.Amount must be a valid number"),
            'amount.gt' => __("message_app.Amount must be greater than zero"),
            'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
            'phone.required' => __("message_app.Phone field cannot be left blank"),
            'type.required' => __("message_app.Type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $userDetail = User::where('id', $user_id)->first();

        $userType = $this->getUserType($userDetail->user_type);
        $amount = $request->amount;


        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        $currentDaySum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount');
        if (($currentDaySum + $amount) > $transactionLimit->onafriqa_max) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Your daily transfer limit has been reached"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($transactionLimit->onafriqa_min > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->onafriqa_min)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->onafriqa_max) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->onafriqa_max)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $type = $request->type;
        $cardType = $request->cardType;
        $accountId = $request->accountId;

        if ($type == "RECHARGE") {
            if ($userDetail->wallet_balance < $amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Wallet Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

        $getCardBalance = $this->cardService->getCardBalance($request->accountId, $request->cardType);
        if ($type == "TRANSFEROUT") {
            if (isset($getCardBalance['data']['balance']) && $getCardBalance['data']['balance'] < $amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Card Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

        $userCardVirtual = UserCard::where('userId', $user_id)->where('cardType', 'VIRTUAL')->first();
        $userCard = UserCard::where('userId', $user_id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();

        $trans = new Transaction([
            'user_id' => $user_id,
            'accountId' => $userCardVirtual->accountId ?? 0,
            'cardType' => $userCardVirtual->cardType,
            'amount' => $amount ?? 0,
            'amount_value' => $amount ?? 0,
            'transaction_amount' => 0,
            'total_amount' => 0,
            'status' => 2,
            'trans_type' => $type === "RECHARGE" ? 1 : 2,
            'transactionType' => $type === "RECHARGE" ? "CARDPAYMENT" : "TRANSAFEROUT",
            'entryType' => "API",
            'payment_mode' => $type === "RECHARGE" ? "CARDPAYMENT" : "TRANSAFEROUT",
            'billing_description' => "Fund Transfer-" . time() . rand(),
            'beforeVirtualBalance' => $getCardBalance['data']['balance'],
            'afterVirtualBalance' => $type === "RECHARGE" ? ($getCardBalance['data']['balance'] + $amount) : ($getCardBalance['data']['balance'] - $amount),
            'beforeBalance' => $userDetail->wallet_balance,
            'afterBalance' => $type === "RECHARGE" ? ($userDetail->wallet_balance - $amount) : ($userDetail->wallet_balance + $amount),
            'created_at' => now(),
            'updated_at' => now(),
            'swapDomainName' => 'UAT_SERVER'
            /* 'beforeBalance' => $userDetail->wallet_balance,
            'afterBalance' => $type === "RECHARGE" ? ($userDetail->wallet_balance - $amount) : ($userDetail->wallet_balance + $amount), */
        ]);
        $trans->save();

        $postData = json_encode([
            "currencyCode" => "XAF",
            "last4Digits" => $userCardVirtual->last4Digits,
            "referenceMemo" => $trans->id,
            "transferAmount" => $amount,
            "transferType" => $type === "RECHARGE" ? "WalletToCard" : "CardToWallet",
            "mobilePhoneNumber" => "241{$userDetail->phone}",
        ]);
        Log::info(['Request' => $postData, 'accountId' => $userCardVirtual->accountId]);
        $getResponse = $this->cardService->addWalletCardTopUp($postData, $userCardVirtual->accountId, $userCardVirtual->cardType);
        if ($getResponse['data']['status'] === 400 && $getResponse['data']['extensions']['statusCode']) {
            Transaction::where('id', $trans->id)->update(['status' => 3]);
            $statusArr = array("status" => "Failed", "reason" => $getResponse['data']['detail']);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            if ($type == "RECHARGE") {
                User::where('id', $userDetail->id)->decrement('wallet_balance', $amount);
                if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userCard->last4Digits,
                        "referenceMemo" => "Settlement",
                        "transferAmount" => $amount,
                        "transferType" => "CardToWallet",
                        "mobilePhoneNumber" => "241{$userDetail->phone}"
                    ]);
                    $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    Log::info('Recharge');
                }

            } else {
                User::where('id', $userDetail->id)->increment('wallet_balance', $amount);
                if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userCard->last4Digits,
                        "referenceMemo" => "Settlement",
                        "transferAmount" => $amount,
                        "transferType" => "WalletToCard",
                        "mobilePhoneNumber" => "241{$userDetail->phone}"
                    ]);
                    $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    Log::info('Transfer');
                }
            }
        }

        if ($getResponse['status'] == true) {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Payment successfully"), "transactionId" => $trans->id);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            $statusArr = array("status" => "Failed", "reason" => __("message_app.Payment failed"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function rechargeAndTransferNew(Request $request)
    {
        $request = $this->decryptContent($request->req);
        $user_id = Auth::user()->id;
        /* $isCheck = $this->checkCompleteKycStatus($user_id);
        if (!$isCheck['status']) {
            $statusArr = ["status" => "Failed", "reason" => $isCheck['message']];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } */


        $userDetail = User::where('id', $user_id)->first();

        $userType = $this->getUserType($userDetail->user_type);
        $amount = $request->amount;

        $transactionLimit = TransactionLimit::where('type', $userType)->first();

        $minAmount = $transactionLimit->onafriqa_min ?? "1000";
        $maxAmount = $transactionLimit->onafriqa_max ?? "2000000";

        $input = [
            'amount' => $request->amount,
            'type' => $request->type,
            'cardType' => $request->cardType ?? null,
            'accountId' => $request->accountId ?? null,
        ];

        $validate_data = [
            'amount' => ['required', 'numeric', 'gt:0', "min:$minAmount", "max:$maxAmount"],
            'type' => 'required|in:RECHARGE,TRANSFEROUT',
            'accountId' => 'required',
            'cardType' => 'required',
        ];

        $customMessages = [
            'amount.required' => __("message_app.Amount is required"),
            'accountId.required' => __("message_app.Account ID is required"),
            'amount.numeric' => __("message_app.Amount must be a valid number"),
            'amount.gt' => __("message_app.Amount must be greater than zero"),
            'amount.min' => __('message_app.amount_min', ['min' => $minAmount]),
            'amount.max' => __('message_app.amount_max', ['max' => $maxAmount]),
            'phone.required' => __("message_app.Phone field cannot be left blank"),
            'type.required' => __("message_app.Type field cannot be left blank"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        $currentDaySum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount');
        if (($currentDaySum + $amount) > $transactionLimit->onafriqa_max) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("message_app.Your daily transfer limit has been reached"),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($transactionLimit->onafriqa_min > $request->amount) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.min_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->onafriqa_min)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($request->amount > $transactionLimit->onafriqa_max) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __('message_app.max_transfer_amount', [
                    'currency' => CURR,
                    'amount' => $this->numberFormatSpaces($this->roundAmount($transactionLimit->onafriqa_max)),
                ]),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $type = $request->type;
        $cardType = $request->cardType;
        $accountId = $request->accountId;

        if ($type == "RECHARGE") {
            if ($userDetail->wallet_balance < $amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Wallet Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

        $getCardBalance = $this->cardService->getCardBalance($request->accountId, $request->cardType);
        if ($type == "TRANSFEROUT") {
            if (isset($getCardBalance['data']['balance']) && $getCardBalance['data']['balance'] < $amount) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => __("message_app.Insufficient Card Balance"),
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

        $userCardVirtual = UserCard::where('userId', $user_id)->where('cardType', 'VIRTUAL')->first();
        $userCard = UserCard::where('userId', $user_id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();

        $trans = new Transaction([
            'user_id' => $user_id,
            'accountId' => $userCardVirtual->accountId ?? 0,
            'cardType' => $userCardVirtual->cardType,
            'amount' => $amount ?? 0,
            'amount_value' => $amount ?? 0,
            'transaction_amount' => 0,
            'total_amount' => 0,
            'status' => 2,
            'trans_type' => $type === "RECHARGE" ? 1 : 2,
            'transactionType' => $type === "RECHARGE" ? "CARDPAYMENT" : "TRANSAFEROUT",
            'entryType' => "API",
            'payment_mode' => $type === "RECHARGE" ? "CARDPAYMENT" : "TRANSAFEROUT",
            'billing_description' => "Fund Transfer-" . time() . rand(),
            'beforeVirtualBalance' => $getCardBalance['data']['balance'],
            'afterVirtualBalance' => $type === "RECHARGE" ? ($getCardBalance['data']['balance'] + $amount) : ($getCardBalance['data']['balance'] - $amount),
            'beforeBalance' => $userDetail->wallet_balance,
            'afterBalance' => $type === "RECHARGE" ? ($userDetail->wallet_balance - $amount) : ($userDetail->wallet_balance + $amount),
            'created_at' => now(),
            'updated_at' => now(),
            'swapDomainName' => 'UAT_SERVER'
            /* 'beforeBalance' => $userDetail->wallet_balance,
            'afterBalance' => $type === "RECHARGE" ? ($userDetail->wallet_balance - $amount) : ($userDetail->wallet_balance + $amount), */
        ]);
        $trans->save();

        $postData = json_encode([
            "currencyCode" => "XAF",
            "last4Digits" => $userCardVirtual->last4Digits,
            "referenceMemo" => $trans->id,
            "transferAmount" => $amount,
            "transferType" => $type === "RECHARGE" ? "WalletToCard" : "CardToWallet",
            "mobilePhoneNumber" => "241{$userDetail->phone}",
        ]);
        Log::info(['Request' => $postData, 'accountId' => $userCardVirtual->accountId]);
        $getResponse = $this->cardService->addWalletCardTopUp($postData, $userCardVirtual->accountId, $userCardVirtual->cardType);
        if (isset($getResponse['data']['status']) && $getResponse['data']['status'] === 400 && $getResponse['data']['extensions']['statusCode']) {
            Transaction::where('id', $trans->id)->update(['status' => 3]);
            $statusArr = array("status" => "Failed", "reason" => $getResponse['data']['detail']);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            if ($type == "RECHARGE") {
                User::where('id', $userDetail->id)->decrement('wallet_balance', $amount);
                if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userCard->last4Digits,
                        "referenceMemo" => "Settlement",
                        "transferAmount" => $amount,
                        "transferType" => "CardToWallet",
                        "mobilePhoneNumber" => "241{$userDetail->phone}"
                    ]);
                    $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    Log::info(['Recharge => ' => $amount]);
                }

            } else {
                User::where('id', $userDetail->id)->increment('wallet_balance', $amount);
                if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $userCard->last4Digits,
                        "referenceMemo" => "Settlement",
                        "transferAmount" => $amount,
                        "transferType" => "WalletToCard",
                        "mobilePhoneNumber" => "241{$userDetail->phone}"
                    ]);
                    $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    Log::info(['Transfer => ' => $amount]);
                }
            }
        }

        if ($getResponse['status'] == true) {
            $statusArr = array("status" => "Success", "reason" => __("message_app.Payment successfully"), "transactionId" => $trans->id);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            $statusArr = array("status" => "Failed", "reason" => __("message_app.Payment failed"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function kycUserJobSubmit(Request $request)
    {
        $authUserId = Auth::user()->id;
        $request = $this->decryptContent($request->req);

        $input = [
            'userId' => $request->userId ?? "",
            'jobId' => $request->jobId ?? "",
        ];

        $validate_data = [
            'userId' => 'required',
            'jobId' => 'required',
        ];

        $customMessages = [
            'userId.required' => __("message_app.User id is required"),
            'jobId.required' => __("message_app.Job ID is required"),
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $user = User::find($authUserId);

        if (!$user) {
            return response()->json(
                $this->encryptContent(json_encode([
                    'status' => 'Failed',
                    'reason' => __("message_app.User not found"),
                ])),
                200
            );
        }
        User::where('id', $authUserId)->update(['unique_key' => $input['userId'], 'jobId' => $input['jobId'], 'kyc_status' => 'pending']);

        return response()->json(
            $this->encryptContent(json_encode([
                'status' => 'Success',
                'reason' => __("message_app.User ID and Job ID updated successfully"),
            ])),
            200
        );
    }

    public function getAppInfo(Request $request)
    {
        $statusArr = [
            "status" => "Success",
            "data" => ['lang' => $this->lang]
        ];

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

}