<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Cookie;
use Session;
use Redirect;
use Input;
use Validator;
use DB;
use Mail;
use App\Mail\SendMailable;
use Socialite;
use App\User;
use App\Models\Admin;
use App\Models\Image;
use App\Models\Order;
use App\Models\Banner;
use App\Models\Scratchcard;
use App\Models\Transaction;
use App\Models\TransactionLedger;
use App\Models\Notification;
use App\Models\Agentoffer;
use App\Models\Offer;
use App\Models\Userfeature;
use App\Models\Feature;
use App\Models\City;
use App\Models\Area;
use App\Models\Driver;
use App\Models\Transactionfee;
use App\Models\Usertransactionfee;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Services\SmsService;



class UsersController extends Controller
{

    public $smsService;
    public function __construct(SmsService $smsService)
    {
        //        $this->middleware('auth');
        $this->middleware('userlogedin', ['only' => ['login', 'loginDriver', 'driverPhoneVerify', 'driverDashboard', 'individualMobileRegister', 'individualRegister', 'individualVerify', 'individualDetail', 'individualKycVerify', 'agentMobileRegister', 'agentRegister', 'agentVerify', 'agentDetail', 'agentKycVerify', 'merchantMobileRegister', 'merchantRegister', 'merchantVerify', 'merchantDetail', 'merchantKycVerify', 'agentLogin', 'individualLogin', 'merchantLogin', 'forgotPassword', 'forgotVerify', 'register', 'verify', 'resendOTP', 'createAccount', 'emailVerify', 'resendEmailOtp', 'verifyEmailForgot', 'resetPassword']]);
        $this->middleware('is_userlogin', ['except' => ['chkpayment', 'login', 'loginDriver', 'driverPhoneVerify', 'driverDashboard', 'payBySatpay', 'onlineShopping', 'individualMobileRegister', 'individualRegister', 'individualVerify', 'individualDetail', 'individualKycVerify', 'agentMobileRegister', 'agentRegister', 'agentVerify', 'agentDetail', 'agentKycVerify', 'merchantMobileRegister', 'merchantRegister', 'merchantVerify', 'merchantDetail', 'merchantKycVerify', 'logout', 'agentLogin', 'individualLogin', 'merchantLogin', 'forgotPassword', 'forgotVerify', 'resetPassword', 'register', 'verify', 'emailConfirmation', 'resendOTP', 'getarealist', 'verifyOtp', 'resendOTP', 'createAccount', 'emailVerify', 'resendEmailOtp', 'verifyEmailForgot', 'deleteAccountUser', 'deleteAccountVerify', 'deleteSuccess']]);
        $this->smsService = $smsService;
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

    public function register(Request $request)
    {
        $pageTitle = 'Register';
        $input = Input::all();
        $ref = $request->query('ref');
        if (!empty($input['phoneNumber'])) {
            $validate_data = [
                'phoneNumber' => 'required',
            ];

            $customMessages = [
                'phoneNumber.required' => __('message.Phone number field can\'t be left blank'),
            ];

            // print_r($request->query('ref')); die;
            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/register')->withInput()->withErrors($messages);
            }

            $phone = $input['phoneNumber'];

            $userInfo = User::where("phone", $phone)
                ->where("user_type", "Merchant")
                ->count();
            if ($userInfo == 0) {
                Session::put('error_message', __('message.Your mobile number is not registered as a merchant'));
                return Redirect::to('/register');
            }

            $encMobile = base64_encode($this->encryptContent($phone));
            Session::put('referralCode', $input['refCode']);
            return Redirect::to('/verify-otp/' . $encMobile);


            /* $createPass = $this->smsService->createOneTimePasscode();
            if (isset($createPass['applicationId']) && !empty($createPass['applicationId']) && $createPass['enabled']) {
                $dataSecond = $this->smsService->sendTwoFactorMessage($createPass['applicationId']);
                if (isset($dataSecond['messageId']) && !empty($dataSecond['messageId'])) {
                    $dataDelivered = $this->smsService->deliverTwoFactorPasscode($dataSecond['applicationId'], $dataSecond['messageId'], "241$phone");
                    if (isset($dataDelivered['pinId']) && !empty($dataDelivered['smsStatus']) && $dataDelivered['smsStatus'] == 'MESSAGE_NOT_SENT') {
                        $encPinId = base64_encode($dataDelivered['pinId']);
                        Session::put('pinID', $encPinId);
                        return Redirect::to("/verify-otp/$encMobile");
                    }
                }
            } */

        }
        return view('users.register', ['title' => $pageTitle, 'ref' => $ref]);
    }

