<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\User;
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
use App\Models\Feature;
use App\Models\Userfeature;
use App\Models\Errorrecords;
use App\Models\GeneratedQrCode;
use DB;
use Input;
use Validator;
use App;
use Illuminate\Support\Facades\Artisan;
use PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use ZipArchive;
use Uni\UniClient;
use Uni\UniException;


class AuthController extends Controller {

    private function encryptContent($content) {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $cipher = $encryption->encryptPlainTextWithRandomIV(
                $content,
                $secretyKey
        );
        return $cipher;
    }

    private function decryptContent($content) {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $plainText = $encryption->decryptCipherTextWithRandomIV(
                $content,
                $secretyKey
        );
        $plainText = $plainText . PHP_EOL;

        return json_decode($plainText);
    }

    private function decryptContentString($content) {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $plainText = $encryption->decryptCipherTextWithRandomIV(
                $content,
                $secretyKey
        );
        $plainText = $plainText . PHP_EOL;

        return $plainText;
    }

    private function generateNumericOTP($n) {
        $generator = "1357902468";
        $result = "";

        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, rand() % strlen($generator), 1);
        }
        return $result;
    }

    private function generateQRCode($qrString, $user_id) {
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

    function asDollars($value) {
        // if ($value < 0) {
        //     return "-" . asDollars(-$value);
        // }
        return number_format($value, 2);
    }

    public function getWalkthroughList() {
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
                "reason" => "Walkthrough Data List",
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
                "reason" => "Walkthrough Data not available.",
            ];
            //            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function loginRegisterOTP(Request $request) {
        try {

            // $statusArr = array("device_token" =>"874364", "device_type" =>"Android","device_id"=>"","type" =>"0");
            // $json = json_encode($statusArr);
            // $requestData = $this->encryptContent($json);
            // echo $requestData; die;
            // $st = $this->decryptContentString("8KI2gVJ/ugZu0RCjSweGwL4OVN0+BHW6ccOwGOko+Vw=");
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
                        ->first();
                if (!empty($userInfo)) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => 'Mobile number already exist',
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                $this->sendSMS($otp_number, $phone);

                $statusArr = [
                    "status" => "Success",
                    "reason" => "OTP sent successfully.",
                    "otpCode" => $otp_number,
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } elseif ($type == "Login") {

                $this->sendSMS($otp_number, $phone);

                $userInfo = User::where("phone", $phone)
                        ->where("user_type", "!=", "")
                        ->where("otp_verify", 1)
                        ->first();
                if (empty($userInfo)) {
                    $statusArr = [
                        "status" => "Failed",
                        "reason" => "User not found",
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
                                "reason" => "Your account might have been temporarily disabled. Please contact us for more details.",
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {

                            $this->sendSMS($otp_number, $phone);

                            $statusArr = [
                                "status" => "Success",
                                "reason" => "OTP sent successfully.",
                                "otpCode" => $otp_number,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {

                        $statusArr = [
                            "status" => "Failed",
                            "reason" => "Number already registered as " . $userInfo->user_type,
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
                                "reason" => "Your account might have been temporarily disabled. Please contact us for more details.",
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {

                            $this->sendSMS($otp_number, $phone);

                            $statusArr = [
                                "status" => "Success",
                                "reason" => "OTP sent successfully.",
                                "otpCode" => $otp_number,
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => "Number already registered as " . $userInfo->user_type,
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

    public function verifyLoginRegisterOTP(Request $request) {
        $requestData = $this->decryptContent($request->req);
        $phone = $requestData->phone;
        $otpCode = $requestData->otpCode;
        $device_token = $requestData->device_token;
        $device_type = $requestData->device_type;
        $user_type = $requestData->user_type;
        $device_id = $requestData->device_id;
        $type = $requestData->type;
        if ($requestData->otpCode == "") {
            $statusArr = ["status" => "Failed", "reason" => "Invalid OTP code"];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        if ($type == "Register") {

            // if ($otpCode != "111111") {
            //     $statusArr = [
            //         "status" => "Failed",
            //         "reason" => "Invalid OTP code",
            //     ];
            //     $json = json_encode($statusArr);
            //     $responseData = $this->encryptContent($json);
            //     return response()->json($responseData, 200);
            // } else {
            //     $statusArr = [
            //         "status" => "Success",
            //         "reason" => "OTP verification completed.",
            //     ];
            //     $json = json_encode($statusArr);
            //     $responseData = $this->encryptContent($json);
            //     return response()->json($responseData, 200);
            // }

            $client = new UniClient([
                'accessKeyId' => UNIMTX_SMS_ACCESS_KEY,
                'accessKeySecret' =>UNIMTX_SMS_SECRET_KEY, // if using simple auth mode, delete this line
                'endpoint' => 'https://api.unimtx.com',
              ]);
            $verificationResult = $client->otp->verify([
                'to' => '+33' . $phone,
                'code' => $otpCode,
            ]);
            if ($verificationResult->data->valid === false) {
                $statusArr = [    
                "status" => "Failed",
                "reason" => "Invalid OTP code",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $statusArr = [
            "status" => "Success",
            "reason" => "OTP verification completed.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);

        } elseif ($type == "Login") {

            $userInfo = User::where("phone", $phone)
                    ->where("user_type", "!=", "")
                    ->where("otp_verify", 1)
                    ->first();

            if (empty($userInfo)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Please provide a registered phone number",
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
                            "Your account might have been temporarily disabled. Please contact us for more details.",
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {

                        $client = new UniClient([
                            'accessKeyId' => UNIMTX_SMS_ACCESS_KEY,
                            'accessKeySecret' =>UNIMTX_SMS_SECRET_KEY, // if using simple auth mode, delete this line
                            'endpoint' => 'https://api.unimtx.com',
                          ]);
                        $verificationResult = $client->otp->verify([
                            'to' => '+33' . $phone,
                            'code' => $otpCode,
                        ]);

                        if ($verificationResult->data->valid === false) {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => "Invalid OTP code",
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
                            User::where("id", $user_id)->update($user);
                            $tokenStr = $userInfo->id . " " . time();
                            $user = $userInfo;
                            $tokenResult = $user->createToken($tokenStr);
                            $token = $tokenResult->token;
                            $token->save();
                            $statusArr = [
                                "status" => "Success",
                                "reason" => "OTP verification completed.",
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
                        "reason" =>
                        "Number already registered as " .
                        $userInfo->user_type,
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
                            "Your account might have been temporarily disabled. Please contact us for more details.",
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {

                        $client = new UniClient([
                            'accessKeyId' => UNIMTX_SMS_ACCESS_KEY,
                            'accessKeySecret' =>UNIMTX_SMS_SECRET_KEY, // if using simple auth mode, delete this line
                            'endpoint' => 'https://api.unimtx.com',
                          ]);
                        $verificationResult = $client->otp->verify([
                            'to' => '+33' . $phone,
                            'code' => $otpCode,
                        ]);

                        if ($verificationResult->data->valid === false) {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => "Invalid OTP code",
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
                            User::where("id", $user_id)->update($user);

                            $tokenStr = $userInfo->id . " " . time();
                            $tokenResult = $userInfo->createToken($tokenStr);
                            $token = $tokenResult->token;
                            $token->save();

                            $statusArr = [
                                "status" => "Success",
                                "reason" => "OTP verification completed.",
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
                        "reason" => "Number already registered as " . $userInfo->user_type,
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        } else if ($type == "UpdateMobile") {

            $client = new UniClient([
                'accessKeyId' => UNIMTX_SMS_ACCESS_KEY,
                'accessKeySecret' =>UNIMTX_SMS_SECRET_KEY, // if using simple auth mode, delete this line
                'endpoint' => 'https://api.unimtx.com',
              ]);
            $verificationResult = $client->otp->verify([
                'to' => '+33' . $phone,
                'code' => $otpCode,
            ]);

            if ($verificationResult->data->valid === false) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Invalid OTP code",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {

                $userId = $requestData->user_id;

                User::where("id", $userId)->update(['phone' => $phone]);

                $statusArr = [
                    "status" => "Success",
                    "reason" => "Phone number has been updated successfully",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function walletHistory(Request $request) {
        $requestData = $this->decryptContent($request->req);

        $type = $requestData->type;

        //$type = 3;
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
            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->orWhere(function($query) use ($user_id) {
               $query->where('receiver_id', $user_id)
               ->where('trans_type', 2)
               ->where('payment_mode', 'Withdraw');
            })->where('status', 1)->orWhere(function($query) use ($user_id) {
                $query->where('user_id', $user_id)
                ->where('trans_type', 1)
                ->where('payment_mode', 'Refund');
             })->where('status', 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2,1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->orWhere(function($query) use ($user_id) {
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

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->orWhere(function($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                ->where('trans_type', 2)
                ->where('payment_mode', 'Withdraw');
             })->where('status', 1)->orWhere(function($query) use ($user_id) {
                $query->where('user_id', $user_id)
                ->where('trans_type', 1)
                ->where('payment_mode', 'Refund');
             })->where('status', 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2,1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->orWhere(function($query) use ($user_id) {
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

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->orWhere(function($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                ->where('trans_type', 2)
                ->where('payment_mode', 'Withdraw');
             })->where('status', 1)->orWhere(function($query) use ($user_id) {
                $query->where('user_id', $user_id)
                ->where('trans_type', 1)
                ->where('payment_mode', 'Refund');
             })->where('status', 1)->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2,1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startOfLastSevenDays, $endOfLastSevenDays])->orWhere(function($query) use ($user_id) {
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

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->orWhere(function($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                ->where('trans_type', 2)
                ->where('payment_mode', 'Withdraw');
             })->where('status', 1)->orWhere(function($query) use ($user_id) {
                $query->where('user_id', $user_id)
                ->where('trans_type', 1)
                ->where('payment_mode', 'Refund');
             })->where('status', 1)->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2,1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateThisMonth, $endDateThisMonth])->orWhere(function($query) use ($user_id) {
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

            $total_credited = Transaction::where('receiver_id', $user_id)->whereIn('trans_type', [1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->orWhere(function($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                ->where('trans_type', 2)
                ->where('payment_mode', 'Withdraw');
             })->where('status', 1)->orWhere(function($query) use ($user_id) {
                $query->where('user_id', $user_id)
                ->where('trans_type', 1)
                ->where('payment_mode', 'Refund');
             })->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->sum('amount_value');

            $total_debited = Transaction::where('user_id', $user_id)->whereIn('trans_type', [2,1,4])->where('payment_mode','!=', 'Refund')->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->orWhere(function($query) use ($user_id) {
                $query->where('receiver_id', $user_id)
                ->where('trans_type', 1)
                ->where('payment_mode', 'Refund');
             })->where('status', 1)->whereBetween('created_at', [$startDateLastMonth, $endDateLastMonth])->sum('total_amount');

            $formattedStartDate = $startDateLastMonth->format('d');
            $formattedEndDate = $endDateLastMonth->format('d M');
            $dateRange = "$formattedStartDate-$formattedEndDate";
        }

        $statusArr = [
            "status" => "Success",
            "total_credit" => strval($total_credited),
            "total_debit" => strval($total_debited),
            "till_date" => $dateRange,
        ];

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function generatePdf(Request $request) {

        $requestData = $this->decryptContent($request->req);

        $transaction_id = $requestData->transaction_id;

        $user_id = Auth::user()->id;

        $userInfo = User::where('id', $user_id)->first();

        $trans = Transaction::where('id', $transaction_id)->get();

        if($trans->isEmpty())
        {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Please provide a valid transaction id",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if($trans[0]->pdf_link!="")
        {
            $statusArr = [
                "status" => "Success",
                "reason"=>"Pdf has been generated successfully",
                "pdf_link" =>$trans[0]->pdf_link,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        global $tranType;

        $transDataArr = '';

        foreach ($trans as $key => $val) {

            $transArr['trans_id'] = $val->id;

            $transArr['payment_mode'] = strtolower($val->payment_mode);
            $transArr['payment_mode_name'] = ucwords(str_replace("_", " ", $val->payment_mode));

            if ($val->receiver_id == 0) {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                $transArr['receiver'] = 'Admin';
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = 0;
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
                    $transArr['payment_mode'] = $val->payment_mode;
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
                        if($val->status==2)
                        {
                        $transArr['trans_type'] = 'Request Debit';
                        }
                        else{
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
                        if($val->status==2)
                        {
                        $transArr['trans_type'] = 'Request Credit';
                        }
                        else{
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

            global $tranStatus;
            $transArr['trans_status'] = $tranStatus[$val->status];
            
            if ($transArr['payment_mode'] == 'agent deposit'){
                $transArr['payment_mode'] = 'agent_deposit';
            }

            $transArr['refrence_id'] = $val->refrence_id;

            $trnsDt = date_create($val->created_at);
            $transDate = date_format($trnsDt, "d M Y, h:i A");

//                        $trnsProcDt = date_create($val->updated_at);
//                        $transProcessDate = date_format($trnsProcDt, "d M Y, h:i A");

            $transArr['trans_date'] = $transDate;
//                        $transArr['trans_process_date'] = $transProcessDate;

            $transDataArr = $transArr;
        }

        $logo_path = 'data:image/png;base64,'.base64_encode(file_get_contents( HTTP_PATH."/public/img/swap.png"));

        $htmlContent = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title></title>
            <style>
                body{margin: 0; padding: 0; background-color: #ccc; font-family:Open Sans, sans-serif !important;}
                .main-table{background-color: #fff;}
                 .main-table tr td{text-align: justify;}
                 .main-table tr td table tr td{font-size: 16px; color: #000; font-weight: 400;}
            </style>
        </head>
        <body>
            <table class="main-table" style="height:100%;" width="550" border="0" cellpadding="0" cellspacing="0" align="center">
                <tbody>
                    <tr>
                        <td>
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
                                <tbody>
                                    <tr>
                                        <td align="center" height="100" style="text-align: center;"> <a href="#" target="_blank"><img src="'.$logo_path.'" alt="image" width="150px">
                                        </a></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" style="padding:0 10px;">
                                                <tbody>
                                                    <tr>
                                                        <td height="40">Total Amount</td>
                                                        <td align="center">&nbsp;</td>
                                                        <td style="text-align:right; padding:10px 0px; color: #000; font-size: 18px; font-weight: 500; border-radius: 15px;">'.$transDataArr['trans_amount_value'].'</td>
                                                    </tr>
                                                    <tr>  
                                                        <td  height="40">&nbsp;</td>
                                                        <td>&nbsp;</td>
                                                        <td height="40">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">Sender Name</td>
                                                        <td align="center">&nbsp;</td>
                                                        <td style="text-align:right;">'.$transDataArr['sender'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">Receiver  Name</td>
                                                        <td align="center">&nbsp;</td>
                                                        <td style="text-align:right;">'.$transDataArr['receiver'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">Received Amount</td>
                                                        <td align="center">&nbsp;</td>
                                                        <td style="text-align:right;">'.$transDataArr['received_amount'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">Transition Fees</td>
                                                        <td align="center">&nbsp;</td>
                                                        <td style="text-align:right;">'.$transDataArr['transaction_fees'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">Transition ID</td>
                                                        <td align="center">&nbsp;</td>
                                                        <td style="text-align:right;">'.$transDataArr['trans_id'].'</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">Reference ID</td>
                                                        <td align="center">&nbsp;</td>
                                                        <td style="text-align:right;">'.$transDataArr['refrence_id'].'</td>
                                                    </tr>

                                                    <tr>
                                                    <td height="40">Transition Date</td>
                                                    <td align="center">&nbsp;</td>
                                                    <td style="text-align:right;">'.$transDataArr['trans_date'].'</td>
                                                    </tr>

                                                    <tr>
                                                        <td height="40">&nbsp;</td>
                                                        <td>&nbsp;</td>
                                                        <td height="40">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">&nbsp;</td>
                                                        <td>&nbsp;</td>
                                                        <td height="40">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <td height="40">&nbsp;</td>
                                                        <td>&nbsp;</td>
                                                        <td height="40">&nbsp;</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </body>
        </html>
        ';

        $pdfOptions = [
            'orientation' => 'portrait', // or 'landscape'
            'margin_top' => 10, // in millimeters
            'margin_right' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'isRemoteEnabled'=>true
            // Add more options as needed
        ];

        $pdf = PDF::loadHTML($htmlContent)->setOptions($pdfOptions);

        $pdfContent = $pdf->output();

        // Define the path where you want to save the PDF
        $savePath = public_path('uploads/transactionPdf/'); // You can adjust the path as needed

        // Generate a unique filename for the PDF
        $filename = 'transaction_' .$transDataArr['trans_id']  . '.pdf';

        // Combine the path and filename to create the full file path
        $filePath = $savePath . $filename;

        // Save the PDF to the specified path
        file_put_contents($filePath, $pdfContent);

        Transaction::where('id', $transaction_id)->update(['pdf_link'=>'public/uploads/transactionPdf/'.$filename]);

        $statusArr = [
            "status" => "Success",
            "reason"=>"Pdf has been generated successfully",
            "pdf_link" =>'public/uploads/transactionPdf/'.$filename,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function sendSMS($otp_number, $mobile) {
  
        $client = new UniClient([
            'accessKeyId' => UNIMTX_SMS_ACCESS_KEY,
            'accessKeySecret' =>UNIMTX_SMS_SECRET_KEY, // if using simple auth mode, delete this line
            'endpoint' => 'https://api.unimtx.com',
          ]);
        $resp = $client->otp->send([
            'to' => '+33'.$mobile,
            'templateId' => 'pub_otp_basic_en', // Replace with the actual OTP template ID
          ]);
        return true;  
        // try {
        // $resp = $client->otp->send([
        //     'to' => '+33'.$mobile,
        //     'templateId' => 'pub_otp_basic_en', // Replace with the actual OTP template ID
        //   ]);
        // return true;
        // } catch (UniException $e) {
        //     $statusArr = [
        //     "status" => "Failed",
        //     "reason" => $e->getMessage(),
        //     ];
        //     $json = json_encode($statusArr);
        //     $responseData = $this->encryptContent($json);
        //     return response()->json($responseData, 200);
        // }

    }

    public function resendOTP(Request $request) {
//        $request->validate(["phone" => "required"]);
        $otp_number = $this->generateNumericOTP(6);
        $this->sendSMS($otp_number, $request->phone);
        $statusArr = [
            "status" => "Success",
            "reason" => "OTP sent successfully.",
            "otp" => $otp_number,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function completeProfileFirstStep(Request $request) {
        $requestData = $this->decryptContent($request->req);

//        echo '<pre>';print_r($requestData);exit;
        $device_token = $requestData->device_token;
        $device_type = $requestData->device_type;
        $user_type = $requestData->user_type;
        $device_id = $requestData->device_id;
        $name = $requestData->name;
        $email = $requestData->email;
        $business_name = $requestData->business_name;
        $is_exist = User::where("phone", $requestData->phone)->where("otp_verify", 1)->count();
        if ($is_exist > 0) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("Account already exist."),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $carbonDate = Carbon::createFromFormat('d/m/Y', $requestData->dob);
        $formattedDate = $carbonDate->format('Y-m-d');

        $user = new User([
            "user_type" => $requestData->user_type,
            "name" => $name,
            "email" => $email,
            "phone" => $requestData->phone,
            "business_name" => $business_name,
            "device_token"=>$device_token,
            "device_type"=>$device_type,
            "device_id"=>$device_id,
            "dob" => $formattedDate,
            "otp_verify" => 1,
            "is_verify" => 1,
            "updated_at" => date("Y-m-d H:i:s"),
            "slug" => $this->createSlug($name, 'users'),
        ]);

        $user->save();
        $userId = $user->id;  

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

        

        $title = "Congratulations! ";
        $message = "Congratulations! You successfully created your account. Welcome to " .
                SITE_TITLE .
                ".";
        $device_type = $requestData->device_type;
        $device_token = $requestData->device_token;
        $this->sendPushNotification($title, $message, $device_type, $device_token);

        $notif = new Notification([
            "user_id" => $userId,
            "notif_title" => $title,
            "notif_body" => $message,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        $notif->save();

        $statusArr = [
            "status" => "Success",
            "reason" => "Profile completed successfully.",
            "access_type" => "Bearer",
            "qrString" => $qrString,
            "access_token" => $tokenResult->accessToken,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function completeProfileSecondStep(Request $request) {
        ini_set("precision", 14);
        ini_set("serialize_precision", -1);
//        $requestData = $this->decryptContent($request->req);
        $isSkipped = $request->isSkipped;
//        echo '<pre>';print_r($_FILES);exit;
//        $identityImage = $requestData->identityImage;
        $userId = Auth::user()->id;

        $images = array();
        if ($isSkipped == '2') {
            User::where('id', $userId)->update(['isProfileCompleted' => '2', 'kyc_status' => 'skipped']);
        } else {

$userDetail = User::where('id', $userId)->first();
            $national_identity_type = $request->national_identity_type;

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

            $currentTimestamp = time();
            $api_key = SMILE_API_KEY;
            $partner_id = SMILE_PARTNER_ID;
            $message = $currentTimestamp . $partner_id . "sid_request";
            $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));

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
                        "user_id": "' . $userDetail->slug . '",
                        "job_id": "' . $userDetail->slug . '",
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
            User::where('id', $userId)->update(['isProfileCompleted' => '2', 'kyc_status' => 'pending', 'national_identity_type' => $national_identity_type, 'selfie_image' => $selfie_image, 'identity_front_image' => $identity_front_image, 'identity_back_image' => $identity_back_image]);
        }

        $statusArr = [
            "status" => "Success",
            "reason" => "KYC details have been successfully submitted!",
        ];

        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function checkKycStatus(Request $request) {

        $users = User::where("kyc_status", 'pending')->orderBy('id', 'DESC')->get();

        $currentTimestamp = time();
        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;
        $message = $currentTimestamp . $partner_id . "sid_request";
        $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));

        foreach ($users as $user) {
            $userId = $user->id;
            $userSlug = $user->slug;
            $curl = curl_init();
            $kk = '{
                    "signature": "' . $signature . '",
                    "timestamp": "' . $currentTimestamp . '",
                     "user_id": "' . $userSlug . '",
                     "job_id": "' . $userSlug . '",
                     "partner_id": "' . $partner_id . '",
                     "image_links": <true || false>,
                     "history": <true || false>
                }';
//            echo '<pre>';print_r($kk);

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
                     "job_id": "' . $userSlug . '",
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
//            echo '<pre>';print_r($responseData);exit;
            $job_complete = $responseData->job_complete;
            $job_success = $responseData->job_success;

            if ($job_complete == 1 && $job_success == true) {

                User::where('slug', $userSlug)->update(['kyc_status' => 'completed']);

                $title = __("KYC varification");
                $message = __("Your KYC varification has been completed successfully");
                $device_type = $user->device_type;
                $device_token = $user->device_token;
                $this->sendPushNotification($title, $message, $device_type, $device_token);
                $notif = new Notification([
                    'user_id' => $user->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();
                echo 'KYC completed for user ID - ' . $userId;
            } else if ($job_complete == 1 && $job_success == '') {
                User::where('slug', $userSlug)->update(['kyc_status' => 'rejected']);

                $title = __("message.KYC varification");
                $message = __("message.Your KYC varification has been rejected.");
                $device_type = $user->device_type;
                $device_token = $user->device_token;
                $this->sendPushNotification($title, $message, $device_type, $device_token);
                $notif = new Notification([
                    'user_id' => $user->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();


                echo 'KYC rejected for user ID - ' . $userId;
            }
            exit;
        }
        exit;
    }

    public function logout(Request $request) {
        $userId = Auth::user()->id;
        
        $request
                ->user()
                ->token()
                ->revoke();
        
        User::where('id', $userId)->update(array('device_type' => '', 'login_status' => 0));
        
        $statusArr = array("status" => "Success", "reason" => __("Logout Successfully."));
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function getWalletBalance(Request $request) {
        $userId = Auth::user()->id;
        $userInfo = User::where("id", $userId)->first();
        $statusArr = [
            "status" => "Success",
            "amount" => $this->asDollars($userInfo->wallet_balance),
            "default_currency" => '₣',
            "reason" => "Wallet Balance Fetched Successfully",
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function profile(Request $request) {
        $userId = Auth::user()->id;

        $requestData = $this->decryptContent($request->req);

        $userInfo = User::where("id", $userId)->first();

        $latitude = $requestData->latitude;
        $longitude = $requestData->longitude;
        $device_id = $requestData->device_id;

        if ($latitude == "" || $longitude == "") {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Please provide latitude & longitude.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if($userInfo->device_id!= $device_id)
        {
            $statusArr = [
                "status" => "Logout",
                "reason" => 'Your token has been expired',
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
        if (!empty($userInfo)) {
            global $kycStatus;
            $userData = [];
            $userData["name"] = $userInfo->name;
            $userData["user_id"] = $userInfo->id;
            $userData["user_type"] = $userInfo->user_type;
            $userData["amount"] = $this->asDollars($userInfo->wallet_balance);
            if ($userInfo->profile_image != "" && $userInfo->profile_image != "no_user.png") {
                $userData["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
            } else {
                $userData["profile_image"] = "public/img/" . "no_user.png";
            }

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
            $userData["kyc_status_title"] = $userInfo->kyc_status ? $kycStatus[$userInfo->kyc_status] : '';
            $userData["qrcode"] = "public/" . $userInfo->qr_code;

            $data["data"] = $userData;

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
                ['id' => 0, 'name' => 'This Week'],
                ['id' => 1, 'name' => 'Last Week'],
                ['id' => 2, 'name' => 'This Month'],
                ['id' => 3, 'name' => 'Last Month'],
                ['id' => 4, 'name' => 'Last Seven Days'],
            ];



            $statusArr = [
                "status" => "Success",
                "amount" => $this->asDollars($amount),
                "reason" => "Profile Details",
            ];
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "You entered wrong phone number.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    function staticPage(Request $request) {
        $requestData = $this->decryptContent($request->req);

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

        $pageInfo = DB::table("pages")
                ->where("slug", $url)
                ->first();

        $statusArr = [
            "status" => "Success",
            "content" => $pageInfo->description,
            "reason" => "Page Detail",
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function updatePhoneNumber(Request $request) {
        $userId = Auth::user()->id;
        $requestData = $this->decryptContent($request->req);
        $phone = $requestData->phone;
        $is_exist = User::where("phone", $requestData->phone)->where("id", '!=', $userId)->where("otp_verify", 1)->count();
        if ($is_exist > 0) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("Phone number already exist."),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $otp_number = $this->generateNumericOTP(6);

        $this->sendSMS($otp_number, $phone);

        $statusArr = [
            "status" => "Success",
            "reason" => "OTP sent successfully.",
            "otpCode" => $otp_number,
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function updateProfileImage(Request $request) {
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
                "reason" => "Profile has been updated successfully",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function updateBasicProfile(Request $request) {
        $userId = Auth::user()->id;
        $requestData = $this->decryptContent($request->req);
        $name = $requestData->name;
        $email = $requestData->email;
        $business_name = $requestData->business_name;
        if ($email != "") {
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
        }

        $phone = $requestData->phone;
        $is_exist = User::where("phone", $requestData->phone)->where("id", '!=', $userId)->where("otp_verify", 1)->count();
        if ($is_exist > 0) {
            $statusArr = [
                "status" => "Failed",
                "reason" => __("Phone number already exist."),
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $carbonDate = Carbon::createFromFormat('d/m/Y', $requestData->dob);
        $formattedDate = $carbonDate->format('Y-m-d');
        $user = User::where('id', $userId)->update([
            "name" => $name,
            "email" => $email,
            "business_name" => $business_name,
            "dob" => $formattedDate,
            "updated_at" => date("Y-m-d H:i:s"),
        ]);
        $statusArr = [
            "status" => "Success",
            "reason" => "Profile has been updated successfully.",
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function kycUpdate(Request $request) {
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

            $title = "Congratulations! ";
            $message = "Congratulations! Your KYC details submitted to admin successfully.";
            $device_type = $userInfo->device_type;
            $device_token = $userInfo->device_token;

            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                "reason" => "KYC details updated successfully.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } catch (\Exception $ex) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Unknown Exception",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function feedback(Request $request) {
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

        $title = "Congratulations! ";
        $message = "Congratulations! Your feedback has sent successfully.";
        $device_type = $userInfo->device_type;
        $device_token = $userInfo->device_token;

        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
            "reason" => "Feedback sent successfully.",
        ];
        return response()->json($statusArr, 200);
    }

    public function requestList(Request $request) {
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

            $statusArr = ["status" => "Success", "reason" => "Request List"];
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            return response()->json($json, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Request not available.",
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function cancelAcceptRequest(Request $request) {
        $user_id = Auth::user()->id;

        $request_id = $request->request_id;

        $request_type = $request->request_type;

        $userInfo = User::where("id", $user_id)->first();

        if($userInfo->kyc_status!="completed")
        { 
        
            if($userInfo->kyc_status=="pending")
            { 
            
            $statusArr = [
            "status" => "KYC Pending",
            "reason" => 'Your KYC is pending',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
    
            }
            else{
                $statusArr = [
                    "status" => "Not Verified",
                    "reason" => 'Please verify your KYC',
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
        
        


        if ($user_id == "" or!is_numeric($user_id)) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid User id"];
            return response()->json($statusArr, 200);
        } elseif ($request_id == "" or!is_numeric($request_id)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid Request id",
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

                        Transaction::where("id", $request_id)->update([
                            "status" => 1,
                            "trans_type" => $type,
                        ]);

                        $receiverInfo = User::where("id", $requestDetail->receiver_id)->first();
                        $wallet_balance = $receiverInfo->wallet_balance + $requestDetail->amount;
                        User::where("id", $receiverInfo->id)->update(["wallet_balance" => $wallet_balance,]);

                        $wallet_balance = $userInfo->wallet_balance - $requestDetail->amount;
                        User::where("id", $userInfo->id)->update(["wallet_balance" => $wallet_balance,]);

                        $title = "Congratulations! ";
                        $message = "Congratulations! Your request successfully accepted for deposit of amount " . $requestDetail->amount;
                        $device_type = $receiverInfo->device_type;
                        $device_token = $receiverInfo->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                            "reason" => "You have insufficient balance to accept request.",
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

                    $title = "Congratulations! ";
                    $message = "Congratulations! Your request successfully accepted for withdraw of amount " .
                            $requestDetail->amount;
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                    "reason" => "Request Accepted Successfully",
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
                        "refrence_id" => $refrence_id,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $trans->save();

                    $title = "Congratulations! ";
                    $message = "Congratulations! Your request rejected for withdraw of amount " .
                            $requestDetail->total_amount;
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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

                    $title = "Congratulations! ";
                    $message = "Congratulations! Your request accepted for deposit of amount " .
                            $requestDetail->amount;
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                    "reason" => "Request Rejected Successfully",
                ];
            }

            return response()->json($statusArr, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid request id.",
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function cashCardList(Request $request) {
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

            $statusArr = ["status" => "Success", "reason" => "Cash Card List"];
            $json = array_merge($statusArr, $data);
            return response()->json($json, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Cash card not available.",
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function buyCashCard(Request $request) {
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
                    "created_at" => date("Y-m-d H:i:s"),
                    "updated_at" => date("Y-m-d H:i:s"),
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

                $title = "Buy Cash Card";
                $message = "Successful purchase of cash card equivalent to " .
                        CURR .
                        " " .
                        $carddetail->card_value .
                        " from System." .
                        $carddetail->card_value .
                        " " .
                        CURR .
                        " Card Number " .
                        $carddetail->card_number;
                $device_type = $userInfo->device_type;
                $device_token = $userInfo->device_token;

                //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                    "reason" => "Transaction Completed",
                ];
                $json = array_merge($statusArr, $data);
                return response()->json($json, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" =>
                    "You have insufficient balance to purchase card.",
                ];
                return response()->json($statusArr, 200);
            }
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Cash card not available.",
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function merchantTransactions(Request $request) {
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
            'page.required' => 'Page field can\'t be left blank',
            'limit.required' => 'Limit field can\'t be left blank',
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
                    ->where("refund_status", 0)
                    ->where("receiver_id", $user_id)
                    ->orderBy("id", "DESC")
                    ->skip($start)
                    ->take($limit)
                    ->get();

            $totalRecords = Transaction::where("created_at", ">=", $NewDate)
                    ->where("payment_mode", "!=", "Refund")
                    ->where("payment_mode", "!=", "Withdraw")
                    ->where("trans_type", 1)
                    ->where("refund_status", 0)
                    ->where("receiver_id", $user_id)
                    ->orderBy("id", "DESC")
                    ->count();

            if ($trans->isEmpty()) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => 'No Record Found',
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
                $userRecordArr["name"] = $request->User->name;
                $userRecordArr["phone"] = $request->User->phone;
                $userData["amount"] = $this->numberFormatPrecision($request->amount_value, 2, ".");
                if ($request->User->profile_image != "" && $request->User->profile_image != "no_user.png") {
                    $userRecordArr["profile_image"] = PROFILE_FULL_DISPLAY_PATH .
                            $request->User->profile_image;
                } else {
                    $userRecordArr["profile_image"] = "public/img/" . "no_user.png";
                }
                $userData["userData"] = $userRecordArr;
                $transDataArr[] = $userData;
            }

            $statusArr = [
                "status" => "Success",
                "reason" => "Transaction List.",
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
                "reason" => "Invalid User.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function refundPayment(Request $request) {
        $user_id = Auth::user()->id;

        $request = $this->decryptContent($request->req);

        $transaction_id = $request->transaction_id;

        //$userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where("id", $user_id)->first();
        if ($user_id == "" or!is_numeric($user_id)) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid User id"];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } elseif ($transaction_id == "" or!is_numeric($transaction_id)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid Transaction id",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $requestDetail = Transaction::where("id", $transaction_id)->first();
        if($userInfo->wallet_balance < $requestDetail->amount_value)
        {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Insufficient Balance",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        if (!empty($requestDetail)) {
            $receiverInfo = User::where("id", $requestDetail->receiver_id)->first();
            $wallet_balance = $receiverInfo->wallet_balance - $requestDetail->amount_value;
            User::where("id", $receiverInfo->id)->update([
                "wallet_balance" => $wallet_balance,
            ]);

            $senderInfo = User::where("id", $requestDetail->user_id)->first();
            $sender_wallet_balance = $senderInfo->wallet_balance + $requestDetail->amount_value;
            User::where("id", $senderInfo->id)->update([
                "wallet_balance" => $sender_wallet_balance,
            ]);

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
                "refrence_id" => $refrence_id,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $trans->save();
            Transaction::where("id", $transaction_id)->update([
                "refund_status" => 1,
            ]);

            $title = "Congratulations! ";
            $message = "Congratulations! Refund send successfully to user of " .
                    CURR .
                    " " .
                    $requestDetail->amount;
            $device_type = $receiverInfo->device_type;
            $device_token = $receiverInfo->device_token;

            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                "user_id" => $receiverInfo->id,
                "notif_title" => $title,
                "notif_body" => $message,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $notif->save();

            $title = "Congratulations! ";
            $message = "Congratulations! You have received refund of " .
                    CURR .
                    " " .
                    $requestDetail->amount .
                    " from merchant";
            $device_type = $senderInfo->device_type;
            $device_token = $senderInfo->device_token;

            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                "user_id" => $senderInfo->id,
                "notif_title" => $title,
                "notif_body" => $message,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
            $notif->save();

            $statusArr = [
                "status" => "Success",
                "reason" => "The refund has been successfully sent!",
            ];

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid request id.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function sendRefund(Request $request) {
        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid Phone Number.",
            ];
            return response()->json($statusArr, 200);
        } elseif ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid User Id."];
            return response()->json($statusArr, 200);
        } elseif ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid QR code."];
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
                        "reason" => "You can not send refund for own account.",
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

                        $totalAmt = number_format($amount + $transFee, 2);

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
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                        ]);
                        $trans->save();
                        $TransId = $trans->id;

                        $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                        User::where("id", $request->user_id)->update([
                            "wallet_balance" => $sender_wallet_amount,
                        ]);

                        $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                        User::where("id", $recieverUser->id)->update([
                            "wallet_balance" => $reciever_wallet_amount,
                        ]);
                        $data["data"][
                                "wallet_amount"
                                ] = $this->numberFormatPrecision(
                                $sender_wallet_amount,
                                2,
                                "."
                        );
                        $data["data"]["trans_amount"] = $totalAmt;
                        $data["data"]["receiver_name"] = $recieverUser->name;
                        $data["data"]["receiver_phone"] = $recieverUser->phone;
                        $data["data"]["trans_id"] = $TransId;
                        $data["data"]["trans_date"] = date("d, M Y, h:i A");

                        $title = CURR . " " . $totalAmt . " debited from wallet.";
                        $message = CURR .
                                " " .
                                $totalAmt .
                                " debited from wallet for fund transfer to user " .
                                $recieverUser->name;
                        $device_type = $senderUser->device_type;
                        $device_token = $senderUser->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $notif = new Notification([
                            "user_id" => $senderUser->id,
                            "notif_title" => $title,
                            "notif_body" => $message,
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                        ]);
                        $notif->save();

                        $title = CURR .
                                " " .
                                $request->amount .
                                " credited to the wallet.";
                        $message = CURR .
                                " " .
                                $request->amount .
                                " credited to the wallet for fund transfer from user " .
                                $senderUser->name;
                        $device_type = $recieverUser->device_type;
                        $device_token = $recieverUser->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                            "reason" => "Sent Successfully",
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
                                "created_at" => date("Y-m-d H:i:s"),
                                "updated_at" => date("Y-m-d H:i:s"),
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
                            $data["data"][
                                    "wallet_amount"
                                    ] = $this->numberFormatPrecision(
                                    $sender_wallet_amount,
                                    2,
                                    "."
                            );
                            $data["data"]["trans_amount"] = $totalAmt;
                            $data["data"]["receiver_name"] = $recieverUser->name;
                            $data["data"]["receiver_phone"] = $recieverUser->phone;
                            $data["data"]["trans_id"] = $TransId;
                            $data["data"]["trans_date"] = date("d, M Y, h:i A");

                            $title = CURR .
                                    " " .
                                    $totalAmt .
                                    " debited from wallet.";
                            $message = CURR .
                                    " " .
                                    $totalAmt .
                                    " debited from wallet for fund transfer to user " .
                                    $recieverUser->name;
                            $device_type = $senderUser->device_type;
                            $device_token = $senderUser->device_token;

                            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                "user_id" => $senderUser->id,
                                "notif_title" => $title,
                                "notif_body" => $message,
                                "created_at" => date("Y-m-d H:i:s"),
                                "updated_at" => date("Y-m-d H:i:s"),
                            ]);
                            $notif->save();

                            $title = CURR .
                                    " " .
                                    $request->amount .
                                    " credited to the wallet.";
                            $message = CURR .
                                    " " .
                                    $request->amount .
                                    " credited to the wallet for fund transfer from user " .
                                    $senderUser->name;
                            $device_type = $recieverUser->device_type;
                            $device_token = $recieverUser->device_token;

                            //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                                "reason" => "Sent Successfully",
                            ];
                            $json = array_merge($statusArr, $data);
                            return response()->json($json, 200);
                        } else {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => "Insufficient Balance.",
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
                                    "created_at" => date("Y-m-d H:i:s"),
                                    "updated_at" => date("Y-m-d H:i:s"),
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
                                $data["data"][
                                        "wallet_amount"
                                        ] = $this->numberFormatPrecision(
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

                                $title = CURR .
                                        " " .
                                        $request->amount .
                                        " debited from wallet.";
                                $message = CURR .
                                        " " .
                                        $request->amount .
                                        " debited from wallet for refund transfer to user " .
                                        $recieverUser->name;
                                $device_type = $senderUser->device_type;
                                $device_token = $senderUser->device_token;

                                //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                                $notif = new Notification([
                                    "user_id" => $senderUser->id,
                                    "notif_title" => $title,
                                    "notif_body" => $message,
                                    "created_at" => date("Y-m-d H:i:s"),
                                    "updated_at" => date("Y-m-d H:i:s"),
                                ]);
                                $notif->save();

                                $title = CURR .
                                        " " .
                                        $totalAmt .
                                        " credited to the wallet.";
                                $message = CURR .
                                        " " .
                                        $totalAmt .
                                        " credited to the wallet for refund transfer from user " .
                                        $senderUser->name;
                                $device_type = $recieverUser->device_type;
                                $device_token = $recieverUser->device_token;

                                //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                                    "reason" => "Refund sent Successfully",
                                ];
                                $json = array_merge($statusArr, $data);
                                return response()->json($json, 200);
                            } else {
                                $statusArr = [
                                    "status" => "Failed",
                                    "reason" =>
                                    "Receiver not found or not verified.",
                                ];
                                return response()->json($statusArr, 200);
                            }
                        } else {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => "Insufficient Balance.",
                            ];
                            return response()->json($statusArr, 200);
                        }
                    }
                }
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Receiver not found.",
                ];
                return response()->json($statusArr, 200);
            }
            //            } catch (\Exception $ex) {
            //                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
            //                return response()->json($statusArr, 200);
            //            }
        }
    }

    public function checkTransactionFee(Request $request) {
        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid Phone Number.",
            ];
            return response()->json($statusArr, 200);
        } elseif ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid User Id."];
            return response()->json($statusArr, 200);
        } elseif ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid QR code."];
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
                    "reason" => "Receiver not found.",
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

    private function sendPushNotification(
            $title,
            $message,
            $device_type,
            $device_token
    ) {
        $push_notification_key = env("PUSH_NOTIFICATION_KEY");
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = [
            "authorization: key=" . $push_notification_key . "",
            "content-type: application/json",
        ];

        if (strtolower($device_type) == "android") {
            $msgArr = [
                "message" => $message,
                "title" => $title,
                "tickerText" => $title,
                "msg_data" => $message,
                "sound" => 1,
            ];

            $fields = ["to" => $device_token, "data" => $msgArr];
            $postdata = json_encode($fields);
            /* $postdata = '{
              "to" : "' . $device_token . '",
              "notification" : {
              "title":"' . $title . '",
              "text" : "' . $message . '"
              },
              "data" : {
              "title":"' . $title . '",
              "description" : "' . $message . '",
              "text" : "' . $message . '",
              "is_read": 0
              }
              }'; */
        } else {
            $postdata = [
                "to" => $device_token,
                "Content-available" => "1",
                "notification" => [
                    "title" => $title,
                    "body" => $message,
                    "sound" => "default",
                ],
                "data" => ["targetScreen" => "detail"],
                "priority" => 10,
            ];

            $postdata = json_encode($postdata);
        }
        //echo $postdata;
        $ch = curl_init();
        $timeout = 120;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Get URL content
        $result = curl_exec($ch);
        // close handle to release resources
        curl_close($ch);

        return $result;
    }

    public function getNotification(Request $request) {

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
            'page.required' => 'Page field can\'t be left blank',
            'limit.required' => 'Limit field can\'t be left blank',
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
                 

        $totalRecords= DB::table("notifications")
        ->where($matchThese)
        ->orderBy("id", "DESC")->count();  


        if ($totalRecords > 0) {
            $notifArr = [];
            $notifDataArr = [];
            foreach ($notifications as $key => $val) {
                $notifArr["id"] = $val->id;
                $notifArr["user_id"] = $val->user_id;
                $notifArr["title"] = $val->notif_title;
                $notifArr["body"] = $val->notif_body;
                $notifArr["is_seen"] = $val->is_seen;
                $notifArr["date"] = date(
                        "d M Y h:i A",
                        strtotime($val->created_at)
                );
                $notifDataArr[] = $notifArr;
            }
            //echo "Count: ".Count($notifications);
            $statusArr = [
                "status" => "Success",
                "reason" => "Notification List.",
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
                "reason" => "Sorry no notification found.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

    }

    public function seenNotification(Request $request) {
        $user_id = Auth::user()->id;

        if (
                $request->notification_id == "" or
                !is_numeric($request->notification_id)
        ) {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid Notification Id.",
            ];
            return response()->json($statusArr, 200);
        } else {
            Notification::where("id", $request->notification_id)->update([
                "is_seen" => 1,
            ]);

            $statusArr = [
                "status" => "Success",
                "reason" => "Notification Seen Status Updated",
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function nearByUser(Request $request) {
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

            $statusArr = array("status" => "Success", "reason" => "No record found!");
            $data['data'] = $records;
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Users not available."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function merchantList() {
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

            $statusArr = ["status" => "Success", "reason" => "Merchants List"];
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            return response()->json($json, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Merchants not available.",
            ];
            return response()->json($statusArr, 200);
        }
    }

    public function generateQR(Request $request) {
        $user_id = Auth::user()->id;
        $request = $this->decryptContent($request->req);

        if ($request->phone == "") {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Invalid Phone Number.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
//            return response()->json($statusArr, 200);
        } elseif ($request->amount == "" || $request->amount == 0) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid QR code."];
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
                    "reason" => "Invalid User.",
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
                    "reason" => "QR Code Detail.",
                ];

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }


    public function generateQRForAgent(Request $request) {    
        $user_id = Auth::user()->id;
        $request = $this->decryptContent($request->req);
        if ($request->amount == "" || $request->amount == 0) {
            $statusArr = ["status" => "Failed", "reason" => "Invalid Amount."];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);  
        } else {  

                $amount = $request->amount;

                $userInfo = User::where('id', $user_id)->first();

                if ($amount > $userInfo->wallet_balance) {  

                    $statusArr = [
                        "status" => "Failed",
                        "reason" => 'Insufficient Balance !',
                    ];
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                $uniqueKey = $this->generateUniqueKey(10, 'unique_key', GeneratedQrCode::class);  

                $qrString = $this->encryptContent($user_id ."##self_generated"."##" .$request->amount.'##'.$uniqueKey);  

                $qrCode = $this->generateQRCode($qrString, $user_id);

                $qrcode = "public/" . $qrCode;  

                $generatedQrCode = GeneratedQrCode::create([
                    'user_id'=>$user_id,
                    'amount'=>$amount,
                    'unique_key'=>$uniqueKey,
                    'qr_code'=>$qrcode
                ]);

                $statusArr = [
                    "status" => "Success",
                    "qr_code" => $qrcode,
                    "reason" => "QR Code Detail.",
                ];

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
        }
    }

    public function getUserByPhone(Request $request) {

        $request = $this->decryptContent($request->req);

        $input = [
            'phone' => $request->phone,
        ];

        $validate_data = [
            'phone' => 'required',
        ];

        $customMessages = [
            'usephoner_id.required' => 'User id field can\'t be left blank',
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
            return response()->json($json, 200);
        }

        $phone = $request->phone;

        $matchThese = ["users.phone" => $phone];

        $userInfo = DB::table("users")->where($matchThese)->first();

        if ($userInfo) {

            $user_role = Auth::user()->user_type;

            if (($user_role == "User" || $user_role == "Merchant") && $userInfo->user_type == "Agent") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "You cannot transfer the money to agent",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $user_role = Auth::user()->user_type;

            if (($user_role == "User" || $user_role == "Merchant") && $userInfo->user_type == "Agent") {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "You cannot transfer the money to agent",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $statusArr = [
                "status" => "Success",
                "reason" => "User detail.",
            ];
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
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = [
                "status" => "Failed",
                "reason" => "Please provide a valid phone number.",
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function getUserByQR(Request $request) {

        $request = $this->decryptContent($request->req);

        if ($request->qr_code == "") {
            $statusArr = ["status" => "Failed", "reason" => "Invalid QR Code."];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $qrcode = $this->decryptContentString($request->qr_code);
            $qrCodeArr = explode("##", $qrcode);
            // echo "<pre>";
            // print_r($qrCodeArr); die;
            $amount=0;
            $uniqueKey= '';
            if (array_key_exists('3', $qrCodeArr)) {
                $uniqueKey = trim($qrCodeArr[3]);
                if($uniqueKey!="")
                {
                    $trans_type = $request->trans_type;
                    $user_role = Auth::user()->user_type;
                    if($trans_type!='withdraw')
                    {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => 'You can use this QR code only for withdrawal.',
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }

                    if($user_role!="Agent")
                    {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => 'Only agent can scan this QR code.',
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                    
                    $is_qr_readed = GeneratedQrCode::where('unique_key',$uniqueKey)->first()->status;
                    if($is_qr_readed==1)
                    {
                        $statusArr = [
                            "status" => "Failed",
                            "reason" => 'Qr Code has been already used',
                        ];
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }
            }

            if (array_key_exists('2', $qrCodeArr)) {
              $amount = trim($qrCodeArr[2]);
            }
            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Invalid QR Code.",
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
                        "reason" => "You cannot transfer the money to agent",
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
                    "reason" => "User detail.",
                    "amount"=>$amount,
                    "uniqueKey"=>$uniqueKey
                ];
                $json = array_merge($statusArr, $data);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Invalid QR Code.",
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function transactionDetail(Request $request) {
        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $request = $this->decryptContent($request->req);

        $input = [
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'trans_type' => $request->trans_type,
        ];

        $validate_data = [
            'user_id' => 'required',
            'amount' => 'required',
            'trans_type' => 'required',
        ];

        $customMessages = [
            'user_id.required' => 'User id field can\'t be left blank',
            'amount.required' => 'Amount field can\'t be left blank',
            'trans_type.required' => 'Trans_type field can\'t be left blank',
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

        $trans_type_exist = Transactionfee::where('slug', $input['trans_type'])->first();
        if (!$trans_type_exist) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Please provide a valid transaction type',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $fees = 0;

        if ($user_role == "User") {
            $fees = $trans_type_exist->user_charge;
        } elseif ($user_role == "Agent") {
            $fees = $trans_type_exist->agent_charge;
        } elseif ($user_role == "Merchant") {
            $fees = $trans_type_exist->merchant_charge;
        }

        $total_fees = 0;

        $amount = $request->amount;

        if ($fees != 0) {
            $total_fees = number_format(($amount * $fees / 100), 2, '.', '');
        }

        $total_tax = "0";

        $total_amount = $amount - $total_fees;

        $statusArr = [
            "status" => "Success",
            "fees" => 'Swap fees '.$fees . '%',
            "amount" => $amount,
            "total_fees" => $total_fees,
            "total_tax" => $total_tax,
            "total_amount" => strval($total_amount)
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function depositByAgent(Request $request) {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $input = [
            'opponent_user_id' => $request->opponent_user_id,
            'amount' => $request->amount,
            'total_amount' => $request->total_amount,
            'trans_type' => $request->trans_type,
        ];

        $validate_data = [
            'opponent_user_id' => 'required',
            'amount' => 'required',
            'total_amount' => 'required',
            'trans_type' => 'required',
        ];

        $customMessages = [
            'opponent_user_id.required' => 'Opponent User id field can\'t be left blank',
            'amount.required' => 'Amount field can\'t be left blank',
            'total_amount.required' => 'Amount field can\'t be left blank',
            'trans_type.required' => 'Trans_type field can\'t be left blank',
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
            return response()->json($json, 200);
        }

        $senderUser = $userDetail = User::where('id', $user_id)->first();

        if($senderUser->kyc_status!="completed")
        { 
        
            if($senderUser->kyc_status=="pending")
            { 
            
            $statusArr = [
                "status" => "KYC Pending",
                "reason" => 'Your KYC is pending',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
    
            }

            else{

            $statusArr = [
                "status" => "Not Verified",
                "reason" => 'User KYC is not verified',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);

           }

        }

       

        $adminInfo =  Admin::where("id", 1)->first();

        if($adminInfo->amount_limit < $request->amount)
        {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot transfer more than the amount limit',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        if ($user_id == $input['opponent_user_id']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot send funds to yourself',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $trans_type_exist = Transactionfee::where('slug', $input['trans_type'])->first();
        if (!$trans_type_exist) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Please provide a valid transaction type',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $fees = 0;

        if ($user_role == "User") {
            $fees = $trans_type_exist->user_charge;
        } elseif ($user_role == "Agent") {
            $fees = $trans_type_exist->agent_charge;
        } elseif ($user_role == "Merchant") {
            $fees = $trans_type_exist->merchant_charge;
        }

        $total_fees = 0;

        $amount = $request->amount;

        if ($fees != 0) {
            $total_fees = number_format(($amount * $fees / 100), 2, '.', '');
        }

        $total_tax = "0";

        $total_amount = $amount - $total_fees;


        if ($total_amount != $input['total_amount']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Transaction failed because fees strucutre has been changed.Please try again.',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

       
        if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => 'Insufficient Balance !',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $trans_id = time();
        $refrence_id = time() . '-' . $input['opponent_user_id'];
        $trans = new Transaction([
            'user_id' => $user_id,
            'receiver_id' => $input['opponent_user_id'],
            'amount' => $amount,
            'amount_value' => $total_amount,
            'transaction_amount' => $total_fees,
            'total_amount' => $amount,
            'trans_type' => 1,
            'payment_mode' => 'Agent Deposit',
            'status' => 1,
            'refrence_id' => $trans_id,
            'billing_description' => 'Agent Deposit-' . $refrence_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $trans->save();

        $recieverUser = $userInfo = User::where('id', $input['opponent_user_id'])->first();

        $sender_wallet_amount = $senderUser->wallet_balance - $amount;
        User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

        $receiver_wallet_amount = $recieverUser->wallet_balance + $total_amount;
        User::where('id', $input['opponent_user_id'])->update(['wallet_balance' => $receiver_wallet_amount]);

        DB::table('admins')->where('id', 1)->increment('wallet_balance',$total_fees);

        $title = __("message.Deposit Request");
        $message = __("message.deposit_request", ['cost' => CURR . " " . $amount, 'username' => $userInfo->name]);
//            $message = __("message.Your deposit request for " . CURR . " " . $amount. " has been sent successfully to agent " . $userInfo->name);
        $device_type = $userDetail->device_type;
        $device_token = $userDetail->device_token;

        $this->sendPushNotification($title, $message, $device_type, $device_token);

        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $title = __("message.Deposit Request");
        $message = __("message.deposit_request_agent", ['cost' => CURR . " " . $amount, 'username' => $userDetail->name]);
//            $message = __("message.User " . $userDetail->name . " has requested to deposit amount " . CURR . " " . $amount . " for his account.");
        $device_type = $userInfo->device_type;
        $device_token = $userInfo->device_token;

        $this->sendPushNotification($title, $message, $device_type, $device_token);

        $notif = new Notification([
            'user_id' => $userInfo->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $statusArr = array("status" => "Success", "reason" => "Amount deposited successfully");
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function withdrawByAgent(Request $request) {

        $request = $this->decryptContent($request->req);
        
        $user_id = Auth::user()->id;
        
        $user_role = Auth::user()->user_type;
        
        $input = [
            'opponent_user_id' => $request->opponent_user_id,
            'amount' => $request->amount,
            'total_amount' => $request->total_amount,
            'trans_type' => $request->trans_type,
            'uniqueKey'=>$request->uniqueKey,
        ];
        
        $validate_data = [
            'opponent_user_id' => 'required',
            'amount' => 'required',
            'total_amount' => 'required',
            'trans_type' => 'required',
            'uniqueKey'=>'required'
        ];
        
        $customMessages = [
            'opponent_user_id.required' => 'Opponent User id field can\'t be left blank',
            'amount.required' => 'Amount field can\'t be left blank',
            'total_amount.required' => 'Amount field can\'t be left blank',
            'trans_type.required' => 'Trans_type field can\'t be left blank',
            'uniqueKey.required' => 'Unique Key field can\'t be left blank',
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
            return response()->json($json, 200);
        }
        
        $senderUser = $userDetail = User::where('id', $input['opponent_user_id'])->first();
        
        if($userDetail->kyc_status!="completed")
        { 
        
            if($userDetail->kyc_status=="pending")
            { 
            
            $statusArr = [
                "status" => "Failed",
                "reason" => 'User KYC is pending',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        
            }
            else{
        
            $statusArr = [
                "status" => "Failed",
                "reason" => 'User KYC is not verified',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        
           }
        
        }
        
        $adminInfo =  Admin::where("id", 1)->first();
        if($adminInfo->amount_limit < $request->amount)
        {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot transfer more than the amount limit',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        
        if ($user_id == $input['opponent_user_id']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot send funds to yourself',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        
        $trans_type_exist = Transactionfee::where('slug', $input['trans_type'])->first();
        if (!$trans_type_exist) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Please provide a valid transaction type',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $is_qr_readed = GeneratedQrCode::where('unique_key',$input['uniqueKey'])->first()->status;
        if($is_qr_readed==1)
        {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Qr Code has been already used',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        
        $fees = 0;
        
        if ($user_role == "User") {
            $fees = $trans_type_exist->user_charge;
        } elseif ($user_role == "Agent") {
            $fees = $trans_type_exist->agent_charge;
        } elseif ($user_role == "Merchant") {
            $fees = $trans_type_exist->merchant_charge;
        }
        
        $total_fees = 0;
        
        $amount = $request->amount;
        
        if ($fees != 0) {
            $total_fees = number_format(($amount * $fees / 100), 2, '.', '');
        }
        
        $total_tax = "0";
        
        $total_amount = $amount - $total_fees;
        
        
        if ($total_amount != $input['total_amount']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Transaction failed because fees strucutre has been changed.Please try again.',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        
        if ($total_amount > $senderUser->wallet_balance) {
        
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Insufficient Balance !',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        
        $trans_id = time();
        $refrence_id = time() . '-' . $input['opponent_user_id'];
        $trans = new Transaction([
            'user_id' => $input['opponent_user_id'],
            'receiver_id' => $user_id,
            'amount' => $amount,
            'amount_value' => $total_amount,
            'transaction_amount' => $total_fees,
            'total_amount' => $amount,
            'trans_type' => 2,
            'payment_mode' => 'Withdraw',
            'status' => 1,
            'refrence_id' => $trans_id,
            'billing_description' => 'Withdraw-' . $refrence_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $trans->save();
        
        $sender_wallet_amount = $senderUser->wallet_balance - $amount;
        User::where('id', $input['opponent_user_id'])->update(['wallet_balance' => $sender_wallet_amount]);
        
        $recieverUser = $userInfo = User::where('id', $user_id)->first();
        $receiver_wallet_amount = $recieverUser->wallet_balance + $total_amount;
        User::where('id', $user_id)->update(['wallet_balance' => $receiver_wallet_amount]);
        
        DB::table('admins')->where('id', 1)->increment('wallet_balance',$total_fees);
        
        GeneratedQrCode::where('unique_key',$input['uniqueKey'])->update(['status'=>1]);
        
        $title = __("message.Withdrawal Request");
        $message = __("message.withdraw_request", ['cost' => CURR . " " . $amount, 'username' => $userInfo->name]);
        //                    $message = __("message.Your withdrawal request for " . CURR . " " . $amount . " has been sent successfully to agent " . $userInfo->name);
        $device_type = $userDetail->device_type;
        $device_token = $userDetail->device_token;
        
        $this->sendPushNotification($title, $message, $device_type, $device_token);
        
        $notif = new Notification([
            'user_id' => $userDetail->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();
        
        
        $title = __("message.Withdrawal Request");
        $message = __("message.withdraw_request_agent", ['cost' => CURR . " " . $amount, 'username' => $userDetail->name]);
        //                    $message = __("message.User " . $userDetail->name . " has requested to withdraw amount " . CURR . " " . $amount . " for his account.");
        $device_type = $userInfo->device_type;
        $device_token = $userInfo->device_token;
        
        $this->sendPushNotification($title, $message, $device_type, $device_token);
        
        $notif = new Notification([
            'user_id' => $userInfo->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();
        
        $statusArr = array("status" => "Success", "reason" => "The withdrawal has been successfully completed!");
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function myTransactions(Request $request) {

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
            'page.required' => 'Page field can\'t be left blank',
            'limit.required' => 'Limit field can\'t be left blank',
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
                    ->where(function($query) use ($user_id) {
                        $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
                    })
                    ->where(function($query) use ($user_id, $search) {
                        $query->where('u1.name', 'LIKE', "%$search%")
                        ->orWhere('u2.name', 'LIKE', "%$search%")
                        ->orWhere('u1.phone', 'LIKE', "%$search%")
                        ->orWhere('u2.phone', 'LIKE', "%$search%")
                        ->orWhere('transactions.id','LIKE',"%$search%");
                    })
                    ->orderBy('transactions.created_at', 'DESC')
                    ->skip($start)
                    ->take($limit)
                    ->get();
       // }

        if ($trans->isEmpty()) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'No Record Found',
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
                    ->where(function($query) use ($user_id) {
                        $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);
                    })
                    ->where(function($query) use ($user_id, $search) {
                        $query->where('u1.name', 'LIKE', "%$search%")
                        ->orWhere('u2.name', 'LIKE', "%$search%")
                        ->orWhere('u1.phone', 'LIKE', "%$search%")
                        ->orWhere('u2.phone', 'LIKE', "%$search%")
                        ->orWhere('transactions.id','LIKE',"%$search%");
                    })
                    ->orderBy('transactions.created_at', 'DESC')
                    ->count();   
       // }

        $transDataArr = [];

        global $tranType;

        foreach ($trans as $key => $val) {

            $transArr['trans_id'] = $val->id;

            $transArr['payment_mode'] = strtolower($val->payment_mode);
            $transArr['payment_mode_name'] = ucwords(str_replace("_", " ", $val->payment_mode));

            if ($val->receiver_id == 0) {
                $transArr['trans_from'] = $val->payment_mode;
                $transArr['sender'] = $this->getUserNameById($val->user_id);
                $transArr['sender_id'] = $val->user_id;
                $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                $transArr['receiver'] = 'Admin';
                $transArr['receiver_id'] = $val->receiver_id;
                $transArr['receiver_phone'] = 0;
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
                        if($val->status==2)
                        {
                        $transArr['trans_type'] = 'Request Debit';
                        }
                        else{
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
                        if($val->status==2)
                        {
                        $transArr['trans_type'] = 'Request Credit';
                        }
                        else{
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
            
            if ($transArr['payment_mode'] == 'agent deposit'){
                $transArr['payment_mode'] = 'agent_deposit';
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id == $user_id){
                    $transArr['trans_type'] = $tranType[1];
                }else if ($user_role == 'Agent'){
                    $transArr['trans_type'] = $tranType[2];
                    
                    $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                    $transArr['receiver_id'] = $val->receiver_id;
                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['sender'] = $this->getUserNameById($val->user_id);
                    $transArr['sender_id'] = $val->user_id;
                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                }
            }
            
            if ($transArr['payment_mode'] == 'withdraw'){
                $senderUserType = $this->getUserTypeById($val->user_id);
                $receiverUserType = $this->getUserTypeById($val->receiver_id);
                if ($senderUserType == 'Agent' && $receiverUserType == 'Agent' && $val->receiver_id != $user_id){
                    $transArr['trans_type'] = $tranType[2];
                }else if ($user_role == 'Agent'){
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

        $statusArr = array("status" => "Success", "reason" => "Transaction List.", "total_records" => $totalRecords,
            "total_page" => ceil($totalRecords / $limit),);
        $data['data'] = $transDataArr;
        $json = array_merge($statusArr, $data);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function senderRequestList(Request $request) {

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
            'status.required' => 'Request type field can\'t be left blank',
            'page.required' => 'Page field can\'t be left blank',
            'limit.required' => 'Limit field can\'t be left blank',
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
            return response()->json($json, 200);
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
                ->where("receiver_id", $user_id)
                ->orderBy("id", "desc")
                ->skip($start)
                ->take($limit)
                ->get();

        $totalRecords = Transaction::where("payment_mode", 'send_money')
                ->whereIn("status", $status)
                ->where("receiver_id", $user_id)
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
                if ($request->User->profile_image != "" &&  $request->User->profile_image != "no_user.png") {
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
            $statusArr = array("status" => "Success", "reason" => 'Request List', "total_records" => $totalRecords,
                "total_page" => ceil($totalRecords / $limit));
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => 'Requests are not exist.');
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function receiverRequestList(Request $request) {

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
            'status.required' => 'Request type field can\'t be left blank',
            'page.required' => 'Page field can\'t be left blank',
            'limit.required' => 'Limit field can\'t be left blank',
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
            return response()->json($json, 200);
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
                ->orderBy("id", "desc")
                ->skip($start)
                ->take($limit)
                ->get();

        $totalRecords = Transaction::where("payment_mode", 'send_money')
                ->whereIn("status", $status)
                ->where("user_id", $user_id)
                ->orderBy("id", "desc")
                ->count();

        if ($requests->isEmpty()) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'No Record Found',
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
                $userData["amount"] = $this->numberFormatPrecision($request->amount, 2, ".");
                $trnsDt = date_create($request->created_at);
                $transDate = date_format($trnsDt, "d M Y, h:i A");
                $userData['date'] = $transDate;
                $userData['statusInDigit'] = $request->status;
                $userData['status'] = $this->getStatusText($request->status);
                $userRecordArr["name"] = $request->Receiver->name;
                $userRecordArr["user_id"] = $request->Receiver->id;
                $userRecordArr["user_type"] = $request->Receiver->user_type;
                if ($request->Receiver->profile_image != "" && $request->Receiver->profile_image != "no_user.png") {
                    $userRecordArr["profile_image"] = PROFILE_FULL_DISPLAY_PATH . $request->Receiver->profile_image;
                } else {
                    $userRecordArr["profile_image"] = "public/img/" . "no_user.png";
                }
                $userRecordArr["user_type"] = $request->Receiver->user_type;
                $userRecordArr["phone"] = $request->Receiver->phone;
                $userRecordArr["email"] = $request->Receiver->email;
                $userRecordArr["country"] = $request->Receiver->country;
                $userData["userData"] = $userRecordArr;

                $records[] = $userData;
            }

            $statusArr = array("status" => "Success", "reason" => 'Request List', "total_records" => $totalRecords,
                "total_page" => ceil($totalRecords / $limit),);
            $data["data"] = $records;
            $json = array_merge($statusArr, $data);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => 'Requests are not exist.');
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    private function getStatusText($status) {

        $statusArr = array('1' => 'Completed', '2' => 'Pending', '3' => 'Failed', '4' => 'Reject', '5' => 'Refund', '6' => 'Refund Completed');
        return $statusArr[$status];
    }

    private function getUserNameById($user_id) {
        $matchThese = ["users.id" => $user_id];
        $user = DB::table('users')->select('users.name')->where($matchThese)->first();
        return $user->name;
    }
    
    private function getUserTypeById($user_id) {
        $matchThese = ["users.id" => $user_id];
        $user = DB::table('users')->select('users.user_type')->where($matchThese)->first();
        return $user->user_type;
    }

    private function getPhoneById($user_id) {
        $matchThese = ["users.id" => $user_id];
        $user = DB::table('users')->select('users.phone')->where($matchThese)->first();
        return $user->phone;
    }

    public function scanMerchantQR(Request $request) {
        $user_id = Auth::user()->id;

        if ($request->qr_code == "") {
            $statusArr = ["status" => "Failed", "reason" => "Invalid QR Code."];
            return response()->json($statusArr, 200);
        } else {
            $qrCodeArr = explode("##", $request->qr_code);
            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Invalid QR Code.",
                ];
                return response()->json($statusArr, 200);
            }

            if (isset($qrCodeArr[1]) && !empty($qrCodeArr[1])) {
                $qrOrder = $qrCodeArr[1];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Invalid QR Code.",
                ];
                return response()->json($statusArr, 200);
            }

            if (isset($qrCodeArr[2]) && !empty($qrCodeArr[2])) {
                $qrAmt = $qrCodeArr[2];
            } else {
                $statusArr = [
                    "status" => "Failed",
                    "reason" => "Invalid QR Code.",
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
                    "reason" => "Merchant detail.",
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
                    "reason" => "Invalid QR Code.",
                ];
                return response()->json($statusArr, 200);
            }
        }
    }

    public function fundTransfer(Request $request) {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $input = [
            'opponent_user_id' => $request->opponent_user_id,
            'amount' => $request->amount,
            'total_amount' => $request->total_amount,
            'trans_type' => $request->trans_type
        ];

        $validate_data = [
            'opponent_user_id' => 'required',
            'amount' => 'required',
            'total_amount' => 'required',
            'trans_type' => 'required',
        ];

        $customMessages = [
            'opponent_user_id.required' => 'Opponent User id field can\'t be left blank',
            'amount.required' => 'Amount field can\'t be left blank',
            'total_amount.required' => 'Amount field can\'t be left blank',
            'trans_type.required' => 'Trans Type field can\'t be left blank',
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
            return response()->json($json, 200);
        }

        $senderUser = User::where('id', $user_id)->first();

        if($senderUser->kyc_status!="completed")
        { 
        
            if($senderUser->kyc_status=="pending")
            { 
            
            $statusArr = [
            "status" => "KYC Pending",
            "reason" => 'Your KYC is pending',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
    
            }
            else{

            $statusArr = [
                "status" => "Not Verified",
                "reason" => 'Please verify your KYC',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);

           }

        }

       

        $adminInfo =  Admin::where("id", 1)->first();

        if($adminInfo->amount_limit < $request->amount)
        {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot transfer more than the amount limit',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($user_id == $input['opponent_user_id']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot send funds to yourself',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $trans_type_exist = Transactionfee::where('slug', $input['trans_type'])->first();
        if (!$trans_type_exist) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Please provide a valid transaction type',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $fees = 0;

        if ($user_role == "User") {
            $fees = $trans_type_exist->user_charge;
        } elseif ($user_role == "Agent") {
            $fees = $trans_type_exist->agent_charge;
        } elseif ($user_role == "Merchant") {
            $fees = $trans_type_exist->merchant_charge;
        }

        $total_fees = 0;

        $amount = $request->amount;

        if ($fees != 0) {
            $total_fees = number_format(($amount * $fees / 100), 2, '.', '');
        }

        $total_tax = "0";

//        $total_amount = $amount+$total_fees;
        $total_amount = $amount - $total_fees;


        if ($total_amount != $input['total_amount']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Transaction failed because fees strucutre has been changed.Please try again.',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if ($total_amount > $senderUser->wallet_balance) {

            $statusArr = [
                "status" => "Failed",
                "reason" => 'Insufficient Balance !',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $trans_id = time();
        $refrence_id = time() . '-' . $input['opponent_user_id'];
        $trans = new Transaction([
            'user_id' => $user_id,
            'receiver_id' => $input['opponent_user_id'],
            'amount' => $amount,
            'amount_value' => $total_amount,
            'transaction_amount' => $total_fees,
            'total_amount' => $amount,
            'trans_type' => 1,
            'payment_mode' => 'wallet2wallet',
            'status' => 1,
            'refrence_id' => $trans_id,
            'billing_description' => 'Fund Transfer-' . $refrence_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $trans->save();

        $recieverUser = $userInfo = User::where('id', $input['opponent_user_id'])->first();

        $sender_wallet_amount = $senderUser->wallet_balance - $amount;
        User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

        $receiver_wallet_amount = $recieverUser->wallet_balance + $total_amount;
        User::where('id', $input['opponent_user_id'])->update(['wallet_balance' => $receiver_wallet_amount]);

        DB::table('admins')->where('id', 1)->increment('wallet_balance',$total_fees);

        $title = __("message.debit_title", ['cost' => CURR . " " . $request->amount]);
        $message = __("message.debit_message", ['cost' => CURR . " " . $request->amount, 'username' => $recieverUser->name]);

        $device_type = $senderUser->device_type;
        $device_token = $senderUser->device_token;

        $this->sendPushNotification($title, $message, $device_type, $device_token);

        $notif = new Notification([
            'user_id' => $senderUser->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();

        $title = __("message.credit_title", ['cost' => CURR . " " . $total_amount]);
        $message = __("message.credit_message", ['cost' => CURR . " " . $total_amount, 'username' => $senderUser->name]);
        $device_type = $recieverUser->device_type;
        $device_token = $recieverUser->device_token;

        $this->sendPushNotification($title, $message, $device_type, $device_token);

        $notif = new Notification([
            'user_id' => $recieverUser->id,
            'notif_title' => $title,
            'notif_body' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $notif->save();


        $statusArr = array("status" => "Success", "reason" => "Payment transfer completed successfully!");
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function sendMoneyRequest(Request $request) {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $input = [
            'phone' => $request->phone,
            'amount' => $request->amount,
        ];

        $validate_data = [
            'phone' => 'required',
            'amount' => 'required',
        ];

        $customMessages = [
            'phone.required' => 'Phone number field can\'t be left blank',
            'amount.required' => 'Amount field can\'t be left blank',
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
            return response()->json($json, 200);
        }

        $amount = $request->amount;
        $phone = $request->phone;

        $senderUser = User::where('id', $user_id)->first();
        $receiverUser = User::where('phone', $phone)->first();

        if ($user_id == $receiverUser['id']) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot send funds to yourself',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $adminInfo =  Admin::where("id", 1)->first();

        if($adminInfo->amount_limit < $request->amount)
        {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'You cannot transfer more than the amount limit',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        if (!empty($receiverUser)) {
            $trans_id = time();
            $refrence_id = time() . '-' . $receiverUser->id;
            $trans = new Transaction([
                'receiver_id' => $user_id,
                'user_id' => $receiverUser->id,
                'amount' => $amount,
                'amount_value' => $amount,
                'total_amount' => $amount,
                'trans_type' => 4,
                'payment_mode' => 'send_money',
                'status' => 2,
                'refrence_id' => $trans_id,
                'billing_description' => 'SendMoney-' . $refrence_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $trans->save();

            $title = 'Send Money Request';
            $message = "Your send money request for " . CURR . " " . $amount . " has been sent successfully to user " . $receiverUser->name;
            $device_type = $senderUser->device_type;
            $device_token = $senderUser->device_token;

            $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                'user_id' => $senderUser->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            $title = 'Send Money Request';
            $message = "User " . $senderUser->name . " has requested to send money amount " . CURR . " " . $amount . " for his account.";
            $device_type = $receiverUser->device_type;
            $device_token = $receiverUser->device_token;

            $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                'user_id' => $receiverUser->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            $statusArr = array("status" => "Success", "reason" => 'The money request has been successfully sent!');
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => 'User is not exist for entered phone number.');
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function cancelAcceptMoneyRequest(Request $request) {

        $request = $this->decryptContent($request->req);

        $user_id = Auth::user()->id;

        $user_role = Auth::user()->user_type;

        $input = [
            'request_id' => $request->request_id,
            'amount' => $request->amount,
            'request_type' => $request->request_type
        ];

        $validate_data = [
            'request_id' => 'required',
            'amount' => 'required',
            'request_type' => 'required',
        ];

        $customMessages = [
            'request_id.required' => 'Request id field can\'t be left blank',
            'amount.required' => 'Amount field can\'t be left blank',
            'request_type.required' => 'Request Type field can\'t be left blank',
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
            return response()->json($json, 200);
        }

        $request_id = $request->request_id;
        $request_type = $request->request_type;
        $amount = $request->amount;

        $senderInfo = User::where("id", $user_id)->first();

        if($senderInfo->kyc_status!="completed")
        { 
            if($senderInfo->kyc_status=="pending")
            { 
            
            $statusArr = [
            "status" => "KYC Pending",
            "reason" => 'Your KYC is pending',
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
    
            }
            else{

                $statusArr = [
                    "status" => "Not Verified",
                    "reason" => 'Please verify your KYC',
                ];
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }

       


//        echo '<pre>';print_r($senderInfo);exit;

        DB::enableQueryLog();
        $requestDetail = Transaction::where("id", $request_id)
                ->first();

        if (!empty($requestDetail)) {
            if ($requestDetail->payment_mode == "send_money") {
                $type = 1;
            } else {
                $type = 2;
            }

            if ($request_type == "Accept") {
                if ($type == 1) {
                    if ($senderInfo->wallet_balance >= $amount) {

                        $trans_type_exist = Transactionfee::where('slug', 'send_money')->first();
                        if (!$trans_type_exist) {
                            $statusArr = [
                                "status" => "Failed",
                                "reason" => 'Please provide a valid transaction type',
                            ];
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }

                        $fees = 0;

                        if ($user_role == "User") {
                            $fees = $trans_type_exist->user_charge;
                        } elseif ($user_role == "Agent") {
                            $fees = $trans_type_exist->agent_charge;
                        } elseif ($user_role == "Merchant") {
                            $fees = $trans_type_exist->merchant_charge;
                        }

                        $total_fees = 0;

                        if ($fees != 0) {
                            $total_fees = number_format(($amount * $fees / 100), 2, '.', '');
                        }

                        $total_tax = "0";

                        $total_amount = $amount - $total_fees;

                        Transaction::where("id", $request_id)->update([
                            "status" => 1,
                            "transaction_amount" => $total_fees,
                            "amount" => $amount,
                            "total_amount"=>$amount,
                            "amount_value" => $total_amount,
                            "updated_at" => date("Y-m-d H:i:s"),
//                            "trans_type" => $type,
                        ]);

                        $receiverInfo = User::where("id", $requestDetail->receiver_id)->first();
                        $wallet_balance = $receiverInfo->wallet_balance + $total_amount;
                        User::where("id", $receiverInfo->id)->update(["wallet_balance" => $wallet_balance,]);

                        $wallet_balance = $senderInfo->wallet_balance - $amount;
                        User::where("id", $senderInfo->id)->update(["wallet_balance" => $wallet_balance,]);

                        DB::table('admins')->where('id', 1)->increment('wallet_balance',$total_fees);

                        $title = "Congratulations! ";
                        $message = "Congratulations! Your request successfully accepted for send money " . $amount;
                        $device_type = $senderInfo->device_type;
                        $device_token = $senderInfo->device_token;

                        //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $notif = new Notification([
                            "user_id" => $senderInfo->id,
                            "notif_title" => $title,
                            "notif_body" => $message,
                            "created_at" => date("Y-m-d H:i:s"),
                            "updated_at" => date("Y-m-d H:i:s"),
                        ]);
                        $notif->save();
                    } else {
                        $statusArr = array("status" => "Failed", "reason" => 'You have insufficient balance to accept request.');
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

                    $title = "Congratulations! ";
                    $message = "Congratulations! Your request successfully accepted for withdraw of amount " .
                            $amount;
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                    "reason" => "Request Accepted Successfully",
                ];

                $statusArr = array("status" => "Success", "reason" => 'Payment transfer completed successfully!');
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                if ($type != 1) {
                    $receiverInfo = User::where(
                                    "id",
                                    $requestDetail->receiver_id
                            )->first();
                    $wallet_balance = $receiverInfo->wallet_balance +
                            $amount;
                    User::where("id", $receiverInfo->id)->update([
                        "wallet_balance" => $wallet_balance,
                    ]);

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
                        "refrence_id" => $refrence_id,
                        "created_at" => date("Y-m-d H:i:s"),
                        "updated_at" => date("Y-m-d H:i:s"),
                    ]);
                    $trans->save();

                    $title = "Congratulations! ";
                    $message = "Congratulations! Your request rejected for withdraw of amount " .
                            $amount;
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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

                    $title = "Congratulations! ";
                    $message = "Congratulations! Your request rejected for send money of amount " . $amount;
                    $device_type = $senderInfo->device_type;
                    $device_token = $senderInfo->device_token;

                    //                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        "user_id" => $senderInfo->id,
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
                    "reason" => "The money request has been successfully cancelled!",
                ];
            }

            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => 'Invalid Request.');
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function getDocumentTypes(Request $request) {
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
            'page.required' => 'Page field can\'t be left blank',
            'limit.required' => 'Limit field can\'t be left blank',
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
                              WHEN user_id = '.$user_id.' THEN receiver_id 
                              WHEN receiver_id = '.$user_id.' THEN user_id 
                              ELSE user_id 
                           END AS unique_user_id', [$user_id, $user_id]))
        ->where(function ($query) use ($user_id) {
            $query->where('user_id', $user_id)
                  ->orWhere('receiver_id', $user_id);
        })
        ->where(function($query) use ($user_id, $search) {
            $query->where('u1.name', 'LIKE', "%$search%")
            ->orWhere('u2.name', 'LIKE', "%$search%")
            ->orWhere('u1.phone', 'LIKE', "%$search%")
            ->orWhere('u2.phone', 'LIKE', "%$search%")
            ->orWhere('transactions.id','LIKE',"%$search%");
        })
        ->orderByDesc('transactions.updated_at')
        ->distinct()
        ->skip($start)
        ->take($limit)
        ->get();

        $totalRecords = DB::table('transactions')
        ->select(DB::raw('CASE 
                              WHEN user_id = '.$user_id.' THEN receiver_id 
                              WHEN receiver_id = '.$user_id.' THEN user_id 
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

       if($uniqueUserIds->isEmpty())
       {
        $statusArr = [
            "status" => "Failed",
            "reason" => 'No record found!',
        ];
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
       }

       $totalRecords = count($totalRecords);

       $userDatas = User::whereIn('id',$uniqueUserIdsArray)->get();
       $data = array();
       foreach($userDatas as $userInfo){
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

       $statusArr = array("status" => "Success","total_records" => $totalRecords,
       "total_page" => ceil($totalRecords / $limit));
       $json = array_merge($statusArr, $data);
       $json = json_encode($json);
       $responseData = $this->encryptContent($json);
       return response()->json($responseData, 200);
    }

    public function getSwapContactsList(Request $request)
    {
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
            'page.required' => 'Page field can\'t be left blank',
            'limit.required' => 'Limit field can\'t be left blank',
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
        $perPage = $input['limit'];
        $offset = ($page - 1) * $perPage;
        $limit = $perPage;
        $contactsList=$request->contactsList;
        $currentPageRecords = array_slice($contactsList, $offset, $limit);

        if(!isset($currentPageRecords))
        {
         $statusArr = [
             "status" => "Failed",
             "reason" => 'No record found!',
         ];
         $json = json_encode($statusArr);
         $responseData = $this->encryptContent($json);
         return response()->json($responseData, 200);
        }

        $response = [];
        $processedNumbers = [];
        foreach ($currentPageRecords as $contact) {
            if (isset($contact->phone) && is_array($contact->phone)) {
                foreach ($contact->phone as $phoneNumber) {
                    if (!in_array($phoneNumber, $processedNumbers)) {
                        $user = User::where('phone', $phoneNumber)->first();
                        if ($user) {
                            
                            $profile='';
                            if ($user->profile_image != "" && $user->profile_image != "no_user.png") {
                                $profile = PROFILE_FULL_DISPLAY_PATH . $user->profile_image;
                            } else {
                                $profile = "public/img/" . "no_user.png";
                            }

                            $response[] = [
                                'phone' => $phoneNumber,
                                'name' => $contact->name,
                                'user_id' => $user->id,
                                'user_type' => $user->user_type,
                                'profile_image' => $profile,
                            ];
                        }
                        else{
                            $response[] = [
                                'phone' => $phoneNumber,
                                'name' => $contact->name,
                                'user_id' => "0",
                                'user_type' => "",
                                'profile_image' =>"",
                            ];  
                        }
                        // Add the phone number to the processed numbers array
                        $processedNumbers[] = $phoneNumber;
                    }
                }
            }
        }

        $statusArr = array("status" => "Success", "reason" => "Fetched record successfully");
        $json = array_merge($statusArr, ['data' => $response]);
        $json = json_encode($json);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function convertEtoD(Request $request) {
        $request = $this->decryptContent($request->req);

        echo '<pre>';
        print_r($request);
        exit;
    }

}
