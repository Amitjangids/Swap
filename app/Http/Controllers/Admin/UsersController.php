<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Cookie;
use Session;
use Redirect;
use Input;
use Validator;
use DB;
use IsAdmin;
use App;
use App\User;
use App\Models\Feature;
use App\Models\Userfeature;
use App\Models\Country;
use App\Models\Notification;
use App\Models\City;
use App\Models\Area;
use App\Models\Admin;
use App\Models\Transaction;
use App\Models\TransactionLedger;
use App\Models\UserCard;
use Mail;
use Storage;
use Carbon\Carbon;
use App\Mail\SendMailable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use App\Walletlimit;
use DateTime;
use DateTimeZone;
use App\Services\FirebaseService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\CardService;

class UsersController extends Controller
{

    protected $firebaseNotificationService;
    public function __construct(CardService $cardService)
    {
        $this->middleware('is_adminlogin');
        $this->firebaseNotificationService = new FirebaseService();
        $this->cardService = $cardService;
        $this->lang = DB::table('app_language')->value('lang') ?? 'en';
        App::setLocale($this->lang);
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
                            $emailData["senderEmail"],
                            $emailData["receiverEmail"],
                            $emailData["receiverEmail"]
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
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'users');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'Manage Users';
        $activetab = 'actusers';
        $query = new User();
        $query = $query->sortable();
        $query = $query->where('user_type', 'User');



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

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $users = $query->orderBy('id', 'DESC')->paginate(20);

        $userIds = $users->pluck('id')->toArray();

        $allCards = UserCard::whereIn('userId', $userIds)
            ->where('cardStatus', 'Active')
            ->get()
            ->groupBy('userId');

        $allDocuments = DB::table('travel_documents')
            ->whereIn('userId', $userIds)
            ->get()
            ->keyBy('userId');



        foreach ($users as $user) {
            $cards = ($allCards[$user->id] ?? collect())->keyBy('cardType');

            $virtualBalance = isset($cards['VIRTUAL'])
                ? $this->cardService->getCardBalance($cards['VIRTUAL']->accountId, 'VIRTUAL')
                : 0;

            $physicalBalance = isset($cards['PHYSICAL'])
                ? $this->cardService->getCardBalance($cards['PHYSICAL']->accountId, 'PHYSICAL')
                : 0;

            // Attach balances to user
            // dd($virtualBalance,$physicalBalance);
            $user->virtualBalance = $virtualBalance['data']['balance'] ?? "";
            $user->physicalBalance = $physicalBalance['data']['balance'] ?? "";
            $user->virtualCardType = $cards['VIRTUAL']->cardType ?? "";
            $user->virtualAccountId = $cards['VIRTUAL']->accountId ?? "";
            $user->physicalCardType = $cards['PHYSICAL']->cardType ?? "";
            $user->physicalAccountId = $cards['PHYSICAL']->accountId ?? "";
            /* if (!isset($userPhysicalCard[$user->id])) {

                $rebateTxn = UserCard::where('userId', $user->id)->where('cardType', "PHYSICAL")->where('cardStatus', 'Active')->first();

                $userPhysicalCard[$user->id] = $rebateTxn ?? null;
            } */

            $user->cardActiveInactive = $cards['PHYSICAL'] ?? null;
            // $user->cardActiveInactive = $userPhysicalCard[$user->id];


            /* if (!isset($documentCache[$user->id])) {

                $getDocsImage = DB::table('travel_documents')
                    ->where('userId', $user->id)
                    ->first();

                $documentCache[$user->id] = [
                    'passport' => $getDocsImage && $getDocsImage->passport
                        ? url(PASSPORT_PATH . $getDocsImage->passport)
                        : null,

                    'ticket' => $getDocsImage && $getDocsImage->ticket
                        ? url(TICKET_PATH . $getDocsImage->ticket)
                        : null,

                    'visa' => $getDocsImage && $getDocsImage->visa
                        ? url(VISA_PATH . $getDocsImage->visa)
                        : null,
                    'status' => $getDocsImage->status ?? null,
                ];
            }
            $user->docs = $documentCache[$user->id]; */

            /* $doc = $allDocuments[$user->id] ?? null;

            $user->docs = [
                'passport' => $doc && $doc->passport ? url(PASSPORT_PATH . $doc->passport) : null,
                'ticket' => $doc && $doc->ticket ? url(TICKET_PATH . $doc->ticket) : null,
                'visa' => $doc && $doc->visa ? url(VISA_PATH . $doc->visa) : null,
                'status' => $doc->status ?? null,
            ]; */
            // dd($users);
        }