    public function verifyOtp($slug)
    {
        $pageTitle = 'Verify Otp';
        $decode_string = base64_decode($slug);
        $pinId = base64_decode(Session::get('pinID'));
        $phone = $this->decryptContent($decode_string);
        $input = Input::all();
        if (!empty($input)) {

            $otp1 = $input['otp1'];
            $otp2 = $input['otp2'];
            $otp3 = $input['otp3'];
            $otp4 = $input['otp4'];
            $otp5 = $input['otp5'];
            $otp6 = $input['otp6'];

            $otpCode = "$otp1$otp2$otp3$otp4$otp5$otp6";
            if ($otpCode != '111111') {
                Session::put('error_message', __('message.Please provide a valid otp'));
                return Redirect::to('/verify-otp/' . $slug);
            }


            /* $verifyResp = $this->smsService->verifyOtpPasscode($pinId, $otpCode);
            $dataVerify = ($verifyResp instanceof \Illuminate\Http\JsonResponse) ? $verifyResp->getData(true) : []; */

            $userInfo = User::where("phone", $phone)
                ->where("user_type", "!=", "")
                ->first();

            if (empty($userInfo)) {
                Session::put('error_message', __('message.Phone number does not exist'));
                return Redirect::to('/login');
            }

            if ($userInfo->is_verify == 0) {
                Session::put('error_message', __('message.Your account might have been temporarily disabled. Please contact us for more details.'));
                return Redirect::to('/login');
            }
            /* if (isset($dataVerify['data']['verified']) && $dataVerify['data']['verified']) {
                Session::put('success_message', __('message.Otp verification has been successful.'));
                } else {
                    return Redirect::to("/verify-otp/$slug");
            } */
            Session::put('success_message', __('message.Otp verification has been successful.'));
            return Redirect::to('/create-account/' . $slug);

        }
        return view('users.verify_otp', ['title' => $pageTitle, 'slug' => $slug]);
    }


