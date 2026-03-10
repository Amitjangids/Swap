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
use App\Mail\SendMailable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Laravel\Passport\Token;
use App\Models\CompanyTransaction;
use DateTime;
use DateTimeZone;

class AgentsController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_adminlogin');
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
        if ($type == 'deposit') {
            /* if ($emailData["senderEmail"] != '') {
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
            } */
        }

        if ($type == 'withdraw') {
            /* if ($emailData['receiverEmail'] != "") {
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
            } */
        }

        return true;
    }

    private function generateQRCode($qrString, $user_id)
    {
        $output_file = 'uploads/qr-code/' . $user_id . '-qrcode-' . time() . '.png';
        $image = \QrCode::format('png')
            ->size(200)->errorCorrection('H')
            ->generate($qrString, base_path() . '/public/' . $output_file);
        return $output_file;
    }

    public function index(Request $request)
    {

        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'agents');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actagents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Manage Agent Users';
        $activetab = 'actagents';
        $query = new User();
        $query = $query->sortable();
        $query = $query->where('user_type', 'Agent')->where('is_account_deleted', '1');
        $role_id = Session::get('admin_role');
        if ($role_id == env('SUPER_AGGREGATOR_ID')) {
            $super_agregateur = Admin::where('id', Session::get('adminid'))->pluck('id')->toarray();
            $company_code = Admin::whereIn('parent_id', $super_agregateur)->pluck('company_code')->toarray();
            $query = $query->whereIn('company_code', $company_code);
        }
        if ($role_id == env('AGGREGATOR_ID')) {
            $company_code = Admin::where('id', Session::get('adminid'))->pluck('company_code')->toarray();
            $query = $query->whereIn('company_code', $company_code);
        }

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
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhereHas('Company', function ($q) use ($keyword) {
                        $q->where('company_name', 'like', '%' . $keyword . '%')
                            ->orWhere('company_code', 'like', '%' . $keyword . '%');
                    });
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

        $agents = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }
        if ($request->ajax() || $page > 1) {
            return view('elements.admin.agents.index', ['allrecords' => $agents, 'page' => $page]);
        }
        return view('admin.agents.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $agents, 'page' => $page]);
    }

    public function add()
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-agents');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actagents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add Agent User';
        $activetab = 'actagents';

        $countrList = Country::getCountryList();

        $role_id = Session::get('admin_role');
        if ($role_id == env('SUPER_AGGREGATOR_ID')) {
            $super_agregateur = Admin::where('id', Session::get('adminid'))->pluck('id')->toarray();
            $company_code = Admin::whereIn('parent_id', $super_agregateur)->pluck('company_name', 'company_code')->toarray();
        } else if ($role_id == env('AGGREGATOR_ID')) {
            $company_code = Admin::where('id', Session::get('adminid'))->pluck('company_name', 'company_code')->toarray();
        } else {
            $company_code = Admin::where('parent_id', "!=", 0)->pluck('company_name', 'company_code')->toarray();
        }


        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'name' => 'required|max:50',
                'phone' => 'required|min:6|max:15|unique:users,phone,NULL,id,is_account_deleted,1',
                'dob' => 'required',
                'company_code' => 'required',
                // 'email' => 'required|email|unique:users,email,',

            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/agents/add-agents')->withErrors($validator)->withInput(Input::except('city'));
            } else {

                unset($input['phone_number']);


                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['slug'] = $this->createSlug($input['name'], 'users');
                $serialisedData['is_kyc_done'] = 0;
                $serialisedData['is_verify'] = 1;
                $serialisedData['otp_verify'] = 1;
                $serialisedData['user_type'] = 'Agent';
                $serialisedData['company_code'] = $input['company_code'];

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

                Session::flash('success_message', "Agent user details saved successfully.");
                return Redirect::to('admin/agents');
            }
        }
        return view('admin.agents.add', ['title' => $pageTitle, $activetab => 1, 'countrList' => $countrList, 'company_code' => $company_code]);
    }



    public function edit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-agents');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actagents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Edit Agent User';
        $activetab = 'actagents';
        $countrList = Country::getCountryList();

        $recordInfo = User::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/agents');
        }

        $role_id = Session::get('admin_role');
        if ($role_id == env('SUPER_AGGREGATOR_ID')) {
            $super_agregateur = Admin::where('id', Session::get('adminid'))->pluck('id')->toarray();
            $company_code = Admin::whereIn('parent_id', $super_agregateur)->pluck('company_name', 'company_code')->toarray();
        } else if ($role_id == env('AGGREGATOR_ID')) {
            $company_code = Admin::where('id', Session::get('adminid'))->pluck('company_name', 'company_code')->toarray();
        } else {
            $company_code = Admin::where('parent_id', "!=", 0)->pluck('company_name', 'company_code')->toarray();
        }

        $input = Input::all();
        if (!empty($input)) {
            // Define validation rules
            $rules = [
                'name' => 'required|max:50',
                'phone' => 'required|min:6|max:15|unique:users,phone,' . $recordInfo->id,
                'company_code' => 'required',
                // 'email' => 'required|email|unique:users,email,' . $recordInfo->id,
            ];

            if (!empty($recordInfo->email)) {
                $rules['email'] = 'email|unique:users,email,' . $recordInfo->id;
            }


            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/agents/edit-agents/' . $slug)->withErrors($validator)->withInput();
            } else {
                // Update user details
                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                User::where('id', $recordInfo->id)->update($serialisedData);

                // Generate QR code
                $qrString = $recordInfo->id . "##" . $recordInfo->name;
                $qrCode = $this->generateQRCode($qrString, $recordInfo->id);
                User::where('id', $recordInfo->id)->update(['qr_code' => $qrCode]);

                Session::flash('success_message', "Agent user details updated successfully.");
                return Redirect::to('admin/agents');
            }
        }
        return view('admin.agents.edit', ['title' => $pageTitle, $activetab => 1, 'countrList' => $countrList, 'recordInfo' => $recordInfo, 'company_code' => $company_code]);
    }


    public function kycdetail($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'kycdetail');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actagents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'View User KYC Detail';
        $activetab = 'actagents';
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
        return view('admin.agents.kycdetail', ['title' => $pageTitle, $activetab => 1, 'userInfo' => $user, 'imageData' => $responseData->image_links ?? null]);
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

            Session::flash('success_message', "User KYC approved successfully.");
            return Redirect::to('admin/agents/kycdetail/' . $slug);
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
            //                echo '<pre>';print_r($result);exit;

            $notif = new Notification([
                'user_id' => $userInfo->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            Session::flash('success_message', "User KYC declined successfully.");
            return Redirect::to('admin/agents/kycdetail/' . $slug);
        }
    }

    public function activate($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_verify' => '1'));
            return view('elements.admin.update_status', ['action' => 'admin/agents/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_verify' => '0', 'device_id' => ""));
            return view('elements.admin.update_status', ['action' => 'admin/agents/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->delete();
            Session::flash('success_message', "Agent user details deleted successfully.");
            return Redirect::to('admin/agents');
        }
    }

    public function deleteimage($slug = null)
    {
        if ($slug) {
            $recordInfo = DB::table('users')->where('slug', $slug)->select('users.profile_image')->first();
            User::where('slug', $slug)->update(array('profile_image' => ''));
            @unlink(PROFILE_FULL_UPLOAD_PATH . $recordInfo->profile_image);
            Session::flash('success_message', "Image deleted successfully.");
            return Redirect::to('admin/agents/edit/' . $slug);
        }
    }

    public function deleteidentity($slug = null)
    {
        if ($slug) {
            $recordInfo = DB::table('users')->where('slug', $slug)->select('users.identity_front_image')->first();
            User::where('slug', $slug)->update(array('identity_front_image' => ''));
            @unlink(IDENTITY_FULL_UPLOAD_PATH . $recordInfo->identity_front_image);
            Session::flash('success_message', "Image deleted successfully.");
            return Redirect::to('admin/agents/edit/' . $slug);
        }
    }

    public function deleteidentity1($slug = null)
    {
        if ($slug) {
            $recordInfo = DB::table('users')->where('slug', $slug)->select('users.identity_back_image')->first();
            User::where('slug', $slug)->update(array('identity_back_image' => ''));
            @unlink(IDENTITY_FULL_UPLOAD_PATH . $recordInfo->identity_back_image);
            Session::flash('success_message', "Image deleted successfully.");
            return Redirect::to('admin/agents/edit/' . $slug);
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



    // Sub Agent 

    public function subAgentIndex(Request $request, $slug)
    {
        $data = User::where('slug', $slug)->first();
        // $isPermitted = $this->validatePermission(Session::get('admin_role'), 'kycdetail');
        // if ($isPermitted == false) {
        //     $pageTitle = 'Not Permitted';
        //     $activetab = 'actagents';
        //     return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        // }

        $pageTitle = 'Manage Sub-Agent Users';
        $activetab = 'actagents';
        $query = new User();
        $query = $query->sortable();
        $query = $query->where('user_type', 'Sub-Agent')->where('parent_id', $data->id);

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

        // echo"<pre>";print_r($data);die;
        $agents = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }
        if ($request->ajax() || $page > 1) {
            return view('elements.admin.agents.sub-agent-index', ['allrecords' => $agents, 'page' => $page, 'data' => $data]);
        }
        return view('admin.agents.subagent-index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $agents, 'page' => $page, 'data' => $data]);
    }




    public function subAgentAdd($slug)
    {
        $data = User::where('slug', $slug)->first();
        // echo"<pre>";print_r($data);die;
        // $access = $this->getRoles(Session::get('adminid'),3);
        // if($access == 0){
        //     return Redirect::to('admin/admins/dashboard');
        // }

        $pageTitle = 'Add Sub-Agent User';
        $activetab = 'actagents';

        $countrList = Country::getCountryList();
        $cityList = City::getCityList();
        $areaList = array();
        $input = Input::all();
        if (!empty($input)) {
            //echo '<pre>';print_r($input);exit;
            $rules = array(
                'name' => 'required|max:50',
                'phone' => 'required|unique:users',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'confirm_password' => 'required|same:password',
                'profile_image' => 'mimes:jpeg,png,jpg',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/agents/add')->withErrors($validator)->withInput(Input::except('city'));
            } else {

                unset($input['phone_number']);

                if (Input::hasFile('profile_image')) {
                    $file = Input::file('profile_image');
                    $uploadedFileName = $this->uploadImage($file, PROFILE_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, PROFILE_FULL_UPLOAD_PATH, PROFILE_SMALL_UPLOAD_PATH, PROFILE_MW, PROFILE_MH);
                    $input['profile_image'] = $uploadedFileName;
                } else {
                    unset($input['profile_image']);
                }

                if (Input::hasFile('identity_image')) {
                    $file = Input::file('identity_image');
                    $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                    $input['identity_image'] = $uploadedFileName;
                } else {
                    unset($input['identity_image']);
                }

                $input['national_identity_number'] = $input['national_identity_number'] ? Crypt::encryptString($input['national_identity_number']) : '';

                if (!empty($input['national_identity_number']) || isset($input['identity_image'])) {
                    $status = 4;
                } else {
                    $status = 3;
                }

                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['slug'] = $this->createSlug($input['name'], 'users');
                $serialisedData['is_kyc_done'] = $status;
                $serialisedData['is_verify'] = 1;
                $serialisedData['user_type'] = 'Sub-Agent';
                $serialisedData['password'] = $this->encpassword($input['password']);
                $serialisedData['parent_id'] = $data->id;;
                User::insert($serialisedData);

                $user_id = DB::getPdo()->lastInsertId();
                $qrString = $user_id . "##" . $input['name'];
                $qrCode = $this->generateQRCode($qrString, $user_id);

                User::where('id', $user_id)->update(array('qr_code' => $qrCode));

                // $parent_id=$data->id;
                $name = $input['name'];
                $emailId = $input['email'];
                $new_password = $input['password'];

                $emailTemplate = DB::table('emailtemplates')->where('id', 2)->first();
                $toRepArray = array('[!email!]', '[!name!]', '[!username!]', '[!password!]', '[!HTTP_PATH!]', '[!SITE_TITLE!]');
                $fromRepArray = array($emailId, $name, $name, $new_password, HTTP_PATH, SITE_TITLE);
                $emailSubject = str_replace($toRepArray, $fromRepArray, $emailTemplate->subject);
                $emailBody = str_replace($toRepArray, $fromRepArray, $emailTemplate->template);
                // Mail::to($emailId)->send(new SendMailable($emailBody, $emailSubject));

                Session::flash('success_message', "Agent user details saved successfully.");
                return Redirect::to('admin/agents');
            }
        }
        return view('admin.agents.subagent-add', ['title' => $pageTitle, $activetab => 1, 'countrList' => $countrList, 'cityList' => $cityList, 'areaList' => $areaList, 'data' => $data]);
    }



    public function subAgentEdit($slug = null)
    {
        // $access = $this->getRoles(Session::get('adminid'),3);
        // if($access == 0){
        //     return Redirect::to('admin/admins/dashboard');
        // }

        $pageTitle = 'Edit Sub-Agent User';
        $activetab = 'actagents';
        $countrList = Country::getCountryList();
        $cityList = City::getCityList();



        $recordInfo = User::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/agents');
        }

        $areaList = array();
        if ($recordInfo->city) {
            $areaList = Area::getAreaList($recordInfo->city);
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'name' => 'required|max:50',
                'email' => 'required|email|unique:users,email,' . $recordInfo->id,
                'profile_image' => 'mimes:jpeg,png,jpg',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/agents/edit/' . $slug)->withErrors($validator)->withInput();
            } else {


                if (Input::hasFile('profile_image')) {
                    $file = Input::file('profile_image');
                    $uploadedFileName = $this->uploadImage($file, PROFILE_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, PROFILE_FULL_UPLOAD_PATH, PROFILE_SMALL_UPLOAD_PATH, PROFILE_MW, PROFILE_MH);
                    $input['profile_image'] = $uploadedFileName;
                    @unlink(PROFILE_FULL_UPLOAD_PATH . $recordInfo->profile_image);
                } else {
                    unset($input['profile_image']);
                }

                if (Input::hasFile('identity_image')) {
                    $file = Input::file('identity_image');
                    $uploadedFileName = $this->uploadImage($file, IDENTITY_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, IDENTITY_FULL_UPLOAD_PATH, IDENTITY_SMALL_UPLOAD_PATH, IDENTITY_MW, IDENTITY_MH);
                    $input['identity_image'] = $uploadedFileName;
                    @unlink(IDENTITY_FULL_UPLOAD_PATH . $recordInfo->identity_image);
                } else {
                    unset($input['identity_image']);
                }

                if ($input['password']) {
                    $input['password'] = $this->encpassword($input['password']);
                } else {
                    unset($input['password']);
                }
                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                User::where('id', $recordInfo->id)->update($serialisedData);

                //                $user_id = $recordInfo->id;
                //                $qrString = $user_id . "##" . $recordInfo->name;
                //                $qrCode = $this->generateQRCode($qrString, $user_id);
                //                
                //                User::where('id', $user_id)->update(array('qr_code' => $qrCode));

                Session::flash('success_message', "Agent user details updated successfully.");
                return Redirect::to('admin/agents');
            }
        }
        return view('admin.agents.subagent-edit', ['title' => $pageTitle, $activetab => 1, 'countrList' => $countrList, 'recordInfo' => $recordInfo, 'cityList' => $cityList, 'areaList' => $areaList]);
    }




    public function payClient(Request $request, $slug = null)
    {

        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'payclient');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actagents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Manage Agent Users';
        $activetab = 'actagents';


        $pageTitle = 'Adjust User Wallet';

        $recordInfo = User::where('slug', $slug)->first();

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
                return Redirect::to('/admin/agents/payclient/' . $slug)->withErrors($validator)->withInput();
            } else {
                $userCard = UserCard::where('userId', $recordInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();

                if ($input['wallet_action'] == "Withdraw") {
                    $remainBal = $input['amount'];
                    $adminBal = $input['amount'];


                    if ($input['amount'] > $recordInfo->wallet_balance) {
                        Session::flash('error_message', 'Insufficient Balance');
                        return Redirect::to('/admin/agents/payclient/' . $slug);
                    }

                    $billing_description = '<br>Agent Withdraw<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];
                    $refrence_id = time() . rand() . Session::get('user_id');
                    $trans = new Transaction([
                        "user_id" => $recordInfo->id,
                        "receiver_id" => 1,
                        "trans_to" => Session::get('adminid'),
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
                    // echo"<pre>";print_r($trans);die;
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

                    $transs = new CompanyTransaction([
                        "user_id" =>  Session::get('adminid'),
                        "receiver_id" => Session::get('adminid'),
                        "amount" => $input['amount'],
                        "trans_type" => 1, //debit
                        "payment_mode" => 'Deposit',
                        "refrence_id" => $refrence_id,
                        "billing_description" => $billing_description,
                        "status" => 1,
                        "trans_id" => $TransId,
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);

                    $transs->save();

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


                } else if ($input['wallet_action'] == "Deposit") {
                    $remainBal = $input['amount'];
                    $adminBal = $input['amount'];
                    $admin = Admin::where('id', Session::get('adminid'))->first();
                    $amount = $admin->wallet_balance;

                    if ($input['amount'] >= $amount) {
                        Session::flash('error_message', 'Insufficient Balance');
                        return Redirect::to('/admin/agents/payclient/' . $slug);
                    }


                    $refrence_id = time() . rand() . Session::get('user_id');
                    $billing_description = '<br>Agent Deposit<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];
                    $trans = new Transaction([
                        "user_id" => 1,
                        "receiver_id" => $recordInfo->id,
                        "trans_to" => Session::get('adminid'),
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
                    Admin::where('id',  Session::get('adminid'))->update(['wallet_balance' => $admin_wallet, 'updated_at' => date('Y-m-d H:i:s')]);
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

                    $transs = new CompanyTransaction([
                        "user_id" =>  Session::get('adminid'),
                        "receiver_id" => Session::get('adminid'),
                        "amount" => $input['amount'],
                        "trans_type" => 2, //Credit
                        "payment_mode" => 'Withdraw',
                        "refrence_id" => $refrence_id,
                        "billing_description" => $billing_description,
                        "status" => 1,
                        "trans_id" => $TransId,
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);
                    $transs->save();

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
                if ($recordInfo->user_type == 'Agent') {
                    return Redirect::to('/admin/agents');
                }
            }
        }

        return view('admin.agents.payclient', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    public function removeAgent($slug)
    {
        $user = User::where('slug', $slug)->first();
        // $accessToken = Token::where('user_id', $user->id)->first();
        // if ($accessToken) {
        //     $accessToken->revoke();
        // }
        Token::where('user_id', $user->id)->delete();
        $user->is_account_deleted = 0;
        $user->save();
        Session::flash('success_message', "Removed account successfully from the network list.");
        return Redirect::to('/admin/agents');
    }

    public function reports(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'reports');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actearnings';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Balance Management';
        $activetab = 'actearningsreports';

        //to calculate summary
        $query1 = new Transaction();
        $query1 = $query1->where('status', 1);
        $query2 = clone $query1;
        $query3 = clone $query1;
        $role_id = Session::get('admin_role');
        $fundTransfer = 0;
        $sendMoney = 0;
        if ($role_id == 1) {
            $fundTransfer = intval($query2->whereHas('User', function ($q) {
                $q->where('user_type', 'agent');
            })->where('trans_type', 1)->where('payment_mode', 'Agent Deposit')->sum('transaction_amount'));

            $sendMoney = intval($query3->whereHas('Receiver', function ($q) {
                $q->where('user_type', 'agent');
            })->where('trans_type', 2)->where('payment_mode', 'Withdraw')->sum('transaction_amount'));
        } elseif ($role_id == env('SUPER_AGGREGATOR_ID')) {
            $super_agregateur = Admin::where('id', Session::get('adminid'))->pluck('id')->toarray();
            $company_code = Admin::whereIn('parent_id', $super_agregateur)->pluck('company_code')->toarray();
            $fundTransfer = intval($query2->whereHas('User', function ($q) use ($company_code) {
                $q->where('user_type', 'agent')->whereIn('company_code', $company_code);
            })->where('trans_type', 1)->where('payment_mode', 'Agent Deposit')->sum('transaction_amount'));

            $sendMoney = intval($query3->whereHas('Receiver', function ($q) use ($company_code) {
                $q->where('user_type', 'agent')->whereIn('company_code', $company_code);
            })->where('trans_type', 2)->where('payment_mode', 'Withdraw')->sum('transaction_amount'));
        } elseif ($role_id == env('AGGREGATOR_ID')) {

            $company_code = Admin::where('id', Session::get('adminid'))->pluck('company_code')->toarray();
            $fundTransfer = intval($query2->whereHas('User', function ($q) use ($company_code) {
                $q->where('user_type', 'agent')->whereIn('company_code', $company_code);
            })->where('trans_type', 1)->where('payment_mode', 'Agent Deposit')->sum('transaction_amount'));

            $sendMoney = intval($query3->whereHas('Receiver', function ($q) use ($company_code) {
                $q->where('user_type', 'agent')->whereIn('company_code', $company_code);
            })->where('trans_type', 2)->where('payment_mode', 'Withdraw')->sum('transaction_amount'));
        }

        $total_earning = intval($fundTransfer + $sendMoney);

        // DB::enableQueryLog();
        $query = new Transaction();

        $query = $query->sortable();

        if ($role_id == 1) {
            $query = $query->where(function ($q) {
                $q->whereHas('User', function ($subQuery) {
                    $subQuery->where('user_type', 'agent')
                        ->where('trans_type', 1)
                        ->where('payment_mode', 'Agent Deposit');
                });
            });
            $query = $query->orWhere(function ($q) {
                $q->whereHas('Receiver', function ($subQuery) {
                    $subQuery->where('user_type', 'agent')
                        ->where('trans_type', 2)
                        ->where('payment_mode', 'Withdraw');
                });
            });


            //     $query = $query->whereHas('User', function($q){
            //           $q->where('user_type', 'agent')->where('trans_type',1)->where('payment_mode','Agent Deposit');
            //     });

            //     $query = $query->orWhereHas('Receiver', function($q){
            //         $q->where('user_type', 'agent')->where('trans_type',2)->where('payment_mode','Withdraw');
            //   });
        } elseif ($role_id == env('SUPER_AGGREGATOR_ID')) {
            $super_agregateur = Admin::where('id', Session::get('adminid'))->pluck('id')->toarray();
            $company_code = Admin::whereIn('parent_id', $super_agregateur)->pluck('company_code')->toarray();

            $query = $query->where(function ($q) use ($company_code) {
                $q->whereHas('User', function ($subQuery) use ($company_code) {
                    $subQuery->where('user_type', 'agent')
                        ->whereIn('company_code', $company_code)
                        ->where('trans_type', 1)
                        ->where('payment_mode', 'Agent Deposit');
                });
            })->orWhere(function ($q) use ($company_code) {
                $q->whereHas('Receiver', function ($subQuery) use ($company_code) {
                    $subQuery->where('user_type', 'agent')
                        ->whereIn('company_code', $company_code)
                        ->where('trans_type', 2)
                        ->where('payment_mode', 'Withdraw');
                });
            });
            // $query = $query->whereHas('User', function($q) use($company_code){
            //     $q->where('user_type', 'agent')->whereIn('company_code',$company_code);
            // })->where('trans_type',1)->where('payment_mode','Agent Deposit');
            // $query = $query->orWhereHas('Receiver', function($q) use($company_code){
            //     $q->where('user_type', 'agent')->whereIn('company_code',$company_code);
            // })->where('trans_type',2)->where('payment_mode','Withdraw');
        } elseif ($role_id == env('AGGREGATOR_ID')) {
            $company_code = Admin::where('id', Session::get('adminid'))->pluck('company_code')->toarray();

            $query = $query->where(function ($q) use ($company_code) {
                $q->whereHas('User', function ($subQuery) use ($company_code) {
                    $subQuery->where('user_type', 'agent')
                        ->whereIn('company_code', $company_code)
                        ->where('trans_type', 1)
                        ->where('payment_mode', 'Agent Deposit');
                });
            })->orWhere(function ($q) use ($company_code) {
                $q->whereHas('Receiver', function ($subQuery) use ($company_code) {
                    $subQuery->where('user_type', 'agent')
                        ->whereIn('company_code', $company_code)
                        ->where('trans_type', 2)
                        ->where('payment_mode', 'Withdraw');
                });
            });

            //   $query = $query->whereHas('User', function($q) use($company_code){
            //     $q->where('user_type', 'agent')->whereIn('company_code',$company_code);
            //   })->where('trans_type',1)->where('payment_mode','Agent Deposit');
            //   $query = $query->orWhereHas('Receiver', function($q) use($company_code){
            //     $q->where('user_type', 'agent')->whereIn('company_code',$company_code);
            //     })->where('trans_type',2)->where('payment_mode','Withdraw');
        }

        $query = $query->where('status', 1);

        $query = $query->where('transaction_amount', '!=', '0.00');

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%')
                    ->orWhere('refrence_id', 'like', '%' . $keyword . '%');
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

        $total['total'] = $query->orderBy('id', 'DESC')->sum('total_amount');
        $total['total_fee'] = $query->orderBy('id', 'DESC')->sum('transaction_amount');

        $users = $query->orderBy('id', 'DESC')->paginate(20);
        // echo '<pre>';print_r($users);exit;

        $transactionTotal = $query->select(DB::raw("SUM(transaction_amount) as transactionTotal"))->orderBy('id', 'DESC')->first();
        //        echo '<pre>';print_r($transactionTotal);exit;
        if ($request->ajax()) {
            return view('elements.admin.agents.earning', ['allrecords' => $users, 'transactionTotal' => $transactionTotal, 'total' => $total]);
        }
        return view('admin.agents.earning', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'transactionTotal' => $transactionTotal, 'total' => $total, 'fundTransfer' => CURR . ' ' . $fundTransfer, 'sendMoney' => CURR . ' ' . $sendMoney, 'totalEarning' => CURR . ' ' . $total_earning]);
    }
}
