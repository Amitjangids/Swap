<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mail;
use DB;
use Session;
use Redirect;
use Input;
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
use App\Walletlimit;
use App\Exports\ReportExport1;
use App\Models\RemittanceData;
use App\Models\FeeApply;

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
use Carbon\Carbon;




class DashboardController extends Controller
{


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

        $total_fees = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->whereNull('remarks')->where('transactions.status', 1)->sum('transaction_amount');

        // $total_fees_formatted = number_format($total_fees, 0, '.', ',');

        return view('dashboard.dashboard', ['title' => $pageTitle, 'wallet_balance' => intval($wallet_balance),'holdAmount' => intval($holdAmount), 'opt_this_month' => intval($opt_this_month), 'successfull_transactions' => intval($successfull_transactions), 'failure_transactions' => intval($failure_transactions), 'total_transfer' => CURR . ' ' . intval($total_transfer), 'total_deposit' => CURR . ' ' . intval($total_deposit), 'total_fees' => CURR .' '. $total_fees]);
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
                'role.required' => 'Role field can\'t be left blank',
                'name.required' => 'Name field can\'t be left blank',
                'email.required' => 'Email field can\'t be left blank',
                'email.email' => 'Please provide a valid email address',
                'email.unique' => 'Email is already exist',
                'phone.required' => 'Please provide a phone number',
                'phone.unique' => 'Phone number is already exist',
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
            Mail::send('emails.generatePasswordLink', $emailData, function ($message) use ($emailData, $emailId) {
                $message->to($emailId, $emailId)
                ->subject($emailData['subject']);
            });

            Session::put('success_message', 'The user has been created successfully and an autogenerated password link has been mailed to generate password on the registered email address');
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
            $action = '<a href="javascript:void(0);" class="btn btn-primaryx"  data-bs-toggle="modal" data-bs-target="#delete_view" onclick=delete_user(' . "'" . '' . $record->slug . "'" . ')>Remove</a>';
            $data_arr[] = array(
                "name" => ucfirst($record->name),
                "phone" => $record->phone,
                "email" => ucfirst($record->email),
                "parent_id" => Auth::user()->id,
                "user_type" => $record->user_type,
                "status" => 'Active',
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
        User::where('slug', $slug)->update(['status' => 2]);
        Session::put('success_message', 'User has been deleted successfully');
        return Redirect::to('/create-user');
    }