    public function createAccount($slug)
    {
        $pageTitle = 'Create Account';
        $input = Input::all();
        $decode_string = base64_decode($slug);
        $phone = $this->decryptContent($decode_string);

        if (!empty($input)) {
            $validate_data = [
                'email' => 'required',
                'password' => 'required',
                'confirm_password' => 'required |same:password',
                'referralBy' => 'nullable|exists:users,referralCode' // Validate referral code
            ];

            $customMessages = [
                'email.required' => __('message.Email field can\'t be left blank'),
                'password.required' => __('message.Password field can\'t be left blank'),
                'confirm_password.required' => __('message.Confirm password field can\'t be left blank'),
                'confirm_password.same' => __('message.Password & Confirm password should be same'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/create-account/' . $slug)->withInput()->withErrors($messages);
            }

            $referralCode = $input['referralCode'] ?? '';

            if (!empty($referralCode)) {
                if ($referralCode && !User::where('referralCode', $referralCode)->exists()) {
                    Session::put('error_message', __('message.The referral code is invalid or does not exist.'));
                    return back();
                }
            }

            $email = $input['email'];
            $isAlreadyExist = User::where("email", $email)->where('phone', '!=', $phone)->count();
            if ($isAlreadyExist > 0) {
                Session::put('error_message', __('message.Email address is already exist'));
                return Redirect::to('/register');
            }
            $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $userData = User::where('phone', $phone)->first();
            $emailId = $email;
            $emailSubject = 'Verify Email';
            $emailData['subject'] = $emailSubject;
            $emailData['name'] = $userData->name;
            $emailData['otp'] = $otp;
            /* $data = Mail::send('emails.veriftOtp', $emailData, function ($message) use ($emailData, $emailId) {
                $message->to($emailId, $emailId)
                    ->subject($emailData['subject']);
            }); */

            $referredByUser = User::where('referralCode', $referralCode)->first();


            $userData->update([
                'email' => $email,
                'password' => Hash::make($input['password']),
                'referralBy' => $referredByUser ? $referredByUser->id : null,
                'referralCode' => strtoupper(substr(md5(uniqid()), 0, 8)),
                'is_email_verified' => Hash::make($otp)
            ]);

            /* User::where('phone', $phone)->update([
                'email' => $email,
                'password' => Hash::make($input['password']),
                'referralBy' => $referredByUser ? $referredByUser->id : null,
                'referralCode' => $referralCodes,
                'is_email_verified' => Hash::make($otp)
            ]); */


            if ($referralCode) {
                $this->handleReferralBonus($userData, $referralCode);
            }

            /* if ($referredByUser) {
                $referredByUser->increment('wallet_balance', 10);
            } */

            Session::put('success_message', __('message.Your account has been successfully linked with your mobile number.Please verify your email address to activate your account'));
            return Redirect::to('/email-verify/' . $slug);
        }
        return view('users.createAccount', ['title' => $pageTitle]);
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

            foreach ($users as $userData) {
                $user = $userData['user'];
                $bonusAmount = $userData['bonusAmount'];

                DB::table('users')->where('id', $user->id)->increment('wallet_balance', $bonusAmount);
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
                    'billing_description' => "Fund Transfer-" . time() . rand(),
                    'onafriq_bda_ids' => 0,
                    'transactionType' => '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $trans->save();

                $result = new TransactionLedger([
                    'user_id' => $user->id,
                    'opening_balance' => $user->wallet_balance,
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


    public function emailVerify($slug)
    {
        $pageTitle = 'Verify Your Email Address';
        $input = Input::all();
        $decode_string = base64_decode($slug);
        $phone = $this->decryptContent($decode_string);
        $input = Input::all();

        $userInfo = User::where("phone", $phone)
            ->where("user_type", "!=", "")
            ->first();

        if (empty($userInfo)) {
            Session::put('error_message', __('message.Phone number does not exist'));
            return Redirect::to('/login');
        }

        if ($userInfo->is_verify == 0) {
            Session::put('error_message', __('message.Your account might have been temporarily disabled. Please contact us for more details.'));
            return Redirect::to('/login');
        }

        if (!empty($input)) {

            $otp1 = $input['otp1'];
            $otp2 = $input['otp2'];
            $otp3 = $input['otp3'];
            $otp4 = $input['otp4'];
            $otp5 = $input['otp5'];
            $otp6 = $input['otp6'];
            $otpCode = $otp1 . $otp2 . $otp3 . $otp4 . $otp5 . $otp6;
            $otp = $userInfo->is_email_verified;
            if (Hash::check($otpCode, $otp)) {
                Auth::login($userInfo);
                Session::put('user_id', $userInfo->id);
                if (Auth::check()) {
                    User::where('phone', $phone)->update([
                        'is_email_verified' => 1
                    ]);

                    $user = Auth::user();
                    $user_role = $user->user_type;
                    if ($user_role == 'Submitter') {
                        return Redirect::to('/submitter-dashboard');
                    } elseif ($user_role == 'Approver') {
                        return Redirect::to('/approver-dashboard');
                    } else {
                        return Redirect::to('/dashboard');
                    }
                }
            }

            Session::put('error_message', __('message.Please provide a valid otp'));
            return Redirect::to('/email-verify/' . $slug);

        }
        return view('users.verify_otp_email', ['title' => $pageTitle, 'slug' => $slug]);
    }


    public function login()
    {
        $pageTitle = 'Login';
        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'email' => 'required',
                'password' => 'required',
            ];

            $customMessages = [
                'email.required' => __('message.Email field can\'t be left blank'),
                'password.required' => __('message.Password field can\'t be left blank'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/login')->withInput()->withErrors($messages);
            }

            $email = $input['email'];

            $userInfo = User::where("email", $email)->first();

            if (isset($userInfo) && $userInfo->id != 746) {

            } else {
                Session::put('error_message', 'You cannot access this portal');
                return Redirect::to('/login');
            }


            if (empty($userInfo)) {
                Session::put('error_message', __('message.Email does not exist'));
                return Redirect::to('/login');
            }

            $user_type = $userInfo->user_type;

            if ($user_type == "Merchant" && $userInfo->isBulkUser == 1) {
                Session::put('error_message', __('message.You cannot access this portal'));
                return Redirect::to('/login');
            }

            if ($user_type != "Merchant" && $user_type != "Submitter" && $user_type != "Approver") {
                Session::put('error_message', __('message.You cannot access this portal'));
                return Redirect::to('/login');
            }

            if ($userInfo->is_verify == 0) {
                Session::put('error_message', __('message.Your account might have been temporarily disabled. Please contact us for more details.'));
                return Redirect::to('/login');
            }


            /* if ($userInfo) {
                Auth::login($userInfo); */

            if (auth()->attempt(['email' => $input['email'], 'password' => $input['password']])) {
                if ($userInfo->is_email_verified != 1) {
                    $encMobile = base64_encode($this->encryptContent($userInfo->phone));
                    $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
                    $emailId = $email;
                    $emailSubject = 'Verify Email';
                    $emailData['subject'] = $emailSubject;
                    $emailData['name'] = $userInfo->name;
                    $emailData['otp'] = $otp;
                    /* $data = Mail::send('emails.veriftOtp', $emailData, function ($message) use ($emailData, $emailId) {
                        $message->to($emailId, $emailId)
                            ->subject($emailData['subject']);
                    }); */
                    User::where('email', $email)->update([
                        'is_email_verified' => Hash::make($otp)
                    ]);
                    return Redirect::to('/email-verify/' . $encMobile);
                }

                $user = Auth::user();
                Session::put('user_id', $userInfo->id);
                $user_role = $user->user_type;
                if ($user_role == 'Submitter') {
                    return Redirect::to('/submitter-dashboard');
                } elseif ($user_role == 'Approver') {
                    return Redirect::to('/approver-dashboard');
                } else {
                    return Redirect::to('/dashboard');
                }
            }
            Session::put('error_message', __('message.please provide valid credentials'));
            return Redirect::to('/login');
        }

        return view('users.login', ['title' => $pageTitle]);
    }

    public function forgotPassword()
    {
        $pageTitle = 'Forgot Password';
        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'email' => 'required',
            ];

            $customMessages = [
                'email.required' => __('message.Email field can\'t be left blank'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/forgot-password')->withInput()->withErrors($messages);
            }

            $email = $input['email'];

            $userInfo = User::where("email", $email)->first();

            if (empty($userInfo)) {
                Session::put('error_message', __('message.Email does not exist'));
                return Redirect::to('/forgot-password');
            }

            if ($userInfo->is_verify == 0) {
                Session::put('error_message', __('message.Your account might have been temporarily disabled. Please contact us for more details.'));
                return Redirect::to('/login');
            }


            $encMobile = base64_encode($userInfo->email);
            $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $emailId = $email;
            $emailSubject = 'Verify Email';
            $emailData['subject'] = $emailSubject;
            $emailData['name'] = $userInfo->name;
            $emailData['otp'] = $otp;
            /* $data = Mail::send('emails.veriftOtp', $emailData, function ($message) use ($emailData, $emailId) {
                $message->to($emailId, $emailId)
                    ->subject($emailData['subject']);
            }); */
            User::where('email', $email)->update([
                'is_email_verified' => Hash::make($otp)
            ]);
            Session::put('success_message', __('message.We have sent an OTP to your email. Please check your email and enter it here.'));
            return Redirect::to('/verify-email/' . $encMobile);
        }
        return view('users.forgotPassword', ['title' => $pageTitle]);
    }

    public function verifyEmailForgot($slug)
    {
        $pageTitle = 'Verify Your Email Address';
        $input = Input::all();
        $decode_string = base64_decode($slug);

        $email = $decode_string;
        //        $email = $this->decryptContent($decode_string);
        $input = Input::all();
        //        echo $email;exit;

        $userInfo = User::where("email", $email)
            ->where("user_type", "!=", "")
            ->first();

        //        echo '<pre>';print_r($userInfo);exit;

        if (empty($userInfo)) {
            Session::put('error_message', __('message.Phone number does not exist'));
            return Redirect::to('/login');
        }

        if ($userInfo->is_verify == 0) {
            Session::put('error_message', __('message.Your account might have been temporarily disabled. Please contact us for more details.'));
            return Redirect::to('/login');
        }

        if (!empty($input)) {

            $otp1 = $input['otp1'];
            $otp2 = $input['otp2'];
            $otp3 = $input['otp3'];
            $otp4 = $input['otp4'];
            $otp5 = $input['otp5'];
            $otp6 = $input['otp6'];
            $otpCode = $otp1 . $otp2 . $otp3 . $otp4 . $otp5 . $otp6;
            $otp = $userInfo->is_email_verified;

            //          echo Hash::check($otpCode, $otp);exit;
            if (Hash::check($otpCode, $otp)) {
                $link = base64_encode($this->encryptContent($userInfo->id));
                return Redirect::to('/reset-password/' . $link);
            }

            Session::put('error_message', __('message.Please provide a valid otp'));
            return Redirect::to('/verify-email/' . $slug);

        }
        return view('users.verify_otp_email_forgot', ['title' => $pageTitle, 'slug' => $slug]);
    }

    public function resetPassword($slug)
    {
        $pageTitle = 'Generate Password';
        $decode_string = base64_decode($slug);
        $user_id = $this->decryptContent($decode_string);
        $userInfo = User::where('id', $user_id)->first();
        if (empty($userInfo)) {
            Session::put('error_message', __('message.Invalid User!'));
            return Redirect::to('/login');
        }

        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'password' => 'required',
                'confirm_password' => 'required|same:password',
            ];

            $customMessages = [
                'password.required' => __('message.Password field can\'t be left blank'),
                'confirm_password.required' => __('message.Confirm password field can\'t be left blank'),
                'confirm_password.same' => __('message.Password and confirm password should be same'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                Session::put('error_message', $messages);
                return Redirect::to('/reset-password/' . $slug);
            }
            $hashedPassword = Hash::make($input['password']);
            User::where('id', $user_id)->update(['password' => $hashedPassword, 'is_email_verified' => 1]);
            Session::put('success_message', __('message.Password has been updated successfully'));
            return Redirect::to('/login');
        }
        return view('dashboard.generate_password', ['title' => $pageTitle]);
    }

    public function logout()
    {
        Auth::logout();
        Session::forget('user_id');
        return Redirect::to('/login');
    }

    public function resendOTP(Request $request)
    {
        $decode_string = base64_decode($request->phone);
        $phone = $this->decryptContent($decode_string);

        /* $createPass = $this->smsService->createOneTimePasscode();
        if (isset($createPass['applicationId']) && !empty($createPass['applicationId']) && $createPass['enabled']) {
            $dataSecond = $this->smsService->sendTwoFactorMessage($createPass['applicationId']);
            if (isset($dataSecond['messageId']) && !empty($dataSecond['messageId'])) {
                $dataDelivered = $this->smsService->deliverTwoFactorPasscode($dataSecond['applicationId'], $dataSecond['messageId'], "241$phone");
                if (isset($dataDelivered['pinId']) && !empty($dataDelivered['smsStatus']) && $dataDelivered['smsStatus'] == 'MESSAGE_SENT') {
                    $encPinId = base64_encode($dataDelivered['pinId']);
                    Session::put('pinID', $encPinId);
                    Session::save();
                    return 1;
                } else {
                    return 0;
                }
            }
        } */
        /* if (isset($createPass['applicationId']) && !empty($createPass['applicationId']) && $createPass['enabled']) {
            $getSecondResp = $this->sendTwoFactorMessage($data['applicationId']);
            $dataSecond = ($getSecondResp instanceof \Illuminate\Http\JsonResponse) ? $getSecondResp->getData(true) : [];
            if (isset($dataSecond['messageId']) && !empty($dataSecond['messageId'])) {
                $deliveredResp = $this->deliverTwoFactorPasscode($dataSecond['applicationId'], $dataSecond['messageId'], "241$phone");
                $dataDelivered = ($deliveredResp instanceof \Illuminate\Http\JsonResponse) ? $deliveredResp->getData(true) : [];
                if (isset($dataDelivered['pinId']) && !empty($dataDelivered['smsStatus']) && $dataDelivered['smsStatus'] == 'MESSAGE_SENT') {
                    $encPinId = base64_encode($dataDelivered['pinId']);
                    Session::put('pinID', $encPinId);
                    Session::save();
                    return 1;
                } else {
                    return 0;
                }
            }
        } */
        $input = Input::all();
        echo 1;
        die;
    }

    public function resendEmailOtp()
    {

        $input = Input::all();
        $phone = $input['phone'];
        //echo"<pre>  ";print_r($phone);exit;
        $email = base64_decode($input['phone']);
        //echo"<pre>  ";print_r($decode_string);exit;
        //$decode_string = base64_decode($phone);

        //$phone = $this->decryptContent($decode_string);
        // echo"<pre>  ";print_r($phone);exit;
        $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $userData = User::where('email', $email)->first();
        $emailId = $userData->email;
        $emailSubject = 'Verify Email';
        $emailData['subject'] = $emailSubject;
        $emailData['name'] = $userData->name;
        $emailData['otp'] = $otp;
        /* $data = Mail::send('emails.veriftOtp', $emailData, function ($message) use ($emailData, $emailId) {
            $message->to($emailId, $emailId)
                ->subject($emailData['subject']);
        }); */
        User::where('phone', $phone)->update([
            'is_email_verified' => Hash::make($otp)
        ]);
        echo 1;
        die;
    }

    public function loginDriver()
    {

        $pageTitle = 'Login Driver';
        $input = Input::all();
        if (!empty($input)) {
            $validate_data = [
                'phoneNumber' => 'required',
            ];

            $customMessages = [
                'phoneNumber.required' => 'Phone number field can\'t be left blank',
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/login-driver')->withInput()->withErrors($messages);
            }

            $phone = $input['phoneNumber'];

            $userInfo = Driver::where("phone", $phone)->first();


            if (empty($userInfo)) {
                Session::put('error_message', 'Phone number does not exist');
                return Redirect::to('/login-driver');
            }

            if ($userInfo->status == 0) {
                Session::put('error_message', 'Your account might have been temporarily disabled. Please contact us for more details.');
                return Redirect::to('/login-driver');
            }

            $encMobile = base64_encode($this->encryptContent($userInfo->phone));
            $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            /* $encMobile = base64_encode($this->encryptContent($userInfo->phone));
            $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $emailId = $userInfo->email;
            $emailSubject = 'Verify Email';
            $emailData['subject'] = $emailSubject;
            $emailData['name'] = $userInfo->name;
            $emailData['otp'] = $otp; */
            return Redirect::to('/driver-phone-verify/' . $encMobile);
            /* Session::put('error_message', 'please provide valid credentials');
            return Redirect::to('/login-driver'); */
        }

        return view('users.login-driver', ['title' => $pageTitle]);
    }
    public function driverPhoneVerify($slug)
    {
        $pageTitle = 'Verify Your Phone';
        $input = Input::all();

        $decode_string = base64_decode($slug);
        $phone = $this->decryptContent($decode_string);
        $userInfo = Driver::where("phone", $phone)
            ->first();

        if (empty($userInfo)) {
            Session::put('error_message', 'Phone number does not exist');
            return Redirect::to('/login-driver');
        }

        if ($userInfo->status == 0) {
            Session::put('error_message', 'Your account might have been temporarily disabled. Please contact us for more details.');
            return Redirect::to('/login-driver');
        }

        if (!empty($input)) {

            $otp1 = $input['otp1'];
            $otp2 = $input['otp2'];
            $otp3 = $input['otp3'];
            $otp4 = $input['otp4'];
            $otp5 = $input['otp5'];
            $otp6 = $input['otp6'];
            $otpCode = $otp1 . $otp2 . $otp3 . $otp4 . $otp5 . $otp6;
            if ($otpCode == 111111) {
                Auth::guard('driver-web')->login($userInfo);
                // Session::put('driver_id', $userInfo->id);
                Session::put('success_message', 'Otp verification has been successful.');
                return Redirect::to('/driver-dashboard');
            }

            Session::put('error_message', 'Please provide a valid otp');
            return Redirect::to('/driver-phone-verify/' . $slug);

        }
        return view('users.verify_otp_driver', ['title' => $pageTitle, 'slug' => $slug]);
    }


    public function deleteAccountUser()
    {

        $pageTitle = 'Swap User';
        $input = Input::all();
        if (!empty($input)) {
            $validate_data = [
                'phoneNumber' => 'required',
            ];

            $customMessages = [
                'phoneNumber.required' => 'Phone number field can\'t be left blank',
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/delete-account-user')->withInput()->withErrors($messages);
            }

            $phone = $input['phoneNumber'];

            $userInfo = User::where("phone", $phone)->first();

            if (empty($userInfo)) {
                Session::put('error_message', 'Phone number does not exist');
                return Redirect::to('/delete-account-user');
            }

            if ($userInfo->is_verify == 0) {
                Session::put('error_message', 'Your account might have been temporarily disabled. Please contact us for more details.');
                return Redirect::to('/delete-account-user');
            }

            $encMobile = base64_encode($this->encryptContent($userInfo->phone));
            $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);


            DB::table('delete_account_otp')->updateOrInsert(
                ['phoneNumber' => $userInfo->phone],
                [
                    'otp' => $otp,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $this->smsService->sendLoginRegisterOtp($otp, $userInfo->phone);

            return Redirect::to('/delete-account-verify/' . $encMobile);
        }

        return view('users.delete-account-user', ['title' => $pageTitle]);
    }

    public function deleteAccountVerify($slug)
    {
        $pageTitle = 'Verify Your Phone';
        $input = Input::all();

        $decode_string = base64_decode($slug);
        $phone = $this->decryptContent($decode_string);
        $userInfo = User::where("phone", $phone)
            ->first();

        if (empty($userInfo)) {
            Session::put('error_message', 'Phone number does not exist');
            return Redirect::to('/delete-account-user');
        }
        if ($userInfo->is_verify == 0) {
            Session::put('error_message', 'Your account might have been temporarily disabled. Please contact us for more details.');
            return Redirect::to('/delete-account-user');
        }

        if (!empty($input)) {

            $otp1 = $input['otp1'];
            $otp2 = $input['otp2'];
            $otp3 = $input['otp3'];
            $otp4 = $input['otp4'];
            $otp5 = $input['otp5'];
            $otp6 = $input['otp6'];
            $otpCode = $otp1 . $otp2 . $otp3 . $otp4 . $otp5 . $otp6;

            $otpExist = DB::table('delete_account_otp')->where('otp', $otpCode)->first();
            if (!isset($otpExist->otp)) {
                Session::put('error_message', 'Please provide a valid otp');
                return Redirect::to('/delete-account-verify/' . $slug);
            }
            Session::put('delete_account_otp', ['otpCheck' => $otpCode, 'expires_at' => now()->addMinutes(5)]);

            return Redirect::to('/delete-success');
        }
        return view('users.verify_delete_account', ['title' => $pageTitle, 'slug' => $slug]);
    }
    public function deleteSuccess()
    {
        $otpSession = Session::get('delete_account_otp');
        if ($otpSession) {
            return view('users.delete_success');
        }
    }


}