        if ($request->ajax() || $page > 1) {
            return view('elements.admin.users.index', ['allrecords' => $users, 'page' => $page]);
        }
        return view('admin.users.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'page' => $page]);
    }

    public function all(Request $request)
    {


        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'all');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actallusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Total Register Users';

        $activetab = 'actallusers';
        $query = new User();
        $query = $query->whereIN('user_type', ['Approver', 'Submitter']);
        $query = $query->sortable();
        $query = $query->where('user_type', '!=', '');

        $query1 = new User();
        $query1 = $query1->where('user_type', '!=', '');
        $query1 = $query1->whereIN('user_type', ['Approver', 'Submitter']);

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

            $query1 = $query1->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('user_type') && $request->get('user_type')) {
            $user_type = $request->get('user_type');

            $query = $query->where(function ($q) use ($user_type) {
                $q->orWhere('user_type', 'like', '%' . $user_type . '%');
            });

            $query1 = $query1->where(function ($q) use ($user_type) {
                $q->orWhere('user_type', 'like', '%' . $user_type . '%');
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });

            $query1 = $query1->where(function ($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $users = $query->orderBy('id', 'DESC')->paginate(20);

        $total['wallet_balance'] = $query1->sum('wallet_balance');

        if ($request->ajax() || $page > 1) {
            return view('elements.admin.users.all', ['allrecords' => $users, 'page' => $page, 'total' => $total]);
        }
        return view('admin.users.all', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'page' => $page, 'total' => $total]);
    }

    public function loginusers(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'loginusers');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers3';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Logged In Users';
        $activetab = 'actusers3';
        $query = new User();
        $query = $query->sortable();
        $query = $query->where('login_status', 1)->whereNotNull('device_type');

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                User::whereIn('id', $idList)->update(['is_verify' => 1]);
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                User::whereIn('id', $idList)->update(['is_verify' => 0]);
                Session::flash('success_message', "Records are deactivated successfully.");
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
                $q->whereBetween('login_time', [$from, $to]);
            });
        }

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $users = $query->orderBy('id', 'DESC')->paginate(20);

        if ($request->ajax() || $page > 1) {
            return view('elements.admin.users.loginusers', ['allrecords' => $users, 'page' => $page]);
        }
        return view('admin.users.loginusers', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'page' => $page]);
    }

    public function payClient(Request $request, $slug = null)
    {


        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'payclient');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Add User';
        $activetab = 'actusers';


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
                return Redirect::to('/admin/users/payclient/' . $slug)->withErrors($validator)->withInput();
            } else {
                $userCard = UserCard::where('userId', $recordInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();

                if ($input['wallet_action'] == "Withdraw") {
                    $remainBal = $input['amount'];
                    $adminBal = $input['amount'];

                    if ($input['amount'] > $recordInfo->wallet_balance) {
                        Session::flash('error_message', 'Insufficient Balance');
                        return Redirect::to('/admin/users/payclient/' . $slug);
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

                        // 'senderName' => $recordInfo->name,
                        'senderAmount' => $input['amount'],
                        'senderEmail' => $recordInfo->email ?? '',
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
                        return Redirect::to('/admin/users/payclient/' . $slug);
                    }

                    $billing_description = '<br>Admin Deposit<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];
                    $refrence_id = time() . rand() . Session::get('user_id');
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
                    $billing_description = '<br>Admin Deposit<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];
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
                        'receiverName' => $recordInfo->name,
                        'receiverAmount' => $input['amount'],
                        'receiverEmail' => $recordInfo->email ?? '',
                        'transId' => $refrence_id,
                        'transactionFees' => 0,
                        'transactionDate' => date('d M, Y h:i A', strtotime($credit->created_at)),
                        'transactionStatus' => $this->getStatusText(1),
                    ], 'deposit');


                    $title = __('message_app.fund_added_title');
                    // dd($title);
                    $message = __('message_app.fund_added_des', ['amount' => $input['amount']]);
                    $device_token = $recordInfo->device_token;
                    $device_type = $recordInfo->device_type;

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
                        'user_id' => $recordInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                }

                Session::flash('success_message', "Client Balance Adjusted Successfully.");
                if ($recordInfo->user_type == 'User') {
                    return Redirect::to('/admin/users');
                }
            }
        }

        return view('admin.users.payclient', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }


    public function add(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-users');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Add User';
        $activetab = 'actusers';
        $countrList = Country::getCountryList();
        $input = Input::all();

        if (!empty($input)) {


            $rules = array(
                'name' => 'required|max:50',
                'phone' => 'required|unique:users|min:6|max:15|string',
                'dob' => 'required',

            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {

                return Redirect::to('/admin/users/add-users')->withErrors($validator)->withInput(Input::except('city'));
            } else {

                unset($input['phone_number']);




                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['slug'] = $this->createSlug($input['name'], 'users');
                $serialisedData['is_kyc_done'] = '0';
                $serialisedData['is_verify'] = 1;
                $serialisedData['otp_verify'] = 1;
                $serialisedData['user_type'] = 'User';

                User::insert($serialisedData);

                $user_id = DB::getPdo()->lastInsertId();
                $qrString = $user_id . "##" . $request->name;
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

                Session::flash('success_message', "User details saved successfully.");
                return Redirect::to('admin/users');
            }
        }
        return view('admin.users.add', ['title' => $pageTitle, $activetab => 1, 'countrList' => $countrList]);
    }

    public function edit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-users');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Edit User';
        $activetab = 'actusers';


        $recordInfo = User::where('slug', $slug)->first();


        if (empty($recordInfo)) {
            return Redirect::to('admin/users');
        }

        $input = Input::all();


        if (!empty($input)) {
            // Define validation rules
            $rules = [
                'name' => 'required|max:50',
                'phone' => 'required|min:6|max:15|unique:users,phone,' . $recordInfo->id,

            ];
            if (!empty($recordInfo->email)) {
                $rules['email'] = 'email|unique:users,email,' . $recordInfo->id;
            }


            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/users/edit-users/' . $slug)->withErrors($validator)->withInput();
            } else {



                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                User::where('id', $recordInfo->id)->update($serialisedData);

                $user_id = $recordInfo->id;
                $qrString = $user_id . "##" . $recordInfo->name;
                $qrCode = $this->generateQRCode($qrString, $user_id);

                User::where('id', $user_id)->update(array('qr_code' => $qrCode));


                Session::flash('success_message', "User details updated successfully.");
                return Redirect::to('admin/users');
            }
        }
        return view('admin.users.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    public function kycdetail($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'kycdetail');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'View User KYC Detail';
        $activetab = 'actusers';
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
        // $user->update(['kyc_status' => 'pending', 'selfie_image' => $responseData->image_links->selfie_image, 'identity_front_image' => $responseData->image_links->id_card_image, 'identity_back_image' => $responseData->image_links->id_card_back]);
        //
        //   echo"<pre>";print_r($userInfo);die;
        return view('admin.users.kycdetail', ['title' => $pageTitle, $activetab => 1, 'userInfo' => $user, 'imageData' => $responseData->image_links ?? null]);
    }

    function generateIdentityNumber()
    {
        $letters = strtoupper(Str::random(2));   // AB
        $numbers = random_int(1000000, 9999999); // 7 digits

        return $letters . $numbers; // AB1234567
    }

    public function formatPreferredName($name, $lastName)
    {
        $fullName = trim($name . ' ' . $lastName);

        // Ensure UTF-8
        $fullName = mb_convert_encoding($fullName, 'UTF-8', 'auto');

        // Convert accents → ASCII
        $fullName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fullName);

        // Remove characters not allowed by regex
        $fullName = preg_replace('/[^a-zA-Z0-9\s]/', '', $fullName);

        // Normalize spaces
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

        // Enforce length
        $fullName = substr($fullName, 0, 18);
        return $fullName;
    }

    public function approvekyc(Request $request, $slug = null)
    {
        if ($slug) {
            $user = DB::table('users')->where('slug', $slug)->first();
            $userId = $user->id;
            global $getStateId;
            $countryVal = $getStateId[$user->country] ?? 0;
            $idType = $request->idType;
            $idNumber = $request->idNumber;

            $ibanData = DB::table('iban_generated_lists')->where('status', 'available')->first();

            if (!$ibanData) {
                Log::info('No available IBAN Admin');
            }

            $iban = $ibanData->iban ?? "";
            $dob = (isset($user->dob) && $user->dob != "Not Available") ? strtoupper(Carbon::parse($user->dob)->format('d-M-Y')) : "01-JAN-2000";
            $dobU = (isset($user->dob) && $user->dob != "Not Available") ? strtoupper(Carbon::parse($user->dob)->format('Y-m-d')) : "2000-01-01";
            User::where('id', $userId)->update(["ibanNumber" => $iban, 'kyc_status' => 'completed', 'dob' => $dobU, 'national_identity_type' => $idType, 'national_identity_number' => $idNumber]);
            if ($iban) {
                DB::table('iban_generated_lists')->where('id', $ibanData->id)->update(['status' => 'assigned']);
            }
            $preferredName = $this->formatPreferredName($user->name, $user->lastName);
            $firstName = $this->formatPreferredName($user->name, '');
            $lastName = $this->formatPreferredName($user->lastName, '');

            $postData = json_encode([
                "accountSource" => "OTHER",
                "address1" => $user->address1,
                "birthDate" => $dob,
                "city" => DB::table('province_city')->where('id', $user->city)->first()->name,
                "country" => "GA",
                "emailAddress" => !empty($user->email) ? $user->email : "test@mailinator.com",
                "firstName" => $firstName,
                "idType" => "1",
                "idValue" => $idNumber,
                "lastName" => $lastName,
                "mobilePhoneNumber" => [
                    "countryCode" => "241",
                    "number" => $user->phone
                ],
                "preferredName" => $preferredName,
                "referredBy" => ONAFRIQ_SUBCOMPANY,
                "stateRegion" => $countryVal,
                "subCompany" => ONAFRIQ_SUBCOMPANY,
                "return" => "RETURNPASSCODE"
            ]);

            Log::info(['Request' => $postData]);
            $getResponse = $this->cardService->saveCardVirtual($postData);
            Log::info(['Response' => $getResponse]);
            if ($getResponse['status'] == true) {
                $registrationAccountId = $getResponse['data']['registrationAccountId'] ?? 0;
                $registrationLast4Digits = $getResponse['data']['registrationLast4Digits'] ?? "";
                $registrationPassCode = $getResponse['data']['registrationPassCode'] ?? "";
                User::where('id', $userId)->update(['accountId' => $registrationAccountId, 'last4Digits' => $registrationLast4Digits, 'passCode' => $registrationPassCode, 'cardType' => 'VIRTUAL']);

                UserCard::create([
                    'userId' => $userId,
                    'accountId' => $registrationAccountId,
                    'last4Digits' => $registrationLast4Digits,
                    'passCode' => $registrationPassCode,
                    'cardType' => 'VIRTUAL'
                ]);
                Log::info("Card added Admin $userId");
            } else {
                Log::info("Card not added Admin  $userId");
            }


            $title = __("message_app.KYC Approved");
            $message = __("message_app.Congratulations! Your KYC Details Approved Successfully By Admin");
            $device_token = $user->device_token;
            $device_type = $user->device_type;

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'KYC',
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

            Session::flash('success_message', "User KYC approved successfully.");
            return Redirect::to('admin/users/kycdetail/' . $slug);
        }
    }

    public function declinekyc($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_kyc_done' => '2', 'kyc_status' => 'rejected'));

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

            $title = __("message_app.KYC Approved");
            $message = __("message_app.Congratulations! Your KYC Details Approved Successfully By Admin");
            $device_token = $userInfo->device_token;
            $device_type = $userInfo->device_type;

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'KYC',
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

            Session::flash('success_message', "User KYC declined successfully.");
            return Redirect::to('admin/users/kycdetail/' . $slug);
        }
    }

    public function activate($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_verify' => '1'));
            return view('elements.admin.update_status', ['action' => 'admin/users/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->update(array('is_verify' => '0', 'device_id' => ""));
            return view('elements.admin.update_status', ['action' => 'admin/users/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null)
    {
        if ($slug) {
            User::where('slug', $slug)->delete();
            Session::flash('success_message', "Individual user details deleted successfully.");
            return Redirect::to('admin/users');
        }
    }

    // public function deleteimage($slug = null) {
    //     if ($slug) {
    //         $recordInfo = DB::table('users')->where('slug', $slug)->select('users.profile_image')->first();
    //         User::where('slug', $slug)->update(array('profile_image' => ''));
    //         @unlink(PROFILE_FULL_UPLOAD_PATH . $recordInfo->profile_image);
    //         Session::flash('success_message', "Image deleted successfully.");
    //         return Redirect::to('admin/users/edit/' . $slug);
    //     }
    // }

    public function deleteidentity($slug = null)
    {
        if ($slug) {
            $recordInfo = DB::table('users')->where('slug', $slug)->select('users.identity_front_image')->first();
            User::where('slug', $slug)->update(array('identity_front_image' => ''));
            @unlink(IDENTITY_FULL_UPLOAD_PATH . $recordInfo->identity_image);
            Session::flash('success_message', "Image deleted successfully.");
            return Redirect::to('admin/users/edit/' . $slug);
        }
    }

    public function deleteidentity1($slug = null)
    {
        if ($slug) {
            $recordInfo = DB::table('users')->where('slug', $slug)->select('users.identity_back_image')->first();
            User::where('slug', $slug)->update(array('identity_back_image' => ''));
            @unlink(IDENTITY_FULL_UPLOAD_PATH . $recordInfo->identity_image1);
            Session::flash('success_message', "Image deleted successfully.");
            return Redirect::to('admin/users/edit/' . $slug);
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

    public function homeFeatures($userSlug = null, Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'homeFeatures');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actUserfeatures';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Enable/Disable Features';
        $activetab = 'actUserfeatures';
        $query = new Userfeature();
        $query = $query->sortable();

        $userInfo = User::where('slug', $userSlug)->first();
        if ($userInfo->user_type == 'Merchant' || $userInfo->user_type == 'agent') {
            $uType = strtolower($userInfo->user_type) . 's';
        } else {
            $uType = 'users';
        }
        $activetab = 'act' . $uType;

        $query = $query->where('user_id', $userInfo->id);

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');
            if ($action == "Activate") {
                Userfeature::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Userfeature::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Userfeature::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        $features1 = $query->orderBy('name', 'ASC')->get();

        if ($features1->isEmpty()) {
            $query1 = new Feature();
            $dFeatures = $query1->orderBy('name', 'ASC')->get();
            if ($dFeatures) {
                foreach ($dFeatures as $dFeature) {

                    if ($userInfo->user_type == 'Merchant') {
                        global $merchantFeature;
                        if (in_array($dFeature->name, $merchantFeature)) {
                            $serialisedData = array();
                            $serialisedData['slug'] = $this->createSlug($dFeature->slug, 'userfeatures');
                            $serialisedData['user_id'] = $userInfo->id;
                            $serialisedData['name'] = $dFeature->name;
                            $serialisedData['status'] = $dFeature->status;
                            $serialisedData['created_at'] = date('Y-m-d H:i:s');
                            $serialisedData['updated_at'] = date('Y-m-d H:i:s');
                            Userfeature::insert($serialisedData);
                        }
                    } elseif ($userInfo->user_type == 'Agent') {
                        global $agentFeature;
                        if (in_array($dFeature->name, $agentFeature)) {
                            $serialisedData = array();
                            $serialisedData['slug'] = $this->createSlug($dFeature->slug, 'userfeatures');
                            $serialisedData['user_id'] = $userInfo->id;
                            $serialisedData['name'] = $dFeature->name;
                            $serialisedData['status'] = $dFeature->status;
                            $serialisedData['created_at'] = date('Y-m-d H:i:s');
                            $serialisedData['updated_at'] = date('Y-m-d H:i:s');
                            Userfeature::insert($serialisedData);
                        }
                    } elseif ($userInfo->user_type == 'Individual') {
                        global $userFeature;
                        if (in_array($dFeature->name, $userFeature)) {
                            $serialisedData = array();
                            $serialisedData['slug'] = $this->createSlug($dFeature->slug, 'userfeatures');
                            $serialisedData['user_id'] = $userInfo->id;
                            $serialisedData['name'] = $dFeature->name;
                            $serialisedData['status'] = $dFeature->status;
                            $serialisedData['created_at'] = date('Y-m-d H:i:s');
                            $serialisedData['updated_at'] = date('Y-m-d H:i:s');
                            Userfeature::insert($serialisedData);
                        }
                    }
                }
            }
        }

        $features = $query->orderBy('name', 'ASC')->paginate(100);
        if ($request->ajax()) {
            return view('elements.admin.users.homeFeatures', ['allrecords' => $features, 'userInfo' => $userInfo]);
        }
        return view('admin.users.homeFeatures', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $features, 'userInfo' => $userInfo]);
    }

    public function activateFeature($userSlug = null, $slug = null)
    {
        if ($slug) {
            Userfeature::where('slug', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_feature', ['action' => 'admin/users/deactivateFeature/' . $userSlug . '/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivateFeature($userSlug = null, $slug = null)
    {
        if ($slug) {
            Userfeature::where('slug', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_feature', ['action' => 'admin/users/activateFeature/' . $userSlug . '/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function getarealist($id = null)
    {
        if ($id && $id > 0) {
            $areaList = Area::getAreaList($id);
            return view('elements.admin.arealist', ['areaList' => $areaList]);
        } else {
            return view('elements.admin.arealist', ['areaList' => array()]);
        }
    }

    public function Importuser(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'importuser');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add User';
        $activetab = 'actusers';

        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');

            $validator = Validator::make(
                ['csv_file' => $file],
                ['csv_file' => 'required|file|mimes:xls|max:2048']
            );

            if ($validator->fails()) {
                return Redirect::to('/admin/users/importuser')
                    ->withErrors($validator)
                    ->withInput();
            } else {
                // Process the uploaded Excel file
                $path = $file->store('public\assets');

                // Define your validation rules and error handling here
                // (your validation and data processing code)

                // Example validation (you may need to adjust this)
                $data = Excel::toArray([], $path);
                $csvData = $data[0]; // Assuming the data is in the first sheet
                $errorMessages = [];

                // Loop through the CSV data and perform validation
                foreach ($csvData as $index => $row) {
                    // Perform your validation for each row and add error messages to $errorMessages if needed

                    if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
                        $errorMessages[] = "Required fields are missing in row $index.";
                        continue; // Skip this row
                    }

                    // Validate name (letters, spaces, single quotes, and hyphens are allowed)
                    if (!preg_match("/^[a-zA-Z' -]+$/", $row[0])) {
                        $errorMessages[] = "Invalid name in row $index.";
                    }

                    // Validate user type (must be one of 'User')
                    $validUserTypes = ['User'];
                    if (!in_array($row[1], $validUserTypes)) {
                        $errorMessages[] = "Invalid user type in row $index.";
                    }

                    // Validate phone number (exactly 10 digits)
                    $phoneNumberPattern = '/^\d{10}$/';
                    if (!preg_match($phoneNumberPattern, $row[2])) {
                        $errorMessages[] = "Invalid phone in row $index.";
                    } else {
                        // Check if the phone number already exists in the database
                        $existingUser = User::where('phone', $row[2])->first();
                        if ($existingUser) {
                            $errorMessages[] = "Phone number already exists in row $index.";
                        }
                    }

                    // Validate email address
                    if (!filter_var($row[3], FILTER_VALIDATE_EMAIL)) {
                        $errorMessages[] = "Invalid email address in row $index.";
                    } else {
                        // Check if the email already exists in the database
                        $existingUser = User::where('email', $row[3])->first();
                        if ($existingUser) {
                            $errorMessages[] = "Email address already exists in row $index.";
                        }
                    }
                }

                if (!empty($errorMessages)) {
                    return Redirect::to('/admin/users/importuser')
                        ->withErrors($errorMessages)
                        ->withInput();
                } else {
                    // All data is valid, save it to the database
                    // (your data processing code)
                    try {
                        // Loop through $csvData (your CSV data) and insert each row into the database
                        foreach ($csvData as $row) {


                            // Insert the data into the database using your Eloquent model or DB query
                            // Example (assuming you have an Eloquent model named User):
                            $user = new User([
                                'name' => $row[0],
                                'user_type' => $row[1],
                                'phone' => $row[2],
                                'email' => $row[3],
                                'dob' => $formattedDOB ?? "",
                                // 'national_identity_type' => $row[4],
                                // 'national_identity_number' => $row[5],
                                // 'id_expiry_date' => $formatted_id_expiry_date,
                                'slug' => $this->createSlug($row[0], 'users') // Fixed argument order
                            ]);

                            $user->save();
                        }

                        // Set a success message for the user
                        Session::flash('success_message', "User details saved successfully.");

                        return Redirect::to('/admin/users/importuser');
                    } catch (\Exception $e) {
                        // Handle database insertion errors
                        return Redirect::to('/admin/users/importuser')
                            ->withErrors(["Database error: " . $e->getMessage()])
                            ->withInput();
                    }
                }
            }
        }
        return view('admin.users.importuser', ['title' => $pageTitle, $activetab => 1]);
    }

    public function transLimit(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'transactions-limit');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigtranslimit';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Configure Transactions Limit';
        $activetab = 'actconfigtranslimit';
        $query = new Walletlimit();
        $query = $query->sortable();

        $allrecords = $query->orderBy('category_for', 'ASC')->get();

        if ($request->ajax()) {
            return view('elements.admin.users.limitTrans', ['allrecords' => $allrecords]);
        }

        return view('admin.users.transLimit', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $allrecords]);
    }

    public function editTransLimit($slug)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-transaction-limit', $slug);
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigtranslimit';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Edit Transactions Limit';
        $activetab = 'actconfigtranslimit';

        $input = Input::all();

        // echo"<pre>";print_r($input);die;
        if (!empty($input)) {
            $rules = array(
                'daily_limit' => 'required|numeric',
                'week_limit' => 'required|numeric',
                'month_limit' => 'required|numeric',
            );
            $customMessages = [
                'daily_limit.required' => 'Daily Limit field can\'t be left blank.',
                'daily_limit.numeric' => 'Invalid Daily Limit! User number only.',
                'week_limit.required' => 'Week Limit field can\'t be left blank.',
                'week_limit.numeric' => 'Invalid Week Limit! User number only.',
                'month_limit.required' => 'Month Limit field can\'t be left blank.',
                'month_limit.numeric' => 'Invalid Month Limit! User number only.',
            ];
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return Redirect::to('/admin/users/edit-transaction-limit/' . $slug)->withErrors($validator)->withInput();
            } else {
                Walletlimit::where('id', $slug)->update(['daily_limit' => $input['daily_limit'], 'week_limit' => $input['week_limit'], 'month_limit' => $input['month_limit'], 'edited_by' => Session::get('adminid'), 'updated_at' => date('Y-m-d H:i:s')]);

                Session::flash('success_message', "Transaction Limit updated successfully.");
                return Redirect::to('admin/users/transactions-limit');
            }
        }

        $limit = Walletlimit::where('id', $slug)->first();
        return view('admin.users.editTransLimit', ['title' => $pageTitle, $activetab => 1, 'limit' => $limit]);
    }


    public static function getMerchantName($id)
    {
        return DB::table('users')
            ->where('id', $id)
            ->select('id', 'name', 'isBulkUser')
            ->first();
    }


    public function editAll($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-users');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Edit User';
        $activetab = 'actallusers';


        $recordInfo = User::where('slug', $slug)->first();


        if (empty($recordInfo)) {
            return Redirect::to('admin/users');
        }

        $input = Input::all();


        if (!empty($input)) {
            // Define validation rules
            $rules = [
                'name' => 'required|max:50',
                'phone' => 'required|min:6|max:12|unique:users,phone,' . $recordInfo->id,

            ];
            if (!empty($recordInfo->email)) {
                $rules['email'] = 'email|unique:users,email,' . $recordInfo->id;
            }


            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/users/edit-users/' . $slug)->withErrors($validator)->withInput();
            } else {



                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                User::where('id', $recordInfo->id)->update($serialisedData);

                $user_id = $recordInfo->id;
                $qrString = $user_id . "##" . $recordInfo->name;
                $qrCode = $this->generateQRCode($qrString, $user_id);

                User::where('id', $user_id)->update(array('qr_code' => $qrCode));


                Session::flash('success_message', "User details updated successfully.");
                //return Redirect::to('admin/users');

                if ($recordInfo->user_type == "Submitter" || $recordInfo->user_type == "Approver") {
                    return Redirect::to('admin/users/all');
                } else {
                    return Redirect::to('admin/users');
                }
            }
        }
        return view('admin.users.editall', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    public function payClientRebate(Request $request, $slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'payclient');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actusers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Pay Rebate';
        $activetab = 'actusers';


        $pageTitle = 'Adjust Rebate';

        $recordInfo = User::where('slug', $slug)->first();
        $getTrans = Transaction::where('receiver_id', $recordInfo->id)->where('payment_mode', "Card Activation Rebate")->first();
        if ($getTrans) {
            Session::flash('success_message', 'Already paid rebate amount.');
            return Redirect::to('/admin/users');
        }

        $input = Input::all();
        if (!empty($input)) {
            $rules = array(
                'amount' => 'required|numeric',
                'reason' => 'required',
            );
            $customMessages = [
                'amount.required' => 'Amount field can\'t be left blank.',
                'amount.numeric' => 'Invalid Amount',
                'reason.required' => 'Reason field can\'t be left blank.',
            ];
            $validator = Validator::make($input, $rules, $customMessages);

            if ($validator->fails()) {
                return Redirect::to('/admin/users/payclientrebate/' . $slug)->withErrors($validator)->withInput();
            } else {

                $remainBal = $input['amount'];
                $adminBal = $input['amount'];
                $admin = Admin::where('id', 1)->first();
                $amount = $admin->wallet_balance;

                if ($input['amount'] >= $amount) {
                    Session::flash('error_message', 'Insufficient Balance');
                    return Redirect::to('/admin/users/payclientrebate/' . $slug);
                }

                $billing_description = '<br>Rebate Deposit<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];
                $refrence_id = time() . rand() . Session::get('user_id');
                $trans = new Transaction([
                    "user_id" => 1,
                    "receiver_id" => $recordInfo->id,
                    "amount" => $input['amount'],
                    "transaction_amount" => 0,
                    "currency" => 'IQD',
                    "trans_type" => 1, //Credit
                    "total_amount" => $input['amount'],
                    "payment_mode" => 'Card Activation Rebate',
                    "trans_for" => 'Admin',
                    "refrence_id" => $refrence_id,
                    "billing_description" => $billing_description,
                    "amount_value" => $remainBal,
                    "remainingWalletBalance" => ($recordInfo->wallet_balance + $input['amount']),
                    "beforeBalance" => $recordInfo->wallet_balance,
                    "afterBalance" => ($recordInfo->wallet_balance + $input['amount']),
                    "status" => 1,
                    "edited_by" => Session::get('adminid'),
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ]);
                $trans->save();
                $TransId = $trans->id;

                $admin_wallet = $amount - $adminBal;
                $billing_description = '<br>Rebate Deposit<br>IP:' . $this->get_client_ip() . '##Amount ' . $input['amount'] . '##Reason: ' . $input['reason'];
                Admin::where('id', 1)->update(['wallet_balance' => $admin_wallet, 'updated_at' => date('Y-m-d H:i:s')]);
                $user_wallet = $recordInfo->wallet_balance + $remainBal;
                $credit = new TransactionLedger([
                    'user_id' => $recordInfo->id,
                    'opening_balance' => $recordInfo->wallet_balance,
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

                $userCard = UserCard::where('userId', $recordInfo->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();

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

                $title = __('message_app.fund_added_tit');
                $message = __('message_app.fund_added_des', ['amount' => $input['amount']]);
                $device_token = $recordInfo->device_token;
                $device_type = $recordInfo->device_type;

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
                    'user_id' => $recordInfo->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                Session::flash('success_message', "Client Balance Adjusted Successfully.");
                if ($recordInfo->user_type == 'User') {
                    return Redirect::to('/admin/users');
                }
            }
        }

        return view('admin.users.payclientrebate', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    public function travelDocumentList(Request $request, $slug)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'travel-document');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'acttraveldocument';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'Travel Document';
        $activetab = 'acttraveldocument';

        $query = DB::table('travel_documents');

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $query = $query->where('userId', $slug);
        $data = $query->orderBy('id', 'DESC')->paginate(20);

        if ($request->ajax() || $page > 1) {
            return view('elements.admin.users.travel_document', ['allrecords' => $data, 'page' => $page]);
        }
        return view('admin.users.travel_document', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $data, 'page' => $page]);
    }

    public function approveTravel($slug = null)
    {
        if ($slug) {
            DB::table('travel_documents')->where('id', $slug)->update(array('status' => 'approved'));
            $getData = DB::table('travel_documents')->where('id', $slug)->first();

            $recordInfo = User::where('id', $getData->userId)->first();
            $title = __("message_app.Approved document");
            $message = __("message_app.Travel document approved successfully");
            $device_token = $recordInfo->device_token;
            $device_type = $recordInfo->device_type;

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'DOCUMENT',
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
                'user_id' => $recordInfo->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            Session::flash('success_message', "User Document approved successfully.");
            return redirect()->back();

        }
    }
    public function declineTravel($slug = null)
    {
        if ($slug) {
            DB::table('travel_documents')->where('id', $slug)->update(array('status' => 'declined'));
            $getData = DB::table('travel_documents')->where('id', $slug)->first();

            $recordInfo = User::where('id', $getData->userId)->first();
            $title = __("message_app.Declined document");
            $message = __("message_app.Travel document declined successfully");
            $device_token = $recordInfo->device_token;
            $device_type = $recordInfo->device_type;

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'DOCUMENT',
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
                'user_id' => $recordInfo->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();
            Session::flash('success_message', "Travel Document declined successfully.");
            return redirect()->back();
        }
    }


    public function gabonVisaStampDocument(Request $request, $slug)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'users');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'acttraveldocument';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'Gabon Visa Stamp';
        $activetab = 'acttraveldocument';
        $data = DB::table('users')->where('slug', $slug)->first();
        if ($data->gabonStampImg === '') {
            Session::flash('success_message', "Gabon visa stamp document not found.");
            return redirect('/admin/users');
        }
        return view('admin.users.gabon_visa_stamp', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $data]);
    }

    public function approveGabonStamp($slug = null)
    {
        if ($slug) {
            DB::table('users')->where('id', $slug)->update(array('gabonStampStatus' => 'approved'));
            $recordInfo = User::where('id', $slug)->first();
            $title = __("message_app.Approved gabon visa stamp");
            $message = __("message_app.Gabon visa stamp approved successfully");
            $device_token = $recordInfo->device_token;
            $device_type = $recordInfo->device_type;

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'DOCUMENT',
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
                'user_id' => $recordInfo->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            Session::flash('success_message', "Gabon visa stamp approved successfully.");
            return redirect()->back();

        }
    }
    public function declineGabonStamp($slug = null)
    {
        if ($slug) {
            DB::table('users')->where('id', $slug)->update(array('gabonStampStatus' => 'declined'));
            $recordInfo = User::where('id', $slug)->first();
            $title = __("message_app.Declined document");
            $message = __("message_app.Gabon visa stamp declined successfully");
            $device_token = $recordInfo->device_token;
            $device_type = $recordInfo->device_type;

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'DOCUMENT',
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
                'user_id' => $recordInfo->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();
            Session::flash('success_message', "Gabon visa stamp declined successfully.");
            return redirect()->back();
        }
    }
}
