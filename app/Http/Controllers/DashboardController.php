<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DriverActivationCard;
use Illuminate\Http\Request;
use Mail;
use DB;
use Session;
use Redirect;
use Input;
use SimpleXMLElement;
use App\Models\User;
use App\Models\UploadedExcel;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\Country;
use App\Models\Beneficiary;
use App\Models\Issuertrxref;
use App\Models\ExcelTransaction;
use App\Models\WalletManager;
use App\Models\TransactionLimit;
use App\Models\TransactionLedger;
use App\Models\FeeApply;
use App\Models\Currency;
use App\Models\Admin;
use App\Models\UserCard;
use App\Models\IbanGeneratedList;
use App\Walletlimit;
use App\Models\RemittanceData;
use App\Models\OnafriqaData;
use App\Exports\ReportExport1;
use App\Exports\TransactionHistoryExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Validator;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Imports\ExcelImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use App\Models\Transactionfee;
use GuzzleHttp\Client;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Http;
use App\Exports\CustomerDepositExport;
use App\Exports\FailerTransactionExport;
use App\Exports\SuccessTransactionExport;
use App\Exports\OperationOfMonthExport;
use App\Helpers\SmileSigner;
use App\Services\CardService;
use App\Services\SmsService;
use App\Services\FirebaseService;
class DashboardController extends Controller
{

    private $apiUrl = APIURL;
    private $authString;
    public $cardService;
    public $smsService;
    public function __construct(SmsService $smsService, CardService $cardService)
    {
        $this->authString = base64_encode(CORPORATECODE . ':' . CORPORATEPASS);
        $this->cardService = $cardService;
        $this->smsService = $smsService;
        $this->firebaseNotificationService = new FirebaseService();

        // $this->middleware('isDriverlogin');
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

    public function dashboard()
    {

        if (Auth::user()->user_type != "Merchant") {
            abort(404, 'Page not found');
        }

        $user = Auth::user();
        $wallet_balance = User::where('id', $user->id)->first()->wallet_balance;
        $holdAmount = User::where('id', $user->id)->first()->holdAmount;
        $pageTitle = 'Dashboards';

        $parent_id = $user->id;
        //Number of Operations of the Month
        $currentMonth = Carbon::now()->format('Y-m');
        $currentMonthTransactions = ExcelTransaction::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->count();
        $totalTransactions = ExcelTransaction::where('parent_id', $parent_id)->count();
        $opt_this_month = round(($currentMonthTransactions > 0) ? ($currentMonthTransactions / $totalTransactions) * 100 : 0);

        //Number of Success
        $successfull_transactions = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->count();

        //Number of Failure Transaction
        $failure_transactions = ExcelTransaction::select('count(*) as allcount')->where('excel_transactions.parent_id', $parent_id)->whereNotNull('excel_transactions.remarks')->count();

        //Movement of Customer Deposits
        $total_deposit = Transaction::where('receiver_id', $parent_id)
            ->where('status', 1)
            ->where('payment_mode', 'wallet2wallet')
            // ->where('trans_type', 1)
            ->sum('amount_value');

        //Movement of Shipments
        $total_transfer = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.receiver_id', 0)->where('transactions.status', 1)->sum('amount_value');

        //to calculate total fees

        $total_fees = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->sum('fees');


        $total_fees_formatted = number_format($total_fees, 0, '.', ',');


        return view('dashboard.dashboard', ['title' => $pageTitle, 'wallet_balance' => intval($wallet_balance), 'holdAmount' => intval($holdAmount), 'opt_this_month' => intval($opt_this_month), 'successfull_transactions' => intval($successfull_transactions), 'failure_transactions' => intval($failure_transactions), 'total_transfer' => CURR . ' ' . intval($total_transfer), 'total_deposit' => CURR . ' ' . intval($total_deposit), 'total_fees' => CURR . ' ' . $total_fees_formatted]);
    }

    public function creatUser()
    {
        $pageTitle = 'Create User';
        $input = Input::all();
        if (!empty($input)) {
            $validate_data = [
                'role' => 'required',
                'name' => 'required',
                'email' => ['required', 'email', Rule::unique('users', 'email')],
                'phone' => ['required', Rule::unique('users', 'phone')],
            ];

            $customMessages = [
                'role.required' => __('message.Role field can\'t be left blank'),
                'name.required' => __('message.Name field can\'t be left blank'),
                'email.required' => __('message.Email field can\'t be left blank'),
                'email.email' => __('message.Please provide a valid email address'),
                'email.unique' => __('message.Email is already exist'),
                'phone.required' => __('message.Please provide a phone number'),
                'phone.unique' => __('message.Phone number is already exist'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/create-user')->withInput()->withErrors($messages);
            }

            $user = new User([
                'name' => $input['name'],
                'parent_id' => Auth::user()->id,
                'email' => $input['email'],
                'user_type' => $input['role'],
                'phone' => $input['phone'],
                'is_verify' => 1,
                "slug" => $this->createSlug($input['name'], 'users'),
            ]);
            $user->save();
            $encId = HTTP_PATH . '/generate-password/' . base64_encode($this->encryptContent($user->id));
            $emailSubject = "Swap Wallet - Account created successfully";
            $emailData['subject'] = $emailSubject;
            $emailData['name'] = ucfirst($input['name']);
            $emailData['email'] = $input['email'];
            $emailData['link'] = $encId;
            $emailId = $input['email'];
            /* Mail::send('emails.generatePasswordLink', $emailData, function ($message) use ($emailData, $emailId) {
                $message->to($emailId, $emailId)
                    ->subject($emailData['subject']);
            }); */

            Session::put('success_message', __('message.The user has been created successfully and an autogenerated password link has been mailed to generate password on the registered email address'));
            return Redirect::to('/create-user');
        }
        return view('dashboard.create_user', ['title' => $pageTitle]);
    }

    public function getUserList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        $totalRecords = User::select('count(*) as allcount')->where('parent_id', Auth::user()->id)->whereIn('user_type', ['Submitter', 'Approver'])->where('status', '!=', 2)->count();
        $totalRecordswithFilter = User::select('count(*) as allcount')->where('parent_id', Auth::user()->id)->whereIn('user_type', ['Submitter', 'Approver'])->where('status', '!=', 2)->count();
        // DB::enableQueryLog();
        $records = User::orderBy($columnName, $columnSortOrder)
            ->where('parent_id', Auth::user()->id)
            ->whereIn('user_type', ['Submitter', 'Approver'])
            ->where('status', '!=', 2)
            ->select('*')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();
        foreach ($records as $record) {
            $action = '<a href="javascript:void(0);" class="btn btn-primaryx" data-bs-toggle="modal" data-bs-target="#delete_view" onclick="delete_user(' . "'" . $record->slug . "'" . ')">' . __('message.Remove') . '</a>';

            $lang = Session::get('locale');



            if ($lang === 'fr') {
                $userTypeText = ($record->user_type === 'Submitter') ? 'Auteur' :
                    (($record->user_type === 'Approver') ? 'Approbateur' : $record->user_type);
            } else {
                $userTypeText = $record->user_type; // Default to English or whatever is in the database
            }

            $status = '';
            if ($lang === 'fr') {
                $status = 'Actif';

            } else {
                $status = 'Active'; // Default to English or whatever is in the database
            }


            $data_arr[] = array(
                "name" => ucfirst($record->name),
                "phone" => $record->phone,
                "email" => ucfirst($record->email),
                "parent_id" => Auth::user()->id,
                "user_type" => $userTypeText,
                "status" => $status,
                "action" => $action,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function deleteUser($slug)
    {
        User::where('slug', $slug)->update(['status' => 2, 'is_verify' => 0]);
        Session::put('success_message', __('message.User has been deleted successfully'));
        return Redirect::to('/create-user');
    }

    public function generatePassword($slug)
    {
        $pageTitle = 'Generate Password';
        $decode_string = base64_decode($slug);
        $user_id = $this->decryptContent($decode_string);
        $userInfo = User::where('id', $user_id)->first();
        if (empty($userInfo)) {
            Session::put('error_message', __('message.Invalid User!'));
            return Redirect::to('/login');
        }
        if ($userInfo->password != "") {
            Session::put('error_message', __('message.Invalid Link!'));
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
                return Redirect::to('/generate-password/' . $slug);
            }
            $hashedPassword = Hash::make($input['password']);
            User::where('id', $user_id)->update(['password' => $hashedPassword, 'is_email_verified' => 1]);
            Session::put('success_message', __('message.Password has been updated successfully'));
            Auth::logout();
            Session::forget('user_id');
            return Redirect::to('/login');
        }
        return view('dashboard.generate_password', ['title' => $pageTitle]);
    }

    public function submitterDashboard(Request $request)
    {
        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }

        $pageTitle = 'Dashboard';
        $parent_id = Auth::user()->parent_id;
        $wallet_balance = User::where('id', $parent_id)->first()->wallet_balance;
        $holdAmount = User::where('id', $parent_id)->first()->holdAmount;

        //Number of Operations of the Month
        $currentMonth = Carbon::now()->format('Y-m');
        $currentMonthTransactions = ExcelTransaction::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->count();
        $totalTransactions = ExcelTransaction::where('parent_id', $parent_id)->count();
        $opt_this_month = round(($currentMonthTransactions > 0) ? ($currentMonthTransactions / $totalTransactions) * 100 : 0);

        //Number of Success
        $successfull_transactions = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->count();

        //Number of Failure Transaction
        $failure_transactions = ExcelTransaction::select('count(*) as allcount')->where('excel_transactions.parent_id', $parent_id)->whereNotNull('excel_transactions.remarks')->count();

        //Movement of Customer Deposits
        $total_deposit = Transaction::where('receiver_id', $parent_id)
            ->where('status', 1)
            ->where('payment_mode', 'wallet2wallet')
            // ->where('trans_type', 2)
            ->sum('amount_value');

        //Movement of Shipments
        $total_transfer = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.receiver_id', 0)->where('transactions.status', 1)->sum('amount_value');

        //to calculate total fees
        $total_fees = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->sum('fees');

        $total_fees_formatted = number_format($total_fees, 0, '.', ',');
        return view('dashboard.submitter_dashbaord', ['title' => $pageTitle, 'wallet_balance' => intval($wallet_balance), 'holdAmount' => intval($holdAmount), 'opt_this_month' => intval($opt_this_month), 'successfull_transactions' => intval($successfull_transactions), 'failure_transactions' => intval($failure_transactions), 'total_deposit' => CURR . ' ' . intval($total_deposit), 'total_transfer' => CURR . ' ' . intval($total_transfer), 'total_fees' => CURR . ' ' . $total_fees_formatted]);
    }

    public function getExcelList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        $user_id = Auth::user()->id;
        $totalRecords = UploadedExcel::select('count(*) as allcount')->whereIn('status', [1, 2, 3, 4, 5, 6])->where('user_id', $user_id)->count();
        $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->whereIn('status', [1, 2, 3, 4, 5, 6])->where('user_id', $user_id)->count();
        // DB::enableQueryLog();
        $records = UploadedExcel::orderBy($columnName, $columnSortOrder)
            ->where('user_id', $user_id)->whereIn('status', [1, 2, 3, 4, 5, 6])
            ->select('*')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();
        foreach ($records as $record) {
            $action = '
            <a href="' . $record->id . '" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="' . __('View') . '"></i></a>' .
                ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="' . __('Download Excel ') . '"></i></a>' : '');
            $data_arr[] = array(
                "reference_id" => $record->reference_id,
                "remarks" => $record->remarks != "" ? ucfirst($record->remarks) : 'Salary',
                "excel" => $record->excel,
                "no_of_records" => $record->no_of_records,
                "created_at" => date('d M,y', strtotime($record->created_at)),
                "totat_amount" => CURR . ' ' . $record->totat_amount,
                "total_fees" => CURR . ' ' . $record->total_fees,
                "status" => $this->getStatus($record->status),
                "action" => $action,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function getExcelTransaction()
    {
        $input = Input::all();
        if (!empty($input)) {
            return view('dashboard.get_excel_transaction', ['id' => $input['id']]);
        }
    }

    public function approverDashboard()
    {
        if (Auth::user()->user_type != "Approver") {
            abort(404, 'Page not found');
        }
        $parent_id = Auth::user()->parent_id;
        $wallet_balance = User::where('id', $parent_id)->first()->wallet_balance;
        $holdAmount = User::where('id', $parent_id)->first()->holdAmount;

        //Number of Operations of the Month
        $currentMonth = Carbon::now()->format('Y-m');
        $currentMonthTransactions = ExcelTransaction::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->count();
        $totalTransactions = ExcelTransaction::where('parent_id', $parent_id)->count();
        $opt_this_month = round(($currentMonthTransactions > 0) ? ($currentMonthTransactions / $totalTransactions) * 100 : 0);

        //Number of Success
        $successfull_transactions = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->count();

        //Number of Failure Transaction
        $failure_transactions = ExcelTransaction::select('count(*) as allcount')->where('excel_transactions.parent_id', $parent_id)->whereNotNull('excel_transactions.remarks')->count();

        //Movement of Customer Deposits
        $total_deposit = Transaction::where('receiver_id', $parent_id)
            ->where('status', 1)
            ->where('payment_mode', 'wallet2wallet')
            // ->where('trans_type', 2)
            ->sum('amount_value');

        //Movement of Shipments
        $total_transfer = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.receiver_id', 0)->where('transactions.status', 1)->sum('amount_value');

        //to calculate total fees
        $total_fees = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->sum('fees');
        $total_fees_formatted = number_format($total_fees, 0, '.', ',');

        $pageTitle = 'Dashboard';
        return view('dashboard.approver_dashbaord', ['title' => $pageTitle, 'wallet_balance' => intval($wallet_balance), 'holdAmount' => intval($holdAmount), 'opt_this_month' => intval($opt_this_month), 'successfull_transactions' => intval($successfull_transactions), 'failure_transactions' => intval($failure_transactions), 'total_deposit' => CURR . ' ' . intval($total_deposit), 'total_transfer' => CURR . ' ' . intval($total_transfer), 'total_fees' => CURR . ' ' . $total_fees_formatted]);
    }

    // public function approverGetExcelList(Request $request)
    // {
    //     $draw = $request->get('draw');
    //     $start = $request->get("start");
    //     $rowperpage = $request->get("length") !='-1' ?  $request->get("length") : 5; // total number of rows per page
    //     $columnIndex_arr = $request->get('order');
    //     $columnName_arr = $request->get('columns');
    //     $order_arr = $request->get('order');
    //     $searchValue = $request->get('search');
    //     $columnIndex = $columnIndex_arr[0]['column']; // Column index
    //     $columnName = $columnName_arr[$columnIndex]['data']; // Column name
    //     $columnSortOrder = $order_arr[0]['dir']; // asc or desc
    //     // $searchValue = $search_arr['value']; // Search value
    //     $daterange = $request->get('daterange');
    //     // DB::enableQueryLog();
    //     $query=new UploadedExcel();
    //     if(Auth::user()->user_type=="Submitter")
    //     {
    //         $user_id = Auth::user()->id;
    //         // echo"<pre>";print_r($columnName);
    //         $parent_id = Auth::user()->parent_id;
    //         $totalRecords = UploadedExcel::select('count(*) as allcount')->whereIn('status',[1,2,3,4,5,6])->where('user_id',$user_id)->count();
    //         $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->whereIn('status',[1,2,3,4,5,6])->where('user_id',$user_id) ->where(function($q) use ($searchValue,$daterange) {
    //         $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
    //         })->where(function($q) use ($daterange) {
    //             if($daterange)
    //             {
    //                 $dt = explode(' - ', $daterange);
    //                 $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
    //                 $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
    //                 $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
    //                                ->whereDate('uploaded_excels.created_at', '<=', $end_date);
    //             }
    //         })
    //         ->count();
    //         // DB::enableQueryLog();
    //         $records = UploadedExcel::where('user_id',$user_id)->whereIn('status',[1,2,3,4,5,6])
    //         ->where(function($q) use ($searchValue) {
    //             $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
    //         })->where(function($q) use ($daterange) {
    //             if($daterange)
    //             {
    //                 $dt = explode(' - ', $daterange);
    //                 $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
    //                 $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
    //                 $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
    //                                ->whereDate('uploaded_excels.created_at', '<=', $end_date);
    //             }
    //         })
    //         ->orderBy('created_at', 'desc') 
    //         ->skip($start)
    //         ->take($rowperpage)
    //         ->get();
    //         $totalCredit = Transaction::where('status', 1)
    //                 ->where('receiver_id', Auth::user()->id)
    //                 ->where('trans_type', 1)
    //                 ->sum('amount_value');
    //                 $totalCredit= intval($totalCredit);
    //             $totalDebit = Transaction::where('status', 1)
    //                 ->where('user_id', Auth::user()->id)
    //                 ->where('trans_type', 2)
    //                 ->sum('amount_value');
    //                 $totalDebit= intval($totalDebit);
    //     }
    //     if(Auth::user()->user_type=="Approver")
    //     {
    //         $parent_id = Auth::user()->parent_id;
    //         $totalRecords = UploadedExcel::select('count(*) as allcount')->where('parent_id',$parent_id)->whereIn('status',[3,4,5,6])->count();
    //         $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->where('parent_id',$parent_id)->whereIn('status',[3,4,5,6])  ->where(function($q) use ($searchValue,$daterange) {
    //             $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
    //         })->where(function($q) use ($daterange) {
    //             if($daterange)
    //             {
    //                 $dt = explode(' - ', $daterange);
    //                 $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
    //                 $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
    //                 $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
    //                                ->whereDate('uploaded_excels.created_at', '<=', $end_date);
    //             }
    //         })->count();
    //         // DB::enableQueryLog();
    //         $records = UploadedExcel::where('parent_id',$parent_id)
    //         ->whereIn('status',[3,4,5,6])
    //         ->where(function($q) use ($searchValue) {
    //             $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
    //             $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
    //         })->where(function($q) use ($daterange) {
    //             if($daterange)
    //             {
    //                 $dt = explode(' - ', $daterange);
    //                 $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
    //                 $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
    //                 $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
    //                                ->whereDate('uploaded_excels.created_at', '<=', $end_date);
    //             }
    //         })
    //         ->orderBy('created_at', 'desc') 
    //         ->skip($start)
    //         ->take($rowperpage)
    //         ->get();
    //         // $totalCredit = number_format(Transaction::where('status', 1)
    //         // ->where('receiver_id', $parent_id)
    //         // ->where('trans_type', 1)
    //         // ->sum('amount_value'), 2);
    //         // $totalCredit = Transaction::where('status', 1)
    //         //         ->where('receiver_id', Auth::user()->id)
    //         //         ->where('trans_type', 1)
    //         //         ->sum('amount_value');
    //         //         $totalCredit= intval($totalCredit);
    //         //     $totalDebit = Transaction::where('status', 1)
    //         //         ->where('user_id', Auth::user()->id)
    //         //         ->where('trans_type', 2)
    //         //         ->sum('amount_value');
    //         //         $totalDebit= intval($totalDebit);
    //     }
    //     if (Auth::user()->user_type == "Merchant") {
    //         $parent_id = Auth::user()->id;
    //         $totalRecords = UploadedExcel::where('parent_id', $parent_id)
    //             ->whereIn('status', [5, 6])
    //             ->count();
    //         $totalRecordswithFilter = UploadedExcel::where('parent_id', $parent_id)
    //             ->whereIn('status', [5, 6])
    //             ->where(function($q) use ($searchValue,$daterange) {
    //                 $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
    //                 $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
    //                 $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
    //             })->where(function($q) use ($daterange) {
    //                 if($daterange)
    //                 {
    //                     $dt = explode(' - ', $daterange);
    //                     $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
    //                     $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
    //                     $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
    //                                    ->whereDate('uploaded_excels.created_at', '<=', $end_date);
    //                 }
    //             })
    //             ->count();
    //         // DB::enableQueryLog();
    //         $records = UploadedExcel::where('parent_id', $parent_id)
    //             ->whereIn('status', [5, 6])
    //             ->where(function($q) use ($searchValue) {
    //                 $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
    //                 $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
    //                 $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
    //             })->where(function($q) use ($daterange) {
    //                 if($daterange)
    //                 {
    //                     $dt = explode(' - ', $daterange);
    //                     $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
    //                     $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
    //                     $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
    //                                    ->whereDate('uploaded_excels.created_at', '<=', $end_date);
    //                 }
    //             })
    //             ->orderBy('created_at', 'desc') 
    //             ->skip($start)
    //             ->take($rowperpage)
    //             ->get();
    //             // $totalCredit = Transaction::where('status', 1)
    //             //     ->where('receiver_id', Auth::user()->id)
    //             //     ->where('trans_type', 1)
    //             //     ->sum('amount_value');
    //             //     $totalCredit= intval($totalCredit);
    //             // $totalDebit = Transaction::where('status', 1)
    //             //     ->where('user_id', Auth::user()->id)
    //             //     ->where('trans_type', 2)
    //             //     ->sum('amount_value');
    //             //     $totalDebit= intval($totalDebit);
    //     //    dd(DB::getQueryLog());
    //     }
    //     // print_r($searchValue);
    //     // echo"<pre>";print_r($records);
    //     $data_arr = array();
    //     foreach ($records as $record) {
    //         $action = '
    //         <a href="'.$record->id.'" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="View"></i></a>' .
    //         ($record->type == 0 ? '<a href="'.PUBLIC_PATH.'/assets/front/excel/'.$record->excel.'" class="" download="'.$record->excel.'"><i class="fa fa-download" aria-hidden="true" title="Download Excel"></i></a>' : '');
    //         $data_arr[] = array(
    //             "reference_id" =>$record->reference_id,
    //             "remarks" => $record->remarks!="" ?  ucfirst($record->remarks) : 'Salary',
    //             "excel"=>$record->excel,
    //             "no_of_records" => $record->no_of_records,
    //             "updated_at" => date('d M,y',strtotime($record->created_at)),
    //             "totat_amount" => CURR.' '.$record->totat_amount,
    //             "total_fees" => CURR.' '.$record->total_fees,
    //             "status" =>$this->getStatus($record->status),
    //             "action" =>$action,
    //         );
    //     }
    //     $response = array(
    //         "draw" => intval($draw),
    //         "iTotalRecords" => $totalRecords,
    //         "iTotalDisplayRecords" => $totalRecordswithFilter,
    //         "aaData" => $data_arr,
    //         "totalCredit"=>$totalCredit,
    //        "totalDebit"=>$totalDebit,
    //     );
    //     echo json_encode($response);
    //     die;
    // }

    public function approverGetExcelList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length") != '-1' ? $request->get("length") : 5; // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $searchValue = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        // $searchValue = $search_arr['value']; // Search value
        $daterange = $request->get('daterange');
        // DB::enableQueryLog();
        $query = new UploadedExcel();
        $lang = Session::get('locale');



        if (Auth::user()->user_type == "Submitter") {
            $user_id = Auth::user()->id;
            // echo"<pre>";print_r($columnName);
            $parent_id = Auth::user()->parent_id;
            $totalRecords = UploadedExcel::select('count(*) as allcount')->whereIn('status', [1, 2, 3, 4, 5, 6])->where('user_id', $user_id)->count();
            $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->whereIn('status', [1, 2, 3, 4, 5, 6])->where('user_id', $user_id)->where(function ($q) use ($searchValue, $daterange) {
                $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
                $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
                $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
            })->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                        ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                }
            })
                ->count();
            // DB::enableQueryLog();
            $records = UploadedExcel::where('user_id', $user_id)->whereIn('status', [1, 2, 3, 4, 5, 6])
                ->where(function ($q) use ($searchValue) {
                    $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
                    $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
                    $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
                })->where(function ($q) use ($daterange) {
                    if ($daterange) {
                        $dt = explode(' - ', $daterange);
                        $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                        $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                        $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                            ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                    }
                })
                ->orderBy('created_at', 'desc')
                ->skip($start)
                ->take($rowperpage)
                ->get();

            $totalCredit = Transaction::where('status', 1)
                ->where('receiver_id', $parent_id)
                ->where('trans_type', 1)
                ->sum('amount_value');
            $total_Credit_formatted = number_format($totalCredit, 0, '.', ',');


            $totalDebit = Transaction::where('status', 1)
                ->where('user_id', $parent_id)
                ->where('trans_type', 2)
                ->sum('amount');

            $total_Debit_formatted = number_format($totalDebit, 0, '.', ',');
        }
        if (Auth::user()->user_type == "Approver") {
            $parent_id = Auth::user()->parent_id;
            $totalRecords = UploadedExcel::select('count(*) as allcount')->where('parent_id', $parent_id)->whereIn('status', [3, 4, 5, 6])->count();
            $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->where('parent_id', $parent_id)->whereIn('status', [3, 4, 5, 6])->where(function ($q) use ($searchValue, $daterange) {
                $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
                $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
                $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
            })->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                        ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                }
            })->count();
            // DB::enableQueryLog();
            $records = UploadedExcel::where('parent_id', $parent_id)
                ->whereIn('status', [3, 4, 5, 6])
                ->where(function ($q) use ($searchValue) {
                    $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
                    $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
                    $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
                })->where(function ($q) use ($daterange) {
                    if ($daterange) {
                        $dt = explode(' - ', $daterange);
                        $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                        $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                        $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                            ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                    }
                })
                ->orderBy('created_at', 'desc')
                ->skip($start)
                ->take($rowperpage)
                ->get();

            $totalCredit = Transaction::where('status', 1)
                ->where('receiver_id', $parent_id)
                ->where('trans_type', 1)
                ->sum('amount_value');
            $total_Credit_formatted = number_format($totalCredit, 0, '.', ',');

            $totalDebit = Transaction::where('status', 1)
                ->where('user_id', $parent_id)
                ->where('trans_type', 2)
                ->sum('amount');
            $total_Debit_formatted = number_format($totalDebit, 0, '.', ',');
        }


        if (Auth::user()->user_type == "Merchant") {
            $parent_id = Auth::user()->id;


            $totalRecords = UploadedExcel::where('parent_id', $parent_id)
                ->whereIn('status', [3, 4, 5, 6])
                ->count();


            $totalRecordswithFilter = UploadedExcel::where('parent_id', $parent_id)
                ->whereIn('status', [3, 4, 5, 6])
                ->where(function ($q) use ($searchValue, $daterange) {
                    $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
                    $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
                    $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
                })->where(function ($q) use ($daterange) {
                    if ($daterange) {
                        $dt = explode(' - ', $daterange);
                        $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                        $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                        $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                            ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                    }
                })
                ->count();


            // DB::enableQueryLog();
            $records = UploadedExcel::where('parent_id', $parent_id)
                ->whereIn('status', [3, 4, 5, 6])
                ->where(function ($q) use ($searchValue) {
                    $q->orWhere('reference_id', 'like', '%' . $searchValue . '%');
                    $q->orWhere('remarks', 'like', '%' . $searchValue . '%');
                    $q->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
                })->where(function ($q) use ($daterange) {
                    if ($daterange) {
                        $dt = explode(' - ', $daterange);
                        $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                        $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                        $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                            ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                    }
                })
                ->orderBy('created_at', 'desc')
                ->skip($start)
                ->take($rowperpage)
                ->get();


            $totalCredit = Transaction::where('status', 1)
                ->where('receiver_id', $parent_id)
                ->where('trans_type', 1)
                ->sum('amount_value');
            $total_Credit_formatted = number_format($totalCredit, 0, '.', ',');

            $totalDebit = Transaction::where('status', 1)
                ->where('user_id', $parent_id)
                ->where('trans_type', 2)
                ->sum('amount');

            $total_Debit_formatted = number_format($totalDebit, 0, '.', ',');
        }



        // print_r($searchValue);

        // echo"<pre>";print_r($records);



        $data_arr = array();
        foreach ($records as $record) {
            $transSt = '';
            $excelTrans = ExcelTransaction::where('excel_id', $record->id)->first();

            if (isset($excelTrans['bdastatus']) && $excelTrans['bdastatus'] == "BDA") {
                $transSt = 'BDA';
            } else {
                $transSt = 'ONAFRIQ';
            }

            global $months;




            if ($lang == 'fr') {
                $date = date('d F Y', strtotime($record->updated_at)); // Full year without comma
                $frenchDate = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate = date('d M Y', strtotime($record->updated_at)); // Full year
            }

            if ($lang == 'fr') {
                $date = date('d F Y', strtotime($record->created_at)); // Full year without comma
                $frenchDate1 = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate1 = date('d M Y', strtotime($record->created_at)); // Full year
            }

            $status = strtolower(trim($this->getStatus($record->status)));

            $statusText = '';

            if ($lang === 'fr') {
                if ($status == 'approved') {
                    $statusText = 'Approuvé';
                } elseif ($status == 'rejected') {
                    $statusText = 'Rejeté';
                }
            } elseif ($lang == 'en') {
                if ($status == 'approved') {
                    $statusText = 'Approved';
                } elseif ($status == 'rejected') {
                    $statusText = 'Rejected';
                }
            }

            $remark = '';
            if ($lang == 'fr') {
                $remark = $record->remarks != "" ? ucfirst($record->remarks) : __('Salaire');
            } elseif ($lang == 'en') {
                $remark = $record->remarks != "" ? ucfirst($record->remarks) : __('Salary');
            }

            $total = number_format($record->totat_amount, 0, '.', ',');

            $fees = number_format($record->total_fees, 0, '.', ',');
            $action = '
         <a href="' . $record->id . '" data-bs-toggle="modal" data-bdastatus="' . $transSt . '" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="' . __('message.View') . '"></i></a>' .
                ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="' . __('message.Download Excel') . '"></i></a>' : '');
            $data_arr[] = array(
                "reference_id" => $record->reference_id,
                // "remarks" => $record->remarks != "" ? ucfirst($record->remarks) : 'Salary',
                "remarks" => $remark,
                "excel" => $record->excel,
                "no_of_records" => $record->no_of_records,
                "updated_at" => $frenchDate1,
                "totat_amount" => CURR . ' ' . $total,
                "total_fees" => CURR . ' ' . $fees,
                // "status" => $status,
                "status" => $statusText,
                "action" => $action,
            );
        }


        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
            "totalCredit" => $total_Credit_formatted,
            "totalDebit" => $total_Debit_formatted,
        );
        echo json_encode($response);
        die;
    }

    public function pendingGetExcelList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length") != '-1' ? $request->get("length") : 5;
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        // echo"<pre>";print_r(Auth::user()->parent_id);die;
        if (Auth::user()->user_type == "Submitter") {

            $id = Auth::user()->id;
            $totalRecords = UploadedExcel::select('count(*) as allcount')->where('user_id', $id)->whereIn('status', [0])->count();
            $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->where('user_id', $id)->whereIn('status', [0])->count();
            // DB::enableQueryLog();
            $records = UploadedExcel::orderBy('created_at', 'desc')
                ->where('user_id', $id)
                ->whereIn('status', [0])
                ->select('*')
                ->skip($start)
                ->take($rowperpage)
                ->get();
        } elseif (Auth::user()->user_type == "Approver") {
            $parent_id = Auth::user()->parent_id;
            $totalRecords = UploadedExcel::select('count(*) as allcount')->where('parent_id', $parent_id)->whereIn('status', [1])->count();
            $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->where('parent_id', $parent_id)->whereIn('status', [1])->count();
            // DB::enableQueryLog();
            $records = UploadedExcel::orderBy('created_at', 'desc')
                ->join('users', 'uploaded_excels.user_id', '=', 'users.id')
                ->where('uploaded_excels.parent_id', $parent_id)
                ->whereIn('uploaded_excels.status', [1])
                ->select('uploaded_excels.*', 'uploaded_excels.id as id', 'users.name as name', 'users.email as email', 'users.user_type as user_type')
                ->skip($start)
                ->take($rowperpage)
                ->get();
        } elseif (Auth::user()->user_type == "Merchant") {

            $parent_id = Auth::user()->id;
            $totalRecords = UploadedExcel::select('count(*) as allcount')->where('parent_id', $parent_id)->whereIn('status', [3])->count();
            $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->where('parent_id', $parent_id)->whereIn('status', [3])->count();
            // DB::enableQueryLog();
            $records = UploadedExcel::orderBy('created_at', 'desc')
                ->join('users', 'uploaded_excels.user_id', '=', 'users.id')
                ->where('uploaded_excels.parent_id', $parent_id)
                ->whereIn('uploaded_excels.status', [3])
                ->select('uploaded_excels.*', 'uploaded_excels.id as id', 'users.name as name', 'users.email as email', 'users.user_type as user_type')
                ->skip($start)
                ->take($rowperpage)
                ->get();
        }
        $data_arr = array();
        foreach ($records as $record) {

            $excelTrans = ExcelTransaction::where('excel_id', $record->id)->first();

            $transSt = '';
            if ($excelTrans['bdastatus'] == "BDA") {
                $transSt = 'BDA';
            } else {
                $transSt = 'ONAFRIQ';
            }

            $encId = base64_encode($this->encryptContent($record->id));

            $userType = Auth::user()->user_type;
            $modalTarget = ($userType == "Submitter") ? "rejectSbumitter" : "rejectRequest";
            $deleteIcon = ($userType == "Submitter") ? "fa-trash-o" : "fa-ban";
            $deleteAction = ($userType == "Submitter") ? 'cancelRequestSubmitter(' . "'" . $encId . "'" . ')' : 'cancelRequest(' . "'" . $encId . "'" . ')';
            $approveAction = 'approveRequest(' . "'" . $encId . "'" . ')';
            $deleteText = ($userType == "Submitter") ? __('message.Delete') : __('message.Reject');
            $action = '
            <a href="' . $record->id . '" data-bs-toggle="modal" data-bdastatus="' . $transSt . '" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="' . __('message.View') . '"></i></a>' .
                ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="' . __('message.Download Excel') . '"></i></a>' : '') .
                '<a href="javascript:void(0)" class="" onclick="' . $approveAction . '"><i class="fa fa-check" aria-hidden="true" title="' . __('message.Approve Excel') . '"></i></a>'
                . '<a href="javascript:void(0)" class="" data-bs-toggle="modal" data-bs-target="#' . $modalTarget . '" onclick="' . $deleteAction . '"><i class="fa ' . $deleteIcon . '" aria-hidden="true" title="' . $deleteText . '"></i></a>';
            // echo"<pre>";print_r($record);die;

            global $months;
            $lang = Session::get('locale');
            if ($lang == 'fr') {
                $date = date('d F Y', strtotime($record->updated_at)); // Full year without comma
                $frenchDate = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate = date('d M Y', strtotime($record->updated_at)); // Full year
            }


            $data_arr[] = array(
                "reference_id" => $record->reference_id,
                "remarks" => $record->remarks != "" ? ucfirst($record->remarks) : 'Salary',
                "excel" => $record->excel,
                "no_of_records" => $record->no_of_records,
                "updated_at" => $frenchDate,
                "total_fees" => CURR . ' ' . $record->total_fees,
                "totat_amount" => CURR . ' ' . $record->totat_amount,
                "status" => $this->getStatus($record->status),
                "action" => $action,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    /* public function approveExcel($slug, Request $request)
    {
        $user_id = Auth::user()->id;
        $user_type = Auth::user()->user_type;
        $decode_string = base64_decode($slug);
        $id = $this->decryptContent($decode_string);
        $userData = User::where('id', $user_id)->first();
        $user_role = Auth::user()->user_type;
        $sum = 0;
        $excel_transactions = ExcelTransaction::where('excel_id', $id)->get();

        foreach ($excel_transactions as $val) {
            $sum += $val->amount; // Add the amount to the sum
        }

        if ($user_type == "Merchant") {
            $excel_transaction = ExcelTransaction::where('excel_id', $id)->get();

            //to create the access token for gimac
            $certificate = public_path("MTN Cameroon Issuing CA1.crt");
            $client = new Client([
                'verify' => $certificate,
            ]);
            $accessToken = '';
            try {
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
                //to generate a access token for gimac
                $response = $client->request('POST', env('GIMAC_TOKEN_URL'), $options);
                // Get the response body as a string
                $body = $response->getBody()->getContents();
                // Decode the JSON response
                $jsonResponse = json_decode($body);
                // Access the access_token property
                $accessToken = $jsonResponse->access_token;
            } catch (\Exception $e) {
            }

            $t_inserted_record = 0;
            $n_inserted_record = 0;

            $total_fees_arry = [];

            $senderUser = User::where('id', $user_id)->first();
            $userType = $this->getUserType($senderUser->user_type);
            $walletlimit = Walletlimit::where('category_for', $userType)->first();
            $transactionLimit = TransactionLimit::where('type', $userType)->first();
            $total_amount_to_transfer = 0;

            //               echo '<pre>';print_r($excel_transaction);exit;
            $total_count_t = $total_count_d = count($excel_transaction);
            foreach ($excel_transaction as $key => $row) {
                $lang = Session::get('locale');

                if (!isset($amount)) {
                    $amount = 0;
                }
                $tomember = 0;
                $country = $row->country_id;
                $tomember_id = $row->wallet_manager_id;
                $receviver_mobile = $row->tel_number;
                $amount = $row->amount;

                $msg = '';
                $country = Country::where('id', $country)->first();
                //to check current month limit
                $currentMonthSum = Transaction::where('user_id', $user_id)->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereIn('status', [1, 2])
                    ->sum('amount');
                if (($currentMonthSum + $amount) > $walletlimit->month_limit) {
                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('Your monthly transfer limit has been reached.')]);
                    $n_inserted_record += 1;
                    continue;
                }

                //to check current week limit
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek = Carbon::now()->endOfWeek();
                $currentWeekSum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('amount');
                if (($currentWeekSum + $amount) > $walletlimit->week_limit) {
                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('Your weekly transfer limit has been reached.')]);
                    $n_inserted_record += 1;
                    continue;
                }

                // Get the sum of amounts for transactions within the current day
                $startOfDay = Carbon::now()->startOfDay();
                $endOfDay = Carbon::now()->endOfDay();
                $currentDaySum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->sum('amount');
                if (($currentDaySum + $amount) > $walletlimit->daily_limit) {
                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('Your daily transfer limit has been reached.')]);
                    $n_inserted_record += 1;
                    continue;
                }

                $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
                $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
                if ($senderUser->kyc_status == "pending") {
                    if ($unverifiedKycMin > $amount) {
                        ExcelTransaction::where('id', $row->id)->update(['remarks' => __('The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin]);
                        $n_inserted_record += 1;
                        continue;
                    }

                    if ($unverifiedKycMax < $amount) {
                        ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . __('because your KYC is still pending.')]);
                        $n_inserted_record += 1;
                        continue;
                    }
                } else {

                    if ($unverifiedKycMin > $amount) {

                        ExcelTransaction::where('id', $row->id)->update(['remarks' => __('The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin]);
                        $n_inserted_record += 1;
                        continue;
                    }

                    if ($unverifiedKycMax < $amount) {

                        ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . __('because your KYC is not verified.Please verify your KYC first')]);
                        $n_inserted_record += 1;
                        continue;
                    }
                }

                $is_receiver_exist = User::where('phone', trim($receviver_mobile))->whereNotIn('user_type', ['Approver', 'Submitter'])->first();


                if (isset($is_receiver_exist) && $excel_transactions[0]->bdastatus == "SWAPTOSWAP") {
                    if ($user_id == $is_receiver_exist->id) { 
                        $msg = __('You cannot send funds to yourself');
                        ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
                        $total_fees = $this->calculateFees($amount, $user_id,'Send Money');
                        DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', ($amount+$total_fees));
                        DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', ($amount+$total_fees));
                        $n_inserted_record += 1;
                        continue;
                    } else {
                        if ($transactionLimit->minSendMoney > $amount) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->minSendMoney]);
                            $n_inserted_record += 1;
                            continue;
                        }

                        if ($amount > $transactionLimit->maxSendMoney) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->maxSendMoney]);
                            $n_inserted_record += 1;
                            continue;
                        }

                        $total_fees = 0;

                        $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', 'Send Money')->where('min_amount', '<=', $amount)
                            ->where('max_amount', '>=', $amount)->first();

                        if (isset($feeapply)) {
                            $feeType = $feeapply->fee_type;
                            if ($feeType == 1) {
                                $total_fees = $feeapply->fee_amount;
                            } else {
                                $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                            }
                        } else {
                            $trans_fees = Transactionfee::where('transaction_type', 'Send Money')->where('min_amount', '<=', $amount)
                                ->where('max_amount', '>=', $amount)->first();
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
                        $tOtAmt = ($total_amount + $total_fees);

                        if ($tOtAmt >= $senderUser->holdAmount) {
                            Session::put('error_message', __('Hold balance not remaining your wallet'));
                            return Redirect::to('/pending-approvals');
                            //$msg = 'Insufficient Balance !';
                           //ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
                           //$n_inserted_record += 1;
                           //continue;
                        } else {
                            $trans_id = time() . '-' . $user_id . '-' . $is_receiver_exist->id;
                            $refrence_id = time() . '-' . $is_receiver_exist->id;

                            $existingTransaction = Transaction::where('excel_trans_id', $row->id)
                                ->where('user_id', $user_id)
                                ->where('amount', $amount)
                                ->where('transaction_amount', $total_fees)
                                ->first();
                            $recieverUser = $userInfo = User::where('id', $is_receiver_exist->id)->first();

                            if (!$existingTransaction) {

                                $trans = new Transaction([
                                    'user_id' => $user_id,
                                    'receiver_id' => $is_receiver_exist->id,
                                    'amount' => $amount,
                                    'amount_value' => $total_amount,
                                    'transaction_amount' => $total_fees,
                                    'total_amount' => $amount + $total_fees,
                                    'trans_type' => 2,
                                    'excel_trans_id' => $row->id,
                                    'payment_mode' => 'wallet2wallet',
                                    'status' => 1,
                                    'refrence_id' => $trans_id,
                                    'billing_description' => 'Fund Transfer-' . $refrence_id,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);

                                $trans->save();
                                $transaction_id = $trans->id;


                                $total_amount_to_transfer += $amount;
                                $total_count = count($total_fees_arry) + 1;
                                $charge = $total_count * $total_fees;
                                $sender_wallet_amount = $senderUser->holdAmount - $total_amount_to_transfer - $charge;

                                $opening_balance_sender = $senderUser->wallet_balance + $senderUser->holdAmount;
                                $closing_balance_sender = $opening_balance_sender - ($total_amount_to_transfer + $charge); // Adjusted to $total_fees instead of $charge if that’s correct

                                $debit = new TransactionLedger([
                                    'user_id' => $user_id,
                                    'opening_balance' => $opening_balance_sender,
                                    'amount' => $amount,
                                    'fees' => $total_fees,
                                    'actual_amount' => $amount + $total_fees,
                                    'type' => 2,
                                    'trans_id' => $transaction_id,
                                    'payment_mode' => 'wallet2wallet',
                                    'closing_balance' => $closing_balance_sender,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $debit->save();
                                //                                User::where('id', $user_id)->update(['holdAmount' => $sender_wallet_amount]);
                                DB::table('users')->where('id', $user_id)->decrement('holdAmount', ($total_amount + $total_fees));

                                $receiver_wallet_amount = $recieverUser->wallet_balance + $total_amount;
                                $credit = new TransactionLedger([
                                    'user_id' => $is_receiver_exist->id,
                                    'opening_balance' => $recieverUser->wallet_balance,
                                    'amount' => $amount,
                                    'actual_amount' => $total_amount,
                                    'type' => 1,
                                    'trans_id' => $transaction_id,
                                    'payment_mode' => 'wallet2wallet',
                                    'closing_balance' => $receiver_wallet_amount,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $credit->save();

                                DB::table('users')->where('id', $is_receiver_exist->id)->increment('wallet_balance', $total_amount);
                                //                                User::where('id', $is_receiver_exist->id)->update(['wallet_balance' => $receiver_wallet_amount]);

                                DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                                $total_fees_arry[] = $total_fees;
                                ExcelTransaction::where('id', $row->id)->update(['fees' => $total_fees]);
                            }
                            $t_inserted_record += 1;

                            $title = __("debit_title", ['cost' => CURR . " " . $amount]);
                            $message = __("debit_message", ['cost' => CURR . " " . $amount, 'username' => $recieverUser->name]);

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

                            $title = __("credit_title", ['cost' => CURR . " " . $total_amount]);
                            $message = __("credit_message", ['cost' => CURR . " " . $total_amount, 'username' => $senderUser->name]);
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
                        }
                    }
                } else {
                    if (!isset($country) && $excel_transactions[0]->bdastatus != 'BDA' && $excel_transactions[0]->bdastatus != 'SWAPTOSWAP' && $excel_transactions[0]->bdastatus != 'ONAFRIQ') {
                        $msg = 'Country is not supported !';

                        ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
                        $n_inserted_record += 1;
                        continue;
                    } else {

                        try {
                            if ($excel_transactions[0]->bdastatus == 'GIMAC') {
                                if ($transactionLimit->gimacMin > $amount) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->gimacMin]);
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                if ($amount > $transactionLimit->gimacMax) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->gimacMax]);
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                $total_fees = 0;

                                $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();

                                if (isset($feeapply)) {

                                    $feeType = $feeapply->fee_type;
                                    if ($feeType == 1) {
                                        $total_fees = $feeapply->fee_amount;
                                    } else {
                                        $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                                    }
                                } else {
                                    $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();
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

                                $total_amount = $amount + $total_fees;
                                //print_r($total_amount); die;
                                if ($userData->holdAmount >= $total_amount) {
                                    $tomemberData = WalletManager::where('id', $tomember_id)->first();
                                    if (!empty($tomemberData)) {
                                        $tomember = $tomemberData->tomember;
                                    }

                                    $dateString = date('d-m-Y H:i:s');
                                    $format = 'd-m-Y H:i:s';
                                    $dateTime = DateTime::createFromFormat($format, $dateString);
                                    $timestamp = $dateTime->getTimestamp();
                                    $randomString = $this->generateRandomString(15);
                                    $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                                    if ($last_record != "") {
                                        $next_issuertrxref = $last_record + 1;
                                    } else {
                                        $next_issuertrxref = '140071';
                                    }

                                    $data = [
                                        'createtime' => $timestamp,
                                        'intent' => 'mobile_transfer',
                                        'walletsource' => $senderUser->phone,
                                        'walletdestination' => $receviver_mobile,
                                        'issuertrxref' => $next_issuertrxref,
                                        'amount' => $amount,
                                        'currency' => '950',
                                        'description' => 'money transfer',
                                        'tomember' => $tomember,
                                    ];


                                    $response = $client->request('POST', env('GIMAC_PAYMENT_URL'), [
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
                                        $rejectedStatus = '';
                                        $status = $state == 'ACCEPTED' ? 1 : 2;
                                        if ($state == 'REJECTED') {
                                            //                                        $updatedWalletBal = $senderUser->wallet_balance + $total_amount;
                                            //                                        $updatedHoldBal = $senderUser->holdAmount - $total_amount;

                                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $total_amount);
                                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $total_amount);

                                            //                                        $wallet = DB::table('users')->where('id', $senderUser->id)->update(['wallet_balance' => $updatedWalletBal, 'holdAmount' => $updatedHoldBal]);

                                            $rejectedStatus = $jsonResponse2->rejectMessage;
                                            ExcelTransaction::where('id', $row->id)->update(['remarks' => $rejectedStatus]);
                                        }
                                        $vouchercode = $jsonResponse2->vouchercode;
                                        $trans_id = time();
                                        $refrence_id = time();
                                        $trans = new Transaction([
                                            'user_id' => $user_id,
                                            'receiver_id' => 0,
                                            'receiver_mobile' => $receviver_mobile,
                                            'amount' => $amount,
                                            'amount_value' => $amount,
                                            'transaction_amount' => $total_fees,
                                            'total_amount' => $total_amount,
                                            'trans_type' => 2,
                                            'excel_trans_id' => $row->id,
                                            'payment_mode' => 'wallet2wallet',
                                            'status' => $status,
                                            'refrence_id' => $issuertrxref,
                                            'billing_description' => 'Fund Transfer-' . $refrence_id,
                                            'tomember' => $tomember,
                                            'acquirertrxref' => $acquirertrxref,
                                            'issuertrxref' => $issuertrxref,
                                            'vouchercode' => $vouchercode,
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s'),
                                        ]);
                                        $trans->save();

                                        if ($state == 'ACCEPTED' || $state == 'PENDING') {

                                            $userRec = User::where('id', $user_id)->first();
                                            $sender_wallet_amount = $userRec->holdAmount - $total_amount;


                                            $opening_balance_sender1 = $userRec->wallet_balance + $userRec->holdAmount;
                                            $closing_balance_sender1 = $opening_balance_sender1 - ($total_amount); 

                                            $debit = new TransactionLedger([
                                                'user_id' => $user_id,
                                                //'opening_balance' => ($userRec->wallet_balance + $total_amount),
                                                'opening_balance' => $opening_balance_sender1,
                                                'amount' => $amount,
                                                'fees' => $total_fees,
                                                'actual_amount' => $total_amount,
                                                'type' => 2,
                                                'trans_id' => $trans->id,
                                                'payment_mode' => 'wallet2wallet',
                                                //'closing_balance' => $userRec->wallet_balance,
                                                'closing_balance' => $closing_balance_sender1,
                                                'created_at' => date('Y-m-d H:i:s'),
                                                'updated_at' => date('Y-m-d H:i:s'),
                                            ]);
                                            $debit->save();

                                            User::where('id', $user_id)->update(['holdAmount' => $sender_wallet_amount]);
                                            DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                                        }
                                        $t_inserted_record += 1;
                                    }
                                } else {
                                    Session::put('error_message', __('Hold balance not remaining your wallet'));
                                    return Redirect::to('/pending-approvals');
                                }
                            } elseif ($excel_transactions[0]->bdastatus == 'BDA') {

                                if ($transactionLimit->bdaMin > $amount) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->bdaMin]);
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                if ($amount > $transactionLimit->bdaMax) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->bdaMax]);
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                $total_fees = 0;

                                $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', 'Money Transfer Via BDA')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();

                                if (isset($feeapply)) {

                                    $feeType = $feeapply->fee_type;
                                    if ($feeType == 1) {
                                        $total_fees = $feeapply->fee_amount;
                                    } else {
                                        $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                                    }
                                } else {
                                    $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via BDA')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();
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

                                $total_amount = $amount + $total_fees;

                                if ($userData->holdAmount >= $total_amount) {
                                    $details = RemittanceData::where('excel_id', $excel_transactions[0]->id)->first();

                                    //                                $refNoLo = $this->generateUniqueReference();

                                    $url = env('BDA_PAYMENT_URL');
                                    $data = [
                                        'referenceLot' => $details->referenceLot,
                                        'nombreVirement' => 1,
                                        'montantTotal' => $details->amount,
                                        'produit' => $details->product,
                                        'virements' => [
                                            [
                                                'ibanCredit' => $details->iban,
                                                'intituleCompte' => $details->titleAccount,
                                                'montant' => $details->amount,
                                                'referencePartenaire' => $details->partnerreference,
                                                'motif' => $details->reason,
                                                'typeVirement' => 'RTGS'
                                            ]
                                        ]
                                    ];

                                    $response = $client->request('POST', $url, [
                                        'headers' => [
                                            'Content-Type' => 'application/json'
                                        ],
                                        'json' => $data,
                                    ]);

                                    $body = $response->getBody()->getContents();

                                    $jsonResponse2 = json_decode($body);

                                    $statusCode = $response->getStatusCode();

                                    if ($statusCode == 200) {

                                        $existingTransaction = Transaction::where('excel_trans_id', $row->id)
                                            ->where('user_id', $user_id)
                                            ->where('amount', $amount)
                                            ->where('transaction_amount', $total_fees)
                                            ->first();

                                        $statut = $jsonResponse2->statut;
                                        $rejectedStatus = '';

                                        //$status = $statut == 'EN_ATTENTE' ? 1 : 2; 
                                        if ($statut == 'REJETE') {
                                            $updatedWalletBal = $senderUser->wallet_balance + $total_amount;
                                            $updatedHoldBal = $senderUser->holdAmount - $total_amount;
                                            $wallet = DB::table('users')->where('id', $senderUser->id)->update(['wallet_balance' => $updatedWalletBal, 'holdAmount' => $updatedHoldBal]);

                                            $uploadE = UploadedExcel::where('id', $id)->update(['status' => 6, 'approved_by' => Auth::user()->id]);
                                            unset($uploadE);
                                            $excelIT = ExcelTransaction::where('excel_id', $id)->update(['remarks' => 'Rejected', 'approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);
                                            unset($excelIT);
                                            RemittanceData::where('excel_id', $details->id)->update(['status' => $statut]);
                                            Session::put('error_message', __('Transaction failed'));
                                            return back();
                                        }

                                        $trans_id = time();
                                        $refrence_id = time();

                                        $userRec = User::where('id', $user_id)->first();
                                        $sender_wallet_amount = $userRec->holdAmount - $total_amount;
                                        if (!$existingTransaction) {
                                            $trans = new Transaction([
                                                'user_id' => $user_id,
                                                'receiver_id' => 0,
                                                'receiver_mobile' => '',
                                                'amount' => $amount,
                                                'amount_value' => $amount,
                                                'transaction_amount' => $total_fees,
                                                'total_amount' => $total_amount,
                                                'trans_type' => 2,
                                                'excel_trans_id' => $row->id,
                                                'payment_mode' => 'wallet2wallet',
                                                'status' => 1,
                                                'bda_status' => 2,
                                                'refrence_id' => '',
                                                'billing_description' => 'Fund Transfer-' . $refrence_id,
                                                'tomember' => '',
                                                'acquirertrxref' => '',
                                                'issuertrxref' => '',
                                                'vouchercode' => '',
                                                'created_at' => date('Y-m-d H:i:s'),
                                                'updated_at' => date('Y-m-d H:i:s'),
                                            ]);

                                            $trans->save();

                                            $opening_balance_sender2 = $userRec->wallet_balance + $userRec->holdAmount;
                                            $closing_balance_sender2 = $opening_balance_sender2 - $total_amount; 

                                            $debit = new TransactionLedger([
                                                'user_id' => $user_id,
                                                //'opening_balance' => ($userRec->wallet_balance + $total_amount),
                                                'opening_balance' => $opening_balance_sender2,
                                                'amount' => $amount,
                                                'fees' => $total_fees,
                                                'actual_amount' => $total_amount,
                                                'type' => 2,
                                                'trans_id' => $trans->id,
                                                'payment_mode' => 'wallet2wallet',
                                                //'closing_balance' => $userRec->wallet_balance,
                                                'closing_balance' => $closing_balance_sender2,
                                                'created_at' => date('Y-m-d H:i:s'),
                                                'updated_at' => date('Y-m-d H:i:s'),
                                            ]);
                                            $debit->save();
                                        }
                                        RemittanceData::where('excel_id', $excel_transactions[0]->id)->update(['status' => $statut]);

                                        if ($statut == 'EN_ATTENTE') {
                                            User::where('id', $user_id)->update(['holdAmount' => $sender_wallet_amount]);
                                            DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                                        }


                                        $t_inserted_record += 1;
                                    }
                                } else {
                                    Session::put('error_message', __('Hold balance not remaining your wallet'));
                                    return Redirect::to('/pending-approvals');
                                }
                            } else if ($excel_transactions[0]->bdastatus == 'ONAFRIQ') {
                                if ($transactionLimit->onafriqa_min > $amount) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->onafriqa_min]);
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                if ($amount > $transactionLimit->onafriqa_max) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->onafriqa_max]);
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                $total_fees = 0;

                                $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', 'Money Transfer Via ONAFRIQ')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();

                                if (isset($feeapply)) {

                                    $feeType = $feeapply->fee_type;
                                    if ($feeType == 1) {
                                        $total_fees = $feeapply->fee_amount;
                                    } else {
                                        $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                                    }
                                } else {
                                    $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via ONAFRIQ')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();
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

                                $total_tax = "0";
                                if ($userData->holdAmount >= $total_amount) {


                                    $postData = '
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
                                </soap:Envelope>';

                                    $getResponse = $this->sendCurlRequest($postData, 'urn:account_request');
                                    //print_r($getResponse); die;
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
                                        $getOnfi = OnafriqaData::where('excelTransId', $excel_transactions[0]->id)->where('status', 'pending')->first();
                                        $postDataRemit = '
                                    <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                    <soap:Body>
                                    <ns:mm_remit_log xmlns:ns="http://ws.mfsafrica.com">
                                    <ns:login>
                                    <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code> 
                                    <ns:password>' . CORPORATEPASS . '</ns:password> 
                                    </ns:login>
                                    <ns:receive_amount>
                                    <ns:amount>' . $getOnfi->amount . '</ns:amount> 
                                    <ns:currency_code>XAF</ns:currency_code> 
                                    </ns:receive_amount>
                                    <ns:sender>
                                    <ns:msisdn></ns:msisdn> 
                                    <ns:name>' . $getOnfi->senderName . '</ns:name> 
                                    <ns:surname></ns:surname> 
                                    <ns:document>
                                    <ns:id_type></ns:id_type>
                                    <ns:id_number>' . $getOnfi->senderIdNumber . '</ns:id_number>
                                    <ns:id_country>' . $getOnfi->senderCountry . '</ns:id_country>
                                    <ns:id_expiry>' . $getOnfi->senderIdExpiry . '</ns:id_expiry>
                                    </ns:document>
                                    </ns:sender>
                                    <ns:recipient>
                                    <ns:msisdn>' . $getOnfi->recipientMsisdn . '</ns:msisdn> 
                                    <ns:to_country>' . $getOnfi->recipientCountry . '</ns:to_country> 
                                    <ns:surname>' . $getOnfi->recipientSurname . '</ns:surname>
                                    <ns:name>' . $getOnfi->recipientName . '</ns:name>
                                    </ns:recipient>
                                    <ns:third_party_trans_id>test1</ns:third_party_trans_id> 
                                    </ns:mm_remit_log>
                                    </soap:Body>
                                    </soap:Envelope>';
                                        $getResponseRemit = $this->sendCurlRequest($postDataRemit, 'urn:mm_remit_log');

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


                                        $status1 = $xml1->xpath('//' . $axNamespace1 . ':status')[0];

                                        $mfs_trans_id = (string) $xml1->xpath('//' . $axNamespace1 . ':mfs_trans_id')[0];
                                        $partner_code = (string) $xml1->xpath('//' . $axNamespace1 . ':partner_code')[0];


                                        $statusCode1 = (string) $status1->xpath('' . $axNamespace1 . ':code/' . $axNamespace1 . ':status_code')[0];
                                        $statusMessage = (string) $status1->xpath('ax21:message')[0];

                                        $receiveAmount = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':amount')[0];
                                        $currencyCode = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':currency_code')[0];


                                        if ($statusCode1 == "MR104" && $statusMessage == "Log Success" && $mfs_trans_id != "") {
                                            $postDataTrans = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
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

                                                $trans_id = time();
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
                                                    'excel_trans_id' => $row->id,
                                                    'payment_mode' => 'wallet2wallet',
                                                    'status' => 1,
                                                    'refrence_id' => '',
                                                    'billing_description' => 'Fund Transfer-' . $refrence_id,
                                                    'tomember' => '',
                                                    'acquirertrxref' => '',
                                                    'issuertrxref' => '',
                                                    'vouchercode' => '',
                                                    'created_at' => date('Y-m-d H:i:s'),
                                                    'updated_at' => date('Y-m-d H:i:s'),
                                                ]);
                                                $trans->save();

                                                $userRec = User::where('id', $user_id)->first();
                                                $sender_wallet_amount = $userRec->holdAmount - $total_amount;

                                                $debit = new TransactionLedger([
                                                    'user_id' => $user_id,
                                                    'opening_balance' => ($userRec->wallet_balance + $total_amount),
                                                    'amount' => $amount,
                                                    'fees' => $total_fees,
                                                    'actual_amount' => $total_amount,
                                                    'type' => 2,
                                                    'trans_id' => $trans->id,
                                                    'payment_mode' => 'wallet2wallet',
                                                    'closing_balance' => $userRec->wallet_balance,
                                                    'created_at' => date('Y-m-d H:i:s'),
                                                    'updated_at' => date('Y-m-d H:i:s'),
                                                ]);
                                                $debit->save();
                                                User::where('id', $user_id)->update(['holdAmount' => $sender_wallet_amount]);
                                                DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);

                                                OnafriqaData::where('excelTransId', $excel_transactions[0]->id)->update(['transactionId' => $mfs_trans_id, 'status' => 'success']);
                                            }
                                            //$data = [
                                            //'paymentStatus' => $statusCode2,
                                            //'e_trans_id' => $e_trans_id2,
                                            //'message' => $message2,
                                        //];
                                        //print_r($data); die;
                                        } else {
                                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $total_amount);
                                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $total_amount);
                                            UploadedExcel::where('id', $id)->update(['status' => 6, 'approved_by' => Auth::user()->id]);
                                            ExcelTransaction::where('excel_id', $id)->update(['remarks' => 'Rejected', 'approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);
                                            Session::put('error_message', __('Transaction failed'));
                                            return back();
                                        }
                                    } else {
                                        DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $total_amount);
                                        DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $total_amount);
                                        UploadedExcel::where('id', $id)->update(['status' => 6, 'approved_by' => Auth::user()->id]);
                                        ExcelTransaction::where('excel_id', $id)->update(['remarks' => 'Rejected', 'approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);
                                        Session::put('error_message', __('Transaction failed'));
                                        return back();
                                    }

                                    $t_inserted_record += 1;
                                } else {
                                    Session::put('error_message', __('Hold balance not remaining your wallet'));
                                    return Redirect::to('/pending-approvals');
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
                                    $next_issuertrxref = '140071';
                                }

                                $total_fees = 0;

                                $feeapply = FeeApply::where('userId', $user_id)->where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();

                                if (isset($feeapply)) {

                                    $feeType = $feeapply->fee_type;
                                    if ($feeType == 1) {
                                        $total_fees = $feeapply->fee_amount;
                                    } else {
                                        $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                                    }
                                } else {
                                    $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $amount)->where('max_amount', '>=', $amount)->first();
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

                                $total_amount = $amount + $total_fees;

                                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                                    $errorDescription = $jsonResponse['error_description'];
                                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);

                                    // $updatedWalletBal = $senderUser->wallet_balance + $total_amount;
                                    // $updatedHoldBal = $senderUser->holdAmount - $total_amount;
                                    // $wallet = DB::table('users')->where('id', $senderUser->id)->update(['wallet_balance' => $updatedWalletBal, 'holdAmount' => $updatedHoldBal]);

                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $total_amount);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $total_amount);

                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => $errorDescription]);
                                    $n_inserted_record += 1;
                                } else {
                                    // $updatedWalletBal = $senderUser->wallet_balance + $total_amount;
                                    // $updatedHoldBal = $senderUser->holdAmount - $total_amount;
                                    // $wallet = DB::table('users')->where('id', $senderUser->id)->update(['wallet_balance' => $updatedWalletBal, 'holdAmount' => $updatedHoldBal]);

                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $total_amount);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $total_amount);

                                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => __('Unable to extract error_description')]);
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('Unable to extract error_description')]);
                                    $n_inserted_record += 1;
                                }
                            } else {
                                if ($excel_transactions[0]->bdastatus == 'BDA') {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('BDA server error')]);
                                } elseif ($excel_transactions[0]->bdastatus == 'ONAFRIQ') {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('ONAFRIQ server error')]);
                                } else {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('GIMAC server error')]);
                                }

                                $n_inserted_record += 1;
                            }
                        }
                    }
                }
                // }
            }

            if ($n_inserted_record > 0) {
                $title = 'Failure';
                $message = __('Transfer failed for') . $n_inserted_record . __('records.');
                $device_type = $userData->device_type;
                $device_token = $userData->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $user_id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                Session::put('error_message', __('We got error in') . $n_inserted_record . __('records while transfer.'));
            }

            UploadedExcel::where('id', $id)->update(['status' => 5, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);

            if ($t_inserted_record > 0) {
                $title = 'Transfered';
                $message = 'Initiated successful transfer for ' . $t_inserted_record . ' records';
                $device_type = $userData->device_type;
                $device_token = $userData->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $user_id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                Session::put(__('success_message'), $t_inserted_record . __(' records inserted successfully!'));
            }

            if (!empty($total_fees_arry)) {
                $all_fees_amount = array_sum($total_fees_arry);
                UploadedExcel::where('id', $id)->update(['total_fees' => $all_fees_amount]);
            }
            return back();
        } elseif ($user_type == "Approver") {
            UploadedExcel::where('id', $id)->update(['status' => 3, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id, 'approver_id' => Auth::user()->id, 'approved_date' => now()]);
            Session::put('success_message', __('Record have been successfully approved and are visible in the merchant account.'));
            return back();
        } else {
            $missingFieldsErrors = [];

            foreach ($excel_transactions as $index => $data) {

                $missingFields = [];
                if (empty($data['first_name'])) $missingFields[] = 'First name';
                if (empty($data['name'])) $missingFields[] = 'Name';
                if (empty($data['country_id'])) $missingFields[] = 'Country';
                if (empty($data['wallet_manager_id'])) $missingFields[] = 'Wallet Manager';
                if (empty($data['tel_number'])) $missingFields[] = 'Tel number';
                if (empty($data['amount'])) $missingFields[] = 'Amount';


                if (!empty($missingFields)) {
                    $missingFieldsErrors[] = 'Row ' . ($index + 1) . ': ' . implode(', ', $missingFields) . ' are missing.';
                }
            }

            if (!empty($missingFieldsErrors)) {
                $errorMessage = __('Errors in Excel file: ') . implode("\n", $missingFieldsErrors);
                Session::put('error_message', $errorMessage);
                return Redirect::to('/pending-approvals');
            }

            UploadedExcel::where('id', $id)->update(['status' => 1, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id]);

            Session::put('success_message', __('Records have been successfully approved and are now visible in the approver account.'));
            return back();
        //UploadedExcel::where('id', $id)->update(['status' => 1, 'approved_by' => Auth::user()->id]);
        //ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id]);
        //Session::put('success_message', __('Record have been successfully approved and are visible in the approver account.'));
        //return back();
        }
    } */

    public function approveExcel($slug, Request $request)
    {
        $user_id = Auth::user()->id;
        $user_type = Auth::user()->user_type;
        $decode_string = base64_decode($slug);
        $id = $this->decryptContent($decode_string);
        $userData = User::where('id', $user_id)->first();
        $user_role = Auth::user()->user_type;
        $sum = 0;
        $excel_transactions = ExcelTransaction::where('excel_id', $id)->get();

        foreach ($excel_transactions as $val) {
            $sum += $val->amount; // Add the amount to the sum
        }

        if ($user_type == "Merchant") {
            $excel_transaction = ExcelTransaction::where('excel_id', $id)->get();

            //to create the access token for gimac
            $certificate = public_path("MTN Cameroon Issuing CA1.crt");
            $client = new Client([
                'verify' => $certificate,
            ]);

            $t_inserted_record = 0;
            $n_inserted_record = 0;

            $total_fees_arry = [];

            $senderUser = User::where('id', $user_id)->first();
            $userType = $this->getUserType($senderUser->user_type);
            $walletlimit = Walletlimit::where('category_for', $userType)->first();
            $transactionLimit = TransactionLimit::where('type', $userType)->first();
            $total_amount_to_transfer = 0;

            //               echo '<pre>';print_r($excel_transaction);exit;
            $total_count_t = $total_count_d = count($excel_transaction);
            $sendUserCurrentBal = $senderUser->wallet_balance;
            foreach ($excel_transaction as $key => $row) {
                $getTransId = Transaction::where('excel_trans_id', $row->id)->first();
                $lang = Session::get('locale');

                if (!isset($amount)) {
                    $amount = 0;
                }
                $tomember = 0;
                $country = $row->country_id;
                $tomember_id = $row->wallet_manager_id;
                $receviver_mobile = $row->tel_number;
                $amount = $row->amount;
                $feeWithAmt = $row->amount + $row->fees;

                $msg = '';
                $country = Country::where('id', $country)->first();
                //to check current month limit
                $currentMonthSum = Transaction::where('user_id', $user_id)->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereIn('status', [1, 2])
                    ->sum('amount');
                if (($currentMonthSum + $amount) > $walletlimit->month_limit) {
                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.Your monthly transfer limit has been reached.')]);
                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                    $sendUserCurrentBal += $feeWithAmt;
                    $n_inserted_record += 1;
                    continue;
                }

                //to check current week limit
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek = Carbon::now()->endOfWeek();
                $currentWeekSum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('amount');
                if (($currentWeekSum + $amount) > $walletlimit->week_limit) {
                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.Your weekly transfer limit has been reached.')]);
                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                    $sendUserCurrentBal += $feeWithAmt;
                    $n_inserted_record += 1;
                    continue;
                }

                // Get the sum of amounts for transactions within the current day
                $startOfDay = Carbon::now()->startOfDay();
                $endOfDay = Carbon::now()->endOfDay();
                $currentDaySum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->sum('amount');
                if (($currentDaySum + $amount) > $walletlimit->daily_limit) {
                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.Your daily transfer limit has been reached.')]);
                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                    $sendUserCurrentBal += $feeWithAmt;
                    $n_inserted_record += 1;
                    continue;
                }
                if ($senderUser->kyc_status != "completed") {
                    $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
                    $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
                    if ($senderUser->kyc_status == "pending") {
                        if ($unverifiedKycMin > $amount) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin]);
                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                            $sendUserCurrentBal += $feeWithAmt;
                            $n_inserted_record += 1;
                            continue;
                        }

                        if ($unverifiedKycMax < $amount) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . __('message.because your KYC is still pending.')]);
                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                            $sendUserCurrentBal += $feeWithAmt;
                            $n_inserted_record += 1;
                            continue;
                        }
                    } else {

                        if ($unverifiedKycMin > $amount) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin]);
                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                            $sendUserCurrentBal += $feeWithAmt;
                            $n_inserted_record += 1;
                            continue;
                        }

                        if ($unverifiedKycMax < $amount) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . __('message.because your KYC is not verified.Please verify your KYC first')]);
                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                            $sendUserCurrentBal += $feeWithAmt;
                            $n_inserted_record += 1;
                            continue;
                        }
                    }
                }
                $is_receiver_exist = User::where('phone', trim($receviver_mobile))->whereNotIn('user_type', ['Approver', 'Submitter'])->first();


                if (isset($is_receiver_exist) && $row->bdastatus == "SWAPTOSWAP") {
                    if ($user_id == $is_receiver_exist->id) {
                        $msg = __('You cannot send funds to yourself');
                        ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
                        $total_fees = $this->calculateFees($amount, $user_id, 'Send Money');
                        DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                        DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                        Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                        $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                        $sendUserCurrentBal += $feeWithAmt;
                        $n_inserted_record += 1;
                        continue;
                    } else {
                        if ($transactionLimit->minSendMoney > $amount) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->minSendMoney]);
                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                            $sendUserCurrentBal += $feeWithAmt;
                            $n_inserted_record += 1;
                            continue;
                        }

                        if ($amount > $transactionLimit->maxSendMoney) {
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->maxSendMoney]);
                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                            $sendUserCurrentBal += $feeWithAmt;
                            $n_inserted_record += 1;
                            continue;
                        }

                        $total_fees = $this->calculateFees($amount, $user_id, 'Send Money');

                        $total_amount = $amount;
                        $tOtAmt = $total_amount + $total_fees;

                        if (trim($tOtAmt) > trim($senderUser->holdAmount)) {
                            Session::put('error_message', __('message.Hold balance not remaining your wallet'));
                            return Redirect::to('/pending-approvals');
                        } else {
                            $trans_id = time() . '-' . $user_id . '-' . $is_receiver_exist->id;
                            $refrence_id = time() . '-' . $is_receiver_exist->id;


                            $recieverUser = $userInfo = User::where('id', $is_receiver_exist->id)->first();

                            /* $trans = new Transaction([
                                'user_id' => $user_id,
                                'receiver_id' => $is_receiver_exist->id,
                                'amount' => $amount,
                                'amount_value' => $total_amount,
                                'transaction_amount' => $total_fees,
                                'total_amount' => $amount + $total_fees,
                                'trans_type' => 2,
                                'excel_trans_id' => $row->id,
                                'payment_mode' => 'wallet2wallet',
                                'status' => 1,
                                'transactionType' => 'SWAPTOSWAP',
                                'refrence_id' => $trans_id,
                                'billing_description' => 'Fund Transfer-' . $refrence_id,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);

                            $trans->save();
                            $transaction_id = $trans->id; */
                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 1]);
                            // TransactionLedger::where('excelTransId', $row->id)->update(['trans_id' => $transaction_id]);

                            DB::table('users')->where('id', $user_id)->decrement('holdAmount', ($total_amount + $total_fees));

                            $receiver_wallet_amount = $recieverUser->wallet_balance + $total_amount;
                            $credit = new TransactionLedger([
                                'user_id' => $is_receiver_exist->id,
                                'opening_balance' => $recieverUser->wallet_balance,
                                'amount' => $amount,
                                'actual_amount' => $total_amount,
                                'type' => 1,
                                'trans_id' => $getTransId->id,
                                'payment_mode' => 'wallet2wallet',
                                'excelTransId' => $row->id,
                                'closing_balance' => $receiver_wallet_amount,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $credit->save();

                            DB::table('users')->where('id', $is_receiver_exist->id)->increment('wallet_balance', $total_amount);
                            //                                User::where('id', $is_receiver_exist->id)->update(['wallet_balance' => $receiver_wallet_amount]);

                            DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                            $total_fees_arry[] = $total_fees;
                            ExcelTransaction::where('id', $row->id)->update(['fees' => $total_fees]);

                            $t_inserted_record += 1;

                            // $title = __("debit_title", ['cost' => CURR . " " . $amount]);
                            // $message = __("debit_message", ['cost' => CURR . " " . $amount, 'username' => $recieverUser->name]);

                            $title_fr = CURR . " " . $amount . " débités du portefeuille";
                            $message_fr = CURR . " " . $amount . " débités du portefeuille pour le transfert de fonds à l'utilisateur " . $recieverUser->name;

                            $title = CURR . " " . $amount . " debited from wallet.";
                            $message = CURR . " " . $amount . " debited from wallet for fund transfer to user " . $recieverUser->name;

                            $device_type = $senderUser->device_type;
                            $device_token = $senderUser->device_token;

                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $senderUser->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'notif_title_fr' => $title_fr,
                                'notif_body_fr' => $message_fr,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();

                            $title_fr = CURR . " " . $total_amount . ' crédités sur le portefeuille';
                            $message_fr = CURR . " " . $total_amount . " crédités sur le portefeuille pour le transfert de fonds de l'utilisateur " . $senderUser->name;

                            $title = CURR . " " . $total_amount . ' credited to the wallet';
                            $message = CURR . " " . $total_amount . ' credited to the wallet for fund transfer from user ' . $senderUser->name;


                            $device_type = $recieverUser->device_type;
                            $device_token = $recieverUser->device_token;

                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $recieverUser->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'notif_title_fr' => $title_fr,
                                'notif_body_fr' => $message_fr,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();
                        }
                    }
                } else {
                    if (!isset($country) && $row->bdastatus != 'BDA' && $row->bdastatus != 'SWAPTOSWAP' && $row->bdastatus != 'ONAFRIQ') {
                        $msg = "{{__('message.Country is not supported !')}}";

                        ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
                        $n_inserted_record += 1;
                        continue;
                    } else {

                        try {
                            if ($row->bdastatus == 'GIMAC') {
                                if ($transactionLimit->gimacMin > $amount) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->gimacMin]);
                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                if ($amount > $transactionLimit->gimacMax) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->gimacMax]);
                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                $total_fees = $this->calculateFees($amount, $user_id, 'Money Transfer Via GIMAC');


                                $total_amount = $amount + $total_fees;

                                if (trim($userData->holdAmount) >= trim($total_amount)) {
                                    $tomemberData = WalletManager::where('id', $tomember_id)->first();
                                    if (!empty($tomemberData)) {
                                        $tomember = $tomemberData->tomember;
                                    }

                                    $dateString = date('d-m-Y H:i:s');
                                    $format = 'd-m-Y H:i:s';
                                    $dateTime = DateTime::createFromFormat($format, $dateString);
                                    $timestamp = $dateTime->getTimestamp();
                                    $randomString = $this->generateRandomString(15);
                                    $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                                    if ($last_record != "") {
                                        $next_issuertrxref = $last_record + 1;
                                    } else {
                                        $next_issuertrxref = '140071';
                                    }

                                    $data = [
                                        'createtime' => $timestamp,
                                        'intent' => 'mobile_transfer',
                                        'walletsource' => $senderUser->phone,
                                        'walletdestination' => $receviver_mobile,
                                        'issuertrxref' => $next_issuertrxref,
                                        'amount' => $amount,
                                        'currency' => '950',
                                        'description' => 'money transfer',
                                        'tomember' => $tomember,
                                    ];


                                    $accessToken = '';
                                    try {
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
                                        $response = $client->request('POST', env('GIMAC_TOKEN_URL'), $options);
                                        $body = $response->getBody()->getContents();
                                        $jsonResponse = json_decode($body);
                                        $accessToken = $jsonResponse->access_token;
                                    } catch (\Exception $e) {
                                    }

                                    $response = $client->request('POST', env('GIMAC_PAYMENT_URL'), [
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
                                        $tomember = $jsonResponse2->tomember ?? "";
                                        $acquirertrxref = $jsonResponse2->acquirertrxref ?? "";
                                        $issuertrxref = $jsonResponse2->issuertrxref ?? "";
                                        $vouchercode = $jsonResponse2->vouchercode ?? "";
                                        $state = $jsonResponse2->state;
                                        $rejectedStatus = '';
                                        $status = $state == 'ACCEPTED' ? 1 : 2;
                                        if ($state == 'REJECTED') {

                                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                            $rejectedStatus = $jsonResponse2->rejectMessage;
                                            ExcelTransaction::where('id', $row->id)->update(['remarks' => $rejectedStatus]);
                                            //Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3, 'refrence_id' => $issuertrxref, 'tomember' => $tomember, 'acquirertrxref' => $acquirertrxref, 'issuertrxref' => $issuertrxref, 'vouchercode' => $vouchercode]);
                                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                            $sendUserCurrentBal += $feeWithAmt;
                                            Log::channel('GIMAC')->info("Rejected $jsonResponse2");
                                        }
                                        $trans_id = time();
                                        $refrence_id = time();
                                        /* $trans = new Transaction([
                                            'user_id' => $user_id,
                                            'receiver_id' => 0,
                                            'receiver_mobile' => $receviver_mobile,
                                            'amount' => $amount,
                                            'amount_value' => $amount,
                                            'transaction_amount' => $total_fees,
                                            'total_amount' => $total_amount,
                                            'trans_type' => 2,
                                            'excel_trans_id' => $row->id,
                                            'payment_mode' => 'wallet2wallet',
                                            'status' => $status,
                                            'refrence_id' => $issuertrxref,
                                            'billing_description' => 'Fund Transfer-' . $refrence_id,
                                            'tomember' => $tomember,
                                            'acquirertrxref' => $acquirertrxref,
                                            'issuertrxref' => $issuertrxref,
                                            'vouchercode' => $vouchercode,
                                            'transactionType' => 'SWAPTOGIMAC',
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s'),
                                        ]);
                                        $trans->save(); */
                                        Transaction::where('excel_trans_id', $row->id)->update(['status' => $status, 'refrence_id' => $issuertrxref, 'tomember' => $tomember, 'acquirertrxref' => $acquirertrxref, 'issuertrxref' => $issuertrxref, 'vouchercode' => $vouchercode]);
                                        if ($state == 'ACCEPTED' || $state == 'PENDING') {

                                            $userRec = User::where('id', $user_id)->first();
                                            $sender_wallet_amount = $userRec->holdAmount - $total_amount;
                                            TransactionLedger::where('excelTransId', $row->id)->update(['trans_id' => $getTransId->id]);

                                            User::where('id', $user_id)->update(['holdAmount' => $sender_wallet_amount]);
                                            DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                                        }
                                        Log::channel('GIMAC')->info("Status $state.'========='.$jsonResponse2");
                                        $t_inserted_record += 1;
                                    }
                                } else {
                                    Session::put('error_message', __('message.Hold balance not remaining your wallet'));
                                    return Redirect::to('/pending-approvals');
                                }
                            } elseif ($row->bdastatus == 'BDA') {

                                if ($transactionLimit->bdaMin > $amount) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->bdaMin]);

                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                if ($amount > $transactionLimit->bdaMax) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->bdaMax]);

                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                $total_fees = $this->calculateFees($amount, $user_id, 'Money Transfer Via BDA');

                                $total_amount = $amount + $total_fees;

                                if (trim($userData->holdAmount) >= trim($total_amount)) {
                                    $details = RemittanceData::where('excel_id', $row->id)->first();

                                    //  $refNoLo = $this->generateUniqueReference();
                                    sleep(1);
                                    $url = env('BDA_PAYMENT_URL');
                                    $data = [
                                        'referenceLot' => $details->referenceLot,
                                        'nombreVirement' => 1,
                                        'montantTotal' => $details->amount,
                                        'produit' => $details->product,
                                        'virements' => [
                                            [
                                                'ibanCredit' => $details->iban,
                                                'intituleCompte' => $details->titleAccount,
                                                'montant' => $details->amount,
                                                'referencePartenaire' => $details->partnerreference,
                                                'motif' => $details->reason,
                                                'typeVirement' => 'RTGS'
                                            ]
                                        ]
                                    ];

                                    $response = $client->request('POST', $url, [
                                        'headers' => [
                                            'Content-Type' => 'application/json'
                                        ],
                                        'json' => $data,
                                    ]);


                                    $body = $response->getBody()->getContents();

                                    $jsonResponse2 = json_decode($body);

                                    $statusCode = $response->getStatusCode();

                                    if ($statusCode == 200) {

                                        $existingTransaction = Transaction::where('excel_trans_id', $row->id)
                                            ->where('user_id', $user_id)
                                            ->where('amount', $amount)
                                            ->where('transaction_amount', $total_fees)
                                            ->first();

                                        $statut = $jsonResponse2->statut;

                                        if ($statut === 'REJETE') {
                                            User::where('id', $user_id)->increment('wallet_balance', $feeWithAmt);
                                            User::where('id', $user_id)->decrement('holdAmount', $feeWithAmt);
                                            RemittanceData::where('excel_id', $row->id)->update(['status' => $statut]);
                                            ExcelTransaction::where('id', $row->id)->update(['remarks' => 'Rejected', 'approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);
                                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                            $sendUserCurrentBal += $feeWithAmt;
                                            $n_inserted_record += 1;
                                        }

                                        if ($statut == 'EN_ATTENTE' || $statut == 'EN_ATTENTE_REGLEMENT') {

                                            Transaction::where('excel_trans_id', $row->id)->update(['bda_status' => 2]);
                                            RemittanceData::where('excel_id', $row->id)->update(['status' => $statut]);
                                            /* User::where('id', $user_id)->decrement('holdAmount', $total_amount);
                                            DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees); */
                                            $t_inserted_record += 1;
                                        }
                                    }
                                } else {
                                    Session::put('error_message', __('message.Hold balance not remaining your wallet'));
                                    return Redirect::to('/pending-approvals');
                                }
                            } else if ($row->bdastatus == 'ONAFRIQ') {
                                if ($transactionLimit->onafriqa_min > $amount) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->onafriqa_min]);
                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;
                                    $n_inserted_record += 1;
                                    continue;
                                }

                                if ($amount > $transactionLimit->onafriqa_max) {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->onafriqa_max]);
                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;
                                    $n_inserted_record += 1;
                                    continue;
                                }


                                $total_fees = $this->calculateFees($amount, $user_id, 'Money Transfer Via ONAFRIQ');

                                $total_amount = $amount + $total_fees;
                                $getOnfi = OnafriqaData::where('excelTransId', $row->id)->where('status', 'pending')->first();
                                $total_tax = "0";
                                if (trim($userData->holdAmount) >= trim($total_amount)) {
                                    $postData = '
                                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                <soap:Body>
                                <ns:account_request xmlns:ns="http://ws.mfsafrica.com">
                                <ns:login>
                                <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                                <ns:password>' . CORPORATEPASS . '</ns:password>
                                </ns:login>
                                <ns:to_country>' . $getOnfi->recipientCountry . '</ns:to_country>
                                <ns:msisdn>' . $getOnfi->recipientMsisdn . '</ns:msisdn>
                                </ns:account_request>
                                </soap:Body>
                                </soap:Envelope>';



                                    $getResponse = $this->sendCurlRequest($postData, 'urn:account_request');

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
                                    Log::channel('ONAFRIQ')->info($postData);
                                    Log::channel('ONAFRIQ')->info($getResponse);
                                    if ($statusCode == "Active") {

                                        /*  $postDataRemit = '
                                     <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                     <soap:Body>
                                     <ns:mm_remit_log xmlns:ns="http://ws.mfsafrica.com">
                                     <ns:login>
                                     <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code> 
                                     <ns:password>' . CORPORATEPASS . '</ns:password> 
                                     </ns:login>
                                     <ns:receive_amount>
                                     <ns:amount>' . $getOnfi->amount . '</ns:amount> 
                                     <ns:currency_code>' . $getOnfi->recipientCurrency . '</ns:currency_code> 
                                     </ns:receive_amount>
                                     <ns:sender>
                                     <ns:msisdn></ns:msisdn> 
                                     <ns:name>' . $getOnfi->senderName . '</ns:name> 
                                     <ns:surname></ns:surname> 
                                     <ns:document>
                                     <ns:id_type></ns:id_type>
                                     <ns:id_number>' . $getOnfi->senderIdNumber . '</ns:id_number>
                                     <ns:id_country>' . $getOnfi->senderCountry . '</ns:id_country>
                                     <ns:id_expiry>' . $getOnfi->senderIdExpiry . '</ns:id_expiry>
                                     </ns:document>
                                     </ns:sender>
                                     <ns:recipient>
                                     <ns:msisdn>' . $getOnfi->recipientMsisdn . '</ns:msisdn> 
                                     <ns:to_country>' . $getOnfi->recipientCountry . '</ns:to_country> 
                                     <ns:surname>' . $getOnfi->recipientSurname . '</ns:surname>
                                     <ns:name>' . $getOnfi->recipientName . '</ns:name>
                                     </ns:recipient>
                                     <ns:third_party_trans_id>' . $getOnfi->thirdPartyTransactionId . '</ns:third_party_trans_id> 
                                     </ns:mm_remit_log>
                                     </soap:Body>
                                     </soap:Envelope>'; */

                                        $postDataRemit = '
                                            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                            <soap:Body>
                                                <ns:mm_remit_log xmlns:ns="http://ws.mfsafrica.com">
                                                <ns:login>
                                                    <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code> 
                                                    <ns:password>' . CORPORATEPASS . '</ns:password> 
                                                </ns:login>
                                                <ns:receive_amount>
                                                    <ns:amount>' . $getOnfi->amount . '</ns:amount> 
                                                    <ns:currency_code>' . $getOnfi->recipientCurrency . '</ns:currency_code> 
                                                </ns:receive_amount>
                                                <ns:sender>
                                                    <ns:address>' . ($getOnfi->senderAddress ?: "") . '</ns:address>
                                                    <ns:city>string</ns:city>
                                                    <ns:date_of_birth>' . ($getOnfi->senderDob ?: "") . '</ns:date_of_birth>
                                                    <ns:document>
                                                    <ns:id_country>string</ns:id_country>
                                                    <ns:id_expiry>string</ns:id_expiry>
                                                    <ns:id_number>' . ($getOnfi->senderIdNumber ?: "") . '</ns:id_number>
                                                    <ns:id_type>' . ($getOnfi->senderIdType ?: "") . '</ns:id_type>
                                                    </ns:document>
                                                    <ns:email>string</ns:email>
                                                    <ns:from_country>' . $getOnfi->senderCountry . '</ns:from_country>
                                                    <ns:msisdn>' . $getOnfi->senderMsisdn . '</ns:msisdn>
                                                    <ns:name>' . $getOnfi->senderName . '</ns:name>
                                                    <ns:place_of_birth>string</ns:place_of_birth>
                                                    <ns:postal_code>string</ns:postal_code>
                                                    <ns:state>string</ns:state>
                                                    <ns:surname>' . $getOnfi->senderSurname . '</ns:surname>
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
                                                    <ns:msisdn>' . $getOnfi->recipientMsisdn . '</ns:msisdn>
                                                    <ns:name>' . $getOnfi->recipientName . '</ns:name>
                                                    <ns:postal_code>string</ns:postal_code>
                                                    <ns:state>string</ns:state>
                                                    <ns:status>
                                                    <ns:status_code>string</ns:status_code>
                                                    </ns:status>
                                                    <ns:surname>' . $getOnfi->recipientSurname . '</ns:surname>
                                                    <ns:to_country>' . $getOnfi->recipientCountry . '</ns:to_country>
                                                </ns:recipient>
                                                <ns:third_party_trans_id>' . $getOnfi->thirdPartyTransactionId . '</ns:third_party_trans_id>
                                                <ns:reference>string</ns:reference>
                                                <ns:source_of_funds>string</ns:source_of_funds>
                                                <ns:purpose_of_transfer>string</ns:purpose_of_transfer>
                                                </ns:mm_remit_log>
                                            </soap:Body>
                                            </soap:Envelope>
                                    ';

                                        $getResponseRemit = $this->sendCurlRequest($postDataRemit, 'urn:mm_remit_log');



                                        Log::channel('ONAFRIQ')->info("$postDataRemit");
                                        Log::channel('ONAFRIQ')->info($getResponseRemit);
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
                                            $postDataTrans = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
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

                                            /* if ($statusCode2 == "MR102") {
                                                $getRes = $this->getTransStatus(3, $mfs_trans_id);
                                                if ($getRes != "success") {
                                                    throw new \Exception('not success');
                                                }
                                            } */

                                            Log::channel('ONAFRIQ')->info("$postDataTrans");
                                            Log::channel('ONAFRIQ')->info($getResponseTrans);

                                            if ($statusCode2 === 'MR101') {

                                                $userRec = User::where('id', $user_id)->first();
                                                $sender_wallet_amount = $userRec->holdAmount - $total_amount;

                                                Transaction::where('excel_trans_id', $row->id)->update(['status' => 1]);
                                                User::where('id', $user_id)->update(['holdAmount' => $sender_wallet_amount]);
                                                DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);



                                                OnafriqaData::where('excelTransId', $row->id)->update(['transactionId' => $mfs_trans_id, 'status' => 'success', 'partnerCode' => $partner_code]);
                                                $t_inserted_record += 1;
                                            } else {

                                                if ($statusCode2 == 'MR108' || $statusCode2 == 'MR103' || $statusCode2 == 'MR102') {
                                                    Transaction::where('excel_trans_id', $row->id)->update(['bda_status' => 5]);
                                                    OnafriqaData::where('excelTransId', $row->id)->update(['transactionId' => $mfs_trans_id, 'partnerCode' => $partner_code]);
                                                    $t_inserted_record += 1;
                                                } else {
                                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => 'Subscriber not authorized to receive amount', 'approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);
                                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                                    $sendUserCurrentBal += $feeWithAmt;
                                                    $n_inserted_record += 1;
                                                }
                                            }
                                        } else {
                                            DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                            DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                            ExcelTransaction::where('id', $row->id)->update(['remarks' => 'Transaction not execute', 'approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);
                                            Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                            $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                            $sendUserCurrentBal += $feeWithAmt;
                                            $n_inserted_record += 1;
                                        }
                                    } else {
                                        DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                        DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                        ExcelTransaction::where('id', $row->id)->update(['remarks' => 'Recipient phone number not active ', 'approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);
                                        Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                        $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                        $sendUserCurrentBal += $feeWithAmt;
                                        $n_inserted_record += 1;
                                    }
                                } else {
                                    Session::put('error_message', __('message.Hold balance not remaining your wallet'));
                                    return Redirect::to('/pending-approvals');
                                }
                            }
                        } catch (\Exception $e) {
                            Log::channel('ONAFRIQ')->error($e->getMessage());
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
                                    $next_issuertrxref = '140071';
                                }

                                $total_fees = $this->calculateFees($data['amount'], $user_id, 'Money Transfer Via GIMAC');

                                $total_amount = $amount + $total_fees;

                                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                                    $errorDescription = $jsonResponse['error_description'];
                                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);

                                    // $updatedWalletBal = $senderUser->wallet_balance + $total_amount;
                                    // $updatedHoldBal = $senderUser->holdAmount - $total_amount;
                                    // $wallet = DB::table('users')->where('id', $senderUser->id)->update(['wallet_balance' => $updatedWalletBal, 'holdAmount' => $updatedHoldBal]);

                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);

                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => $errorDescription]);
                                    Log::channel('GIMAC')->info("Rejected First Catch $next_issuertrxref.'----'.$errorDescription");
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3, 'issuertrxref' => $next_issuertrxref]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;

                                    $n_inserted_record += 1;
                                } else {
                                    // $updatedWalletBal = $senderUser->wallet_balance + $total_amount;
                                    // $updatedHoldBal = $senderUser->holdAmount - $total_amount;
                                    // $wallet = DB::table('users')->where('id', $senderUser->id)->update(['wallet_balance' => $updatedWalletBal, 'holdAmount' => $updatedHoldBal]);

                                    DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                    DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);

                                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => __('message.Unable to extract error_description')]);
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.Unable to extract error_description')]);
                                    Log::channel('GIMAC')->info("Rejected Second Catch $next_issuertrxref Unable to extract error_description");
                                    Transaction::where('excel_trans_id', $row->id)->update(['status' => 3, 'issuertrxref' => $next_issuertrxref]);
                                    $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                    $sendUserCurrentBal += $feeWithAmt;

                                    $n_inserted_record += 1;
                                }
                            } else {
                                // $stackTrace = $e->getTraceAsString();
                                // print_r($stackTrace); die;
                                if ($row->bdastatus == 'BDA') {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.BDA server error')]);
                                } elseif ($row->bdastatus == 'ONAFRIQ') {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.ONAFRIQ server error !!!')]);
                                } else {
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('message.GIMAC server error')]);
                                }
                                DB::table('users')->where('id', $senderUser->id)->increment('wallet_balance', $feeWithAmt);
                                DB::table('users')->where('id', $senderUser->id)->decrement('holdAmount', $feeWithAmt);
                                Transaction::where('excel_trans_id', $row->id)->update(['status' => 3]);
                                $this->transLadger($user_id, $sendUserCurrentBal, $amount, $row->fees, '1', $row->id, $getTransId->id);
                                $sendUserCurrentBal += $feeWithAmt;
                                $n_inserted_record += 1;
                            }
                        }
                    }
                }
                // }
            }

            if ($n_inserted_record > 0) {
                $title = 'Failure';
                $message = 'Transfer failed for ' . $n_inserted_record . ' records.';

                $title_fr = 'Échec';
                $message_fr = 'Le transfert a échoué pour ' . $n_inserted_record . ' enregistrements.';

                $device_type = $userData->device_type;
                $device_token = $userData->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $user_id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'notif_title_fr' => $title_fr,
                    'notif_body_fr' => $message_fr,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();

                Session::put('error_message', __('message.We got error in') . $n_inserted_record . __('message.records while transfer.'));
            }

            UploadedExcel::where('id', $id)->update(['status' => 5, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id, 'approved_by_merchant' => Auth::user()->id, 'approved_merchant_date' => now()]);

            if ($t_inserted_record > 0) {
                $title = 'Transfered';
                $message = 'Initiated successful transfer for ' . $t_inserted_record . ' records';

                $title_fr = 'Transféré';
                $message_fr = 'Transfert réussi lancé pour ' . $t_inserted_record . ' enregistrements.';

                $device_type = $userData->device_type;
                $device_token = $userData->device_token;

                $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $user_id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'notif_title_fr' => $title_fr,
                    'notif_body_fr' => $message_fr,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();
                //Session::put(__('success_message '), $t_inserted_record . __(' records inserted successfully!'));
                Session::put('success_message', $t_inserted_record . ' ' . __('message.records inserted successfully!'));
            }

            if (!empty($total_fees_arry)) {
                $all_fees_amount = array_sum($total_fees_arry);
                UploadedExcel::where('id', $id)->update(['total_fees' => $all_fees_amount]);
            }

            return back();
        } elseif ($user_type == "Approver") {
            UploadedExcel::where('id', $id)->update(['status' => 3, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id, 'approver_id' => Auth::user()->id, 'approved_date' => now()]);
            Session::put('success_message', __('message.Record have been successfully approved and are visible in the merchant account.'));
            return back();
        } else {
            $missingFieldsErrors = [];

            foreach ($excel_transactions as $index => $data) {

                $missingFields = [];
                if ($data['bdastatus'] == 'SWAPTOSWAP') {
                    if (empty($data['tel_number']))
                        $missingFields[] = 'Tel number';
                    if (empty($data['amount']))
                        $missingFields[] = 'Amount';
                } else if ($data['bdastatus'] == 'GIMAC') {
                    if (empty($data['first_name']))
                        $missingFields[] = 'First name';
                    if (empty($data['name']))
                        $missingFields[] = 'Name';
                    if (empty($data['country_id']))
                        $missingFields[] = 'Country';
                    if (empty($data['wallet_manager_id']))
                        $missingFields[] = 'Wallet Manager';
                    if (empty($data['tel_number']))
                        $missingFields[] = 'Tel number';
                    if (empty($data['amount']))
                        $missingFields[] = 'Amount';
                } else if ($data['bdastatus'] == 'BDA') {
                    $data = RemittanceData::where('excel_id', $data['id'])->first();
                    if (empty($data['titleAccount']))
                        $missingFields[] = 'Beneficiary';
                    if (empty($data['iban']))
                        $missingFields[] = 'Iban';
                    if (empty($data['partnerreference']))
                        $missingFields[] = 'PartnerReference';
                    if (empty($data['reason']))
                        $missingFields[] = 'Reason';
                    if (empty($data['amount']))
                        $missingFields[] = 'Amount';
                } else if ($data['bdastatus'] == 'ONAFRIQ') {
                    $data = OnafriqaData::where('excelTransId', $data['id'])->first();
                    if (empty($data['recipientMsisdn']))
                        $missingFields[] = 'Recipient Msisdn';
                    if (empty($data['recipientCountry']))
                        $missingFields[] = 'Recipient Country';
                    if (empty($data['amount']))
                        $missingFields[] = 'Amount';
                }


                if (!empty($missingFields)) {
                    $missingFieldsErrors[] = __('message.Row') . ($index + 1) . ': ' . implode(', ', $missingFields) . __('message.are missing');
                }
            }

            if (!empty($missingFieldsErrors)) {
                $errorMessage = __('message.Errors in Excel file: ') . implode("\n", $missingFieldsErrors);
                Session::put('error_message', $errorMessage);
                return Redirect::to('/pending-approvals');
            }

            UploadedExcel::where('id', $id)->update(['status' => 1, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id]);

            Session::put('success_message', __('message.Records have been successfully approved and are now visible in the approver account.'));
            return back();
            /*UploadedExcel::where('id', $id)->update(['status' => 1, 'approved_by' => Auth::user()->id]);
        ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id]);
        Session::put('success_message', __('Record have been successfully approved and are visible in the approver account.'));
        return back();*/
        }
    }

    public function createTransaction($parentId, $receiver_id, $receiver_mobile, $amount, $total_fees, $excelId, $onafriq_bda_ids, $type)
    {

        $receiverId = (!empty($type) && $type == 'SWAPTOSWAP') ? $receiver_id : 0;
        $receviverMobile = (!empty($type) && $type == 'SWAPTOGIMAC') ? $receiver_mobile : "";
        $refrence_id = (!empty($type) && $type == 'SWAPTOSWAP') ? time() . rand() : "";
        $status = (!empty($type) && $type == 'SWAPTOGIMAC') ? 0 : 2;
        $onafriqBdaIds = (!empty($type) && $type == 'SWAPTOONAFRIQ' || $type == 'SWAPTOBDA') ? $onafriq_bda_ids : 0;



        $feeWithTotalAmt = $amount + $total_fees;
        $trans = new Transaction([
            'user_id' => $parentId,
            'receiver_id' => $receiverId,
            'receiver_mobile' => $receviverMobile,
            'amount' => $amount,
            'amount_value' => $amount,
            'transaction_amount' => $total_fees,
            'total_amount' => $feeWithTotalAmt,
            'trans_type' => 2,
            'excel_trans_id' => $excelId,
            'payment_mode' => 'wallet2wallet',
            'status' => $status,
            'refrence_id' => $refrence_id,
            'billing_description' => "Fund Transfer-" . time() . rand(),
            'onafriq_bda_ids' => $onafriqBdaIds,
            'transactionType' => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $trans->save();
        return $trans->id;
    }
    public function transLadger($userId, $openingBal, $amount, $fees, $type, $excelId, $transId = '')
    {
        $totalAmount = $amount + $fees;
        if ($type == 1) {
            $closingBal = $openingBal + $totalAmount;
        } else {
            $closingBal = $openingBal - $totalAmount;
        }
        $result = new TransactionLedger([
            'user_id' => $userId,
            'opening_balance' => $openingBal,
            'amount' => $amount,
            'fees' => $fees,
            'actual_amount' => $totalAmount,
            'excelTransId' => $excelId,
            'type' => $type, // 1 for credit and 2 for debit
            'payment_mode' => 'wallet2wallet',
            'closing_balance' => $closingBal,
            'trans_id' => $transId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $result->save();
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

    private function generateRandomString($length)
    {
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= rand(0, 9); // Append a random digit (0-9) to the string
        }
        return $randomString;
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

    public function notifications()
    {
        $pageTitle = 'Notifications List';
        $lang = Session::get('locale');
        return view('dashboard.notifications', ['title' => $pageTitle, 'lang' => $lang]);
    }

    public function pendingapprovals()
    {
        $pageTitle = 'Pending Approvals';
        return view('dashboard.pending-approvals', ['title' => $pageTitle]);
    }

    public function transitionhistory()
    {
        $pageTitle = 'Transaction History';
        $country_list = Country::orderBy('name', 'asc')->get();
        $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();
        return view('dashboard.transition-history', ['title' => $pageTitle, 'country_list' => $country_list, 'wallet_manager_list' => $wallet_manager_list]);
    }

    public function operationsmonth()
    {
        $pageTitle = 'operations-month';
        return view('dashboard.operations-month', ['title' => $pageTitle]);
    }

    public function numberofsuccess()
    {
        $pageTitle = 'number_success';
        $country_list = Country::orderBy('name', 'asc')->get();
        $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();
        return view('dashboard.number_success', ['title' => $pageTitle, 'country_list' => $country_list, 'wallet_manager_list' => $wallet_manager_list]);
    }

    public function successfullTransactionList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $daterange = $request->get('daterange'); // Extract date range from request
        //$searchValue = $search_arr['value']; // Search value
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;

        $country_id = $request->get('country_id'); // Get country_id from the request
        $wallet_manager_id = $request->get('wallet_manager_id'); // Get wallet_manager_id from the request


        $query = new ExcelTransaction();
        $lang = Session::get('locale');


        $totalRecords = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)
            ->where('transactions.status', 1)
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id') // Alias to avoid overwriting approver join
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('first_name', 'like', '%' . $search_arr . '%')
                    ->orWhere('comment', 'like', '%' . $search_arr . '%')
                    ->orWhere('tel_number', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $search_arr . '%')
                    //->orWhere('countries.name', 'like', '%' . $search_arr . '%')
                    // Search by submitter and approver name
                    ->orWhere('submitter.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('merchant.name', 'like', '%' . $search_arr . '%');
                //->orWhere('wallet_managers.name', 'like', '%' . $search_arr . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('excel_transactions.created_at', '>=', $start_date)
                        ->whereDate('excel_transactions.created_at', '<=', $end_date);
                }
            })
            ->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status')->count();


        $totalRecordswithFilter = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)
            ->where('transactions.status', 1)
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id') // Alias to avoid overwriting approver join
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('first_name', 'like', '%' . $search_arr . '%')
                    ->orWhere('comment', 'like', '%' . $search_arr . '%')
                    ->orWhere('tel_number', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $search_arr . '%')
                    //->orWhere('countries.name', 'like', '%' . $search_arr . '%')
                    // Search by submitter and approver name
                    ->orWhere('submitter.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('merchant.name', 'like', '%' . $search_arr . '%');
                //->orWhere('wallet_managers.name', 'like', '%' . $search_arr . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('excel_transactions.created_at', '>=', $start_date)
                        ->whereDate('excel_transactions.created_at', '<=', $end_date);
                }
            })

            ->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status')
            ->count();
        //  DB::enableQueryLog();
        $records = $query->where('excel_transactions.parent_id', $parent_id)
            ->where('transactions.status', 1)
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id') // Alias to avoid overwriting approver join
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('first_name', 'like', '%' . $search_arr . '%')
                    ->orWhere('comment', 'like', '%' . $search_arr . '%')
                    ->orWhere('tel_number', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $search_arr . '%')
                    //->orWhere('countries.name', 'like', '%' . $search_arr . '%')
                    // Search by submitter and approver name
                    ->orWhere('submitter.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('merchant.name', 'like', '%' . $search_arr . '%');
                //->orWhere('wallet_managers.name', 'like', '%' . $search_arr . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('excel_transactions.created_at', '>=', $start_date)
                        ->whereDate('excel_transactions.created_at', '<=', $end_date);
                }
            })
            ->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select(
                'excel_transactions.*',
                'submitter.name as submitted_by',
                'approver.name as approved_by',
                'approver_2.name as approver_name', // Name of the approver based on approver_id
                'merchant.name as merchant_by', // Name of the merchant
                'countries.name as country_name',
                'wallet_managers.name as wallet_name',
                'transactions.status as transaction_status'
            )
            ->when($columnName, function ($query) use ($columnName, $columnSortOrder) {
                switch ($columnName) {
                    case 'submitted_by':
                        $orderByColumn = 'submitter.name';
                        break;
                    case 'approved_by':
                        $orderByColumn = 'approver.name';
                        break;
                    default:
                        $orderByColumn = $columnName;
                        break;
                }
                $query->orderBy('created_at', 'DESC');
            })
            ->skip($start)
            ->take($rowperpage)
            ->get();

        //dd(\DB::getQueryLog()); // Show results of log
        $data_arr = array();
        foreach ($records as $record) {
            global $months;

            if ($lang == 'fr') {
                $submittedDate = str_replace(array_keys($months), $months, date('d F Y', strtotime($record->created_at)));
                $approvedDate = $record->approved_date ? str_replace(array_keys($months), $months, date('d F Y', strtotime($record->approved_date))) : '-';
                $approvedMerchantDate = $record->approved_merchant_date ? str_replace(array_keys($months), $months, date('d F Y', strtotime($record->approved_merchant_date))) : '-';
            } else {
                $submittedDate = date('d M Y', strtotime($record->created_at));
                $approvedDate = $record->approved_date ? date('d M Y', strtotime($record->approved_date)) : '-';
                $approvedMerchantDate = $record->approved_merchant_date ? date('d M Y', strtotime($record->approved_merchant_date)) : '-';
            }



            $status = ($record->remarks != "") ? 'Rejected' : $this->getStatusText($record->transaction_status);
            $statusupdate = '';


            if ($lang == 'fr') {
                if ($status == 'Completed') {
                    // echo "ok" ;die;
                    $statusupdate = 'Terminé';
                } else if ($status == 'Pending') {
                    $statusupdate = 'En attente';
                } else if ($status == 'Failed') {
                    $statusupdate = 'Échoué';
                } else if ($status == 'Rejected') {
                    $statusupdate = 'Rejeté';
                } else if ($status == 'Refund') {
                    $statusupdate = 'Remboursement';
                } else if ($status == 'Refund Completed') {
                    $statusupdate = 'Remboursement terminé';
                }
            } elseif ($lang == 'en') {
                if ($status == 'Completed') {
                    // echo "simple" ;die;
                    $statusupdate = 'Completed';
                } else if ($status == 'Pending') {
                    $statusupdate = 'Pending';
                } else if ($status == 'Failed') {
                    $statusupdate = 'Failed';
                } else if ($status == 'Rejected') {
                    $statusupdate = 'Rejected';
                } else if ($status == 'Refund') {
                    $statusupdate = 'Refund';
                } else if ($status == 'Refund Completed') {
                    $statusupdate = 'Refund Completed';
                }
            }

            $amount = number_format($record->amount, 0, '.', ',');

            $OnafriqaData = OnafriqaData::where('excelTransId', $record->id)->first();
            if (!empty($record->bdastatus) && $record->bdastatus == "ONAFRIQ") {
                $firstName = $OnafriqaData->recipientName ?? '-';
                $lastName = $OnafriqaData->recipientSurname ?? '-';
            } else {
                $firstName = $record->first_name;
                $lastName = $record->name;
            }
            $data_arr[] = array(
                "first_name" => $firstName != '' ? $firstName : '-',
                "name" => $lastName != '' ? $lastName : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                /* "country_name" => $record->country_name != '' ? $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number, */

                "country_name" => $record->country_name != '' ? $record->country_name : (isset($OnafriqaData->recipientCountry) && $OnafriqaData->recipientCountry != '' ? $this->getCountryStatus($OnafriqaData->recipientCountry) : '-'),
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : (isset($OnafriqaData->walletManager) && $OnafriqaData->walletManager != '' ? $OnafriqaData->walletManager : '-'),
                "tel_number" => $record->tel_number != '' ? $record->tel_number : (isset($OnafriqaData->recipientMsisdn) && $OnafriqaData->recipientMsisdn != '' ? $OnafriqaData->recipientMsisdn : '-'),


                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "submitted_date" => $submittedDate,
                "approved_by" => $record->approver_name ? $record->approver_name : '-', // Name of the approver from approved_by
                "approved_date" => $approvedDate,
                "merchant_by" => $record->merchant_by ? $record->merchant_by : '-',
                "approved_merchant_date" => $approvedMerchantDate,
                "gimac_status" => $statusupdate ?? "",
                "remarks" => $record->remarks ? $record->remarks : '-',
            );
        }
        $totalRecords = number_format($totalRecords, 0, '.', ',');
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function operationofthismonthList(Request $request)
    {
        $pageTitle = 'operation of this month';
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length") != '-1' ? $request->get("length") : 5;
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        //$searchValue = $search_arr['value']; // Search value
        $daterange = $request->get('daterange'); // Extract date range from request
        $query = new UploadedExcel();
        $lang = Session::get('locale');

        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $currentMonth = Carbon::now()->format('Y-m');
        $currentMonthTransactions = ExcelTransaction::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->count();
        $totalTransactions = ExcelTransaction::where('parent_id', $parent_id)->count();
        $opt_this_month = round(($currentMonthTransactions > 0) ? ($currentMonthTransactions / $totalTransactions) * 100 : 0);

        $totalRecords = UploadedExcel::select('count(*)
           as allcount')->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->whereIn('status', [1, 3, 4, 5, 6])->where(function ($q) use ($search_arr, $daterange) {
            $q->orWhere('reference_id', 'like', '%' . $search_arr . '%');
        })->where(function ($q) use ($daterange) {
            if ($daterange) {
                $dt = explode(' - ', $daterange);
                $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                    ->whereDate('uploaded_excels.created_at', '<=', $end_date);
            }
        })->count();
        //$totalRecordswithFilter = UploadedExcel::select('count(*)
        $totalRecordswithFilter = UploadedExcel::select('count(*)
           as allcount')->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->whereIn('status', [1, 3, 4, 5, 6])
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('reference_id', 'like', '%' . $search_arr . '%');
            })->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                        ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                }
            })
            ->count();
        //dd(\DB::getQueryLog());
        $records = UploadedExcel::orderBy('created_at', 'desc')
            ->join('users', 'uploaded_excels.user_id', '=', 'users.id')
            ->whereRaw("DATE_FORMAT(uploaded_excels.created_at, '%Y-%m') = ?", [$currentMonth])
            ->where('uploaded_excels.parent_id', $parent_id)
            ->whereIn('uploaded_excels.status', [1, 3, 4, 5, 6])
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('reference_id', 'like', '%' . $search_arr . '%');
            })->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('uploaded_excels.created_at', '>=', $start_date)
                        ->whereDate('uploaded_excels.created_at', '<=', $end_date);
                }
            })
            ->select('uploaded_excels.*', 'uploaded_excels.id as id', 'users.name as name', 'users.email as email', 'users.user_type as user_type')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();
        foreach ($records as $record) {
            $encId = base64_encode($this->encryptContent($record->id));
            $excelTrans = ExcelTransaction::where('excel_id', $record->id)->first();

            $transSt = '';
            if (isset($excelTrans['bdastatus']) && $excelTrans['bdastatus'] == "BDA") {
                $transSt = 'BDA';
            } else {
                $transSt = 'ONAFRIQ';
            }

            $action = '
            <a href="' . $record->id . '" data-bs-toggle="modal" data-bdastatus="' . $transSt . '" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="' . __('message.View') . '"></i></a>' .
                ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="' . __('message.Download Excel') . '"></i></a>' : '');

            $amount = number_format($record->totat_amount, 0, '.', ',');


            global $months;


            if ($lang == 'fr') {
                $date = date('d F Y', strtotime($record->created_at)); // Full year without comma
                $frenchDate = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate = date('d M Y', strtotime($record->created_at)); // Full year
            }
            $status = $this->getStatus($record->status);

            $statusupdate = '';
            if ($lang == 'fr') {
                if ($status == 'Pending ') {
                    // echo "ok" ;die;
                    $statusupdate = 'En attente';
                } else if ($status == 'Approved ') {
                    $statusupdate = 'Approuvé';
                } else if ($status == 'Rejected ') {
                    $statusupdate = 'Rejeté';
                }
            } elseif ($lang == 'en') {
                if ($status == 'Pending ') {
                    // echo "ok" ;die;
                    $statusupdate = 'Pending ';
                } else if ($status == 'Approved ') {
                    $statusupdate = 'Approved';
                } else if ($status == 'Rejected ') {
                    $statusupdate = 'Rejected';
                }
            }

            $remark = '';

            if ($lang == 'fr') {

                $remark = $record->remarks != "" ? ucfirst($record->remarks) : __('Salaire');
            } elseif ($lang == 'en') {
                $remark = $record->remarks != "" ? ucfirst($record->remarks) : __('Salary');
            }

            $data_arr[] = array(
                "reference_id" => $record->reference_id,
                "remarks" => $remark,
                "excel" => $record->excel,
                "no_of_records" => $record->no_of_records,
                "created_at" => $frenchDate,
                "total_fees" => CURR . ' ' . $record->total_fees,
                "totat_amount" => CURR . ' ' . $amount,
                "status" => $statusupdate,
                "action" => $action,
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "opt_this_month" => $opt_this_month,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function failureTransaction()
    {
        $pageTitle = 'failure-transaction';
        $country_list = Country::orderBy('name', 'asc')->get();
        $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();
        return view('dashboard.failure-transaction', ['title' => $pageTitle, 'country_list' => $country_list, 'wallet_manager_list' => $wallet_manager_list]);
    }

    public function failureTransactionList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $daterange = $request->get('daterange'); // Extract date range from request
        $country_id = $request->get('country_id'); // Get country_id from the request
        $wallet_manager_id = $request->get('wallet_manager_id'); // Get wallet_manager_id from the request
        //$searchValue = $search_arr['value']; // Search value
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $query = new ExcelTransaction();
        $lang = Session::get('locale');

        $totalRecords = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)
            ->whereNotNull('excel_transactions.remarks')
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id') // Alias to avoid overwriting approver join
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('first_name', 'like', '%' . $search_arr . '%')
                    ->orWhere('comment', 'like', '%' . $search_arr . '%')
                    ->orWhere('tel_number', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $search_arr . '%')
                    //->orWhere('countries.name', 'like', '%' . $search_arr . '%')
                    // Search by submitter and approver name
                    ->orWhere('submitter.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('merchant.name', 'like', '%' . $search_arr . '%');
                //->orWhere('wallet_managers.name', 'like', '%' . $search_arr . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('excel_transactions.created_at', '>=', $start_date)
                        ->whereDate('excel_transactions.created_at', '<=', $end_date);
                }
            })->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status')->count();


        $totalRecordswithFilter = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)
            ->whereNotNull('excel_transactions.remarks')
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id') // Alias to avoid overwriting approver join
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('first_name', 'like', '%' . $search_arr . '%')
                    ->orWhere('comment', 'like', '%' . $search_arr . '%')
                    ->orWhere('tel_number', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $search_arr . '%')
                    //->orWhere('countries.name', 'like', '%' . $search_arr . '%')
                    // Search by submitter and approver name
                    ->orWhere('submitter.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('merchant.name', 'like', '%' . $search_arr . '%');
                //->orWhere('wallet_managers.name', 'like', '%' . $search_arr . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('excel_transactions.created_at', '>=', $start_date)
                        ->whereDate('excel_transactions.created_at', '<=', $end_date);
                }
            })->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status')
            ->count();
        //  DB::enableQueryLog();
        $records = $query->where('excel_transactions.parent_id', $parent_id)
            ->whereNotNull('excel_transactions.remarks')
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id') // Alias to avoid overwriting approver join
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($search_arr, $daterange) {
                $q->orWhere('first_name', 'like', '%' . $search_arr . '%')
                    ->orWhere('comment', 'like', '%' . $search_arr . '%')
                    ->orWhere('tel_number', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $search_arr . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $search_arr . '%')
                    //->orWhere('countries.name', 'like', '%' . $search_arr . '%')
                    // Search by submitter and approver name
                    ->orWhere('submitter.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $search_arr . '%')
                    ->orWhere('merchant.name', 'like', '%' . $search_arr . '%');
                // ->orWhere('wallet_managers.name', 'like', '%' . $search_arr . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dt = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                    $q->whereDate('excel_transactions.created_at', '>=', $start_date)
                        ->whereDate('excel_transactions.created_at', '<=', $end_date);
                }
            })->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select(
                'excel_transactions.*',
                'submitter.name as submitted_by',
                'approver.name as approved_by',
                'approver_2.name as approver_name', // Name of the approver based on approver_id
                'merchant.name as merchant_by', // Name of the merchant
                'countries.name as country_name',
                'wallet_managers.name as wallet_name',
                'transactions.status as transaction_status'
            )
            ->when($columnName, function ($query) use ($columnName, $columnSortOrder) {
                switch ($columnName) {
                    case 'submitted_by':
                        $orderByColumn = 'submitter.name';
                        break;
                    case 'approved_by':
                        $orderByColumn = 'approver.name';
                        break;
                    default:
                        $orderByColumn = $columnName;
                        break;
                }
                $query->orderBy('created_at', 'DESC');
            })
            ->skip($start)
            ->take($rowperpage)
            ->get();

        //dd(\DB::getQueryLog()); // Show results of log
        global $months;
        $data_arr = array();
        foreach ($records as $record) {
            $OnafriqaData = OnafriqaData::where('excelTransId', $record->id)->first();

            if ($lang == 'fr') {
                $submittedDate = str_replace(array_keys($months), $months, date('d F Y', strtotime($record->created_at)));
                $approvedDate = $record->approved_date ? str_replace(array_keys($months), $months, date('d F Y', strtotime($record->approved_date))) : '-';
                $approvedMerchantDate = $record->approved_merchant_date ? str_replace(array_keys($months), $months, date('d F Y', strtotime($record->approved_merchant_date))) : '-';
            } else {
                $submittedDate = date('d M Y', strtotime($record->created_at));
                $approvedDate = $record->approved_date ? date('d M Y', strtotime($record->approved_date)) : '-';
                $approvedMerchantDate = $record->approved_merchant_date ? date('d M Y', strtotime($record->approved_merchant_date)) : '-';
            }

            $status = $record->remarks != "" ? 'Rejected' : $this->getStatusText($record->transaction_status);

            $amount = number_format($record->amount, 0, '.', ',');

            $remark = '';

            if ($lang === 'fr') {
                if ($record->remarks === 'BDA server error') {
                    $remark = 'Erreur du serveur BDA';
                } elseif ($record->remarks === 'Rejected') {
                    $remark = 'Rejeté';
                } elseif ($record->remarks === 'You cannot send funds to yourself') {
                    $remark = 'Vous ne pouvez pas envoyer de fonds à vous-même.';
                } elseif ($record->remarks === 'gimac server error' || $record->remarks === 'GIMAC server error') {
                    $remark = 'Erreur du serveur GIMAC';
                } elseif ($record->remarks === 'Insufficient Balance !') {
                    $remark = 'Solde insuffisant !';
                } elseif ($record->remarks === 'You cannot transfer more thanXAF 200000because your KYC is not verified.Please verify your KYC first') {
                    $remark = 'Vous ne pouvez pas transférer plus de 200 000 XAF car votre KYC nest pas vérifié. Veuillez dabord vérifier votre KYC';
                } elseif ($record->remarks === 'ONAFRIQ server error') {
                    $remark = 'Erreur du serveur ONAFRIQ';
                } elseif ($record->remarks === 'Unable to extract error_description') {
                    $remark = 'Impossible dextraire la description de lerreur.';
                } elseif ($record->remarks === 'Your monthly transfer limit has been reached.') {
                    $remark = 'Votre limite de transfert mensuelle a été atteinte.';
                } elseif ($record->remarks === 'Your weekly transfer limit has been reached.') {
                    $remark = 'Votre limite de transfert hebdomadaire a été atteinte.';
                } elseif ($record->remarks === 'Your daily transfer limit has been reached.') {
                    $remark = 'Votre limite de transfert quotidienne a été atteinte.';
                } elseif ($record->remarks === 'The minimum transfer amount should be greater than XAF 1.') {
                    $remark = 'Le montant minimum du transfert doit être supérieur à 1 XAF.';
                } elseif ($record->remarks === 'You cannot transfer more than XAF 200000 because your KYC is still pending.') {
                    $remark = 'Vous ne pouvez pas transférer plus de 200 000 XAF car votre KYC est toujours en attente.';
                } elseif ($record->remarks === 'You cannot transfer less than XAF 1.') {
                    $remark = 'Vous ne pouvez pas transférer moins de 1 XAF.';
                } elseif ($record->remarks === 'Recipient phone number not active ') {
                    $remark = 'Numéro de téléphone du destinataire inactif.';
                } elseif ($record->remarks === 'Subscriber not authorized to receive amount') {
                    $remark = 'L\abonné n\est pas autorisé à recevoir le montant.';
                } else {
                    $remark = $record->remarks;
                }
            } elseif ($lang === 'en') {
                if ($record->remarks === 'BDA server error') {
                    $remark = 'BDA server error';
                } elseif ($record->remarks === 'Rejected') {
                    $remark = 'Rejected';
                } elseif ($record->remarks === 'You cannot send funds to yourself') {
                    $remark = 'You cannot send funds to yourself';
                } elseif ($record->remarks === 'gimac server error' || $record->remarks === 'GIMAC server error') {
                    $remark = 'GIMAC server error';
                } elseif ($record->remarks === 'Insufficient Balance !') {
                    $remark = 'Insufficient Balance !';
                } elseif ($record->remarks === 'You cannot transfer more thanXAF 200000because your KYC is not verified.Please verify your KYC first') {
                    $remark = 'You cannot transfer more thanXAF 200000because your KYC is not verified.Please verify your KYC first';
                } elseif ($record->remarks === 'ONAFRIQ server error') {
                    $remark = 'ONAFRIQ server error';
                } elseif ($record->remarks === 'Unable to extract error_description') {
                    $remark = 'Unable to extract error_description';
                } elseif ($record->remarks === 'Your monthly transfer limit has been reached.') {
                    $remark = 'Your monthly transfer limit has been reached.';
                } elseif ($record->remarks === 'Your weekly transfer limit has been reached.') {
                    $remark = 'Your weekly transfer limit has been reached.';
                } elseif ($record->remarks === 'Your daily transfer limit has been reached.') {
                    $remark = 'Your daily transfer limit has been reached.';
                } elseif ($record->remarks === 'The minimum transfer amount should be greater than XAF 1.') {
                    $remark = 'The minimum transfer amount should be greater than XAF 1.';
                } elseif ($record->remarks === 'You cannot transfer more than XAF 200000 because your KYC is still pending.') {
                    $remark = 'You cannot transfer more than XAF 200000 because your KYC is still pending.';
                } elseif ($record->remarks === 'You cannot transfer less than XAF 1.') {
                    $remark = 'You cannot transfer less than XAF 1.';
                } elseif ($record->remarks === 'Recipient phone number not active') {
                    $remark = 'Recipient phone number not active.';
                } elseif ($record->remarks === 'Subscriber not authorized to receive amount') {
                    $remark = 'Subscriber not authorized to receive amount.';
                } else {
                    $remark = $record->remarks;
                }
            }

            if (!empty($record->bdastatus) && $record->bdastatus == "ONAFRIQ") {
                $firstName = $OnafriqaData->recipientName ?? 0;
                $lastName = $OnafriqaData->recipientSurname ?? 0;
            } else {
                $firstName = $record->first_name;
                $lastName = $record->name;
            }

            $data_arr[] = array(
                "first_name" => $firstName != '' ? $firstName : '-',
                "name" => $lastName != '' ? $lastName : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                /* "country_name" => $record->country_name != '' ? $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number, */

                "country_name" => $record->country_name != '' ? $record->country_name : (isset($OnafriqaData->recipientCountry) && $OnafriqaData->recipientCountry != '' ? $this->getCountryStatus($OnafriqaData->recipientCountry) : '-'),
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : (isset($OnafriqaData->walletManager) && $OnafriqaData->walletManager != '' ? $OnafriqaData->walletManager : '-'),
                "tel_number" => $record->tel_number != '' ? $record->tel_number : (isset($OnafriqaData->recipientMsisdn) && $OnafriqaData->recipientMsisdn != '' ? $OnafriqaData->recipientMsisdn : '-'),

                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "submitted_date" => $submittedDate,
                "approved_by" => $record->approver_name ? $record->approver_name : '-', // Name of the approver from approved_by
                "approved_date" => $approvedDate,
                "merchant_by" => $record->merchant_by ? $record->merchant_by : '-',
                "approved_merchant_date" => $approvedMerchantDate,
                "gimac_status" => $status ?? "",
                "remarks" => $remark ? $remark : '-',
            );
        }
        $totalRecords = number_format($totalRecords, 0, '.', ',');
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function getCountryStatus($countryShortName)
    {
        switch ($countryShortName) {
            case 'BJ':
                return "Benin";
            case 'BF':
                return "Burkina Faso";
            case 'GW':
                return "Guinea Bissau";
            case 'ML':
                return "Mali";
            case 'NE':
                return "Niger";
            case 'SN':
                return "Senegal";
            case 'TG':
                return "Togo";
            case 'CM':
                return "Cameroon";
            case 'CI':
                return "Ivoiry Coast";
            case 'GA':
                return "Gabon";
            case 'FR':
                return "France";

            default:
                return "-";
        }
    }

    public function customerDeposits()
    {
        $pageTitle = 'customer-deposits';
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $wallet_balance = User::where('id', $parent_id)->first()->wallet_balance;
        return view('dashboard.customer-deposits', ['title' => $pageTitle, 'wallet_balance' => $wallet_balance]);
    }

    public function getDepositList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        //$searchValue = $search_arr['value']; // Search value
        $daterange = $request->get('daterange'); // Extract date range from request



        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;

        $query = TransactionLedger::where('user_id', $parent_id);


        // Apply search filter
        if ($search_arr) {
            $query->where(function ($q) use ($search_arr) {
                // Handle search for the 'type' field
                $search_arr = strtolower($search_arr);
                if (stripos($search_arr, 'withdraw') !== false) {
                    $q->orWhere('type', '=', 2); // 'Withdraw' mapped to type = 1
                }
                if (stripos($search_arr, 'credit') !== false) {
                    $q->orWhere('type', '=', 1); // 'Credit' mapped to type = 2
                }
                if (stripos($search_arr, 'cancel') !== false) {
                    $q->orWhere('type', '=', 4); // 'Credit' mapped to type = 2
                }

                // Handle search for other fields
                //     $q->orWhere('amount', 'like', '%' . $search_arr . '%')
                //    ->orWhere('closing_balance', 'like', '%' . $search_arr . '%');
                if (is_numeric($search_arr)) {
                    $search_numeric = round($search_arr);

                    // Handle search for numeric fields using exact rounded match
                    $q->orWhere(DB::raw('ROUND(actual_amount, 0)'), '=', $search_numeric)
                        ->orWhere(DB::raw('ROUND(closing_balance, 0)'), '=', $search_numeric)
                        ->orWhere(DB::raw('ROUND(amount, 0)'), '=', $search_numeric);
                }
            });
        }


        // Apply date range filter
        if ($daterange) {
            $dates = explode(' - ', $daterange);
            $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
            $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        $totalRecords = $query->count();
        // Count total records with filter
        $totalRecordswithFilter = $query->count();

        // Fetch records with pagination
        $records = $query->orderBy('id', 'desc') // Correct column name
            ->skip($start)
            ->take($rowperpage)
            ->get();

        // echo"<pre>";print_r($records);die;
        $data_arr = array();

        foreach ($records as $record) {

            $getExcelR = ExcelTransaction::where('id', $record->excelTransId)->first();

            $transSt = '';
            if (isset($getExcelR['bdastatus']) && $getExcelR['bdastatus'] == "BDA") {
                $transSt = 'BDA';
            } else {
                $transSt = 'ONAFRIQ';
            }

            global $months;
            $lang = Session::get('locale');

            if ($lang == 'fr') {
                $date = date('d F Y', strtotime($record->created_at)); // Full year without comma
                $frenchDate = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate = date('d M Y', strtotime($record->created_at)); // Full year
            }

            if ($lang === 'fr') {
                $userTypeText = $record->type == 1 ? 'Crédit' : ($record->type == 2 ? 'Retirer' : ($record->type == 4 ? 'Annulation' : ($record->type == 5 ? 'Référence' : '')));

            } else {
                $userTypeText = $record->type == 1 ? 'Credit' : ($record->type == 2 ? 'Withdraw' : ($record->type == 4 ? 'Cancellation' : ($record->type == 5 ? 'Referral' : '')));
            }

            $amount = number_format($record->amount, 0, '.', ',');
            $balance = number_format($record->closing_balance, 0, '.', ',');

            $data_arr[] = array(
                "name" => $record->User->name,
                "created_at" => $frenchDate,
                "actual_amount" => CURR . ' ' . $amount,
                //"type" => $record->type == 1 ? 'Credit' : 'Withdraw',
                "type" => $userTypeText,

                "balance" => CURR . ' ' . $balance,
                "action" => '<a href="' . $record->trans_id . '" data-typestatus="' . $userTypeText . '" data-bdastatus="' . $transSt . '" data-user_id="' . $record->user_id . '" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="' . __('message.View') . '"></i></a>'
            );
        }


        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords, // Total records with filter applied
            "iTotalDisplayRecords" => $totalRecordswithFilter, // Filtered records count
            "aaData" => $data_arr,
        );

        return response()->json($response);
    }

    public function movementShipments()
    {
        $pageTitle = 'movement-shipments';
        return view('dashboard.movement-shipments', ['title' => $pageTitle]);
    }

    public function gimacTransactionList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $totalRecords = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.receiver_id', 0)->where('transactions.status', 1)->count();
        $totalRecordswithFilter = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->where('transactions.receiver_id', 0)->count();
        // DB::enableQueryLog();
        $records = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->where('transactions.receiver_id', 0)
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status')
            ->when($columnName, function ($query) use ($columnName, $columnSortOrder) {
                switch ($columnName) {
                    case 'submitted_by':
                        $orderByColumn = 'submitter.name';
                        break;
                    case 'approved_by':
                        $orderByColumn = 'approver.name';
                        break;
                    default:
                        $orderByColumn = $columnName;
                        break;
                }
                $query->orderBy('created_at', 'desc');
            })
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $totalAmount = (int) ExcelTransaction::select('count(*) as allcount')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where('excel_transactions.parent_id', $parent_id)
            ->where('transactions.receiver_id', 0)
            ->where('transactions.status', 1)
            ->sum('amount_value');

        $data_arr = array();
        foreach ($records as $record) {
            $status = $this->getStatusText($record->transaction_status);
            $data_arr[] = array(
                "first_name" => $record->first_name != '' ? $record->first_name : '-',
                "name" => $record->name != '' ? $record->name : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                "country_name" => $record->country_name != '' ? $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number,
                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "approved_by" => $record->approved_by ? $record->approved_by : 'Not Approved',
                "created_at" => date('d M,y', strtotime($record->created_at)),
                "gimac_status" => $status ?? "",
                "remarks" => $record->remarks ? $record->remarks : '-',
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "totalAmount" => $totalAmount,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function getNotificationList(Request $request)
    {
        $lang = Session::get('locale');
        $user_id = Auth::user()->id;

        $perPage = 10;
        // Adjust the number of items per page as needed
        // Extract the page number from the request, default to 1 if not present
        $page = $request->query('page', 1);

        //$data = Notification::where('user_id', $user_id)->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $data = '';

        if ($lang == 'fr') {
            $data = Notification::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->select('notif_title_fr', 'notif_body_fr', 'created_at')  // Selecting French notification fields
                ->paginate($perPage, ['*'], 'page', $page);

        } elseif ($lang == 'en') {
            $data = Notification::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->select('notif_title', 'notif_body', 'created_at')  // Selecting English notification fields
                ->paginate($perPage, ['*'], 'page', $page);
        }

        //echo"<pre>";print_r($data);die;

        return response()->json($data);
    }

    public function rejectRequest($slug)
    {
        $decode_string = base64_decode($slug);
        $id = $this->decryptContent($decode_string);

        $user_type = Auth::user()->user_type;
        $user_id = Auth::user()->id;
        //echo"$id"; die;
        $input = Input::all();
        // echo"$user_type"; die;
        // echo"<pre>";print_r($input);
        $getTra = ExcelTransaction::where('excel_id', $id)->get();

        if (!empty($input)) {
            $reocrd = UploadedExcel::where('id', $id)->first();

            $amount = ($reocrd->totat_amount + $reocrd->total_fees);

            $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
            $getHoldBalance = User::where('id', $parent_id)->first();

            if (trim($getHoldBalance->holdAmount) >= trim($amount)) {
                if ($user_type == "Submitter") {
                    if ($reocrd->type == 0) {
                        $filename = $reocrd->excel;
                        unlink(public_path('/assets/front/excel/') . $filename);
                    }
                    UploadedExcel::where('id', $id)->delete();

                    foreach ($getTra as $key => $value) {
                        if ($value->bdastatus == "BDA") {
                            RemittanceData::where('excel_id', $value->id)->delete();
                        } elseif ($value->bdastatus == "ONAFRIQ") {
                            OnafriqaData::where('excelTransId', $value->id)->delete();
                        }
                    }
                    ExcelTransaction::where('excel_id', $id)->delete();

                    Session::put('success_message', __('message.Record has been deleted successfully'));
                    //return Redirect::to('/pending-approvals');
                }

                $status = 4;
                $errorDescription = (isset($input['remarks']) ? $input['remarks'] : "");
                if ($user_type == "Merchant") {
                    $status = 6;
                    ExcelTransaction::where('excel_id', $id)->update(['remarks' => $errorDescription, 'approved_by' => $user_id, 'approved_by_merchant' => $user_id,]);
                } else {
                    ExcelTransaction::where('excel_id', $id)->update(['remarks' => $errorDescription, 'approved_by' => $user_id, 'approver_id' => $user_id,]);
                }

                DB::table('users')->where('id', $reocrd->parent_id)->increment('wallet_balance', $amount);
                DB::table('users')->where('id', $reocrd->parent_id)->decrement('holdAmount', $amount);
                UploadedExcel::where('id', $id)->update(['status' => $status, 'approved_by' => $user_id]);
                Session::put('success_message', __('message.Record has been rejected successfully'));
            } else {
                Session::put('error_message', __('message.There was a problem to reject this record. Hold balance not remaining your wallet'));
                return Redirect::to('/pending-approvals');
            }
        } else {
            Session::put('error_message', __('message.There was a problem to reject this record.'));
        }

        if ($user_type == "Merchant") {
            return Redirect::to('/pending-approvals');
        } else {
            return Redirect::to('/pending-approvals');
        }
    }

    public function checkTransactionLimit($userType, $amount)
    {

        $user_id = Auth::user()->parent_id;
        //to check the transaction limit for current month & week & and year
        $walletlimit = Walletlimit::where('category_for', $userType)->first();

        //to check current month limit
        $currentMonthSum = Transaction::where('user_id', $user_id)->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereIn('status', [1, 2])
            ->sum('amount');

        if (($currentMonthSum + $amount) > $walletlimit->month_limit) {
            Session::put('error_message', __('message.Your monthly transfer limit has been reached.'));
            return back();
        }

        //to check current week limit
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $currentWeekSum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('amount');


        //  echo"<pre>";print_r($walletlimit->week_limit);die;
        if (($currentWeekSum + $amount) > $walletlimit->week_limit) {
            Session::put('error_message', __('Your weekly transfer limit has been reached.'));
            return back();
        }

        // Get the sum of amounts for transactions within the current day
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        $currentDaySum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('amount');
        if (($currentDaySum + $amount) > $walletlimit->daily_limit) {
            Session::put('error_message', __('Your daily transfer limit has been reached.'));
            return back();
        }
    }

    public function getWalletmanagerList(Request $request)
    {

        $input = $request->all();

        if (isset($input['lang'])) {
            Session::put('lang', $input['lang']);
        }

        if (!empty($input)) {
            $country_id = $input['country_id'];

            $walletManager = WalletManager::where('country_id', $country_id)->get();

            return view('dashboard.wallet_manager', ['walletManager' => $walletManager]);
        }
    }

    // public function singleTransfer(Request $request)
    // {
    //     $lang = Session::get('locale');
    //     // dd($lang);
    //     if (Auth::user()->user_type != "Submitter") {
    //         abort(404, 'Page not found');
    //     }
    //     $pageTitle = 'Single Transfer';
    //     $input = Input::all();
    //     if($lang == 'fr'){
    //         $country_list = Country::orderBy('name_fr', 'asc')->get();
    //     }else{
    //         $country_list = Country::orderBy('name', 'asc')->get();
    //     }
    //     if($lang == 'fr'){
    //         $wallet_manager_list = WalletManager::orderBy('name_fr', 'asc')->get();
    //     }else{
    //         $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();
    //     }
    //     //    $input=$request->all();
    //     //  echo"<pre>";print_r($input); die;
    //     if (!empty($input)) {
    //         $validate_data = [
    //             'phone' => 'required',
    //             'amount' => 'required',
    //         ];
    //         $customMessages = [
    //             'phone.required' => __('Phone number field can\'t be left blank'),
    //             'amount.required' => __('Amount field can\'t be left blank'),
    //         ];
    //         if ($_POST['option'] !== 'swap_to_swap') {
    //             // echo"hello";die;
    //             $validate_data = [
    //                 'country_id' => 'required',
    //                 'wallet_manager_id' => 'required',
    //             ];
    //             $customMessages = [
    //                 'country_id.required' => __('Country field can\'t be left blank'),
    //                 'wallet_manager_id.required' => __('Wallet manager field can\'t be left blank'),
    //             ];
    //             // $validate_data['country_id'] = 'required';
    //             // $validate_data['wallet_manager_id'] = 'required';
    //             // $customMessages['country_id.required'] = 'Country field can\'t be left blank';
    //             // $customMessages['wallet_manager_id.required'] = 'Wallet manager field can\'t be left blank';
    //         }
    //         $validator = Validator::make($input, $validate_data, $customMessages);
    //         if ($validator->fails()) {
    //             // $messages = $validator->messages();
    //             $errorMessage = $validator->errors()->first();
    //             Session::put('error_message', $errorMessage);
    //             return Redirect::to('/single-transfer');
    //         }
    //         $amount = $request->amount;
    //         $parent_id = Auth::user()->parent_id;
    //         $senderUser = $userDetail = User::where('id', $parent_id)->first();
    //         $userType = $this->getUserType($senderUser->user_type);
    //         // echo"<pre>";print_r($userType);die;
    //         if ($this->checkTransactionLimit($userType, $input['amount'])) {
    //             return $this->checkTransactionLimit($userType, $input['amount']);
    //         }
    //         $transactionLimit = TransactionLimit::where('type', $userType)->first();
    //         // echo"<pre>";print_r($transactionLimit);die;
    //         if ($senderUser->kyc_status != "completed") {
    //             $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
    //             $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
    //             if ($senderUser->kyc_status == "pending") {
    //                 if ($unverifiedKycMin > $request->amount) {
    //                     Session::put('error_message', __('The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
    //                     return Redirect::to('/single-transfer');
    //                 }
    //                 if ($unverifiedKycMax < $request->amount) {
    //                     Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is still pending.');
    //                     return Redirect::to('/single-transfer');
    //                 }
    //             } else {
    //                 if ($unverifiedKycMin > $request->amount) {
    //                     Session::put('error_message', __('The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
    //                     return Redirect::to('/single-transfer');
    //                 }
    //                 if ($unverifiedKycMax < $request->amount) {
    //                     Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is not verified.Please verify your KYC first');
    //                     return Redirect::to('/single-transfer');
    //                 }
    //             }
    //         }
    //         $total_fees = 0;
    //         if ($_POST['option'] == 'swap_to_swap') {
    //             if ($transactionLimit->minSendMoney > $request->amount) {
    //                 Session::put('error_message', __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->minSendMoney);
    //                 return Redirect::to('/single-transfer');
    //             }
    //             if ($request->amount > $transactionLimit->maxSendMoney) {
    //                 Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->maxSendMoney);
    //                 return Redirect::to('/single-transfer');
    //             }
    //             $is_receiver_exist = User::where('phone', $input['phone'])->whereNotIn('user_type', ['Approver', 'Submitter'])->count();
    //             if ($is_receiver_exist == 0) {
    //                 Session::put('error_message', __('The receiver s mobile is not registered with Swap.'));
    //                 return Redirect::to('/single-transfer');
    //             }
    //             if (isset($is_receiver_exist)) {
    //                 $total_fees = 0;
    //                 $feeapply = FeeApply::where('userId',$parent_id)->where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
    //                 ->where('max_amount', '>=',  $request->amount)->first();
    //                     // echo"<pre>";print_r($feeapply);die;
    //                 if(isset($feeapply)){
    //                     $feeType=$feeapply->fee_type;
    //                     if ($feeType == 1) {
    //                         $total_fees = $feeapply->fee_amount;
    //                     } else {
    //                         $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
    //                     }
    //                 }else{
    //                     $trans_fees = Transactionfee::where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
    //                     ->where('max_amount', '>=',  $request->amount)->first();
    //                     if (!empty($trans_fees)) {
    //                         $feeType = $trans_fees->fee_type;
    //                         if ($feeType == 1) {
    //                             $total_fees = $trans_fees->fee_amount;
    //                         } else {
    //                             $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
    //                         }
    //                     }
    //                 }
    //             }
    //         } else {
    //             if ($transactionLimit->gimacMin > $request->amount) {
    //                 Session::put('error_message', __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->gimacMin);
    //                 return Redirect::to('/single-transfer');
    //             }
    //             if ($request->amount > $transactionLimit->gimacMax) {
    //                 Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->gimacMax);
    //                 return Redirect::to('/single-transfer');
    //             }
    //             $total_fees = 0;
    //             $feeapply = FeeApply::where('userId',$parent_id)->where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $request->amount)
    //             ->where('max_amount', '>=',  $request->amount)->first();
    //             // echo"<pre>";print_r($feeapply);die;
    //             if(isset($feeapply)){
    //                 $feeType=$feeapply->fee_type;
    //                 if ($feeType == 1) {
    //                     $total_fees = $feeapply->fee_amount;
    //                 } else {
    //                     $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
    //                 }
    //             }else{
    //                 $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $request->amount)
    //                 ->where('max_amount', '>=',  $request->amount)->first();
    //                 if (!empty($trans_fees)) {
    //                     $feeType = $trans_fees->fee_type;
    //                     if ($feeType == 1) {
    //                         $total_fees = $trans_fees->fee_amount;
    //                     } else {
    //                         $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
    //                     }
    //                 }
    //             }
    //         }
    //         if ($_POST['option'] == 'swap_to_bda') {
    //             if (!empty($input)) {
    //                 $remittanceData = new RemittanceData();
    //                 $refNoLo = $this->generateUniqueReference();
    //             // Set remittance data
    //             $remittanceData->transactionId = $this->generateAndCheckUnique(); // Assuming this method exists
    //             $remittanceData->product = $request->input('product', '');
    //             $remittanceData->iban = $request->input('iban', '');
    //             $remittanceData->titleAccount = 'DEMO001'; // Hardcoded as per original
    //             $remittanceData->amount = $request->input('rupees', '');
    //             $remittanceData->partnerreference = $request->input('partnerreference', '');
    //             $remittanceData->reason = $request->input('reason', '');
    //             $remittanceData->userId = $id;
    //             $remittanceData->referenceLot = $refNoLo;
    //             $remittanceData->type = 'bank_transfer';
    //             $remittanceData->save();
    //             $refrence_id = time() . rand();
    //             $totalAmount = $input['amount'];
    //             $country_id = $request->has('country_id') ? $input['country_id'] : 0;
    //             $wallet_manager_id = $request->has('wallet_manager_id') ? $input['wallet_manager_id'] : 0;
    //             $uploadedExcel = UploadedExcel::create([
    //                 'user_id' => Auth::user()->id,
    //                 'parent_id' => Auth::user()->parent_id,
    //                 'excel' => 'Single BDA',
    //                 'reference_id' => $refrence_id,
    //                 'no_of_records' => 1,
    //                 'totat_amount' => $input['rupees'],
    //                 'type' => 1,
    //                 'total_fees' => 0,
    //                 'remarks' => $input['reason']
    //             ]);
    //         }
    //         }else{
    //             $refrence_id = time() . rand();
    //             $totalAmount = $input['amount'];
    //             $country_id = $request->has('country_id') ? $input['country_id'] : 0;
    //             $wallet_manager_id = $request->has('wallet_manager_id') ? $input['wallet_manager_id'] : 0;
    //             $uploadedExcel = UploadedExcel::create([
    //                 'user_id' => Auth::user()->id,
    //                 'parent_id' => Auth::user()->parent_id,
    //                 'excel' => 'Single',
    //                 'reference_id' => $refrence_id,
    //                 'no_of_records' => 1,
    //                 'totat_amount' => $totalAmount,
    //                 'type' => 1,
    //                 'total_fees' => $total_fees,
    //                 'remarks' => $input['comment']
    //             ]);
    //             $is_receiver_exist = User::where('phone', $input['phone'])
    //             ->whereNotIn('user_type', ['Approver', 'Submitter'])
    //             ->first();
    //             if (!empty($input['first_name']) && !empty($input['name'])) {
    //                 // Both first_name and name are filled in the input array
    //                 $first = $input['first_name'];
    //                 $name = $input['name'];
    //             } else {
    //                 // Either first_name or name or both are not filled, use name from $is_receiver_exist
    //                 $first = $is_receiver_exist ? $is_receiver_exist->name : '';
    //                 $name = '-';
    //             }
    //             ExcelTransaction::create([
    //                 'excel_id' => $uploadedExcel->id,
    //                 'parent_id' => Auth::user()->parent_id,
    //                 'submitted_by' => Auth::user()->id,
    //                 'first_name' => $first,
    //                 'name' => $name,
    //                 'comment' => $input['comment'],
    //                 'country_id' => $country_id,
    //                 'wallet_manager_id' => $wallet_manager_id,
    //                 'tel_number' =>  $input['country_code'] . $input['phone'],
    //                 'amount' => $input['amount'],
    //                 'fees' => $total_fees,
    //                 'comment' => $input['comment']
    //             ]);
    //             Session::put('success_message', __('Single transaction has been saved successfully'));
    //             return Redirect::to('/pending-approvals');
    //         }
    //     }
    //     // echo"<pre>";print_r($lang);die;
    //     return view('dashboard.single_transfer', ['title' => $pageTitle, 'country_list' => $country_list, 'wallet_manager_list' => $wallet_manager_list,'lang'=>$lang]);
    // }

    public function singleTransfer(Request $request)
    {
        $lang = Session::get('locale');
        // dd($lang);
        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }
        $input = $request->all();
        $id = Auth::user()->id;

        $pageTitle = 'Single Transfer';
        $input = Input::all();

        if ($lang == 'fr') {
            $country_list = Country::orderBy('name_fr', 'asc')->get();
        } else {
            $country_list = Country::orderBy('name', 'asc')->get();
        }

        if ($lang == 'fr') {
            $wallet_manager_list = WalletManager::orderBy('name_fr', 'asc')->get();
        } else {
            $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();
        }

        if (!empty($input)) {
            $validate_data = [
                'phone' => 'required_if:option,swap_to_swap,swap_to_gimac',
            ];

            $customMessages = [
                'phone.required' => __('message.Phone number field can\'t be left blank'),
                'amount.required' => __('message.Amount field can\'t be left blank'),
            ];

            if ($_POST['option'] == 'swap_to_gimac') {
                $validate_data = [
                    'country_id' => 'required',
                    'wallet_manager_id' => 'required',
                ];

                $customMessages = [
                    'country_id.required' => __('message.Country field can\'t be left blank'),
                    'wallet_manager_id.required' => __('message.Wallet manager field can\'t be left blank'),
                ];
            }



            if ($_POST['option'] == 'swap_to_bda') {
                $validate_data = [
                    'newBeneficiary' => ['required', 'max:50'],
                    'iban' => ['required', 'regex:/^[a-zA-Z0-9]+$/', 'min:24', 'max:30'],
                    'reason' => 'required',
                    'rupees' => 'required|numeric|min:1|max:99999999',
                ];

                $customMessages = [
                    'newBeneficiary.required' => __('message.newBeneficiary can\'t be left blank'),
                    'iban.required' => __('message.Iban field can\'t be left blank'),
                    'iban.min' => __('message.Iban min length is 24'),
                    'iban.max' => __('message.Iban max length is 30'),
                    'reason.required' => __('message.Reason field can\'t be left blank'),
                    'rupees.required' => __('message.Amount field can\'t be left blank'),
                    'rupees.min' => __('message.Amount must be at least 1'),
                    'rupees.max' => __('message.Amount maximum 99999999'),
                ];
            }
            if ($_POST['option'] == 'swap_to_onafriq') {
                $validate_data = [
                    'africamount' => 'required|numeric|min:500|max:1500000',
                    'recipientMsisdn' => 'required',
                    'recipientCountry' => 'required',
                    'recipientSurname' => 'required',
                    'recipientName' => 'required',
                    'onafriqCountryCode' => 'required',
                    'walletManager' => 'required',
                    'senderCountry' => 'required',
                    'senderMsisdn' => 'required',
                    'senderName' => 'required',
                    'senderSurname' => 'required',
                    'senderAddress' => 'required_if:recipientCountry,ML,SN',
                    'senderIdType' => 'required_if:recipientCountry,ML,SN',
                    'senderIdNumber' => 'required_if:recipientCountry,ML,SN',
                    'senderDob' => 'required_if:recipientCountry,ML,SN,BF',
                    'senderCountryCode' => 'required',
                ];

                $customMessages = [
                    'recipientCountry.required' => __('message.Recipient Country field can\'t be left blank'),
                    'recipientSurname.required' => __('message.Recipient Surname field can\'t be left blank'),
                    'recipientName.required' => __('message.Recipient First Name field can\'t be left blank'),
                    'africamount.required' => __('message.Amount field can\'t be left blank'),
                    'africamount.min' => __('message.The amount must be at least 500'),
                    'africamount.max' => __('message.The amount maximum 1500000'),
                    'recipientMsisdn.required' => __('message.Recipient Phone Number field can\'t be left blank'),
                    'onafriqCountryCode.required' => __('message.Country code field can\'t be left blank'),
                    'walletManager.required' => __('message.Wallet Manager field can\'t be left blank'),

                    'senderCountry.required' => __('message.Sender Country field can\'t be left blank'),
                    'senderMsisdn.required' => __('message.Sender Phone Number field can\'t be left blank'),
                    'senderName.required' => __('message.Sender Name field can\'t be left blank'),
                    'senderSurname.required' => __('message.Sender Surname field can\'t be left blank'),
                    'senderAddress.required' => __('message.Sender Address field can\'t be left blank'),
                    'senderIdType.required' => __('message.Sender Id Type field can\'t be left blank'),
                    'senderIdNumber.required' => __('message.Sender Id Number field can\'t be left blank'),
                    'senderDob.required' => __('message.Sender Dob field can\'t be left blank'),
                ];
            }

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                // $messages = $validator->messages();
                $errorMessage = $validator->errors()->first();
                Session::put('error_message', $errorMessage);
                return Redirect::to('/single-transfer');
            }
            $amount = $request->amount;
            $rupees = $request->rupees;
            $africamount = $request->africamount;


            if ($_POST['option'] == "swap_to_swap") {
                $amount = $request->amount;
            } elseif ($_POST['option'] == "swap_to_bda") {
                $amount = $request->rupees;
            } elseif ($_POST['option'] == "swap_to_gimac") {
                $amount = $request->amount;
            } elseif ($_POST['option'] == "swap_to_onafriq") {
                $amount = $request->africamount;
            }

            $parent_id = Auth::user()->parent_id;

            $senderUser = $userDetail = User::where('id', $parent_id)->first();

            $userType = $this->getUserType($senderUser->user_type);
            $walletbalance = $senderUser->wallet_balance;
            // echo"<pre>";print_r($userType);die;

            if ($this->checkTransactionLimit($userType, $amount)) {
                return $this->checkTransactionLimit($userType, $amount);
            }

            $transactionLimit = TransactionLimit::where('type', $userType)->first();

            // echo"<pre>";print_r($transactionLimit);die;
            if ($senderUser->kyc_status != "completed") {
                $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
                $unverifiedKycMax = $transactionLimit->unverifiedKycMax;

                /* if ($_POST['option'] == "swap_to_swap") {
                    $amount = $request->amount;
                } elseif ($_POST['option'] == "swap_to_bda") {
                    $amount = $request->rupees;
                } elseif ($_POST['option'] == "swap_to_gimac") {
                    $amount = $request->amount;
                } elseif ($_POST['option'] == "swap_to_onafriq") {
                    $amount = $request->africamount;
                } */

                if ($senderUser->kyc_status == "pending") {
                    if ($unverifiedKycMin > $amount) {
                        Session::put('error_message', __('message.The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
                        return Redirect::to('/single-transfer');
                    }

                    if ($unverifiedKycMax < $amount) {
                        Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is still pending.');
                        return Redirect::to('/single-transfer');
                    }
                } else {

                    if ($unverifiedKycMin > $amount) {
                        Session::put('error_message', __('message.The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
                        return Redirect::to('/single-transfer');
                    }

                    if ($unverifiedKycMax < $amount) {
                        Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is not verified.Please verify your KYC first');
                        return Redirect::to('/single-transfer');
                    }
                }
            }


            $total_fees = 0;
            if ($_POST['option'] == 'swap_to_swap') {

                if ($transactionLimit->minSendMoney > $request->amount) {
                    Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->minSendMoney);
                    return Redirect::to('/single-transfer');
                }

                if ($request->amount > $transactionLimit->maxSendMoney) {
                    Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->maxSendMoney);
                    return Redirect::to('/single-transfer');
                }


                $is_receiver_exist = User::where('phone', $input['phone'])->whereNotIn('user_type', ['Approver', 'Submitter'])->count();

                if ($is_receiver_exist == 0) {
                    Session::put('error_message', __('message.The receiver s mobile is not registered with Swap.'));
                    return Redirect::to('/single-transfer');
                }
                if (isset($is_receiver_exist)) {

                    $total_fees = $this->calculateFees($request->amount, $parent_id, 'Send Money');

                    if ($walletbalance < ($request->amount + $total_fees)) {
                        Session::put('error_message', __('message.Not sufficient balance in wallet'));
                        return redirect('/single-transfer');
                    }
                }
            } elseif ($_POST['option'] == 'swap_to_bda') {
                if ($transactionLimit->bdaMin > $request->rupees) {
                    Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->bdaMin);
                    return Redirect::to('/single-transfer');
                }

                if ($request->rupees > $transactionLimit->bdaMax) {
                    Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->bdaMax);
                    return Redirect::to('/single-transfer');
                }

                $total_fees = $this->calculateFees($request->rupees, $parent_id, 'Money Transfer Via BDA');

                if ($walletbalance < ($request->rupees + $total_fees)) {
                    Session::put('error_message', __('message.Not sufficient balance in wallet'));
                    return redirect('/single-transfer');
                }
            } elseif ($_POST['option'] == 'swap_to_onafriq') {
                if ($transactionLimit->onafriqa_min > $request->africamount) {
                    Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->onafriqa_min);
                    return Redirect::to('/single-transfer');
                }

                if ($request->africamount > $transactionLimit->onafriqa_max) {
                    Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->onafriqa_max);
                    return Redirect::to('/single-transfer');
                }

                $total_fees = $this->calculateFees($request->africamount, $parent_id, 'Money Transfer Via ONAFRIQ');

                if ($walletbalance < ($request->africamount + $total_fees)) {
                    Session::put('error_message', __('message.Not sufficient balance in wallet'));
                    return redirect('/single-transfer');
                }
            } else {

                if ($transactionLimit->gimacMin > $request->amount) {
                    Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->gimacMin);
                    return Redirect::to('/single-transfer');
                }

                if ($request->amount > $transactionLimit->gimacMax) {
                    Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->gimacMax);
                    return Redirect::to('/single-transfer');
                }

                $total_fees = $this->calculateFees($request->amount, $parent_id, 'Money Transfer Via GIMAC');

                if ($walletbalance < ($request->amount + $total_fees)) {
                    Session::put('error_message', __('message.Not sufficient balance in wallet'));
                    return redirect('/single-transfer');
                }
            }

            /* if ($walletbalance < ($request->amount + $trans_fees)) {
              Session::put('error_message', __('Not sufficient balance in wallet'));
              return redirect('/single-transfer');
          } */

            $opening_balance_sender = $senderUser->wallet_balance;
            if ($_POST['option'] == 'swap_to_bda') {
                if (!empty($input)) {



                    $refrence_id = time() . rand();
                    $totalAmount = $amount;
                    $country_id = $request->has('country_id') ? $input['country_id'] : 0;
                    $wallet_manager_id = $request->has('wallet_manager_id') ? $input['wallet_manager_id'] : 0;
                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => Auth::user()->id,
                        'parent_id' => Auth::user()->parent_id,
                        'excel' => 'Single BDA',
                        'reference_id' => $refrence_id,
                        'no_of_records' => 1,
                        'totat_amount' => $input['rupees'],
                        'type' => 1,
                        'total_fees' => $total_fees,
                        'remarks' => $input['reason']
                    ]);

                    $excelTrans = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'first_name' => '',
                        'name' => '',
                        'country_id' => '',
                        'wallet_manager_id' => '',
                        'tel_number' => '',
                        'amount' => $input['rupees'],
                        'fees' => $total_fees,
                        'comment' => $input['reason'],
                        'bdastatus' => 'BDA'
                    ]);

                    $remittanceData = new RemittanceData();
                    //                    $refNoLo = $this->generateUniqueReference();

                    $getLstNo = RemittanceData::orderBy('id', 'desc')->select('referenceLot')->first();
                    if (empty($getLstNo)) {
                        $refNoLo = 'SWAP9999';
                    } else {
                        preg_match('/([a-zA-Z]+)([0-9]+)/', $getLstNo->referenceLot, $matches);
                        $incrementedPart = (int) $matches[2] + 1;
                        $newReferenceLot = $matches[1] . $incrementedPart;
                        $refNoLo = $newReferenceLot;
                    }
                    $remittanceData->transactionId = $this->generateAndCheckUnique();
                    $remittanceData->product = 'SWAP';
                    $remittanceData->iban = $request->input('iban', '');
                    $remittanceData->titleAccount = $request->input('newBeneficiary', '');
                    $remittanceData->amount = $request->input('rupees', '');
                    $remittanceData->partnerreference = $this->generateString();
                    $remittanceData->reason = $request->input('reason', '');
                    $remittanceData->userId = $id;
                    $remittanceData->referenceLot = $refNoLo;
                    $remittanceData->type = 'bank_transfer';
                    $remittanceData->excel_id = $excelTrans->id;
                    $remittanceData->save();
                    $sender_wallet_amount = $senderUser->wallet_balance - ($input['rupees'] + $total_fees);
                    $sender_hold_amount = $senderUser->holdAmount + ($input['rupees'] + $total_fees);
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);

                    $transId = $this->createTransaction(Auth::user()->parent_id, '', '', $input['rupees'], $total_fees, $excelTrans->id, $remittanceData->id, 'SWAPTOBDA');
                    //$this->createTransaction(Auth::user()->parent_id, $receiver_id, $receiver_mobile, $amount, $total_fees, $excelId, $status, $bdaStatus, $billingRefrence, $tomember, $acquirertrxref, $issuertrxref, $vouchercode, $onafriq_bda_ids, $type);
                    $this->transLadger($parent_id, $senderUser->wallet_balance, $amount, $total_fees, '2', $excelTrans->id, $transId);

                    Session::put('success_message', __('message.Single transaction has been saved successfully'));
                    return Redirect::to('/pending-approvals');
                }
            } else if ($_POST['option'] == 'swap_to_onafriq') {

                if (!empty($input)) {

                    $refrence_id = time() . rand();
                    $totalAmount = $amount;

                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => Auth::user()->id,
                        'parent_id' => Auth::user()->parent_id,
                        'excel' => 'Single Onafriq',
                        'reference_id' => $refrence_id,
                        'no_of_records' => 1,
                        'totat_amount' => $totalAmount,
                        'type' => 1,
                        'total_fees' => $total_fees,
                        'remarks' => ''
                    ]);

                    $excelT = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'first_name' => '',
                        'name' => '',
                        'country_id' => '',
                        'wallet_manager_id' => '',
                        'tel_number' => '',
                        'amount' => $totalAmount,
                        'fees' => $total_fees,
                        'comment' => '',
                        'bdastatus' => 'ONAFRIQ'
                    ]);

                    $recipientCurrency = "";
                    if ($request->input('recipientCountry') == 'GA') {
                        $recipientCurrency = 'XAF';
                    } else {
                        $recipientCurrency = 'XOF';
                    }


                    $onafriqaDataA = new OnafriqaData();
                    $onafriqaDataA->amount = $request->input('africamount', '');
                    $onafriqaDataA->recipientMsisdn = $request->input('onafriqCountryCode', '') . $request->input('recipientMsisdn', '');
                    $onafriqaDataA->walletManager = $request->input('walletManager', '');
                    $onafriqaDataA->recipientCountry = $request->input('recipientCountry', '');
                    $onafriqaDataA->recipientSurname = $request->input('recipientSurname', '');
                    $onafriqaDataA->recipientName = $request->input('recipientName', '');

                    $onafriqaDataA->senderCountry = $request->input('senderCountry', '');
                    $onafriqaDataA->senderMsisdn = $request->input('senderCountryCode', '') . $request->input('senderMsisdn', '');
                    $onafriqaDataA->senderName = $request->input('senderName', '');
                    $onafriqaDataA->senderSurname = $request->input('senderSurname', '');
                    $onafriqaDataA->senderAddress = $request->input('senderAddress', '');
                    $onafriqaDataA->senderDob = $request->input('senderDob', '');
                    $onafriqaDataA->senderIdType = $request->input('senderIdType', '');
                    $onafriqaDataA->senderIdNumber = $request->input('senderIdNumber', '');

                    $onafriqaDataA->recipientCurrency = $recipientCurrency;
                    $onafriqaDataA->thirdPartyTransactionId = $this->generateAndCheckUnique();
                    $onafriqaDataA->status = 'pending';
                    $onafriqaDataA->excelTransId = $excelT->id;
                    $onafriqaDataA->userId = Auth::user()->parent_id;
                    $onafriqaDataA->fromMSISDN = $request->input('senderCountryCode', '') . $request->input('senderMsisdn', '');
                    $onafriqaDataA->save();

                    $sender_wallet_amount = $senderUser->wallet_balance - ($input['africamount'] + $total_fees);
                    $sender_hold_amount = $senderUser->holdAmount + ($input['africamount'] + $total_fees);
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);
                    $transId = $this->createTransaction(Auth::user()->parent_id, '', '', $amount, $total_fees, $excelT->id, $onafriqaDataA->id, 'SWAPTOONAFRIQ');
                    $this->transLadger($parent_id, $senderUser->wallet_balance, $amount, $total_fees, '2', $excelT->id, $transId);
                    Session::put('success_message', __('message.Single transaction has been saved successfully'));
                    return Redirect::to('/pending-approvals');
                }
            } else {
                $refrence_id = time() . rand();
                $totalAmount = $input['amount'];
                $country_id = $request->has('country_id') ? $input['country_id'] : 0;
                $wallet_manager_id = $request->has('wallet_manager_id') ? $input['wallet_manager_id'] : 0;
                $uploadedExcel = UploadedExcel::create([
                    'user_id' => Auth::user()->id,
                    'parent_id' => Auth::user()->parent_id,
                    'excel' => 'Single',
                    'reference_id' => $refrence_id,
                    'no_of_records' => 1,
                    'totat_amount' => $totalAmount,
                    'type' => 1,
                    'total_fees' => $total_fees,
                    'remarks' => $input['comment']
                ]);

                $sender_wallet_amount = $senderUser->wallet_balance - ($totalAmount + $total_fees);
                $sender_hold_amount = $senderUser->holdAmount + ($totalAmount + $total_fees);
                User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);
            }

            $is_receiver_exist = User::where('phone', $input['phone'])
                ->whereNotIn('user_type', ['Approver', 'Submitter'])
                ->first();

            if (!empty($input['first_name']) && !empty($input['name'])) {
                $first = $input['first_name'];
                $name = $input['name'];
            } else {
                $first = $is_receiver_exist ? $is_receiver_exist->name : '';
                $name = '-';
            }

            $excelTr = ExcelTransaction::create([
                'excel_id' => $uploadedExcel->id,
                'parent_id' => Auth::user()->parent_id,
                'submitted_by' => Auth::user()->id,
                'first_name' => $first,
                'name' => $name,
                'comment' => $input['comment'],
                'country_id' => $country_id,
                'wallet_manager_id' => $wallet_manager_id,
                //'tel_number' => $input['country_code'] . ' ' . $input['phone'],
                'tel_number' => $input['phone'],
                'amount' => $input['amount'],
                'fees' => $total_fees,
                'bdastatus' => ($input['option'] == "swap_to_swap" ? "SWAPTOSWAP" : "GIMAC")
            ]);

            $transId = $this->createTransaction(Auth::user()->parent_id, $input['option'] == "swap_to_swap" ? $is_receiver_exist->id : 0, $input['option'] != "swap_to_swap" ? $input['phone'] : null, $amount, $total_fees, $excelTr->id, 0, ($input['option'] == "swap_to_swap" ? "SWAPTOSWAP" : "SWAPTOGIMAC"));
            $this->transLadger($parent_id, $senderUser->wallet_balance, $amount, $total_fees, '2', $excelTr->id, $transId);

            Session::put('success_message', __('message.Single transaction has been saved successfully'));
            return Redirect::to('/pending-approvals');
        }
        // echo"<pre>";print_r($lang);die;
        $countryList = Currency::all();
        $getPrevDetail = OnafriqaData::where('userId', Auth::user()->parent_id)->orderBy('id', 'desc')->first();
        if (empty($getPrevDetail)) {
            $getPrevDetail = "";
        }
        return view('dashboard.single_transfer', ['title' => $pageTitle, 'country_list' => $country_list, 'wallet_manager_list' => $wallet_manager_list, 'lang' => $lang, 'countryList' => $countryList, 'getPrevDetail' => $getPrevDetail]);
    }

    function generateString()
    {
        // Generate components
        $part1 = rand(1000, 9999); // Random 4-digit number
        $part2 = 'PR'; // Fixed string
        $part3 = rand(10000, 99999); // Random 5-digit number

        // Combine parts
        return $part1 . $part2 . $part3;
    }


    /*  public function bulkTransfer(Request $request)
    {


        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }

        $pageTitle = 'Bulk Transfer';
        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'excel_file' => 'required|mimes:xlsx,xls',
            ];

            $customMessages = [
                'excel_file.required' => __('Please upload an excel file'),
                'excel_file.mimes' => __('Excel file should be in xlsx,xls'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                Session::put('error_message', $messages);
                return Redirect::to('/bulk-transfer');
            }
            $file = $request->file('excel_file');
            $image = $this->uploadImage($file, 'public/assets/front/excel/');
            $path = 'public/assets/front/excel/' . $image;
            $import = new ExcelImport;
            Excel::import($import, $path);
            $totalAmount = $import->getTotalAmount();

            $errors = $import->getErrors();
            $errorCount = $import->getErrorCount();
            $duplicateNumbers = $import->getDuplicateNumbers();
            $responseMessage = '';
            $responseType = '';

            if (!empty($errors)) {
                unlink($path);
                $errorMessage = '';
                foreach ($errors as $error) {
                    $rowNumber = $error['row'];
                    $errorMessages = implode(', ', $error['errors']);
                    $errorMessage .= "Row $rowNumber: $errorMessages\n";
                }
                Session::put('error_message', $errorMessage);
                return Redirect::to('/bulk-transfer');
            }

            if ($errorCount > 0) {
                $responseMessage = $errorCount . ' ' . __('records not transferred due to errors.');
                $responseType = __('error_message');
            } else {

                $dupNo = !empty($duplicateNumbers) ? __(" Duplicate numbers found: " . implode(', ', $duplicateNumbers) . '.') : '';
                $responseMessage = __('Excel has been uploaded successfully ' . $dupNo);
                $responseType = __('success_message');
            }
            // echo"<pre>";print_r($totalAmount);die;

            $parent_id = Auth::user()->parent_id;
            $senderUser = User::where('id', $parent_id)->first();
            $userType = $this->getUserType($senderUser->user_type);
            $transactionLimit = TransactionLimit::where('type', $userType)->first();

            if ($transactionLimit->bulkMin > $totalAmount) {
                unlink($path);
                Session::put('error_message', __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->bulkMin);
                return Redirect::to('/bulk-transfer');
            }

            if ($totalAmount > $transactionLimit->bulkMax) {
                unlink($path);
                Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->bulkMax);
                return Redirect::to('/bulk-transfer');
            }

            $excelData = $import->getCollectedData();
            $rowCount = count($excelData);
            $refrence_id = time() . rand();
            $header_array = ['First name', 'Name', 'Comment', 'Country', 'Wallet Manager', 'Tel number', 'Amount'];
            $header_row = $import->onFirstRecord();
            if ($rowCount <= 0) {
                Session::put('error_message', __('You cannot upload empty file'));
                return Redirect::to('/bulk-transfer');
            }

            if (array_diff($header_array, $header_row) === array()) {


                $invalidNumbers = [];
                $fees_arr = array();
                foreach ($excelData as $data) {

                    $is_receiver_exist = null;

                    // Only check if both country and wallet_manager are not empty
                    if (empty($data['country']) && empty($data['wallet_manager'])) {
                        $is_receiver_exist = User::where('phone', $data['tel_number'])
                            ->whereNotIn('user_type', ['Approver', 'Submitter'])
                            ->first();
                    }

                    // If user does not exist, add the number to invalidNumbers
                    if (empty($data['country']) && empty($data['wallet_manager']) && empty($is_receiver_exist)) {
                        $invalidNumbers[] = $data['tel_number'];
                    }


                    $is_receiver_exist = User::where('phone', $data['tel_number'])->whereNotIn('user_type', ['Approver', 'Submitter'])->first();

                    $total_fees = 0;

                    $trans_type = $is_receiver_exist ? 'Send Money' : 'Money Transfer Via GIMAC';

                    $feeapply = FeeApply::where('userId', $parent_id)->where('transaction_type', $trans_type)->where('min_amount', '<=', $data['amount'])
                        ->where('max_amount', '>=', $data['amount'])->first();
                    // echo"<pre>";print_r($feeapply);die;

                    if (isset($feeapply)) {

                        $feeType = $feeapply->fee_type;
                        if ($feeType == 1) {
                            $total_fees = $feeapply->fee_amount;
                        } else {
                            $total_fees = number_format(($data['amount'] * $feeapply->fee_amount / 100), 2, '.', '');
                        }
                        $fees_arr[] = $total_fees;
                    } else {
                        $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $data['amount'])
                            ->where('max_amount', '>=', $data['amount'])->first();
                        if (!empty($trans_fees)) {
                            $feeType = $trans_fees->fee_type;
                            if ($feeType == 1) {
                                $total_fees = $trans_fees->fee_amount;
                            } else {
                                $total_fees = number_format(($data['amount'] * $trans_fees->fee_amount / 100), 2, '.', '');
                            }
                            $fees_arr[] = $total_fees;
                        }
                    }
                }

                //                if (!empty($invalidNumbers)) {
                //                    unlink($path);
                //                    Session::put('error_message', __('The following numbers do not match any user records: ') . implode(', ', $invalidNumbers));
                //                    return Redirect::to('/bulk-transfer');
                //                }




                $totalAmtWithFee = ($totalAmount + array_sum($fees_arr));
                if ($senderUser->wallet_balance <= $totalAmtWithFee) {
                    unlink($path);
                    Session::put('error_message', 'Insufficient Balance !');
                    return Redirect::to('/bulk-transfer');
                }

                $uploadedExcel = UploadedExcel::create([
                    'user_id' => Auth::user()->id,
                    'parent_id' => Auth::user()->parent_id,
                    'excel' => $image,
                    'reference_id' => $refrence_id,
                    'no_of_records' => $rowCount,
                    'totat_amount' => $totalAmount,
                    'remarks' => $input['remarks'],
                ]);

                foreach ($excelData as $data) {
                    $country_id = 0;
                    if ($data['country'] != '') {
                        $is_country_exist = Country::where('name', 'like', '%' . $data['country'] . '%')->first();
                        if ($is_country_exist) {
                            $country_id = $is_country_exist->id;
                        }
                    }

                    $wallet_manager_id = 0;

                    if ($data['wallet_manager'] != '') {
                        $is_wallet_manager_exist = WalletManager::where('name', 'like', '%' . $data['wallet_manager'] . '%')->where('country_id', $country_id)->first();
                        if ($is_wallet_manager_exist) {
                            $wallet_manager_id = $is_wallet_manager_exist->id;
                        }
                    }

                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmtWithFee;
                    $sender_hold_amount = $senderUser->holdAmount + $totalAmtWithFee;
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);



                    //$userExistOrNot = User::where('phone',$data['tel_number'])->first();

                    // if(!empty($userExistOrNot)){
                       // $bdaStatus = 'SWAPTOSWAP';
                    // }else{
                        ///$bdaStatus = 'GIMAC';
                    }

                    $total_fees = 0;

                    $trans_type = $is_receiver_exist ? 'Send Money' : 'Money Transfer Via GIMAC';

                    $feeapply = FeeApply::where('userId', $parent_id)->where('transaction_type', $trans_type)->where('min_amount', '<=', $data['amount'])
                        ->where('max_amount', '>=', $data['amount'])->first();
                    // echo"<pre>";print_r($feeapply);die;

                    if (isset($feeapply)) {

                        $feeType = $feeapply->fee_type;
                        if ($feeType == 1) {
                            $total_fees = $feeapply->fee_amount;
                        } else {
                            $total_fees = number_format(($data['amount'] * $feeapply->fee_amount / 100), 2, '.', '');
                        } 
                    } else {
                        $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $data['amount'])
                            ->where('max_amount', '>=', $data['amount'])->first();
                        if (!empty($trans_fees)) {
                            $feeType = $trans_fees->fee_type;
                            if ($feeType == 1) {
                                $total_fees = $trans_fees->fee_amount;
                            } else {
                                $total_fees = number_format(($data['amount'] * $trans_fees->fee_amount / 100), 2, '.', '');
                            } 
                        }
                    }

                    ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'first_name' => $data['first_name'],
                        'name' => $data['name'],
                        'comment' => $data['comment'],
                        'country_id' => $country_id,
                        'wallet_manager_id' => $wallet_manager_id,
                        'tel_number' => $data['tel_number'],
                        'amount' => $data['amount'],
                        'fees' => $total_fees,
                        'bdastatus' => 'GIMAC',
                    ]);
                    // $total_fees_amount = array_sum($fees_arr);
                    //   echo"<pre>";print_r($fees_arr);die;
                }

                if (!empty($fees_arr)) {
                    $total_fees_amount = array_sum($fees_arr);
                    UploadedExcel::where('id', $uploadedExcel->id)->update(['total_fees' => $total_fees_amount]);
                }

                Session::put($responseType, $responseMessage);
                return Redirect::to('/pending-approvals');
            } else {
                Session::put('error_message', __('You cannot change the header row'));
            }


            return Redirect::to('/bulk-transfer');
        }
        return view('dashboard.bulk_transfer', ['title' => $pageTitle]);
    } */


    public function bulkTransferSwap(Request $request)
    {
        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }


        $lang = Session::get('locale');

        $pageTitle = 'Bulk Transfer';
        $input = Input::all();


        if (!empty($input)) {

            $validate_data = [
                'excel_file' => 'required|mimes:xlsx,xls',
            ];

            $customMessages = [
                'excel_file.required' => __('message.Please upload an excel file'),
                'excel_file.mimes' => __('message.Excel file should be in xlsx,xls'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                Session::put('error_message', $messages);
                return Redirect::to('/bulk-transfer');
            }
            $file = $request->file('excel_file');
            $image = $this->uploadImage($file, 'public/assets/front/excel/');
            $path = 'public/assets/front/excel/' . $image;
            $import = new ExcelImport($input['option']);
            Excel::import($import, $path);
            $totalAmount = $import->getTotalAmount();

            $errors = $import->getErrors();
            $errorCount = $import->getErrorCount();
            $duplicateNumbers = $import->getDuplicateNumbers();
            $responseMessage = '';
            $responseType = '';

            if (!empty($errors)) {
                unlink($path);
                $errorMessage = '';
                foreach ($errors as $error) {
                    $rowNumber = $error['row'];
                    $errorMessages = implode(', ', $error['errors']);
                    $errorMessage .= __('message.Row') . "$rowNumber: $errorMessages\n";
                }
                Session::put('error_message', $errorMessage);
                return Redirect::to('/bulk-transfer');
            }


            if ($errorCount > 0) {
                $responseMessage = $errorCount . ' ' . __('message.records not transferred due to errors.');
                $responseType = __('error_message');
            } else {

                $dupNo = !empty($duplicateNumbers) ? __('message.Duplicate numbers found:') . implode(', ', $duplicateNumbers) . '.' : '';
                $responseMessage = __('message.Excel has been uploaded successfully') . $dupNo;
                $responseType = 'success_message';
            }



            $parent_id = Auth::user()->parent_id;
            $senderUser = User::where('id', $parent_id)->first();
            $userType = $this->getUserType($senderUser->user_type);
            $transactionLimit = TransactionLimit::where('type', $userType)->first();

            if ($transactionLimit->bulkMin > $totalAmount) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->bulkMin);
                return Redirect::to('/bulk-transfer');
            }

            if ($totalAmount > $transactionLimit->bulkMax) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->bulkMax);
                return Redirect::to('/bulk-transfer');
            }

            $excelData = $import->getCollectedData();
            $rowCount = count($excelData);
            $refrence_id = time() . rand();
            $header_array = ['Comment', 'Tel number', 'Amount'];
            $header_row = $import->onFirstRecord();
            if ($rowCount <= 0) {
                Session::put('error_message', __('message.You cannot upload empty file'));
                return Redirect::to('/bulk-transfer');
            }

            if (array_diff($header_array, $header_row) === array()) {
                $invalidNumbers = [];
                $fees_arr = array();
                foreach ($excelData as $data) {

                    $is_receiver_exist = User::where('phone', $data['tel_number'])->whereNotIn('user_type', ['Approver', 'Submitter'])->first();
                    if (empty($is_receiver_exist)) {
                        $invalidNumbers[] = $data['tel_number'];
                    }
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Send Money');
                    $fees_arr[] = $total_fees;
                }

                if (!empty($invalidNumbers)) {
                    unlink($path);
                    Session::put('error_message', __('message.The following numbers do not match any user records:') . implode(', ', $invalidNumbers));
                    return Redirect::to('/bulk-transfer');
                }

                $totalAmtWithFee = ($totalAmount + array_sum($fees_arr));
                if ($senderUser->wallet_balance <= $totalAmtWithFee) {
                    unlink($path);
                    Session::put('error_message', __('message.Insufficient Balance !'));
                    return Redirect::to('/bulk-transfer');
                }

                $uploadedExcel = UploadedExcel::create([
                    'user_id' => Auth::user()->id,
                    'parent_id' => Auth::user()->parent_id,
                    'excel' => $image,
                    'reference_id' => $refrence_id,
                    'no_of_records' => $rowCount,
                    'totat_amount' => $totalAmount,
                    'remarks' => $input['remarks'],
                ]);
                $currentWalletBalance = $senderUser->wallet_balance;
                foreach ($excelData as $data) {
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Send Money');

                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmtWithFee;
                    $sender_hold_amount = $senderUser->holdAmount + $totalAmtWithFee;
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);

                    $excelTr = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'comment' => $data['comment'],
                        'country_id' => 0,
                        'wallet_manager_id' => 0,
                        'tel_number' => $data['tel_number'],
                        'amount' => $data['amount'],
                        'fees' => $total_fees,
                        'bdastatus' => 'SWAPTOSWAP',
                    ]);
                    $getReceiverId = User::where('phone', $data['tel_number'])->first();
                    $transId = $this->createTransaction(Auth::user()->parent_id, $getReceiverId->id, null, $data['amount'], $total_fees, $excelTr->id, 0, "SWAPTOSWAP");
                    $this->transLadger($parent_id, $currentWalletBalance, $data['amount'], $total_fees, '2', $excelTr->id, $transId);
                    $currentWalletBalance -= $data['amount'] + $total_fees;
                }

                if (!empty($fees_arr)) {
                    $total_fees_amount = array_sum($fees_arr);
                    UploadedExcel::where('id', $uploadedExcel->id)->update(['total_fees' => $total_fees_amount]);
                }

                // echo '<pre>';print_r($responseMessage);exit;
                Session::put($responseType, $responseMessage);
                return Redirect::to('/pending-approvals');
            } else {
                Session::put('error_message', __('message.You cannot change the header row'));
            }
            return Redirect::to('/bulk-transfer');
        }
        return view('dashboard.bulk_transfer', ['title' => $pageTitle]);
    }

    public function bulkTransferGimac(Request $request)
    {
        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }

        $pageTitle = 'Bulk Transfer';
        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'excel_file' => 'required|mimes:xlsx,xls',
            ];

            $customMessages = [
                'excel_file.required' => __('message.Please upload an excel file'),
                'excel_file.mimes' => __('message.Excel file should be in xlsx,xls'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                Session::put('error_message', $messages);
                return Redirect::to('/bulk-transfer');
            }
            $file = $request->file('excel_file');
            $image = $this->uploadImage($file, 'public/assets/front/excel/');
            $path = 'public/assets/front/excel/' . $image;
            //$import = new ExcelImportGimac;
            $import = new ExcelImport($input['option']);
            Excel::import($import, $path);
            $totalAmount = $import->getTotalAmount();

            $errors = $import->getErrors();
            $errorCount = $import->getErrorCount();
            $duplicateNumbers = $import->getDuplicateNumbers();
            $responseMessage = '';
            $responseType = '';

            if (!empty($errors)) {
                unlink($path);
                $errorMessage = '';
                foreach ($errors as $error) {
                    $rowNumber = $error['row'];
                    $errorMessages = implode(', ', $error['errors']);
                    $errorMessage .= __('message.Row') . "$rowNumber: $errorMessages\n";
                }
                Session::put('error_message', $errorMessage);
                return Redirect::to('/bulk-transfer');
            }

            if ($errorCount > 0) {
                $responseMessage = $errorCount . ' ' . __('message.records not transferred due to errors.');
                $responseType = __('error_message');
                /* Session::flash('error_message', $responseMessage);
                return Redirect::to('/bulk-transfer'); */
            } else {

                $dupNo = !empty($duplicateNumbers) ? __('message.Duplicate numbers found:') . implode(', ', $duplicateNumbers) . '.' : '';
                $responseMessage = __('message.Excel has been uploaded successfully') . $dupNo;
                $responseType = __('success_message');
            }
            // echo"<pre>";print_r($totalAmount);die;

            $parent_id = Auth::user()->parent_id;
            $senderUser = User::where('id', $parent_id)->first();
            $userType = $this->getUserType($senderUser->user_type);
            $transactionLimit = TransactionLimit::where('type', $userType)->first();

            if ($transactionLimit->bulkMin > $totalAmount) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->bulkMin);
                return Redirect::to('/bulk-transfer');
            }

            if ($totalAmount > $transactionLimit->bulkMax) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->bulkMax);
                return Redirect::to('/bulk-transfer');
            }

            $excelData = $import->getCollectedData();
            $rowCount = count($excelData);
            $refrence_id = time() . rand();
            $header_array = ['First name', 'Name', 'Comment', 'Country', 'Wallet Manager', 'Tel number', 'Amount'];
            $header_row = $import->onFirstRecord();
            if ($rowCount <= 0) {
                Session::put('error_message', __('message.You cannot upload empty file'));
                return Redirect::to('/bulk-transfer');
            }

            if (array_diff($header_array, $header_row) === array()) {

                foreach ($excelData as $data) {
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Money Transfer Via GIMAC');
                    $fees_arr[] = $total_fees; // Store calculated fee
                }

                $totalAmtWithFee = ($totalAmount + array_sum($fees_arr));
                if ($senderUser->wallet_balance <= $totalAmtWithFee) {
                    unlink($path);
                    Session::put('error_message', __('message.Insufficient Balance !'));
                    return Redirect::to('/bulk-transfer');
                }

                $uploadedExcel = UploadedExcel::create([
                    'user_id' => Auth::user()->id,
                    'parent_id' => Auth::user()->parent_id,
                    'excel' => $image,
                    'reference_id' => $refrence_id,
                    'no_of_records' => $rowCount,
                    'totat_amount' => $totalAmount,
                    'remarks' => $input['remarks'],
                ]);
                $currentWalletBalance = $senderUser->wallet_balance;
                foreach ($excelData as $data) {
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Money Transfer Via GIMAC');
                    $country_id = 0;
                    if ($data['country'] != '') {
                        $is_country_exist = Country::where('name', 'like', '%' . $data['country'] . '%')->first();
                        if ($is_country_exist) {
                            $country_id = $is_country_exist->id;
                        }
                    }

                    $wallet_manager_id = 0;

                    if ($data['wallet_manager'] != '') {
                        $is_wallet_manager_exist = WalletManager::where('name', 'like', '%' . $data['wallet_manager'] . '%')->where('country_id', $country_id)->first();
                        if ($is_wallet_manager_exist) {
                            $wallet_manager_id = $is_wallet_manager_exist->id;
                        }
                    }

                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmtWithFee;
                    $sender_hold_amount = $senderUser->holdAmount + $totalAmtWithFee;
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);

                    $excelTr = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'first_name' => $data['first_name'],
                        'name' => $data['name'],
                        'comment' => $data['comment'],
                        'country_id' => $country_id,
                        'wallet_manager_id' => $wallet_manager_id,
                        'tel_number' => $data['tel_number'],
                        'amount' => $data['amount'],
                        'fees' => $total_fees,
                        'bdastatus' => 'GIMAC',
                    ]);

                    $transId = $this->createTransaction(Auth::user()->parent_id, 0, $data['tel_number'], $data['amount'], $total_fees, $excelTr->id, 0, "SWAPTOGIMAC");
                    $this->transLadger($parent_id, $currentWalletBalance, $data['amount'], $total_fees, '2', $excelTr->id, $transId);
                    $currentWalletBalance -= $data['amount'] + $total_fees;
                }

                if (!empty($fees_arr)) {
                    $total_fees_amount = array_sum($fees_arr);
                    UploadedExcel::where('id', $uploadedExcel->id)->update(['total_fees' => $total_fees_amount]);
                }

                Session::put($responseType, $responseMessage);
                return Redirect::to('/pending-approvals');
            } else {
                Session::put('error_message', __('message.You cannot change the header row'));
            }


            return Redirect::to('/bulk-transfer');
        }
        return view('dashboard.bulk_transfer', ['title' => $pageTitle]);
    }

    public function bulkTransferBda(Request $request)
    {
        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }

        $pageTitle = 'Bulk Transfer';
        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'excel_file' => 'required|mimes:xlsx,xls',
            ];

            $customMessages = [
                'excel_file.required' => __('message.Please upload an excel file'),
                'excel_file.mimes' => __('message.Excel file should be in xlsx,xls'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                Session::put('error_message', $messages);
                return Redirect::to('/bulk-transfer');
            }
            $file = $request->file('excel_file');
            $image = $this->uploadImage($file, 'public/assets/front/excel/');
            $path = 'public/assets/front/excel/' . $image;
            //$import = new ExcelImportBda;
            $import = new ExcelImport($input['option']);
            Excel::import($import, $path);
            $totalAmount = $import->getTotalAmount();

            $errors = $import->getErrors();
            $errorCount = $import->getErrorCount();
            $duplicateNumbers = $import->getDuplicateNumbers();
            $responseMessage = '';
            $responseType = '';

            global $months;
            $lang = Session::get('locale');



            if (!empty($errors)) {
                unlink($path);
                $errorMessage = '';
                foreach ($errors as $error) {
                    $rowNumber = $error['row'];
                    $errorMessages = implode(', ', $error['errors']);
                    $errorMessage .= __('message.Row') . "$rowNumber: $errorMessages\n";
                }
                Session::put('error_message', $errorMessage);
                return Redirect::to('/bulk-transfer');
            }

            if ($errorCount > 0) {
                $responseMessage = $errorCount . ' ' . __('message.records not transferred due to errors.');
                $responseType = __('error_message');
            } else {

                $dupNo = !empty($duplicateNumbers) ? __('message.Duplicate numbers found:') . implode(', ', $duplicateNumbers) . '.' : '';
                $responseMessage = __('message.Excel has been uploaded successfully') . $dupNo;
                $responseType = __('success_message');
            }

            $parent_id = Auth::user()->parent_id;
            $senderUser = User::where('id', $parent_id)->first();
            $userType = $this->getUserType($senderUser->user_type);
            $transactionLimit = TransactionLimit::where('type', $userType)->first();

            if ($transactionLimit->bulkMin > $totalAmount) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->bulkMin);
                return Redirect::to('/bulk-transfer');
            }

            if ($totalAmount > $transactionLimit->bulkMax) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->bulkMax);
                return Redirect::to('/bulk-transfer');
            }

            $excelData = $import->getCollectedData();
            $rowCount = count($excelData);
            $refrence_id = time() . rand();
            $header_array = ['Beneficiary', 'Iban', 'Reason', 'Amount'];
            $header_row = $import->onFirstRecord();
            if ($rowCount <= 0) {
                Session::put('error_message', __('message.You cannot upload empty file'));
                return Redirect::to('/bulk-transfer');
            }

            if (array_diff($header_array, $header_row) === array()) {

                $fees_arr = array();
                foreach ($excelData as $data) {
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Money Transfer Via BDA');
                    $fees_arr[] = $total_fees; // Store calculated fee
                }

                $totalAmtWithFee = ($totalAmount + array_sum($fees_arr));

                if ($senderUser->wallet_balance < $totalAmtWithFee) {
                    unlink($path);
                    Session::put('error_message', __('message.Insufficient Balance !'));
                    return Redirect::to('/bulk-transfer');
                }

                $uploadedExcel = UploadedExcel::create([
                    'user_id' => Auth::user()->id,
                    'parent_id' => Auth::user()->parent_id,
                    'excel' => $image,
                    'reference_id' => $refrence_id,
                    'no_of_records' => $rowCount,
                    'totat_amount' => $totalAmount,
                    'remarks' => $input['remarks'],
                ]);
                $currentWalletBalance = $senderUser->wallet_balance;
                foreach ($excelData as $data) {
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Money Transfer Via BDA');
                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmtWithFee;
                    $sender_hold_amount = $senderUser->holdAmount + $totalAmtWithFee;
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);

                    $excelTrans = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'first_name' => '',
                        'name' => '',
                        'country_id' => '',
                        'wallet_manager_id' => '',
                        'tel_number' => '',
                        'amount' => $data['amount'],
                        'fees' => $total_fees,
                        'comment' => $data['reason'],
                        'bdastatus' => 'BDA'
                    ]);

                    $remittanceData = new RemittanceData();
                    $getLstNo = RemittanceData::orderBy('id', 'desc')->select('referenceLot')->first();
                    if (empty($getLstNo)) {
                        $refNoLo = 'SWAP9999';
                    } else {
                        preg_match('/([a-zA-Z]+)([0-9]+)/', $getLstNo->referenceLot, $matches);
                        $incrementedPart = (int) $matches[2] + 1;
                        $newReferenceLot = $matches[1] . $incrementedPart;
                        $refNoLo = $newReferenceLot;
                    }
                    $remittanceData->transactionId = $this->generateAndCheckUnique();
                    $remittanceData->product = 'SWAP';
                    $remittanceData->iban = $data['iban'];
                    $remittanceData->titleAccount = $data['beneficiary'];
                    $remittanceData->amount = $data['amount'];
                    $remittanceData->partnerreference = $this->generateString();
                    $remittanceData->reason = $data['reason'];
                    $remittanceData->userId = Auth::user()->parent_id;
                    $remittanceData->referenceLot = $refNoLo;
                    $remittanceData->type = 'bank_transfer';
                    $remittanceData->excel_id = $excelTrans->id;
                    $remittanceData->save();

                    $transId = $this->createTransaction(Auth::user()->parent_id, 0, null, $data['amount'], $total_fees, $excelTrans->id, $remittanceData->id, "SWAPTOBDA");
                    $this->transLadger($parent_id, $currentWalletBalance, $data['amount'], $total_fees, '2', $excelTrans->id, $transId);
                    $currentWalletBalance -= $data['amount'] + $total_fees;
                }

                if (!empty($fees_arr)) {
                    $total_fees_amount = array_sum($fees_arr);
                    UploadedExcel::where('id', $uploadedExcel->id)->update(['total_fees' => $total_fees_amount]);
                }

                Session::put($responseType, $responseMessage);
                return Redirect::to('/pending-approvals');
            } else {
                Session::put('error_message', __('message.You cannot change the header row'));
            }


            return Redirect::to('/bulk-transfer');
        }
        return view('dashboard.bulk_transfer', ['title' => $pageTitle]);
    }

    public function bulkTransferOnafriq(Request $request)
    {
        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }

        $pageTitle = 'Bulk Transfer';
        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'excel_file' => 'required|mimes:xlsx,xls',
            ];

            $customMessages = [
                'excel_file.required' => __('message.Please upload an excel file'),
                'excel_file.mimes' => __('message.Excel file should be in xlsx,xls'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                Session::put('error_message', $messages);
                return Redirect::to('/bulk-transfer');
            }
            $file = $request->file('excel_file');
            $image = $this->uploadImage($file, 'public/assets/front/excel/');
            $path = 'public/assets/front/excel/' . $image;
            //$import = new ExcelImportBda;
            $import = new ExcelImport($input['option']);
            Excel::import($import, $path);
            $totalAmount = $import->getTotalAmount();

            $errors = $import->getErrors();
            $errorCount = $import->getErrorCount();
            $duplicateNumbers = $import->getDuplicateNumbers();
            $responseMessage = '';
            $responseType = '';

            global $months;
            $lang = Session::get('locale');

            if (!empty($errors)) {
                unlink($path);
                $errorMessage = '';
                foreach ($errors as $error) {
                    $rowNumber = $error['row'];
                    $errorMessages = implode(', ', $error['errors']);
                    $errorMessage .= __('message.Row') . "$rowNumber: $errorMessages\n";
                }
                Session::put('error_message', $errorMessage);
                return Redirect::to('/bulk-transfer');
            }

            if ($errorCount > 0) {
                $responseMessage = $errorCount . ' ' . __('message.records not transferred due to errors.');
                $responseType = __('error_message');
            } else {

                $dupNo = !empty($duplicateNumbers) ? __("message.Duplicate numbers found:") . implode(', ', $duplicateNumbers) . '.' : '';
                $responseMessage = __('message.Excel has been uploaded successfully') . $dupNo;
                $responseType = __('success_message');
            }

            $parent_id = Auth::user()->parent_id;
            $senderUser = User::where('id', $parent_id)->first();
            $userType = $this->getUserType($senderUser->user_type);
            $transactionLimit = TransactionLimit::where('type', $userType)->first();

            if ($transactionLimit->onafriqa_min > $totalAmount) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer less than') . CURR . ' ' . $transactionLimit->onafriqa_min);
                return Redirect::to('/bulk-transfer');
            }

            if ($totalAmount > $transactionLimit->onafriqa_max) {
                unlink($path);
                Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $transactionLimit->onafriqa_max);
                return Redirect::to('/bulk-transfer');
            }

            $excelData = $import->getCollectedData();
            $rowCount = count($excelData);
            $refrence_id = time() . rand();
            $header_array = ['Recipient Country', 'Wallet Manager', 'Recipient Phone Number', 'Recipient First Name', 'Recipient Surname', 'Amount', 'Sender Country', 'Sender Phone Number', 'Sender First Name', 'Sender Surname', 'Sender Address', 'Sender DOB', 'Sender ID Type', 'Sender ID Number'];
            $header_row = $import->onFirstRecord();
            if ($rowCount <= 0) {
                Session::put('error_message', __('message.You cannot upload empty file'));
                return Redirect::to('/bulk-transfer');
            }

            if (array_diff($header_array, $header_row) === array()) {

                $fees_arr = array();
                foreach ($excelData as $data) {
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Money Transfer Via ONAFRIQ');
                    $fees_arr[] = $total_fees; // Store calculated fee
                }


                // echo"<pre>";print_r($transactionLimit);die;
                foreach ($excelData as $datas) {
                    $transactionLimit = TransactionLimit::where('type', $userType)->first();

                    $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
                    $unverifiedKycMax = $transactionLimit->unverifiedKycMax;
                    if ($senderUser->kyc_status == "pending") {
                        if ($unverifiedKycMin > $datas['amount']) {
                            Session::put('error_message', __('message.The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
                            return Redirect::to('/bulk-transfer');
                        }

                        if ($unverifiedKycMax < $datas['amount']) {
                            Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is still pending.');
                            return Redirect::to('/bulk-transfer');
                        }
                    } else {

                        if ($unverifiedKycMin > $datas['amount']) {
                            Session::put('error_message', __('message.The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
                            return Redirect::to('/bulk-transfer');
                        }

                        if ($unverifiedKycMax < $datas['amount']) {
                            Session::put('error_message', __('message.You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is not verified.Please verify your KYC first');
                            return Redirect::to('/bulk-transfer');
                        }
                    }
                }

                $totalAmtWithFee = ($totalAmount + array_sum($fees_arr));

                if ($senderUser->wallet_balance < $totalAmtWithFee) {
                    unlink($path);
                    Session::put('error_message', __('message.Insufficient Balance !'));
                    return Redirect::to('/bulk-transfer');
                }

                $uploadedExcel = UploadedExcel::create([
                    'user_id' => Auth::user()->id,
                    'parent_id' => Auth::user()->parent_id,
                    'excel' => $image,
                    'reference_id' => $refrence_id,
                    'no_of_records' => $rowCount,
                    'totat_amount' => $totalAmount,
                    'remarks' => $input['remarks'],
                ]);

                $currentWalletBalance = $senderUser->wallet_balance;
                foreach ($excelData as $data) {
                    $total_fees = $this->calculateFees($data['amount'], $parent_id, 'Money Transfer Via ONAFRIQ');
                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmtWithFee;
                    $sender_hold_amount = $senderUser->holdAmount + $totalAmtWithFee;
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount, 'holdAmount' => $sender_hold_amount]);

                    $excelTrans = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'first_name' => '',
                        'name' => '',
                        'country_id' => '',
                        'wallet_manager_id' => '',
                        'tel_number' => '',
                        'amount' => $data['amount'],
                        'fees' => $total_fees,
                        'reason' => '',
                        'bdastatus' => 'ONAFRIQ'
                    ]);

                    $recipientCountry = "";
                    if ($data['recipientCountry'] == 'Benin') {
                        $recipientCountry = 'BJ';
                    } elseif ($data['recipientCountry'] == 'Burkina Faso') {
                        $recipientCountry = 'BF';
                    } elseif ($data['recipientCountry'] == 'Guinea Bissau') {
                        $recipientCountry = 'GW';
                    } elseif ($data['recipientCountry'] == 'Niger') {
                        $recipientCountry = 'NE';
                    } elseif ($data['recipientCountry'] == 'Senegal') {
                        $recipientCountry = 'SN';
                    } elseif ($data['recipientCountry'] == 'Mali') {
                        $recipientCountry = 'ML';
                    } elseif ($data['recipientCountry'] == 'Togo') {
                        $recipientCountry = 'TG';
                    } elseif ($data['recipientCountry'] == 'Ivoiry Coast') {
                        $recipientCountry = 'CI';
                    }


                    $senderCountry = "";
                    if ($data['senderCountry'] == 'Benin') {
                        $senderCountry = 'BJ';
                    } elseif ($data['senderCountry'] == 'Burkina Faso') {
                        $senderCountry = 'BF';
                    } elseif ($data['senderCountry'] == 'Guinea Bissau') {
                        $senderCountry = 'GW';
                    } elseif ($data['senderCountry'] == 'Niger') {
                        $senderCountry = 'NE';
                    } elseif ($data['senderCountry'] == 'Senegal') {
                        $senderCountry = 'SN';
                    } elseif ($data['senderCountry'] == 'Mali') {
                        $senderCountry = 'ML';
                    } elseif ($data['senderCountry'] == 'Togo') {
                        $senderCountry = 'TG';
                    } elseif ($data['senderCountry'] == 'Cameroon') {
                        $senderCountry = 'CM';
                    } elseif ($data['senderCountry'] == 'Ivoiry Coast') {
                        $senderCountry = 'CI';
                    } elseif ($data['senderCountry'] == 'Gabon') {
                        $senderCountry = 'GA';
                    } elseif ($data['senderCountry'] == 'France') {
                        $senderCountry = 'FR';
                    }

                    // if($data['Recipient Country']){
                    //     'BJ'
                    // }

                    $id = Auth::user()->id;
                    $onafriqaDataA = new OnafriqaData();
                    $onafriqaDataA->recipientCountry = $recipientCountry;
                    $onafriqaDataA->recipientMsisdn = $data['recipientMsisdn'];
                    $onafriqaDataA->walletManager = $data['walletManager'];
                    $onafriqaDataA->recipientName = $data['recipientName'];
                    $onafriqaDataA->recipientSurname = $data['recipientSurname'];
                    $onafriqaDataA->recipientCurrency = 'XOF';
                    $onafriqaDataA->amount = $data['amount'];
                    $onafriqaDataA->senderCountry = $senderCountry ?? null;
                    $onafriqaDataA->senderMsisdn = $data['senderMsisdn'] ?? null;
                    $onafriqaDataA->senderName = $data['senderName'] ?? null;
                    $onafriqaDataA->senderSurname = $data['senderSurname'] ?? null;
                    $onafriqaDataA->senderAddress = $data['senderAddress'] ?? null;
                    $onafriqaDataA->senderDob = $data['senderDob'] ?? null;
                    $onafriqaDataA->senderIdType = $data['senderIdType'] ?? null;
                    $onafriqaDataA->senderIdNumber = $data['senderIdNumber'] ?? null;
                    $onafriqaDataA->thirdPartyTransactionId = $this->generateAndCheckUnique();
                    $onafriqaDataA->status = 'pending';
                    $onafriqaDataA->userId = Auth::user()->parent_id;
                    $onafriqaDataA->excelTransId = $excelTrans->id;
                    $onafriqaDataA->fromMSISDN = $data['senderMsisdn'];
                    $onafriqaDataA->save();


                    $transId = $this->createTransaction(Auth::user()->parent_id, 0, null, $data['amount'], $total_fees, $excelTrans->id, $onafriqaDataA->id, "SWAPTOONAFRIQ");
                    $this->transLadger($parent_id, $currentWalletBalance, $data['amount'], $total_fees, '2', $excelTrans->id, $transId);
                    $currentWalletBalance -= $data['amount'] + $total_fees;
                }

                if (!empty($fees_arr)) {
                    $total_fees_amount = array_sum($fees_arr);
                    UploadedExcel::where('id', $uploadedExcel->id)->update(['total_fees' => $total_fees_amount]);
                }

                Session::put($responseType, $responseMessage);
                return Redirect::to('/pending-approvals');
            } else {
                Session::put('error_message', __('message.You cannot change the header row'));
            }


            return Redirect::to('/bulk-transfer');
        }
        return view('dashboard.bulk_transfer', ['title' => $pageTitle]);
    }

    public function checkDuplicates(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('excel_file');
        $image = $this->uploadImage($file, 'public/assets/front/excel/');
        $path = 'public/assets/front/excel/' . $image;

        $import = new ExcelImport($request->option);

        Excel::import($import, $path);

        $duplicateNumbers = $import->getDuplicateNumbers();

        unlink($path);

        return response()->json(['hasDuplicates' => !empty($duplicateNumbers), 'duplicates' => array_keys($duplicateNumbers)]);
    }

    public function getStatus($status)
    {
        $lang = Session::get('locale');

        $status_list = array(0 => 'Pending', 1 => 'Approved ', 2 => 'Rejected ', 3 => 'Approved ', 4 => 'Rejected ', 5 => 'Approved ', 6 => 'Rejected ');

        return $status_list[$status];
    }

    private function getStatusText($status)
    {
        // echo"<pre>";print_r($status);die;
        $lang = Session::get('locale');

        // echo"<pee>";print_r($lang);die;

        if ($status != "") {
            $statusArr = array('0' => 'Pending', '1' => 'Completed', '2' => 'Pending', '3' => 'Failed', '4' => 'Reject', '5' => 'Refund', '6' => 'Refund Completed');
            return $statusArr[$status];
        }

        return '-';
    }

    public function getExcelRecords(Request $request, $id)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        $lang = Session::get('locale');


        $totalRecords = ExcelTransaction::select('count(*) as allcount')->where('excel_id', $id)->count();
        $totalRecordswithFilter = ExcelTransaction::select('count(*) as allcount')->where('excel_id', $id)->count();
        // DB::enableQueryLog();
        $records = ExcelTransaction::where('excel_id', $id)
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'excel_transactions.first_name as firstName', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status', 'transactions.receiver_id')
            ->orderBy('excel_transactions.id', 'ASC')
            ->when($columnName, function ($query) use ($columnName, $columnSortOrder) {
                switch ($columnName) {
                    case 'submitted_by':
                        $orderByColumn = 'submitter.name';
                        break;
                    case 'approved_by':
                        $orderByColumn = 'approver.name';
                        break;
                    default:
                        $orderByColumn = $columnName;
                        break;
                }
                $query->orderBy($orderByColumn, $columnSortOrder);
            })
            ->skip($start)
            ->take($rowperpage)
            ->get();

        // echo "<pre>";
        // print_r($records);

        $data_arr = array();

        foreach ($records as $record) {
            $OnafriqaData = OnafriqaData::where('excelTransId', $record->id)->first();

            // echo"<pre>";print_r($record);die;

            if (isset($record->bdastatus) && $record->bdastatus == "BDA") {
                $remittanceData = RemittanceData::where('excel_id', $record->id)->first();
                $status = $remittanceData->status == "EN_ATTENTE" ? "Pending" :
                    ($remittanceData->status == "EN_ATTENTE_REGLEMENT" ? "Pending Recipient Bank" :
                        ($remittanceData->status == "ERRONE" ? "Reject" :
                            ($remittanceData->status == "REJETE" ? "Reject" :
                                ($remittanceData->status == "PENDING" ? "Pending" :
                                    ($remittanceData->status == "SUCCESS" ? "Completed" : "")))));
            } else {
                $status = ($record->remarks != "") ? 'Rejected' : $this->getStatusText($record->transaction_status);
            }
            $remarks = '';

            if ($record->remarks == null) {
                $remarks = '-';
            } else {/*  */
                $remarks = $record->remarks;
            }
            if ($record->bdastatus == " ") {
                if ($record->first_name === null) {
                    $remark = __('First name is missing');
                } elseif ($record->name === null) {
                    $remark = __('Last name is missing');
                } elseif ($record->first_name === null && $record->name === null) {
                    $remark = __('First name and Last name are missing');
                } elseif ($record->remarks == 'Insufficient Balance !') {
                    $remark = "Solde insuffisant !";
                } else {
                    $remark = $record->remarks;
                }

                if ($record->first_name == null || $record->name == null) {
                    $status = '<div class="tooltip-container">   
                    <div class="tooltip-text">' . htmlspecialchars($this->getStatusText(3)) . '</div>
                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="First Name and Last Name blank">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    </span>       
                    </div>';
                }
            }

            if ($lang === 'fr') {
                if ($record->remarks === 'BDA server error') {
                    $remarks = 'Erreur du serveur BDA';
                } elseif ($record->remarks === 'Rejected') {
                    $remarks = 'Rejeté';
                } elseif ($record->remarks === 'You cannot send funds to yourself') {
                    $remarks = 'Vous ne pouvez pas envoyer de fonds à vous-même.';
                } elseif ($record->remarks === 'gimac server error' || $record->remarks === 'Gimac Server Error') {
                    $remarks = 'Erreur du serveur GIMAC';
                } elseif ($record->remarks === 'Insufficient Balance !') {
                    $remarks = 'Solde insuffisant !';
                } elseif ($record->remarks === 'You cannot transfer more thanXAF 200000because your KYC is not verified.Please verify your KYC first') {
                    $remarks = 'Vous ne pouvez pas transférer plus de 200 000 XAF car votre KYC nest pas vérifié. Veuillez dabord vérifier votre KYC';
                } elseif ($record->remarks === 'ONAFRIQ server error') {
                    $remarks = 'Erreur du serveur ONAFRIQ';
                } elseif ($record->remarks === 'Unable to extract error_description') {
                    $remarks = 'Impossible dextraire la description de lerreur.';
                } elseif ($record->remarks === 'Your monthly transfer limit has been reached.') {
                    $remarks = 'Votre limite de transfert mensuelle a été atteinte.';
                } elseif ($record->remarks === 'Your weekly transfer limit has been reached.') {
                    $remarks = 'Votre limite de transfert hebdomadaire a été atteinte.';
                } elseif ($record->remarks === 'Your daily transfer limit has been reached.') {
                    $remarks = 'Votre limite de transfert quotidienne a été atteinte.';
                } elseif ($record->remarks === 'The minimum transfer amount should be greater than XAF 1.') {
                    $remarks = 'Le montant minimum du transfert doit être supérieur à 1 XAF.';
                } elseif ($record->remarks === 'You cannot transfer more than XAF 200000 because your KYC is still pending.') {
                    $remarks = 'Vous ne pouvez pas transférer plus de 200 000 XAF car votre KYC est toujours en attente.';
                } elseif ($record->remarks === 'You cannot transfer less than XAF 1.') {
                    $remarks = 'Vous ne pouvez pas transférer moins de 1 XAF.';
                } elseif ($record->remarks === 'Recipient phone number not active ') {
                    $remarks = 'Numéro de téléphone du destinataire inactif.';
                } elseif ($record->remarks === 'Subscriber not authorized to receive amount') {
                    $remark = 'L\abonné n\est pas autorisé à recevoir le montant.';
                } else {
                    $remarks = $record->remarks;
                }
            } elseif ($lang === 'en') {
                if ($record->remarks === 'BDA server error') {
                    $remarks = 'BDA server error';
                } elseif ($record->remarks === 'Rejected') {
                    $remarks = 'Rejected';
                } elseif ($record->remarks === 'You cannot send funds to yourself') {
                    $remarks = 'You cannot send funds to yourself';
                } elseif ($record->remarks === 'gimac server error' || $record->remarks === 'GIMAC server error') {
                    $remarks = 'GIMAC server error';
                } elseif ($record->remarks === 'Insufficient Balance !') {
                    $remarks = 'Insufficient Balance !';
                } elseif ($record->remarks === 'You cannot transfer more thanXAF 200000because your KYC is not verified.Please verify your KYC first') {
                    $remarks = 'You cannot transfer more thanXAF 200000because your KYC is not verified.Please verify your KYC first';
                } elseif ($record->remarks === 'ONAFRIQ server error') {
                    $remarks = 'ONAFRIQ server error';
                } elseif ($record->remarks === 'Unable to extract error_description') {
                    $remarks = 'Unable to extract error_description';
                } elseif ($record->remarks === 'Your monthly transfer limit has been reached.') {
                    $remarks = 'Your monthly transfer limit has been reached.';
                } elseif ($record->remarks === 'Your weekly transfer limit has been reached.') {
                    $remarks = 'Your weekly transfer limit has been reached.';
                } elseif ($record->remarks === 'Your daily transfer limit has been reached.') {
                    $remarks = 'Your daily transfer limit has been reached.';
                } elseif ($record->remarks === 'The minimum transfer amount should be greater than XAF 1.') {
                    $remarks = 'The minimum transfer amount should be greater than XAF 1.';
                } elseif ($record->remarks === 'You cannot transfer more than XAF 200000 because your KYC is still pending.') {
                    $remarks = 'You cannot transfer more than XAF 200000 because your KYC is still pending.';
                } elseif ($record->remarks === 'You cannot transfer less than XAF 1.') {
                    $remarks = 'You cannot transfer less than XAF 1.';
                } elseif ($record->remarks === 'Recipient phone number not active ') {
                    $remarks = 'Recipient phone number not active.';
                } elseif ($record->remarks === 'Subscriber not authorized to receive amount') {
                    $remark = 'Subscriber not authorized to receive amount.';
                } else {
                    $remarks = $record->remarks;
                }
            }

            global $months;
            $lang = Session::get('locale');

            if ($lang == 'fr') {
                $date = date('d F, Y', strtotime($record->created_at));
                $frenchDate = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate = date('d M,y', strtotime($record->created_at));
            }


            $statusupdate = '';

            if ($lang == 'fr') {
                if ($status == 'Completed') {
                    $statusupdate = 'Terminé';
                } else if ($status == 'Pending') {
                    $statusupdate = 'En attente';
                } else if ($status == 'Failed') {
                    $statusupdate = 'Échoué';
                } else if ($status == 'Reject') {
                    $statusupdate = 'Rejeté';
                } else if ($status == 'Rejected') {
                    $statusupdate = 'Rejetée';
                } else if ($status == 'Refund') {
                    $statusupdate = 'Remboursement';
                } else if ($status == 'Refund Completed') {
                    $statusupdate = 'Remboursement terminé';
                } else if ($status == 'EN_ATTENTE_REGLEMENT') {
                    $statusupdate = 'Banque destinataire en attente';
                }
            } elseif ($lang == 'en') {
                if ($status == 'Completed') {
                    $statusupdate = 'Completed';
                } else if ($status == 'Pending') {
                    $statusupdate = 'Pending';
                } else if ($status == 'Failed') {
                    $statusupdate = 'Failed';
                } else if ($status == 'Reject') {
                    $statusupdate = 'Reject';
                } else if ($status == 'Rejected') {
                    $statusupdate = 'Rejected';
                } else if ($status == 'Refund') {
                    $statusupdate = 'Refund';
                } else if ($status == 'Refund Completed') {
                    $statusupdate = 'Refund Completed';
                } else if ($status == 'EN_ATTENTE_REGLEMENT') {
                    $statusupdate = 'Pending Recipient Bank';
                }
            }

            $approv = '';

            if ($lang == 'fr') {
                $approv = $record->approved_by ? $record->approved_by : __('Non approuvé');

            } elseif ($lang == 'en') {
                $approv = $record->approved_by ? $record->approved_by : __('Not Approved');

            }



            // echo"<pre>";print_r($lang);die;

            $is_receiver_exist = User::where('phone', $record->tel_number)->whereNotIn('user_type', ['Approver', 'Submitter'])->first();
            if (isset($is_receiver_exist)) {

                $record->first_name = $is_receiver_exist->name;
            }

            $tel_number = $record->tel_number;

            $phone_number_only = preg_replace('/^\+?\d+\s/', '', $tel_number);
            //echo"<pre>";print_r($record->bdastatus);die;
            if (!empty($record->bdastatus) && $record->bdastatus == "ONAFRIQ") {
                $firstName = $OnafriqaData->recipientName ?? '-';
                $lastName = $OnafriqaData->recipientSurname ?? '-';
            } else {
                $firstName = $record->firstName;
                $lastName = $record->name;
            }

            $data_arr[] = array(
                "first_name" => $firstName != '' ? $firstName : '-',
                "name" => $lastName != '' ? $lastName : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                "country_name" => $record->country_name != '' ? $record->country_name : (isset($OnafriqaData->recipientCountry) && $OnafriqaData->recipientCountry != '' ? $this->getCountryStatus($OnafriqaData->recipientCountry) : '-'),
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : (isset($OnafriqaData->walletManager) && $OnafriqaData->walletManager != '' ? $OnafriqaData->walletManager : '-'),
                "tel_number" => $record->tel_number != '' ? $record->tel_number : (isset($OnafriqaData->recipientMsisdn) && $OnafriqaData->recipientMsisdn != '' ? $OnafriqaData->recipientMsisdn : '-'),
                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "approved_by" => $record->approved_by ? $record->approved_by : __('message.Not Approved'),
                "created_at" => $frenchDate,
                "gimac_status" => $statusupdate ?? "",
                "remarks" => $remarks,
                "beneficiary" => $remittanceData->titleAccount ?? '',
                "iban" => $remittanceData->iban ?? '',
                "reason" => $remittanceData->reason ?? '',
                "transactionId" => ($OnafriqaData->transactionId ?? '-'),
            );
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function getUserType($key)
    {
        $userArray = array("User" => 1, "Merchant" => 2, "Agent" => 3);
        return $userArray[$key];
    }

    public function ExportExcel(Request $request)
    {
        $searchValue = $request->input('search');
        $daterange = $request->input('daterange');
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;

        if (Auth::user()->user_type == "Submitter") {
            $query = UploadedExcel::query()
                ->where('parent_id', $parent_id)
                ->whereIn('status', [1, 2, 3, 4, 5, 6])
                ->where(function ($q) use ($searchValue) {
                    $q->where('reference_id', 'like', '%' . $searchValue . '%')
                        ->orWhere('remarks', 'like', '%' . $searchValue . '%')
                        ->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
                })
                ->where(function ($q) use ($daterange) {
                    if ($daterange) {
                        $dt = explode(' - ', $daterange);
                        $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                        $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                        $q->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date);
                    }
                });
        }
        if (Auth::user()->user_type == "Approver") {
            $query = UploadedExcel::query()
                ->where('parent_id', $parent_id)
                ->whereIn('status', [3, 4, 5, 6])
                ->where(function ($q) use ($searchValue) {
                    $q->where('reference_id', 'like', '%' . $searchValue . '%')
                        ->orWhere('remarks', 'like', '%' . $searchValue . '%')
                        ->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
                })
                ->where(function ($q) use ($daterange) {
                    if ($daterange) {
                        $dt = explode(' - ', $daterange);
                        $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                        $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                        $q->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date);
                    }
                });
        }
        if (Auth::user()->user_type == "Merchant") {
            $query = UploadedExcel::query()
                ->where('parent_id', $parent_id)
                ->whereIn('status', [5, 6])
                ->where(function ($q) use ($searchValue) {
                    $q->where('reference_id', 'like', '%' . $searchValue . '%')
                        ->orWhere('remarks', 'like', '%' . $searchValue . '%')
                        ->orWhere('totat_amount', 'like', '%' . $searchValue . '%');
                })
                ->where(function ($q) use ($daterange) {
                    if ($daterange) {
                        $dt = explode(' - ', $daterange);
                        $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
                        $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
                        $q->whereDate('created_at', '>=', $start_date)
                            ->whereDate('created_at', '<=', $end_date);
                    }
                });
        }


        // Get the filtered records
        $records = $query->get();

        // Generate and return the Excel file

        return Excel::download(new TransactionHistoryExport($records), 'TransactionHistoryExport..xlsx');
    }

    public function beneficiaryList(Request $request)
    {
        $pageTitle = 'Beneficiary List';

        return view('dashboard.beneficiaryList', [
            'title' => $pageTitle,
        ]);
    }

    public function addBeneficiary(Request $request)
    {

        $lang = Session::get('locale');


        $user = Auth::user();
        if ($request->method() == 'GET') {
            $pageTitle = 'Add Beneficiary';
            if ($lang == 'fr') {

                $country_list = Country::orderBy('name_fr', 'asc')->get();
            } else {
                $country_list = Country::orderBy('name', 'asc')->get();
            }



            if ($lang == 'fr') {
                $wallet_manager_list = WalletManager::orderBy('name_fr', 'asc')->get();
            } else {
                $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();
            }

            return view('dashboard.addBeneficiary', [
                'title' => $pageTitle,
                'country_list' => $country_list,
                'wallet_manager_list' => $wallet_manager_list,
                'lang' => $lang
            ]);
        }

        // Check if entry with same phone and same account exists
        $input = $request->all();
        //echo"<pre>";print_r($input);die;
        if ($input['option'] == 'swap_to_swap') {
            // Check if the user exists with the given countryCode and phone
            $exits = User::where('countryCode', $request->countryCode)
                ->where('phone', $request->phone)
                ->first();

            $count = $exits ? 1 : 0;

            // If user doesn't exist, flash the message and redirect
            if ($count <= 0) {
                Session::flash('fail_message', __('The user does not exist'));
                return Redirect::to('/add-beneficiary');
            }
        }



        if ($input['option'] == 'swap_to_swap') {
            $create = [
                'userId' => $user->id,
                'parentId' => $user->parent_id ? $user->parent_id : $user->id,
                'type' => $request->option,
                'first_name' => $exits->name,
                'name' => '-',
                'country' => '-',
                'country_code' => $request->countryCode ?? null,
                'telephone' => $request->phone ?? null,
                'walletManagerId' => '-',
            ];
        } else {
            $create = [
                'userId' => $user->id,
                'parentId' => $user->parent_id ? $user->parent_id : $user->id,
                'type' => $request->option,
                'first_name' => $request->first_name,
                'name' => $request->name ?? null,
                'country' => $request->country_id ?? null,
                'country_code' => $request->country_code ?? null,
                'telephone' => $request->phone ?? null,
                'walletManagerId' => $request->wallet_manager_id ?? null,
            ];
        }


        Beneficiary::create($create);

        return redirect()
            ->to('beneficiary-list')
            ->with('success_message', __('message.Beneficiary Added successfully'));
    }

    public function allBeneficiaryList(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length");
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column'];
        $columnName = $columnName_arr[$columnIndex]['data'];
        $columnSortOrder = $order_arr[0]['dir'];
        $lang = Session::get('locale');


        $input = $request->all();

        $user = Auth::user();
        if ($user->user_type == "Merchant") {
            $parent_id = $user->id;
        } else {
            $parent_id = $user->parent_id;
        }
        $daterange = $request->get('daterange'); // Retrieve the daterange from request
        // Start the query
        $query = Beneficiary::with(['countryDetail', 'walletManager'])
            ->where('parentId', $parent_id);

        // Apply search if available
        if (!empty($search_arr)) {
            $searchValue = $search_arr;
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('first_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('telephone', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('countryDetail', function ($q) use ($searchValue) {
                        $q->where('name', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhereHas('walletManager', function ($q) use ($searchValue) {
                        $q->where('name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        // Date range filtering
        if (!empty($daterange)) {
            $dt = explode(' - ', $daterange);
            $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
            $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');

            $query->where(function ($query) use ($start_date, $end_date) {
                $query->whereDate('beneficiary.created_at', '>=', $start_date)
                    ->whereDate('beneficiary.created_at', '<=', $end_date);
            });
        }

        // Count total records without filtering
        $totalRecords = $query->count();

        // Apply sorting
        $query->orderBy('created_at', 'DESC');

        // Apply pagination
        $beneficiaries = $query->skip($start)->take($rowperpage)->get();

        $data_arr = [];
        foreach ($beneficiaries as $item) {
            $checked = ($item->status) ? 'checked' : '';


            global $months;

            if ($lang == 'fr') {
                $date = date('d F, Y', strtotime($item->created_at));
                $frenchDate = str_replace(array_keys($months), $months, $date);
            } else {
                $frenchDate = $item->created_at->format('d M, Y');
            }

            $status = '';
            if ($lang == 'fr') {
                $status = ($item->status) ? 'Actif' : 'Inactif';
            } else {
                $status = ($item->status) ? 'Active' : 'Inactive';
            }

            $data_arr[] = [
                'first_name' => $item->first_name,
                'name' => $item->name ?? '-',
                'country' => $item->countryDetail->name ?? '',
                'country_code' => $item->country_code,
                'walletManager' => $item->walletManager->name ?? '',
                'telephone' => $item->telephone,
                'createdAt' => $frenchDate,
                'action' => trim(preg_replace('/\n+/', '', "
                    <div class=\"form-check form-switch\">
                    <input
                    class=\"form-check-input ms-2\"
                    title=\"{$status}\"
                    data-item=\"{$item->id}\"
                    onchange=\"toggleStatus(this)\"
                    type=\"checkbox\"
                    id=\"flexSwitchCheckChecked{$item->id}\"
                    {$checked}
                    />
                    </div>
                    "))
            ];
        }

        // Prepare response
        return response()->json([
            'draw' => intval($draw),
            'iTotalRecords' => $totalRecords,
            'iTotalDisplayRecords' => $totalRecords,
            'aaData' => $data_arr,
        ]);
    }

    public function toggleBeneficiaryStatus(Beneficiary $id, $status)
    {
        $id->update([
            'status' => $status
        ]);

        return [
            'status' => true,
            'message' => ((bool) $status) ? __('message.Beneficiary active successfully') : __('message.Beneficiary inactive successfully')
        ];
    }

    public function changePassword(Request $request)
    {
        $pageTitle = 'Change Password';
        $input = Input::all();
        if ($input) {
            $validate_data = [
                'current_password' => 'required|string',
                'password' => [
                    'required',
                    'string',
                    'min:6', // Minimum length of 8 characters
                ],
                'confirm_password' => 'required|string|same:password', // Must match the new password
            ];

            // Define custom error messages
            $customMessages = [
                'current_password.required' => __('message.Enter current password'),
                'password.required' => __('message.Enter new password'),
                'password.min' => __('message.Password must be at least 6 characters long'),
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/change-password')->withErrors($messages);
            }

            $id = Auth::user()->id;

            $user = User::where('id', $id)->first();

            if (!Hash::check($request->input('current_password'), $user->password)) {
                Session::put('error_message', __('message.Your current password didn`t matched!'));
                return Redirect::to('/change-password');
            }

            $user->password = Hash::make($request->input('password'));
            $user->save();
            Session::put('success_message', __('message.Your password has been updated successfully'));
            return Redirect::to('/logout');
        }
        return view('dashboard.change_password', ['title' => $pageTitle]);
    }

    public function bdaPayment(Request $request)
    {
        $pageTitle = 'BDA Payment';
        $lang = Session::get('locale');
        $id = Auth::User()->id;

        $input = $request->all();

        $country_list = Country::orderBy('name', 'asc')->get();
        $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();


        $UserDetails = User::where('id', $id)->first();



        if (!empty($input)) {

            //  echo"<pre>";print_r($input);die;
            // Initialize a new RemittanceData instance
            $remittanceData = new RemittanceData();

            // Generate and assign a unique transaction ID
            $remittanceData->transactionId = $this->generateAndCheckUnique();
            $receiver = User::where('phone', $input['phone'])->first();

            // Sender details
            $remittanceData->senderType = "I";  // Assuming 'I' stands for 'Individual'
            $remittanceData->firstName = $UserDetails->name ?? null;
            $remittanceData->lastName = $UserDetails->name ?? null;
            $remittanceData->businessName = $UserDetails->business_name ?? null;
            $remittanceData->idType = $input['id_type'] ?? null;
            $remittanceData->idNumber = $input['IDNumber'] ?? null;
            $remittanceData->senderPhoneNumber = $UserDetails->phone ?? null;
            $remittanceData->senderAddress = $UserDetails->address ?? null;

            // Receiver details
            $remittanceData->receiverType = "I";  // Assuming 'I' stands for 'Individual'
            $remittanceData->receiverFirstName = $receiver->name ?? null;
            $remittanceData->receiverLastName = $receiver->name ?? null;
            $remittanceData->receiverBusinessName = $receiver->business_name ?? null;

            // Transaction details
            $remittanceData->deliveryMethod = "Courier";  // Assuming this is static for now
            $remittanceData->walletSource = "Bank of Example";  // Fixed source, ensure it's accurate
            $remittanceData->walletDestination = "US";  // Fixed destination, ensure it's accurate
            $remittanceData->walletManager = $input['walletManager'] ?? null;
            $remittanceData->transactionDescription = $input['description'] ?? null;
            $remittanceData->transactionSourceAmount = $input['amount'] ?? null;
            $remittanceData->sourceCurrency = $input['XAF'] ?? null;  // Ensure XAF (currency code) is correct
            // Uncomment if target amount and currency are needed
            // $remittanceData->transactionTargetAmount = $input['transactionTargetAmount'] ?? null;
            // $remittanceData->targetCurrency = $input['targetCurrency'] ?? null;
            // Set the transaction type to wallet transfer
            $remittanceData->type = 'wallet_transfer';

            // Save the remittance data to the database
            $remittanceData->save();



            return response()->json(['status' => 'Success', 'message' => 'Data saved successfully', 'transactionId' => $remittanceData->transactionId], 200);
        }





        return view('dashboard.bdapayment', ['title' => $pageTitle, 'lang' => $lang, 'country_list' => $country_list, 'wallet_manager_list' => $wallet_manager_list]);
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

    // public function bankBDA(Request $request){
    //     $pageTitle = 'BDA Payment';
    //     $input=$request->all();
    //     $id=Auth::User()->id;
    //     if (!empty($input)) { 
    //         $remittanceData = new RemittanceData(); 
    //         $refNoLo = 'SWAP1011';
    //         $remittanceData->transactionId = $this->generateAndCheckUnique();
    //         $remittanceData->product = $request->has('product') ? $input['product'] : '';
    //         $remittanceData->iban = $request->has('iban') ? $input['iban'] : '';
    //         $remittanceData->titleAccount = $request->has('titleAccount') ? $input['titleAccount'] : '';
    //         $remittanceData->amount = $request->has('amount') ? $input['amount'] : '';
    //         $remittanceData->partnerreference = $request->has('partnerreference') ? $input['partnerreference'] : '';
    //         $remittanceData->reason = $request->has('reason') ? $input['reason'] : '';
    //         $remittanceData->userId =  $id; 
    //         $remittanceData->referenceLot =  $refNoLo; 
    //         $remittanceData->type = 'bank_transfer'; 
    //         $remittanceData->save();
    //         if(!empty($remittanceData->transactionId)){
    //             $data = '{
    //                 "referenceLot":$refNoLo,
    //                 "Transfer number":1,
    //                 "Total amount": $input["amount"],
    //                 "product":$input["amount"],
    //                     "transfers": [
    //                         {
    //                             "ibanCredit": $input["amount"],
    //                             "accounttitle": $input["amount"],
    //                             "amount": $input["amount"],
    //                             "partnerreference": $input["amount"],
    //                             "reason": $input["amount"],
    //                             "Transfer type": "RTGS"
    //                         },
    //                     ]
    //                 }';
    //                 $client = new Client();
    //                 try {
    //                     $response = $client->post('https://survey-apps.bda-net.ci/transfert/', [
    //                         'json' => $data,
    //                         'headers' => [
    //                             'x-api-key' => 'RZdJqzjrkVoapWaRCjGmIUDJLgQPSqXAAaJ8y3ne/dvGoSzzFdJz6T0R0cRazL4wSyExYteJEHu4Xh3DhCMoguG9rlBFfVI+yx8fWtYLdpYv/vO3IdqHeOco+jKI3CrZNmWPlwWZVfqkNZqEaXEfCRBC0L30mrn2mXcQMfveaHmWUN0OeaPbWWS2Cgd34+cj7Qay29jkKbihNiIAPunatQ==',
    //                             'x-client-id' => '7766694c-3bb2-4f35-ab50-2b9a34d95ba6',
    //                         ],
    //                     ]);
    //                     $responseBody = json_decode($response->getBody(), true);
    //                     return $responseBody->;
    //                 } catch (\Exception $e) {
    //                     return ['error' => $e->getMessage()];
    //                 }
    //             }
    //         } 
    //         return view('dashboard.bankbda', ['title' => $pageTitle]); 
    //     }
    //    public function bankBDA(Request $request) {
    //        $pageTitle = 'BDA Payment';
    //        $input = $request->all();
    //        $id = Auth::user()->id;
    //
    //        // Validation rules and custom messages
    //        // $validate_data = [
    //        //     'product' => 'required|string',
    //        //     'iban' => 'required|string',
    //        //     'partnerreference' => 'required|string|max:255',
    //        //     'reason' => 'required|string',
    //        //     'amount' => 'required|numeric|min:1|max:999999',
    //        // ];
    //        // $customMessages = [
    //        //     'product.required' => 'The product field is required.',
    //        //     'product.string' => 'The product must be a valid string.',
    //        //     'iban.required' => 'The IBAN field is required.',
    //        //     'iban.string' => 'The IBAN must be a valid string.',
    //        //     'partnerreference.required' => 'The partner reference field is required.',
    //        //     'partnerreference.string' => 'The partner reference must be a valid string.',
    //        //     'partnerreference.max' => 'The partner reference must not exceed 255 characters.',
    //        //     'amount.required' => 'The amount field is required.',
    //        //     'amount.numeric' => 'The amount must be a number.',
    //        //     'amount.min' => 'The amount must be at least 1.',
    //        //     'amount.max' => 'The amount must not exceed 999,999.',
    //        //     'reason.required' => 'The reason field is required.',
    //        //     'reason.string' => 'The reason must be a valid string.',
    //        // ];
    //        // // Validate request data
    //        // $validator = Validator::make($input, $validate_data, $customMessages);
    //        // if ($validator->fails()) {
    //        //     return Redirect::to('/bdapayment')->withInput()->withErrors($customMessages);
    //        // }
    //        // Save remittance data if input is valid
    //        if (!empty($input)) {
    //            $remittanceData = new RemittanceData();
    //            $refNoLo = $this->generateUniqueReference();
    //
    //            // Set remittance data
    //            $remittanceData->transactionId = $this->generateAndCheckUnique(); // Assuming this method exists
    //            $remittanceData->product = $request->input('product', '');
    //            $remittanceData->iban = $request->input('iban', '');
    //            $remittanceData->titleAccount = 'DEMO001'; // Hardcoded as per original
    //            $remittanceData->amount = $request->input('amount', '');
    //            $remittanceData->partnerreference = $request->input('partnerreference', '');
    //            $remittanceData->reason = $request->input('reason', '');
    //            $remittanceData->userId = $id;
    //            $remittanceData->referenceLot = $refNoLo;
    //            $remittanceData->type = 'bank_transfer';
    //            $remittanceData->save();
    //
    //            // If remittance data is successfully saved
    //            if (!empty($remittanceData->transactionId)) {
    //                // Construct request data to send to the API
    //                $data = [
    //                    'referenceLot' => $refNoLo,
    //                    'Transfer number' => 1,
    //                    'Total amount' => $input['amount'],
    //                    'product' => $input['product'],
    //                    'transfers' => [
    //                        [
    //                            'ibanCredit' => $input['iban'],
    //                            'accounttitle' => $remittanceData->titleAccount,
    //                            'amount' => $input['amount'],
    //                            'partnerreference' => $input['partnerreference'],
    //                            'reason' => $input['reason'],
    //                            'Transfer type' => 'RTGS'
    //                        ]
    //                    ]
    //                ];
    //
    //
    //                $certificate = public_path("CA Bundle.crt");
    //                $client = new Client([
    //                    'verify' => $certificate,
    //                ]);
    //                try {
    //                    $response = $client->post('https://survey-apps.bda-net.ci/transfert/v2.0/virements', [
    //                        'json' => $data,
    //                        'headers' => [
    //                            'x-api-key' => 'RZdJqzjrkVoapWaRCjGmIQkukdOL8e39JQrmW+gH9B+DIjnJbEh1AmUV26OLPAjblWS8jkjAo9j6pMHJOx/sMoPtkB32ha/brVKNJrT3++Qpu+qFa1T2mPVGqKgeGUOGM1QxU71Ts0xnsGpq7IQfX2IA3YGYnJhS8fD+Ggvf2N4KHz9qH6+/Yuj9lxtUNyEN1x57YFkogOjPLqvgdfVk3fbl4p5UgxZyEF+RUiPojpsgsMPfM3dewwd7ysgwlzLv',
    //                            'x-client-id' => '9ca1a01c-a55a-4c1c-a5b9-ec09b5aea768',
    //                        ],
    //                    ]);
    //
    //                    // Decode the response JSON
    //                    $responseBody = json_decode($response->getBody(), true);
    //                    return response()->json($responseBody); // Return JSON response to client
    //                } catch (\Exception $e) {
    //                    // Return the error message
    //                    return response()->json(['error' => $e->getMessage()], 500);
    //                }
    //            }
    //        }
    //
    //        return view('dashboard.bankbda', ['title' => $pageTitle]);
    //    }
    // Generate a unique reference number
    function generateUniqueReference()
    {
        $prefix = 'SWAP';

        do {
            // Generate a random 4-digit number
            $randomDigits = rand(1000, 9999);

            // Generate the full reference number
            $refNoLo = $prefix . $randomDigits;

            // Check if the generated reference number already exists in the database
            $exists = RemittanceData::where('referenceLot', $refNoLo)->exists();
        } while ($exists); // Repeat if the reference number already exists

        return $refNoLo;
    }

    public function beneficiarysingleList(Request $request)
    {
        // Fetch pagination and sorting parameters from the request
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length") != '-1' ? $request->get("length") : 5;
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value
        // Get the authenticated user's ID and parent ID
        $user_id = Auth::user()->id; // Authenticated user ID
        $parent_id = Auth::user()->parent_id; // Parent ID (assuming the field is available)
        // Filter records based on the user ID or parent ID
        $totalRecords = Beneficiary::where(function ($query) use ($user_id, $parent_id) {
            $query->where('userId', $user_id)
                ->orWhere('parentId', $parent_id);
        })
            ->where('status', '1') // Add status filter
            ->count(); // Count total records based on user or parent and status

        $totalRecordswithFilter = Beneficiary::where(function ($query) use ($user_id, $parent_id) {
            $query->where('userId', $user_id)
                ->orWhere('parentId', $parent_id);
        })
            ->where('status', '1') // Add status filter
            ->count(); // Count filtered records with status
        // Fetch filtered and paginated data
        $records = Beneficiary::where(function ($query) use ($user_id, $parent_id) {
            $query->where('userId', $user_id)
                ->orWhere('parentId', $parent_id);
        })
            ->where('status', '1') // Add status filter
            ->orderBy($columnName, $columnSortOrder) // Sort by the selected column
            ->skip($start)
            ->take($rowperpage)
            ->get();


        //echo"<pre>";print_r($records);die;

        $data_arr = array();
        foreach ($records as $record) {
            $encId = base64_encode($this->encryptContent($record->id));

            $data_arr[] = array(
                "id" => $record->id,
                'first_name' => $record->first_name ?? '-',
                'name' => $record->name ?? '-',
                'country' => $record->countryDetail->name ?? '-',
                'country_code' => $record->country_code ? '+' . $record->country_code : '-',
                'telephone' => $record->telephone ?? '-',
                'walletManager' => $record->walletManager->name ?? '-',
                "created_at" => date('d M, Y', strtotime($record->created_at)),
            );
        }

        // Prepare response
        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr,
        );

        echo json_encode($response);
        die;
    }

    public function formFill(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'id' => 'required|integer', // Validate that ID is an integer
        ]);

        // Get the beneficiary ID from the request
        $beneficiaryId = $request->input('id');

        // Process the beneficiary data (e.g., fill out a form)
        // You can use this ID to query the database or perform other actions
        $beneficiary = Beneficiary::find($beneficiaryId);

        //echo"<pre>";print_r($beneficiary);die;
        // Example response (adjust as needed)
        if ($beneficiary) {
            return response()->json([
                'success' => true,
                'message' => __('message.Beneficiary data processed successfully'),
                'data' => $beneficiary
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => __('message.Beneficiary not found'),
            ]);
        }
    }

    // public function CustomerExportExcel(Request $request)
    // {
    //     $searchValue = $request->input('search');
    //     $daterange = $request->input('daterange');

    //     $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;

    //     $query = TransactionLedger::query()->where('user_id', $parent_id);

    //     if (!empty($searchValue)) {
    //         $query->where(function ($q) use ($searchValue) {
    //             if (stripos($searchValue, 'withdraw') !== false) {
    //                 $q->orWhere('type', '=', 2); // Withdraw
    //             }
    //             if (stripos($searchValue, 'credit') !== false) {
    //                 $q->orWhere('type', '=', 1); // Credit
    //             }
    //             $q->orWhere('amount', 'like', '%' . $searchValue . '%')
    //             ->orWhere('closing_balance', 'like', '%' . $searchValue . '%');
    //         });
    //     }

    //     // Apply date range filter
    //     if (!empty($daterange)) {
    //         $dates = explode(' - ', $daterange);
    //         $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
    //         $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
    //         $query->whereBetween('created_at', [$start_date, $end_date]);
    //     }

    //     // Get the filtered records
    //     $records = $query->orderBy('created_at','DESC')->get();

    //     // Export the data using Laravel Excel
    //     return Excel::download(new CustomerDepositExport($records), 'CustomerAccount.xlsx');
    // }

    public function CustomerExportExcel(Request $request)
    {
        $search_arr = $request->input('search');
        $daterange = $request->input('daterange');

        // Get the current user's parent or merchant ID
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;

        // Build the query with filters
        $query = TransactionLedger::query()->where('user_id', $parent_id);

        // Apply search filter
        /*if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        if (stripos($searchValue, 'withdraw') !== false) {
                            $q->orWhere('type', '=', 2); 
                        }
                        if (stripos($searchValue, 'credit') !== false) {
                            $q->orWhere('type', '=', 1); 
                        }
                        $q->orWhere('actual_amount', 'like', '%' . $searchValue . '%')
                        ->orWhere('closing_balance', 'like', '%' . $searchValue . '%');
                    });
                }*/


        if ($search_arr) {
            $query->where(function ($q) use ($search_arr) {
                // Handle search for the 'type' field
                if (stripos($search_arr, 'withdraw') !== false) {
                    $q->orWhere('type', '=', 2); // 'Withdraw' mapped to type = 2
                }
                if (stripos($search_arr, 'credit') !== false) {
                    $q->orWhere('type', '=', 1); // 'Credit' mapped to type = 1
                }

                // Convert the search term to a numeric value for number fields
                if (is_numeric($search_arr)) {
                    $search_numeric = round($search_arr);

                    // Handle search for numeric fields using exact rounded match
                    $q->orWhere(DB::raw('ROUND(actual_amount, 0)'), '=', $search_numeric)
                        ->orWhere(DB::raw('ROUND(closing_balance, 0)'), '=', $search_numeric);
                }
            });
        }

        // Apply date range filter
        if (!empty($daterange)) {
            $dates = explode(' - ', $daterange);
            $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
            $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        // Get the filtered records
        $records = $query->orderBy('created_at', 'DESC')->get();

        // Export the data using Laravel Excel
        return Excel::download(new CustomerDepositExport($records), 'CustomerAccount.xlsx');
    }

    public function FailerTransactionExcel(Request $request)
    {
        $searchValue = $request->input('search');
        $daterange = $request->input('daterange');
        $country_id = $request->input('country_id');
        $wallet_manager_id = $request->input('wallet_manager_id');

        // Get the current user's parent or merchant ID
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;


        $query = ExcelTransaction::query()
            ->where('excel_transactions.parent_id', $parent_id)
            ->whereNotNull('excel_transactions.remarks')
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id')
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($searchValue) {
                $q->where('excel_transactions.first_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.comment', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.tel_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('countries.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('wallet_managers.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('submitter.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('merchant.name', 'like', '%' . $searchValue . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dates = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay()->format('Y-m-d H:i:s');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay()->addDay()->format('Y-m-d H:i:s');
                    $q->whereBetween('excel_transactions.created_at', [$start_date, $end_date]);
                }
            })->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select(
                'excel_transactions.*',
                'submitter.name as submitted_by',
                'approver.name as approved_by',
                'approver_2.name as approver_name',
                'merchant.name as merchant_by',
                'countries.name as country_name',
                'wallet_managers.name as wallet_name',
                'transactions.status as transaction_status'
            );

        // Get the filtered records
        $records = $query->orderBy('created_at', 'DESC')->get();


        // Build the query with filters
        // $query = ExcelTransaction::query()
        // ->where('excel_transactions.parent_id', $parent_id) // Specify table for parent_id
        // ->whereNotNull('excel_transactions.remarks') // Specify table for remarks
        // ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
        // ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
        // ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id') 
        // ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
        // ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
        // ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
        // ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id');


        // // Apply search filter
        // if (!empty($searchValue)) {
        //     $query->where(function ($q) use ($searchValue) {
        //         $q->where('excel_transactions.first_name', 'like', '%' . $searchValue . '%')
        //         ->orWhere('excel_transactions.comment', 'like', '%' . $searchValue . '%')
        //         ->orWhere('excel_transactions.tel_number', 'like', '%' . $searchValue . '%')
        //         ->orWhere('excel_transactions.amount', 'like', '%' . $searchValue . '%')
        //         ->orWhere('excel_transactions.name', 'like', '%' . $searchValue . '%')
        //         ->orWhere('countries.name', 'like', '%' . $searchValue . '%')
        //         ->orWhere('wallet_managers.name', 'like', '%' . $searchValue . '%')
        //         ->orWhere('submitter.name', 'like', '%' . $searchValue . '%')
        //         ->orWhere('approver.name', 'like', '%' . $searchValue . '%');
        //     });
        // }

        // // Apply date range filter
        // if (!empty($daterange)) {
        //     $dates = explode(' - ', $daterange);
        //     $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay()->format('Y-m-d H:i:s');
        //     $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay()->addDay()->format('Y-m-d H:i:s');
        //     $query->whereBetween('excel_transactions.created_at', [$start_date, $end_date]);
        // }

        // // Select fields for export
        // $query->select('excel_transactions.*', 
        //     'submitter.name as submitted_by', 
        //     'approver.name as approved_by', 
        //     'approver_2.name as approver_name', 
        //     'merchant.name as merchant_by', 
        //     'countries.name as country_name', 
        //     'wallet_managers.name as wallet_name',
        //     'transactions.status as transaction_status');

        // // Get the filtered records
        // $records = $query->orderBy('created_at','DESC')->get();

        return Excel::download(new FailerTransactionExport($records), 'FailureTransaction.xlsx');
    }

    public function SuccessTransactionExcel(Request $request)
    {
        $searchValue = $request->input('search');
        $daterange = $request->input('daterange');
        $country_id = $request->input('country_id');
        $wallet_manager_id = $request->input('wallet_manager_id');
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;

        // Build the query with filters
        $query = ExcelTransaction::query()
            ->where('excel_transactions.parent_id', $parent_id)
            ->where('transactions.status', 1)
            ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
            ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
            ->leftJoin('users as approver_2', 'excel_transactions.approver_id', '=', 'approver_2.id')
            ->leftJoin('users as merchant', 'excel_transactions.approved_by_merchant', '=', 'merchant.id')
            ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
            ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
            ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
            ->where(function ($q) use ($searchValue) {
                $q->where('excel_transactions.first_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.comment', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.tel_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.amount', 'like', '%' . $searchValue . '%')
                    ->orWhere('excel_transactions.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('submitter.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('approver_2.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('merchant.name', 'like', '%' . $searchValue . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dates = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay()->format('Y-m-d H:i:s');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay()->addDay()->format('Y-m-d H:i:s');
                    $q->whereBetween('excel_transactions.created_at', [$start_date, $end_date]);
                }
            })
            ->where(function ($q) use ($country_id) {
                if ($country_id) {
                    $q->where('excel_transactions.country_id', $country_id);
                }
            })->where(function ($q) use ($wallet_manager_id) {
                if ($wallet_manager_id) {
                    $q->where('excel_transactions.wallet_manager_id', $wallet_manager_id);
                }
            })
            ->select(
                'excel_transactions.*',
                'submitter.name as submitted_by',
                'approver.name as approved_by',
                'approver_2.name as approver_name',
                'merchant.name as merchant_by',
                'countries.name as country_name',
                'wallet_managers.name as wallet_name',
                'transactions.status as transaction_status'
            );

        // Get the filtered records
        $records = $query->orderBy('created_at', 'DESC')->get();

        // Process records for export
        // Generate and return the Excel file
        return Excel::download(new SuccessTransactionExport($records), 'SuccessTransaction.xlsx');
    }
    public function OperationOfMonthExcel(Request $request)
    {
        $searchValue = $request->input('search');
        $daterange = $request->input('daterange');
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $currentMonth = Carbon::now()->format('Y-m');

        // Build the query with filters
        $query = UploadedExcel::query()
            ->leftJoin('users', 'uploaded_excels.user_id', '=', 'users.id')
            ->whereRaw("DATE_FORMAT(uploaded_excels.created_at, '%Y-%m') = ?", [$currentMonth])
            ->where('uploaded_excels.parent_id', $parent_id)
            ->whereIn('uploaded_excels.status', [1, 3, 4, 5, 6])
            ->where(function ($q) use ($searchValue) {
                $q->where('uploaded_excels.reference_id', 'like', '%' . $searchValue . '%');
            })
            ->where(function ($q) use ($daterange) {
                if ($daterange) {
                    $dates = explode(' - ', $daterange);
                    $start_date = Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay()->format('Y-m-d H:i:s');
                    $end_date = Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay()->addDay()->format('Y-m-d H:i:s');
                    $q->whereBetween('uploaded_excels.created_at', [$start_date, $end_date]);
                }
            })
            ->select(
                'uploaded_excels.*',
                'users.name as name',
                'users.email as email',
                'users.user_type as user_type'
            );
        $records = $query->orderBy('created_at', 'DESC')->get();
        // Generate and return the Excel file
        return Excel::download(new OperationOfMonthExport($records), 'OperationOfMonth.xlsx');
    }

    function calculateFees($amount, $parent_id, $type)
    {
        $feeapply = FeeApply::where('userId', $parent_id)
            ->where('transaction_type', $type)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if ($feeapply) {
            return ($feeapply->fee_type == 1) ? $feeapply->fee_amount : number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
        }

        // Default transaction fee if custom fee is not available
        $trans_fees = Transactionfee::where('transaction_type', $type)
            ->where('min_amount', '<=', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if ($trans_fees) {
            return ($trans_fees->fee_type == 1) ? $trans_fees->fee_amount : number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
        }

        return 0;
    }

    function getTransStatus()
    {

        $postData = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
            <ns:get_trans_status xmlns:ns="http://ws.mfsafrica.com">
            <ns:login>
            <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
            <ns:password>' . CORPORATEPASS . '</ns:password>
            </ns:login>
            <ns:trans_id>166086648047958528</ns:trans_id>
            </ns:get_trans_status>
            </soap:Body>
            </soap:Envelope>';
        $getResponse = $this->sendCurlRequest($postData, 'urn:get_trans_status');

        $xml12 = new SimpleXMLElement($getResponse);
        echo '<pre>';
        print_r($getResponse);
        exit;

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

    /* public function paymentLogin(Request $request)
    {

        $pageTitle = "Login";
        $data = $request->all();
        $orderId = $request->query('orderId');
        $merchantId = $request->query('merchantId');
        if (!$orderId && !$merchantId) {
            return response()->json(['error' => 'Enter Merchant Id and Order ID is required'], 400);
        }
        $getMerchantId = User::where('merchantKey', $data['merchantId'])->first();
        if (!$getMerchantId) {
            print_r('Merchant ID does not exist');
            die;
        }
        if ($request->isMethod('post')) {

            $validate_data = [
                'phoneNumber' => 'required',
            ];

            $customMessages = [
                'phoneNumber.required' => __('Phone number field can\'t be left blank'),
            ];

            $validator = Validator::make($data, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/register')->withInput()->withErrors($messages);
            }

            $phone = $data['phoneNumber'];

            $userInfo = User::where("phone", $phone)->where("user_type", "User")->count();
            if ($userInfo == 0) {
                Session::put('error_message', __('Your mobile number is not registered'));
                return redirect('/payment-login?merchantId=' . $data['merchantId']);
            }
            //session()->put('user_id_payment', $data['id']);
            session()->put('merchant_id_payment', $data['merchantId']);
            session()->put('order_id_payment', $data['orderId']);
            $encMobile = base64_encode($this->encryptContent($phone));
            return Redirect::to('/verify-otp-external/' . $encMobile);
        }
        return view('dashboard.payment-login', ['pageTitle' => $pageTitle]);
    } */

    /* public function verifyOtpExternal($slug)
    {
        $pageTitle = 'Verify Otp';
        $decode_string = base64_decode($slug);
        $phone = $this->decryptContent($decode_string);
        $input = Input::all();
        if (!empty($input)) {

            $otp1 = $input['otp1'];
            $otp2 = $input['otp2'];
            $otp3 = $input['otp3'];
            $otp4 = $input['otp4'];
            $otp5 = $input['otp5'];
            $otp6 = $input['otp6'];

            $otpCode = $otp1 . $otp2 . $otp3 . $otp4 . $otp5 . $otp6;
            if ($otpCode != '111111') {
                Session::put('error_message', __('Please provide a valid otp'));
                return Redirect::to('/verify-otp-external/' . $slug);
            }

            $userInfo = User::where("phone", $phone)
                ->where("user_type", "!=", "")
                ->first();

            if (empty($userInfo)) {
                Session::put('error_message', __('Phone number does not exist'));
                return redirect('/payment-login?merchantId=' . Session::get('merchant_id_payment'));
            }

            if ($userInfo->is_verify == 0) {
                Session::put('error_message', __('Your account might have been temporarily disabled. Please contact us for more details.'));
                return Redirect::to('/login');
            }

            Session::put('success_message', __('Otp verification has been successful.'));
            session()->put('user_id_payment', $userInfo->id);
            session()->put('user_name_payment', $userInfo->name);
            return Redirect::to('/payment?merchantId=' . Session::get('merchant_id_payment') . '&orderId=' . Session::get('order_id_payment'));

        }
        return view('dashboard.verify-otp-external', ['title' => $pageTitle, 'slug' => $slug, 'merchant' => Session::get('merchant_id_payment')]);
    } */

    public function createConnection(Request $request)
    {
        $orderId = $request->post('orderId');
        $merchantKey = $request->post('merchantId');

        if (!$orderId || strlen($orderId) > 20) {
            return response()->json(['error' => 'Invalid or too long order ID. Maximum length is 20 characters.'], 400);
        }
        if (!$orderId || !$merchantKey) {
            return response()->json(['error' => 'Enter Merchant Id and Order ID is required'], 400);
        }
        return response()->json(['status' => "SUCCESS", 'initiateLink' => url('/') . "/initPayment?merchantId=$merchantKey&orderId=" . $orderId], 200);
    }
    public function initPayment(Request $request)
    {
        $orderId = $request->query('orderId');
        $merchantKey = $request->query('merchantId');
        if (!$orderId || strlen($orderId) > 20) {
            return response()->json(['error' => 'Invalid or too long order ID. Maximum length is 20 characters.'], 400);
        }
        if (!$orderId || !$merchantKey) {
            return response()->json(['error' => 'Enter Merchant Id and Order ID is required'], 400);
        }

        $checkOrderId = Transaction::where('orderId', $orderId)->first();
        if ($checkOrderId) {
            return response()->json(['error' => 'Please enter different order Id.'], 400);
        }

        $getMerchantData = User::where('merchantKey', $merchantKey)->first();
        session()->put('merchant_id_payment', $merchantKey);
        session()->put('order_id_payment', $orderId);
        $userId = $getMerchantData->id;
        if (!$getMerchantData) {
            Session::put('error_message', __('Merchant ID does not exist'));
            return Redirect::to("/initPayment?merchantId=$merchantKey&orderId=" . $orderId);
        }
        $input = $request->all();
        if ($request->isMethod('post')) {
            if (isset($input['type']) && $input['type'] == 'GIMAC') {
                $validate_data = [
                    'country_id' => 'required',
                    'wallet_manager_id' => 'required',
                    'firstName' => 'required',
                    'name' => 'required',
                    'phone' => 'required',
                    'amount' => 'required|numeric|min:1|max:99999999',
                ];

                $customMessages = [
                    'firstName.required' => __('First name field can\'t be left blank'),
                    'name.required' => __('Last name field can\'t be left blank'),
                    'amount.required' => __('Amount field can\'t be left blank'),
                    'country_id.required' => __('Country field can\'t be left blank'),
                    'wallet_manager_id.required' => __('Wallet manager field can\'t be left blank'),
                ];
            }

            if (isset($input['type']) && $input['type'] == 'BDA') {
                $validate_data = [
                    'newBeneficiary' => ['required', 'max:50'],
                    'iban' => ['required', 'regex:/^[a-zA-Z0-9]+$/', 'min:24', 'max:30'],
                    'reason' => 'required',
                    'amountB' => 'required|numeric|min:1|max:99999999',
                ];

                $customMessages = [
                    'newBeneficiary.required' => __('Beneficiary can\'t be left blank'),
                    'iban.required' => __('Iban field can\'t be left blank'),
                    'iban.min' => __('Iban min length is 24'),
                    'iban.max' => __('Iban max length is 30'),
                    'reason.required' => __('Reason field can\'t be left blank'),
                    'amountB.required' => __('Amount field can\'t be left blank'),
                    'amountB.min' => __('Amount must be at least 1'),
                    'amountB.max' => __('Amount maximum 99999999'),
                ];
            }
            if (isset($input['type']) && $input['type'] == 'ONAFRIQ') {
                $validate_data = [
                    'amountO' => 'required|numeric|min:500|max:1500000',
                    'phoneNo' => 'required',
                    'country_id' => 'required',
                    'firstNameO' => 'required',
                    'lastNameO' => 'required',
                    'walletManager' => 'required',
                    'senderCountry' => 'required',
                    'phoneNoS' => 'required',
                    'senderName' => 'required',
                    'senderSurname' => 'required',
                    'senderAddress' => 'required_if:country_id,ML,SN',
                    'senderIdType' => 'required_if:country_id,ML,SN',
                    'senderIdNumber' => 'required_if:country_id,ML,SN',
                    'senderDob' => 'required_if:country_id,ML,SN,BF',
                ];

                $customMessages = [
                    'country_id.required' => __('Recipient Country field can\'t be left blank'),
                    'firstNameO.required' => __('Recipient First Name field can\'t be left blank'),
                    'lastNameO.required' => __('Recipient Surname field can\'t be left blank'),
                    'africamount.required' => __('Amount field can\'t be left blank'),
                    'africamount.min' => __('The amount must be at least 500'),
                    'africamount.max' => __('The amount maximum 1500000'),
                    'phoneNo.required' => __('Recipient Phone Number field can\'t be left blank'),
                    'walletManager.required' => __('Wallet Manager field can\'t be left blank'),

                    'senderCountry.required' => __('Sender Country field can\'t be left blank'),
                    'phoneNoS.required' => __('Sender Phone Number field can\'t be left blank'),
                    'senderName.required' => __('Sender Name field can\'t be left blank'),
                    'senderSurname.required' => __('Sender Surname field can\'t be left blank'),
                    'senderAddress.required' => __('Sender Address field can\'t be left blank'),
                    'senderIdType.required' => __('Sender Id Type field can\'t be left blank'),
                    'senderIdNumber.required' => __('Sender Id Number field can\'t be left blank'),
                    'senderDob.required' => __('Sender Dob field can\'t be left blank'),
                ];
            }

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                // $messages = $validator->messages();
                $errorMessage = $validator->errors()->first();
                Session::put('error_message', $errorMessage);
                return Redirect::to('/initPayment?merchantId=' . $merchantKey . '&orderId=' . $orderId);
            }

            $refrence_id = time() . rand();
            $certificate = public_path("MTN Cameroon Issuing CA1.crt");
            $client = new Client([
                'verify' => $certificate,
            ]);

            if ($input['type'] == 'GIMAC') {
                try {
                    // $total_fees = $this->calculateFees($input['amount'], $userId, 'Money Transfer Via GIMAC');
                    $tomember_id = $input['wallet_manager_id'];
                    $totalAmount = $input['amount'];

                    /* if ($getMerchantData->wallet_balance < $totalAmount) {
                        Session::put('error_message', 'Not sufficient balance.');
                        return Redirect::to('/initPayment?merchantId=' . $merchantKey . '&orderId=' . $orderId);
                    } */

                    $tomemberData = WalletManager::where('id', $tomember_id)->first();
                    if (!empty($tomemberData)) {
                        $tomember = $tomemberData->tomember;
                    }

                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => $userId,
                        'parent_id' => $userId,
                        'excel' => 'External Gimac',
                        'reference_id' => $refrence_id,
                        'no_of_records' => 1,
                        'totat_amount' => $totalAmount,
                        'type' => 1,
                        'status' => 5,
                        'total_fees' => 0,
                        'approved_by' => $userId,
                        'remarks' => $input['comment']
                    ]);

                    if (!empty($input['first_name']) && !empty($input['name'])) {
                        $first = $input['first_name'];
                        $name = $input['name'];
                    } else {
                        $first = $getMerchantData ? $getMerchantData->name : '';
                        $name = '-';
                    }

                    $excelData = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => $userId,
                        'submitted_by' => 0,
                        'first_name' => $first,
                        'name' => $name,
                        'comment' => $input['comment'],
                        'country_id' => $input['country_id'],
                        'wallet_manager_id' => $tomember_id,
                        'tel_number' => $input['phone'],
                        'amount' => $totalAmount,
                        'fees' => 0,
                        'bdastatus' => "GIMAC"
                    ]);

                    $dateString = date('d-m-Y H:i:s');
                    $format = 'd-m-Y H:i:s';
                    $dateTime = DateTime::createFromFormat($format, $dateString);
                    $timestamp = $dateTime->getTimestamp();

                    $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                    $next_issuertrxref = ($last_record != "") ? $last_record + 1 : '140071';
                    $data = [
                        'createtime' => $timestamp,
                        'intent' => 'mobile_transfer',
                        'walletsource' => $input['phone'],
                        'walletdestination' => $getMerchantData->phone,
                        'issuertrxref' => $next_issuertrxref,
                        'amount' => $totalAmount,
                        'currency' => '950',
                        'description' => 'money transfer',
                        'tomember' => $tomember,
                    ];

                    Log::info($data);
                    $accessToken = '';

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
                    $response = $client->request('POST', env('GIMAC_TOKEN_URL'), $options);
                    $body = $response->getBody()->getContents();
                    $jsonResponse = json_decode($body);
                    $accessToken = $jsonResponse->access_token;



                    $response = $client->request('POST', env('GIMAC_PAYMENT_URL'), [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => "Bearer $accessToken"
                        ],
                        'json' => $data,
                    ]);

                    $body = $response->getBody()->getContents();
                    $jsonResponse2 = json_decode($body);
                    $statusCode = $response->getStatusCode();

                    if ($statusCode == 200) {
                        Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                        $tomember = $jsonResponse2->tomember;
                        $acquirertrxref = $jsonResponse2->acquirertrxref ?? 0;
                        $issuertrxref = $jsonResponse2->issuertrxref;
                        $state = $jsonResponse2->state;
                        $rejectedStatus = '';
                        // $status = $state == 'ACCEPTED' ? 1 : 2;
                        $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));


                        if ($state == 'REJECTED') {
                            $rejectedStatus = $jsonResponse2->rejectMessage;
                            ExcelTransaction::where('id', $excelData->id)->update(['remarks' => $rejectedStatus]);
                            Session::put('error_message', $rejectedStatus);
                            return Redirect::to('/payment-return-status');
                        }
                        $vouchercode = $jsonResponse2->vouchercode;
                        $trans_id = time();
                        $refrence_id = time();
                        $trans = new Transaction([
                            'user_id' => 0,
                            'receiver_id' => $getMerchantData->id,
                            'receiver_mobile' => $input['phone'],
                            'amount' => $totalAmount,
                            'amount_value' => $totalAmount,
                            'transaction_amount' => 0,
                            'total_amount' => $totalAmount,
                            'trans_type' => 1,
                            'excel_trans_id' => $excelData->id,
                            'payment_mode' => 'External',
                            'status' => $status,
                            'refrence_id' => $issuertrxref,
                            'billing_description' => 'Fund Transfer-' . $refrence_id,
                            'tomember' => $tomember,
                            'acquirertrxref' => $acquirertrxref,
                            'issuertrxref' => $issuertrxref,
                            'vouchercode' => $vouchercode,
                            'transactionType' => 'SWAPTOCEMAC',
                            'orderId' => $orderId,
                            'walletsource' => $input['phone'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save();

                        if ($state == 'ACCEPTED') {
                            $opening_balance_senderG = $getMerchantData->wallet_balance;

                            $credit = new TransactionLedger([
                                'user_id' => $getMerchantData->id,
                                'opening_balance' => $opening_balance_senderG,
                                'amount' => $totalAmount,
                                'fees' => 0,
                                'actual_amount' => $totalAmount,
                                'type' => 1,
                                'excelTransId' => $excelData->id,
                                'trans_id' => $trans->id,
                                'payment_mode' => 'External',
                                'closing_balance' => $opening_balance_senderG + $totalAmount,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $credit->save();
                            DB::table('users')->where('id', $userId)->increment('wallet_balance', $totalAmount);
                            Session::put('success_message', __('Payment successfully'));
                            return Redirect::to("/payment-return-status");
                        } elseif ($state == 'PENDING') {
                            Session::put('success_message', __('Payment successfully'));
                            return Redirect::to("/payment-return-status");
                        }
                        Session::put('error_message', __('Payment failed'));
                        return Redirect::to("/payment-return-status");
                    }
                } catch (\Exception $e) {
                    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                        $response = $e->getResponse();
                        $body = $response->getBody();
                        $contents = $body->getContents();
                        // Now, $contents contains the response body
                        $jsonResponse = json_decode($contents, true);
                        $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                        $next_issuertrxref = ($last_record != "") ? $last_record + 1 : '140071';
                        if ($jsonResponse && isset($jsonResponse['error_description'])) {
                            $errorDescription = $jsonResponse['error_description'];
                            Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);
                            ExcelTransaction::where('id', $excelData->id)->update(['remarks' => $errorDescription]);
                            Session::put('error_message', $errorDescription);
                            return Redirect::to("/payment-return-status");
                        } else {
                            Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => __('Unable to extract error_description')]);
                            ExcelTransaction::where('id', $excelData->id)->update(['remarks' => __('Unable to extract error_description')]);
                            Session::put('error_message', __('Unable to extract error_description'));
                            return Redirect::to("/payment-return-status");
                        }
                    } else {
                        ExcelTransaction::where('id', $excelData->id)->update(['remarks' => __('Gimac Server Error')]);
                        Session::put('error_message', 'Gimac Server Error');
                        return Redirect::to("/payment-return-status");
                    }
                }
                /* } else {
                    Session::put('error_message', __('Hold balance not remaining your wallet'));
                    return Redirect::to('/pending-approvals');
                } */
                print_r($statusCode);
                die;
            } elseif ($input['type'] == 'ONAFRIQ') {
                try {
                    $totalAmount = $input['amountO'];
                    /* if ($getMerchantData->wallet_balance < $totalAmount) {
                        Session::put('error_message', 'Not sufficient balance.');
                        return Redirect::to('/initPayment?merchantId=' . $merchantKey . '&orderId=' . $orderId);
                    } */

                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => $userId,
                        'parent_id' => '',
                        'excel' => 'External ONAFRIQ',
                        'reference_id' => $refrence_id,
                        'no_of_records' => 1,
                        'totat_amount' => $totalAmount,
                        'type' => 1,
                        'status' => 5,
                        'approved_by' => $userId,
                        'total_fees' => 0,
                        'remarks' => ''
                    ]);

                    $excelT = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => $getMerchantData->id,
                        'submitted_by' => 0,
                        'first_name' => "---", //$userName,
                        'name' => '',
                        'country_id' => '',
                        'wallet_manager_id' => '',
                        'tel_number' => '',
                        'amount' => $totalAmount,
                        'fees' => 0,
                        'comment' => '',
                        'bdastatus' => 'ONAFRIQ'
                    ]);

                    $onafriqaDataA = new OnafriqaData();
                    $onafriqaDataA->amount = $totalAmount;
                    $onafriqaDataA->recipientMsisdn = $input['phoneNo'];
                    $onafriqaDataA->walletManager = $input['walletManager'];
                    $onafriqaDataA->recipientCountry = $input['country_id'];
                    $onafriqaDataA->recipientSurname = $input['firstNameO'];
                    $onafriqaDataA->recipientName = $input['lastNameO'];
                    $onafriqaDataA->senderCountry = $input['senderCountry'];
                    $onafriqaDataA->senderMsisdn = $input['phoneNoS'];
                    $onafriqaDataA->senderName = $input['senderName'];
                    $onafriqaDataA->senderSurname = $input['senderSurname'];
                    $onafriqaDataA->senderAddress = $input['senderAddress'] ?? "";
                    $onafriqaDataA->senderDob = $input['senderDob'] ?? "";
                    $onafriqaDataA->senderIdType = $input['senderIdType'] ?? "";
                    $onafriqaDataA->senderIdNumber = $input['senderIdNumber'] ?? "";
                    $onafriqaDataA->recipientCurrency = 'XOF';
                    $onafriqaDataA->thirdPartyTransactionId = $this->generateAndCheckUnique();
                    $onafriqaDataA->status = 'pending';
                    $onafriqaDataA->excelTransId = $excelT->id;
                    $onafriqaDataA->userId = $getMerchantData->id;
                    $onafriqaDataA->fromMSISDN = $input['phoneNoS'];
                    $onafriqaDataA->save();

                    $total_amount = $input['amountO'];
                    $postData = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                <soap:Body>
                                <ns:account_request xmlns:ns="http://ws.mfsafrica.com">
                                <ns:login>
                                <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                                <ns:password>' . CORPORATEPASS . '</ns:password>
                                </ns:login>
                                <ns:to_country>' . $input['country_id'] . '</ns:to_country>
                                <ns:msisdn>' . $input['phoneNo'] . '</ns:msisdn>
                                </ns:account_request>
                                </soap:Body>
                                </soap:Envelope>';
                    $getResponse = $this->sendCurlRequest($postData, 'urn:account_request');
                    Log::info("Request => $postData");
                    Log::info("Response => $getResponse");
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
                        $getOnfi = OnafriqaData::where('excelTransId', $excelT->id)->where('status', 'pending')->first();
                        $postDataRemit = '
                            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                <soap:Body>
                                    <ns:mm_remit_log xmlns:ns="http://ws.mfsafrica.com">
                                    <ns:login>
                                        <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code> 
                                        <ns:password>' . CORPORATEPASS . '</ns:password> 
                                    </ns:login>
                                    <ns:receive_amount>
                                        <ns:amount>' . $getOnfi->amount . '</ns:amount> 
                                        <ns:currency_code>' . $getOnfi->recipientCurrency . '</ns:currency_code> 
                                    </ns:receive_amount>
                                    <ns:sender>
                                        <ns:address>' . ($getOnfi->senderAddress ?: "") . '</ns:address>
                                        <ns:city>string</ns:city>
                                        <ns:date_of_birth>' . ($getOnfi->senderDob ?: "") . '</ns:date_of_birth>
                                        <ns:document>
                                        <ns:id_country>string</ns:id_country>
                                        <ns:id_expiry>string</ns:id_expiry>
                                        <ns:id_number>' . ($getOnfi->senderIdNumber ?: "") . '</ns:id_number>
                                        <ns:id_type>' . ($getOnfi->senderIdType ?: "") . '</ns:id_type>
                                        </ns:document>
                                        <ns:email>string</ns:email>
                                        <ns:from_country>' . $getOnfi->senderCountry . '</ns:from_country>
                                        <ns:msisdn>' . $getOnfi->senderMsisdn . '</ns:msisdn>
                                        <ns:name>' . $getOnfi->senderName . '</ns:name>
                                        <ns:place_of_birth>string</ns:place_of_birth>
                                        <ns:postal_code>string</ns:postal_code>
                                        <ns:state>string</ns:state>
                                        <ns:surname>' . $getOnfi->senderSurname . '</ns:surname>
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
                                        <ns:msisdn>' . $getOnfi->recipientMsisdn . '</ns:msisdn>
                                        <ns:name>' . $getOnfi->recipientName . '</ns:name>
                                        <ns:postal_code>string</ns:postal_code>
                                        <ns:state>string</ns:state>
                                        <ns:status>
                                        <ns:status_code>string</ns:status_code>
                                        </ns:status>
                                        <ns:surname>' . $getOnfi->recipientSurname . '</ns:surname>
                                        <ns:to_country>' . $getOnfi->recipientCountry . '</ns:to_country>
                                    </ns:recipient>
                                    <ns:third_party_trans_id>' . $getOnfi->thirdPartyTransactionId . '</ns:third_party_trans_id>
                                    <ns:reference>string</ns:reference>
                                    <ns:source_of_funds>string</ns:source_of_funds>
                                    <ns:purpose_of_transfer>string</ns:purpose_of_transfer>
                                    </ns:mm_remit_log>
                                </soap:Body>
                            </soap:Envelope>';

                        $getResponseRemit = $this->sendCurlRequest($postDataRemit, 'urn:mm_remit_log');
                        Log::info("Request => $postDataRemit");
                        Log::info("Response => $getResponseRemit");
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
                        $status1 = $xml1->xpath('//' . $axNamespace1 . ':status')[0];
                        $mfs_trans_id = (string) $xml1->xpath('//' . $axNamespace1 . ':mfs_trans_id')[0];
                        $partner_code = (string) $xml1->xpath('//' . $axNamespace1 . ':partner_code')[0];
                        $statusCode1 = (string) $status1->xpath('' . $axNamespace1 . ':code/' . $axNamespace1 . ':status_code')[0];
                        $statusMessage = (string) $status1->xpath('ax21:message')[0];
                        $receiveAmount = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':amount')[0];
                        $currencyCode = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':currency_code')[0];


                        if ($statusCode1 == "MR104" && $statusMessage == "Log Success" && $mfs_trans_id != "") {
                            $postDataTrans = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
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
                            Log::info("Request => $postDataTrans");
                            Log::info("Response => $getResponseTrans");
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
                                    'user_id' => 0,
                                    'receiver_id' => $getMerchantData->id, //$getReceiver['id'],
                                    'receiver_mobile' => '', //$getReceiver['phone'],
                                    'amount' => $totalAmount,
                                    'amount_value' => $totalAmount,
                                    'transaction_amount' => 0,
                                    'total_amount' => $totalAmount,
                                    'trans_type' => 1,
                                    'excel_trans_id' => $excelT->id,
                                    'payment_mode' => 'External',
                                    'status' => 1,
                                    'refrence_id' => '',
                                    'billing_description' => "Fund Transfer-$refrence_id",
                                    'tomember' => '',
                                    'acquirertrxref' => '',
                                    'issuertrxref' => '',
                                    'vouchercode' => '',
                                    'onafriq_bda_ids' => $getOnfi->id,
                                    'transactionType' => 'SWAPTOONAFRIQ',
                                    'orderId' => $orderId,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $trans->save();

                                $opening_balance_senderO = $getMerchantData->wallet_balance;
                                $credit = new TransactionLedger([
                                    'user_id' => $userId,
                                    'opening_balance' => $opening_balance_senderO,
                                    'amount' => $input['amountO'],
                                    'fees' => $total_fees,
                                    'actual_amount' => $total_amount,
                                    'type' => 1,
                                    'excelTransId' => $excelT->id,
                                    'trans_id' => $trans->id,
                                    'payment_mode' => 'External',
                                    'closing_balance' => $opening_balance_senderO + $total_amount,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $credit->save();
                                OnafriqaData::where('excelTransId', $excelT->id)->update(['transactionId' => $mfs_trans_id, 'status' => 'success', 'partnerCode' => $partner_code, 'userId' => $userId]);
                                ExcelTransaction::where('id', $excelTrans->id)->update(['approved_by' => $userId, 'approver_id' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                                DB::table('users')->where('id', $userId)->increment('wallet_balance', $totalAmount);
                                Session::put('success_message', __('Payment successfully'));
                                return Redirect::to("/payment-return-status?transactionId=$mfs_trans_id");
                            } else {
                                if ($statusCode2 == 'MR108' || $statusCode2 == 'MR103' || $statusCode2 == 'MR102') {
                                    $refrence_id = time();
                                    $trans = new Transaction([
                                        'user_id' => 0,
                                        'receiver_id' => $getMerchantData->id,
                                        'receiver_mobile' => '',
                                        'amount' => $totalAmount,
                                        'amount_value' => $totalAmount,
                                        'transaction_amount' => 0,
                                        'total_amount' => $totalAmount,
                                        'trans_type' => 1,
                                        'excel_trans_id' => $excelT->id,
                                        'payment_mode' => 'External',
                                        'status' => 2,
                                        'refrence_id' => '',
                                        'bda_status' => 5,
                                        'billing_description' => 'Fund Transfer-' . $refrence_id,
                                        'tomember' => '',
                                        'acquirertrxref' => '',
                                        'issuertrxref' => '',
                                        'vouchercode' => '',
                                        'onafriq_bda_ids' => $getOnfi->id,
                                        'transactionType' => 'SWAPTOONAFRIQ',
                                        'orderId' => $orderId,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $trans->save();

                                    /* $opening_balance_senderP = $getMerchantData->wallet_balance;

                                    $debit = new TransactionLedger([
                                        'user_id' => $userId,
                                        'opening_balance' => $opening_balance_senderP,
                                        'amount' => $totalAmount,
                                        'fees' => 0,
                                        'actual_amount' => $totalAmount,
                                        'type' => 2,
                                        'excelTransId' => $excelT->id,
                                        'trans_id' => $trans->id,
                                        'payment_mode' => 'External',
                                        'closing_balance' => $opening_balance_senderP - $totalAmount,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $debit->save(); */

                                    OnafriqaData::where('excelTransId', $excelTrans->id)->update(['transactionId' => $mfs_trans_id, 'partnerCode' => $partner_code]);
                                    Session::put('success_message', __('Payment Inprogress'));
                                    return Redirect::to("/payment-return-status?transactionId=$mfs_trans_id");
                                } else {
                                    ExcelTransaction::where('id', $excelT->id)->update(['remarks' => 'Subscriber not authorized to receive amount', 'approved_by' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                                    Session::put('error_message', __('Payment failed'));
                                    return Redirect::to('/payment-return-status');
                                }
                            }
                        } else {
                            ExcelTransaction::where('id', $excelT->id)->update(['remarks' => 'Transaction not execute', 'approved_by' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                            Session::put('error_message', __('Payment failed remit log not response success'));
                            return Redirect::to('/payment-return-status');
                        }
                    } else {
                        ExcelTransaction::where('id', $excelT->id)->update(['remarks' => 'Recipient phone number not active ', 'approved_by' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                        Session::put('error_message', __('Recipient phone number not active'));
                        return Redirect::to('/payment-return-status');
                    }
                } catch (\Exception $e) {
                    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                        $response = $e->getResponse();
                        $body = $response->getBody();
                        $contents = $body->getContents();
                        $jsonResponse = json_decode($contents, true);
                        if ($jsonResponse && isset($jsonResponse['error_description'])) {
                            $errorDescription = $jsonResponse['error_description'];
                            ExcelTransaction::where('id', $excelT->id)->update(['remarks' => $errorDescription]);
                            Session::put('error_message', $errorDescription);
                            return Redirect::to("/payment-return-status");
                        } else {
                            ExcelTransaction::where('id', $excelT->id)->update(['remarks' => __('Unable to extract error_description')]);
                            Session::put('error_message', __('Unable to extract error_description'));
                            return Redirect::to("/payment-return-status");
                        }
                    } else {
                        ExcelTransaction::where('id', $excelT->id)->update(['remarks' => __('ONAFRIQ Server Error')]);
                        Session::put('error_message', 'ONAFRIQ Server Error');
                        return Redirect::to("/payment-return-status");
                    }
                }
            } elseif ($input['type'] == 'BDA') {
                try {
                    $totalAmount = $input['amountB'];
                    /* if ($getMerchantData->wallet_balance < $totalAmount) {
                        Session::put('error_message', 'Not sufficient balance.');
                        return Redirect::to('/initPayment?merchantId=' . $merchantKey . '&orderId=' . $orderId);
                    } */
                    $country_id = 0;
                    $wallet_manager_id = 0;

                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => $userId,
                        'parent_id' => '',
                        'excel' => 'External BDA',
                        'reference_id' => $refrence_id,
                        'no_of_records' => 1,
                        'totat_amount' => $totalAmount,
                        'type' => 1,
                        'status' => 5,
                        'approved_by' => $userId,
                        'total_fees' => 0,
                        'remarks' => $input['reason']
                    ]);

                    $excelTrans = ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => $userId,
                        'submitted_by' => 0,
                        'first_name' => "~~~", //$userName,
                        'name' => '',
                        'country_id' => '',
                        'wallet_manager_id' => '',
                        'tel_number' => '',
                        'amount' => $totalAmount,
                        'fees' => 0,
                        'comment' => $input['reason'],
                        'bdastatus' => 'BDA'
                    ]);

                    $remittanceData = new RemittanceData();
                    $getLstNo = RemittanceData::orderBy('id', 'desc')->select('referenceLot')->first();
                    if (empty($getLstNo)) {
                        $refNoLo = 'SWAP9999';
                    } else {
                        preg_match('/([a-zA-Z]+)([0-9]+)/', $getLstNo->referenceLot, $matches);
                        $incrementedPart = (int) $matches[2] + 1;
                        $newReferenceLot = $matches[1] . $incrementedPart;
                        $refNoLo = $newReferenceLot;
                    }
                    $remittanceData->transactionId = $this->generateAndCheckUnique();
                    $remittanceData->product = 'SWAP';
                    $remittanceData->iban = $input['iban'];
                    $remittanceData->titleAccount = $input['newBeneficiary'];
                    $remittanceData->amount = $totalAmount;
                    $remittanceData->partnerreference = $this->generateString();
                    $remittanceData->reason = $input['reason'];
                    $remittanceData->userId = $getMerchantData->id;
                    $remittanceData->referenceLot = $refNoLo;
                    $remittanceData->type = 'bank_transfer';
                    $remittanceData->excel_id = $excelTrans->id;
                    $remittanceData->save();

                    $details = RemittanceData::where('id', $remittanceData->id)->first();
                    $url = env('BDA_PAYMENT_URL');
                    $data = [
                        'referenceLot' => $details->referenceLot,
                        'nombreVirement' => 1,
                        'montantTotal' => $details->amount,
                        'produit' => $details->product,
                        'virements' => [
                            [
                                'ibanCredit' => $details->iban,
                                'intituleCompte' => $details->titleAccount,
                                'montant' => $details->amount,
                                'referencePartenaire' => $details->partnerreference,
                                'motif' => $details->reason,
                                'typeVirement' => 'RTGS'
                            ]
                        ]
                    ];

                    $response = $client->request('POST', $url, [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'json' => $data,
                    ]);

                    $body = $response->getBody()->getContents();
                    $jsonResponse2 = json_decode($body);
                    $statusCode = $response->getStatusCode();
                    if ($statusCode == 200) {

                        $statut = $jsonResponse2->statut;
                        if ($statut === 'REJETE') {
                            RemittanceData::where('excel_id', $excelTrans->id)->update(['status' => $statut]);
                            ExcelTransaction::where('id', $excelTrans->id)->update(['remarks' => 'Rejected', 'approved_by' => $userId, 'approver_id' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                            Session::put('error_message', __('Payment Rejected'));
                            return Redirect::to('/payment-return-status');
                        }

                        if ($statut == 'EN_ATTENTE' || $statut == 'EN_ATTENTE_REGLEMENT') {
                            $refrence_id = time();

                            $trans = new Transaction([
                                'user_id' => 0,
                                'receiver_id' => $getMerchantData->id,
                                'receiver_mobile' => '',
                                'amount' => $totalAmount,
                                'amount_value' => $totalAmount,
                                'transaction_amount' => 0,
                                'total_amount' => $totalAmount,
                                'trans_type' => 1,
                                'excel_trans_id' => $excelTrans->id,
                                'payment_mode' => 'External',
                                'status' => 2,
                                'bda_status' => 2,
                                'refrence_id' => '',
                                'billing_description' => 'Fund Transfer-' . $refrence_id,
                                'tomember' => '',
                                'acquirertrxref' => '',
                                'issuertrxref' => '',
                                'vouchercode' => '',
                                'onafriq_bda_ids' => $details->id,
                                'transactionType' => 'SWAPTOBDA',
                                'orderId' => $orderId,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $trans->save();

                            /* $opening_balance_senderB = $userData->wallet_balance;
                            $credit = new TransactionLedger([
                                'user_id' => $userId,
                                'opening_balance' => $opening_balance_senderB,
                                'amount' => $input['amountB'],
                                'fees' => $total_fees,
                                'actual_amount' => $totalAmount,
                                'type' => 2,
                                'excelTransId' => $excelTrans->id,
                                'trans_id' => $trans->id,
                                'payment_mode' => 'External',
                                'closing_balance' => $opening_balance_senderB - $totalAmount,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $credit->save(); */

                            RemittanceData::where('excel_id', $excelTrans->id)->update(['status' => $statut]);
                            ExcelTransaction::where('id', $excelTrans->id)->update(['approved_by' => $userId, 'approver_id' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                            // DB::table('users')->where('id', $getMerchantData->id)->decrement('wallet_balance', $totalAmount);
                            Session::put('success_message', __('Payment Successfully'));
                            return Redirect::to('/payment-return-status');
                        } else {
                            Session::put('error_message', __('Payment Failed'));
                            return Redirect::to('/payment-return-status');
                        }
                    }
                    print_r('BDA');
                    die;
                } catch (\Exception $e) {
                    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                        $response = $e->getResponse();
                        $body = $response->getBody();
                        $contents = $body->getContents();
                        $jsonResponse = json_decode($contents, true);
                        if ($jsonResponse && isset($jsonResponse['error_description'])) {
                            $errorDescription = $jsonResponse['error_description'];
                            ExcelTransaction::where('id', $excelTrans->id)->update(['remarks' => $errorDescription]);
                            Session::put('error_message', $errorDescription);
                            return Redirect::to("/payment-return-status");
                        } else {
                            ExcelTransaction::where('id', $excelTrans->id)->update(['remarks' => __('Unable to extract error_description')]);
                            Session::put('error_message', __('Unable to extract error_description'));
                            return Redirect::to("/payment-return-status");
                        }
                    } else {
                        ExcelTransaction::where('id', $excelTrans->id)->update(['remarks' => __('BDA Server Error')]);
                        Session::put('error_message', 'BDA Server Error');
                        return Redirect::to("/payment-return-status");
                    }
                }
            }
        }
        $country_list = Country::orderBy('name', 'asc')->get();
        return view('dashboard.payment', ['country_list' => $country_list]);
    }
    public function paymentReturnStatus(Request $request)
    {
        $merchantId = session()->get('merchant_id_payment');
        $orderId = session()->get('order_id_payment');
        return view('dashboard.payment-return-status', ['merchant_id_payment' => $merchantId, 'order_id_payment' => $orderId]);
    }

    public function trackOrderTransaction(Request $request, $orderId)
    {

        $record = Transaction::where('orderId', $orderId)->orderBy('id', 'desc')->first();
        if (empty($record)) {
            return response()->json(['status' => 'success', 'message' => 'Order not found', 'data' => null]);
        }
        $record = [
            'Amount' => $record->amount,
            'Fees' => $record->transaction_amount,
            'Order Id' => $record->orderId,
            'Status' => $this->getStatusText($record->status) == "Completed" ? "Transaction Successful" : "Transaction " . $this->getStatusText($record->status),
        ];

        return response()->json(['status' => 'success', 'message' => 'Order track successful', 'data' => $record]);

    }


    function transComExternal()
    {
        $postDataTrans =
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Body>
                <ns:trans_com xmlns:ns="http://ws.mfsafrica.com">
                <ns:login>
                <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                <ns:password>' . CORPORATEPASS . '</ns:password>
                </ns:login>
                <ns:trans_id>165857261513183232</ns:trans_id>
                </ns:trans_com>
                </soap:Body>
                </soap:Envelope>';

        $getResponseTrans = $this->sendCurlRequest($postDataTrans, 'urn:trans_com');
        log::info($getResponseTrans);
        print_r($getResponseTrans);
        die;
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
        $statusCode2 = (string) $status2->xpath('' . $axNamespace2 . ':status_code')[0];
        return $statusCode2;
    }
    public function getOldRecord(Request $request)
    {
        $data = $request->all();
        if ($data['msisdn']) {

            $actionType = $data['actionType'];

            $record = ($actionType === 'sender') ? OnafriqaData::where('senderMsisdn', $data['msisdn'])->orderBy('id', 'desc')->first() : OnafriqaData::where('recipientMsisdn', $data['msisdn'])->orderBy('id', 'desc')->first();

            if ($record) {
                return response()->json(['status' => 'success', 'data' => $record]);
            } else {
                return response()->json(['status' => 'success', 'data' => null]);
            }
        }
    }












    public function getTransactionDetail(Request $request, $id, $user_id)
    {
        $userInfo = User::where('id', $user_id)->first();

        $user_role = $userInfo->user_type;

        $lang = Session::get('locale');



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

            if (isset($request->type) && $request->type == 'Crédit' || $request->type == 'Credit') {
                $transType = TransactionLedger::orderBy('id', 'desc')->where('excelTransId', $val->excel_trans_id)->first();
            } else {
                $transType = TransactionLedger::orderBy('id', 'asc')->where('excelTransId', $val->excel_trans_id)->first();
            }

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
                if (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "ONAFRIQ") {
                    $transArr['sender_phone'] = $onafriqData['senderMsisdn'] ? $onafriqData['senderMsisdn'] : $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = $onafriqData['recipientName'] ? $onafriqData['recipientName'] : '';
                    $transArr['receiver_phone'] = $onafriqData['recipientMsisdn'] ? $onafriqData['recipientMsisdn'] : $val->receiver_mobile;

                } elseif (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "BDA") {
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = 'BDA Transfer';
                    $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;

                } elseif (isset($getPaymentType->bdastatus) && $getPaymentType->bdastatus == "GIMAC") {
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = 'GIMAC Transfer';
                    $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;

                } elseif (isset($val->payment_mode) && $val->payment_mode == "Referral") {
                    $transArr['sender'] = 'Admin';
                    $transArr['sender_phone'] = '-';
                    $transArr['receiver'] = $this->getUserNameById(Auth::user()->id);
                    $transArr['receiver_phone'] = $this->getPhoneById(Auth::user()->id);
                } else {
                    $transArr['sender_phone'] = $this->getPhoneById($val->user_id);
                    $transArr['receiver'] = 'WALLET2WALLET';
                    $transArr['receiver_phone'] = $val->receiver_mobile != "" ? $val->receiver_mobile : 0;
                }

                //$transArr['receiver_phone'] = $receiverPhone ? $receiverPhone : $val->receiver_mobile;
                $transArr['receiver_id'] = $val->receiver_id;
                if (isset($val->payment_mode) && $val->payment_mode == "Referral") {
                    $transArr['trans_type'] = "Referral Bonus";
                } else {

                    $transArr['trans_type'] = $transType['type'] == 1 ? 'CREDIT' : ($transType['type'] == 2 ? 'DEBIT' : ($transType['type'] == 3 ? 'TOPUP' : '')); //$tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
                }
                //$transArr['trans_type'] = $tranType[$val->trans_type]; //1=Credit;2=Debit;3=topup
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



            if ($lang == 'fr') {
                if ($transArr['trans_type'] == 'Debit') {
                    $transArr['trans_type'] = 'Débit';
                } elseif ($transArr['trans_type'] == 'Credit') {
                    $transArr['trans_type'] = 'Crédit';
                } elseif ($transArr['trans_type'] == 'Topup') {
                    $transArr['trans_type'] = 'Recharge';
                } elseif ($transArr['trans_type'] == 'Request') {
                    $transArr['trans_type'] = 'Demande';
                }
            } elseif ($lang == 'en') {
                if ($transArr['trans_type'] == 'Debit') {
                    $transArr['trans_type'] = 'Debit';
                } elseif ($transArr['trans_type'] == 'Credit') {
                    $transArr['trans_type'] = 'Credit';
                } elseif (
                    $transArr['trans_type'] == 'Topup'
                    || $tranType[3] == '3'
                ) {
                    $transArr['trans_type'] = 'Topup';
                } elseif ($transArr['trans_type'] == 'Request') {
                    $transArr['trans_type'] = 'Request';
                }
            }



            global $tranStatus;


            if ($lang == 'fr') {
                $transArr['trans_status'] = ($request->type == 'Crédit' && $val->payment_mode != "Dépôt") ? 'Refund Completed' : $tranStatus[$val->status];
                if ($transArr['trans_status'] == 'Success') {
                    $transArr['trans_status'] = 'Succès';
                } elseif ($transArr['trans_status'] == 'Pending') {
                    $transArr['trans_status'] = 'En attente';
                } elseif ($transArr['trans_status'] == 'Failed') {
                    $transArr['trans_status'] = 'Échoué';
                } elseif ($transArr['trans_status'] == 'Reject') {
                    $transArr['trans_status'] = 'Rejeter';
                } elseif ($transArr['trans_status'] == 'Refund') {
                    $transArr['trans_status'] = 'Remboursement';
                } elseif ($transArr['trans_status'] == 'Refund Completed') {
                    $transArr['trans_status'] = 'Remboursement terminé';
                }
            } elseif ($lang == 'en') {
                // $transArr['trans_status'] = $request->type == 'Credit'  ? 'Refund Completed' : $tranStatus[$val->status];

                $transArr['trans_status'] = ($request->type == 'Credit' && $val->payment_mode != 'Deposit') ? 'Refund Completed' : $tranStatus[$val->status];
                if ($request->type == 'Credit' && $val->payment_mode == 'External') {
                    $transArr['trans_status'] = ($request->type == 'Credit' && $val->payment_mode != 'Deposit') ? 'External Credit' : $tranStatus[$val->status];
                }

                if ($transArr['trans_status'] == 'Success') {
                    $transArr['trans_status'] = 'Success';
                } elseif ($transArr['trans_status'] == 'Pending') {
                    $transArr['trans_status'] = 'Pending';
                } elseif ($transArr['trans_status'] == 'Failed') {
                    $transArr['trans_status'] = 'Failed';
                } elseif ($transArr['trans_status'] == 'Reject') {
                    $transArr['trans_status'] = 'Reject';
                } elseif ($transArr['trans_status'] == 'Refund') {
                    $transArr['trans_status'] = 'Refund';
                } elseif ($transArr['trans_status'] == 'Refund Completed') {
                    $transArr['trans_status'] = 'Refund Completed';
                }
            }

            //echo"<pre>";print_r($transArr['trans_status']);die;
            $transArr['beneficiary'] = $RemittanceData['titleAccount'] ?? 0;
            $transArr['iban'] = $RemittanceData['iban'] ?? 0;
            $transArr['reason'] = $RemittanceData['reason'] ?? 0;
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

    private function getUserNameById($user_id)
    {
        if ($user_id == 1) {
            $matchThese = ["admins.id" => $user_id];
            $user = DB::table('admins')->select('admins.username')->where($matchThese)->first();
            return $user->username ?? "";
        } else {
            $matchThese = ["users.id" => $user_id];
            $user = DB::table('users')->select('users.name')->where($matchThese)->first();
            return $user->name ?? "";
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

    public function checkGimacInquiry()
    {

        $certificate = public_path("MTN Cameroon Issuing CA1.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);

        $dateString = date('d-m-Y H:i:s');
        $format = 'd-m-Y H:i:s';
        $dateTime = DateTime::createFromFormat($format, $dateString);
        $timestamp = $dateTime->getTimestamp();

        /* $data = [
            "intent" => "account_inquiry",
            "dstaccounts" => [["iden" => "CM2110029260110131825310134", "type" => "ACCOUNT"]],
            "issuertrxref" => "141929",
            "tomember" => "10029",
        ]; */
        /* $data = [
            'intent' => 'account_inquiry',
            'walletdestination' => "1101318253101",
            'issuertrxref' => "141927",
            'tomember' => "10029",
        ]; */
        $accessToken = '';
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
        // https://10.20.36.212:8443/stdendpointfacade/v1/external/oauth/token

        $response1 = $client->request('POST', 'https://10.20.16.212:8443/stdendpointfacade/v1/external/oauth/token', $options);
        $body = $response1->getBody()->getContents();
        $jsonResponse = json_decode($body);

        $accessToken = $jsonResponse->access_token;
        dd($accessToken);

        $data = [
            "intent" => "pp_reload",
            "createtime" => 1490134050,
            "walletsource" => "237699947943",
            "dstaccounts" => [
                [
                    "iden" => "FR712348165434161",
                    "type" => "CARD"
                ]
            ],
            "issuertrxref" => uniqid(),
            "amount" => 150,
            "currency" => "950",
            "tomember" => "16001",
        ];

        Log::info('Send Req Info', ['request' => $data]);

        $response = $client->request('POST', 'https://10.20.36.212:8443/stdendpointfacade/v1/external/payment/send', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $accessToken"
            ],
            'json' => $data,
        ]);

        $body = $response->getBody()->getContents();
        $jsonResponse2 = json_decode($body);
        $statusCode = $response->getStatusCode();

        dd($body);

        /* $data = [
            "intent" => "wallet_to_account",
            "createtime" => 1490109044,
            "walletsource" => "7909202",
            "dstaccounts" => [
                [
                    "iden" => "CI93CI0420128006366950250104",
                    "type" => "ACCOUNT"
                ]
            ],
            "issuertrxref" => "141910",
            "amount" => 500,
            "currency" => "950",
            "tomember" => "10029"
        ]; */

        /*  $data = [
             "intent" => "inc_acc_remit",
             "createtime" => $timestamp,
             "issuertrxref" => "141969",
             "sendermobile" => "7909202",
             "dstaccounts" => [
                 [
                     "iden" => "CM2110029260110131825310134",
                     "type" => "ACCOUNT"
                 ]
             ],
             "tomember" => "10029",
             "amount" => 500,
             "currency" => "950",
             "sendercustomerdata" => [
                 "firstname" => "Jhon",
             ],
             "receivercustomerdata" => [
                 "firstname" => "Ali",
             ]
         ]; */

        /* $data = [
            "intent" => "outg_wal_remit",
            "createtime" => $timestamp,
            "walletsource" => "7909202",
            "walletdestination" => "01111318253101",
            "issuertrxref" => "141916",
            "amount" => 500,
            "currency" => "950",
            "tomember" => "10029",
            "sendercustomerdata" => [
                "firstname" => "Jhon",
                "secondname" => 'Test',
                "phone" => 'SDG',
                "idtype" => 'FDASF',
                "idnumber" => 'SH',
                "address" => 'HFDS',
                "city" => 'ADF',
                "country" => 'AGFDG',
            ],
            "receivercustomerdata" => [
                "firstname" => "Ali",
                "secondname" =>'Test',
                "phone" => 'GFDSHH',
                "idtype" => 'SFGS',
                "idnumber" => 'JHGFJ',
                "address" => 'RTE',
                "city" => 'WRW',
                "country" => 'REWTHF',
            ]
        ]; */



        /* $data = [
            "intent" => "request_to_pay",
            "createtime" => $timestamp,
            "issuertrxref" => "141917",
            "walletsource" => "7909202",
            "walletdestination" => "0131825319878901",
            "amount" => 500,
            "currency" => "950",
            "tomember" => "10029",
        ]; */

        /* $data = [
            'createtime' => $timestamp,
            'intent' => 'mobile_transfer',
            'walletsource' => "7909202",
            'walletdestination' => "097951",
            'issuertrxref' => "141931",
            'amount' => 500,
            'currency' => '950',
            'description' => 'money transfer',
            'tomember' => "12001",
        ]; */

        /* $data = [
            "intent" => "wallet_to_account",
            'createtime' => $timestamp,
            "walletsource" => "7909202",
            "dstaccounts" => [
                [
                    "iden" => "699633256",
                    "type" => "ACCOUNT"
                ]
            ],
            "issuertrxref" => "141933",
            "amount" => 500,
            "currency" => "950",
            "tomember" => "12001"
        ]; */


        /* $data = [
            "intent" => "outg_wal_remit",
            'createtime' => $timestamp,
            "walletsource" => "7909202",
            "issuertrxref" => "141962",
            "amount" => 500,
            "currency" => "950",
            "tomember" => "16001",
            "walletdestination" => "699633", 
            "sendercustomerdata" => [
                "firstname" => "Jhon",
                "secondname" => 'Test',
                "phone" => 'SDG',
                "idtype" => 'FDASF',
                "idnumber" => 'SH',
                "address" => 'HFDS',
                "city" => 'ADF',
                "country" => 'AGFDG',
            ],
            "receivercustomerdata" => [
                "firstname" => "Ali",
                "secondname" =>'Test',
                "phone" => 'GFDSHH',
                "idtype" => 'SFGS',
                "idnumber" => 'JHGFJ',
                "address" => 'RTE',
                "city" => 'WRW',
                "country" => 'REWTHF',
            ]
        ]; */

        /* $url = env('GIMAC_PAYMENT_INQUIRY_TEST');
        $data = [
            'issuertrxref' => "141919",
        ]; 

        $response = $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ],
            'json' => $data,
        ]);

        $jsonResponse = json_decode($response->getBody()->getContents(),true);
        $statusCode = $response->getStatusCode();
        dd($jsonResponse,$statusCode); */

        /* $data = [
            "intent" => "mobile_transfer",
            "updatetime" => 1490109047,
            "issuertrxref" => "940654875270",
            "vouchercode" => "0vW0lJCIu014",
            "state" => "ACCEPTED",
        ];



        $response = $client->request('POST', 'https://10.20.36.212:8443/stdendpointfacade/v1/external/payment/update', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $accessToken"
            ],
            'json' => $data,
        ]); */

        $data = [
            "intent" => "mobile_transferqqqqqq",
            "createtime" => 1490109044,
            "walletsource" => "123456",
            "walletdestination" => "237699947943",
            "issuertrxref" => uniqid(),
            "amount" => 500,
            "currency" => "950",
            "tomember" => "10029",
        ];

        $response = $client->request('POST', 'https://10.20.36.212:8443/stdendpointfacade/v1/external/payment/send', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $accessToken"
            ],
            'json' => $data,
        ]);

        $body = $response->getBody()->getContents();
        $jsonResponse2 = json_decode($body);
        $statusCode = $response->getStatusCode();

        dd($body);



        /* $postDataRemit = "";
        try {
            Log::info('Remit POST Data:', [$postDataRemit]);
        } catch (AuthorizationException $e) {
            Log::warning('Permission denied while processing remit: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error in Remit process: ' . $e->getMessage());
        } */

        /*  $host = 'api.smileidentity.com';
         $uri = '/v1/smilelinks';
         $url = "https://$host$uri";  */

        /* $randomString = 'user-' . bin2hex(random_bytes(4));

        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;

        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $currentTimestamps = $dt->format("Y-m-d\TH:i:s.v\Z");

        $message = $currentTimestamps . $partner_id. 'sid_request';
        $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));
        $payloadArray =[
            "partner_id"=> $partner_id,
            "signature"=> $signature,
            "timestamp"=> $currentTimestamps,
            "name"=> "My Link",
            "company_name"=> "My Company Name",
            "id_types"=> [[
                "country"=> "GA",
                "id_type"=> "PASSPORT",
                "verification_method"=> "doc_verification"
            ]],
            "callback_url"=> "https://api.swap-africa.net/api/smileidCallback",
            "data_privacy_policy_url"=> "https://api.swap-africa.net/pages/privacy-policy-agent-merchant",
            "logo_url"=> "https://api.swap-africa.net/public/assets/front/images/logo.svg",
            "redirect_url" => "https://api.swap-africa.net/api/smileidCallback",
            "is_single_use"=> true,
            "user_id"=> $randomString,
            "partner_params"=> [
              "is_paying"=> "true",
              "customer branch"=> "country x"
            ],
            "expires_at"=> "2025-07-29T16:13:40.813Z"
        ];

        $jsonPayload = json_encode($payloadArray);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://testapi.smileidentity.com/v1/smile_links',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "X-Partner-ID: $partner_id",
                "X-Timestamp: $currentTimestamps",
                "X-Signature: $signature"
            ]
        ));



        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }

        die; */

        /* Get data */

        $userId = "user-3787706a"; // 9dd12037-c48f-48fb-b0b9-efd7787b0e63

        // Your Smile Identity API credentials
        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;

        $timestamp = (new DateTime('now', new DateTimeZone('UTC')))
            ->format('Y-m-d\TH:i:s.u\Z');


        $message = $timestamp . $partner_id . 'sid_request';
        $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));

        // Your original user_id
        $user_id = 'user-3787706a'; // replace with actual value from redirect

        $payload = json_encode([
            "user_id" => $user_id,
            "job_id" => "", // leave blank if you don't have job_id
            "partner_id" => $partner_id,
            "timestamp" => $timestamp,
            "signature" => $signature
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://testapi.smileidentity.com/v1/job_status",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-Partner-ID: $partner_id",
                "X-Timestamp: $timestamp",
                "X-Signature: $signature"
            ]
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        echo $response;
        /* end data */
        die;










        $randomString = 'user-' . bin2hex(random_bytes(4));

        $currentTimestamp = gmdate('Y-m-d\TH:i:s\Z');
        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;

        $message = $currentTimestamp . $partner_id;
        $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));

        $payload = [
            "source_sdk" => "rest_api",
            "partner_id" => $partner_id,
            "timestamp" => $currentTimestamp,
            "signature" => $signature,
            "partner_params" => [
                "user_id" => $randomString,
                "job_id" => $randomString,
                "job_type" => 6
            ],
            "optional_info" => [
                "callback_url" => "https://api.swap-africa.net/api/smileidCallback"
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://testapi.smileidentity.com/v1/smilelinks',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }


        die;
        /* $users = User::whereNull('referralCode')->where('user_type', 'User')->get();

        foreach ($users as $user) {
            $user->referralCode = strtoupper(substr(md5(uniqid()), 0, 8));
            $user->save();
        } */


        /* $users = User::where('user_type', 'User')->whereNull('ibanNumber')->get();

        foreach ($users as $user) {
            $ibanData = DB::table('iban_generated_lists')->where('status', 'available')->first();

            if (!$ibanData) {
                break;
            }

            $user->ibanNumber = $ibanData->iban;
            $user->save();

            DB::table('iban_generated_lists')->where('id', $ibanData->id)->update(['status' => 'assigned']);
        } */



        $certificate = public_path("MTN Cameroon Issuing CA1.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
        // WALLET INQUIRY
        $data = [
            'intent' => 'account_inquiry',
            'walletdestination' => 'CM2110029260110131825310134',
            'issuertrxref' => uniqid(),
            'tomember' => '10029',
        ];


        // ACCOUNT INQUIRY
        /*  $data = [
             "intent"=> "account_inquiry", 
             "dstaccounts"=>[["iden"=>"CM2110029260110131825310134","type"=>"ACCOUNT"]], 
             "issuertrxref"=>uniqid(), 
             "tomember"=>"10029", 
         ]; */

        // WALLET TRANSFER / WALLET TO WALLET
        /* $data = [
            "creattime" => 1490109044,
            "intent" => "wallet_transfer",
            "walletsource" => "111111111111",
            "walletdestination" => "111111111111",
            "issuertrxref" => uniqid(), 
            "amount" => 500,
            "currency" => "950",
            "tomember" => "12001"
        ]; */


        // WALLET TO ACCOUNT

        /* $data = [
            "intent" => "wallet_to_account",
            "createtime" => 1490109044,
            "walletsource" => "111111111111",
            "dstaccounts" => [
                [
                    "iden" => "237699947943",
                    "type" => "ACCOUNT"
                ]
            ],
            "issuertrxref" => uniqid(),
            "amount" => 500,
            "currency" => "950",
            "tomember" => "12001"
        ]; */

        //ACCOUNT TO WALLET

        /* $data = [
            "intent" => "account_to_wallet",
            "createtime" => 1490109044,
            "srcaccounts" => [["iden" => "111111111111", "type" => "ACCOUNT" ]],
            // "walletdestination" => "237699947943",
            "amount" => 500,
            "currency" => "950",
            "issuertrxref" => uniqid(),
            "tomember" => "12001"
        ]; */

        // ACCOUNT TO ACCOUNT

        /* $data = [
            "intent" => "account_transfer",
            "createtime" => 1490109044,
            "srcaccounts" => [["iden" => "GA2114007500012300177455963", "type" => "ACCOUNT"]],
            "dstaccounts" => [["iden" => "GA2114007500012300162807023", "type" => "ACCOUNT"]],
            "walletdestination" => "237699947943",
            "issuertrxref" => uniqid(),
            "amount" => 500,
            "currency" => "950",
            "tomember" => "12001"
        ]; */

        // WALLET TO WALLET
        /* $data = [
            "intent" => "mobile_transfer",  
            "createtime" => 1490109044, 
            "walletsource" => "237699947943", 
            "walletdestination" => "237699947943", 
            "issuertrxref" => uniqid(), 
            "amount" => 100, 
            "currency" => "950" ,
            "tomember" => "12001", 
        ]; */


        // Merchant purchase

        /* $data = [ 
            "intent"=>"merchant_purchase", 
            "createtime"=> 1490109044, 
            "walletsource"=>"111111111111", 
            "walletdestination"=>"237699947943", 
            "issuertrxref"=>uniqid(),
            "amount"=>500, 
            "currency"=>"950", 
            "tomember"=>"12001" 
        ]; */

        // REQUEST TO PAY

        /* $data = [
            "createtime"=> 1490109044, 
            "intent"=>"request_to_pay", 
            "walletsource"=>"111111111111", 
            "walletdestination"=>"111111111111", 
            "issuertrxref"=>uniqid(),
            "amount"=> 100, 
            "currency"=>"950", 
            "tomember"=>"12001",  
        ]; */

        // Prepaid Card Reload
        /* $data = [
            "intent" => "pp_reload",  
            "createtime" => 1490109044, 
            "issuertrxref" => uniqid(), 
            "walletsource" => "237699947943", 
            "dstaccounts" => [  
                [
                    "iden" => "123456789971", 
                    "type" => "CARD"  
                ]
            ], 
            "amount" => 10, 
            "currency" => "950" 
        ]; */

        // ACCOUNT INCOMING REMITTANCE
        /* $data = [
            "intent" => "inc_acc_remit",
            "createtime" => 1490109044,
            "issuertrxref" => uniqid(),
            "sendermobile" => "+212522564541",
            "dstaccounts" => [
                [
                    "iden" => "237699947943",
                    "type" => "ACCOUNT"
                ]
            ],
            "tomember" => "12001",
            "amount" => 500,
            "currency" => "950",
            "sendercustomerdata" => [
                "firstname" => "Hassan",
            ],
            "receivercustomerdata" => [
                "firstname" => "Ali", 
            ]
        ]; */

        // MOBILE RELOAD
        /* $data = [
            "issuertrxref" => uniqid(),
            "intent" => "mobile_reload",
            "createtime" => 1490109044,
            "sendermobile" => "+212522564541",
            "receivermobile" => "237699947943",
            "tomember" => "12001",
            "amount" => 500,
            "currency" => "950" */
        /* "sendercustomerdata" => [
            "firstname" => "Hassan",
            "secondname" => "TYPE",
            "idtype" => "nationalid",
            "idnumber" => "BE145278",
            "address" => "Road 2",
            "birthdate" => "21/09/1981"
        ],
        "receivercustomerdata" => [
            "firstname" => "Ali",
            "secondname" => "BAGHO",
            "idtype" => "passport",
            "idnumber" => "XC145278",
            "address" => "Road",
            "city" => "Casablanca",
            "country" => "Morocco",
            "phone" => "+2125224578",
            "birthdate" => "21/09/1981"
        ] */
        //];

        // PAYMENT VOUCHER
        /* $data = [
            "issuertrxref" => uniqid(),
            "intent" => "purchase_voucher",
            "createtime" => 1490109044,
            "walletsource" => "111111111111",
            "receivermobile" => "237699947943",
            "amount" => 500,
            "currency" => "950",
        ]; */


        /* $data = [
            "intent" => "outg_wal_remit",
            "createtime" => 1744782294,
            "walletsource" => "620215710",
            "walletdestination" => "237699947943",
            "issuertrxref" => uniqid(),
            "amount" => "1",
            "currency" => "950",
            "tomember" => "14008",
            "sendercustomerdata" =>  [
              "firstname" => "Eli MCH",
              "secondname" => "Merchant",
              "phone" => "620215710",
              "idtype" => "PASSPORT",
              "idnumber" => "DSFG2424S",
              "address" => "Jaipur",
              "city" => "Jaipur",
              "country" => "Gabon"
            ],
            "receivercustomerdata" =>  [
              "firstname" => "Wallet",
              "secondname" => "Toto",
              "phone" => "237699947943",
              "idtype" => "PASSPORT",
              "idnumber" => "124545",
              "address" => "12345613",
              "city" => "Alwer",
              "country" => "Benin"
            ]
            ]; */


        $accessToken = '';
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

            $response1 = $client->request('POST', env('GIMAC_TOKEN_URL_TEST'), $options);
            $body = $response1->getBody()->getContents();
            $jsonResponse = json_decode($body);
            $accessToken = $jsonResponse->access_token;

            $response = $client->request('POST', env('GIMAC_PAYMENT_URL_TEST'), [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer $accessToken"
                ],
                'json' => $data,
            ]);

            $body = $response->getBody()->getContents();
            $jsonResponse2 = json_decode($body);
            $statusCode = $response->getStatusCode();
            print_r($jsonResponse2);
            die;
        } catch (\Throwable $th) {
            print_r($th->getMessage());
        }

        die;
    }


    public function generateMultipleIBANs()
    {
        die('stop');
        ini_set('max_execution_time', 300); // Set to 5 minutes (adjust as needed)
        ini_set('memory_limit', '512M'); // Increase memory limit
        $countryCode = "GA";
        $controlKey = "21";
        $bankCode = 14007;
        $agencyCode = 50001;

        $ibanData = [];
        $usedNumbers = []; // Track generated account numbers
        $count = 0;

        while ($count < 100000) {
            // Generate unique 6-digit number
            do {
                $newNumber = str_pad(rand(100000, 999999), 6, "0", STR_PAD_LEFT);
            } while (isset($usedNumbers[$newNumber]) || IbanGeneratedList::where('accountNumber', "23001$newNumber")->exists());

            $usedNumbers[$newNumber] = true; // Mark as used

            $accountNumber = "23001$newNumber";

            // Calculate RIB key using Modulo 97
            $ribKey = 97 - ((89 * intval($bankCode) + 15 * intval($agencyCode) + 3 * intval($accountNumber)) % 97);
            $ribKey = str_pad($ribKey, 2, "0", STR_PAD_LEFT);

            // Construct IBAN
            $iban = $countryCode . $controlKey . $bankCode . $agencyCode . $accountNumber . $ribKey;

            $ibanData[] = [
                'iban' => $iban,
                'bankCode' => $bankCode,
                'agencyCode' => $agencyCode,
                'accountNumber' => $accountNumber,
                'ribKey' => $ribKey,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;

            // Insert in chunks of 1,000 to optimize performance
            if (count($ibanData) >= 1000) {
                IbanGeneratedList::insert($ibanData);
                $ibanData = [];
            }
        }

        // Insert remaining IBANs
        if (!empty($ibanData)) {
            IbanGeneratedList::insert($ibanData);
        }

        return response()->json([
            'message' => '100,000 IBANs generated successfully.'
        ]);
    }

    public function referralDashboard(Request $request)
    {
        if (Auth::user()->user_type != "Merchant") {
            return redirect()->back();
        }
        $getBonusReferral = Admin::where('id', 1)->first();
        $authUserId = Auth::user()->id;
        //DB::enableQueryLog();
        $successfulReferrals = User::where('referralBy', Auth::user()->id)->count();
        //dd(DB::getQueryLog());
        $referralEarnings = Transaction::where('user_id', Auth::user()->id)
            ->where('payment_mode', 'Referral')
            ->sum('amount');

        return view('dashboard.referral', compact('getBonusReferral', 'successfulReferrals', 'referralEarnings'));
    }

    public function initPaymentExternal(Request $request)
    {

        $orderId = $request->query('orderId');
        $merchantId = $request->query('merchantId');
        if (!$orderId && !$merchantId) {
            return response()->json(['error' => 'Enter Merchant Id and Order ID is required'], 400);
        }
        $input = $request->all();

        $type = "";
        $input = [
            'country_id' => 'Cameroon',
            'iban' => '',
            'wallet_manager_id' => 'BICEC',
            'firstName' => 'Test',
            'name' => 'Test Name',
            'amount' => 1,
            'phone' => '123123221',
        ];

        $validate_data = [
            'country_id' => 'required',
        ];

        $customMessages = [
            'country_id.required' => __('Country field can\'t be left blank')
        ];
        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $errorMessage, 'data' => null]);
        }

        if (!empty($input['country_id'])) {
            $onafriqCountries = [
                'Benin' => 'BJ',
                'Burkina Faso' => 'BF',
                'Guinea Bissau' => 'GW',
                'Niger' => 'NE',
                'Senegal' => 'SN',
                'Mali' => 'ML',
                'Togo' => 'TG',
                'Ivoiry Coast' => 'CI',
            ];
            $countryExists = Country::where('name', 'LIKE', '%' . $input['country_id'] . '%')->exists();
            if ($countryExists) {
                $type = 'GIMAC';
            } elseif (array_key_exists($input['country_id'], $onafriqCountries)) {
                $type = 'ONAFRIQ';
            } elseif (!empty($input['country_id']) && !empty($input['iban'])) {
                $type = 'BDA';
            }
        }

        // if ($request->isMethod('post')) {
        if (isset($type) && $type == 'GIMAC') {
            $validate_data = [
                'country_id' => 'required',
                'wallet_manager_id' => 'required',
                'firstName' => 'required',
                'name' => 'required',
                'amount' => 'required|numeric|min:1|max:99999999',
                'phone' => 'required|numeric|digits:9',
            ];

            $customMessages = [
                'firstName.required' => __('First name field can\'t be left blank'),
                'name.required' => __('Name field can\'t be left blank'),
                'amount.required' => __('Amount field can\'t be left blank'),
                'amount.min' => __('Amount must be at least 1'),
                'amount.max' => __('Amount maximum 99999999'),
                'country_id.required' => __('Country field can\'t be left blank'),
                'wallet_manager_id.required' => __('Wallet manager field can\'t be left blank'),
                'phone.required' => __('Phone field can\'t be left blank'),
                'phone.numeric' => __('Phone enter only numeric'),
                'phone.digits' => __('Phone enter 9 digits'),
            ];
        }

        if (isset($type) && $type == 'BDA') {
            $validate_data = [
                'beneficiary' => ['required', 'max:50'],
                'iban' => ['required', 'regex:/^[a-zA-Z0-9]+$/', 'min:24', 'max:30'],
                'reason' => 'required',
                'amount' => 'required|numeric|min:1|max:99999999',
            ];

            $customMessages = [
                'beneficiary.required' => __('Beneficiary can\'t be left blank'),
                'iban.required' => __('Iban field can\'t be left blank'),
                'iban.min' => __('Iban min length is 24'),
                'iban.max' => __('Iban max length is 30'),
                'reason.required' => __('Reason field can\'t be left blank'),
                'amount.required' => __('Amount field can\'t be left blank'),
                'amount.min' => __('Amount must be at least 1'),
                'amount.max' => __('Amount maximum 99999999'),
            ];
        }
        if (isset($type) && $type == 'ONAFRIQ') {
            $validate_data = [
                'amount' => 'required|numeric|min:500|max:1500000',
                'phone' => 'required',
                'country_id' => 'required',
                'firstName' => 'required',
                'name' => 'required',
                'wallet_manager_id' => 'required',
                'senderCountry' => 'required',
                'phoneSender' => 'required',
                'senderName' => 'required',
                'senderSurname' => 'required',
                'senderAddress' => 'required_if:country_id,Mali,Senegal',
                'senderIdType' => 'required_if:country_id,Mali,Senegal',
                'senderIdNumber' => 'required_if:country_id,Mali,Senegal',
                'senderDob' => 'required_if:country_id,Mali,Senegal,Burkina Faso',
            ];

            $customMessages = [
                'country_id.required' => __('Recipient Country field can\'t be left blank'),
                'firstName.required' => __('Recipient First Name field can\'t be left blank'),
                'name.required' => __('Recipient Surname field can\'t be left blank'),
                'amount.required' => __('Amount field can\'t be left blank'),
                'amount.min' => __('The amount must be at least 500'),
                'amount.max' => __('The amount maximum 1500000'),
                'phone.required' => __('Recipient Phone Number field can\'t be left blank'),
                'wallet_manager_id.required' => __('Wallet Manager field can\'t be left blank'),
                'senderCountry.required' => __('Sender Country field can\'t be left blank'),
                'phoneSender.required' => __('Sender Phone Number field can\'t be left blank'),
                'senderName.required' => __('Sender Name field can\'t be left blank'),
                'senderSurname.required' => __('Sender Surname field can\'t be left blank'),
                'senderAddress.required' => __('Sender Address field can\'t be left blank'),
                'senderIdType.required' => __('Sender Id Type field can\'t be left blank'),
                'senderIdNumber.required' => __('Sender Id Number field can\'t be left blank'),
                'senderDob.required' => __('Sender Dob field can\'t be left blank'),
            ];
        }

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $errorMessage]);
        }

        $certificate = public_path("MTN Cameroon Issuing CA1.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
        if ($type == 'GIMAC') {
            try {
                $tomember_id = $input['wallet_manager_id'];
                $total_amount = $input['amount'];

                $is_receiver_exist = User::where('merchantKey', $merchantId)->first();

                $tomemberData = WalletManager::where('name', $tomember_id)->first();
                if (!empty($tomemberData)) {
                    $tomember = $tomemberData->tomember;
                }

                $dateString = date('d-m-Y H:i:s');
                $format = 'd-m-Y H:i:s';
                $dateTime = DateTime::createFromFormat($format, $dateString);
                $timestamp = $dateTime->getTimestamp();

                /* $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                if ($last_record != "") {
                    $next_issuertrxref = $last_record + 1;
                } else {
                    $next_issuertrxref = '140071';
                } */

                $data = [
                    'createtime' => $timestamp,
                    'intent' => 'mobile_transfer',
                    'walletsource' => $input['phone'],
                    'walletdestination' => $is_receiver_exist->phone,
                    'issuertrxref' => uniqid(),
                    'amount' => $input['amount'],
                    'currency' => '950',
                    'description' => 'money transfer',
                    'tomember' => $tomember,
                ];


                $accessToken = '';

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
                $response = $client->request('POST', env('GIMAC_TOKEN_URL'), $options);
                $body = $response->getBody()->getContents();
                $jsonResponse = json_decode($body);
                $accessToken = $jsonResponse->access_token;

                $response = $client->request('POST', env('GIMAC_PAYMENT_URL'), [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer $accessToken"
                    ],
                    'json' => $data,
                ]);

                $body = $response->getBody()->getContents();
                $jsonResponse2 = json_decode($body);
                $statusCode = $response->getStatusCode();

                if ($statusCode == 200) {
                    //Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                    $tomember = $jsonResponse2->tomember;
                    $acquirertrxref = $jsonResponse2->acquirertrxref ?? 0;
                    $issuertrxref = $jsonResponse2->issuertrxref;
                    $state = $jsonResponse2->state;
                    $rejectedStatus = '';
                    // $status = $state == 'ACCEPTED' ? 1 : 2;
                    $status = $state == 'ACCEPTED' ? 1 : ($state == 'PENDING' ? 2 : ($state == 'SUSPECTED' ? 7 : 4));


                    if ($state == 'REJECTED') {
                        $rejectedStatus = $jsonResponse2->rejectMessage;
                        return response()->json(['status' => false, 'message' => $rejectedStatus, 'data' => null]);
                    }

                    $vouchercode = $jsonResponse2->vouchercode;
                    $trans_id = time();
                    $refrence_id = time();
                    /* $trans = new Transaction([
                        'user_id' => $userId,
                        'receiver_id' => 0,
                        'receiver_mobile' => $is_receiver_exist->phone,
                        'amount' => $input['amount'],
                        'amount_value' => $input['amount'],
                        'transaction_amount' => $total_fees,
                        'total_amount' => $total_amount,
                        'trans_type' => 2,
                        'excel_trans_id' => $excelData->id,
                        'payment_mode' => 'External',
                        'status' => $status,
                        'refrence_id' => $issuertrxref,
                        'billing_description' => 'Fund Transfer-' . $refrence_id,
                        'tomember' => $tomember,
                        'acquirertrxref' => $acquirertrxref,
                        'issuertrxref' => $issuertrxref,
                        'vouchercode' => $vouchercode,
                        'transactionType' => 'SWAPTOGIMAC',
                        'orderId' => $orderId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save(); */

                    if ($state == 'ACCEPTED' || $state == 'PENDING') {
                        return response()->json(['status' => true, 'message' => 'Payment successfully', 'data' => null]);
                    }
                    return response()->json(['status' => false, 'message' => 'Payment failed', 'data' => null]);
                }
            } catch (\Exception $e) {
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $response = $e->getResponse();
                    $body = $response->getBody();
                    $contents = $body->getContents();
                    // Now, $contents contains the response body
                    $jsonResponse = json_decode($contents, true);
                    /* $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                    if ($last_record != "") {
                        $next_issuertrxref = $last_record + 1;
                    } else {
                        $next_issuertrxref = '140071';
                    } */
                    if ($jsonResponse && isset($jsonResponse['error_description'])) {
                        $errorDescription = $jsonResponse['error_description'];
                        //Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);
                        return response()->json(['status' => false, 'message' => $errorDescription, 'data' => null]);
                    } else {
                        //Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => __('Unable to extract error_description')]);
                        return response()->json(['status' => false, 'message' => 'Unable to extract error_description', 'data' => null]);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'Server error', 'data' => null]);
                }
            }
            print_r($statusCode);
            die;
        } elseif ($type == 'ONAFRIQ') {

            try {

                $totalAmount = $input['amountO'];
                // if ($userData->wallet_balance >= $total_amount) {
                $postData = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                <soap:Body>
                                <ns:account_request xmlns:ns="http://ws.mfsafrica.com">
                                <ns:login>
                                <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                                <ns:password>' . CORPORATEPASS . '</ns:password>
                                </ns:login>
                                <ns:to_country>' . $input['country_id'] . '</ns:to_country>
                                <ns:msisdn>' . $input['phoneNo'] . '</ns:msisdn>
                                </ns:account_request>
                                </soap:Body>
                                </soap:Envelope>';
                $getResponse = $this->sendCurlRequest($postData, 'urn:account_request');
                //print_r($getResponse); die;
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
                                        <ns:amount>' . $input["amount"] . '</ns:amount> 
                                        <ns:currency_code>' . $input["recipientCurrency"] . '</ns:currency_code> 
                                    </ns:receive_amount>
                                    <ns:sender>
                                        <ns:address>' . ($input["senderAddress"] ?: "") . '</ns:address>
                                        <ns:city>string</ns:city>
                                        <ns:date_of_birth>' . ($input["senderDob"] ?: "") . '</ns:date_of_birth>
                                        <ns:document>
                                        <ns:id_country>string</ns:id_country>
                                        <ns:id_expiry>string</ns:id_expiry>
                                        <ns:id_number>' . ($input["senderIdNumber"] ?: "") . '</ns:id_number>
                                        <ns:id_type>' . ($input["senderIdType"] ?: "") . '</ns:id_type>
                                        </ns:document>
                                        <ns:email>string</ns:email>
                                        <ns:from_country>' . $input["senderCountry"] . '</ns:from_country>
                                        <ns:msisdn>' . $input["senderMsisdn"] . '</ns:msisdn>
                                        <ns:name>' . $input["senderName"] . '</ns:name>
                                        <ns:place_of_birth>string</ns:place_of_birth>
                                        <ns:postal_code>string</ns:postal_code>
                                        <ns:state>string</ns:state>
                                        <ns:surname>' . $input["senderSurname"] . '</ns:surname>
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
                                        <ns:msisdn>' . $input["recipientMsisdn"] . '</ns:msisdn>
                                        <ns:name>' . $input["recipientName"] . '</ns:name>
                                        <ns:postal_code>string</ns:postal_code>
                                        <ns:state>string</ns:state>
                                        <ns:status>
                                        <ns:status_code>string</ns:status_code>
                                        </ns:status>
                                        <ns:surname>' . $input["recipientSurname"] . '</ns:surname>
                                        <ns:to_country>' . $input["recipientCountry"] . '</ns:to_country>
                                    </ns:recipient>
                                    <ns:third_party_trans_id>' . $input["thirdPartyTransactionId"] . '</ns:third_party_trans_id>
                                    <ns:reference>string</ns:reference>
                                    <ns:source_of_funds>string</ns:source_of_funds>
                                    <ns:purpose_of_transfer>string</ns:purpose_of_transfer>
                                    </ns:mm_remit_log>
                                </soap:Body>
                            </soap:Envelope>';

                    $getResponseRemit = $this->sendCurlRequest($postDataRemit, 'urn:mm_remit_log');

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
                        $postDataTrans = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
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

                            /* $refrence_id = time();
                            $trans = new Transaction([
                                'user_id' => $userId,
                                'receiver_id' => '0', 
                                'receiver_mobile' => '', 
                                'amount' => $input['amountO'],
                                'amount_value' => $input['amountO'],
                                'transaction_amount' => $total_fees,
                                'total_amount' => $total_amount,
                                'trans_type' => 2,
                                'excel_trans_id' => $excelT->id,
                                'payment_mode' => 'External',
                                'status' => 1,
                                'refrence_id' => '',
                                'billing_description' => "Fund Transfer-$refrence_id",
                                'tomember' => '',
                                'acquirertrxref' => '',
                                'issuertrxref' => '',
                                'vouchercode' => '',
                                'onafriq_bda_ids' => $getOnfi->id,
                                'transactionType' => 'SWAPTOONAFRIQ',
                                'orderId' => $orderId,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $trans->save();  */
                            return response()->json(['status' => true, 'message' => 'Payment successfully', 'data' => null]);
                        } else {

                            if ($statusCode2 == 'MR108' || $statusCode2 == 'MR103' || $statusCode2 == 'MR102') {
                                $refrence_id = time();
                                /*  $trans = new Transaction([
                                     'user_id' => $userId,
                                     'receiver_id' => 0,
                                     'receiver_mobile' => '',
                                     'amount' => $input['amountO'],
                                     'amount_value' => $input['amountO'],
                                     'transaction_amount' => $total_fees,
                                     'total_amount' => $total_amount,
                                     'trans_type' => 2,
                                     'excel_trans_id' => $excelT->id,
                                     'payment_mode' => 'External',
                                     'status' => 2,
                                     'refrence_id' => '',
                                     'bda_status' => 5,
                                     'billing_description' => 'Fund Transfer-' . $refrence_id,
                                     'tomember' => '',
                                     'acquirertrxref' => '',
                                     'issuertrxref' => '',
                                     'vouchercode' => '',
                                     'onafriq_bda_ids' => $getOnfi->id,
                                     'transactionType' => 'SWAPTOONAFRIQ',
                                     'orderId' => $orderId,
                                     'created_at' => date('Y-m-d H:i:s'),
                                     'updated_at' => date('Y-m-d H:i:s'),
                                 ]);
                                 $trans->save();  */

                                return response()->json(['status' => true, 'message' => 'Payment Inprogress', 'data' => null]);

                            } else {
                                return response()->json(['status' => false, 'message' => 'Payment failed', 'data' => null]);
                            }
                        }
                    } else {
                        return response()->json(['status' => false, 'message' => 'Payment failed remit log not response success', 'data' => null]);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'Recipient phone number not active', 'data' => null]);
                }
            } catch (\Exception $e) {
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $response = $e->getResponse();
                    $body = $response->getBody();
                    $contents = $body->getContents();
                    $jsonResponse = json_decode($contents, true);
                    if ($jsonResponse && isset($jsonResponse['error_description'])) {
                        $errorDescription = $jsonResponse['error_description'];
                        return response()->json(['status' => false, 'message' => $errorDescription, 'data' => null]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Unable to extract error_description', 'data' => null]);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'Server error', 'data' => null]);
                }
            }

            /* } else {
                Session::put('error_message', 'Balance not remaining your wallet');
                return Redirect::to('/payment-return-status?transactionId=failed');
            } */
        } elseif ($type == 'BDA') {
            try {

                $totalAmount = $input['amountB'];

                $remittanceData = new RemittanceData();
                $getLstNo = RemittanceData::orderBy('id', 'desc')->select('referenceLot')->first();
                if (empty($getLstNo)) {
                    $refNoLo = 'SWAP9999';
                } else {
                    preg_match('/([a-zA-Z]+)([0-9]+)/', $getLstNo->referenceLot, $matches);
                    $incrementedPart = (int) $matches[2] + 1;
                    $newReferenceLot = $matches[1] . $incrementedPart;
                    $refNoLo = $newReferenceLot;
                }

                $total_amount = $input['amountB'];

                $url = env('BDA_PAYMENT_URL');
                $data = [
                    'referenceLot' => $input["referenceLot"],
                    'nombreVirement' => 1,
                    'montantTotal' => $input["amount"],
                    'produit' => "SWAP",
                    'virements' => [
                        [
                            'ibanCredit' => $input["iban"],
                            'intituleCompte' => $input["newBeneficiary"],
                            'montant' => $input["amount"],
                            'referencePartenaire' => $this->generateString(),
                            'motif' => $input["reason"],
                            'typeVirement' => 'RTGS'
                        ]
                    ]
                ];

                $response = $client->request('POST', $url, [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $data,
                ]);

                $body = $response->getBody()->getContents();
                $jsonResponse2 = json_decode($body);
                $statusCode = $response->getStatusCode();
                if ($statusCode == 200) {
                    $statut = $jsonResponse2->statut;
                    if ($statut === 'REJETE') {
                        return response()->json(['status' => false, 'message' => 'Payment Rejected', 'data' => null]);
                    }

                    if ($statut == 'EN_ATTENTE' || $statut == 'EN_ATTENTE_REGLEMENT') {
                        $refrence_id = time();

                        /* $trans = new Transaction([
                            // 'user_id' => $userId,
                            'receiver_id' => 0,
                            'receiver_mobile' => '',
                            'amount' => $input['amountB'],
                            'amount_value' => $input['amountB'],
                            // 'transaction_amount' => $total_fees,
                            'total_amount' => $total_amount,
                            'trans_type' => 2,
                            'excel_trans_id' => $excelTrans->id,
                            'payment_mode' => 'External',
                            'status' => 2,
                            'bda_status' => 2,
                            'refrence_id' => '',
                            'billing_description' => 'Fund Transfer-' . $refrence_id,
                            'tomember' => '',
                            'acquirertrxref' => '',
                            'issuertrxref' => '',
                            'vouchercode' => '',
                            'onafriq_bda_ids' => $details->id,
                            'transactionType' => 'SWAPTOBDA',
                            'orderId' => $orderId,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $trans->save(); */

                        return response()->json(['status' => true, 'message' => 'Payment successfull', 'data' => null]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Payment Failed', 'data' => null]);
                    }
                }
                print_r('BDA');
                die;
            } catch (\Exception $e) {
                if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                    $response = $e->getResponse();
                    $body = $response->getBody();
                    $contents = $body->getContents();
                    $jsonResponse = json_decode($contents, true);
                    if ($jsonResponse && isset($jsonResponse['error_description'])) {
                        $errorDescription = $jsonResponse['error_description'];
                        return response()->json(['status' => false, 'message' => $errorDescription, 'data' => null]);
                    } else {
                        return response()->json(['status' => false, 'message' => 'Unable to extract error_description', 'data' => null]);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'Server Error', 'data' => null]);
                }
            }
        }
        //}
    }

    public function smileidReturnUrl(Request $request)
    {
        $response = $request->all();
        $userIdNew = $request->input('user_id');
        sleep(2);
        Log::info('Testing userIdssss unique_key  : ' . $userIdNew);
        $userId = $response['user_id'];
        // $userId = "user-413";
        $user = User::where("unique_key", $userIdNew)->first();

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
        Log::info('SmileID response data:', ['res' => $responseData]);
        // dd($responseData);
        if (isset($responseData->code) && $responseData->code == 2302) {
            $updated = User::where('unique_key', $userIdNew)->update(['kyc_status' => 'pending']);
            Log::info('Response Code Success : ' . $responseData->code);
            return view('dashboard.smile-return');
        } else {
            Log::info('Response Code : ' . $responseData->code);
            $error = $responseData->result->ResultText ?? "Error";
            return view('dashboard.smile-return-error', compact('error'));

        }
    }

    public function driverDashboard(Request $request)
    {
        $pageTitle = 'Driver Dashboard';

        if ($request->ajax()) {
            $input = $request->all();
            $otp_number = $this->generateNumericOTP(6);
            // $otp_number = '111111';
            $validate_data = [
                'accountId' => 'required|max:10',
                'last4Digits' => 'required|max:4',
            ];

            $customMessages = [
                'accountId.required' => 'Enter Account ID',
                'accountId.max' => 'Account ID cannot be more than 10 digits',
                'last4Digits.required' => 'Enter Last 4 Digits',
                'last4Digits.max' => 'Only last 4 digits are allowed',
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first()
                ], 422);
            }

            
            /* $getData = User::where("accountId", $input['accountId'])
                ->where("last4Digits", $input['last4Digits'])
                ->first();

            if (!$getData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Account ID or Last 4 Digits.'
                ], 422);
            } */


            $userCard = UserCard::firstWhere(['accountId' => $request->accountId, 'last4Digits' => $request->last4Digits,'cardType'=>'PHYSICAL']);
            
            $getData = User::where("id", $userCard->userId)->first();

            if (!$getData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Account ID or Last 4 Digits.'
                ], 422);
            }


            $getCustomerDetail = $this->cardService->getCustomerData($input['accountId'], 'PHYSICAL');
            if ($getCustomerDetail['status'] == true) {
                $customerData = $getCustomerDetail['data'] ?? [];
                $isActive = $customerData['cardStatus'] ?? "";
                if ($isActive == "AC") {
                    return response()->json(['status' => 'failed', 'message' => 'Already verified this accountId.']);
                }
            }

            // Normally send OTP here...
            $phone = $getData->phone ?? "";
            $userId = $getData->id ?? 0;

            /* DriverActivationCard::create([
                'driverId' => Auth::guard('driver-web')->id(),
                'userId' => $userId,
                'accountId' => $input['accountId'],
                'otp' => $otp_number,
            ]); */



            DriverActivationCard::updateOrCreate(
                ['accountId' => $input['accountId'], 'driverId' => Auth::guard('driver-web')->id()],
                [
                    'driverId' => Auth::guard('driver-web')->id(),
                    'userId' => $userId,
                    'otp' => $otp_number,
                ]
            );

            $this->smsService->sendLoginRegisterOtp($otp_number, $phone);

            $title = "OTP Verification";
            $message = "Your OTP is $otp_number. Do not share it with anyone.";
            $device_type = $getData->device_type;
            $device_token = $getData->device_token;

            $data1 = [
                'title' => $title,
                'message' => $message,
                'id' => "",
                'type' => 'OTP',
            ];

            if ($device_type && $device_token) {
                $this->firebaseNotificationService->sendPushNotificationToToken(
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

            return response()->json([
                'status' => 'success',
                'message' => 'OTP sent successfully to your registered mobile number: ' . $phone
            ]);
        }

        // For normal page load
        return view('dashboard.driver-dashboard', ['title' => $pageTitle]);
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

    public function verifyCardStatus(Request $request)
    {
        $getCustomerDetail = $this->cardService->getCustomerData($request->accountId, 'PHYSICAL');
        if ($getCustomerDetail['status'] == true) {
            $customerData = $getCustomerDetail['data'] ?? [];
            $isActive = $customerData['cardStatus'];
            if ($isActive == "AC") {
                return response()->json(['status' => 'verified', 'cardstatus' => $isActive]);
            } elseif ($isActive != "AC") {
                return response()->json(['status' => 'inactive']);
            }
        }
        return response()->json(['status' => 'failed']);
    }
    public function logoutDriver()
    {
        Auth::guard('driver-web')->logout();
        return redirect('/login-driver')->with('success_message', 'You have been logged out successfully.');
    }

    public function captureFace()
    {
        return view('dashboard.capture-face');
    }
    public function getSmileIdSignature()
    {
        $timestamp = time();
        $partnerId = TEST_SMILE_PARTNER_ID;
        $apiKey = TEST_SMILE_API_KEY;

        $signature = base64_encode(
            hash_hmac('sha256', $timestamp, $apiKey, true)
        );

        return response()->json([
            'partner_id' => $partnerId,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);
    }

}



