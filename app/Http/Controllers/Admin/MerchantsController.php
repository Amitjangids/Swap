<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Cookie;
use Session;
use Redirect;
use Input;
use Validator;
use DB;
use IsAdmin;
use App\User;
use App\Models\Country;
use App\Models\Notification;
use App\Models\City;
use App\Models\Area;
use App\Models\Admin;
use App\Models\Transaction;
use App\Models\TransactionLedger;
use App\Models\UserCard;
use Mail;
use Hash;
use App\Mail\SendMailable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use DateTime;
use DateTimeZone;

class MerchantsController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_adminlogin');
    }

    private function generateQRCode($qrString, $user_id)
    {
        $output_file = 'uploads/qr-code/' . $user_id . '-qrcode-' . time() . '.png';
        $image = \QrCode::format('png')
            ->size(200)->errorCorrection('H')
            ->generate($qrString, base_path() . '/public/' . $output_file);
        return $output_file;
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

    private function getStatusText($status)
    {
        $statusArr = [
            '1' => 'Completed',
            '2' => 'Pending',
            '3' => 'Failed',
            '4' => 'Rejected',
            '5' => 'Refund',
            '6' => 'Refund Completed'
        ];

        return $statusArr[$status];
    }

    private function sendEmail(
        array $emailData,
        $type
    ) {
        /* if ($type == 'deposit') {
            if ($emailData["senderEmail"] != '') {
                Mail::send(
                    'emails.fund_transfer_sender',
                    $emailData,
                    function ($message) use ($emailData) {
                        $message->to(
                            $emailData["senderEmail"],
                            $emailData["senderEmail"]
                        )
                            ->subject($emailData["subjects"]);
                    }
                );
            }
        }

        if ($type == 'withdraw') {
            if ($emailData['receiverEmail'] != "") {
                Mail::send(
                    'emails.fund_transfer_receiver',
                    $emailData,
                    function ($message) use ($emailData) {
                        $message->to(
                            $emailData["receiverEmail"],
                            $emailData["receiverEmail"]
                        )
                            ->subject($emailData["subjects"]);
                    }
                );
            }
        } */

        return true;
    }

    public function index(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'merchants');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actmerchants';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Merchant Users';
        $activetab = 'actmerchants';
        $query = new User();
        $query = $query->sortable();
        $query = $query->where('user_type', 'Merchant')->where('isBulkUser', 0);

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                User::whereIn('id', $idList)->update(array('is_verify' => 1));
                Session::flash('success_message', "Records are activate successfully.");
            } else if ($action == "Deactivate") {
                User::whereIn('id', $idList)->update(array('is_verify' => 0));
                Session::flash('success_message', "Records are deactivate successfully.");
            } else if ($action == "Delete") {
                User::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('business_name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        $merchants = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }
        if ($request->ajax() || $page > 1) {
            return view('elements.admin.merchants.index', ['allrecords' => $merchants, 'page' => $page]);
        }
        return view('admin.merchants.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $merchants, 'page' => $page]);
    }

    public function add()
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-merchants');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actmerchants';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add Merchant User';
        $activetab = 'actmerchants';

        $countrList = Country::getCountryList();
        $input = Input::all();

        if (!empty($input)) {
            $rules = array(
                'name' => 'required|max:50',
                'phone' => 'required|unique:users|size:9',
                'dob' => 'required',
                // 'email' => 'required|email|unique:users,email,',

            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/merchants/add-merchants')->withErrors($validator)->withInput(Input::except('city'));
            } else {

                unset($input['phone_number']);

                $input['merchantKey'] = $this->generate_string(12);
                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['slug'] = $this->createSlug($input['name'], 'users');
                $serialisedData['is_kyc_done'] = 0;
                $serialisedData['is_verify'] = 1;
                $serialisedData['otp_verify'] = 1;
                $serialisedData['user_type'] = 'Merchant';

                User::insert($serialisedData);

                $user_id = DB::getPdo()->lastInsertId();
                $qrString = $user_id . "##" . $input['name'];
                $qrCode = $this->generateQRCode($qrString, $user_id);

                User::where('id', $user_id)->update(array('qr_code' => $qrCode));

                $name = $input['name'];
                $emailId = $input['email'];


                $emailTemplate = DB::table('emailtemplates')->where('id', 2)->first();
                $toRepArray = array('[!email!]', '[!name!]', '[!username!]', '[!HTTP_PATH!]', '[!SITE_TITLE!]');
                $fromRepArray = array($emailId, $name, $name, HTTP_PATH, SITE_TITLE);
                $emailSubject = str_replace($toRepArray, $fromRepArray, $emailTemplate->subject);
                $emailBody = str_replace($toRepArray, $fromRepArray, $emailTemplate->template);
                //Mail::to($emailId)->send(new SendMailable($emailBody,$emailSubject));

                Session::flash('success_message', "Merchant user details saved successfully.");
                return Redirect::to('admin/merchants');
            }
        }
        return view('admin.merchants.add', ['title' => $pageTitle, $activetab => 1, 'countrList' => $countrList]);
    }

    public function edit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-merchants');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actmerchants';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $countrList = Country::getCountryList();




        $recordInfo = User::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/merchants');
        }

        $pageTitle = ($recordInfo->isBulkUser == 1)
            ? 'Edit Bulk Paymenet Merchant User' 
            : 'Edit Merchant User';
        $activetab = ($recordInfo->isBulkUser == 1)
            ? 'bulkpaymentmerchant' 
            : 'actmerchants';


        $input = Input::all();

        if (!empty($input)) {
            $rules = [
                'name' => 'required|max:50',
                'phone' => 'required|size:15|unique:users,phone,' . $recordInfo->id,
                // 'email' => 'required|email|unique:users,email,' . $recordInfo->id,
            ];
            if (!empty($recordInfo->email)) {
                $rules['email'] = 'email|unique:users,email,' . $recordInfo->id;
            }


            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/merchants/edit-merchants/' . $slug)->withErrors($validator)->withInput();
            } else {

                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                User::where('id', $recordInfo->id)->update($serialisedData);

                $user_id = $recordInfo->id;
                $qrString = $user_id . "##" . $recordInfo->name;
                $qrCode = $this->generateQRCode($qrString, $user_id);

                User::where('id', $user_id)->update(array('qr_code' => $qrCode));

                Session::flash('success_message', "Merchant user details updated successfully.");

                $path = ($recordInfo->isBulkUser == 1)
                    ? '/admin/bulk-payment-merchants'
                    : '/admin/merchants';

                return Redirect::to($path);
            }
        }

        return view('admin.merchants.edit', ['title' => $pageTitle, $activetab => 1, 'countrList' => $countrList, 'recordInfo' => $recordInfo]);
    }

    public function merchantSetting($slug = null)
    {
        // $isPermitted = $this->validatePermission(Session::get('admin_role'), 'kycdetail');
        // if ($isPermitted == false) {
        //     $pageTitle = 'Not Permitted';
        //     $activetab = 'actmerchants';
        //     return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        // }
        $pageTitle = 'Change Merchant Setting';
        $activetab = 'actmerchants';
        $userInfo = User::where('slug', $slug)->first();

        $user_id = $userInfo->id;

        $input = Input::all();
        if (!empty($input)) {
            if (isset($input['trans_pay_by'])) {
                $trans_pay_by = 'User';
            } else {
                $trans_pay_by = 'Merchant';
            }

            if (isset($input['shopping_trans_pay_by'])) {
                $shopping_trans_pay_by = 'User';
            } else {
                $shopping_trans_pay_by = 'Merchant';
            }

            if (isset($input['withdrawal_trans_pay_by'])) {
                $withdrawal_trans_pay_by = 'User';
            } else {
                $withdrawal_trans_pay_by = 'Merchant';
            }

            if (isset($input['newwithdrawal_trans_pay_by'])) {
                $newwithdrawal_trans_pay_by = 'User';
            } else {
                $newwithdrawal_trans_pay_by = 'Merchant';
            }

            if (isset($input['deposit_trans_pay_by'])) {
                $deposit_trans_pay_by = 'User';
            } else {
                $deposit_trans_pay_by = 'Merchant';
            }

            User::where('id', $user_id)->update(array('trans_pay_by' => $trans_pay_by, 'shopping_trans_pay_by' => $shopping_trans_pay_by, 'withdrawal_trans_pay_by' => $withdrawal_trans_pay_by, 'newwithdrawal_trans_pay_by' => $newwithdrawal_trans_pay_by, 'deposit_trans_pay_by' => $deposit_trans_pay_by));
            Session::flash('success_message', "Merchant setting updated successfully.");
            return Redirect::to('admin/merchants/merchantSetting/' . $slug);
        }
        return view('admin.merchants.merchantSetting', ['title' => $pageTitle, $activetab => 1, 'userInfo' => $userInfo]);
    }

    public function kycdetail($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'kycdetail');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actmerchants';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'View User KYC Detail';
        $activetab = 'actmerchants';
        $user = User::where('slug', $slug)->first();


        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $currentTimestamp = $dt->format("Y-m-d\TH:i:s.v\Z");
        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;
        global $getStateId;
        $message = $currentTimestamp . $partner_id . "sid_request";
        $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));


        $userSlug = $user->unique_key;
        $userJobId = $user->jobId;
        $user_id = $user->id;
        $curl = curl_init();
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
                     "image_links": true,
                     "history": false
                }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $responseData = json_decode($response);
        if (isset($responseData->image_links) && $responseData->image_links == true) {
            $user->selfie_image = $responseData->image_links->selfie_image ?? null;
            $user->identity_front_image = $responseData->image_links->id_card_image ?? null;
            $user->identity_back_image = $responseData->image_links->id_card_back ?? null;
            $user->save();
        }

        // return view('admin.agents.kycdetail', ['title' => $pageTitle, $activetab => 1, 'userInfo' => $userInfo]);

        return view('admin.merchants.kycdetail', ['title' => $pageTitle, $activetab => 1, 'userInfo' => $user, 'imageData' => $responseData->image_links ?? null]);
    }

    public function approvekyc($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_kyc_done' => '1', 'kyc_status' => 'completed'));

            $userInfo = DB::table('users')->where('slug', $slug)->first();
            //
            //            $username = $userInfo->name;
            //            $emailId = $userInfo->email;
            //            $emailTemplate = DB::table('emailtemplates')->where('id', 6)->first();
            //            $toRepArray = array('[!username!]', '[!HTTP_PATH!]', '[!SITE_TITLE!]');
            //            $fromRepArray = array($username, HTTP_PATH, SITE_TITLE);
            //            $emailSubject = str_replace($toRepArray, $fromRepArray, $emailTemplate->subject);
            //            $emailBody = str_replace($toRepArray, $fromRepArray, $emailTemplate->template);
            //            Mail::to($emailId)->send(new SendMailable($emailBody, $emailSubject));

            $title = "KYC Approved";
            $message = "Congratulations! Your KYC Details Approved Successfully By Admin.";

            $title_fr = "Approuvé KYC";
            $message_fr = "Félicitations! Vos détails KYC approuvés avec succès par l'administrateur.";


            $device_type = $userInfo->device_type;
            $device_token = $userInfo->device_token;

            $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                'user_id' => $userInfo->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'notif_title_fr' => $title_fr,
                'notif_body_fr' => $message_fr,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            Session::flash('success_message', "User KYC approved successfully.");
            return Redirect::to('admin/merchants/kycdetail/' . $slug);
        }
    }

    public function declinekyc($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_kyc_done' => '2'));

            $userInfo = DB::table('users')->where('slug', $slug)->first();
            //
            //            $username = $userInfo->name;
            //            $emailId = $userInfo->email;
            //            $emailTemplate = DB::table('emailtemplates')->where('id', 5)->first();
            //            $toRepArray = array('[!username!]', '[!HTTP_PATH!]', '[!SITE_TITLE!]');
            //            $fromRepArray = array($username, HTTP_PATH, SITE_TITLE);
            //            $emailSubject = str_replace($toRepArray, $fromRepArray, $emailTemplate->subject);
            //            $emailBody = str_replace($toRepArray, $fromRepArray, $emailTemplate->template);
            //            Mail::to($emailId)->send(new SendMailable($emailBody, $emailSubject));

            $title = "KYC Declined";
            $message = "Oops! Your KYC Details Declined By Admin.";
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

            Session::flash('success_message', "User KYC declined successfully.");
            return Redirect::to('admin/merchants/kycdetail/' . $slug);
        }
    }

    public function activate($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_verify' => '1'));
            return view('elements.admin.update_status', ['action' => 'admin/merchants/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_verify' => '0', 'device_id' => ""));
            return view('elements.admin.update_status', ['action' => 'admin/merchants/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->delete();
            Session::flash('success_message', "Merchant user details deleted successfully.");
            return Redirect::to('admin/merchants');
        }
    }

    // public function deleteimage($slug = null) {
    //     if ($slug) {
    //         $recordInfo = DB::table('users')->where('slug', $slug)->select('users.profile_image')->first();
    //         User::where('slug', $slug)->update(array('profile_image' => ''));
    //         @unlink(PROFILE_FULL_UPLOAD_PATH . $recordInfo->profile_image);
    //         Session::flash('success_message', "Image deleted successfully.");
    //         return Redirect::to('admin/agents/edit/' . $slug);
    //     }
    // }

    public function deleteidentity($slug = null)
    {
        if ($slug) {
            $recordInfo = DB::table('users')->where('slug', $slug)->select('users.identity_front_image')->first();
            User::where('slug', $slug)->update(array('identity_front_image' => ''));
            @unlink(IDENTITY_FULL_UPLOAD_PATH . $recordInfo->identity_front_image);
            Session::flash('success_message', "Image deleted successfully.");
            return Redirect::to('admin/merchants/edit/' . $slug);
        }
    }

    public function deleteidentity1($slug = null)
    {
        if ($slug) {
            $recordInfo = DB::table('users')->where('slug', $slug)->select('users.identity_back_image')->first();
            User::where('slug', $slug)->update(array('identity_back_image' => ''));
            @unlink(IDENTITY_FULL_UPLOAD_PATH . $recordInfo->identity_back_image);
            Session::flash('success_message', "Image deleted successfully.");
            return Redirect::to('admin/merchants/edit/' . $slug);
        }
    }
    private function sendPushNotification($title, $message, $device_type, $device_token)
    {
        $push_notification_key = env('PUSH_NOTIFICATION_KEY');
        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array(
            "authorization: key=" . $push_notification_key . "",
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

    public function apiActivate($slug = null)
    {
        $useerr = User::where('slug', $slug)->first();
        if (!empty($useerr->api_key)) {
            $apikey = $useerr->api_key;
        } else {
            $apikey = $this->generate_string(12);
        }


        if ($slug) {
            User::where('slug', $slug)->update(array('api_enable' => 'Y', 'api_key' => $apikey, 'updated_at' => date('Y-m-d H:i:s')));
            return view('elements.admin.update_apistatus', ['action' => 'admin/merchants/api-deactivate/' . $slug, 'status' => 1, 'id' => $slug, 'apikey' => $apikey]);
        } else {
        }
    }

    public function apiDeactivate($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('api_enable' => 'N', 'updated_at' => date('Y-m-d H:i:s')));
            return view('elements.admin.update_apistatus', ['action' => 'admin/merchants/api-activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    private function generate_string($strength = 12)
    {
        $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    public function payClient(Request $request, $slug = null)
    {
    
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'payclient');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actmerchants';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Adjust User Wallet';
 
        $recordInfo = User::where('slug', $slug)->first();
       
        $pageTitle = ($recordInfo->isBulkUser == 1)
            ? 'Manage Bulk Payment Merchant' 
            : 'Manage Merchant Users';
        $activetab = ($recordInfo->isBulkUser == 1)
            ? 'bulkpaymentmerchant' 
            : 'actmerchants';

        $input = Input::all();

        // echo"<pre>";print_r($input);die;
        if (!empty($input)) {

            $rules = array(
                'wallet_action' => 'required',
                'amount' => 'required|numeric',
                'reason' => 'required',
            );

            $customMessages = [
                'wallet_amount.required' => 'Invalid Transaction Type',
                'amount.required' => 'Amount field can\'t be left blank.',
                'amount.numeric' => 'Invalid Amount',
                'reason.required' => 'Reason field can\'t be left blank.',
            ];
            $validator = Validator::make($input, $rules, $customMessages);

            if ($validator->fails()) {
                return Redirect::to('/admin/merchants/payclient/' . $slug)->withErrors($validator)->withInput();
            } else {
                $userCard = UserCard::where('userId', $recordInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                if ($input['wallet_action'] == "Withdraw") {
                    $remainBal = $input['amount'];
                    $adminBal = $input['amount'];

                    if ($input['amount'] > $recordInfo->wallet_balance) {
                        Session::flash('error_message', 'Insufficient Balance');
                        return Redirect::to('/admin/merchants/payclient/' . $slug);
                    }

                    $billing_description = '<br>Admin Withdraw<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];

                    $refrence_id = time() . rand() . Session::get('user_id');
                    $trans = new Transaction([
                        "user_id" => $recordInfo->id,
                        "receiver_id" => 1,
                        "amount" => $input['amount'],
                        "transaction_amount" => 0,
                        "currency" => $recordInfo->currency,
                        "trans_type" => 2, //Debit
                        "total_amount" => $input['amount'],
                        "payment_mode" => 'Withdraw',
                        "trans_for" => 'Admin',
                        "refrence_id" => $refrence_id,
                        "billing_description" => $billing_description,
                        "amount_value" => $remainBal,
                        "remainingWalletBalance" => ($recordInfo->wallet_balance - $input['amount']),
                        "runningBalance" => ($recordInfo->wallet_balance - $input['amount']),
                        "beforeBalance" => $recordInfo->wallet_balance,
                        "afterBalance" => ($recordInfo->wallet_balance - $input['amount']),
                        "status" => 1,
                        "edited_by" => Session::get('adminid'),
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);

                    $trans->save();
                    $TransId = $trans->id;

                    $admin = Admin::where('id', 1)->first();
                    $admin_wallet = $admin->wallet_balance + $adminBal;
                    Admin::where('id', 1)->update(['wallet_balance' => $admin_wallet, 'updated_at' => date('Y-m-d H:i:s')]);

                    $user_wallet = $recordInfo->wallet_balance - $remainBal;
                    $debit = new TransactionLedger([
                        'user_id' => $recordInfo->id,
                        'opening_balance' => $recordInfo->wallet_balance,
                        'amount' => $input['amount'],
                        'actual_amount' => $input['amount'],
                        'type' => 2,
                        'trans_id' => $TransId,
                        'payment_mode' => 'Admin Withdraw',
                        'closing_balance' => $user_wallet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $debit->save();

                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $input['amount'],
                            "transferType" => "CardToWallet",
                            "mobilePhoneNumber" => "241{$recordInfo->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    }


                    User::where('slug', $slug)->update(['wallet_balance' => $user_wallet, 'updated_at' => date('Y-m-d H:i:s')]);

                    $this->sendEmail([
                        'subjects' => 'Funds Transfer Details',

                        'senderName' => session('admin_username') ?? 'Swap Wallet',
                        'currency' => CURR,

                        'receiverName' => $recordInfo->name,
                        'receiverAmount' => $input['amount'],
                        'receiverEmail' => $recordInfo->email ?? '',

                        'transId' => $refrence_id,
                        'transactionFees' => 0,
                        'transactionDate' => date('d M, Y h:i A', strtotime($debit->created_at)),
                        'transactionStatus' => $this->getStatusText(1),

                    ], 'withdraw');

                    //Mail End
                } else if ($input['wallet_action'] == "Deposit") {

                    $remainBal = $input['amount'];
                    $adminBal = $input['amount'];
                    $admin = Admin::where('id', 1)->first();
                    $amount = $admin->wallet_balance;

                    if ($input['amount'] >= $amount) {
                        Session::flash('error_message', 'Insufficient Balance');
                        return Redirect::to('/admin/merchants/payclient/' . $slug);
                    }

                    $refrence_id = time() . rand() . Session::get('user_id');
                    $billing_description = '<br>Admin Deposit<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];

                    $trans = new Transaction([
                        "user_id" => 1,
                        "receiver_id" => $recordInfo->id,
                        "amount" => $input['amount'],
                        "transaction_amount" => 0,
                        "currency" => 'IQD',
                        "trans_type" => 1, //Credit
                        "total_amount" => $input['amount'],
                        "payment_mode" => 'Deposit',
                        "trans_for" => 'Admin',
                        "refrence_id" => $refrence_id,
                        "billing_description" => $billing_description,
                        "amount_value" => $remainBal,
                        "remainingWalletBalance" => ($recordInfo->wallet_balance + $input['amount']) ?? 0,
                        "runningBalance" => ($recordInfo->wallet_balance + $input['amount']) ?? 0,
                        "beforeBalance" => $recordInfo->wallet_balance ?? 0,
                        "afterBalance" => ($recordInfo->wallet_balance + $input['amount']) ?? 0,
                        "status" => 1,
                        "edited_by" => Session::get('adminid'),
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);


                    $trans->save();
                    $TransId = $trans->id;

                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $userCard->last4Digits,
                            "referenceMemo" => "Settlement",
                            "transferAmount" => $input['amount'],
                            "transferType" => "WalletToCard",
                            "mobilePhoneNumber" => "241{$recordInfo->phone}"
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    }

                    $admin_wallet = $amount - $adminBal;
                    Admin::where('id', 1)->update(['wallet_balance' => $admin_wallet, 'updated_at' => date('Y-m-d H:i:s')]);
                    $user_wallet = $recordInfo->wallet_balance + $remainBal;
                    $credit = new TransactionLedger([
                        'user_id' => $recordInfo->id,
                        'opening_balance' => $recordInfo->wallet_balance ?? 0,
                        'amount' => $input['amount'],
                        'actual_amount' => $input['amount'],
                        'type' => 1,
                        'trans_id' => $TransId,
                        'payment_mode' => 'Admin Deposit',
                        'closing_balance' => $user_wallet,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $credit->save();
                    User::where('slug', $slug)->update(['wallet_balance' => $user_wallet, 'updated_at' => date('Y-m-d H:i:s')]);

                    $this->sendEmail([
                        'subjects' => 'Funds Transfer Details',
                        'senderName' => session('admin_username') ?? 'Swap Wallet',

                        'currency' => CURR,

                        'senderName' => $recordInfo->name,
                        'senderAmount' => $input['amount'],
                        'senderEmail' => $recordInfo->email ?? '',

                        'transId' => $refrence_id,
                        'transactionFees' => 0,
                        'transactionDate' => date('d M, Y h:i A', strtotime($credit->created_at)),
                        'transactionStatus' => $this->getStatusText(1),
                    ], 'deposit');
                }

                Session::flash('success_message', "Client Balance Adjusted Successfully.");
                if ($recordInfo->user_type == 'Merchant') {
                    $path = ($recordInfo->isBulkUser == 1)
                    ? '/admin/bulk-payment-merchants'
                    : '/admin/merchants';

                    return Redirect::to($path);
                }
            }
        }

        return view('admin.merchants.payclient', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    // For bulk payment merchant
    public function bulkPaymentMerchantIndex(Request $request)
    {

        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'bulk-payment-merchants');

        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'bulkpaymentmerchant';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Bulk Payment Merchant';
        $activetab = 'bulkpaymentmerchant';

        $query = new User();
        $query = $query->sortable();
        $query = $query->where('user_type', 'Merchant')->where('isBulkUser', 1);

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                User::whereIn('id', $idList)->update(array('is_verify' => 1));
                Session::flash('success_message', "Records are activate successfully.");
            } else if ($action == "Deactivate") {
                User::whereIn('id', $idList)->update(array('is_verify' => 0));
                Session::flash('success_message', "Records are deactivate successfully.");
            } else if ($action == "Delete") {
                User::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('business_name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        $merchants = $query->orderBy('id', 'DESC')->paginate(20);

        $page = ($request->has('page'))
            ? $request->get('page')
            : 1;


        if ($request->ajax() || $page > 1) {
            return view('elements.admin.bulk-merchants.index', ['allrecords' => $merchants, 'page' => $page]);
        }

        return view('admin.bulk-merchants.index', [
            "$activetab" => 1,
            'title' => $pageTitle,
            'page' => $page,
            'allrecords' => $merchants,
        ]);
    }

    private function generatePassword($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters .= '0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function bulkPaymentMerchantAdd(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-bulk-payment-merchants');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'bulkpaymentmerchant';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add Merchant User';
        $activetab = 'bulkpaymentmerchant';

        $countrList = Country::getCountryList();
        $input = Input::all();

        if ( !empty($input) ) {
            $rules = array(
                'business_name' => ['required', 'max:50', 'regex:/^[a-zA-Z0-9\s]+$/'],
                'name' => ['required', 'max:50', 'regex:/^[a-zA-Z0-9\s]+$/'],
                'phone' => 'required|unique:users|min:6|max:15',
                'dob' => 'required',
                'email' => 'required|email|unique:users,email,',
            );
            
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/add-bulk-payment-merchants')->withErrors($validator)->withInput(Input::except('city'));
            } else {
                unset($input['phone_number']);
                $serialisedData = $this->serialiseFormData($input);
               
                $serialisedData['slug'] = $this->createSlug($input['name'], 'users');
                $serialisedData['is_kyc_done'] = 1;
                $serialisedData['kyc_status'] = 'completed';
                $serialisedData['is_verify'] = 1;
                $serialisedData['otp_verify'] = 1;
                $serialisedData['user_type'] = 'Merchant';
                $serialisedData['isBulkUser'] = 1;
                // $serialisedData['password'] = Hash::make($password);

                User::insert($serialisedData);

                $user_id = DB::getPdo()->lastInsertId();
                $qrString = $user_id . "##" . $input['name'];
                $qrCode = $this->generateQRCode($qrString, $user_id);

                User::where('id', $user_id)->update(array('qr_code' => $qrCode));

                $name = $input['name'];
                $emailId = $input['email'];

                $encId = "https://bulk.swap-africa.net/generate-password/" . base64_encode($this->encryptContent($user_id));
                $emailSubject = "Swap Wallet - Account created successfully";
                $emailData['subject'] = $emailSubject;
                $emailData['name'] = ucfirst($name);
                $emailData['email'] = $input['email'];
                $emailData['link'] = $encId;

                /* Mail::send('emails.generatePasswordLink', $emailData, function ($message) use ($emailData, $emailId) {
                    $message->to($emailId, $emailId)
                            ->subject($emailData['subject']);
                }); */

                Session::flash('success_message', "Bulk Payment Merchant user details saved successfully.");
                return Redirect::to('admin/bulk-payment-merchants');
            }
        }

        return view('admin.bulk-merchants.add', [
            "$activetab" => 1,
            'title' => $pageTitle,
            'countrList' => $countrList
        ]);

    }




}
