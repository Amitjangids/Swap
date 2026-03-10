<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Cookie;
use Session;
use Redirect;
use Input;
use Validator;
use DB;
use IsAdmin;
use Mail;
use App\Mail\SendMailable;
use App\Models\Carddetail;
use App\Role;
use App\Permission;
use App\Models\Admin;
use App\Models\CompanyTransaction;
use App\Http\Controllers\Admin\Auth;
use Illuminate\Support\Str;
use App\Models\Transaction;


class AdminsController extends Controller
{

    public function __construct()
    {
        $this->middleware('adminlogedin', ['only' => ['login', 'forgotPassword']]);
        $this->middleware('is_adminlogin', ['except' => ['logout', 'login', 'forgotPassword']]);
    }

    public function login(Request $request)
    {
        $pageTitle = 'Admin Login';
        $input = $request->all();

        if (!empty($input)) {
            //  echo"<pre>";print_r($input);die;         
            $rules = array(
                'username' => 'required',
                'password' => 'required',
                'g-recaptcha-response' => 'required'
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/login')->withErrors($validator)->withInput(request()->except('password'));
            } else {
                $adminInfo = DB::table('admins')->where('username', $input['username'])->first();
                //    echo"<pre>";print_r($adminInfo);die;           
                if (!empty($adminInfo)) {

                    if (password_verify($input['password'], $adminInfo->password)) {
                        if ($adminInfo->status == 0) {
                            $error = 'Your account got temporary disabled.';
                        } else {
                            if (isset($input['remember']) && $input['remember'] == '1') {
                                Cookie::queue('admin_username', $adminInfo->username, time() + 60 * 60 * 24 * 7, "/");
                                Cookie::queue('admin_password', $input['password'], time() + 60 * 60 * 24 * 7, "/");
                                Cookie::queue('admin_remember', '1', time() + 60 * 60 * 24 * 100, "/");
                            } else {
                                Cookie::queue('admin_username', '', time() + 60 * 60 * 24 * 7, "/");
                                Cookie::queue('admin_password', '', time() + 60 * 60 * 24 * 7, "/");
                                Cookie::queue('admin_remember', '', time() + 60 * 60 * 24 * 7, "/");
                            }
                            Session::put('adminid', $adminInfo->id);
                            Session::put('admin_username', $adminInfo->username);
                            Session::put('admin_role', $adminInfo->role_id);
                            DB::table('admins')->update(['activation_status' => '1']);
                            $usertype = 'Subadmin';
                            if ($adminInfo->id == 1) {
                                $usertype = 'Admin';
                            }

                            Session::put('admin_usertype', $usertype);
                            return Redirect::to('admin/admins/dashboard');
                        }
                    } else {
                        $error = 'Invalid username or password.';
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
                return Redirect::to('/admin/login')->withErrors($error)->withInput(request()->except('password'));
            }
        }
        return view('admin.admins.login', ['title' => $pageTitle]);
    }

    public function forgotPassword(Request $request)
    {

        $pageTitle = 'Admin Forgot Password';
        $input = $request->all();
        if (!empty($input)) {
            $rules = array(
                'email' => 'required|email'
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/admins/forgot-password')->withErrors($validator);
            } else {
                $adminInfo = DB::table('admins')->where('email', $input['email'])->first();
                if (!empty($adminInfo)) {
                    $plainPassword  = $this->getRandString(8);
                    $new_password = $this->encpassword($plainPassword);
                    DB::table('admins')->where('id', $adminInfo->id)->update(array('password' => $new_password));

                    $username = $adminInfo->username;
                    $emailId =  $adminInfo->email;
                    $emailTemplate = DB::table('emailtemplates')->where('id', 1)->first();
                    $toRepArray = array('[!email!]', '[!username!]', '[!password!]', '[!HTTP_PATH!]', '[!SITE_TITLE!]');
                    $fromRepArray = array($emailId, $username, $plainPassword, HTTP_PATH, SITE_TITLE);
                    $emailSubject = str_replace($toRepArray, $fromRepArray, $emailTemplate->subject);
                    $emailBody = str_replace($toRepArray, $fromRepArray, $emailTemplate->template);
                    // Mail::to($emailId)->send(new SendMailable($emailBody, $emailSubject));

                    Session::flash('success_message', "A new password has been sent to your email address.");
                    return Redirect::to('admin/admins/login');
                } else {
                    $error = 'Invalid email address, please enter correct email address.';
                }
                return Redirect::to('/admin/admins/forgot-password')->withErrors($error);
            }
        }
        return view('admin.admins.forgotPassword', ['title' => $pageTitle]);
    }

    public function logout()
    {
        // session_start();
        // session_destroy();
        Session::forget('adminid');
        Session::save();
        Session::flash('success_message', "Logout successfully.");
        return Redirect::to('admin/admins/login');
    }

    private function getStatusText($status)
    {
        $statusArr = array('1' => 'Completed', '2' => 'Pending', '3' => 'Failed', '4' => 'Rejected', '5' => 'Refund', '6' => 'Refund Completed');
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
                            $emailData["receiverEmail"],
                            $emailData["receiverEmail"],
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
                            $emailData["receiverEmail"],
                            $emailData["senderEmail"],
                            $emailData["senderEmail"]
                        )
                            ->subject($emailData["subjects"]);
                    }
                );
            } */
        }

        return true;
    }

    public function dashboard()
    {

        $pageTitle = 'Admin Dashboard';
        $dadhboardData = array();
        $role_id = Session::get('admin_role');
        if ($role_id == env('AGGREGATOR_ID')) {
            $adminInfo = Admin::where('id', Session::get('adminid'))->first();
        } else {
            $adminInfo = Admin::where('id', 1)->first();
        }
        $dadhboardData['users_count'] = DB::table('users')->where('user_type', 'User')->count();

        $dadhboardData['agents_count'] =  $adminInfo->company_name != "" ? DB::table('users')->where('company_code', $adminInfo->company_code)->where('user_type', 'Agent')->where('is_account_deleted', '1')->count() : DB::table('users')->where('is_account_deleted', '1')->where('user_type', 'Agent')->count();

        $dadhboardData['merchants_count'] = DB::table('users')->where('user_type', 'Merchant')->count();
        $dadhboardData['subadmins_count'] = DB::table('admins')->where('id', '!=', 1)->count();

        if ($adminInfo->company_code != "") {
            $company_code = $adminInfo->company_code;
            $dadhboardData['transactions_count'] = Transaction::where(function ($q) use ($company_code) {
                $q->whereHas('User', function ($subQuery) use ($company_code) {
                    $subQuery->where('user_type', 'agent')
                        ->where('company_code', $company_code)
                        ->where('trans_type', 1)
                        ->where('payment_mode', 'Agent Deposit');
                });
            })->orWhere(function ($q) use ($company_code) {
                $q->whereHas('Receiver', function ($subQuery) use ($company_code) {
                    $subQuery->where('user_type', 'agent')
                        ->where('company_code', $company_code)
                        ->where('trans_type', 2)
                        ->where('payment_mode', 'Withdraw');
                });
            })->count();
        } else {
            $dadhboardData['transactions_count'] = DB::table('transactions')->count();
        }

        $dadhboardData['scratchcards_count'] = DB::table('scratchcards')->count();
        $dadhboardData['login_users_count'] = DB::table('users')->where('login_status', 1)->count();
        $Adminwallet = DB::table('admins')->where('role_id', 1)->where('status', 1)->first();
        /*$query = new Carddetail();
        $query = $query->with('Card');
        $query->whereHas('Card',function($q){
                $q = $q->where('card_type', 2);
            });   
        $dadhboardData['mobilecards_count'] = $query->count();
        
        $query = new Carddetail();
        $query = $query->with('Card');
        $query->whereHas('Card',function($q){
                $q = $q->where('card_type', 1);
            });   
        $dadhboardData['internetcards_count'] = $query->count();
               
        $query = new Carddetail();
        $query = $query->with('Card');
        $query->whereHas('Card',function($q){
                $q = $q->where('card_type', 3);
            });   
        $dadhboardData['onlinecards_count'] = $query->count(); */
        $dadhboardData['mobilecards_count'] = DB::table('cards')->where('card_type', 2)->count();
        $dadhboardData['internetcards_count'] = DB::table('cards')->where('card_type', 1)->count();
        $dadhboardData['onlinecards_count'] = DB::table('cards')->where('card_type', 3)->count();
        return view('admin.admins.dashboard', ['title' => $pageTitle, 'actdashboard' => 1, 'dadhboardData' => $dadhboardData, 'Adminwallet' => $Adminwallet, 'adminInfo' => $adminInfo]);
    }

    public static function getRoles($id = null, $roleId = null)
    {
        if ($id) {
            $adminInfo = DB::table('admins')
                ->select('role_id')
                ->where('id', Session::get('adminid'))
                ->first();

            if ($adminInfo) {
                $role_ids = explode(',', $adminInfo->role_id);

                return $role_ids;
            } else {
                // Return null if adminInfo is not found
                return 0;
            }
        } else {
            // For admin with id 1, return null or an appropriate value
            return null;
        }
    }


    public function changeUsername(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'change-username');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actchangeusername';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Change Username';
        $activetab = 'actchangeusername';
        $adminInfo = DB::table('admins')->select('admins.username', 'admins.id')->where('id', Session::get('adminid'))->first();
        $input = $request->all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'old_username' => 'required|different:new_username',
                'new_username' => 'required',
                'confirm_username' => 'required|same:new_username'
            );
            $customMessages = ['different' => 'You can not change new username same as current username'];
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return view('admin.admins.changeUsername', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo])->withErrors($validator);
            } else {
                DB::table('admins')->where('id', $adminInfo->id)->update(array('username' => $input['new_username']));
                Session::put('admin_username', $input['new_username']);
                Session::flash('success_message', "Admin username updated successfully.");
                return Redirect::to('admin/admins/change-username');
            }
        }
        return view('admin.admins.changeUsername', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo]);
    }

    public function changePassword(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'change-password');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actchangepassword';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Change Password';
        $activetab = 'actchangepassword';
        $input = $request->all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'old_password' => 'required|different:new_password',
                'new_password' => 'required',
                'confirm_password' => 'required|same:new_password',
            );
            $customMessages = ['different' => 'You can not change new password same as current password.'];
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return view('admin.admins.changePassword', ['title' => $pageTitle, $activetab => 1])->withErrors($validator);
            } else {
                $adminInfo = DB::table('admins')->select('admins.password', 'admins.id')->where('id', Session::get('adminid'))->first();
                if (!password_verify($input['old_password'], $adminInfo->password)) {
                    $error = 'Current password is not correct.';
                    return view('admin.admins.changePassword', ['title' => $pageTitle, $activetab => 1])->withErrors($error);
                } else {
                    $new_password = bcrypt($input['new_password']);
                    DB::table('admins')->where('id', $adminInfo->id)->update(array('password' => $new_password));
                    Session::flash('success_message', "Admin password updated successfully.");
                    return Redirect::to('admin/admins/logout');
                }
            }
        }
        return view('admin.admins.changePassword', ['title' => $pageTitle, $activetab => 1]);
    }

    public function changeEmail(Request $request)
    {
        $pageTitle = 'Change Email';
        $activetab = 'actchangeemail';
        $adminInfo = DB::table('admins')->select('admins.email', 'admins.id')->where('id', Session::get('adminid'))->first();
        $input = $request->all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'old_email' => 'required|email|different:new_email',
                'new_email' => 'required|email',
                'confirm_email' => 'required|email|same:new_email'
            );
            $customMessages = ['different' => 'You can not change new email same as current email'];
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return view('admin.admins.changeEmail', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo])->withErrors($validator);
            } else {
                DB::table('admins')->where('id', $adminInfo->id)->update(array('email' => $input['new_email']));
                Session::flash('success_message', "Admin email updated successfully.");
                return Redirect::to('admin/admins/change-email');
            }
        }
        return view('admin.admins.changeEmail', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo]);
    }

    public function changeReferralBonus(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'change-referral-bonus');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actchangereferralbonus';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Change Referral Bonus';
        $activetab = 'actchangereferralbonus';
        $adminInfo = DB::table('admins')->select('admins.referralBonusSender','admins.referralBonusReceiver', 'admins.id')->where('id', Session::get('adminid'))->first();
        $input = $request->all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'referralBonusSender' => 'required',
                'referralBonusReceiver' => 'required'
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return view('admin.admins.change-referral-bonus', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo])->withErrors($validator);
            } else {
                DB::table('admins')->where('id', $adminInfo->id)->update(['referralBonusSender' => $input['referralBonusSender'],'referralBonusReceiver' => $input['referralBonusReceiver']]);
                Session::flash('success_message', "Referral updated successfully.");
                return Redirect::to('admin/admins/change-referral-bonus');
            }
        }
        return view('admin.admins.change-referral-bonus', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo]);
    }
    public function changeLimit(Request $request, $slug)
    {


        $pageTitle = 'Change Limit';

        if ($slug == "customer-transaction-limit") {
            $activetab = 'actchangelimit';
        } else if ($slug == "merchant-transaction-limit") {
            $activetab = 'actmerchantlimit';
        } else {
            $activetab = 'actagentlimit';
        }

        $isPermitted = $this->validatePermission(Session::get('admin_role'), $slug);
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = '$activetab';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $adminInfo = DB::table('transactions_limit')->where('slug', $slug)->first();

        $input = $request->all();
        if (!empty($input)) {

            $error = '';
            $rules = array(
                'minAirtel' => 'required',
                'maxAirtel' => 'required',
                'minDeposit' => 'required',
                'maxDeposit' => 'required',
                'minWithdraw' => 'required',
                'maxWithdraw' => 'required',
                'gimacMin' => 'required',
                'gimacMax' => 'required',
                'bdaMin' => 'required',
                'bdaMax' => 'required',
                'onafriqa_min' => 'required',
                'onafriqa_max' => 'required',
                'unverifiedKycMin' => 'required',
                'unverifiedKycMax' => 'required',

            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return view('admin.admins.changeLimit', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo, 'slug' => $slug])->withErrors($validator);
            } else {
                DB::table('transactions_limit')->where('slug', $slug)->update(array(
                    'type' => $adminInfo->type,
                    'minAirtel' => $input['minAirtel'],
                    'maxAirtel' => $input['maxAirtel'],
                    'minDeposit' => $input['minDeposit'],
                    'maxDeposit' => $input['maxDeposit'],
                    'minWithdraw' => $input['minWithdraw'],
                    'maxWithdraw' => $input['maxWithdraw'],
                    'minSendMoney' => $request->has('minSendMoney') ? $input['minSendMoney'] : '0.00',
                    'maxSendMoney' => $request->has('maxSendMoney') ? $input['maxSendMoney'] : '0.00',
                    'gimacMin' => $input['gimacMin'],
                    'gimacMax' => $input['gimacMax'],
                    'bdaMin' => $input['bdaMin'],
                    'bdaMax' => $input['bdaMax'],
                    'onafriqa_min' => $input['onafriqa_min'],
                    'onafriqa_max' => $input['onafriqa_max'],
                    'moneyReceivingMin' => $request->has('moneyReceivingMin') ? $input['moneyReceivingMin'] : '0.00',
                    'moneyReceivingMax' => $request->has('moneyReceivingMax') ? $input['moneyReceivingMax'] : '0.00',
                    'unverifiedKycMin' => $input['unverifiedKycMin'],
                    'unverifiedKycMax' => $input['unverifiedKycMax'],
                    'bulkMin' => $request->has('bulkMin') ? $input['bulkMin'] : '0.00',
                    'bulkMax' => $request->has('bulkMax') ? $input['bulkMax'] : '0.00',
                ));
                Session::flash('success_message', "Amount limit updated successfully.");
                return Redirect::to('admin/admins/change-limit/' . $slug);
            }
        }
        return view('admin.admins.changeLimit', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo, 'slug' => $slug]);
    }

    public function changeCommission(Request $request)
    {
        $pageTitle = 'Change Commission';
        $activetab = 'actchangecommission';
        $adminInfo = DB::table('admins')->select('admins.commission', 'admins.id')->where('id', Session::get('adminid'))->first();
        $input = $request->all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'commission' => 'required'
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return view('admin.admins.changeCommission', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo])->withErrors($validator);
            } else {
                DB::table('admins')->where('id', $adminInfo->id)->update(array('commission' => $input['commission']));
                Session::flash('success_message', "Commission updated successfully.");
                return Redirect::to('admin/admins/change-commission');
            }
        }
        return view('admin.admins.changeCommission', ['title' => $pageTitle, $activetab => 1, 'adminInfo' => $adminInfo]);
    }

    public function changeService(Request $request)
    {
        $pageTitle = 'Change Service';
        $activetab = 'actservices';
        $serviceInfo = DB::table('services')->where('id', 1)->first();
        $input = $request->all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'ach' => 'required',
                'c21' => 'required',
                'eft' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return view('admin.admins.changeService', ['title' => $pageTitle, $activetab => 1, 'serviceInfo' => $serviceInfo])->withErrors($validator);
            } else {
                DB::table('services')->where('id', 1)->update(array('ach' => $input['ach'], 'c21' => $input['c21'], 'eft' => $input['eft']));
                Session::flash('success_message', "Service details updated successfully.");
                return Redirect::to('admin/admins/change-service');
            }
        }
        return view('admin.admins.changeService', ['title' => $pageTitle, $activetab => 1, 'serviceInfo' => $serviceInfo]);
    }

    public function userchart($daycount = 2)
    {
        switch ($daycount) {
            case 0:
                $daycount = 1;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d') . ' 00:00:00';
                break;
            case 1:
                $daycount = 1;
                $today = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 2:
                $daycount = 31;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-30 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 3:
                $daycount = 365;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-365 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 4:
                $daycount = 7;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-7 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
        }

        $catArray = array();
        $CTempArray = array();

        if ($daycount == 365) {
            $countUserArray = DB::table('users')
                ->select('created_at as date', DB::raw('count(*) as count'))
                ->where('user_type', 'User')
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->groupBy(DB::raw('Month(created_at)'))
                ->get();

            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $catArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i <= 12; $i++) {
                $value = 0;
                $date = date('Y-m', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $finalArray[] = $value;
                $catArray[] = "'" . date('M', $strtotime) . "'";
                $strtotime = strtotime("+1month", $strtotime);
            }
        } else {
            $countUserArray = DB::table('users')
                ->select('created_at as date', DB::raw('count(*) as count'))
                ->where('user_type', 'User')
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->groupBy(DB::raw('Day(created_at)'))
                ->get();

            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m-d", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i < $daycount; $i++) {
                $value = 0;
                $date = date('Y-m-d', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $datea = date('Y, m-1, d', $strtotime);
                $finalArray[] = "Date.UTC($datea), " . $value;
                $strtotime = $strtotime + 24 * 3600;
            }
        }
        return view('elements.admin.chart', ['dayCount' => $daycount, 'finalArray' => "[" . implode('],[', $finalArray) . "]", 'catArray' => implode(', ', $catArray)]);
    }

    public function agentchart($daycount = 2)
    {

        switch ($daycount) {
            case 0:
                $daycount = 1;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d') . ' 00:00:00';
                break;
            case 1:
                $daycount = 1;
                $today = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 2:
                $daycount = 31;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-30 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 3:
                $daycount = 365;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-365 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 4:
                $daycount = 7;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-7 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
        }

        $catArray = array();
        $CTempArray = array();

        $adminInfo = Admin::where('id', Session::get('adminid'))->first();
        $company_code = $adminInfo->company_code;


        if ($daycount == 365) {

            $countUserArray = DB::table('users')
                ->select('created_at as date', DB::raw('count(*) as count'))
                ->where('user_type', 'Agent')
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->where('is_account_deleted', '1')
                ->when(!empty($company_code), function ($query) use ($company_code) {
                    return $query->where('company_code', $company_code);
                })
                ->groupBy(DB::raw('Month(created_at)'))
                ->get();

            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $catArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i <= 12; $i++) {
                $value = 0;
                $date = date('Y-m', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $finalArray[] = $value;
                $catArray[] = "'" . date('M', $strtotime) . "'";
                $strtotime = strtotime("+1month", $strtotime);
            }
        } else {
            $countUserArray = DB::table('users')
                ->select('created_at as date', DB::raw('count(*) as count'))
                ->where('user_type', 'Agent')
                ->where('is_account_deleted', '1')
                ->when(!empty($company_code), function ($query) use ($company_code) {
                    return $query->where('company_code', $company_code);
                })
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->groupBy(DB::raw('Day(created_at)'))
                ->get();

            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m-d", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i < $daycount; $i++) {
                $value = 0;
                $date = date('Y-m-d', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $datea = date('Y, m-1, d', $strtotime);
                $finalArray[] = "Date.UTC($datea), " . $value;
                $strtotime = $strtotime + 24 * 3600;
            }
        }
        return view('elements.admin.agentchart', ['dayCount' => $daycount, 'finalArray' => "[" . implode('],[', $finalArray) . "]", 'catArray' => implode(', ', $catArray)]);
    }

    public function merchantchart($daycount = 2)
    {
        switch ($daycount) {
            case 0:
                $daycount = 1;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d') . ' 00:00:00';
                break;
            case 1:
                $daycount = 1;
                $today = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 2:
                $daycount = 31;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-30 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 3:
                $daycount = 365;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-365 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 4:
                $daycount = 7;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-7 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
        }

        $catArray = array();
        $CTempArray = array();

        if ($daycount == 365) {
            $countUserArray = DB::table('users')
                ->select('created_at as date', DB::raw('count(*) as count'))
                ->where('user_type', 'Merchant')
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->groupBy(DB::raw('Month(created_at)'))
                ->get();

            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $catArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i <= 12; $i++) {
                $value = 0;
                $date = date('Y-m', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $finalArray[] = $value;
                $catArray[] = "'" . date('M', $strtotime) . "'";
                $strtotime = strtotime("+1month", $strtotime);
            }
        } else {
            $countUserArray = DB::table('users')
                ->select('created_at as date', DB::raw('count(*) as count'))
                ->where('user_type', 'Merchant')
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->groupBy(DB::raw('Day(created_at)'))
                ->get();

            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m-d", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i < $daycount; $i++) {
                $value = 0;
                $date = date('Y-m-d', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $datea = date('Y, m-1, d', $strtotime);
                $finalArray[] = "Date.UTC($datea), " . $value;
                $strtotime = $strtotime + 24 * 3600;
            }
        }
        return view('elements.admin.merchantchart', ['dayCount' => $daycount, 'finalArray' => "[" . implode('],[', $finalArray) . "]", 'catArray' => implode(', ', $catArray)]);
    }

    public function transchart($daycount = 2)
    {
        switch ($daycount) {
            case 0:
                $daycount = 1;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d') . ' 00:00:00';
                break;
            case 1:
                $daycount = 1;
                $today = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 2:
                $daycount = 31;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-30 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 3:
                $daycount = 365;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-365 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
            case 4:
                $daycount = 7;
                $today = date('Y-m-d') . ' 23:59:00';
                $lastday = date('Y-m-d', strtotime("-7 day", strtotime(date('Y-m-d')))) . ' 00:00:00';
                break;
        }

        $catArray = array();
        $CTempArray = array();

        $adminInfo = Admin::where('id', Session::get('adminid'))->first();
        $company_code = $adminInfo->company_code;

        if ($daycount == 365) {

            $countUserArray = Transaction::select('created_at as date', DB::raw('count(*) as count'))
                ->when(!empty($company_code), function ($q) use ($company_code) {
                    return $q->whereHas('User', function ($subQuery) use ($company_code) {
                        $subQuery->where('user_type', 'agent')
                            ->where('company_code', $company_code)
                            ->where('trans_type', 1)
                            ->where('payment_mode', 'Agent Deposit');
                    })->orWhere(function ($q) use ($company_code) {
                        $q->whereHas('Receiver', function ($subQuery) use ($company_code) {
                            $subQuery->where('user_type', 'agent')
                                ->where('company_code', $company_code)
                                ->where('trans_type', 2)
                                ->where('payment_mode', 'Withdraw');
                        });
                    });
                })
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->groupBy(DB::raw('Month(created_at)'))
                ->get();



            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $catArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i <= 12; $i++) {
                $value = 0;
                $date = date('Y-m', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $finalArray[] = $value;
                $catArray[] = "'" . date('M', $strtotime) . "'";
                $strtotime = strtotime("+1month", $strtotime);
            }
        } else {
            $countUserArray = Transaction::select('created_at as date', DB::raw('count(*) as count'))
                ->where('created_at', '<=', $today)
                ->where('created_at', '>=', $lastday)
                ->when(!empty($company_code), function ($q) use ($company_code) {
                    return $q->whereHas('User', function ($subQuery) use ($company_code) {
                        $subQuery->where('user_type', 'agent')
                            ->where('company_code', $company_code)
                            ->where('trans_type', 1)
                            ->where('payment_mode', 'Agent Deposit');
                    })->orWhere(function ($q) use ($company_code) {
                        $q->whereHas('Receiver', function ($subQuery) use ($company_code) {
                            $subQuery->where('user_type', 'agent')
                                ->where('company_code', $company_code)
                                ->where('trans_type', 2)
                                ->where('payment_mode', 'Withdraw');
                        });
                    });
                })
                ->groupBy(DB::raw('Day(created_at)'))
                ->get();

            foreach ($countUserArray as $row) {
                $CTempArray[date("Y-m-d", strtotime($row->date))] = $row->count;
            }
            ksort($CTempArray);
            $finalArray = array();
            $strtotime = strtotime($lastday);
            for ($i = 0; $i < $daycount; $i++) {
                $value = 0;
                $date = date('Y-m-d', $strtotime);
                if (array_key_exists($date, $CTempArray)) {
                    $value = $CTempArray[$date];
                }
                $datea = date('Y, m-1, d', $strtotime);
                $finalArray[] = "Date.UTC($datea), " . $value;
                $strtotime = $strtotime + 24 * 3600;
            }
        }
        return view('elements.admin.transchart', ['dayCount' => $daycount, 'finalArray' => "[" . implode('],[', $finalArray) . "]", 'catArray' => implode(', ', $catArray)]);
    }

    public function listRole(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'department');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'List Role\'s';
        $activetab = 'actconfigrole';
        $roles = DB::table('roles')->where('id', '!=', 1)->orderBy('created_at', 'DESC')->get();

        return view('admin.admins.listRole', ['title' => $pageTitle, $activetab => 1, 'roles' => $roles]);
    }

    public function addRole(Request $request)
    {

        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-department');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'Create Role';
        $activetab = 'actconfigrole';

        $input = Input::all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'role_name' => 'required',
                // 'permission' => 'required|array|min:1',
            );

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return view('admin.admins.add-role', ['title' => $pageTitle, $activetab => 1])->withErrors($validator);
            } else {
                $isExists = Role::where('role_name', trim($input['role_name']))->first();
                if (!empty($isExists)) {
                    Session::flash('error_message', "Role name already exists!");
                    return Redirect::to('admin/admins/department');
                } else {
                    $role = new Role([
                        'role_name' => trim($input['role_name']),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $role->save();
                    $role_id = $role->id;

                    foreach ($input['permission'] as $permission) {
                        $perm = new Permission([
                            'role_id' => $role_id,
                            'permission_name' => $permission,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $perm->save();
                    }

                    Session::flash('success_message', "Role added Successfully.");
                    return Redirect::to('admin/admins/department');
                }
            }
        }
        return view('admin.admins.addRole', ['title' => $pageTitle, $activetab => 1]);
    }

    public function editRole($slug)
    {

        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-department');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'Edit Role';
        $activetab = 'actconfigrole';

        $role = Role::where('id', $slug)->first();
        $permissions = Permission::where('role_id', $slug)->get();
        $permissionArr = array();
        foreach ($permissions as $permission) {
            $permissionArr[] = $permission->permission_name;
        }

        $input = Input::all();
        if (!empty($input)) {
            $error = '';
            $rules = array(
                'role_name' => 'required',
                'permission' => 'required',
            );

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return view('admin.admins.editRole', ['title' => $pageTitle, $activetab => 1, 'role' => $role, 'permissions' => $permissionArr])->withErrors($validator);
            } else {
                if (!isset($input['permission']) && Count($input['permission']) <= 0) {
                    Session::flash('error_message', "Role should have atleast one permission.");
                    return Redirect::to('admin/admins/department');
                }
                Role::where('id', $slug)->update(['role_name' => $input['role_name'], 'updated_at' => date('Y-m-d H:i:s')]);
                Permission::where('role_id', $slug)->delete();

                foreach ($input['permission'] as $permission) {
                    $perm = new Permission([
                        'role_id' => $slug,
                        'permission_name' => $permission,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $perm->save();
                }

                Session::flash('success_message', "Role updated Successfully.");
                return Redirect::to('admin/admins/department');
            }
        }
        return view('admin.admins.editRole', ['title' => $pageTitle, $activetab => 1, 'role' => $role, 'permissions' => $permissionArr]);
    }

    public function companiesList(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'company-list');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Companies List';
        $activetab = 'actcompanieslist';
        $query = new Admin();
        $query = $query->sortable();
        $role = Session::get('admin_role');
        $query = $role == 1 ? $query->where('parent_id', '!=', 0) : $query->where('parent_id', Session::get('adminid'));

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('company_name', 'like', '%' . $keyword . '%')
                    ->orWhere('username', 'like', '%' . $keyword . '%')
                    ->orWhere('company_code', 'like', '%' . $keyword . '%')
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


        $companies = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->input('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }
        if ($request->ajax() || $page > 1) {
            return view('elements.admin.admins.companyList', ['allrecords' => $companies, 'page' => $page]);
        }
        return view('admin.admins.companyList', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $companies, 'page' => $page]);
    }

    public function addCompany(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-company');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Add Company';
        $activetab = 'actcompanieslist';

        $code = Str::random(6);

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'company_name' => 'required|unique:admins',
                'company_code' => 'required|unique:admins',
                'username' => 'required|max:50|unique:admins',
                'phone' => 'required|unique:admins',
                'password' => 'required|min:8',
                'confirm_password' => 'required|same:password',
                // 'wallet_balance' => 'required',
            );
            $customMessages = [
                'company_name.required' => 'Company name is required field.',
                'company_name.unique' => 'Company name should be unique.',
                'company_code.required' => 'Company code is required field.',
                'company_code.unique' => 'Company code should be unique.',
                'username.required' => 'Username is required field.',
                'username.unique' => 'Username should be unique.',
                'phone.required' => 'Phone is required field.',
                'phone.unique' => 'Phone should be unique.',
                'password.required' => 'Password is required field.',
                'password.min' => 'Password should be at least 8 characters long.',
                'confirm_password.required' => 'Confirm password is a required field.',
                'password.same' => 'Confirm password must match the password field.',
                // 'wallet_balance' => 'Wallet balance is required field',
            ];
            $validator = Validator::make($input, $rules, $customMessages);

            if ($validator->fails()) {
                return Redirect::to('/admin/admins/add-company')->withErrors($validator)->withInput();
            } else {

                if (isset($input['profile'])) {
                    $files = $input['profile'];
                    $upload_path = public_path() . '/assets/company_logo/';
                    $img_name = $this->uploadImage($files, $upload_path);
                    $input['profile'] = $img_name;
                }

                $slug = $this->createSlug($input['username'], 'admins');
                $role_ids =  Session::get('admin_role');
                $parent_id =  Session::get('adminid');

                $slug = $this->createSlug($input['username'], 'admins');
                $admin = new Admin([
                    'company_name' => $input['company_name'],
                    'company_code' => $input['company_code'],
                    'phone' => $input['phone'],
                    'wallet_balance' => 0,
                    'company_address' => $input['company_address'],
                    'website' => $input['website'],
                    'profile' => isset($input['profile']) ? $input['profile'] : '',
                    'username' => $input['username'],
                    'email' => $input['email'],
                    'role_id' => env('AGGREGATOR_ID'),
                    'parent_id' => $parent_id,
                    'password' => $this->encpassword($input['password']),
                    'status' => 1,
                    'activation_status' => 1,
                    'slug' => $slug,
                    'edited_by' => Session::get('adminid'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $admin->save();
                Session::flash('success_message', "Company has been saved successfully.");
                return Redirect::to('admin/admins/company-list');
            }
        }
        return view('admin.admins.addCompany', ['title' => $pageTitle, $activetab => 1, 'code' => $code]);
    }

    public function editCompany(Request $request, $slug)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-company');
        if (!$isPermitted) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, 'activetab' => $activetab]);
        }

        $pageTitle = 'Edit Company';
        $activetab = 'actcompanieslist';
        $recordInfo = Admin::where('slug', $slug)->first();

        if ($request->isMethod('post')) {
            $input = $request->all();

            $rules = [
                'company_name' => 'required|unique:admins,company_name,' . $recordInfo->id,
                'username' => 'required|max:50|unique:admins,username,' . $recordInfo->id,
                'phone' => 'required|unique:admins,phone,' . $recordInfo->id,
                'password' => 'nullable|min:8',
                'confirm_password' => 'nullable|same:password',
                // 'wallet_balance' => 'required',
            ];

            $customMessages = [
                'company_name.required' => 'Company name is required.',
                'company_name.unique' => 'Company name should be unique.',
                'username.required' => 'Username is required.',
                'username.unique' => 'Username should be unique.',
                'phone.required' => 'Phone is required.',
                'phone.unique' => 'Phone should be unique.',
                'password.min' => 'Password should be at least 8 characters long.',
                'confirm_password.same' => 'Confirm password must match the password.',
                // 'wallet_balance' => 'Wallet balance is required field',
            ];

            $validator = Validator::make($input, $rules, $customMessages);

            if ($validator->fails()) {
                return Redirect::back()->withErrors($validator)->withInput();
            }

            // Process image upload if a new image is provided
            if ($request->hasFile('profile')) {
                $file = $request->file('profile');
                $upload_path = public_path() . '/assets/company_logo/';
                $img_name = $this->uploadImage($file, $upload_path);
                $input['profile'] = $img_name;
                unlink('public/assets/company_logo/' . $recordInfo->profile);
            }

            // Update the company record
            $data = [
                'company_name' => $input['company_name'],
                'company_code' => $input['company_code'],
                'phone' => $input['phone'],
                'company_address' => $input['company_address'],
                'website' => $input['website'],
                'profile' => isset($input['profile']) ? $input['profile'] : $recordInfo->profile,
                'username' => $input['username'],
                'email' => $input['email'],
                'edited_by' => Session::get('adminid'),
            ];

            // Update password if provided
            if (!empty($input['password'])) {
                $data['password'] = $this->encpassword($input['password']);
            }

            $recordInfo->update($data);

            Session::flash('success_message', "Company has been updated successfully.");
            return Redirect::to('admin/admins/company-list');
        }

        return view('admin.admins.editCompany', ['title' => $pageTitle, 'activetab' => $activetab, 'recordInfo' => $recordInfo]);
    }

    public function updateCompanyStatus($slug, $status)
    {
        Admin::where('slug', $slug)->update(array('status' => $status));
        Session::flash('success_message', "Company status has been updated successfully");
        return Redirect::to('admin/admins/company-list');
    }

    public function payCompany(Request $request, $slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'pay-company');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $activetab = 'actcompanieslist';
        $pageTitle = 'Adjust Company Wallet';

        $recordInfo = Admin::where('slug', $slug)->first();


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
                return Redirect::to('/admin/admins/pay-company' . $slug)->withErrors($validator)->withInput();
            } else {

                if ($input['wallet_action'] == "Withdraw") {
                    $remainBal = $input['amount'];
                    if ($input['amount'] >= $recordInfo->wallet_balance) {
                        Session::flash('error_message', 'Insufficient Balance');
                        return Redirect::to('/admin/admins/pay-company/' . $slug);
                    }

                    $billing_description = '<br>Admin Withdraw<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];

                    // $admin = Admin::where('id', 1)->first();
                    // $admin_wallet = $admin->wallet_balance + $adminBal;
                    // Admin::where('id', 1)->update(['wallet_balance' => $admin_wallet, 'updated_at' => date('Y-m-d H:i:s')]);
                    $refrence_id = time() . rand() . Session::get('user_id');
                    $trans = new CompanyTransaction([
                        "user_id" =>  $recordInfo->id,
                        "receiver_id" => Session::get('adminid'),
                        "amount" => $input['amount'],
                        "trans_type" => 2, //debit
                        "payment_mode" => 'Withdraw',
                        "refrence_id" => $refrence_id,
                        "billing_description" => $billing_description,
                        "status" => 1,
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();
                    $user_wallet = $recordInfo->wallet_balance - $remainBal;
                    Admin::where('slug', $slug)->update(['wallet_balance' => $user_wallet, 'updated_at' => date('Y-m-d H:i:s')]);

                    $this->sendEmail([
                        'subjects' => 'Funds Transfer Details',
                        'senderName' => session('admin_username') ?? 'Swap Wallet',

                        'currency' => CURR,

                        'senderName' => $recordInfo->username,
                        'senderAmount' => $input['amount'],
                        'senderEmail' => $recordInfo->email ?? '',

                        'receiverName' => $recordInfo->username,
                        'receiverAmount' => $input['amount'],
                        'receiverEmail' => $recordInfo->email ?? '',

                        'transId' => $refrence_id,
                        'transactionFees' => 0,
                        'transactionDate' => date('d M, Y h:i A', strtotime($trans->created_at)),
                        'transactionStatus' => $this->getStatusText(1),
                    ], 'withdraw');

                    //Mail End
                } else if ($input['wallet_action'] == "Deposit") {
                    // $adminBal = $input['amount'];
                    // $admin = Admin::where('id', 1)->first();
                    // $amount = $admin->wallet_balance;
                    // if ($input['amount'] >= $amount) {
                    //     Session::flash('error_message', 'Insufficient Balance');
                    //     return Redirect::to('/admin/admins/pay-company' . $slug);
                    // }  
                    $remainBal = $input['amount'];
                    $refrence_id = time() . rand() . Session::get('user_id');
                    $billing_description = '<br>Admin Deposit<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];
                    $trans = new CompanyTransaction([
                        "user_id" =>  $recordInfo->id,
                        "receiver_id" => Session::get('adminid'),
                        "amount" => $input['amount'],
                        "trans_type" => 1, //Credit
                        "payment_mode" => 'Deposit',
                        "refrence_id" => $refrence_id,
                        "billing_description" => $billing_description,
                        "status" => 1,
                        "created_at" => date('Y-m-d H:i:s'),
                        "updated_at" => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();
                    $user_wallet = $recordInfo->wallet_balance + $remainBal;

                    Admin::where('slug', $slug)->update(['wallet_balance' => $user_wallet, 'updated_at' => date('Y-m-d H:i:s')]);

                    $this->sendEmail([
                        'subjects' => 'Funds Transfer Details',
                        'senderName' => session('admin_username') ?? 'Swap Wallet',

                        'currency' => CURR,

                        'senderName' => $recordInfo->username,
                        'senderAmount' => $input['amount'],
                        'senderEmail' => $recordInfo->email ?? '',
                        'receiverName' => $recordInfo->username,
                        'receiverAmount' => $input['amount'],
                        'receiverEmail' => $recordInfo->email ?? '',
                        'transId' => $refrence_id,
                        'transactionFees' => 0,
                        'transactionDate' => date('d M, Y h:i A', strtotime($trans->created_at)),
                        'transactionStatus' => $this->getStatusText(1),
                    ], 'deposit');
                }
                Session::flash('success_message', "Client Balance Adjusted Successfully.");
                return Redirect::to('/admin/admins/company-list');
                // return Redirect::to('/admin/admins/company-transaction-history/'.$slug);
            }
        }

        return view('admin.admins.payclient', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }


    public function companyTransactionHistory(Request $request, $slug = null)
    {

        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'company-transaction-history');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $username = Admin::where('slug', $slug)->first()->username;
        $activetab = 'actcompanieslist';
        $pageTitle = 'Company Transaction History';
        $query = new CompanyTransaction();
        $query = $query->sortable();
        $query = $query->whereHas('User', function ($q) use ($slug) {
            $q = $q->where('slug', $slug);
        });
        $query = $query->whereHas('Receiver');

        if ($request->has('for')) {
            $for = $request->get('for');
            if (!empty($for)) {
                $query = $query->where('payment_mode', $for);
            }
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";
            $query = $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        $query2 = clone $query;
        $query3 = clone $query;
        $total_deposit = $query2->where('trans_type', 1)->sum('amount');
        $total_withdraw = $query3->where('trans_type', 2)->sum('amount');
        $balance = $total_deposit -  $total_withdraw;
        $companies = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->input('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }
        if ($request->ajax() || $page > 1) {
            return view('elements.admin.admins.company_transaction_history', ['allrecords' => $companies, 'page' => $page, 'slug' => $slug, 'username' => $username, 'balance' => $balance]);
        }
        return view('admin.admins.company_transaction_history', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $companies, 'page' => $page, 'slug' => $slug, 'username' => $username, 'balance' => $balance]);
    }
}