    public function generatePassword($slug)
    {
        $pageTitle = 'Generate Password';
        $decode_string = base64_decode($slug);
        $user_id = $this->decryptContent($decode_string);
        $userInfo = User::where('id', $user_id)->first();
        if (empty($userInfo)) {
            Session::put('error_message', 'Invalid User!');
            return Redirect::to('/login');
        }
        if ($userInfo->password != "") {
            Session::put('error_message', 'Invalid Link!');
            return Redirect::to('/login');
        }

        $input = Input::all();
        if (!empty($input)) {

            $validate_data = [
                'password' => 'required',
                'confirm_password' => 'required|same:password',
            ];

            $customMessages = [
                'password.required' => 'Password field can\'t be left blank',
                'confirm_password.required' => 'Confirm password field can\'t be left blank',
                'confirm_password.same' => 'Password and confirm password should be same',
            ];

            $validator = Validator::make($input, $validate_data, $customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                Session::put('error_message', $messages);
                return Redirect::to('/generate-password/' . $slug);
            }
            $hashedPassword = Hash::make($input['password']);
            User::where('id', $user_id)->update(['password' => $hashedPassword, 'is_email_verified' => 1]);
            Session::put('success_message', 'Password has been updated successfully');
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

        return view('dashboard.submitter_dashbaord', ['title' => $pageTitle, 'wallet_balance' => intval($wallet_balance),'holdAmount' => intval($holdAmount), 'opt_this_month' => intval($opt_this_month), 'successfull_transactions' => intval($successfull_transactions), 'failure_transactions' => intval($failure_transactions), 'total_deposit' => CURR . ' ' . intval($total_deposit), 'total_transfer' => CURR . ' ' . intval($total_transfer), 'total_fees' => CURR . ' ' . intval($total_fees)]);
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
            <a href="' . $record->id . '" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="View"></i></a>' .
            ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="Download Excel"></i></a>' : '');
            $data_arr[] = array(
                "reference_id" => $record->reference_id,
                "remarks" => $record->remarks != "" ?  ucfirst($record->remarks) : 'Salary',
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

        $pageTitle = 'Dashboard';
        return view('dashboard.approver_dashbaord', ['title' => $pageTitle, 'wallet_balance' => intval($wallet_balance), 'holdAmount' => intval($holdAmount), 'opt_this_month' => intval($opt_this_month), 'successfull_transactions' => intval($successfull_transactions), 'failure_transactions' => intval($failure_transactions), 'total_deposit' => CURR . ' ' . intval($total_deposit), 'total_transfer' => CURR . ' ' . intval($total_transfer), 'total_fees' => CURR . ' ' . intval($total_fees)]);
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
        $rowperpage = $request->get("length") != '-1' ?  $request->get("length") : 5; // total number of rows per page
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
            ->whereIn('status', [5, 6])
            ->count();


            $totalRecordswithFilter = UploadedExcel::where('parent_id', $parent_id)
            ->whereIn('status', [5, 6])
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
            ->whereIn('status', [5, 6])
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
        $data_arr = array();
        foreach ($records as $record) {

           $total = number_format($record->totat_amount, 0, '.', ',');

           $fees = number_format($record->total_fees, 0, '.', ',');

           global $months;
           $lang = Session::get('locale');

           if($lang == 'fr'){
               $date = date('d F, Y', strtotime($record->created_at));
               $frenchDate = str_replace(array_keys($months), $months, $date);


           }else{
               $frenchDate = $record->created_at->format('d M, Y');


           }
           $action = '
           <a href="' . $record->id . '" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="' . __('View') . '"></i></a>' .
           ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="' . __('Download Excel '). '"></i></a>' : '');


           $getRecordName = ExcelTransaction::where('excel_id', $record->id)
           ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
           ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
           ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by')->get();

           $data_arr[] = array(
            "reference_id" => $record->reference_id,
            "remarks" => $record->remarks != "" ?  ucfirst($record->remarks) : 'Salary',
            "excel" => $record->excel,
            "no_of_records" => $record->no_of_records,
            "updated_at" => $frenchDate,
            "totat_amount" => CURR . ' ' .$total ,
            "total_fees" => CURR . ' ' .$fees ,
            //"submitted_by" => $getRecordName[0]->submitted_by,
            "approved_by" => (isset($getRecordName[0]->approved_by) ? $getRecordName[0]->approved_by : 'Not Approved'),
            "created_at" => date('d M, Y', strtotime($record->created_at)),
            //"status" => $this->getStatus($record->status),
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
    $rowperpage = $request->get("length") != '-1' ?  $request->get("length") : 5;
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
            $encId = base64_encode($this->encryptContent($record->id));

            $userType = Auth::user()->user_type;
            $modalTarget = ($userType == "Submitter") ? "rejectSbumitter" : "rejectRequest";
            $deleteIcon = ($userType == "Submitter") ? "fa-trash-o" : "fa-ban";
            $deleteAction = ($userType == "Submitter") ? 'cancelRequestSubmitter(' . "'" . $encId . "'" . ')' : 'cancelRequest(' . "'" . $encId . "'" . ')';
            $deleteText = ($userType == "Submitter") ? __('Delete') : __('Reject');
            $action = '
            <a href="' . $record->id . '" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title= "' . __('View') . '"></i></a>' .
            ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="' . __('Download Excel') . '"></i></a>' : '') .
            '<a id="approveExcelLink"  href="' . HTTP_PATH . '/approve-excel/' . $encId . '" class="approve-btn approveExcelLink">
            <i class="fa fa-check" aria-hidden="true" title="' . __('Approve Excel') . '"></i>
            </a><script>
            document.getElementById("approveExcelLink").addEventListener("click", function() { 
                document.getElementById("loader-wrapper").style.display = "block";
                });
                </script>' .
                '<a href="javascript:void(0)" class="" data-bs-toggle="modal" data-bs-target="#' . $modalTarget . '" onclick="' . $deleteAction . '"><i class="fa ' . $deleteIcon . '" aria-hidden="true" title="' . $deleteText . '"></i></a>';
// echo"<pre>";print_r($record);die;

                global $months;
                $lang = Session::get('locale');

                if($lang == 'fr'){
                    $date = date('d F, Y', strtotime($record->updated_at));
                    $frenchDate = str_replace(array_keys($months), $months, $date);  

                }else{
                    $frenchDate = date('d M,y', strtotime($record->updated_at));

                }

                $data_arr[] = array(
                    "reference_id" => $record->reference_id,
                    "remarks" => $record->remarks != "" ?  ucfirst($record->remarks) : 'Salary',
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


        public function approveExcel($slug)
        {
            $user_id = Auth::user()->id;
            $user_type = Auth::user()->user_type;
            $decode_string = base64_decode($slug);
            $id = $this->decryptContent($decode_string);
            $userData = User::where('id', $user_id)->first();
            $user_role = Auth::user()->user_type;
            $sum = 0;
            $uploadTrans = UploadedExcel::where('id', $id)->first();
            $excel_transactions = ExcelTransaction::where('excel_id', $id)->get();

            foreach ($excel_transactions as $val) {
                $sum += $val->amount;
            }

            if ($user_type == "Merchant") {
                $excel_transaction = ExcelTransaction::where('excel_id', $id)->get();


            //to create the access token for gimac
                $certificate  = public_path("MTN Cameroon Issuing CA1.crt");
                $client = new Client([
                    'verify' => $certificate,
                ]);
                $accessToken = '';

                $t_inserted_record = 0;
                $n_inserted_record = 0;

                $total_fees_arry = [];

                $senderUser = User::where('id', $user_id)->first();
                $userType = $this->getUserType($senderUser->user_type);
                $walletlimit = Walletlimit::where('category_for', $userType)->first();
                $transactionLimit = TransactionLimit::where('type', $userType)->first();
                $total_amount_to_transfer = 0;


                foreach ($excel_transaction as $key => $row) {

                    if (!isset($amount)) {
                        $amount = 0;
                    }
                    $tomember = 0;
                    $country = $row->country_id;
                    $tomember_id = $row->wallet_manager_id;
                    $receviver_mobile = $row->tel_number;
                    $amount = $row->amount;

                // $total_amount_to_transfer += $row->amount;


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
                    $currentWeekSum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->sum('amount');
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

                // if($senderUser->kyc_status!="completed")
                // { 
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
                    // echo"hello";die;
                    }
                // }
                // else
                // {


                    $is_receiver_exist = User::where('phone', $receviver_mobile)->whereNotIn('user_type', ['Approver', 'Submitter'])->first();


                    if (isset($is_receiver_exist)) {
                    //  $user_id.'/'.$is_receiver_exist->id;
                        if ($user_id == $is_receiver_exist->id) {
                            $msg = __('You cannot send funds to yourself');
                            ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
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

                            $feeapply = FeeApply::where('userId',$user_id)->where('transaction_type', 'Send Money')->where('min_amount', '<=', $amount)
                            ->where('max_amount', '>=',  $amount)->first();
                        // echo"<pre>";print_r($feeapply);die;

                            if(isset($feeapply)){

                                $feeType=$feeapply->fee_type;
                                if ($feeType == 1) {
                                    $total_fees = $feeapply->fee_amount;
                                } else {
                                    $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                                }
                            }else{
                                $trans_fees = Transactionfee::where('transaction_type', 'Send Money')->where('min_amount', '<=', $amount)
                                ->where('max_amount', '>=',  $amount)->first();
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

                            $total_amount = $amount ;
                            
                            if ($sum > $senderUser->holdAmount) {
                                $msg = 'Insufficient Balance !';
                                ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
                                $n_inserted_record += 1;
                                continue;
                            } else {

                                $user = User::find($user_id);
                                $wallet_balance = $user->holdAmount;
                                $wallet_balance -= $amount + $total_fees;
                                $user->holdAmount = $wallet_balance;

                                $user->save();

                            // if (!$existingTransaction) {
                            // Create a new transaction only if a matching record doesn't exist
                                $trans_id = time() . '-' . $user_id . '-' . $is_receiver_exist->id;
                                $refrence_id = time() . '-' . $is_receiver_exist->id;
                                $trans = new Transaction([
                                    'user_id' => $user_id,
                                    'receiver_id' => $is_receiver_exist->id,
                                    'amount' => $amount,
                                    'amount_value' => $total_amount,
                                    'transaction_amount' => $total_fees,
                                    'total_amount' => $amount+$total_fees,
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
                            // }
                                $recieverUser = $userInfo = User::where('id', $is_receiver_exist->id)->first();
                                $total_amount_to_transfer += $amount ;
                                $total_count = count($total_fees_arry) + 1;
                                $charge= $total_count * $total_fees;

                                $sender_wallet_amount = $senderUser->holdAmount - $total_amount_to_transfer - $charge;

                                $debit = new TransactionLedger([
                                    'user_id' => $user_id,
                                    'opening_balance' => $senderUser->wallet_balance,
                                    'amount' => $amount,
                                    'fees' => $total_fees,
                                    'actual_amount' => $amount+$total_fees,
                                    'type' => 2,
                                    'trans_id' => $transaction_id,
                                    'payment_mode' => 'wallet2wallet',
                                    'closing_balance' => $senderUser->wallet_balance,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $debit->save();

                                User::where('id', $user_id)->update(['holdAmount' => $sender_wallet_amount]);


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

                                User::where('id', $is_receiver_exist->id)->update(['wallet_balance' => $receiver_wallet_amount]);

                                DB::table('admins')->where('id', 1)->increment('wallet_balance', $total_fees);
                                $total_fees_arry[] = $total_fees;
                                ExcelTransaction::where('id', $row->id)->update(['fees' => $total_fees]);

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
                    // exit;
                    } else {
                    /*echo $excel_transactions[0]->bdastatus;
                    echo $country; die;*/
                    if (!isset($country) && $excel_transactions[0]->bdastatus != 'BDA') {

                        $msg = 'Country is not supported !';
                        ExcelTransaction::where('id', $row->id)->update(['remarks' => $msg]);
                        $n_inserted_record += 1;
                        
                        continue;
                    } else {
                        try {

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

                            $feeapply = FeeApply::where('userId',$user_id)->where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $amount)->where('max_amount', '>=',  $amount)->first();
                            // echo"<pre>";print_r($feeapply);die;

                            if(isset($feeapply)){

                                $feeType=$feeapply->fee_type;
                                if ($feeType == 1) {
                                    $total_fees = $feeapply->fee_amount;
                                } else {
                                    $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                                }
                            }else{
                                $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $amount)->where('max_amount', '>=',  $amount)->first();
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

                            $total_amount = $amount+$total_fees;


                            $tomemberData = WalletManager::where('id', $tomember_id)->first();
                            if (!empty($tomemberData)) {
                                $tomember = $tomemberData->tomember;
                            }

                            $dateString = date('d-m-Y H:i:s');
                            $format = 'd-m-Y H:i:s';
                            // Create a DateTime object from the date string and format
                            $dateTime = DateTime::createFromFormat($format, $dateString);
                            // Get the Unix timestamp
                            $timestamp = $dateTime->getTimestamp();

                            $randomString = $this->generateRandomString(15);

                            $last_record = Issuertrxref::orderBy('id', 'desc')->first()->issuertrxref;
                            if ($last_record != "") {
                                $next_issuertrxref = $last_record + 1;
                            } else {
                                $next_issuertrxref = '140071';
                            }
                            if ($excel_transactions[0]->bdastatus != 'BDA') {
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
                                        
                                        $debit = new TransactionLedger([
                                            'user_id' => $user_id,
                                            'opening_balance' => $userRec->wallet_balance,
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
                                    }
                                    $t_inserted_record += 1;
                                }
                            } else if ($excel_transactions[0]->bdastatus == 'BDA') {
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
                                    'opening_balance' => $userRec->wallet_balance,
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

                                $t_inserted_record += 1;
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
                                if ($jsonResponse && isset($jsonResponse['error_description'])) {
                                    $errorDescription = $jsonResponse['error_description'];
                                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => $errorDescription]);
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => $errorDescription]);
                                    $n_inserted_record += 1;
                                } else {
                                    Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => __('Unable to extract error_description')]);
                                    ExcelTransaction::where('id', $row->id)->update(['remarks' => __('Unable to extract error_description')]);
                                    $n_inserted_record += 1;
                                }
                            } else {
                                ExcelTransaction::where('id', $row->id)->update(['remarks' => __('gimac server error')]);
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
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id]);

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

                Session::put(__('success_message'), $t_inserted_record . __('records inserted successfully!'));
            }

            if (!empty($total_fees_arry)) {
                $all_fees_amount = array_sum($total_fees_arry);
                UploadedExcel::where('id', $id)->update(['total_fees' => $all_fees_amount]);
            }
            // exit;
            return back();
        } elseif ($user_type == "Approver") {
            UploadedExcel::where('id', $id)->update(['status' => 3, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id]);
            Session::put('success_message', __('Record have been successfully approved and are visible in the merchant account.'));
            return back();
        } else {
            UploadedExcel::where('id', $id)->update(['status' => 1, 'approved_by' => Auth::user()->id]);
            ExcelTransaction::where('excel_id', $id)->update(['approved_by' => Auth::user()->id]);
            Session::put('success_message', __('Record have been successfully approved and are visible in the approver account.'));
            return back();
        }
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
    return view('dashboard.notifications', ['title' => $pageTitle]);
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
    return view('dashboard.number_success', ['title' => $pageTitle]);
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
        $searchValue = $search_arr['value']; // Search value
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $totalRecords = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->count();
        $totalRecordswithFilter = ExcelTransaction::select('count(*) as allcount')->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')->where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)->count();
        // DB::enableQueryLog();
        $records = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)
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
        //    echo"<pre>";print_r($records);die;
        $data_arr = array();
        foreach ($records as $record) {


            $status = $record->remarks != "" ? 'Rejected' : $this->getStatusText($record->transaction_status);

            $data_arr[] = array(
                "first_name" => $record->first_name != '' ? $record->first_name : '-',
                "name" => $record->name != '' ? $record->name : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                "country_name" => $record->country_name != '' ?  $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number,
                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "approved_by" => $record->approved_by ? $record->approved_by : 'Not Approved',
                "created_at" => date('d M,y', strtotime($record->created_at)),
                "gimac_status" => $status,
                "remarks" => $record->remarks ? $record->remarks : '-',
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

    public function operationofthismonthList(Request $request)
    {
        $pageTitle = 'operation of this month';
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length") != '-1' ?  $request->get("length") : 5;
        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');
        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $currentMonth = Carbon::now()->format('Y-m');
        $currentMonthTransactions = ExcelTransaction::whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->count();
        $totalTransactions = ExcelTransaction::where('parent_id', $parent_id)->count();
        $opt_this_month = round(($currentMonthTransactions > 0) ? ($currentMonthTransactions / $totalTransactions) * 100 : 0);

        $totalRecords = UploadedExcel::select('count(*) as allcount')->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->whereIn('status', [1, 3, 4, 5, 6])->count();
        $totalRecordswithFilter = UploadedExcel::select('count(*) as allcount')->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])->where('parent_id', $parent_id)->whereIn('status', [1, 3, 4, 5, 6])->count();
        // DB::enableQueryLog();
        $records = UploadedExcel::orderBy('created_at', 'desc')
        ->join('users', 'uploaded_excels.user_id', '=', 'users.id')
        ->whereRaw("DATE_FORMAT(uploaded_excels.created_at, '%Y-%m') = ?", [$currentMonth])
        ->where('uploaded_excels.parent_id', $parent_id)
        ->whereIn('uploaded_excels.status', [1, 3, 4, 5, 6])
        ->select('uploaded_excels.*', 'uploaded_excels.id as id', 'users.name as name', 'users.email as email', 'users.user_type as user_type')
        ->skip($start)
        ->take($rowperpage)
        ->get();

        $data_arr = array();
        foreach ($records as $record) {
            $encId = base64_encode($this->encryptContent($record->id));

            $action = '
            <a href="' . $record->id . '" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="View"></i></a>' .
            ($record->type == 0 ? '<a href="' . PUBLIC_PATH . '/assets/front/excel/' . $record->excel . '" class="" download="' . $record->excel . '"><i class="fa fa-download" aria-hidden="true" title="Download Excel"></i></a>' : '');


            $data_arr[] = array(
                "reference_id" => $record->reference_id,
                "remarks" => $record->remarks != "" ?  ucfirst($record->remarks) : 'Salary',
                "excel" => $record->excel,
                "no_of_records" => $record->no_of_records,
                "created_at" => date('d M,y', strtotime($record->created_at)),
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
            "opt_this_month" => $opt_this_month,
            "aaData" => $data_arr,
        );
        echo json_encode($response);
        die;
    }

    public function failureTransaction()
    {
        $pageTitle = 'failure-transaction';
        return view('dashboard.failure-transaction', ['title' => $pageTitle]);
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
        $searchValue = $search_arr['value']; // Search value
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;
        $totalRecords = ExcelTransaction::select('count(*) as allcount')->where('excel_transactions.parent_id', $parent_id)->whereNotNull('excel_transactions.remarks')->count();
        $totalRecordswithFilter = ExcelTransaction::select('count(*) as allcount')->where('excel_transactions.parent_id', $parent_id)->whereNotNull('excel_transactions.remarks')
        ->count();
        // DB::enableQueryLog();
        $records = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)->whereNotNull('excel_transactions.remarks')
        ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
        ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
        ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
        ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
        ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name')
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

        $data_arr = array();
        foreach ($records as $record) {
            $status = $record->remarks != "" ? 'Rejected' : $this->getStatusText($record->transaction_status);
            $data_arr[] = array(
                "first_name" => $record->first_name != '' ? $record->first_name : '-',
                "name" => $record->name != '' ? $record->name : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                "country_name" => $record->country_name != '' ?  $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number,
                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "approved_by" => $record->approved_by ? $record->approved_by : 'Not Approved',
                "created_at" => date('d M,y', strtotime($record->created_at)),
                "gimac_status" => $status,
                "remarks" => $record->remarks ? $record->remarks : '-',
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
        $searchValue = $search_arr['value']; // Search value
        $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id;

        $ledger_count = TransactionLedger::where('user_id', $parent_id)
        ->orderBy('created_at', 'ASC')
        ->count();
        $totalRecords = $ledger_count;
        $totalRecordswithFilter = $ledger_count;
        // DB::enableQueryLog();

        $records = TransactionLedger::where('user_id', $parent_id)
        ->orderBy('created_at', 'DESC')
        ->skip($start)
        ->take($rowperpage)
        ->get();
        $data_arr = array();
        foreach ($records as $record) {
            $data_arr[] = array(
                "name" => $record->User->name,
                "created_at" => date('d M Y, h:i:A', strtotime($record->created_at)),
                "actual_amount" => CURR . ' ' . $record->actual_amount,
                "type" => $record->type == 1 ? 'Credit' : 'Withdraw',
                "balance" => CURR . ' ' . $record->closing_balance,
                "action" => '<a href="' . $record->trans_id . '" data-user_id="' . $record->user_id . '" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-info-circle" aria-hidden="true"></i></a>'
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

        $totalAmount = (int)ExcelTransaction::select('count(*) as allcount')
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
                "country_name" => $record->country_name != '' ?  $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number,
                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "approved_by" => $record->approved_by ? $record->approved_by : 'Not Approved',
                "created_at" => date('d M,y', strtotime($record->created_at)),
                "gimac_status" => $status,
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

        $user_id = Auth::user()->id;

        $perPage = 10;
        // Adjust the number of items per page as needed

        // Extract the page number from the request, default to 1 if not present
        $page = $request->query('page', 1);

        $data = Notification::where('user_id', $user_id)->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json($data);
    }

    public function rejectRequest($slug)
    {
        $decode_string = base64_decode($slug);
        $id = $this->decryptContent($decode_string);

        $user_type = Auth::user()->user_type;
        $user_type = Auth::user()->id;
        echo"$user_type"; die;
        $input = Input::all();
        if (!empty($input)) {
            if ($user_type == "Submitter") {
                $reocrd = UploadedExcel::where('id', $id)->first();
                if ($reocrd->type == 0) {
                    $filename = $reocrd->excel;
                    unlink(public_path('/assets/front/excel/') . $filename);
                }
                UploadedExcel::where('id', $id)->delete();
                ExcelTransaction::where('excel_id', $id)->delete();
                Session::put('success_message', 'Record has been deleted successfully');
                return Redirect::to('/pending-approvals');
            }

            $status = 4;
            if ($user_type == "Merchant") {
                $status = 6;
            }
            $errorDescription = $input['remarks'];
            UploadedExcel::where('id', $id)->update(['status' => $status]);
            ExcelTransaction::where('excel_id', $id)->update(['remarks' => $errorDescription]);
            Session::put('success_message', 'Record has been rejected successfully');
        } else {
            Session::put('error_message', 'There was a problem to reject this record.');
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
            Session::put('error_message', 'Your monthly transfer limit has been reached.');
            return back();
        }

        //to check current week limit
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $currentWeekSum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfWeek, $endOfWeek])
        ->sum('amount');


        //  echo"<pre>";print_r($walletlimit->week_limit);die;
        if (($currentWeekSum + $amount) > $walletlimit->week_limit) {
            Session::put('error_message', 'Your weekly transfer limit has been reached.');
            return back();
        }

        // Get the sum of amounts for transactions within the current day
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();
        $currentDaySum = Transaction::where('user_id', $user_id)->whereIn('status', [1, 2])->whereBetween('created_at', [$startOfDay, $endOfDay])
        ->sum('amount');
        if (($currentDaySum + $amount) > $walletlimit->daily_limit) {
            Session::put('error_message', 'Your daily transfer limit has been reached.');
            return back();
        }
    }

    public function singleTransfer(Request $request)
    {
        $lang = Session::get('locale');
        // dd($lang);
        if (Auth::user()->user_type != "Submitter") {
            abort(404, 'Page not found');
        }
        $input=$request->all();
        $id = Auth::user()->id;

        $pageTitle = 'Single Transfer';
        $input = Input::all();

        if($lang == 'fr'){
            $country_list = Country::orderBy('name_fr', 'asc')->get();
        }else{
            $country_list = Country::orderBy('name', 'asc')->get();
        }
        
        if($lang == 'fr'){
            $wallet_manager_list = WalletManager::orderBy('name_fr', 'asc')->get();
        }else{
            $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();
        }

        if (!empty($input)) {
            $validate_data = [
                'phone' => 'required_if:option,swap_to_swap,swap_to_gimac',
            ];

            $customMessages = [
                'phone.required' => __('Phone number field can\'t be left blank'),
                'amount.required' => __('Amount field can\'t be left blank'),
            ];

            if ($_POST['option'] == 'swap_to_gimac') {
                $validate_data = [
                    'country_id' => 'required',
                    'wallet_manager_id' => 'required',
                ];

                $customMessages = [
                    'country_id.required' => __('Country field can\'t be left blank'),
                    'wallet_manager_id.required' => __('Wallet manager field can\'t be left blank'),
                ];
            }



            if ($_POST['option'] == 'swap_to_bda') {
                $validate_data = [
                    'product' => 'required',
                    'iban' => 'required',
                    'partnerreference' => 'required',
                    'reason' => 'required',
                    'rupees' => 'required',
                ];

                $customMessages = [
                    'product.required' => __('Product can\'t be left blank'),
                    'iban.required' => __('Iban field can\'t be left blank'),
                    'partnerreference.required' => __('Partner Reference field can\'t be left blank'),
                    'reason.required' => __('Reason field can\'t be left blank'),
                    'rupees.required' => __('Amount field can\'t be left blank'),
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

            $parent_id = Auth::user()->parent_id;

            $senderUser = $userDetail = User::where('id', $parent_id)->first();

            $userType = $this->getUserType($senderUser->user_type);
            $walletbalance=$senderUser->wallet_balance;
                // echo"<pre>";print_r($userType);die;

            if ($this->checkTransactionLimit($userType, $input['amount'])) {
                return $this->checkTransactionLimit($userType, $input['amount']);
            }

            $transactionLimit = TransactionLimit::where('type', $userType)->first();
                // echo"<pre>";print_r($transactionLimit);die;
            if ($senderUser->kyc_status != "completed") {
                $unverifiedKycMin = $transactionLimit->unverifiedKycMin;
                $unverifiedKycMax = $transactionLimit->unverifiedKycMax;


                if ($senderUser->kyc_status == "pending") {
                    if ($unverifiedKycMin > $request->amount) {
                        Session::put('error_message', __('The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
                        return Redirect::to('/single-transfer');
                    }

                    if ($unverifiedKycMax < $request->amount) {
                        Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is still pending.');
                        return Redirect::to('/single-transfer');
                    }
                } else {

                    if ($unverifiedKycMin > $request->amount) {
                        Session::put('error_message', __('The minimum transfer amount should be greater than') . CURR . ' ' . $unverifiedKycMin);
                        return Redirect::to('/single-transfer');
                    }

                    if ($unverifiedKycMax < $request->amount) {
                        Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $unverifiedKycMax . ' because merchant KYC is not verified.Please verify your KYC first');
                        return Redirect::to('/single-transfer');
                    }
                }
            }
            

            $total_fees = 0; 
            if ($_POST['option'] == 'swap_to_swap') {

                if ($transactionLimit->minSendMoney > $request->amount) {
                    Session::put('error_message', __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->minSendMoney);
                    return Redirect::to('/single-transfer');
                }

                if ($request->amount > $transactionLimit->maxSendMoney) {
                    Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->maxSendMoney);
                    return Redirect::to('/single-transfer');
                }


                $is_receiver_exist = User::where('phone', $input['phone'])->whereNotIn('user_type', ['Approver', 'Submitter'])->count();

                if ($is_receiver_exist == 0) {
                    Session::put('error_message', __('The receiver s mobile is not registered with Swap.'));
                    return Redirect::to('/single-transfer');
                }
                if (isset($is_receiver_exist)) {
                    $total_fees = 0;
                    $feeapply = FeeApply::where('userId',$parent_id)->where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
                    ->where('max_amount', '>=',  $request->amount)->first();
                            // echo"<pre>";print_r($feeapply);die;

                    if(isset($feeapply)){

                        $feeType=$feeapply->fee_type;
                        if ($feeType == 1) {
                            $total_fees = $feeapply->fee_amount;
                        } else {
                            $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                        }
                    }else{
                        $trans_fees = Transactionfee::where('transaction_type', 'Send Money')->where('min_amount', '<=', $request->amount)
                        ->where('max_amount', '>=',  $request->amount)->first();
                        if (!empty($trans_fees)) {
                            $feeType = $trans_fees->fee_type;
                            if ($feeType == 1) {
                                $total_fees = $trans_fees->fee_amount;
                            } else {
                                $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                            }
                        }
                    }
                    
                    if ($walletbalance < ($request->amount + $total_fees)) {
                        Session::put('error_message', __('Not sufficient balance in wallet'));
                        return redirect('/single-transfer');
                    }
                }

                
            } elseif ($_POST['option'] == 'swap_to_bda') {
                if ($transactionLimit->bdaMin > $request->rupees) {
                    Session::put('error_message', __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->bdaMin);
                    return Redirect::to('/single-transfer');
                }

                if ($request->rupees > $transactionLimit->bdaMax) {
                    Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->bdaMax);
                    return Redirect::to('/single-transfer');
                }



                $total_fees = 0;

                $feeapply = FeeApply::where('userId',$parent_id)->where('transaction_type', 'Money Transfer Via BDA')->where('min_amount', '<=', $request->rupees)
                ->where('max_amount', '>=',  $request->rupees)->first();
                    // echo"<pre>";print_r($feeapply);die;

                if(isset($feeapply)){

                    $feeType=$feeapply->fee_type;
                    if ($feeType == 1) {
                        $total_fees = $feeapply->fee_amount;
                    } else {
                        $total_fees = number_format(($rupees * $feeapply->fee_amount / 100), 2, '.', '');
                    }
                }else{
                    $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via BDA')->where('min_amount', '<=', $request->rupees)
                    ->where('max_amount', '>=',  $request->rupees)->first();
                    if (!empty($trans_fees)) {
                        $feeType = $trans_fees->fee_type;
                        if ($feeType == 1) {
                            $total_fees = $trans_fees->fee_amount;
                        } else {
                            $total_fees = number_format(($rupees * $trans_fees->fee_amount / 100), 2, '.', '');
                        }
                    }
                }

                if ($walletbalance < ($request->rupees + $total_fees)) {
                    Session::put('error_message', __('Not sufficient balance in wallet'));
                    return redirect('/single-transfer');
                }  


            } else {

                if ($transactionLimit->gimacMin > $request->amount) {
                    Session::put('error_message', __('You cannot transfer less than') . CURR . ' ' . $transactionLimit->gimacMin);
                    return Redirect::to('/single-transfer');
                }

                if ($request->amount > $transactionLimit->gimacMax) {
                    Session::put('error_message', __('You cannot transfer more than') . CURR . ' ' . $transactionLimit->gimacMax);
                    return Redirect::to('/single-transfer');
                }

                $total_fees = 0;

                $feeapply = FeeApply::where('userId',$parent_id)->where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $request->amount)
                ->where('max_amount', '>=',  $request->amount)->first();
                    // echo"<pre>";print_r($feeapply);die;

                if(isset($feeapply)){

                    $feeType=$feeapply->fee_type;
                    if ($feeType == 1) {
                        $total_fees = $feeapply->fee_amount;
                    } else {
                        $total_fees = number_format(($amount * $feeapply->fee_amount / 100), 2, '.', '');
                    }
                }else{
                    $trans_fees = Transactionfee::where('transaction_type', 'Money Transfer Via GIMAC')->where('min_amount', '<=', $request->amount)
                    ->where('max_amount', '>=',  $request->amount)->first();
                    if (!empty($trans_fees)) {
                        $feeType = $trans_fees->fee_type;
                        if ($feeType == 1) {
                            $total_fees = $trans_fees->fee_amount;
                        } else {
                            $total_fees = number_format(($amount * $trans_fees->fee_amount / 100), 2, '.', '');
                        }
                    }
                }
                if ($walletbalance < ($request->amount + $total_fees)) {
                    Session::put('error_message', __('Not sufficient balance in wallet'));
                    return redirect('/single-transfer');
                }  

            }

            /*if ($walletbalance < ($request->amount + $trans_fees)) {
                Session::put('error_message', __('Not sufficient balance in wallet'));
                return redirect('/single-transfer');
            }*/


            if ($_POST['option'] == 'swap_to_bda') {
                if (!empty($input)) {
                    $remittanceData = new RemittanceData();
                    $refNoLo = $this->generateUniqueReference();
                    $remittanceData->transactionId = $this->generateAndCheckUnique(); // Assuming this method exists
                    $remittanceData->product = $request->input('product', '');
                    $remittanceData->iban = $request->input('iban', '');
                    $remittanceData->titleAccount = 'DEMO001'; // Hardcoded as per original
                    $remittanceData->amount = $request->input('rupees', '');
                    $remittanceData->partnerreference = $request->input('partnerreference', '');
                    $remittanceData->reason = $request->input('reason', '');
                    $remittanceData->userId = $id;
                    $remittanceData->referenceLot = $refNoLo;
                    $remittanceData->type = 'bank_transfer';
                    $remittanceData->save();

                    $refrence_id = time() . rand();
                    $totalAmount = $input['amount'];
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
                    
                    ExcelTransaction::create([
                        'excel_id' => $uploadedExcel->id,
                        'parent_id' => Auth::user()->parent_id,
                        'submitted_by' => Auth::user()->id,
                        'first_name' => '',
                        'name' => '', 
                        'country_id' => '',
                        'wallet_manager_id' => '',
                        'tel_number' =>  '',
                        'amount' => $input['rupees'],
                        'fees' => $total_fees,
                        'comment' => $input['reason'],
                        'bdastatus'=>'BDA'
                    ]);
                    
                    
                    $sender_wallet_amount = $senderUser->wallet_balance - ($input['rupees']+$total_fees);
                    $sender_hold_amount = $senderUser->holdAmount + ($input['rupees']+$total_fees);
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount,'holdAmount' => $sender_hold_amount]);

                    Session::put('success_message', __('Single transaction has been saved successfully'));
                    return Redirect::to('/pending-approvals');
                }
            }else{
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

                $sender_wallet_amount = $senderUser->wallet_balance - ($totalAmount+$total_fees);
                $sender_hold_amount = $senderUser->holdAmount + ($totalAmount+$total_fees);
                User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount,'holdAmount' => $sender_hold_amount]);
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

            ExcelTransaction::create([
                'excel_id' => $uploadedExcel->id,
                'parent_id' => Auth::user()->parent_id,
                'submitted_by' => Auth::user()->id,
                'first_name' => $first,
                'name' => $name,
                'comment' => $input['comment'],
                'country_id' => $country_id,
                'wallet_manager_id' => $wallet_manager_id,
                'tel_number' =>  $input['country_code'] . $input['phone'],
                'amount' => $input['amount'],
                'fees' => $total_fees,
                'comment' => $input['comment']
            ]);



            Session::put('success_message', __('Single transaction has been saved successfully'));
            return Redirect::to('/pending-approvals'); 
        } 
        // echo"<pre>";print_r($lang);die;

        return view('dashboard.single_transfer', ['title' => $pageTitle, 'country_list' => $country_list, 'wallet_manager_list' => $wallet_manager_list,'lang'=>$lang]);
    }

    public function bulkTransfer(Request $request)
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

            if ($errorCount > 0) {
                $responseMessage = $errorCount . ' ' . __('records not transferred due to errors.');
                $responseType = __('error_message');
                /* Session::flash('error_message', $responseMessage);
                return Redirect::to('/bulk-transfer'); */
            } else {
                $responseMessage = __('Excel has been uploaded successfully');
                $responseType = __('success_message');
            }
            // echo"<pre>";print_r($totalAmount);die;

            if (!empty($duplicateNumbers)) {
                Session::put('error_message', __(" Duplicate numbers found: " . implode(', ', $duplicateNumbers) . '.'));
                return Redirect::to('/bulk-transfer');
            }
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

                $uploadedExcel = UploadedExcel::create([
                    'user_id' => Auth::user()->id,
                    'parent_id' => Auth::user()->parent_id,
                    'excel' => $image,
                    'reference_id' => $refrence_id,
                    'no_of_records' => $rowCount,
                    'totat_amount' => $totalAmount,
                    'remarks' => $input['remarks'],
                ]);

                $fees_arr = array();

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


                    $is_receiver_exist = User::where('phone', $data['tel_number'])->whereNotIn('user_type', ['Approver', 'Submitter'])->first(); 
                    
                    $total_fees = 0; 
                    
                    $trans_type= $is_receiver_exist ? 'Send Money' : 'Money Transfer Via GIMAC';

                    $feeapply = FeeApply::where('userId',$parent_id)->where('transaction_type', $trans_type)->where('min_amount', '<=', $data['amount'])
                    ->where('max_amount', '>=',  $data['amount'])->first();
                    // echo"<pre>";print_r($feeapply);die;

                    if(isset($feeapply)){

                        $feeType=$feeapply->fee_type;
                        if ($feeType == 1) {
                            $total_fees = $feeapply->fee_amount;
                        } else {
                            $total_fees = number_format(($data['amount'] * $feeapply->fee_amount / 100), 2, '.', '');
                        }
                        $fees_arr[] = $total_fees;
                    }else{
                        $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $data['amount'])
                        ->where('max_amount', '>=',  $data['amount'])->first();
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
                //     $trans_fees = Transactionfee::where('transaction_type', $trans_type)->where('min_amount', '<=', $data['amount'])
                //     ->where('max_amount', '>=',  $data['amount'])->first();


                // if ($trans_fees) {
                //     $feeType = $trans_fees->fee_type;
                //     if ($feeType == 1) {
                //         $total_fees = $trans_fees->fee_amount;
                //     } else {
                //         $total_fees = number_format(($data['amount'] * $trans_fees->fee_amount / 100), 2, '.', '');
                //     }
                //     $fees_arr[] = $total_fees;
                // }

                // echo"<pre>";print_r($total_fees);die;

                    /* Amit Jangid*/ 

                    $totalAmtWithFee = ($totalAmount+array_sum($fees_arr));
                    if ($senderUser->wallet_balance <= $totalAmtWithFee) {
                        unlink($path);
                        Session::put('error_message','Insufficient Balance !');
                        return Redirect::to('/bulk-transfer');
                    }

                    $sender_wallet_amount = $senderUser->wallet_balance - $totalAmtWithFee;
                    $sender_hold_amount = $senderUser->holdAmount + $totalAmtWithFee;
                    User::where('id', $parent_id)->update(['wallet_balance' => $sender_wallet_amount,'holdAmount' => $sender_hold_amount]);

                    /**/

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
                        'fees' => $total_fees
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
    }

    public function getWalletmanagerList(Request $request)
    {

        $input = Input::all();
        if (!empty($input)) {
            $country_id = $input['country_id'];
            $walletManager = WalletManager::where('country_id', $country_id)->get();
            return view('dashboard.wallet_manager', ['walletManager' => $walletManager]);
        }
    }

    public function getStatus($status)
    {

        $status_list = array(0 => 'Pending', 1 => 'Approved by ', 2 => 'Rejected by ', 3 => 'Approved by ', 4 => 'Rejected by ', 5 => 'Approved by ', 6 => 'Rejected by ');

        return $status_list[$status];
    }

    private function getStatusText($status)
    {

        if ($status != "") {
            $statusArr = array('1' => 'Completed', '2' => 'Pending', '3' => 'Failed', '4' => 'Reject', '5' => 'Refund', '6' => 'Refund Completed');
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
        ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status', 'transactions.receiver_id')
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



            // echo"<pre>";print_r($record);die;

            $status = ($record->remarks != "")
            ? 'Rejected'
            : $this->getStatusText($record->transaction_status);

            $remarks = '';

            if ($record->remarks == null) {
                $remarks = '-';
            }else{
                $remarks = $record->remarks;
            }   
            if($record->bdastatus == " " ){
                if ($record->first_name === null) {
                    $remark = __('First name is missing');
                } elseif ($record->name === null) {
                    $remark = __('Last name is missing');
                } elseif ($record->first_name === null && $record->name === null) {
                    $remark = __('First name and Last name are missing');
                } elseif($record->remarks == 'Insufficient Balance !'){
                    $remark =  "Solde insuffisant !";

                }else{
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

            global $months;
            $lang = Session::get('locale');

            if($lang == 'fr'){
                $date = date('d F, Y', strtotime($record->created_at));
                $frenchDate = str_replace(array_keys($months), $months, $date);


            }else{
                $frenchDate = date('d M,y', strtotime($record->created_at));


            }



            // echo"<pre>";print_r($lang);die;

            $is_receiver_exist = User::where('phone', $record->tel_number)->whereNotIn('user_type', ['Approver', 'Submitter'])->first();
            if (isset($is_receiver_exist)) {

                $record->first_name = $is_receiver_exist->name;
            }

            //echo"<pre>";print_r($record);die;
            $data_arr[] = array(
                "first_name" => $record->first_name != '' ? $record->first_name : '-',
                "name" => $record->name != '' ? $record->name : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                "country_name" => $record->country_name != '' ?  $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number,
                "amount" => CURR . ' ' . $record->amount,
                "submitted_by" => $record->submitted_by,
                "approved_by" => $record->approved_by ? $record->approved_by : __('Not Approved'),
                "created_at" => $frenchDate,
                "gimac_status" => $status,
                "remarks" => $remarks,
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

    public function ExportExcel()
    {
        $file_name = 'report_' . date('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new ReportExport1, $file_name);
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
        $user = Auth::user();
        if ($request->method() == 'GET') {
            $pageTitle = 'Add Beneficiary';
            $country_list = Country::where('status', '1')->get();
            $wallet_manager_list = WalletManager::orderBy('name', 'asc')->get();

            return view('dashboard.addBeneficiary', [
                'title' => $pageTitle,
                'country_list' => $country_list,
                'wallet_manager_list' => $wallet_manager_list
            ]);
        }

        // Check if entry with same phone and same account exists
        $oldEntry = Beneficiary::where([
            ['accountNumber', $request->accountNumber]
        ])->count();

        if ($oldEntry !== 0) {
            return redirect()
            ->back()
            ->with('error_message', 'Please fill values correctly')
            ->withInput();
        }

        if ($request->country === null) {
            return redirect()
            ->back()
            ->with('error_message', 'Please Select the country')
            ->withInput();
        }

        $create = [
            'userId' => $user->id,
            'parentId' => $user->parent_id,

            'name' => $request->name ?? null,
            'country' => $request->country ?? null,
            'address' => $request->address ?? null,
            'telephone' => $request->telephoneNum ?? null,
            'walletManagerId' => $request->wallet_manager_id ?? null,
            'bankCode' => $request->bankCode ?? null,
            'branchCode' => $request->branchCode ?? null,
            'accountNumber' => $request->accountNumber ?? null,
            'ribKey' => $request->ribKey ?? null,
        ];

        Beneficiary::create($create);

        return redirect()
        ->to('beneficiary-list')
        ->with('success_message', 'Beneficiary Added successfully');
    }

    public function allBeneficiaryList(Request $request)
    {
        $user = Auth::user();
        $draw = $request->get('draw');
        $start = $request->get("start");

        $columns = [
            'id',
            'name',
            'id',/* 'countries.name', */
            'address',
            'telephone',
            'id',/* 'wallet_managers.name', */
            'bankCode',
            'branchCode',
            'accountNumber',
            'ribKey',
            'createdAt',
        ];

        $sortBy = $columns[$request->order[0]['column']];
        $orderBy = $request->order[0]['dir'];

        $rowperpage = $request->get("length") != '-1'
        ? $request->get("length")
        : 5;

        $allBeneficiary = Beneficiary::where(function ($query) use ($user, $request) {
            $query->where('userId', $user->id);

            if ($request->has('search')) {
                $search = $request->search;
                $ucSearch = strtoupper($request->search);

                $query
                ->where('name', 'LIKE', "%{$search}%")
                ->orWhere('name', 'LIKE', "%{$ucSearch}%")
                ->orWhere('address', 'LIKE', "%{$search}%")
                ->orWhere('bankCode', 'LIKE', "%{$search}%")
                ->orWhere('branchCode', 'LIKE', "%{$search}%")
                ->orWhere('accountNumber', 'LIKE', "%{$search}%")
                ->orWhere('ribKey', 'LIKE', "%{$search}%")
                ->orWhereHas('walletManager', function ($subQuery) use ($search, $ucSearch) {
                    $subQuery
                    ->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$ucSearch}%");
                })
                ->orWhereHas('countryDetail', function ($subQuery) use ($search, $ucSearch) {
                    $subQuery
                    ->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$ucSearch}%");
                });
            }
        })
        ->skip($start)->take($rowperpage)
        ->orderBy($sortBy, $orderBy)
        ->with([
            'countryDetail',
            'walletManager',
        ])
        ->get();


        return response()->json([
            'draw' => intval($draw),
            'iTotalRecords' => $allBeneficiary->count(),
            'iTotalDisplayRecords' => $allBeneficiary->count(),
            'aaData' => $allBeneficiary->map(function ($item) {
                $checked = ($item->status) ? 'checked' : '';
                $status = ($item->status) ? 'Active' : 'Inactive';

                return [
                    'name' => $item->name,
                    'country' => $item->countryDetail->name ?? '',

                    'address' => $item->address,

                    'walletManager' => $item->walletManager->name ?? '',

                    'telephone' => $item->telephone,

                    'bankCode' => $item->bankCode,
                    'branchCode' => $item->branchCode,
                    'accountNumber' => $item->accountNumber,
                    'ribKey' => $item->ribKey,
                    'createdAt' => $item->created_at->format('d M, Y'),
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
            }),
        ]);
    }

    public function toggleBeneficiaryStatus(Beneficiary $id, $status)
    {
        $id->update([
            'status' => $status
        ]);

        return [
            'status' => true,
            'message' => ((bool) $status)
            ? 'Beneficiary active successfully'
            : 'Beneficiary inactive successfully'
        ];
    }

    public function changePassword(Request $request)
    {
        $pageTitle = 'Change Password';
        $input = Input::all();
        if($input)
        {
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
                'current_password.required' => 'Enter current password',
                'password.required' => 'Enter new password',
                'password.min' => 'Password must be at least 6 characters long',
            ];

            $validator = Validator::make($input, $validate_data,$customMessages);
            if ($validator->fails()) {
                $messages = $validator->messages();
                return Redirect::to('/change-password')->withErrors($messages);
            }

            $id = Auth::user()->id;

            $user = User::where('id', $id)->first();

            if (!Hash::check($request->input('current_password'), $user->password)) {
                Session::put('error_message', 'Your current password didn`t matched!');
                return Redirect::to('/change-password');
            }

            $user->password = Hash::make($request->input('password'));
            $user->save();
            Session::put('success_message', 'Your password has been updated successfully');
            return Redirect::to('/logout');
        }
        return view('dashboard.change_password', ['title' => $pageTitle]);
    }

    public function bankBDA(Request $request)
    {
        $pageTitle = 'BDA Payment';
        $input = $request->all();
        $id = Auth::user()->id;


        // Validation rules and custom messages
        // $validate_data = [
        //     'product' => 'required|string',
        //     'iban' => 'required|string',
        //     'partnerreference' => 'required|string|max:255',
        //     'reason' => 'required|string',
        //     'amount' => 'required|numeric|min:1|max:999999',
        // ];

        // $customMessages = [
        //     'product.required' => 'The product field is required.',
        //     'product.string' => 'The product must be a valid string.',

        //     'iban.required' => 'The IBAN field is required.',
        //     'iban.string' => 'The IBAN must be a valid string.',
        
        //     'partnerreference.required' => 'The partner reference field is required.',
        //     'partnerreference.string' => 'The partner reference must be a valid string.',
        //     'partnerreference.max' => 'The partner reference must not exceed 255 characters.',
        
        //     'amount.required' => 'The amount field is required.',
        //     'amount.numeric' => 'The amount must be a number.',
        //     'amount.min' => 'The amount must be at least 1.',
        //     'amount.max' => 'The amount must not exceed 999,999.',
        
        //     'reason.required' => 'The reason field is required.',
        //     'reason.string' => 'The reason must be a valid string.',
        // ];

        // // Validate request data
        // $validator = Validator::make($input, $validate_data, $customMessages);

        // if ($validator->fails()) {
        //     return Redirect::to('/bdapayment')->withInput()->withErrors($customMessages);
        // }

        // Save remittance data if input is valid
        if (!empty($input)) {
            $remittanceData = new RemittanceData();
            $refNoLo = $this->generateUniqueReference();

            // Set remittance data
            $remittanceData->transactionId = $this->generateAndCheckUnique(); // Assuming this method exists
            $remittanceData->product = $request->input('product', '');
            $remittanceData->iban = $request->input('iban', '');
            $remittanceData->titleAccount = 'DEMO001'; // Hardcoded as per original
            $remittanceData->amount = $request->input('amount', '');
            $remittanceData->partnerreference = $request->input('partnerreference', '');
            $remittanceData->reason = $request->input('reason', '');
            $remittanceData->userId = $id;
            $remittanceData->referenceLot = $refNoLo;
            $remittanceData->type = 'bank_transfer';
            $remittanceData->save();
            // If remittance data is successfully saved
            if (!empty($remittanceData->transactionId)) {
                // Construct request data to send to the API
                $data = [
                    'referenceLot' => $refNoLo,
                    'nombreVirement' => 1,
                    'montantTotal' => $input['amount'],
                    'produit' => $input['product'],
                    'virements' => [
                        [
                            'ibanCredit' => $input['iban'],
                            'intituleCompte' => $remittanceData->titleAccount,
                            'montant' => $input['amount'],
                            'referencePartenaire' => $input['partnerreference'],
                            'motif' => $input['reason'],
                            'typeVirement' => 'RTGS'
                        ]
                    ]
                ];

                $certificate = public_path("CA Bundle.crt");
                $client = new Client([
                    'verify' => $certificate,
                ]); 

                try {
                    $response = $client->post('https://survey-apps.bda-net.ci/transfert/v2.0/virements', [
                        'json' => $data,
                        'headers' => [
                            'x-api-key' => 'RZdJqzjrkVoapWaRCjGmIQkukdOL8e39JQrmW+gH9B+DIjnJbEh1AmUV26OLPAjblWS8jkjAo9j6pMHJOx/sMoPtkB32ha/brVKNJrT3++Qpu+qFa1T2mPVGqKgeGUOGM1QxU71Ts0xnsGpq7IQfX2IA3YGYnJhS8fD+Ggvf2N4KHz9qH6+/Yuj9lxtUNyEN1x57YFkogOjPLqvgdfVk3fbl4p5UgxZyEF+RUiPojpsgsMPfM3dewwd7ysgwlzLv',
                            'x-client-id' => '9ca1a01c-a55a-4c1c-a5b9-ec09b5aea768',
                        ],
                    ]);

                    // Decode the response JSON
                    $responseBody = json_decode($response->getBody(), true);

                    if (isset($responseBody['statut'])) {
                        $remittanceData->status = $responseBody['statut'];
                    } 
                    $remittanceData->save();

                    // Set a success message in the session
                    Session::put('success_message', 'Your payment status : ' . $responseBody['statut']); 

                    // Redirect back to /bdapayment with the success message
                    return redirect('/bdapayment');
                } catch (\Exception $e) {
                    // Handle the exception and return a JSON response with the error message
                    return response()->json(['error' => $e->getMessage()], 500);
                }
            }
        }


        return view('dashboard.bankbda', ['title' => $pageTitle]);
    }
    
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

    public function generateAndCheckUnique() {
        do {
            // Generate a random alphanumeric string
            $randomString = $this->generateRandomAlphaNumeric();

            // Check if the generated string exists in the transactions table
            $exists = RemittanceData::where('transactionID', $randomString)->exists();
        } while ($exists);

        return $randomString;
    }

    private function generateRandomAlphaNumeric() {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $shuffled = str_shuffle($characters);
        return substr($shuffled, 0, 10);
    }

    
}
