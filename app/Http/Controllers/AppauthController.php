<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\User;
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
use DB;
use Input;
use App;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class AppauthController extends Controller {

    private function encryptContent($content) {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $cipher = $encryption->encryptPlainTextWithRandomIV($content, $secretyKey);
        return $cipher;
    }

    private function decryptContent($content) {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $plainText = $encryption->decryptCipherTextWithRandomIV($content, $secretyKey);
        $plainText = $plainText . PHP_EOL;

        return json_decode($plainText);
    }

    private function generateNumericOTP($n) {
        $generator = "1357902468";
        $result = "";

        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, (rand() % (strlen($generator))), 1);
        }
        return $result;
    }

    private function generateQRCode($qrString, $user_id) {
        $output_file = 'uploads/qr-code/' . $user_id . '-qrcode-' . time() . '.png';
        $image = \QrCode::format('png')
                ->size(200)->errorCorrection('H')
                ->generate($qrString, base_path() . '/public/' . $output_file);
        return $output_file;
    }

    public function isEmailExist(Request $request) {

        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);

        $matchThese = ["users.email" => $request->email];
        $flag = DB::table('users')->where($matchThese)->first();

        if ($flag) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Email already exist"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Success", "reason" => 'Email not registered');
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    function correctImageOrientation($filename) {
        if (function_exists('exif_read_data')) {
            try {
                $exif = exif_read_data($filename, 0, true);
            } catch (Exception $exp) {
                $exif = false;
            }

//            $exif = exif_read_data($filename);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                if ($orientation != 1) {
                    $img = imagecreatefromjpeg($filename);
                    $deg = 0;
                    switch ($orientation) {
                        case 3:
                            $deg = 180;
                            break;
                        case 6:
                            $deg = 270;
                            break;
                        case 8:
                            $deg = 90;
                            break;
                    }
                    if ($deg) {
                        $img = imagerotate($img, $deg, 0);
                    }
                    // then rewrite the rotated image back to the disk as $filename 
                    imagejpeg($img, $filename, 95);
                } // if there is some rotation necessary
            } // if have the exif orientation info
        } // if function exists      
    }

    public function signup(Request $request) {

//        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->user_id == "") {
            if ($request->phone == "") {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Phone number"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $matchThese = ["users.phone" => $request->phone];
            $flag = DB::table('users')->where($matchThese)->first();

            $otp_number = $this->generateNumericOTP(6);
            //            $res = $this->sendSMS($otp_number,$request->phone);
            if ($flag) {
                if ($flag->name != '') {
                    $statusArr = array("status" => "Failed", "reason" => __("message.User already registered"));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {

                    $user_id = $flag->id;

                    $statusArr = array("status" => "Success", "reason" => __("message.OTP send, please enter OTP for verification."), "otp" => $otp_number, "user_id" => $user_id);
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                $slug = $this->createSlug(time(), 'users');
                $user = new User([
                    'user_type' => $request->user_type,
                    'phone' => $request->phone,
                    'verify_code' => $otp_number,
                    'slug' => $slug,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $user->save();
                $user_id = $user->id;

                $statusArr = array("status" => "Success", "reason" => __("message.OTP send, please enter OTP for verification."), "otp" => $otp_number, "user_id" => $user_id);
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $user_id = $request->user_id;
            $userInfo = User::where('id', $user_id)->first();
            if (!empty($userInfo)) {

                $isExists = 0;
                if ($request->device_type != 'Android' && $request->isExists == 1) {
                    $isExists = 1;
                }



                if ($userInfo->is_verify == 1 && $isExists == 0) {
                    if ($userInfo->phone == $request->phone) {
                        $statusArr = array("status" => "Failed", "reason" => __("message.User already registered with same phone number"));
//                        return response()->json($statusArr, 200);
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                    if ($userInfo->email == $request->email) {
                        $statusArr = array("status" => "Failed", "reason" => __("message.User already registered with same email address"));
//                        return response()->json($statusArr, 200);
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }

                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] != '0') {
                    $file = $_FILES['profile_image'];
                    $file = Input::file('profile_image');
                    $uploadedFileName = $this->uploadImage($file, PROFILE_FULL_UPLOAD_PATH);

                    $this->resizeImage($uploadedFileName, PROFILE_FULL_UPLOAD_PATH, PROFILE_SMALL_UPLOAD_PATH, PROFILE_MW, PROFILE_MH);
                    $profile_image = $uploadedFileName;

                    $this->correctImageOrientation(PROFILE_FULL_UPLOAD_PATH . '/' . $profile_image);
                    $this->correctImageOrientation(PROFILE_SMALL_UPLOAD_PATH . '/' . $profile_image);
                } else {
                    $profile_image = $userInfo->profile_image;
                }

                if (isset($_FILES['identity_image']) && $_FILES['identity_image']['size'] != '0') {
                    $file = $_FILES['identity_image'];
                    $file = Input::file('identity_image');
                    $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                    $identity_image = $uploadedFileName;

                    $this->correctImageOrientation(IDENTITY_FULL_UPLOAD_PATH . '/' . $identity_image);
                    $this->correctImageOrientation(IDENTITY_SMALL_UPLOAD_PATH . '/' . $identity_image);
                } else {
                    $identity_image = '';
                }
                $qrString = $user_id . "##" . $request->name;
                $qrCode = $this->generateQRCode($qrString, $user_id);

                if (!empty($request->national_identity_number) || !empty($identity_image)) {
                    $status = 4;
                } else {
                    $status = 3;
                }

                if ($userInfo->user_type == 'Individual' || $request->user_type == 'Individual') {
                    $isVerify = 1;
                } else {
                    $isVerify = 0;
                }


                $dob = date('Y-m-d', strtotime(str_replace('/', '-', $request->dob)));
                $user = array(
                    'user_type' => $request->user_type,
                    'name' => $request->name,
                    'email' => $request->email,
                    'city' => $request->city_id,
                    'area' => $request->area_id,
                    'qr_code' => $qrCode,
                    'business_name' => $request->business_name,
                    'registration_number' => Crypt::encryptString($request->registration_number),
                    'identity_image' => $identity_image,
                    'profile_image' => $profile_image,
                    'national_identity_number' => Crypt::encryptString($request->national_identity_number),
                    'dob' => $dob,
                    'password' => $this->encpassword($request->password),
                    'is_verify' => $isVerify,
                    'is_kyc_done' => $status,
                    'updated_at' => date('Y-m-d H:i:s'),
                );
//echo '<pre>';print_r($user);exit;
                User::where('id', $user_id)->update($user);

                $notiInfo = Notification::where('user_id', $user_id)->first();
                if (empty($notiInfo)) {
                    $title = __("message.Congratulations!");
                    $message = __("message.Congratulations! You successfully created your account. Welcome to SatPay.");
                    $device_type = $request->device_type;
                    $device_token = $request->device_token;

                    $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $user_id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                }

                $statusArr = array("status" => "Success", "reason" => __("message.Register Success."), "user_id" => $user_id);
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.User not found"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function login(Request $request) {

//        $value = '["device_type": "iPhone", "user_type": "Individual", "phone": "222222", "device_token": "", "device_id": "7261E46B-2F0B-420C-AE5A-8E92AB13BF1C", "password": "Hello@123", "method_name": "auth/login", "device_language": "en"]';
//        $request = $this->encryptContent($value);
//        echo $request;
        $requestData = $this->decryptContent($request->req);

        $lang = $requestData->device_language;
        App::setLocale($lang);

        $device_token = $requestData->device_token;
        $device_type = $requestData->device_type;
        $user_type = $requestData->user_type;
        $device_id = $requestData->device_id;

        $userInfo = User::where('phone', $requestData->phone)->where('user_type', '!=', '')->first();

        if (!empty($userInfo)) {
            if ($user_type == '') {
                $statusArr = array("status" => "Failed", "reason" => __("message.Your registration is not completed yet, please sign up your account again."));

                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if ($user_type == 'Individual') {
                if ($userInfo->user_type == 'Individual') {
                    if ($userInfo->is_verify == 0) {
                        $statusArr = array("status" => "Failed", "reason" => __("message.Your account might have been temporarily disabled. Please contact us for more details."));
//                        return response()->json($statusArr, 200);

                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $password = $requestData->password;
                        if (password_verify($password, $userInfo->password)) {

                            $user = array(
                                'device_token' => $device_token,
                                'device_type' => $device_type,
                                'device_id' => $device_id,
                                'api_token' => Str::random(80),
                                'login_status' => 1,
                                'login_time' => date('Y-m-d H:i:s')
                            );
                            User::where('phone', $requestData->phone)->update($user);

                            $userInfo = User::where('phone', $requestData->phone)->first();

                            $userData = array();
                            $userData['name'] = $userInfo->name;
                            $userData['user_id'] = $userInfo->id;
                            $userData['amount'] = $this->asDollars($userInfo->wallet_balance);
                            if ($userInfo->profile_image != '') {
                                $userData['profile_image'] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                            } else {
                                $userData['profile_image'] = HTTP_PATH . '/public/img/' . 'no_user.png';
                            }


                            $userData['user_type'] = $userInfo->user_type;
                            $userData['phone'] = $userInfo->phone;
                            $userData['email'] = $userInfo->email;
                            $userData['city_id'] = $userInfo->city;
                            $userData['area_id'] = $userInfo->area ? $userInfo->area : '';

                            $cityNames = User::getCityName($userInfo->city);

                            $userData['city_en'] = $cityNames->name_en;
                            $userData['city_ar'] = $cityNames->name_ar;
                            $userData['area_en'] = '';
                            $userData['area_ar'] = '';
                            if ($userInfo->area) {
                                $area = User::getAreaName($userInfo->area);
                                $userData['area_en'] = $area->name;
                                $userData['area_ar'] = $area->name;
                            }


                            $userData['dob'] = date('d/m/Y', strtotime($userInfo->dob));
                            $userData['national_identity_number'] = $userInfo->national_identity_number ? Crypt::decryptString($userInfo->national_identity_number) : '';
                            $userData['business_name'] = $userInfo->business_name ? $userInfo->business_name : '';
                            $userData['registration_number'] = $userInfo->registration_number ? Crypt::decryptString($userInfo->registration_number) : '';

//                        $userData['qrcode'] = HTTP_PATH . "/public/" . $userInfo->qr_code;
//                            $credentials = request(['phone', 'password']);
                            $credentials['phone'] = $requestData->phone;
                            $credentials['password'] = $requestData->password;
//                            print_r($credentials);exit;
                            if (!Auth::attempt($credentials)) {
                                return response()->json(['message' => __('message.Unauthorized')], 401);
                            }
                            $user = $request->user();

//                            $token = $request->token();
//                            echo '<pre>';print_r($token);exit;
//        $token->revoke();
//                            DB::table('oauth_access_tokens')->where('user_id', $userInfo->id)->delete();

                            $tokenStr = $userInfo->id . " " . $userInfo->name . " " . time();
                            $tokenResult = $user->createToken($tokenStr);
//                            $tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;
                            $token->save();

                            if ($userInfo->is_kyc_done == 4) {
                                $userInfo->is_kyc_done = 0;
                            }

                            $statusArr = array("status" => "Success", 'is_kyc_done' => $userInfo->is_kyc_done, "access_token" => $tokenResult->accessToken, "token_type" => "Bearer", "reason" => "Login Successfully.");
                            $data['data'] = $userData;

                            $json = array_merge($statusArr, $data);

//                            return response()->json($json, 200);

                            $json = json_encode($json);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $statusArr = array("status" => "Failed", "reason" => __("message.You have entered wrong mobile number or password."));
//                            return response()->json($statusArr, 200);

                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    }
                } else {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Number already registered as " . $userInfo->user_type));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if ($userInfo->user_type == 'Agent' || $userInfo->user_type == 'Merchant') {
                    if ($userInfo->is_verify == 0) {
                        $statusArr = array("status" => "Failed", "reason" => __("message.Your account might have been temporarily disabled. Please contact us for more details."));
//                        return response()->json($statusArr, 200);
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $password = $requestData->password;
                        if (password_verify($password, $userInfo->password)) {

                            $user = array(
                                'device_token' => $device_token,
                                'device_type' => $device_type,
                                'device_id' => $device_id,
                                'login_status' => 1,
                                'login_time' => date('Y-m-d H:i:s')
                            );
                            User::where('phone', $requestData->phone)->update($user);

                            $userInfo = User::where('phone', $requestData->phone)->first();

                            $userData = array();
                            $userData['name'] = $userInfo->name;
                            $userData['user_id'] = $userInfo->id;
                            $userData['amount'] = $this->asDollars($userInfo->wallet_balance);
                            if ($userInfo->profile_image != '') {
                                $userData['profile_image'] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                            } else {
                                $userData['profile_image'] = HTTP_PATH . '/public/img/' . 'no_user.png';
                            }


                            $userData['user_type'] = $userInfo->user_type;
                            $userData['phone'] = $userInfo->phone;
                            $userData['email'] = $userInfo->email;
                            $userData['city_id'] = $userInfo->city;
                            $userData['area_id'] = $userInfo->area ? $userInfo->area : '';

                            $cityNames = User::getCityName($userInfo->city);

                            $userData['city_en'] = $cityNames->name_en;
                            $userData['city_ar'] = $cityNames->name_ar;
                            $userData['area_en'] = '';
                            $userData['area_ar'] = '';
                            if ($userInfo->area) {
                                $area = User::getAreaName($userInfo->area);
                                $userData['area_en'] = $area->name;
                                $userData['area_ar'] = $area->name;
                            }

                            $userData['dob'] = date('d/m/Y', strtotime($userInfo->dob));
                            $userData['national_identity_number'] = $userInfo->national_identity_number ? Crypt::decryptString($userInfo->national_identity_number) : '';
                            $userData['business_name'] = $userInfo->business_name ? $userInfo->business_name : '';
                            $userData['registration_number'] = $userInfo->registration_number ? Crypt::decryptString($userInfo->registration_number) : '';

                            $userData['qrcode'] = HTTP_PATH . "/public/" . $userInfo->qr_code;

                            $credentials['phone'] = $requestData->phone;
                            $credentials['password'] = $requestData->password;
//                            $credentials = request(['phone', 'password']);
                            if (!Auth::attempt($credentials)) {
                                return response()->json(['message' => __("message.Unauthorized")], 401);
                            }
                            $user = $request->user();

//                            $token = $request->token();
//                            echo '<pre>';print_r($token);exit;
//        $token->revoke();
//                            DB::table('oauth_access_tokens')->where('user_id', $userInfo->id)->delete();

                            $tokenStr = $userInfo->id . " " . $userInfo->name . " " . time();
                            $tokenResult = $user->createToken($tokenStr);
                            //$tokenResult = $user->createToken('Personal Access Token');
                            $token = $tokenResult->token;
                            $token->save();

                            if ($userInfo->user_type == 'Merchant') {
                                $is_merchant = 1;
                                $userData['trans_pay_by'] = $userInfo->trans_pay_by;
                            } else {
                                $is_merchant = 0;
                                $userData['trans_pay_by'] = '';
                            }

                            if ($userInfo->is_kyc_done == 4) {
                                $userInfo->is_kyc_done = 0;
                            }

                            $statusArr = array("status" => "Success", 'is_merchant' => $is_merchant, 'is_kyc_done' => $userInfo->is_kyc_done, "access_token" => $tokenResult->accessToken, "token_type" => "Bearer", "reason" => __("message.Login Successfully."));
                            $data['data'] = $userData;

                            $json = array_merge($statusArr, $data);

//                            return response()->json($json, 200);
                            $json = json_encode($json);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $statusArr = array("status" => "Failed", "reason" => __("message.You have entered wrong mobile number or password."));
//                            return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    }
                } else {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Number already registered as " . $userInfo->user_type));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.You have entered wrong mobile number or password."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function forgotPassword(Request $request) {

        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        $app_type = $request->app_type;
        App::setLocale($lang);

        if ($app_type == 'User') {
            $userInfo = User::where('phone', $request->phone)->where('user_type', 'Individual')->first();
        } else {
            $userInfo = User::where('phone', $request->phone)->where('user_type', '!=', 'Individual')->first();
        }


        if (!empty($userInfo)) {
            $user_id = $userInfo->id;
            $otp_number = $this->generateNumericOTP(6);
            User::where('id', $userInfo->id)->update(array('forget_password_status' => 1));

            $statusArr = array("status" => "Success", "reason" => __("message.OTP send, please enter OTP for verification."), "otp" => $otp_number, "user_id" => $user_id);
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.You entered wrong phone number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function resetPassword(Request $request) {

        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);
        $userInfo = User::where('phone', $request->phone)->first();

        if (!empty($userInfo)) {
            $user_id = $userInfo->id;
            $password = $this->encpassword($request->password);
            User::where('phone', $userInfo->phone)->update(array('password' => $password));

            $statusArr = array("status" => "Success", "reason" => __("message.Password updated successfully."), "user_id" => $user_id);
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.You entered wrong phone number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function changePassword(Request $request) {

        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);

        $userInfo = User::where('id', $request->user_id)->first();
        $old_password = $request->old_password;
        $new_password = $request->new_password;
        $is_valid = $request->is_valid;
        if (!empty($userInfo)) {
            if ($is_valid == 1) {
                if (!password_verify($old_password, $userInfo->password)) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Current password is not correct."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else if ($old_password == $new_password) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.You can not change new password same as current password."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $user_id = $userInfo->id;
                    $password = $this->encpassword($request->new_password);
                    User::where('id', $userInfo->id)->update(array('password' => $password));

                    $statusArr = array("status" => "Success", "reason" => __("message.Password updated successfully."), "user_id" => $user_id);
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                if (!password_verify($old_password, $userInfo->password)) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Current password is not correct."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else if ($old_password == $new_password) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.You can not change new password same as current password."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $otp_number = $this->generateNumericOTP(6);
                    $user_id = $userInfo->id;

                    $statusArr = array("status" => "Success", "reason" => __("message.OTP send, please enter OTP for verification."), "otp" => $otp_number, "user_id" => $user_id);
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.You entered wrong phone number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function verifyOTP(Request $request) {

        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);
        if ($request->user_id == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid user id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        if ($request->otp_code == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid OTP code"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $matchThese = ["users.id" => $request->user_id];
        $flag = DB::table('users')->select('users.*')->where($matchThese)->first();
        //echo '<pre>';print_r($flag->verify_code);exit;
        if (empty($flag)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.User not exists!"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } elseif ($flag->verify_code != $request->otp_code) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid OTP code"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $user_id = $request->user_id;
            User::where('id', $user_id)->update([
                'otp_verify' => 1
            ]);
            $statusArr = array("status" => "Success", "reason" => __("message.OTP verification completed, please complete registration process."), "user_id" => $user_id);
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function sendSMS($otp_number, $mobile) {
        try {
            $otp_code = $otp_number;
            $toNumber = '+964' . $mobile;

            $message = __("message.send_otp", ["OTP" => $otp_code]);
            $account_sid = Account_SID;
            $auth_token = Auth_Token;
            $id = "$account_sid";
            $token = "$auth_token";
            global $sms_from;
            $url = "https://api.twilio.com/2010-04-01/Accounts/" . $account_sid . "/Messages.json";
            $data = array(
                'From' => $sms_from,
                'To' => $toNumber,
                'Body' => $message,
            );
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
            if (isset($data['status'])) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            $statusArr = array("status" => "Failed", "reason" => __("message.You entered wrong phone number."));
//            return response()->json($statusArr, 501);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 501);
        }
    }

    public function resendOTP(Request $request) {
        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);
//        $request->validate(['phone' => 'required']);
        $otp_number = $this->generateNumericOTP(6);
//        $this->sendSMS($otp_number, $request->phone);
        $statusArr = array("status" => "Success", "reason" => __("message.OTP sent successfully."), "otp" => $otp_number);
//        return response()->json($statusArr, 200);
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function updateProfile(Request $request) {
        ini_set("precision", 14);
        ini_set("serialize_precision", -1);

//        $requestUser = $request->user();
//
//        if ($requestUser->id != $request->user_id) {
//            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
//            $json = json_encode($statusArr);
//            $responseData = $this->encryptContent($json);
//            return response()->json($responseData, 200);
//        }
//        $request = $this->decryptContent($request->req);
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            try {
                $userInfo = User::where('id', $request->user_id)->first();

                if ($userInfo->email != $request->email) {

                    $userExist = User::where('email', $request->email)->first();
                    if (!empty($userExist)) {
                        $statusArr = array("status" => "Failed", "reason" => __("message.User already registered with same email address"));
//                        return response()->json($statusArr, 200);
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                }

                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] != '0') {
                    $file = $_FILES['profile_image'];
                    $file = Input::file('profile_image');
                    $uploadedFileName = $this->uploadImage($file, PROFILE_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, PROFILE_FULL_UPLOAD_PATH, PROFILE_SMALL_UPLOAD_PATH, PROFILE_MW, PROFILE_MH);
                    $data['profile_image'] = $uploadedFileName;
                    @unlink(PROFILE_FULL_UPLOAD_PATH . $userInfo->profile_image);

                    $this->correctImageOrientation(PROFILE_FULL_UPLOAD_PATH . '/' . $uploadedFileName);
                    $this->correctImageOrientation(PROFILE_SMALL_UPLOAD_PATH . '/' . $uploadedFileName);
                }

                if (isset($_FILES['identity_image']) && $_FILES['identity_image']['size'] != '0') {
                    $file = $_FILES['identity_image'];
                    $file = Input::file('identity_image');
                    $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                    $data['identity_image'] = $uploadedFileName;
                    @unlink(IDENTITY_FULL_UPLOAD_PATH . $userInfo->identity_image);

                    $this->correctImageOrientation(IDENTITY_FULL_UPLOAD_PATH . '/' . $uploadedFileName);
                    $this->correctImageOrientation(IDENTITY_SMALL_UPLOAD_PATH . '/' . $uploadedFileName);
                }


                $data['name'] = $request->name;
                $data['email'] = $request->email;
//                $data['city'] = $request->city_id;
//                $data['area'] = $request->area_id;
                $data['business_name'] = $request->business_name;
//                $data['registration_number'] = $request->registration_number;

                $data['dob'] = date('Y-m-d', strtotime(str_replace('/', '-', $request->dob)));

                $serialisedData = $this->serialiseFormData($data, 1); //send 1 for edit
                User::where('id', $request->user_id)->update($serialisedData);

                $statusArr = array("status" => "Success", "reason" => __("message.User profile updated successfully."));
                $userInfo = User::where('id', $request->user_id)->first();

                $data = array();
                $userData = array();
                $userData['name'] = $userInfo->name;
                $userData['user_id'] = $userInfo->id;
                $userData['amount'] = $this->numberFormatPrecision($userInfo->wallet_balance, 2, '.');
                if ($userInfo->profile_image != '') {
                    $userData['profile_image'] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                } else {
                    $userData['profile_image'] = HTTP_PATH . '/public/img/' . 'no_user.png';
                }

                $userData['qrcode'] = HTTP_PATH . "/public/" . $userInfo->qr_code;

                $userData['device_language'] = $lang;
                $userData['user_type'] = $userInfo->user_type;
                $userData['phone'] = $userInfo->phone;
                $userData['email'] = $userInfo->email;
                $userData['city_id'] = $userInfo->city;
                $userData['area_id'] = $userInfo->area ? $userInfo->area : '';
                $cityNames = User::getCityName($userInfo->city);

                $userData['city_en'] = $cityNames->name_en;
                $userData['city_ar'] = $cityNames->name_ar;
                $userData['area_en'] = '';
                $userData['area_ar'] = '';
                if ($userInfo->area) {
                    $area = User::getAreaName($userInfo->area);
                    $userData['area_en'] = $area->name;
                    $userData['area_ar'] = $area->name;
                }
                $userData['dob'] = date('d/m/Y', strtotime($userInfo->dob));
                $userData['national_identity_number'] = $userInfo->national_identity_number ? Crypt::decryptString($userInfo->national_identity_number) : '';
                $userData['business_name'] = $userInfo->business_name ? $userInfo->business_name : '';
                $userData['registration_number'] = $userInfo->registration_number ? Crypt::decryptString($userInfo->registration_number) : '';

                $data['data'] = $userData;
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } catch (\Exception $ex) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Unknown Exception"));
//                return response()->json($statusArr, 501);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 501);
            }
        }
    }

    public function logout(Request $request) {
        $requestUser = $request->user();
        $requestData = $this->decryptContent($request->req);
        $user_id = $requestData->user_id;
//        $device_token = $request->device_token;
//        $device_type = $request->device_type;

        $lang = $requestData->device_language;
        App::setLocale($lang);

        User::where('id', $user_id)->update(array('device_type' => '', 'device_token' => '', 'device_id' => '', 'login_status' => 0));

//        $token = $request->token();
//        $token->revoke();

        $statusArr = array("status" => "Success", "reason" => __("message.Logout Successfully."));
//        return response()->json($statusArr, 200);
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    function asDollars($value) {
//        if ($value < 0)
//            return "-" . asDollars(-$value);
        return number_format($value, 2);
    }

    public function user_home(Request $request) {
//        Configure::write('debug', 2);
//        echo '<pre>';print_r($request->user_id);
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $userInfo = User::where('id', $request->user_id)->first();
        $lat = $request->lat;
        $lng = $request->lng;

        $lang = $request->device_language;
        $device_id = $request->device_id;
        App::setLocale($lang);

        if (!empty($userInfo)) {

            $user = $requestUser;

            if ($userInfo->is_verify != 1) {
                $statusArr = array("status" => "Logout", "reason" => __("message.Your account might have been temporarily disabled. Please contact us for more details."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if ($userInfo->device_id != $device_id) {
                $statusArr = array("status" => "Logout", "reason" => __("message.You are logged out. Logged in new device."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

//            if ($lat != '' || $lng != '') {
//                User::where('id', $userInfo->id)->update(array('lat' => $lat, 'lng' => $lng));
//            }


            $userData = array();
            $amount = $userInfo->wallet_balance;

            $bannerArr = array();
            if ($userInfo->user_type == 'Agent') {
                $banners = Banner::where('status', 1)->where('user_type', '=', 'Agent')->get();
            } elseif ($userInfo->user_type == 'Merchant') {
                $banners = Banner::where('status', 1)->where('user_type', '=', 'Merchant')->get();
            } else {
                $banners = Banner::where('status', 1)->where('user_type', '=', 'Individual')->get();
            }

            if (!empty($banners)) {
                foreach ($banners as $banner) {
//                    $bannerA['banner_name'] = $banner->banner_name;
                    $bannerA['banner_image'] = BANNER_FULL_DISPLAY_PATH . $banner->banner_image;
                    $bannerA['category'] = $banner->category;
                    $bannerArr[] = $bannerA;
                }
            }
            $data['data'] = $bannerArr;

            $featureArr = array();
            $globelfeatures = Userfeature::where('user_id', $userInfo->id)->orderBy('name', 'ASC')->get();
//            echo '<pre>';print_r($globelfeatures);
            if (!$globelfeatures->isEmpty()) {
                foreach ($globelfeatures as $feature) {
                    if ($feature->status == 0) {
                        $featureArr[] = $feature->name;
                    } else {
                        $features = Feature::where('status', 0)->orderBy('name', 'ASC')->pluck('name', 'name')->all();
                        if (!empty($features)) {
                            foreach ($features as $feature) {
                                $featureArr[] = $feature;
                            }
                        }
                    }
                }
            } else {
                $features = Feature::where('status', 0)->orderBy('name', 'ASC')->pluck('name', 'name')->all();
                if (!empty($features)) {
                    foreach ($features as $feature) {
                        $featureArr[] = $feature;
                    }
                }
            }

            $data['disable_features'] = $featureArr;

            if ($userInfo->is_kyc_done == 4) {
                $userInfo->is_kyc_done = 0;
            }

//            setlocale(LC_MONETARY, 'en_US');
//            echo $this->asDollars($amount);
            if ($userInfo->profile_image != '') {
                $profile_image = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
            } else {
                $profile_image = HTTP_PATH . '/public/img/' . 'no_user.png';
            }

//            $token = '';
//            $oauthRecord = DB::table('oauth_access_tokens')->where('user_id', $userInfo->id)->first();
//            if ($oauthRecord->expires_at < now()) {
//
//                DB::table('oauth_access_tokens')->where('user_id', $userInfo->id)->delete();
//
//                $tokenStr = $userInfo->id . " " . $userInfo->name . " " . time();
//                $tokenResult = $user->createToken($tokenStr);
//                $token = $tokenResult->token;
//            }

            $national_identity_number = $userInfo->national_identity_number ? Crypt::decryptString($userInfo->national_identity_number) : '';
            $registration_number = $userInfo->registration_number ? Crypt::decryptString($userInfo->registration_number) : '';

            $statusArr = array("status" => "Success", 'national_identity_number' => $national_identity_number, 'business_name' => $userInfo->business_name, 'registration_number' => $registration_number, 'email' => $userInfo->email, 'profile_image' => $profile_image, 'trans_pay_by' => $userInfo->trans_pay_by, 'is_kyc_done' => $userInfo->is_kyc_done, 'amount' => $this->asDollars($amount), "reason" => __("message.Home Details"));
//            $data['data'] = $userData;

            $json = array_merge($statusArr, $data);

//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Logout", "reason" => __("message.User logout"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function updateLatLng(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $userInfo = User::where('id', $request->user_id)->first();
        $lat = $request->lat;
        $lng = $request->lng;

        $lang = $request->device_language;
        App::setLocale($lang);
        if (!empty($userInfo)) {

            if ($lat != '' || $lng != '') {
                User::where('id', $userInfo->id)->update(array('lat' => $lat, 'lng' => $lng));
            }

            $tokenUp = '';
            $oauthRecord = DB::table('oauth_access_tokens')->where('user_id', $userInfo->id)->first();
            if ($oauthRecord->expires_at < now()) {

                $user = $requestUser;

                DB::table('oauth_access_tokens')->where('user_id', $userInfo->id)->delete();

                $tokenStr = $userInfo->id . " " . $userInfo->name . " " . time();
                $tokenResult = $user->createToken($tokenStr);
                $token = $tokenResult->token;
                $token->save();

                $tokenUp = $tokenResult->accessToken;
            }

            $statusArr = array("status" => "Success", "reason" => __("message.Home Details"), 'access_token' => $tokenUp);
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function depositByCard(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $user_id = $request->user_id;
        $cardNumber = $request->card_number;

        $lang = $request->device_language;
        App::setLocale($lang);

//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where('id', $user_id)->first();
//        if (empty($userInfo)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }

        $cardInfo = Scratchcard::where('card_number', $cardNumber)->where('status', 1)->first();
        $trans_id = time();
        if (!empty($cardInfo)) {
            if ($cardInfo->used_status == 0) {
                if ($cardInfo->expiry_date >= date('Y-m-d')) {
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => $user_id,
                        'amount' => $cardInfo->real_value,
                        'amount_value' => $cardInfo->real_value,
                        'transaction_amount' => 0,
                        'total_amount' => $cardInfo->real_value,
                        'real_value' => $cardInfo->real_value,
                        'trans_type' => 3,
                        'payment_mode' => 'Cash card',
                        'status' => 1,
                        'refrence_id' => $trans_id,
                        'billing_description' => $cardNumber,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();

                    $wallet_balance = $userInfo->wallet_balance + $cardInfo->real_value;
                    User::where('id', $user_id)->update(array('wallet_balance' => $wallet_balance));

                    Scratchcard::where('card_number', $cardNumber)->update(array('used_status' => 1, 'used_by_name' => $userInfo->name, 'used_by_id' => $user_id));

                    $title = __("message.Congratulations!");
                    $message = __("message.deposit_by_card", ["cost" => CURR . " " . $cardInfo->real_value]);
//                    $message = __("message.Congratulations! You have successfully deposited amount " . CURR . " " . $cardInfo->real_value . " in your account using cash card.");
                    $device_type = $userInfo->device_type;
                    $device_token = $userInfo->device_token;

                    $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $user_id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                    $statusArr = array("status" => "Success", "reason" => __("message.Deposit Completed Successfully."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Card already expired."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Card not a valid card or already used."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.You entered wrong card number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function kycUpdate(Request $request) {
        $requestUser = $request->user();
//        $request = $this->decryptContent($request->req);
//        if ($requestUser->id != $request->user_id) {
//            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
//            $json = json_encode($statusArr);
//            $responseData = $this->encryptContent($json);
//            return response()->json($responseData, 200);
//        }
        ini_set("precision", 14);
        ini_set("serialize_precision", -1);

        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            try {
                $userInfo = User::where('id', $request->user_id)->first();
                $user_id = $request->user_id;

                if (isset($_FILES['identity_image']) && $_FILES['identity_image']['size'] != '0') {
                    $file = $_FILES['identity_image'];
                    $file = Input::file('identity_image');
                    $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                    $data['identity_image'] = $uploadedFileName;
                    @unlink(IDENTITY_FULL_UPLOAD_PATH . $userInfo->identity_image);
                }

                $data['registration_number'] = Crypt::encryptString($request->registration_number);
                $data['national_identity_number'] = Crypt::encryptString($request->national_identity_number);
                $data['is_kyc_done'] = 4;
//                $data['is_verify'] = 0;

                $serialisedData = $this->serialiseFormData($data, 1); //send 1 for edit
                User::where('id', $request->user_id)->update($serialisedData);

                $title = __("message.Congratulations!");
                $message = __("message.Congratulations! Your KYC details submitted to admin successfully.");
                $device_type = $userInfo->device_type;
                $device_token = $userInfo->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $user_id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                $statusArr = array("status" => "Success", 'is_kyc_done' => 0, "reason" => __("message.KYC details updated successfully."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } catch (\Exception $ex) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Unknown Exception"));
//                return response()->json($statusArr, 501);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 501);
            }
        }
    }

    public function myTransactions(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->page == "" or!is_numeric($request->page)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Page."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            global $tranType;
//            try {
            $userInfo = User::where('id', $request->user_id)->first();
            if (!empty($userInfo)) {

                if ($userInfo->user_type == 'Agent') {
                    $trans = DB::table('transactions')->select('transactions.*')->where("payment_mode", '!=', 'Refund')->where("user_id", $request->user_id)->orwhere("receiver_id", "=", $request->user_id)->orderBy("id", "DESC")->paginate(50);
                } else {
                    $trans = DB::table('transactions')->select('transactions.*')->where("user_id", $request->user_id)->orwhere("receiver_id", "=", $request->user_id)->orderBy("id", "DESC")->paginate(50);
                }
//                $trans = Transaction::where("user_id", $request->user_id)->orwhere("receiver_id", "=", $request->user_id)->orderBy('id', 'desc')->get();
                //echo '<pre>';print_r($trans);exit;
                if (!empty($trans)) {
                    $transArr = array();
                    $transDataArr = array();

                    foreach ($trans as $key => $val) {
                        $transArr['trans_id'] = $val->id;

                        $transArr['payment_mode'] = $val->payment_mode;

                        if ($val->receiver_id == 0) {
                            $transArr['trans_from'] = $val->payment_mode;
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                            $transArr['receiver'] = 'Admin';
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = 0;
                            $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                        } elseif ($val->user_id == $request->user_id) { //User is sender
                            $transArr['trans_from'] = $val->payment_mode;
                            $transArr['sender'] = $this->getUserNameById($val->user_id);
                            $transArr['sender_id'] = $val->user_id;
                            $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                            $transArr['receiver'] = $this->getUserNameById($val->receiver_id);
                            $transArr['receiver_id'] = $val->receiver_id;
                            $transArr['receiver_phone'] = $this->getPhoneById($val->receiver_id);
                            $transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup

                            if ($val->payment_mode == 'Send Money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                                $val->payment_mode = 'wallet2wallet'; //1=Credit;2=Debit;3=topup
                                $transArr['payment_mode'] = $val->payment_mode;
                                $transArr['trans_from'] = $val->payment_mode;
                            }

                            if ($val->payment_mode != 'Cash card') {
                                if ($val->trans_type == 2) {
                                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                                } else {
                                    $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                                }
                            }

                            if ($val->payment_mode == 'Agent Deposit') {
                                $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                                $transArr['receiver'] = $this->getUserNameById($val->user_id);
                                $transArr['receiver_id'] = $val->user_id;
                                $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                                $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                                $transArr['sender_id'] = $val->receiver_id;
                                $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
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
                        } else if ($val->receiver_id == $request->user_id) { //USer is Receiver
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

                            if ($val->payment_mode == 'Send Money' || $val->payment_mode == 'Shop Payment' || $val->payment_mode == 'Online Shopping') {
                                $val->payment_mode = 'wallet2wallet'; //1=Credit;2=Debit;3=topup
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
//                                    $transArr['receiver'] = $this->getUserNameById($val->user_id);
//                                    $transArr['receiver_id'] = $val->user_id;
//                                    $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
//                                    $transArr['sender'] = $this->getUserNameById($val->receiver_id);
//                                    $transArr['sender_id'] = $val->receiver_id;
//                                    $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                                }
                                if ($val->payment_mode == 'Refund' && $val->trans_type == 1 && $val->refund_status == 0) {
                                    $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
                                }
                            }

                            if ($val->payment_mode == 'Agent Deposit') {
                                $transArr['trans_type'] = $tranType[2]; //1=Credit;2=Debit;3=topup
                                $transArr['receiver'] = $this->getUserNameById($val->user_id);
                                $transArr['receiver_id'] = $val->user_id;
                                $transArr['receiver_phone'] = $this->getPhoneById($val->user_id);
                                $transArr['sender'] = $this->getUserNameById($val->receiver_id);
                                $transArr['sender_id'] = $val->receiver_id;
                                $transArr['sender_phone'] = $this->getPhoneById($val->receiver_id);
                            }
//                            if($val->payment_mode == 'wallet2wallet' && $val->trans_type == 2){
//                                $transArr['trans_type'] = $tranType[1]; //1=Credit;2=Debit;3=topup
//                            }                            
                        }

                        if ($userInfo->user_type == 'Individual') {
                            if ($transArr['trans_type'] == 'Credit') {
                                if ($val->payment_mode == 'Refund') {
                                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount, 2, '.');
                                } else {
                                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                                }
                            } elseif ($transArr['trans_type'] == 'Topup') {
                                $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                                $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
                            } elseif ($transArr['trans_type'] == 'Request') {
                                if ($val->payment_mode == "Withdraw") {
                                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->total_amount, 2, '.');
                                } else {
                                    $transArr['trans_amount'] = $this->numberFormatPrecision($val->amount, 2, '.');
                                    $transArr['trans_amount_value'] = $this->numberFormatPrecision($val->amount_value, 2, '.');
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

                        $transArr['transaction_amount'] = $this->numberFormatPrecision($val->transaction_amount, 2, '.');
                        $transArr['trans_amount_android'] = number_format($transArr['trans_amount'], 2);

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

                    $total_page = $trans->lastPage();
                    $statusArr = array("status" => "Success", "reason" => __("message.Transaction List."), "totalPage" => $total_page);
                    $data['data'] = $transDataArr;
                    $json = array_merge($statusArr, $data);
//                    return response()->json($json, 200);
                    $json = json_encode($json);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Sorry no transaction found."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
//            } catch (\Exception $ex) {
//                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
//                return response()->json($statusArr, 200);
//            }
        }
    }

    private function getUserNameById($user_id) {
        $matchThese = ["users.id" => $user_id];
        $user = DB::table('users')->select('users.name')->where($matchThese)->first();
        return $user->name;
    }

    private function getPhoneById($user_id) {
        $matchThese = ["users.id" => $user_id];
        $user = DB::table('users')->select('users.phone')->where($matchThese)->first();
        return $user->phone;
    }

    public function depositByAgent(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $user_id = $request->user_id;
        $phone = substr($request->phone, -10);
        $amount = $request->amount;

        $lang = $request->device_language;
        App::setLocale($lang);

//        $userDetail = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userDetail = User::where('id', $user_id)->first();
//        if (empty($userDetail)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }
//        $userInfo = User::where('phone', $phone)->where('user_type', 'Agent')->where('is_verify', 1)->where('is_kyc_done', 1)->first();
        $userInfo = User::where('phone', $phone)->where('user_type', 'Agent')->first();
        if (!empty($userInfo)) {
            $trans_id = time();
            $refrence_id = time() . '-' . $userInfo->id;
            $trans = new Transaction([
                'receiver_id' => $userInfo->id,
                'user_id' => $user_id,
                'amount' => $amount,
                'amount_value' => $amount,
                'total_amount' => $amount,
                'trans_type' => 4,
                'payment_mode' => 'Agent Deposit',
                'status' => 2,
                'refrence_id' => $trans_id,
                'billing_description' => 'Deposit-' . $refrence_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $trans->save();

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

            $statusArr = array("status" => "Success", "reason" => __("message.Deposit Request Sent Successfully To Agent."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Agent is not exist for entered phone number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function withdrawByAgent(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $user_id = $request->user_id;
        $phone = substr($request->phone, -10);
        $amount = $request->amount;
        $transactionFee = $request->trans_fee;

        $lang = $request->device_language;
        App::setLocale($lang);

//        $userDetail = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $senderUser = $userDetail = User::where('id', $user_id)->first();
//        if (empty($userDetail)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }
//        $userInfo = User::where('phone', $phone)->where('user_type', 'Agent')->where('is_verify', 1)->where('is_kyc_done', 1)->first();
        $recieverUser = $userInfo = User::where('phone', $phone)->first();

        $totalAmt = $request->amount + $transactionFee;
        if (!empty($userInfo)) {

            if ($userInfo->id == $userDetail->id) {
                $statusArr = array("status" => "Failed", "reason" => __("message.You can not send fund for own account."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if ($userInfo->user_type == 'Agent') {

                // if ($transactionFee == 0 || empty($transactionFee)) {
                //     $userFee = Usertransactionfee::where('user_id', $userDetail->id)->where('transaction_type', 'Withdraw')->where('status', 1)->first();
                //     if (empty($userFee)) {
                //         $fees = Transactionfee::where('transaction_type', 'Withdraw')->where('status', 1)->first();
                //         if (!empty($fees)) {
                //             $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
                //         }
                //     } else {
                //         if (!empty($userFee)) {
                //             $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                //         }
                //     }
                //     $transactionFee = $transFee;
                //     $totalAmt = $this->numberFormatPrecision(($amount + $transFee), 2);
                // }
                if ($userDetail->wallet_balance >= $totalAmt) {
                    $trans_id = time();
                    $refrence_id = time() . '-' . $userInfo->id;
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => $userInfo->id,
                        'amount' => $amount,
                        'amount_value' => $amount,
                        'transaction_amount' => $transactionFee,
                        'total_amount' => $totalAmt,
                        'trans_type' => 4,
                        'payment_mode' => 'Withdraw',
                        'status' => 2,
                        'refrence_id' => $trans_id,
                        'billing_description' => 'Withdraw-' . $refrence_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();

                    $user_wallet_amount = $userDetail->wallet_balance - $totalAmt;
                    User::where('id', $user_id)->update(['wallet_balance' => $user_wallet_amount]);

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

                    $statusArr = array("status" => "Success", "reason" => __("message.Withdraw Request Sent Successfully To Agent."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient balance available in the wallet."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {

                if ($senderUser->user_type == 'Merchant' && $recieverUser->user_type == 'Individual') {

                    $paymentType = 'Refund';

                    if ($senderUser->trans_pay_by != 'User') {

                        $userFee = Usertransactionfee::where('user_id', $senderUser->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                            if (!empty($fees)) {
                                $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                            }
                        }

                        $transactionFee = $transFee;
                        $totalAmt = $amount + $transactionFee;

                        if ($totalAmt > $senderUser->wallet_balance) {

                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                        $receiverInfo = $senderUser;
                        $wallet_balance = $receiverInfo->wallet_balance - $totalAmt;
                        User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                        $senderInfo = $recieverUser;
                        $sender_wallet_balance = $senderInfo->wallet_balance + $amount;
                        User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                        $trans_id = time();
                        $refrence_id = time() . rand();
                        $trans = new Transaction([
                            'user_id' => $senderInfo->id,
                            'receiver_id' => $receiverInfo->id,
                            'amount' => $amount,
                            'amount_value' => $amount,
                            'transaction_amount' => $transactionFee,
                            'total_amount' => $totalAmt,
                            'trans_type' => 1,
                            'payment_mode' => 'Refund',
                            'status' => 1,
                            'refund_status' => 1,
                            'refrence_id' => $trans_id,
                            'billing_description' => $refrence_id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save();

                        $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_balance, 2, '.');
                        $data['data']['trans_amount'] = $request->amount;
                        $data['data']['receiver_name'] = $recieverUser->name;
                        $data['data']['receiver_phone'] = $recieverUser->phone;
                        $data['data']['trans_id'] = $trans_id;
                        $data['data']['trans_date'] = date('d, M Y, h:i A');

                        $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! Refund send successfully to user of " . CURR . " " . $requestDetail->amount);
                        $message = __("message.refund_fund", ['cost' => CURR . " " . $amount]);
                        $device_type = $receiverInfo->device_type;
                        $device_token = $receiverInfo->device_token;

                        $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $notif = new Notification([
                            'user_id' => $receiverInfo->id,
                            'notif_title' => $title,
                            'notif_body' => $message,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $notif->save();

                        $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! You have received refund of " . CURR . " " . $totalAmt . " from merchant");
                        $message = __("message.refund_receive", ['cost' => CURR . " " . $amount]);
                        $device_type = $senderInfo->device_type;
                        $device_token = $senderInfo->device_token;

                        $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $notif = new Notification([
                            'user_id' => $senderInfo->id,
                            'notif_title' => $title,
                            'notif_body' => $message,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $notif->save();
                    } else {

                        $receiverInfo = $senderUser;
                        // $transactionFee = $this->getRefundFee($receiverInfo->id, $requestDetail->amount);

                        $userFee = Usertransactionfee::where('user_id', $senderUser->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                            if (!empty($fees)) {
                                $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                            }
                        }
                        $transactionFee = $transFee;
                        $totalAmt = $amount - $transactionFee;

                        if ($amount > $senderUser->wallet_balance) {

                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }


                        $wallet_balance = $receiverInfo->wallet_balance - $amount;
                        User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                        $senderInfo = $recieverUser;
                        $sender_wallet_balance = $senderInfo->wallet_balance + $totalAmt;
                        User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                        $trans_id = time();
                        $refrence_id = time() . rand();
                        $trans = new Transaction([
                            'user_id' => $senderInfo->id,
                            'receiver_id' => $receiverInfo->id,
                            'amount' => $totalAmt,
                            'amount_value' => $totalAmt,
                            'transaction_amount' => $transactionFee,
                            'total_amount' => $amount,
                            'trans_type' => 1,
                            'payment_mode' => 'Refund',
                            'status' => 1,
                            'refund_status' => 1,
                            'refrence_id' => $trans_id,
                            'billing_description' => $refrence_id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save();

                        $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_balance, 2, '.');
                        $data['data']['trans_amount'] = $request->amount;
                        $data['data']['receiver_name'] = $recieverUser->name;
                        $data['data']['receiver_phone'] = $recieverUser->phone;
                        $data['data']['trans_id'] = $trans_id;
                        $data['data']['trans_date'] = date('d, M Y, h:i A');

                        $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! Refund send successfully to user of " . CURR . " " . $requestDetail->amount);
                        $message = __("message.refund_fund", ['cost' => CURR . " " . $amount]);
                        $device_type = $receiverInfo->device_type;
                        $device_token = $receiverInfo->device_token;

                        $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $notif = new Notification([
                            'user_id' => $receiverInfo->id,
                            'notif_title' => $title,
                            'notif_body' => $message,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $notif->save();

                        $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! You have received refund of " . CURR . " " . $totalAmt . " from merchant");
                        $message = __("message.refund_receive", ['cost' => CURR . " " . $totalAmt]);
                        $device_type = $senderInfo->device_type;
                        $device_token = $senderInfo->device_token;

                        $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $notif = new Notification([
                            'user_id' => $senderInfo->id,
                            'notif_title' => $title,
                            'notif_body' => $message,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $notif->save();
                    }

                    $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Amount Refunded Successfully"));
                    $json = array_merge($statusArr, $data);
//                    return response()->json($json, 200);

                    $json = json_encode($json);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {

                    if ($senderUser->user_type != 'Merchant' && $recieverUser->shopping_trans_pay_by == 'Merchant') {
                        $amount = $request->amount;
                        $user_id = $senderUser->id;
                        $payerId = $recieverUser->id;
                        $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', 'Shopping')->where('status', 1)->first();

                        if (empty($userFee)) {
                            $fees = Transactionfee::where('transaction_type', 'Shopping')->where('status', 1)->first();

                            if (!empty($fees)) {
                                $chargeFee = $fees->merchant_charge;

                                $transFee = (($amount * $chargeFee) / 100);
                            }
                        } else {
                            if (!empty($userFee)) {
                                $chargeFee = $userFee->user_charge;
                                $transFee = (($amount * $chargeFee) / 100);
                            }
                        }

                        $transactionFee = $transFee;

                        $userActiveAmount = $senderUser->wallet_balance;
                        $totalAmt = $amount - $transactionFee;

                        if ($userActiveAmount >= $amount) {
                            if (!empty($senderUser)) {
                                $trans_id = time();
                                $refrence_id = time() . rand() . $user_id;
                                $trans = new Transaction([
                                    'user_id' => $user_id,
                                    'receiver_id' => $recieverUser->id,
                                    'amount' => $totalAmt,
                                    'amount_value' => $totalAmt,
                                    'transaction_amount' => $transactionFee,
                                    'total_amount' => $amount,
                                    'trans_type' => 2,
                                    'trans_to' => 'Wallet',
                                    'payment_mode' => 'Send Money',
                                    'refrence_id' => $trans_id,
                                    'billing_description' => $refrence_id,
                                    'status' => 1,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $trans->save();
                                $TransId = $trans->id;

                                $sender_wallet_amount = $senderUser->wallet_balance - $amount;
                                User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                                $reciever_wallet_amount = $recieverUser->wallet_balance + $totalAmt;
                                User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                                $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                                $data['data']['trans_amount'] = $amount;
                                $data['data']['receiver_name'] = $recieverUser->name;
                                $data['data']['receiver_phone'] = $recieverUser->phone;
                                $data['data']['trans_id'] = $TransId;
                                $data['data']['trans_date'] = date('d, M Y, h:i A');

                                $title = __("message.debit_title", ['cost' => CURR . " " . $amount]);
                                $message = __("message.debit_message", ['cost' => CURR . " " . $amount, 'username' => $recieverUser->name]);
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

                                $title = __("message.credit_title", ['cost' => CURR . " " . $totalAmt]);
                                $message = __("message.credit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $senderUser->name]);
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

                                $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                                $json = array_merge($statusArr, $data);
//                                    return response()->json($json, 200);
                                $json = json_encode($json);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            } else {
                                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                    return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        } else {
                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                                return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {
                        $userActiveAmount = $senderUser->wallet_balance;
                        $totalAmt = $request->amount + $transactionFee;

                        if ($senderUser->user_type != 'Merchant' && $recieverUser->user_type == 'Merchant') {
                            $payment_mode = 'Shop Payment';
                        } else {
                            $payment_mode = 'Send Money';
                        }
                        if ($userActiveAmount >= $totalAmt) {
                            $trans_id = time();
                            $refrence_id = time() . rand() . $request->user_id;
                            $trans = new Transaction([
                                'user_id' => $request->user_id,
                                'receiver_id' => $recieverUser->id,
                                'amount' => $request->amount,
                                'amount_value' => $request->amount,
                                'transaction_amount' => $transactionFee,
                                'total_amount' => $totalAmt,
                                'trans_type' => 2,
                                'trans_to' => 'Wallet',
                                'payment_mode' => $payment_mode,
                                'refrence_id' => $trans_id,
                                'billing_description' => $refrence_id,
                                'status' => 1,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $trans->save();
                            $TransId = $trans->id;

                            $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                            User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                            $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                            User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                            $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                            $data['data']['trans_amount'] = $totalAmt;
                            $data['data']['receiver_name'] = $recieverUser->name;
                            $data['data']['receiver_phone'] = $recieverUser->phone;
                            $data['data']['trans_id'] = $TransId;
                            $data['data']['trans_date'] = date('d, M Y, h:i A');

                            $title = __("message.debit_title", ['cost' => CURR . " " . $totalAmt]);
                            $message = __("message.debit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
//                    $title = CURR . ' ' . $totalAmt . __("message. debited from wallet.");
//                    $message = CURR . ' ' . $totalAmt . __("message. debited from wallet for fund transfer to user ") . $recieverUser->name;
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

                            $title = __("message.credit_title", ['cost' => CURR . " " . $request->amount]);
                            $message = __("message.credit_message", ['cost' => CURR . " " . $request->amount, 'username' => $senderUser->name]);

//                    $title = CURR . ' ' . $request->amount . __("message. credited to the wallet.");
//                    $message = CURR . ' ' . $request->amount . __("message. credited to the wallet for fund transfer from user ") . $senderUser->name;
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

                            $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                            $json = array_merge($statusArr, $data);
//                        return response()->json($json, 200);
                            $json = json_encode($json);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                        return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    }
                }
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.User is not exist for entered phone number"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function fundTransfer(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Phone Number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $transactionFee = $request->trans_fee;
            $transactionType = $request->type;
//            try {         
//            $matchThese = ["users.phone" => $request->phone, "users.is_verify" => 1, "users.is_kyc_done" => 1];
            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table('users')->where($matchThese)->first();
//                 echo '<pre>';print_r($matchThese);
//        echo '<pre>';print_r($recieverUser);exit;
            if (!empty($recieverUser)) {

                if ($recieverUser->id == $request->user_id) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.You can not send fund for own account."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

//                $matchThese = ["users.id" => $request->user_id, "users.is_verify" => 1, "users.is_kyc_done" => 1];
                $matchThese = ["users.id" => $request->user_id];
                $senderUser = DB::table('users')->where($matchThese)->first();

//                if (empty($senderUser)) {
//                    $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//                    return response()->json($statusArr, 200);
//                }

                $paymentType = $this->checkTransactionType($senderUser->id, $recieverUser->id);

                if ($paymentType == 'Withdraw') {
                    $user_id = $senderUser->id;
                    $amount = $request->amount;
                    $totalAmt = $amount + $transactionFee;
                    $userDetail = $senderUser;
                    $userInfo = $recieverUser;
                    if ($userDetail->wallet_balance >= $totalAmt) {
                        $trans_id = time();
                        $refrence_id = time() . '-' . $userInfo->id;
                        $trans = new Transaction([
                            'user_id' => $user_id,
                            'receiver_id' => $userInfo->id,
                            'amount' => $amount,
                            'amount_value' => $amount,
                            'transaction_amount' => $transactionFee,
                            'total_amount' => $totalAmt,
                            'trans_type' => 4,
                            'payment_mode' => 'Withdraw',
                            'status' => 2,
                            'refrence_id' => $trans_id,
                            'billing_description' => 'Withdraw-' . $refrence_id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save();
                        $TransId = $trans->id;

                        $user_wallet_amount = $userDetail->wallet_balance - $totalAmt;
                        User::where('id', $user_id)->update(['wallet_balance' => $user_wallet_amount]);

                        $title = __("message.Withdrawal Request");
                        $message = __("message.withdraw_request", ['cost' => CURR . " " . $amount, 'username' => $userInfo->name]);
//                        $message = __("message.Your withdrawal request for " . $amount . " " . CURR . " has been sent successfully to agent " . $userInfo->name);
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

                        $data['data']['wallet_amount'] = $this->numberFormatPrecision($user_wallet_amount, 2, '.');
                        $data['data']['trans_amount'] = $totalAmt;
                        $data['data']['receiver_name'] = $recieverUser->name;
                        $data['data']['receiver_phone'] = $recieverUser->phone;
                        $data['data']['trans_id'] = $TransId;
                        $data['data']['trans_date'] = date('d, M Y, h:i A');

                        $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Withdraw Request Sent Successfully To Agent."));
                        $json = array_merge($statusArr, $data);
//                        return response()->json($json, 200);
                        $json = json_encode($json);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient balance available in the wallet."));
//                        return response()->json($statusArr, 200);
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                } else {

                    if ($paymentType == 'Deposit') {
                        $userActiveAmount = $senderUser->wallet_balance;
                        $transactionFee = $this->checkFee($recieverUser->id, 'Deposit', $request->amount, $recieverUser->user_type);
//                        exit;
                        $totalAmt = $request->amount - $transactionFee;

                        if ($userActiveAmount >= $request->amount) {
                            if (!empty($senderUser)) {
                                $trans_id = time();
                                $refrence_id = time() . rand() . $request->user_id;
                                $trans = new Transaction([
                                    'user_id' => $recieverUser->id,
                                    'receiver_id' => $request->user_id,
                                    'amount' => $totalAmt,
                                    'amount_value' => $totalAmt,
                                    'transaction_amount' => $transactionFee,
                                    'total_amount' => $request->amount,
                                    'trans_type' => 2,
                                    'trans_to' => 'Wallet',
                                    'payment_mode' => 'Agent Deposit',
                                    'refrence_id' => $trans_id,
                                    'billing_description' => $refrence_id,
                                    'status' => 1,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $trans->save();
                                $TransId = $trans->id;

                                $sender_wallet_amount = $senderUser->wallet_balance - $request->amount;
                                User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                                $reciever_wallet_amount = $recieverUser->wallet_balance + $totalAmt;
                                User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                                $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                                $data['data']['trans_amount'] = $request->amount;
                                $data['data']['receiver_name'] = $recieverUser->name;
                                $data['data']['receiver_phone'] = $recieverUser->phone;
                                $data['data']['trans_id'] = $TransId;
                                $data['data']['trans_date'] = date('d, M Y, h:i A');

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

                                $title = __("message.credit_title", ['cost' => CURR . " " . $totalAmt]);
                                $message = __("message.credit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $senderUser->name]);
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

                                $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                                $json = array_merge($statusArr, $data);
//                                return response()->json($json, 200);
                                $json = json_encode($json);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            } else {
                                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        } else {
                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {

                        if ($recieverUser->user_type == 'Merchant' && $recieverUser->shopping_trans_pay_by == 'Merchant') {
                            $amount = $request->amount;
                            $user_id = $senderUser->id;
                            $payerId = $recieverUser->id;
                            $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', 'Shopping')->where('status', 1)->first();

                            if (empty($userFee)) {
                                $fees = Transactionfee::where('transaction_type', 'Shopping')->where('status', 1)->first();

                                if (!empty($fees)) {
                                    $chargeFee = $fees->merchant_charge;

                                    $transFee = (($amount * $chargeFee) / 100);
                                }
                            } else {
                                if (!empty($userFee)) {
                                    $chargeFee = $userFee->user_charge;
                                    $transFee = (($amount * $chargeFee) / 100);
                                }
                            }

                            $transactionFee = $transFee;

                            $userActiveAmount = $senderUser->wallet_balance;
                            $totalAmt = $amount - $transactionFee;

                            if ($userActiveAmount >= $amount) {
                                if (!empty($senderUser)) {
                                    $trans_id = time();
                                    $refrence_id = time() . rand() . $user_id;
                                    $trans = new Transaction([
                                        'user_id' => $user_id,
                                        'receiver_id' => $recieverUser->id,
                                        'amount' => $totalAmt,
                                        'amount_value' => $totalAmt,
                                        'transaction_amount' => $transactionFee,
                                        'total_amount' => $amount,
                                        'trans_type' => 2,
                                        'trans_to' => 'Wallet',
                                        'payment_mode' => 'Shop Payment',
                                        'refrence_id' => $trans_id,
                                        'billing_description' => $refrence_id,
                                        'status' => 1,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $trans->save();
                                    $TransId = $trans->id;

                                    $sender_wallet_amount = $senderUser->wallet_balance - $amount;
                                    User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                                    $reciever_wallet_amount = $recieverUser->wallet_balance + $totalAmt;
                                    User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                                    $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                                    $data['data']['trans_amount'] = $amount;
                                    $data['data']['receiver_name'] = $recieverUser->name;
                                    $data['data']['receiver_phone'] = $recieverUser->phone;
                                    $data['data']['trans_id'] = $TransId;
                                    $data['data']['trans_date'] = date('d, M Y, h:i A');

                                    $title = __("message.debit_title", ['cost' => CURR . " " . $amount]);
                                    $message = __("message.debit_message", ['cost' => CURR . " " . $amount, 'username' => $recieverUser->name]);
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

                                    $title = __("message.credit_title", ['cost' => CURR . " " . $totalAmt]);
                                    $message = __("message.credit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $senderUser->name]);
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

                                    $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                                    $json = array_merge($statusArr, $data);
//                                    return response()->json($json, 200);
                                    $json = json_encode($json);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                } else {
                                    $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                    return response()->json($statusArr, 200);
                                    $json = json_encode($statusArr);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                }
                            } else {
                                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                                return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        } else {
                            $userActiveAmount = $senderUser->wallet_balance;
                            $totalAmt = $request->amount + $transactionFee;

                            if ($userActiveAmount >= $totalAmt) {
                                if (!empty($senderUser)) {
                                    $trans_id = time();
                                    $refrence_id = time() . rand() . $request->user_id;
                                    $trans = new Transaction([
                                        'user_id' => $request->user_id,
                                        'receiver_id' => $recieverUser->id,
                                        'amount' => $request->amount,
                                        'amount_value' => $request->amount,
                                        'transaction_amount' => $transactionFee,
                                        'total_amount' => $totalAmt,
                                        'trans_type' => 2,
                                        'trans_to' => 'Wallet',
                                        'payment_mode' => 'Send Money',
                                        'refrence_id' => $trans_id,
                                        'billing_description' => $refrence_id,
                                        'status' => 1,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $trans->save();
                                    $TransId = $trans->id;

                                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                                    User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                                    $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                                    User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                                    $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                                    $data['data']['trans_amount'] = $totalAmt;
                                    $data['data']['receiver_name'] = $recieverUser->name;
                                    $data['data']['receiver_phone'] = $recieverUser->phone;
                                    $data['data']['trans_id'] = $TransId;
                                    $data['data']['trans_date'] = date('d, M Y, h:i A');

                                    $title = __("message.debit_title", ['cost' => CURR . " " . $totalAmt]);
                                    $message = __("message.debit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
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

                                    $title = __("message.credit_title", ['cost' => CURR . " " . $request->amount]);
                                    $message = __("message.credit_message", ['cost' => CURR . " " . $request->amount, 'username' => $senderUser->name]);
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

                                    $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                                    $json = array_merge($statusArr, $data);
//                                    return response()->json($json, 200);
                                    $json = json_encode($json);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                } else {
                                    $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                    return response()->json($statusArr, 200);
                                    $json = json_encode($statusArr);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                }
                            } else {
                                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                                return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        }
                    }
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
//            } catch (\Exception $ex) {
//                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
//                return response()->json($statusArr, 200);
//            }
        }
    }

    public function fundTransfer1(Request $request) {

        $request = $this->decryptContent($request->req);
        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = array("status" => "Failed", "reason" => "Invalid Phone Number.");
            return response()->json($statusArr, 200);
        } else if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => "Invalid User Id.");
            return response()->json($statusArr, 200);
        } else if ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = array("status" => "Failed", "reason" => "Invalid QR Code.");
            return response()->json($statusArr, 200);
        } else {
            $transactionFee = $request->trans_fee;
            $transactionType = $request->type;
//            try {         
//            $matchThese = ["users.phone" => $request->phone, "users.is_verify" => 1, "users.is_kyc_done" => 1];
            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table('users')->where($matchThese)->first();
//                 echo '<pre>';print_r($matchThese);
//        echo '<pre>';print_r($recieverUser);exit;
            if (!empty($recieverUser)) {

                if ($recieverUser->id == $request->user_id) {
                    $statusArr = array("status" => "Failed", "reason" => "You can not send fund for own account.");
                    return response()->json($statusArr, 200);
                }

//                $matchThese = ["users.id" => $request->user_id, "users.is_verify" => 1, "users.is_kyc_done" => 1];
                $matchThese = ["users.id" => $request->user_id];
                $senderUser = DB::table('users')->where($matchThese)->first();

//                if (empty($senderUser)) {
//                    $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//                    return response()->json($statusArr, 200);
//                }
                $userActiveAmount = $senderUser->wallet_balance;
                $totalAmt = $request->amount + $transactionFee;

                if ($userActiveAmount >= $totalAmt) {
                    if (!empty($senderUser)) {
                        $trans_id = time();
                        $refrence_id = time() . rand() . $request->user_id;
                        $trans = new Transaction([
                            'user_id' => $request->user_id,
                            'receiver_id' => $recieverUser->id,
                            'amount' => $request->amount,
                            'amount_value' => $request->amount,
                            'transaction_amount' => $transactionFee,
                            'total_amount' => $totalAmt,
                            'trans_type' => 2,
                            'trans_to' => 'Wallet',
                            'payment_mode' => 'wallet2wallet',
                            'refrence_id' => $trans_id,
                            'billing_description' => $refrence_id,
                            'status' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save();
                        $TransId = $trans->id;

                        $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                        User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                        $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                        User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                        $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                        $data['data']['trans_amount'] = $totalAmt;
                        $data['data']['receiver_name'] = $recieverUser->name;
                        $data['data']['receiver_phone'] = $recieverUser->phone;
                        $data['data']['trans_id'] = $TransId;
                        $data['data']['trans_date'] = date('d, M Y, h:i A');

                        $title = __("message.debit_title", ['cost' => CURR . " " . $totalAmt]);
                        $message = __("message.debit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
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

                        $title = __("message.credit_title", ['cost' => CURR . " " . $request->amount]);
                        $message = __("message.credit_message", ['cost' => CURR . " " . $request->amount, 'username' => $senderUser->name]);
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

                        $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => 'Sent Successfully');
                        $json = array_merge($statusArr, $data);
//                        return response()->json($json, 200);
                        $json = json_encode($json);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $statusArr = array("status" => "Failed", "reason" => "Receiver not found or not verified");
                        return response()->json($statusArr, 200);
                    }
                } else {
                    $statusArr = array("status" => "Failed", "reason" => "Insufficient Balance.");
                    return response()->json($statusArr, 200);
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => "Receiver not found or not verified");
                return response()->json($statusArr, 200);
            }
//            } catch (\Exception $ex) {
//                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
//                return response()->json($statusArr, 200);
//            }
        }
    }

    public function generateQR(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $lang = $request->device_language;
        App::setLocale($lang);
        if ($request->user_id == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User ID."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->phone == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Phone Number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->amount == "" || $request->amount == 0) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table('users')->where($matchThese)->first();

            if (empty($recieverUser)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $user_id = $request->user_id;
                $qrString = $request->user_id . "##" . $request->phone . '##' . $request->amount;
                $qrCode = $this->generateQRCode($qrString, $user_id);

                $qrcode = HTTP_PATH . "/public/" . $qrCode;

                $statusArr = array("status" => "Success", 'qr_code' => $qrcode, 'sender_name' => $recieverUser->name, "reason" => __("message.QR Code Detail."));
//                return response()->json($statusArr, 201);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 201);
            }
        }
    }

    public function getUserByQR(Request $request) {

        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);
        if ($request->qr_code == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            $qrCodeArr = explode("##", $request->qr_code);

            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            if (isset($qrCodeArr[1]) && !empty($qrCodeArr[1])) {
                $qrNm = $qrCodeArr[1];
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            if (isset($qrCodeArr[2]) && !empty($qrCodeArr[2])) {
                $qrAmt = $qrCodeArr[2];
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $matchThese = ["users.id" => $qrId];
            $user = DB::table('users')->where($matchThese)->first();

            $matchThese = ["users.phone" => $qrNm];
            $userByPhone = DB::table('users')->where($matchThese)->first();

            if (empty($userByPhone)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            if ($user) {
                $statusArr = array("status" => "Success", "reason" => __("message.User detail."));
                $userData['id'] = $user->id;
                $userData['name'] = $userByPhone->name;
                $userData['phone'] = $qrNm;
                $userData['amount'] = $qrAmt;
                $data['data'] = $userData;
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 201);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 201);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function selectMobileCard(Request $request) {
//        $user_id = $request->user_id;

        $request = $this->decryptContent($request->req);

        $lang = $request->device_language;
        App::setLocale($lang);

        $data = array();
        $cards = Card::where('card_type', 2)->where('status', 1)->get();
        //echo '<pre>';print_r($cards);exit;
        if (!empty($cards)) {
            foreach ($cards as $card) {
                $carddetails = Carddetail::where('card_id', $card->id)->where('status', 1)->where('used_status', 0)->get();
                if (count($carddetails) > 0) {
                    $cardData['card_id'] = $card->id;
                    $cardData['card_image'] = COMPANY_FULL_DISPLAY_PATH . $card->company_image;
                    $data['data'][] = $cardData;
                }
            }

            if (isset($data['data'])) {
                $statusArr = array("status" => "Success", "reason" => __("message.Card List"));
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Mobile recharge card not available"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Mobile recharge card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function mobileCardList(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $card_id = $request->card_id;
        $user_id = $request->user_id;

        $lang = $request->device_language;
        App::setLocale($lang);

        $agentOffer = Agentoffer::where('user_id', $user_id)->where('type', 'Mobile Card')->where('status', 1)->first();
        // $offer = Offer::where('type', 'Mobile Card')->where('status', 1)->first();

        $data = array();
        $carddetails = Carddetail::where('card_id', $card_id)->where('status', 1)->where('used_status', 0)->groupBy('real_value', 'currency')->get();
        $cardValue = Card::where('id', $card_id)->first();
        $userDetail = User::where('id', $user_id)->first();

        if (!empty($carddetails)) {
            foreach ($carddetails as $card) {
                $cardData['card_id'] = $card->id;
                $cardData['card_name'] = $cardValue->company_name;
                $cardData['currency'] = $card->currency;
                $cardData['real_value'] = $this->numberFormatPrecision($card->real_value, 2, '.');
                $cardData['card_value'] = $this->numberFormatPrecision($card->card_value, 2, '.');

                if ($userDetail->user_type == 'Agent') {
                    if (!empty($agentOffer)) {
                        $cardData['card_value'] = $this->numberFormatPrecision(($card->agent_card_value - (($card->agent_card_value * $agentOffer->offer) / 100)), 2);
                    } else {
                        $cardData['card_value'] = $this->numberFormatPrecision($card->agent_card_value, 2, '.');
                    }
                }

                // if ($userDetail->user_type == 'Agent') {
                //     if (!empty($agentOffer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $agentOffer->offer) / 100)), 2);
                //     } elseif (!empty($offer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $offer->offer) / 100)), 2);
                //     }
                // }
                $cardData['card_description'] = $card->description ? $card->description : '';
                $data['data'][] = $cardData;
            }

            $statusArr = array("status" => "Success", "reason" => __("message.Card List"));
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Mobile recharge card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function buyMobileCard(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $card_id = $request->card_id;
        $user_id = $request->user_id;
        $card_value = str_replace(",", "", $request->card_value);

        $lang = $request->device_language;
        App::setLocale($lang);

        $data = array();
        $carddetail = Carddetail::where('id', $card_id)->where('status', 1)->where('used_status', 0)->first();
//        $userInfo = User::where('id', $user_id)->first();
//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where('id', $user_id)->first();
//        if (empty($userInfo)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }

        if (!empty($carddetail)) {
            if ($userInfo->wallet_balance >= $card_value) {
//                $transactionFee = $carddetail->real_value - $card_value;
                $transactionFee = 0;
                $trans_id = time();
                $refrence_id = time() . rand() . '-' . $card_id;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'amount' => $card_value,
                    'amount_value' => $card_value,
                    'currency' => $carddetail->currency,
                    'real_value' => $carddetail->real_value,
                    'transaction_amount' => 0,
                    'total_amount' => $card_value,
                    'trans_type' => 2,
                    'trans_to' => 'Wallet',
                    'trans_for' => 'Mobile Recharge',
                    'company_name' => $carddetail->Card->company_name,
                    'payment_mode' => 'Mobile Recharge',
                    'refrence_id' => $trans_id,
                    'billing_description' => $refrence_id,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $trans->save();
                $TransId = $trans->id;

                $sender_wallet_amount = $userInfo->wallet_balance - $card_value;
                User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                Carddetail::where('id', $carddetail->id)->update(array('used_status' => 1, 'used_by' => $user_id, 'used_date' => date('Y-m-d H:i:s')));

                $title = __("message.Buy Mobile Card");
//                $message = __("message.Successful purchase of recharge PIN equivalent to " . $carddetail->currency . " " . $carddetail->real_value . " from System. " . $carddetail->Card->company_name . " " . $carddetail->currency . " " . $carddetail->real_value . " Serial No " . $carddetail->serial_number . " PIN: " . $carddetail->pin_number . " " . $carddetail->instruction);

                $message = __("message.success_purchase", ['cost' => $carddetail->currency . " " . $carddetail->real_value, 'company' => $carddetail->Card->company_name, 'serial' => $carddetail->serial_number, 'pin' => $carddetail->pin_number, 'instruction' => $carddetail->instruction]);

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

                $result['serial_number'] = $carddetail->serial_number;
                $result['pin_number'] = $carddetail->pin_number;
                $result['instruction'] = $carddetail->instruction;
                $result['get_date'] = date('d/m/Y');
                $result['get_time'] = date('h:i A');

//                $html_content = '<html><body>Test</body></html>';
//                
//                $result['html_content'] = $html_content;

                $data['data'] = $result;
                $statusArr = array("status" => "Success", "reason" => __("message.Transaction Completed"));
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient balance available in the wallet."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Mobile recharge card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function selectOnlineCard(Request $request) {

        $request = $this->decryptContent($request->req);
//        $user_id = $request->user_id;

        $lang = $request->device_language;
        App::setLocale($lang);

        $data = array();
        $cards = Card::where('card_type', 3)->where('status', 1)->get();
        //echo '<pre>';print_r($cards);exit;
        if (!empty($cards)) {
            foreach ($cards as $card) {
                $carddetails = Carddetail::where('card_id', $card->id)->where('status', 1)->where('used_status', 0)->get();
                if (count($carddetails) > 0) {
                    $cardData['card_id'] = $card->id;
                    $cardData['card_image'] = COMPANY_FULL_DISPLAY_PATH . $card->company_image;
                    $data['data'][] = $cardData;
                }
            }

            if (isset($data['data'])) {
                $statusArr = array("status" => "Success", "reason" => __("message.Card List"));
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Online card not available"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Online card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function onlineCardList(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $card_id = $request->card_id;
        $user_id = $request->user_id;

        $lang = $request->device_language;
        App::setLocale($lang);

        $agentOffer = Agentoffer::where('user_id', $user_id)->where('type', 'Online Card')->where('status', 1)->first();
        // $offer = Offer::where('type', 'Online Card')->where('status', 1)->first();

        $data = array();
        $carddetails = Carddetail::where('card_id', $card_id)->where('status', 1)->where('used_status', 0)->groupBy('real_value', 'currency')->get();
        $cardValue = Card::where('id', $card_id)->first();
        $userDetail = User::where('id', $user_id)->first();

        if (!empty($carddetails)) {
            foreach ($carddetails as $card) {
                $cardData['card_id'] = $card->id;
                $cardData['card_name'] = $cardValue->company_name;
                $cardData['currency'] = $card->currency;
                $cardData['real_value'] = $this->numberFormatPrecision($card->real_value, 2, '.');
                $cardData['card_value'] = $this->numberFormatPrecision($card->card_value, 2, '.');

                if ($userDetail->user_type == 'Agent') {
                    if (!empty($agentOffer)) {
                        $cardData['card_value'] = $this->numberFormatPrecision(($card->agent_card_value - (($card->agent_card_value * $agentOffer->offer) / 100)), 2);
                    } else {
                        $cardData['card_value'] = $this->numberFormatPrecision($card->agent_card_value, 2, '.');
                    }
                }

                // if ($userDetail->user_type == 'Agent') {
                //     if (!empty($agentOffer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $agentOffer->offer) / 100)), 2);
                //     } elseif (!empty($offer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $offer->offer) / 100)), 2);
                //     }
                // }
                $cardData['card_description'] = $card->description ? $card->description : '';
                $data['data'][] = $cardData;
            }

            $statusArr = array("status" => "Success", "reason" => __("message.Card List"));
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Online card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function buyOnlineCard(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $card_id = $request->card_id;
        $user_id = $request->user_id;
        $card_value = str_replace(",", "", $request->card_value);

        $lang = $request->device_language;
        App::setLocale($lang);

        $data = array();
        $carddetail = Carddetail::where('id', $card_id)->where('status', 1)->where('used_status', 0)->first();
//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where('id', $user_id)->first();
//        if (empty($userInfo)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }

        if (!empty($carddetail)) {
            if ($userInfo->wallet_balance >= $card_value) {
//                $transactionFee = $carddetail->real_value - $card_value;
                $trans_id = time();
                $refrence_id = time() . rand() . '-' . $card_id;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'amount' => $card_value,
                    'amount_value' => $card_value,
                    'currency' => $carddetail->currency,
                    'real_value' => $carddetail->real_value,
                    'transaction_amount' => 0,
                    'total_amount' => $card_value,
                    'trans_type' => 2,
                    'trans_to' => 'Wallet',
                    'trans_for' => 'Online Card',
                    'company_name' => $carddetail->Card->company_name,
                    'payment_mode' => 'Online Card',
                    'refrence_id' => $trans_id,
                    'billing_description' => $refrence_id,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $trans->save();
                $TransId = $trans->id;

                $sender_wallet_amount = $userInfo->wallet_balance - $card_value;
                User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                Carddetail::where('id', $carddetail->id)->update(array('used_status' => 1, 'used_by' => $user_id, 'used_date' => date('Y-m-d H:i:s')));

                $result['serial_number'] = $carddetail->serial_number;
                $result['pin_number'] = $carddetail->pin_number;
                $result['instruction'] = $carddetail->instruction;

                $title = __("message.Buy Online Card");
//                $message = __("message.Successful purchase of recharge PIN equivalent to " . $carddetail->currency . " " . $carddetail->real_value . " from System. " . $carddetail->Card->company_name . " " . $carddetail->currency . " " . $carddetail->real_value . " Serial No " . $carddetail->serial_number . " PIN: " . $carddetail->pin_number . " " . $carddetail->instruction);
                $message = __("message.success_purchase", ['cost' => $carddetail->currency . " " . $carddetail->real_value, 'company' => $carddetail->Card->company_name, 'serial' => $carddetail->serial_number, 'pin' => $carddetail->pin_number, 'instruction' => $carddetail->instruction]);
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

                $data['data'] = $result;
                $statusArr = array("status" => "Success", "reason" => __("message.Transaction Completed"));
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient balance available in the wallet."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Online card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function selectInternetCard(Request $request) {

        $request = $this->decryptContent($request->req);
//        $user_id = $request->user_id;

        $lang = $request->device_language;
        App::setLocale($lang);

        $data = array();
        $cards = Card::where('card_type', 1)->where('status', 1)->get();
        //echo '<pre>';print_r($cards);exit;
        if (!empty($cards)) {
            foreach ($cards as $card) {
                $carddetails = Carddetail::where('card_id', $card->id)->where('status', 1)->where('used_status', 0)->get();
                if (count($carddetails) > 0) {
                    $cardData['card_id'] = $card->id;
                    $cardData['card_image'] = COMPANY_FULL_DISPLAY_PATH . $card->company_image;
                    $data['data'][] = $cardData;
                }
            }

            if (isset($data['data'])) {
                $statusArr = array("status" => "Success", "reason" => __("message.Card List"));
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Internet card not available"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Internet card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function internetCardList(Request $request) {

        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $card_id = $request->card_id;
        $user_id = $request->user_id;

        $lang = $request->device_language;
        App::setLocale($lang);

        $agentOffer = Agentoffer::where('user_id', $user_id)->where('type', 'Internet Card')->where('status', 1)->first();
        // $offer = Offer::where('type', 'Internet Card')->where('status', 1)->first();

        $data = array();
        $carddetails = Carddetail::where('card_id', $card_id)->where('status', 1)->where('used_status', 0)->groupBy('real_value', 'currency')->get();
        $cardValue = Card::where('id', $card_id)->first();
        $userDetail = User::where('id', $user_id)->first();

        if (!empty($carddetails)) {
            foreach ($carddetails as $card) {
                $cardData['card_id'] = $card->id;
                $cardData['card_name'] = $cardValue->company_name;
                $cardData['currency'] = $card->currency;
                $cardData['real_value'] = $this->numberFormatPrecision($card->real_value, 2, '.');
                $cardData['card_value'] = $this->numberFormatPrecision($card->card_value, 2, '.');

                if ($userDetail->user_type == 'Agent') {
                    if (!empty($agentOffer)) {
                        $cardData['card_value'] = $this->numberFormatPrecision(($card->agent_card_value - (($card->agent_card_value * $agentOffer->offer) / 100)), 2);
                    } else {
                        $cardData['card_value'] = $this->numberFormatPrecision($card->agent_card_value, 2, '.');
                    }
                }

                // if ($userDetail->user_type == 'Agent') {
                //     if (!empty($agentOffer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $agentOffer->offer) / 100)), 2);
                //     } elseif (!empty($offer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $offer->offer) / 100)), 2);
                //     }
                // }
                $cardData['card_description'] = $card->description ? $card->description : '';
                $data['data'][] = $cardData;
            }

            $statusArr = array("status" => "Success", "reason" => "Card List");
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Internet card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function buyInternetCard(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $card_id = $request->card_id;
        $user_id = $request->user_id;
//        $bundle_number = $request->bundle_number;
        $card_value = str_replace(",", "", $request->card_value);

        $lang = $request->device_language;
        App::setLocale($lang);

        $data = array();
        $carddetail = Carddetail::where('id', $card_id)->where('status', 1)->where('used_status', 0)->first();
//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where('id', $user_id)->first();
//        if (empty($userInfo)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }

        if (!empty($carddetail)) {
            if ($userInfo->wallet_balance >= $card_value) {
//                $transactionFee = $carddetail->real_value - $card_value;
                $transactionFee = 0;
                $trans_id = time();
                $refrence_id = time() . rand() . '-' . $card_id;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'amount' => $card_value,
                    'amount_value' => $card_value,
                    'currency' => $carddetail->currency,
                    'real_value' => $carddetail->real_value,
                    'transaction_amount' => 0,
                    'total_amount' => $card_value,
                    'trans_type' => 2,
                    'trans_to' => 'Wallet',
                    'trans_for' => 'Internet Recharge',
                    'company_name' => $carddetail->Card->company_name,
                    'payment_mode' => 'Internet Recharge',
                    'refrence_id' => $trans_id,
                    'billing_description' => $refrence_id,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $trans->save();
                $TransId = $trans->id;

                $sender_wallet_amount = $userInfo->wallet_balance - $card_value;
                User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                Carddetail::where('id', $carddetail->id)->update(array('used_status' => 1, 'used_by' => $user_id, 'used_date' => date('Y-m-d H:i:s')));

                $result['serial_number'] = $carddetail->serial_number;
                $result['pin_number'] = $carddetail->pin_number;
                $result['instruction'] = $carddetail->instruction;

                $title = __("message.Buy Internet Card");
//                $message = __("message.Successful purchase of recharge PIN equivalent to " . $carddetail->currency . " " . $carddetail->real_value . " from System. " . $carddetail->Card->company_name . " " . $carddetail->currency . " " . $carddetail->real_value . " Serial No " . $carddetail->serial_number . " PIN: " . $carddetail->pin_number . " " . $carddetail->instruction);
                $message = __("message.success_purchase", ['cost' => $carddetail->currency . " " . $carddetail->real_value, 'company' => $carddetail->Card->company_name, 'serial' => $carddetail->serial_number, 'pin' => $carddetail->pin_number, 'instruction' => $carddetail->instruction]);
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

                $data['data'] = $result;
                $statusArr = array("status" => "Success", "reason" => __("message.Transaction Completed"));
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient balance available in the wallet."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Online card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function nearByUser(Request $request) {
        $request = $this->decryptContent($request->req);
        $user_id = $request->user_id;
        $user_type = $request->user_type;
//        $lat = $request->lat;
//        $lng = $request->lng;

        $lang = $request->device_language;
        App::setLocale($lang);

        if ($user_type == 'Reseller') {
            $user_type = 'Agent';
        }

//        $userDetail = User::where('id', $user_id)->first();
//        $lat = $userDetail->lat;
//        $lng = $userDetail->lng;

        $users = User::where('is_verify', 1)->where('user_type', $user_type)->get();

//        $stateQry = "SELECT *,(3959 *acos(cos(radians(" . $lat . ")) *cos(radians(lat)) *cos(radians(lng) -radians(" . $lng . ")) +sin(radians(" . $lat . ")) *sin(radians(lat )))) AS distance FROM users Where user_type='" . $user_type . "' HAVING distance < 50 ORDER BY distance LIMIT 0, 20";
//        $users = DB::select($stateQry);

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
//                $userData['distance'] = number_format($userInfo->distance * 1.609344, 1); /* KM */
                if ($userInfo->profile_image != '') {
                    $userData['profile_image'] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                } else {
                    $userData['profile_image'] = HTTP_PATH . '/public/img/' . 'no_user.png';
                }
                $records[] = $userData;
            }

            $statusArr = array("status" => "Success", "reason" => "Users List");
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

    public function merchantList(Request $request) {
        $request = $this->decryptContent($request->req);
        $users = User::where('is_verify', 1)->where('user_type', 'Merchant')->get();

        $lang = $request->device_language;
        App::setLocale($lang);

        $records = array();
        if ($users) {
            foreach ($users as $userInfo) {
                $userData = array();
                $userData['user_id'] = $userInfo->id;
                $userData['name'] = $userInfo->business_name;
                $userData['phone'] = $userInfo->phone;
                if ($userInfo->profile_image != '') {
                    $userData['business_image'] = PROFILE_FULL_DISPLAY_PATH . $userInfo->profile_image;
                } else {
                    $userData['business_image'] = HTTP_PATH . '/public/img/' . 'no_user.png';
                }
                $userData['latitude'] = $userInfo->lat ? $userInfo->lat : '0.00';
                $userData['longitude'] = $userInfo->lng ? $userInfo->lng : '0.00';
                $records[] = $userData;
            }

            $statusArr = array("status" => "Success", "reason" => "Merchants List");
            $data['data'] = $records;
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Merchants not available."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function getMerchantByQR(Request $request) {
        $request = $this->decryptContent($request->req);
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->qr_code == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $qrCodeArr = explode("##", $request->qr_code);
            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            if (isset($qrCodeArr[1]) && !empty($qrCodeArr[1])) {
                $qrNm = $qrCodeArr[1];
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if(isset($qrCodeArr[2])){
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            $matchThese = ["users.id" => $qrId];
            $user = DB::table('users')->where($matchThese)->first();
            if ($user) {
                $statusArr = array("status" => "Success", "reason" => __("message.Merchant detail."));
                $userData['id'] = $user->id;
                $userData['name'] = $user->business_name;
                $userData['phone'] = $user->phone;
                $data['data'] = $userData;
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 201);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 201);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function getAgentByQR(Request $request) {
        $request = $this->decryptContent($request->req);
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->qr_code == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $qrCodeArr = explode("##", $request->qr_code);
            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
            if (isset($qrCodeArr[1]) && !empty($qrCodeArr[1])) {
                $qrNm = $qrCodeArr[1];
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $matchThese = ["users.id" => $qrId, 'users.user_Type' => 'Agent'];
            $user = DB::table('users')->where($matchThese)->first();
            if ($user) {
                $statusArr = array("status" => "Success", "reason" => __("message.Merchant detail."));
                $userData['id'] = $user->id;
                $userData['name'] = $user->name;
                $userData['phone'] = $user->phone;
                $data['data'] = $userData;
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 201);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 201);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function shopPayment(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Phone Number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            $transactionFee = $request->trans_fee;
//            try {         
//            $matchThese = ["users.phone" => $request->phone, "users.is_verify" => 1, "users.is_kyc_done" => 1];
            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table('users')->where($matchThese)->first();
            if (!empty($recieverUser)) {

                if ($recieverUser->id == $request->user_id) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.You can not send fund for own account."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

//                $matchThese = ["users.id" => $request->user_id, "users.is_verify" => 1, "users.is_kyc_done" => 1];
                $matchThese = ["users.id" => $request->user_id];
                $senderUser = DB::table('users')->where($matchThese)->first();

//                if (empty($senderUser)) {
//                    $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//                    return response()->json($statusArr, 200);
//                }

                $paymentType = $this->checkTransactionType($senderUser->id, $recieverUser->id);

                if ($paymentType == 'Withdraw') {
                    $user_id = $senderUser->id;
                    $amount = $request->amount;
                    $totalAmt = $amount + $transactionFee;
                    $userDetail = $senderUser;
                    $userInfo = $recieverUser;
                    if ($userDetail->wallet_balance >= $totalAmt) {
                        $trans_id = time();
                        $refrence_id = time() . '-' . $userInfo->id;
                        $trans = new Transaction([
                            'user_id' => $user_id,
                            'receiver_id' => $userInfo->id,
                            'amount' => $amount,
                            'amount_value' => $amount,
                            'transaction_amount' => $transactionFee,
                            'total_amount' => $totalAmt,
                            'trans_type' => 4,
                            'payment_mode' => 'Withdraw',
                            'status' => 2,
                            'refrence_id' => $trans_id,
                            'billing_description' => 'Withdraw-' . $refrence_id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save();
                        $TransId = $trans->id;

                        $user_wallet_amount = $userDetail->wallet_balance - $totalAmt;
                        User::where('id', $user_id)->update(['wallet_balance' => $user_wallet_amount]);

                        $title = __("message.Withdrawal Request");
                        $message = __("message.withdraw_request", ['cost' => CURR . " " . $amount, 'username' => $userInfo->name]);
//                        $message = __("message.Your withdrawal request for " . $amount . " " . CURR . " has been sent successfully to agent " . $userInfo->name);
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

                        $data['data']['wallet_amount'] = $this->numberFormatPrecision($user_wallet_amount, 2, '.');
                        $data['data']['trans_amount'] = $totalAmt;
                        $data['data']['receiver_name'] = $recieverUser->name;
                        $data['data']['receiver_phone'] = $recieverUser->phone;
                        $data['data']['trans_id'] = $TransId;
                        $data['data']['trans_date'] = date('d, M Y, h:i A');

                        $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Withdraw Request Sent Successfully To Agent."));
                        $json = array_merge($statusArr, $data);
//                        return response()->json($json, 200);
                        $json = json_encode($json);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } else {
                        $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient balance available in the wallet."));
//                        return response()->json($statusArr, 200);
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                } else {

                    if ($paymentType == 'Deposit') {
                        $userActiveAmount = $senderUser->wallet_balance;
                        $transactionFee = $this->checkFee($recieverUser->id, 'Deposit', $request->amount, $recieverUser->user_type);
//                        exit;
                        $totalAmt = $request->amount - $transactionFee;

                        if ($userActiveAmount >= $request->amount) {
                            if (!empty($senderUser)) {
                                $trans_id = time();
                                $refrence_id = time() . rand() . $request->user_id;
                                $trans = new Transaction([
                                    'user_id' => $recieverUser->id,
                                    'receiver_id' => $request->user_id,
                                    'amount' => $totalAmt,
                                    'amount_value' => $totalAmt,
                                    'transaction_amount' => $transactionFee,
                                    'total_amount' => $request->amount,
                                    'trans_type' => 2,
                                    'trans_to' => 'Wallet',
                                    'payment_mode' => 'Agent Deposit',
                                    'refrence_id' => $trans_id,
                                    'billing_description' => $refrence_id,
                                    'status' => 1,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $trans->save();
                                $TransId = $trans->id;

                                $sender_wallet_amount = $senderUser->wallet_balance - $request->amount;
                                User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                                $reciever_wallet_amount = $recieverUser->wallet_balance + $totalAmt;
                                User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                                $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                                $data['data']['trans_amount'] = $request->amount;
                                $data['data']['receiver_name'] = $recieverUser->name;
                                $data['data']['receiver_phone'] = $recieverUser->phone;
                                $data['data']['trans_id'] = $TransId;
                                $data['data']['trans_date'] = date('d, M Y, h:i A');

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

                                $title = __("message.credit_title", ['cost' => CURR . " " . $totalAmt]);
                                $message = __("message.credit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $senderUser->name]);
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

                                $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                                $json = array_merge($statusArr, $data);
//                                return response()->json($json, 200);
                                $json = json_encode($json);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            } else {
                                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        } else {
                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {

                        if ($recieverUser->user_type == 'Merchant' && $recieverUser->shopping_trans_pay_by == 'Merchant') {
                            $amount = $request->amount;
                            $user_id = $senderUser->id;
                            $payerId = $recieverUser->id;
                            $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', 'Shopping')->where('status', 1)->first();

                            if (empty($userFee)) {
                                $fees = Transactionfee::where('transaction_type', 'Shopping')->where('status', 1)->first();

                                if (!empty($fees)) {
                                    $chargeFee = $fees->merchant_charge;

                                    $transFee = (($amount * $chargeFee) / 100);
                                }
                            } else {
                                if (!empty($userFee)) {
                                    $chargeFee = $userFee->user_charge;
                                    $transFee = (($amount * $chargeFee) / 100);
                                }
                            }

                            $transactionFee = $transFee;

                            $userActiveAmount = $senderUser->wallet_balance;
                            $totalAmt = $amount - $transactionFee;

                            if ($userActiveAmount >= $amount) {
                                if (!empty($senderUser)) {
                                    $trans_id = time();
                                    $refrence_id = time() . rand() . $user_id;
                                    $trans = new Transaction([
                                        'user_id' => $user_id,
                                        'receiver_id' => $recieverUser->id,
                                        'amount' => $totalAmt,
                                        'amount_value' => $totalAmt,
                                        'transaction_amount' => $transactionFee,
                                        'total_amount' => $amount,
                                        'trans_type' => 2,
                                        'trans_to' => 'Wallet',
                                        'payment_mode' => 'Send Money',
                                        'refrence_id' => $trans_id,
                                        'billing_description' => $refrence_id,
                                        'status' => 1,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $trans->save();
                                    $TransId = $trans->id;

                                    $sender_wallet_amount = $senderUser->wallet_balance - $amount;
                                    User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                                    $reciever_wallet_amount = $recieverUser->wallet_balance + $totalAmt;
                                    User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                                    $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                                    $data['data']['trans_amount'] = $amount;
                                    $data['data']['receiver_name'] = $recieverUser->name;
                                    $data['data']['receiver_phone'] = $recieverUser->phone;
                                    $data['data']['trans_id'] = $TransId;
                                    $data['data']['trans_date'] = date('d, M Y, h:i A');

                                    $title = __("message.debit_title", ['cost' => CURR . " " . $amount]);
                                    $message = __("message.debit_message", ['cost' => CURR . " " . $amount, 'username' => $recieverUser->name]);
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

                                    $title = __("message.credit_title", ['cost' => CURR . " " . $totalAmt]);
                                    $message = __("message.credit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $senderUser->name]);
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

                                    $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                                    $json = array_merge($statusArr, $data);
//                                    return response()->json($json, 200);
                                    $json = json_encode($json);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                } else {
                                    $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                    return response()->json($statusArr, 200);
                                    $json = json_encode($statusArr);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                }
                            } else {
                                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                                return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        } else {
                            $userActiveAmount = $senderUser->wallet_balance;
                            $totalAmt = $request->amount + $transactionFee;

                            if ($userActiveAmount >= $totalAmt) {
                                if (!empty($senderUser)) {
                                    $trans_id = time();
                                    $refrence_id = time() . rand() . $request->user_id;
                                    $trans = new Transaction([
                                        'user_id' => $request->user_id,
                                        'receiver_id' => $recieverUser->id,
                                        'amount' => $request->amount,
                                        'amount_value' => $request->amount,
                                        'transaction_amount' => $transactionFee,
                                        'total_amount' => $totalAmt,
                                        'trans_type' => 2,
                                        'trans_to' => 'Wallet',
                                        'payment_mode' => 'Send Money',
                                        'refrence_id' => $trans_id,
                                        'billing_description' => $refrence_id,
                                        'status' => 1,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $trans->save();
                                    $TransId = $trans->id;

                                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                                    User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                                    $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                                    User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                                    $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                                    $data['data']['trans_amount'] = $totalAmt;
                                    $data['data']['receiver_name'] = $recieverUser->name;
                                    $data['data']['receiver_phone'] = $recieverUser->phone;
                                    $data['data']['trans_id'] = $TransId;
                                    $data['data']['trans_date'] = date('d, M Y, h:i A');

                                    $title = __("message.debit_title", ['cost' => CURR . " " . $totalAmt]);
                                    $message = __("message.debit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
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

                                    $title = __("message.credit_title", ['cost' => CURR . " " . $request->amount]);
                                    $message = __("message.credit_message", ['cost' => CURR . " " . $request->amount, 'username' => $senderUser->name]);
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

                                    $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                                    $json = array_merge($statusArr, $data);
//                                    return response()->json($json, 200);
                                    $json = json_encode($json);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                } else {
                                    $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                    return response()->json($statusArr, 200);
                                    $json = json_encode($statusArr);
                                    $responseData = $this->encryptContent($json);
                                    return response()->json($responseData, 200);
                                }
                            } else {
                                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                                return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }
                        }
                    }
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
//            } catch (\Exception $ex) {
//                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
//                return response()->json($statusArr, 200);
//            }
        }
    }

    private function sendPushNotification($title, $message, $device_type, $device_token) {

        if ($device_type != 'Web') {
            $push_notification_key = env('PUSH_NOTIFICATION_KEY');
            $url = "https://fcm.googleapis.com/fcm/send";
            $header = array("authorization: key=" . $push_notification_key . "",
                "content-type: application/json"
            );

            if (strtolower($device_type) == "android") {
                $msgArr = array(
                    'message' => $message,
                    'title' => $title,
                    'tickerText' => $title,
                    'msg_data' => $message,
                    'sound' => 1
                );

                $fields = array('to' => $device_token, 'data' => $msgArr);
                $postdata = json_encode($fields);
            } else {
                $postdata = array(
                    "to" => $device_token,
                    "Content-available" => "1",
                    "notification" => array(
                        "title" => $title,
                        "body" => $message,
                        "sound" => "default"
                    ),
                    "data" => array("targetScreen" => "detail"),
                    "priority" => 10
                );

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
    }

    public function getNotification(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->page == "" or!is_numeric($request->page)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Page."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $matchThese = ["notifications.user_id" => $request->user_id];
            $notifications = DB::table('notifications')->select('notifications.*')->where($matchThese)->orderBy("id", "DESC")->paginate(10);
            if (Count($notifications) > 0) {
                $notifArr = array();
                $notifDataArr = array();
                foreach ($notifications as $key => $val) {
                    $notifArr['id'] = $val->id;
                    $notifArr['user_id'] = $val->user_id;
                    $notifArr['title'] = $val->notif_title;
                    $notifArr['body'] = $val->notif_body;
                    $notifArr['is_seen'] = $val->is_seen;
                    $notifArr['date'] = date('d M Y h:i A', strtotime($val->created_at));
                    $notifDataArr[] = $notifArr;
                }
                //echo "Count: ".Count($notifications);
                $total_page = $notifications->lastPage();
                $statusArr = array("status" => "Success", "reason" => __("message.Notification List."), "totalPage" => $total_page);
                $data['data'] = $notifDataArr;
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Success", "reason" => __("message.Sorry no notification found."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function seenNotification(Request $request) {
        $request = $this->decryptContent($request->req);
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->notification_id == "" or!is_numeric($request->notification_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Notification Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            Notification::where('id', $request->notification_id)->update(['is_seen' => 1]);

            $statusArr = array("status" => "Success", "reason" => __("message.Notification Seen Status Updated"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function scanMerchantQR(Request $request) {
        $request = $this->decryptContent($request->req);
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->qr_code == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $qrCodeArr = explode("##", $request->qr_code);
            $qrId = $qrCodeArr[0];
            if (empty($qrId)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if (isset($qrCodeArr[1]) && !empty($qrCodeArr[1])) {
                $qrOrder = $qrCodeArr[1];
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            if (isset($qrCodeArr[2]) && !empty($qrCodeArr[2])) {
                $qrAmt = $qrCodeArr[2];
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $matchThese = ["users.id" => $qrId];
            $user = DB::table('users')->where($matchThese)->first();
            if ($user) {
                $statusArr = array("status" => "Success", "reason" => __("message.Merchant detail."));
                $userData['id'] = $user->id;
                $userData['name'] = $user->business_name;
                $userData['phone'] = $user->phone;
                $userData['order_id'] = $qrOrder;
                $userData['amount'] = $qrAmt;
                $data['data'] = $userData;
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 201);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 201);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        }
    }

    public function shoppingPayment(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Merchant ID."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->order_id == "") {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Order ID."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {

            $transactionFee = $request->trans_fee;

            $amount = $request->amount;
            $user_id = $request->user_id;

            if (!isset($transactionFee)) {
                $userFee = Usertransactionfee::where('user_id', $user_id)->where('transaction_type', 'Online Shopping')->where('status', 1)->first();
                if (empty($userFee)) {
                    $fees = Transactionfee::where('transaction_type', 'Online Shopping')->where('status', 1)->first();

                    if (!empty($fees)) {
                        $transFee = (($amount * $fees->user_charge) / 100);
                    }
                } else {
                    if (!empty($userFee)) {
                        $transFee = (($amount * $userFee->user_charge) / 100);
                    }
                }

                $transactionFee = $transFee;
            }


//            try {         
//            $matchThese = ["users.phone" => $request->phone, "users.is_verify" => 1, "users.is_kyc_done" => 1];
            $matchThese = ["users.phone" => $request->phone];
            $recieverUser = DB::table('users')->where($matchThese)->first();
            if (!empty($recieverUser)) {

                if ($recieverUser->id == $request->user_id) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.You can not send fund for own account."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

//                $matchThese = ["users.id" => $request->user_id, "users.is_verify" => 1, "users.is_kyc_done" => 1];
                $matchThese = ["users.id" => $request->user_id];
                $senderUser = DB::table('users')->where($matchThese)->first();

//                if (empty($senderUser)) {
//                    $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//                    return response()->json($statusArr, 200);
//                }

                $userActiveAmount = $senderUser->wallet_balance;
                $totalAmt = $request->amount + $transactionFee;

                if ($userActiveAmount >= $totalAmt) {
                    $trans_id = time();
                    $refrence_id = $request->order_id;
                    $trans = new Transaction([
                        'user_id' => $request->user_id,
                        'receiver_id' => $recieverUser->id,
                        'amount' => $request->amount,
                        'amount_value' => $request->amount,
                        'transaction_amount' => $transactionFee,
                        'total_amount' => $totalAmt,
                        'trans_type' => 2,
                        'trans_to' => 'Online Shopping',
                        'payment_mode' => 'Online Shopping',
                        'refrence_id' => $trans_id,
                        'billing_description' => $refrence_id,
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();
                    $TransId = $trans->id;

                    Order::where('order_id', $request->order_id)->update(array('status' => 1));

                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                    User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                    $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                    User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                    $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                    $data['data']['trans_amount'] = $totalAmt;
                    $data['data']['receiver_name'] = $recieverUser->name;
                    $data['data']['receiver_phone'] = $recieverUser->phone;
                    $data['data']['trans_id'] = $TransId;
                    $data['data']['trans_date'] = date('d, M Y, h:i A');

                    $title = __("message.Online Shopping");
//                        $message = __("message.Congratulations! You've received " . CURR . " " . $request->amount . " from " . $senderUser->name . " for online shopping.");
                    $message = __("message.receive_shopping_payment", ['cost' => CURR . " " . $request->amount, 'username' => $senderUser->name]);
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

                    $title = __("message.Online Shopping");
//                        $message = __("message.Congratulations! You've paid " . CURR . " " . $totalAmt . " successfully to " . $recieverUser->name . " for online shopping.");
                    $message = __("message.send_shopping_payment", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
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

                    $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Paid Successfully"));
                    $json = array_merge($statusArr, $data);
//                    return response()->json($json, 200);
                    $json = json_encode($json);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
//            } catch (\Exception $ex) {
//                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
//                return response()->json($statusArr, 200);
//            }
        }
    }

    public function requestList(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $user_id = $request->user_id;
        $request_type = $request->request_type;

        $lang = $request->device_language;
        App::setLocale($lang);

        $userInfo = User::where('id', $user_id)->first();
//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
//        if (empty($userInfo)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }
        if ($request_type == 'Deposit') {
            $request_type = 'Agent Deposit';
        }
        $requests = Transaction::where('trans_type', 4)->where('status', 2)->where('receiver_id', $user_id)->where('payment_mode', $request_type)->orderBy('id', 'desc')->get();

        $records = array();
        if ($requests) {
            foreach ($requests as $request) {
                $userData = array();
                $userData['request_id'] = $request->id;
                $userData['user_id'] = $request->user_id;
                $userData['name'] = $request->User->name;
                $userData['phone'] = $request->User->phone;
                $userData['amount'] = $this->numberFormatPrecision($request->amount, 2, '.');
                if ($request->User->profile_image != '') {
                    $userData['user_image'] = PROFILE_FULL_DISPLAY_PATH . $request->User->profile_image;
                } else {
                    $userData['user_image'] = HTTP_PATH . '/public/img/' . 'no_user.png';
                }
                $records[] = $userData;
            }

            $statusArr = array("status" => "Success", "reason" => __("message.Request List"));
            $data['data'] = $records;
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Request not available."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function cancelAcceptRequest(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $user_id = $request->user_id;
        $request_id = $request->request_id;
        $request_type = $request->request_type;

        $lang = $request->device_language;
        App::setLocale($lang);

        $userInfo = User::where('id', $user_id)->first();
//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
//        if (empty($userInfo)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }

        if ($user_id == "" or!is_numeric($user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request_id == "" or!is_numeric($request_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Request id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $requestDetail = Transaction::where('id', $request_id)->where('receiver_id', $user_id)->where('trans_type', 4)->first();
        if (!empty($requestDetail)) {
            if ($requestDetail->payment_mode == 'Agent Deposit') {
                $type = 1;
            } else {
                $type = 2;
            }

            if ($request_type == 'Accept') {
                if ($type == 1) {
                    if ($userInfo->wallet_balance >= $requestDetail->amount) {
                        Transaction::where('id', $request_id)->update(array('status' => 1, 'trans_type' => $type));

                        $userFee = Usertransactionfee::where('user_id', $requestDetail->user_id)->where('transaction_type', 'Deposit')->where('status', 1)->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where('transaction_type', 'Deposit')->where('status', 1)->first();

                            if (!empty($fees)) {
                                $transFee = $this->numberFormatPrecision((($requestDetail->amount * $fees->user_charge) / 100), 2);
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = $this->numberFormatPrecision((($requestDetail->amount * $userFee->user_charge) / 100), 2);
                            }
                        }
                        $transactionFee = $transFee;
                        $ttlAmt = $requestDetail->amount - $transactionFee;

                        $receiverInfo = User::where('id', $requestDetail->user_id)->first();
                        $wallet_balance = $receiverInfo->wallet_balance + ($ttlAmt);
                        User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                        $wallet_balance = $userInfo->wallet_balance - $requestDetail->amount;
                        User::where('id', $userInfo->id)->update(array('wallet_balance' => $wallet_balance));

                        Transaction::where('id', $request_id)->update(array('amount' => $ttlAmt, 'amount_value' => $ttlAmt, 'transaction_amount' => $transactionFee));

                        $title = __("message.Congratulations!");
//                        $message = __("message.Congratulations! Your request successfully accepted for deposit for amount " . CURR . ' ' . $requestDetail->amount);
                        $message = __("message.accept_deposit_request", ['cost' => CURR . " " . $requestDetail->amount]);
                        $device_type = $receiverInfo->device_type;
                        $device_token = $receiverInfo->device_token;

                        $this->sendPushNotification($title, $message, $device_type, $device_token);

                        $notif = new Notification([
                            'user_id' => $receiverInfo->id,
                            'notif_title' => $title,
                            'notif_body' => $message,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $notif->save();
                    } else {
                        $statusArr = array("status" => "Failed", "reason" => __("message.You have insufficient balance to accept request."));
//                        return response()->json($statusArr, 200);
                        $json = json_encode($statusArr);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    }
                } else {
                    Transaction::where('id', $request_id)->update(array('status' => 1, 'trans_type' => $type));

                    $receiverInfo = User::where('id', $requestDetail->user_id)->first();

                    $userInfo = User::where('id', $user_id)->first();
                    $wallet_balance = $userInfo->wallet_balance + $requestDetail->amount;
                    User::where('id', $userInfo->id)->update(array('wallet_balance' => $wallet_balance));

                    $title = __("message.Congratulations!");
//                    $message = __("message.Congratulations! Your request successfully accepted for withdraw of amount " . CURR . ' ' . $requestDetail->amount);
                    $message = __("message.accept_withdraw_request", ['cost' => CURR . " " . $requestDetail->amount]);
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $receiverInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                }
                $statusArr = array("status" => "Success", "reason" => __("message.Request Accepted Successfully"));
            } else {
                if ($type != 1) {
                    $senderInfo = User::where('id', $requestDetail->user_id)->first();
                    $receiverInfo = User::where('id', $requestDetail->receiver_id)->first();
                    $wallet_balance = $senderInfo->wallet_balance + $requestDetail->total_amount;
                    User::where('id', $senderInfo->id)->update(array('wallet_balance' => $wallet_balance));

                    $trans_id = time();
                    $refrence_id = 'Trans-' . $request_id;
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => $senderInfo->id,
                        'amount' => $requestDetail->total_amount,
                        'amount_value' => $requestDetail->total_amount,
                        'transaction_amount' => $requestDetail->transaction_amount,
                        'total_amount' => $requestDetail->total_amount,
                        'trans_type' => 1,
                        'payment_mode' => 'Refund',
                        'status' => 1,
                        'refrence_id' => $trans_id,
                        'billing_description' => $refrence_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();

                    $title = __("message.Congratulations!");
//                    $message = __("message.Your withdrawal request for " . CURR . ' ' . $requestDetail->amount . ' has been rejected by agent ' . $userInfo->name);
                    $message = __("message.reject_withdraw_request", ['cost' => CURR . " " . $requestDetail->amount, 'username' => $userInfo->name]);
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $senderInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                } else {
                    $receiverInfo = User::where('id', $requestDetail->user_id)->first();

                    $title = __("message.Congratulations!");
//                    $message = __("message.Your deposit request for " . CURR . ' ' . $requestDetail->amount . ' has been rejected by agent ' . $userInfo->name);
                    $message = __("message.reject_deposit_request", ['cost' => CURR . " " . $requestDetail->amount, 'username' => $userInfo->name]);
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

                    $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $receiverInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                }
                Transaction::where('id', $request_id)->update(array('status' => 4));
                $statusArr = array("status" => "Success", "reason" => __("message.Request Rejected Successfully"));
            }

//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => 'Invalid request id.');
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function cashCardList(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $user_id = $request->user_id;

        $lang = $request->device_language;
        App::setLocale($lang);

        $userDetail = User::where('id', $user_id)->first();
        $agentOffer = Agentoffer::where('user_id', $user_id)->where('type', 'Cash Card')->where('status', 1)->first();
        $offer = Offer::where('type', 'Cash Card')->where('status', 1)->first();

        $data = array();
        $carddetails = Scratchcard::where('expiry_date', '>=', date('Y-m-d'))->where('status', 1)->where('used_status', 0)->where('purchase_by_id', NULL)->groupBy('scratchcards.real_value')->get();

        if (!empty($carddetails)) {
            foreach ($carddetails as $card) {
                $cardData['card_id'] = $card->id;
                $cardData['currency'] = CURR;
                $cardData['real_value'] = $this->numberFormatPrecision($card->real_value, 2, '.');
                $cardData['card_value'] = $this->numberFormatPrecision($card->card_value, 2, '.');

                if ($userDetail->user_type == 'Agent') {
                    if (!empty($agentOffer)) {
                        $cardData['card_value'] = $this->numberFormatPrecision(($card->card_value - (($card->card_value * $agentOffer->offer) / 100)), 2);
                    } elseif (!empty($offer)) {
                        $cardData['card_value'] = $this->numberFormatPrecision(($card->card_value - (($card->card_value * $offer->offer) / 100)), 2);
                    }
                }

                $data['data'][] = $cardData;
            }

            $statusArr = array("status" => "Success", "reason" => __("message.Cash Card List"));
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Cash card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function buyCashCard(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $card_id = $request->card_id;
        $user_id = $request->user_id;
        $card_value = str_replace(",", "", $request->card_value);

        $lang = $request->device_language;
        App::setLocale($lang);

        $data = array();
        $carddetail = Scratchcard::where('id', $card_id)->first();

//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where('id', $user_id)->first();
//        if (empty($userInfo)) {
//            $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//            return response()->json($statusArr, 200);
//        }

        if (!empty($carddetail)) {

            $cardNumber = $carddetail->card_number;

            if ($userInfo->wallet_balance >= $card_value) {
                $transactionFee = $carddetail->real_value - $card_value;
                $transactionFee = 0;
                $trans_id = time();
                $refrence_id = time() . rand() . '-' . $card_id;
                $trans = new Transaction([
                    'user_id' => $user_id,
                    'receiver_id' => 0,
                    'amount' => $card_value,
                    'amount_value' => $card_value,
                    'real_value' => $carddetail->real_value,
                    'transaction_amount' => 0,
                    'total_amount' => $card_value,
                    'trans_type' => 2,
                    'trans_to' => 'Wallet',
                    'trans_for' => 'Cash Card',
                    'payment_mode' => 'Cash Card',
                    'refrence_id' => $trans_id,
                    'billing_description' => $refrence_id,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $trans->save();
                $TransId = $trans->id;

                $sender_wallet_amount = $userInfo->wallet_balance - $card_value;
                User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

//                Scratchcard::where('card_number', $cardNumber)->update(array('used_status' => 1));
                Scratchcard::where('card_number', $cardNumber)->update(array('purchase_by_id' => $user_id));

                $title = __("message.Buy Cash Card");
//                $message = __("message.Successful purchase of cash card equivalent to " . CURR . " " . $carddetail->real_value . " from System." . CURR . " " . $carddetail->real_value . " Card Number " . $carddetail->card_number);
                $message = __("message.buy_cash_card", ['cost' => CURR . " " . $carddetail->real_value, 'card_number' => $carddetail->card_number]);
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

                $result['card_number'] = $carddetail->card_number;

                $data['data'] = $result;
                $statusArr = array("status" => "Success", "reason" => __("message.Transaction Completed"));
                $json = array_merge($statusArr, $data);
//                return response()->json($json, 200);
                $json = json_encode($json);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient balance available in the wallet."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Cash card not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function merchantTransactions(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->page == "" or!is_numeric($request->page)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Page."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            global $tranType;
//            try {
            $userInfo = User::where('id', $request->user_id)->first();
            if (!empty($userInfo)) {

                if ($userInfo->trans_pay_by == 'User') {
                    $payBy = 'Merchant';
                } else {
                    $payBy = 'User';
                }

//                $NewDate = Date('Y-m-d', strtotime('-15 days'));
//                $trans = Transaction::where('created_at', '>=', $NewDate)->where("payment_mode", '!=', 'Refund')->where("payment_mode", '!=', 'Withdraw')->where("trans_type", 2)->where("refund_status", 0)->where("receiver_id", $request->user_id)->orderBy("id", "DESC")->paginate(10);
                $trans = Transaction::where("payment_mode", '!=', 'Refund')->where("payment_mode", '!=', 'Withdraw')->where('payment_mode', '!=', 'Send Money')->where("trans_type", 2)->where("refund_status", 0)->where("receiver_id", $request->user_id)->orderBy("id", "DESC")->paginate(10);

                if (!empty($trans)) {
                    $transArr = array();
                    $transDataArr = array();

                    foreach ($trans as $key => $request) {
                        $userData = array();
                        $userData['request_id'] = $request->id;
                        $userData['user_id'] = $request->user_id;
                        $userData['name'] = $request->User->name;
                        $userData['phone'] = $request->User->phone;
                        // $userData['pay_by'] = $payBy;
                        $userData['amount'] = $this->numberFormatPrecision($request->amount, 2, '.');

                        // $receiverInfo = User::where('id', $request->receiver_id)->first();

                        $amount = $request->amount;
                        if ($userInfo->trans_pay_by != 'User') {
                            $receiverInfo = User::where('id', $request->user_id)->first();
//                            $transactionFee = $this->getRefundFee($userInfo->id, $request->amount);

                            $userFee = Usertransactionfee::where('user_id', $userInfo->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                            if (empty($userFee)) {
                                $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                                if (!empty($fees)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
                                }
                            } else {
                                if (!empty($userFee)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                                }
                            }
                            $transactionFee = $transFee;
                            $totalAmt = $request->amount + $transactionFee;

                            $userData['transaction_msg'] = __("message.merchant_refund", ['cost' => CURR . " " . $request->amount, 'username' => $receiverInfo->name, 'recieverName' => $receiverInfo->name, 'fee' => CURR . ' ' . number_format($transactionFee, 2), 'paidAmt' => CURR . " " . $totalAmt]);
                        } else {
                            $receiverInfo = User::where('id', $request->user_id)->first();
//                            $transactionFee = $this->getRefundFee($receiverInfo->id, $request->amount);

                            $userFee = Usertransactionfee::where('user_id', $receiverInfo->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                            if (empty($userFee)) {
                                $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                                if (!empty($fees)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
                                }
                            } else {
                                if (!empty($userFee)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                                }
                            }
                            $transactionFee = $transFee;
                            $totalAmt = $request->amount - $transactionFee;

                            $userData['transaction_msg'] = __("message.user_refund", ['cost' => CURR . " " . $request->amount, 'username' => $receiverInfo->name, 'recieverName' => $receiverInfo->name, 'fee' => CURR . ' ' . number_format($transactionFee, 2), 'paidAmt' => CURR . " " . $totalAmt]);
                        }




                        // $userData['transaction_fee'] = $this->numberFormatPrecision($transactionFee, 2, '.');
                        // $userData['total_amount'] = $this->numberFormatPrecision($totalAmt, 2, '.');
                        if ($request->User->profile_image != '') {
                            $userData['user_image'] = PROFILE_FULL_DISPLAY_PATH . $request->User->profile_image;
                        } else {
                            $userData['user_image'] = HTTP_PATH . '/public/img/' . 'no_user.png';
                        }
                        $transDataArr[] = $userData;
                    }

                    $total_page = $trans->lastPage();
                    $statusArr = array("status" => "Success", "reason" => __("message.Transaction List."), "totalPage" => $total_page);
                    $data['data'] = $transDataArr;
                    $json = array_merge($statusArr, $data);
//                    return response()->json($json, 200);
                    $json = json_encode($json);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                } else {
                    $statusArr = array("status" => "Failed", "reason" => __("message.Sorry no transaction found."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
//            } catch (\Exception $ex) {
//                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
//                return response()->json($statusArr, 200);
//            }
        }
    }

    public function refundPayment(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $user_id = $request->user_id;
        $transaction_id = $request->transaction_id;

        $lang = $request->device_language;
        App::setLocale($lang);

//        $userInfo = User::where('id', $user_id)->where("is_verify", 1)->where("is_kyc_done", 1)->first();
        $userInfo = User::where('id', $user_id)->first();

        if ($user_id == "" or!is_numeric($user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($transaction_id == "" or!is_numeric($transaction_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Transaction id"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $requestDetail = Transaction::where('id', $transaction_id)->first();
        if (!empty($requestDetail)) {

            if ($userInfo->trans_pay_by != 'User') {
                $amount = $requestDetail->amount;
                $userFee = Usertransactionfee::where('user_id', $requestDetail->receiver_id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                if (empty($userFee)) {
                    $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                    if (!empty($fees)) {
                        $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
                    }
                } else {
                    if (!empty($userFee)) {
                        $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                    }
                }

                $transactionFee = $transFee;
                $totalAmt = $requestDetail->amount + $transactionFee;

                $senderInfo = User::where('id', $requestDetail->user_id)->first();
                $receiverInfo = User::where('id', $requestDetail->receiver_id)->first();

                if ($totalAmt > $receiverInfo->wallet_balance) {

                    $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                
                $wallet_balance = $receiverInfo->wallet_balance - $totalAmt;
                User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                $sender_wallet_balance = $senderInfo->wallet_balance + $requestDetail->amount;
                User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                $trans_id = time();
                $refrence_id = 'Trans-' . $transaction_id;
                $trans = new Transaction([
                    'user_id' => $senderInfo->id,
                    'receiver_id' => $receiverInfo->id,
                    'amount' => $requestDetail->amount,
                    'amount_value' => $requestDetail->amount,
                    'transaction_amount' => $transactionFee,
                    'total_amount' => $totalAmt,
                    'trans_type' => 1,
                    'payment_mode' => 'Refund',
                    'fee_pay_by' => 'Merchant',
                    'status' => 1,
                    'refund_status' => 1,
                    'refrence_id' => $trans_id,
                    'billing_description' => $refrence_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $trans->save();
                Transaction::where('id', $transaction_id)->update(array('refund_status' => 1));

                $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! Refund send successfully to user of " . CURR . " " . $requestDetail->amount);
                $message = __("message.refund_fund", ['cost' => CURR . " " . $totalAmt]);
                $device_type = $receiverInfo->device_type;
                $device_token = $receiverInfo->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $receiverInfo->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! You have received refund of " . CURR . " " . $totalAmt . " from merchant");
                $message = __("message.refund_receive", ['cost' => CURR . " " . $requestDetail->amount]);
                $device_type = $senderInfo->device_type;
                $device_token = $senderInfo->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $senderInfo->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();
            } else {

                $receiverInfo = User::where('id', $requestDetail->receiver_id)->first();
                // $transactionFee = $this->getRefundFee($userInfo->id, $requestDetail->amount);
                $amount = $requestDetail->amount;
                $userFee = Usertransactionfee::where('user_id', $requestDetail->user_id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                if (empty($userFee)) {
                    $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                    if (!empty($fees)) {
                        $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
                    }
                } else {
                    if (!empty($userFee)) {
                        $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                    }
                }
                $transactionFee = $transFee;
                $totalAmt = $requestDetail->amount - $transactionFee;

                $senderInfo = User::where('id', $requestDetail->user_id)->first();
                if ($requestDetail->amount > $receiverInfo->wallet_balance) {

                    $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

                $wallet_balance = $receiverInfo->wallet_balance - $requestDetail->amount;
                User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                $sender_wallet_balance = $senderInfo->wallet_balance + $totalAmt;
                User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                $trans_id = time();
                $refrence_id = 'Trans-' . $transaction_id;
                $trans = new Transaction([
                    'user_id' => $senderInfo->id,
                    'receiver_id' => $receiverInfo->id,
                    'amount' => $totalAmt,
                    'amount_value' => $totalAmt,
                    'transaction_amount' => $transactionFee,
                    'total_amount' => $requestDetail->amount,
                    'trans_type' => 1,
                    'payment_mode' => 'Refund',
                    'status' => 1,
                    'refund_status' => 1,
                    'refrence_id' => $trans_id,
                    'billing_description' => $refrence_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $trans->save();
                Transaction::where('id', $transaction_id)->update(array('refund_status' => 1));

                $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! Refund send successfully to user of " . CURR . " " . $requestDetail->amount);
                $message = __("message.refund_fund", ['cost' => CURR . " " . $requestDetail->amount]);
                $device_type = $receiverInfo->device_type;
                $device_token = $receiverInfo->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $receiverInfo->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! You have received refund of " . CURR . " " . $totalAmt . " from merchant");
                $message = __("message.refund_receive", ['cost' => CURR . " " . $totalAmt]);
                $device_type = $senderInfo->device_type;
                $device_token = $senderInfo->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $senderInfo->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();
            }





            $statusArr = array("status" => "Success", "reason" => __("message.Amount Refunded Successfully"));

//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid request id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    function staticPage(Request $request) {
        $request = $this->decryptContent($request->req);
        $pagename = $request->page_name;
        $user_type = $request->user_type;

        $lang = $request->device_language;
        App::setLocale($lang);

        if ($pagename == 'help') {
            $url = 'help';
        } elseif ($pagename == 'about') {
            $url = 'about-us';
        } elseif ($pagename == 'privacy') {
            $url = 'privacy-policy';
        } elseif ($pagename == 'faq') {
            $url = 'faq';
        } elseif ($pagename == 'terms') {
            $url = 'terms-and-condition';
        }

        $pageInfo = DB::table('pages')->where('slug', $url)->first();

        $statusArr = array("status" => "Success", 'content' => $pageInfo->description, "reason" => "Page Detail");
//        return response()->json($statusArr, 200);
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function feedback(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $user_id = $request->user_id;
        $email = $request->email;
        $subject = $request->subject;
        $message = $request->message;

        $lang = $request->device_language;
        App::setLocale($lang);

        $data['user_id'] = $user_id;
        $data['email'] = $email;
        $data['subject'] = $subject;
        $data['message'] = $message;

        $serialisedData = $this->serialiseFormData($data); //send 1 for edit

        Contact::insert($serialisedData);

        $userInfo = User::where('id', $user_id)->first();

        $title = __("message.Congratulations!");
        $message = __("message.Congratulations! Your feedback has sent successfully.");
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

        $statusArr = array("status" => "Success", "reason" => __("message.Feedback sent successfully."));
//        return response()->json($statusArr, 200);
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function sendRefund(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Phone Number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
//            try {
//            $matchThese = ["users.phone" => $request->phone, "users.is_verify" => 1, "users.is_kyc_done" => 1];
            $matchThese = ["users.phone" => $request->phone];
            $userInfo = $recieverUser = DB::table('users')->where($matchThese)->first();

            if (!empty($recieverUser)) {

                if ($recieverUser->id == $request->user_id) {
                    $statusArr = array("status" => "Failed", "reason" => __("message.You can not send refund for own account."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
                    $responseData = $this->encryptContent($json);
                    return response()->json($responseData, 200);
                }

//                $matchThese = ["users.id" => $request->user_id, "users.is_verify" => 1, "users.is_kyc_done" => 1];
                $matchThese = ["users.id" => $request->user_id];
                $userDetail = $senderUser = DB::table('users')->where($matchThese)->first();

//                if (empty($senderUser)) {
//                    $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//                    return response()->json($statusArr, 200);
//                }


                $senderUserType = $senderUser->user_type;
                $receiverUserType = $recieverUser->user_type;

                $user_id = $request->user_id;
        $phone = substr($request->phone, -10);
                $amount = $request->amount;

                if ($senderUserType == 'Merchant') {
                    if ($receiverUserType == 'Merchant') {
                        $transactionFee = $request->trans_fee;

                        $totalAmt = $this->numberFormatPrecision(($amount + $transactionFee), 2);

                        if ($totalAmt > $senderUser->wallet_balance) {

                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }

                        $trans_id = time();
                        $refrence_id = time() . rand() . $request->user_id;
                        $trans = new Transaction([
                            'user_id' => $request->user_id,
                            'receiver_id' => $recieverUser->id,
                            'amount' => $request->amount,
                            'amount_value' => $request->amount,
                            'transaction_amount' => $transactionFee,
                            'total_amount' => $totalAmt,
                            'trans_type' => 2,
                            'trans_to' => 'Wallet',
                            'payment_mode' => 'Send Money',
                            'refrence_id' => $trans_id,
                            'billing_description' => $refrence_id,
                            'status' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save();
                        $TransId = $trans->id;

                        $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
                        User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                        $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
                        User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
                        $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
                        $data['data']['trans_amount'] = $totalAmt;
                        $data['data']['receiver_name'] = $recieverUser->name;
                        $data['data']['receiver_phone'] = $recieverUser->phone;
                        $data['data']['trans_id'] = $TransId;
                        $data['data']['trans_date'] = date('d, M Y, h:i A');

                        $title = __("message.debit_title", ['cost' => CURR . " " . $totalAmt]);
                        $message = __("message.debit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
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

                        $title = __("message.credit_title", ['cost' => CURR . " " . $request->amount]);
                        $message = __("message.credit_message", ['cost' => CURR . " " . $request->amount, 'username' => $senderUser->name]);
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

                        $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Sent Successfully"));
                        $json = array_merge($statusArr, $data);
//                        return response()->json($json, 200);
                        $json = json_encode($json);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);
                    } elseif ($receiverUserType == 'Agent') {
                        $paymentType = 'Withdraw';
                        $payerId = $senderUser->id;

                        $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', $paymentType)->where('status', 1)->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where('transaction_type', $paymentType)->where('status', 1)->first();

                            if (!empty($fees)) {
                                $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                            }
                        }
                        $transactionFee = $transFee;
                        $totalAmt = $this->numberFormatPrecision(($amount + $transFee), 2);

                        $userActiveAmount = $senderUser->wallet_balance;

                        if ($userActiveAmount >= $totalAmt) {

                            $trans_id = time();
                    $refrence_id = time() . '-' . $userInfo->id;
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => $userInfo->id,
                        'amount' => $amount,
                        'amount_value' => $amount,
                        'transaction_amount' => $transactionFee,
                        'total_amount' => $totalAmt,
                        'trans_type' => 4,
                        'payment_mode' => 'Withdraw',
                        'status' => 2,
                        'refrence_id' => $trans_id,
                        'billing_description' => 'Withdraw-' . $refrence_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();

                    $user_wallet_amount = $userDetail->wallet_balance - $totalAmt;
                    User::where('id', $user_id)->update(['wallet_balance' => $user_wallet_amount]);

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

                            $statusArr = array("status" => "Success", "reason" => __("message.Withdraw Request Sent Successfully To Agent."));
//                    return response()->json($statusArr, 200);
                    $json = json_encode($statusArr);
//                            return response()->json($json, 200);
//                            $json = json_encode($json);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        } else {
                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
                            $json = json_encode($statusArr);
                            $responseData = $this->encryptContent($json);
                            return response()->json($responseData, 200);
                        }
                    } else {
                        $paymentType = 'Refund';

                        if ($senderUser->trans_pay_by != 'User') {

                            $userFee = Usertransactionfee::where('user_id', $senderUser->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                            if (empty($userFee)) {
                                $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                                if (!empty($fees)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
                                }
                            } else {
                                if (!empty($userFee)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                                }
                            }

                            $transactionFee = $transFee;
                            $totalAmt = $amount + $transactionFee;

                            if ($totalAmt > $senderUser->wallet_balance) {

                                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
                                return response()->json($statusArr, 200);
                            }
                            $receiverInfo = $senderUser;
                            $wallet_balance = $receiverInfo->wallet_balance - $totalAmt;
                            User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                            $senderInfo = $recieverUser;
                            $sender_wallet_balance = $senderInfo->wallet_balance + $amount;
                            User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                            $trans_id = time();
                            $refrence_id = time() . rand();
                            $trans = new Transaction([
                                'user_id' => $senderInfo->id,
                                'receiver_id' => $receiverInfo->id,
                                'amount' => $amount,
                                'amount_value' => $amount,
                                'transaction_amount' => $transactionFee,
                                'total_amount' => $totalAmt,
                                'trans_type' => 1,
                                'payment_mode' => 'Refund',
                                'fee_pay_by' => 'Merchant',
                                'status' => 1,
                                'refund_status' => 1,
                                'refrence_id' => $trans_id,
                                'billing_description' => $refrence_id,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $trans->save();

                            $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_balance, 2, '.');
                            $data['data']['trans_amount'] = $request->amount;
                            $data['data']['receiver_name'] = $recieverUser->name;
                            $data['data']['receiver_phone'] = $recieverUser->phone;
                            $data['data']['trans_id'] = $trans_id;
                            $data['data']['trans_date'] = date('d, M Y, h:i A');

                            $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! Refund send successfully to user of " . CURR . " " . $requestDetail->amount);
                            $message = __("message.refund_fund", ['cost' => CURR . " " . $amount]);
                            $device_type = $receiverInfo->device_type;
                            $device_token = $receiverInfo->device_token;

                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $receiverInfo->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();

                            $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! You have received refund of " . CURR . " " . $totalAmt . " from merchant");
                            $message = __("message.refund_receive", ['cost' => CURR . " " . $amount]);
                            $device_type = $senderInfo->device_type;
                            $device_token = $senderInfo->device_token;

                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $senderInfo->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();
                        } else {

                            $receiverInfo = $senderUser;
                            // $transactionFee = $this->getRefundFee($receiverInfo->id, $requestDetail->amount);

                            $userFee = Usertransactionfee::where('user_id', $recieverUser->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                            if (empty($userFee)) {
                                $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                                if (!empty($fees)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
                                }
                            } else {
                                if (!empty($userFee)) {
                                    $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                                }
                            }
                            $transactionFee = $transFee;
                            $totalAmt = $amount - $transactionFee;

                            if ($amount > $senderUser->wallet_balance) {

                                $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                                return response()->json($statusArr, 200);
                                $json = json_encode($statusArr);
                                $responseData = $this->encryptContent($json);
                                return response()->json($responseData, 200);
                            }


                            $wallet_balance = $receiverInfo->wallet_balance - $amount;
                            User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                            $senderInfo = $recieverUser;
                            $sender_wallet_balance = $senderInfo->wallet_balance + $totalAmt;
                            User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                            $trans_id = time();
                            $refrence_id = time() . rand();
                            $trans = new Transaction([
                                'user_id' => $senderInfo->id,
                                'receiver_id' => $receiverInfo->id,
                                'amount' => $totalAmt,
                                'amount_value' => $totalAmt,
                                'transaction_amount' => $transactionFee,
                                'total_amount' => $amount,
                                'trans_type' => 1,
                                'payment_mode' => 'Refund',
                                'status' => 1,
                                'refund_status' => 1,
                                'refrence_id' => $trans_id,
                                'billing_description' => $refrence_id,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $trans->save();

                            $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_balance, 2, '.');
                            $data['data']['trans_amount'] = $request->amount;
                            $data['data']['receiver_name'] = $recieverUser->name;
                            $data['data']['receiver_phone'] = $recieverUser->phone;
                            $data['data']['trans_id'] = $trans_id;
                            $data['data']['trans_date'] = date('d, M Y, h:i A');

                            $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! Refund send successfully to user of " . CURR . " " . $requestDetail->amount);
                            $message = __("message.refund_fund", ['cost' => CURR . " " . $amount]);
                            $device_type = $receiverInfo->device_type;
                            $device_token = $receiverInfo->device_token;

                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $receiverInfo->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();

                            $title = __("message.Congratulations!");
//            $message = __("message.Congratulations! You have received refund of " . CURR . " " . $totalAmt . " from merchant");
                            $message = __("message.refund_receive", ['cost' => CURR . " " . $totalAmt]);
                            $device_type = $senderInfo->device_type;
                            $device_token = $senderInfo->device_token;

                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $senderInfo->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();
                        }

                        $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Amount Refunded Successfully"));
                        $json = array_merge($statusArr, $data);
//                        return response()->json($json, 200);
                        $json = json_encode($json);
                        $responseData = $this->encryptContent($json);
                        return response()->json($responseData, 200);

//                        if($senderUser->trans_pay_by == 'User'){
//                            $payerId = $senderUser->id;
//
//                            $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', $paymentType)->where('status', 1)->first();
//                        if (empty($userFee)) {
//                            $fees = Transactionfee::where('transaction_type', $paymentType)->where('status', 1)->first();
//
//                            if (!empty($fees)) {
//                                $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
//                            }
//                        } else {
//                            if (!empty($userFee)) {
//                                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
//                            }
//                        }
//                        $transactionFee = $transFee;
//                        $totalAmt = $this->numberFormatPrecision($amount - $transactionFee,2);
//
//                        $userActiveAmount = $senderUser->wallet_balance;
//
//                        if ($userActiveAmount >= $totalAmt) {
//                            if (!empty($senderUser)) {
//
//                                $trans_id = time();
//                                $refrence_id = time() . rand() . $request->user_id;
//                                $trans = new Transaction([
//                                    'user_id' => $recieverUser->id,
//                                    'receiver_id' => $request->user_id,
//                                    'amount' => $request->amount,
//                                    'amount_value' => $request->amount,
//                                    'transaction_amount' => $transactionFee,
//                                    'total_amount' => $totalAmt,
//                                    'trans_type' => 1,
//                                    'refund_status' => 1,
//                                    'trans_to' => 'Wallet',
//                                    'payment_mode' => 'Refund',
//                                    'refrence_id' => $trans_id,
//                                    'billing_description' => $refrence_id,
//                                    'status' => 1,
//                                    'created_at' => date('Y-m-d H:i:s'),
//                                    'updated_at' => date('Y-m-d H:i:s'),
//                                ]);
//                                $trans->save();
//                                $TransId = $trans->id;
//
//                                $sender_wallet_amount = $senderUser->wallet_balance - $totalAmt;
//                                User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);
//// echo $totalAmt;exit;
//                                $reciever_wallet_amount = $recieverUser->wallet_balance + $request->amount;
//                                User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
//                                $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
//                                $data['data']['trans_amount'] = $request->amount;
//                                $data['data']['receiver_name'] = $recieverUser->name;
//                                $data['data']['receiver_phone'] = $recieverUser->phone;
//                                $data['data']['trans_id'] = $TransId;
//                                $data['data']['trans_date'] = date('d, M Y, h:i A');
//
//                                $title = __("message.debit_title", ['cost' => CURR . " " . $totalAmt]);
//                                $message = __("message.debit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
//                                $device_type = $senderUser->device_type;
//                                $device_token = $senderUser->device_token;
//
//                                $this->sendPushNotification($title, $message, $device_type, $device_token);
//
//                                $notif = new Notification([
//                                    'user_id' => $senderUser->id,
//                                    'notif_title' => $title,
//                                    'notif_body' => $message,
//                                    'created_at' => date('Y-m-d H:i:s'),
//                                    'updated_at' => date('Y-m-d H:i:s'),
//                                ]);
//                                $notif->save();
//
//                                $title = __("message.credit_title", ['cost' => CURR . " " . $request->amount]);
//                                $message = __("message.credit_message", ['cost' => CURR . " " . $request->amount, 'username' => $senderUser->name]);
//                                $device_type = $recieverUser->device_type;
//                                $device_token = $recieverUser->device_token;
//
//                                $this->sendPushNotification($title, $message, $device_type, $device_token);
//
//                                $notif = new Notification([
//                                    'user_id' => $recieverUser->id,
//                                    'notif_title' => $title,
//                                    'notif_body' => $message,
//                                    'created_at' => date('Y-m-d H:i:s'),
//                                    'updated_at' => date('Y-m-d H:i:s'),
//                                ]);
//                                $notif->save();
//
//                                $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Amount Refunded Successfully"));
//                                $json = array_merge($statusArr, $data);
//                                return response()->json($json, 200);
//                            } else {
//                                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                return response()->json($statusArr, 200);
//                            }
//                        } else {
//                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
//                        }
//                        } else{
//                            $payerId = $recieverUser->id;
//
//                            $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', $paymentType)->where('status', 1)->first();
//                        if (empty($userFee)) {
//                            $fees = Transactionfee::where('transaction_type', $paymentType)->where('status', 1)->first();
//
//                            if (!empty($fees)) {
//                                $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
//                            }
//                        } else {
//                            if (!empty($userFee)) {
//                                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
//                            }
//                        }
//                        $transactionFee = $transFee;
//                        $totalAmt = $this->numberFormatPrecision($amount - $transactionFee,2);
//
//                        $userActiveAmount = $senderUser->wallet_balance;
//
//                        if ($userActiveAmount >= $request->amount) {
//                            if (!empty($senderUser)) {
//
//                                $trans_id = time();
//                                $refrence_id = time() . rand() . $request->user_id;
//                                $trans = new Transaction([
//                                    'user_id' => $recieverUser->id,
//                                    'receiver_id' => $request->user_id,
//                                    'amount' => $totalAmt,
//                                    'amount_value' => $totalAmt,
//                                    'transaction_amount' => $transactionFee,
//                                    'total_amount' => $request->amount,
//                                    'trans_type' => 1,
//                                    'refund_status' => 1,
//                                    'trans_to' => 'Wallet',
//                                    'payment_mode' => 'Refund',
//                                    'refrence_id' => $trans_id,
//                                    'billing_description' => $refrence_id,
//                                    'status' => 1,
//                                    'created_at' => date('Y-m-d H:i:s'),
//                                    'updated_at' => date('Y-m-d H:i:s'),
//                                ]);
//                                $trans->save();
//                                $TransId = $trans->id;
//
//                                $sender_wallet_amount = $senderUser->wallet_balance - $request->amount;
//                                User::where('id', $request->user_id)->update(['wallet_balance' => $sender_wallet_amount]);
//// echo $totalAmt;exit;
//                                $reciever_wallet_amount = $recieverUser->wallet_balance + $totalAmt;
//                                User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);
//                                $data['data']['wallet_amount'] = $this->numberFormatPrecision($sender_wallet_amount, 2, '.');
//                                $data['data']['trans_amount'] = $request->amount;
//                                $data['data']['receiver_name'] = $recieverUser->name;
//                                $data['data']['receiver_phone'] = $recieverUser->phone;
//                                $data['data']['trans_id'] = $TransId;
//                                $data['data']['trans_date'] = date('d, M Y, h:i A');
//
//                                $title = __("message.debit_title", ['cost' => CURR . " " . $request->amount]);
//                                $message = __("message.debit_message", ['cost' => CURR . " " . $request->amount, 'username' => $recieverUser->name]);
//                                $device_type = $senderUser->device_type;
//                                $device_token = $senderUser->device_token;
//
//                                $this->sendPushNotification($title, $message, $device_type, $device_token);
//
//                                $notif = new Notification([
//                                    'user_id' => $senderUser->id,
//                                    'notif_title' => $title,
//                                    'notif_body' => $message,
//                                    'created_at' => date('Y-m-d H:i:s'),
//                                    'updated_at' => date('Y-m-d H:i:s'),
//                                ]);
//                                $notif->save();
//
//                                $title = __("message.credit_title", ['cost' => CURR . " " . $totalAmt]);
//                                $message = __("message.credit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $senderUser->name]);
//                                $device_type = $recieverUser->device_type;
//                                $device_token = $recieverUser->device_token;
//
//                                $this->sendPushNotification($title, $message, $device_type, $device_token);
//
//                                $notif = new Notification([
//                                    'user_id' => $recieverUser->id,
//                                    'notif_title' => $title,
//                                    'notif_body' => $message,
//                                    'created_at' => date('Y-m-d H:i:s'),
//                                    'updated_at' => date('Y-m-d H:i:s'),
//                                ]);
//                                $notif->save();
//
//                                $statusArr = array("status" => "Success", "payment_status" => "Success", "reason" => __("message.Amount Refunded Successfully"));
//                                $json = array_merge($statusArr, $data);
//                                return response()->json($json, 200);
//                            } else {
//                                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                                return response()->json($statusArr, 200);
//                            }
//                        } else {
//                            $statusArr = array("status" => "Failed", "reason" => __("message.Insufficient Balance."));
//                            return response()->json($statusArr, 200);
//                        }
//                        }
                    }
                }
            } else {
                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found."));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }
//            } catch (\Exception $ex) {
//                $statusArr = array("status" => "Failed", "reason" => "Unknown Exception");
//                return response()->json($statusArr, 200);
//            }
        }
    }

    public function checkTransactionFee(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $lang = $request->device_language;
        App::setLocale($lang);

        if ($request->phone == "" or!is_numeric($request->phone)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Phone Number."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->user_id == "" or!is_numeric($request->user_id)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid User Id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else if ($request->amount == "" or!is_numeric($request->amount)) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Invalid QR Code."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $user_id = $request->user_id;
            $amount = $request->amount;
            $requestType = '';
            if (isset($request->type)) {
                $requestType = $request->type;
            }

            $matchThese = ["users.id" => $user_id];
            $senderUser = DB::table('users')->where($matchThese)->first();

            $matchThese = ["users.phone" => $request->phone, "users.is_verify" => 1];
            $recieverUser = DB::table('users')->where($matchThese)->first();

            $matchAdmin = ["admins.id" => 1];
            $adminUser = DB::table('admins')->where($matchAdmin)->first();

            if ($adminUser->amount_limit < $amount) {
                $message = __("message.send_limit", ['cost' => CURR . " " . $adminUser->amount_limit]);
                $statusArr = array("status" => "Failed", "reason" => $message);
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $transFee = 0;
            if (empty($recieverUser)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Receiver not found or not verified"));
//                return response()->json($statusArr, 200);
                $json = json_encode($statusArr);
                $responseData = $this->encryptContent($json);
                return response()->json($responseData, 200);
            }

            $senderUserType = $senderUser->user_type;
            $receiverUserType = $recieverUser->user_type;

            if ($senderUserType == 'Merchant') {
                if ($receiverUserType == 'Merchant') {
                    $paymentType = 'Send Money';
                    $chargeBy = 'Merchant';
                    $payingBy = 'Sender';
                    $payerId = $senderUser->id;
                } elseif ($receiverUserType == 'Agent') {
                    $paymentType = 'Withdraw';
                    $chargeBy = 'Merchant';
                    $payingBy = 'Sender';
                    $payerId = $senderUser->id;
                } else {
                    if ($senderUser->trans_pay_by == 'User') {
                        $paymentType = 'Refund';
                        $chargeBy = 'Merchant';
                        $payingBy = 'Receiver';
                        $payerId = $senderUser->id;
                    } else {
                        $paymentType = 'Refund';
                        $chargeBy = 'User';
                        $payingBy = 'Receiver';
                        $payerId = $recieverUser->id;
                    }
                }
            } elseif ($senderUserType == 'Agent') {
                if ($receiverUserType == 'Merchant') {
                    $paymentType = 'Deposit';
                    $chargeBy = 'Agent';
                    $payingBy = 'Receiver';
                    $payerId = $recieverUser->id;
                } elseif ($receiverUserType == 'Agent') {
                    $paymentType = 'Send Money';
                    $chargeBy = 'Agent';
                    $payingBy = 'Sender';
                    $payerId = $senderUser->id;
                } else {
                    $paymentType = 'Deposit';
                    $chargeBy = 'Agent';
                    $payingBy = 'Receiver';
                    $payerId = $recieverUser->id;
                }
            } else {
                if ($receiverUserType == 'Merchant') {
                    if ($requestType == 'Online Shopping') {
                        $paymentType = 'Online Shopping';
                        $chargeBy = 'User';
                        $payingBy = 'Sender';
                        $payerId = $senderUser->id;
                    } else {
                        if ($recieverUser->shopping_trans_pay_by == 'User') {
                            $paymentType = 'Shopping';
                            $chargeBy = 'User';
                            $payingBy = 'Sender';
                            $payerId = $senderUser->id;
                        } else {
                            $paymentType = 'Shopping';
                            $chargeBy = 'Merchant';
                            $payingBy = 'Sender';
                            $payerId = $recieverUser->id;
                        }
                    }
                } elseif ($receiverUserType == 'Agent') {
                    $paymentType = 'Withdraw';
                    $chargeBy = 'User';
                    $payingBy = 'Sender';
                    $payerId = $senderUser->id;
                } else {
                    $paymentType = 'Send Money';
                    $chargeBy = 'User';
                    $payingBy = 'Sender';
                    $payerId = $senderUser->id;
                }
            }


            if ($payingBy == 'Sender') {
                $feePayBy = 'Sender';
                if ($senderUser->user_type != 'Merchant' && $recieverUser->shopping_trans_pay_by == 'Merchant' && $requestType != 'Online Shopping') {
                    $transFee = '0.00';
                } else {
                    $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', $paymentType)->where('status', 1)->first();

                    if (empty($userFee)) {
                        $fees = Transactionfee::where('transaction_type', $paymentType)->where('status', 1)->first();

                        if (!empty($fees)) {
                            if ($chargeBy == 'Merchant') {
                                $chargeFee = $fees->merchant_charge;
                            } else if ($chargeBy == 'Agent') {
                                $chargeFee = $fees->agent_charge;
                            } else {
                                $chargeFee = $fees->user_charge;
                            }

                            $transFee = (($amount * $chargeFee) / 100);
                        }
                    } else {
                        if (!empty($userFee)) {
                            $chargeFee = $userFee->user_charge;
                            $transFee = (($amount * $chargeFee) / 100);
                        }
                    }
                }
                $totalAmt = number_format(($amount + $transFee), 2);
                if ($receiverUserType == 'Merchant') {
//                    $message = __('message.You are about to pay ' . CURR . ' ' . $totalAmt . ' to ' . $recieverUser->name . ' with transaction fee ' . CURR . ' ' . number_format($transFee, 2));
                    $message = __("message.about_to_pay", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name, 'fee' => CURR . ' ' . number_format($transFee, 2)]);
                } elseif ($receiverUserType == 'Agent') {
//                    $message = __('message.You are about to pay ' . CURR . ' ' . $totalAmt . ' to ' . $recieverUser->name . ' with transaction fee ' . CURR . ' ' . number_format($transFee, 2));
                    $message = __("message.about_to_pay", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name, 'fee' => CURR . ' ' . number_format($transFee, 2)]);
                } else {
//                    $message = __('message.You are about to sent ' . CURR . ' ' . $totalAmt . ' to ' . $recieverUser->name . ' with transaction fee ' . CURR . ' ' . number_format($transFee, 2));
                    $message = __("message.about_to_pay", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name, 'fee' => CURR . ' ' . number_format($transFee, 2)]);
                }
            } else {
                $feePayBy = 'Receiver';
                if ($paymentType == 'Refund') {
                    if ($senderUser->trans_pay_by != 'User') {

                        $userFee = Usertransactionfee::where('user_id', $senderUser->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                            if (!empty($fees)) {
                                $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                            }
                        }
                        $transactionFee = $transFee;

                        $totalAmt = $amount + $transactionFee;

                        $message = __("message.merchant_refund", ['cost' => CURR . " " . $amount, 'username' => $recieverUser->name, 'recieverName' => $recieverUser->name, 'fee' => CURR . ' ' . $this->numberFormatPrecision($transactionFee, 2), 'paidAmt' => CURR . " " . $totalAmt]);
                    } else {
                        $userFee = Usertransactionfee::where('user_id', $recieverUser->id)->where('transaction_type', 'Refund')->where('status', 1)->first();
                        if (empty($userFee)) {
                            $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

                            if (!empty($fees)) {
                                $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
                            }
                        } else {
                            if (!empty($userFee)) {
                                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                            }
                        }
                        $totalAmt = $amount - $transFee;

                        $message = __("message.user_refund", ['cost' => CURR . " " . $amount, 'username' => $recieverUser->name, 'recieverName' => $recieverUser->name, 'fee' => CURR . ' ' . $this->numberFormatPrecision($transFee, 2), 'paidAmt' => CURR . " " . $totalAmt]);
                    }
                } else {

//                $message = __('message.You are about to send amount ') . CURR . ' ' . $amount;
                    $message = __("message.send_about", ['cost' => CURR . " " . $amount]);
                }
            }

            $statusArr = array("status" => "Success", 'reason' => $message, "fee_pay_by" => $feePayBy, "transaction_fee" => $this->numberFormatPrecision($transFee, 2));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function checkFee($payerId = null, $paymentType = null, $amount = null, $userType = null) {
//echo $payerId;
//echo $paymentType;
//echo $amount;
//echo $userType;
        $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', $paymentType)->where('status', 1)->first();
        if (empty($userFee)) {
            $fees = Transactionfee::where('transaction_type', $paymentType)->where('status', 1)->first();

            if (!empty($fees)) {
                if ($userType == 'Merchant') {
                    $chargeFee = $fees->merchant_charge;
                } else if ($userType == 'Agent') {
                    $chargeFee = $fees->agent_charge;
                } else {
                    $chargeFee = $fees->user_charge;
                }
                $transFee = $this->numberFormatPrecision((($amount * $chargeFee) / 100), 2);
            }
        } else {
            if (!empty($userFee)) {
                $chargeFee = $userFee->user_charge;
                $transFee = $this->numberFormatPrecision((($amount * $chargeFee) / 100), 2);
            }
        }

        return $transFee;
    }

    public function checkTransactionType($senderId = null, $receiverId = null) {
        $user_id = $senderId;

        $matchThese = ["users.id" => $user_id];
        $senderUser = DB::table('users')->where($matchThese)->first();

        $matchThese = ["users.id" => $receiverId];
        $recieverUser = DB::table('users')->where($matchThese)->first();

        $senderUserType = $senderUser->user_type;
        $receiverUserType = $recieverUser->user_type;

        if ($senderUserType == 'Merchant') {
            if ($receiverUserType == 'Merchant') {
                $paymentType = 'Send Money';
            } elseif ($receiverUserType == 'Agent') {
                $paymentType = 'Withdraw';
            } else {
                $paymentType = 'Refund';
            }
        } elseif ($senderUserType == 'Agent') {
            if ($receiverUserType == 'Merchant') {
                $paymentType = 'Deposit';
            } elseif ($receiverUserType == 'Agent') {
                $paymentType = 'Send Money';
            } else {
                $paymentType = 'Deposit';
            }
        } else {
            if ($receiverUserType == 'Merchant') {
                $paymentType = 'Shopping';
            } elseif ($receiverUserType == 'Agent') {
                $paymentType = 'Withdraw';
            } else {
                $paymentType = 'Send Money';
            }
        }

        return $paymentType;
    }

    public function getRefundFee($payerId = null, $amount = null) {
        $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', 'Refund')->where('status', 1)->first();
        if (empty($userFee)) {
            $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

            if (!empty($fees)) {
                $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
            }
        } else {
            if (!empty($userFee)) {
                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
            }
        }

        return $transFee;
    }

    public function checkOnlineShopping(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
        $user_id = $request->user_id;

        $lang = $request->device_language;
        App::setLocale($lang);

        $orderInfo = Order::where('user_id', $user_id)->where('status', 0)->orderBy('id', 'DESC')->first();

        if (!empty($orderInfo)) {
            $amount = $orderInfo->amount;

            $userFee = Usertransactionfee::where('user_id', $user_id)->where('transaction_type', 'Online Shopping')->where('status', 1)->first();
            if (empty($userFee)) {
                $fees = Transactionfee::where('transaction_type', 'Online Shopping')->where('status', 1)->first();

                if (!empty($fees)) {
                    $transFee = (($amount * $fees->user_charge) / 100);
                }
            } else {
                if (!empty($userFee)) {
                    $transFee = (($amount * $userFee->user_charge) / 100);
                }
            }
            $totalAmt = number_format(($amount + $transFee), 2);

            $data['data']['message'] = __("message.about_to_pay", ['cost' => CURR . " " . $totalAmt, 'username' => $orderInfo->Merchant->name, 'fee' => CURR . ' ' . number_format($transFee, 2)]);
//            $data['data']['message'] = __('message.You are about to pay ' . CURR . ' ' . $totalAmt . ' to ' . $orderInfo->Merchant->name . ' with transaction fee ' . CURR . ' ' . number_format($transFee, 2));
            $data['data']['amount'] = $this->numberFormatPrecision($amount, 2, '.');
            $data['data']['transaction_fee'] = $this->numberFormatPrecision($transFee, 2, '.');

            $data['data']['order_id'] = $orderInfo->order_id;
            $data['data']['merchant_name'] = $orderInfo->Merchant->name;
            $data['data']['merchant_phone'] = $orderInfo->Merchant->phone;
            $data['data']['merchant_id'] = (string) $orderInfo->Merchant->id;

            $statusArr = array("status" => "Success", "reason" => __('message.Request received'));
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __('message.Order not found.'));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function merchantSetting(Request $request) {
        $requestUser = $request->user();
        $request = $this->decryptContent($request->req);

        if ($requestUser->id != $request->user_id) {
            $statusArr = array("status" => "Failed", "reason" => __("message.Unauthorized"));
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }

        $lang = $request->device_language;
        App::setLocale($lang);
        $userInfo = User::where('id', $request->user_id)->first();
        $trans_pay_by = $request->trans_pay_by;
        if (!empty($userInfo)) {
            if ($trans_pay_by != '') {
                User::where('id', $userInfo->id)->update(array('trans_pay_by' => $trans_pay_by));
            }

            $statusArr = array("status" => "Success", "reason" => __("message.Setting Updated"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.You entered wrong user id."));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function saveerror(Request $request) {
        $request = $this->decryptContent($request->req);
        $title = $request->title;
        $message = $request->message;

        $data['title'] = $title;
        $data['message'] = $message;
        $data['date_time'] = date('Y-m-d H:i:s');

        $serialisedData = $this->serialiseFormData($data); //send 1 for edit

        Errorrecords::insert($serialisedData);

        $statusArr = array("status" => "Success", "reason" => "Record saved successfully.");
//        return response()->json($statusArr, 200);
        $json = json_encode($statusArr);
        $responseData = $this->encryptContent($json);
        return response()->json($responseData, 200);
    }

    public function getCityList(Request $request) {
        $request = $this->decryptContent($request->req);
        $lang = $request->device_language;
        App::setLocale($lang);

        $dataArr = array();
        $cities = DB::table('cities')->get();

        if (!empty($cities)) {
            foreach ($cities as $cityData) {
                if ($lang == 'en') {
                    $dataArr['id'] = $cityData->id;
                    $dataArr['name'] = $cityData->name_en;
                } else {
                    $dataArr['id'] = $cityData->id;
                    $dataArr['name'] = $cityData->name_ar;
                }
                $data['data'][] = $dataArr;
            }


            $statusArr = array("status" => "Success", "reason" => __("message.City List"));
            $json = array_merge($statusArr, $data);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Cities not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function getAreaList(Request $request) {
        $request = $this->decryptContent($request->req);
        $city_id = $request->city_id;
        $lang = $request->device_language;
        App::setLocale($lang);

        $dataArr = array();
        $dataArr1 = array();
        $areas = DB::table('areas')->where('city_id', $city_id)->get();

        if (!empty($areas)) {
            foreach ($areas as $area) {
                $data['id'] = $area->id;
                $data['name'] = $area->name;
                $dataArr1[] = $data;
            }
            $dataArr['data'] = $dataArr1;

            $statusArr = array("status" => "Success", "reason" => __("message.Area List"));
            $json = array_merge($statusArr, $dataArr);
//            return response()->json($json, 200);
            $json = json_encode($json);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        } else {
            $statusArr = array("status" => "Failed", "reason" => __("message.Areas not available"));
//            return response()->json($statusArr, 200);
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }
    }

    public function updateAES(Request $request) {

        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = 'BlVssQKxzAHFAUNZbqvwS+yKwSa';

// $string     = 'Hi, This is a encripted and descripted test for PHP, Android and iOS';
//         $cipher  = $encryption->encryptPlainTextWithRandomIV($string, $secretyKey);
// $string = $cipher . PHP_EOL;

        $req = $request->req;
        $string = $req;
        //echo '<pre>';print_r($request->req);
        // $string     = 'Hi, This is a encripted and descripted test for PHP, Android and iOS';





        $plainText = $encryption->decryptCipherTextWithRandomIV($string, $secretyKey);
// $plainText = 'Updated string using PHP '.$plainText . ' PHP ';
        $plainText = $plainText . PHP_EOL;
// echo '<pre>';print_r($plainText);exit;
        $plainText = json_decode($plainText);
        // print_r($plainText);
// $cipher  = $encryption->encryptPlainTextWithRandomIV($plainText->string, $secretyKey);
        $data['data'] = str_replace('\n', '', $plainText->string);
// $data['data'] = $plainText;
// $plainText1 = $encryption->decryptCipherTextWithRandomIV($cipher, $secretyKey);
// $plainText1 = $plainText1 . PHP_EOL;
// $cipher1 = str_replace('\n', '', $plainText->string);
// $plainText12 = $encryption->decryptCipherTextWithRandomIV($cipher1, $secretyKey);
        $plainText12 = $plainText->string;

        $statusArr = array("status" => "Success", "reason" => 'Success', 'plainText' => $plainText12);
        $b = array_merge($statusArr, $data);
        $json = json_encode($b);
        // print_r($json);
// $json = json_encode($json);
        $cipher = $encryption->encryptPlainTextWithRandomIV($json, $secretyKey);
        // echo $cipher;

        $json = $cipher;
// $data['data'] = $plainText;
// $statusArr = array("res" => $json);

        return response()->json($json, 200);
    }

}
