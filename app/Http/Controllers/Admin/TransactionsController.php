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
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Admin;
use App\Kyc;
use Mail;
use App\Models\ExcelTransaction;
use App\Mail\SendMailable;
use App\Models\RemittanceData;
use App\Exports\ReportExport;
use App\Exports\GimacReportExport;
use App\Exports\BdaReportExport;
use App\Exports\AirtelReportExport;
use App\Exports\VisaReportExport;
use App\Exports\ExternalReportExport;
use App\Exports\OnafriqReportExport;
use App\Exports\IndividualReportExport;
use App\Exports\ReferralReportExport;
use App\Exports\CompleteTransactionExport;
use Excel;

class TransactionsController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_adminlogin');
    }

    public function index(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'transactions');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'acttransactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Transactions';
        $activetab = 'acttransactions';
        //\DB::enableQueryLog();
        $query = Transaction::query()
            ->leftJoin('onafriqa_data as onafriq', 'transactions.excel_trans_id', '=', 'onafriq.excelTransId')
            ->leftJoin('excel_transactions', 'transactions.excel_trans_id', '=', 'excel_transactions.id')
            ->leftJoin('users as receiver_user', 'transactions.receiver_id', '=', 'receiver_user.id') 
            ->leftJoin('remittance_data as remittance', 'transactions.excel_trans_id', '=', 'remittance.excel_id');
        $query = $query->sortable();

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Verify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 1));
                Session::flash('success_message', "Records are verified successfully.");
            } else if ($action == "Unverify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 0));
                Session::flash('success_message', "Records are unverified successfully.");
            } else if ($action == "Delete") {
                Transaction::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('Aggregator', function ($q) use ($keyword) {
                        $q = $q->where('username', 'like', '%' . $keyword . '%');
                    })
                    ->orWhere('onafriq.senderName', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $receiver = $request->get('receiver');
            $query = $query->where(function ($q) use ($receiver) {
                $q
                    ->orWhereHas('Receiver', function ($q) use ($receiver) {
                        $q = $q->where('name', 'like', '%' . $receiver . '%');
                    })
                    ->orWhereHas('Aggregator', function ($q) use ($receiver) {
                        $q = $q->where('username', 'like', '%' . $receiver . '%');
                    })
                    ->orWhere('onafriq.recipientName', 'like', '%' . $receiver . '%');
            });
        }

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {
                $q->orWhereHas('User', function ($q) use ($keyword) {
                    $q = $q->where('phone', 'like', '%' . $keyword . '%');
                })
                    ->orWhere('onafriq.senderMsisdn', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $receiver = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($receiver) {
                $q = $q->where('receiver_mobile', 'like', '%' . $receiver . '%');
                $q->orWhereHas('Receiver', function ($q) use ($receiver) {
                    $q = $q->where('phone', 'like', '%' . $receiver . '%');
                })
                    ->orWhere('onafriq.recipientMsisdn', 'like', '%' . $receiver . '%');
            });
        }

        if ($request->has('type') && $request->get('type')) {
            $type = $request->get('type');
            $query = $query->where(function ($q) use ($type) {
                if ($type == 'Debit') {
                    $q->orWhere('trans_type', 2);
                } elseif ($type == 'Credit') {
                    $q->orWhere('trans_type', 1);
                } elseif ($type == 'Request') {
                    $q->orWhere('trans_type', 4);
                }
            });
        }

        if ($request->has('for') && $request->get('for')) {
            $for = $request->get('for');
            $query = $query->where(function ($q) use ($for) {
                if ($for != "gimac_transfer") {
                    $q->orWhere('payment_mode', 'like', '%' . $for . '%');
                } else {
                    $q->orWhere('receiver_id', 0);
                }
            });
        }

        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('refrence_id', 'like', '%' . $refrence . '%')
                    ->orWhere('onafriq.transactionId', $refrence)
                    ->orWhere('remittance.transactionId', $refrence);
            });
        }


        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }

        if ($request->has('to1') && $request->get('to1')) {
            $dateQ1 = explode("/", $request->get('to1'));
            $from1 = $dateQ1[0] . " 00:00:00";
            $too1 = $dateQ1[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from1, $too1) {
                $q->whereBetween('transactions.updated_at', array($from1, $too1));
            });
        }

        // $query = $query->where('transactions.status',1);
        $query->select(
            'transactions.id',
            'transactions.transactionId as cardTransId',
            'transactions.user_id',
            'transactions.receiver_id',
            'transactions.receiver_mobile',
            'transactions.payment_mode',
            'transactions.refrence_id',
            'transactions.status',
            'transactions.bda_status',
            'transactions.country_id',
            'transactions.walletManagerId',
            'transactions.senderName as transaction_sender_name',
            'transactions.senderLastName as transaction_sender_lastname',
            'transactions.amount',
            'transactions.transactionType',
            'transactions.total_amount',
            'transactions.transaction_amount',
            'transactions.created_at',
            'transactions.updated_at',
            'onafriq.transactionId as onafriq_transaction_id',
            'onafriq.recipientMsisdn',
            'onafriq.recipientName',
            'onafriq.recipientSurname',
            'onafriq.senderMsisdn',
            'onafriq.senderName as onafriq_sender_name',
            'onafriq.senderSurname as onafriq_sender_surname',
            'remittance.iban',
            'remittance.reason',
            'remittance.titleAccount',
            'remittance.transactionId as remittance_transaction_id',
            'transactions.transactionDate',
            'transactions.transactionTime',
            'transactions.merchantName',
            'transactions.merchantCountry',
            'transactions.description',
            'transactions.accountId',
            'transactions.trans_type',
            'excel_transactions.first_name as externalFirstName',
            'excel_transactions.name as externalName',
            'receiver_user.phone as externalPhone',
        );

        /* $total['amount'] = (clone $query)->sum('transactions.amount');
        $total['fee'] = (clone $query)->sum('transaction_amount'); */

        $query->groupBy('transactions.id');
        $users = $query->orderBy('transactions.id', 'DESC')->paginate(20);
        // dd($users->User);
        $total = Transaction::selectRaw('SUM(transactions.amount) as total_amount_a, SUM(transactions.transaction_amount) as total_fee_a')->first();

        if ($request->ajax()) {
            $allDataIsBlank = collect($_POST)->except('_token')->filter(function ($item) {
                return !empty($item);  // Keep only non-empty values
            })->isEmpty();


            if (!$allDataIsBlank) {
                $totalsQuery = clone $query;
                $total = $totalsQuery->selectRaw('SUM(transactions.amount) as total_amount_a, SUM(transactions.transaction_amount) as total_fee_a')->get();

                $total = $total->reduce(function ($carry, $item) {
                    return [
                        'total_amount_a' => $carry['total_amount_a'] + $item->total_amount_a,
                        'total_fee_a' => $carry['total_fee_a'] + $item->total_fee_a,
                    ];
                }, ['total_amount_a' => 0, 'total_fee_a' => 0]);

                /* $total['total_amount_a'] = (clone $query)->sum('transactions.amount');
                $total['total_fee_a'] = (clone $query)->sum('transaction_amount'); */
            }
            /* $totalsQuery = clone $query; 
            $total = $totalsQuery->selectRaw('SUM(transactions.amount) as total_amount_a, SUM(transactions.transaction_amount) as total_fee_a')->first(); */
            return view('elements.admin.transactions.index', ['allrecords' => $users, 'total' => $total]);
        }
        //dd(\DB::getQueryLog()); 
        return view('admin.transactions.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'total' => $total]);
    }

    public function adminTrans(Request $request)
    {
        // $access = $this->getRoles(Session::get('adminid'), 17);
        // if ($access == 0) {
        //     return Redirect::to('admin/admins/dashboard');
        // }

        $adminList = Admin::where('status', 1)->orderBy('username', 'ASC')->pluck('username', 'id')->all();

        $pageTitle = 'Manage Admin Transactions';
        $activetab = 'actadmintransactions';
        $query = new Transaction();
        $query = $query->sortable();
        $query = $query->where('add_by', '!=', '');

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Verify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 1));
                Session::flash('success_message', "Records are verified successfully.");
            } else if ($action == "Unverify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 0));
                Session::flash('success_message', "Records are unverified successfully.");
            } else if ($action == "Delete") {
                Transaction::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('user') && $request->get('user')) {
            $keyword = $request->get('user');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        // if ($request->has('receiver') && $request->get('receiver')) {
        //     $receiver = $request->get('receiver');
        //     $query = $query->where(function($q) use ($receiver) {
        //         $q
        //                 ->orWhereHas('Admin', function($q) use ($receiver) {
        //                     $q = $q->where('username', 'like', '%' . $receiver . '%');
        //                 });
        //     });
        // }

        if ($request->has('user_phone') && $request->get('user_phone')) {
            $keyword = $request->get('user_phone');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('phone', 'like', '%' . $keyword . '%');
                    });
            });
        }

        // if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
        //     $receiver = $request->get('receiver_phone');
        //     $query = $query->where(function($q) use ($receiver) {
        //         $q
        //                 ->orWhereHas('Receiver', function($q) use ($receiver) {
        //                     $q = $q->where('phone', 'like', '%' . $receiver . '%');
        //                 });
        //     });
        // }

        //        if ($request->has('type') && $request->get('type')) {
//            $type = $request->get('type');
//            $query = $query->where(function($q) use ($type) {
//                if ($type == 'Debit') {
//                    $q->orWhere('trans_type', 2);
//                } elseif ($type == 'Credit') {
//                    $q->orWhere('trans_type', 1);
//                } elseif ($type == 'Request') {
//                    $q->orWhere('trans_type', 4);
//                }
//            });
//        }

        if ($request->has('for') && $request->get('for')) {
            $for = $request->get('for');
            $query = $query->where(function ($q) use ($for) {
                $q->orWhere('payment_mode', 'like', '%' . $for . '%');
            });
        }

        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('refrence_id', 'like', '%' . $refrence . '%');
            });
        }

        if ($request->has('last_updated_by')) {
            $last_updated_by = $request->get('last_updated_by');
            $query = $query->where(function ($q) use ($last_updated_by) {
                if ($last_updated_by != '') {
                    $q->where('add_by', $last_updated_by);
                }
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('created_at', array($from, $too));
            });
        }

        $users = $query->orderBy('id', 'DESC')->paginate(20);

        $total['amount'] = $query->sum('amount');
        if ($request->ajax()) {
            return view('elements.admin.transactions.adminTrans', ['allrecords' => $users, 'total' => $total]);
        }
        return view('admin.transactions.adminTrans', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'total' => $total, 'adminList' => $adminList]);
    }

    public function transactionHistory($userSlug = null, Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'transactionHistory');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'acttransactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $userInfo = User::where('slug', $userSlug)->first();
        if ($userInfo->user_type == 'Merchant' || $userInfo->user_type == 'agent') {
            $uType = strtolower($userInfo->user_type) . 's';
        } else {
            $uType = 'users';
        }

        if ($userInfo->isBulkUser == 1) {
            $activetab = 'bulkpaymentmerchant';
        } else {
            $activetab = 'act' . $uType;
        }


        $pageTitle = 'Manage Transaction History';
        //        $activetab = 'acttransactions';
        $query = new Transaction();
        $query = $query->sortable();

        $user_id = $userInfo->id;
        //        $query = $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);

        $query = $query->where(function ($q) use ($user_id) {
            $q->where('user_id', $user_id)
                ->orwhere('receiver_id', $user_id);
        });

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Verify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 1));
                Session::flash('success_message', "Records are verified successfully.");
            } else if ($action == "Unverify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 0));
                Session::flash('success_message', "Records are unverified successfully.");
            } else if ($action == "Delete") {
                Transaction::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('Aggregator', function ($q) use ($keyword) {
                        $q = $q->where('username', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $receiver = $request->get('receiver');
            $query = $query->where(function ($q) use ($receiver) {
                $q
                    ->orWhereHas('Receiver', function ($q) use ($receiver) {
                        $q = $q->where('name', 'like', '%' . $receiver . '%');
                    })
                    ->orWhereHas('Aggregator', function ($q) use ($receiver) {
                        $q = $q->where('username', 'like', '%' . $receiver . '%');
                    });
            });
        }

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('phone', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $receiver = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($receiver) {
                $q
                    ->orWhereHas('Receiver', function ($q) use ($receiver) {
                        $q = $q->where('phone', 'like', '%' . $receiver . '%');
                    });
            });
        }
        if ($request->filled('accountId')) {
            $accountId = $request->get('accountId');

            $query->where('transactions.accountId', $accountId);
        }


        if ($request->has('type') && $request->get('type')) {
            $type = $request->get('type');
            $query = $query->where(function ($q) use ($type) {
                if ($type == 'Debit') {
                    $q->orWhere('trans_type', 2);
                } elseif ($type == 'Credit') {
                    $q->orWhere('trans_type', 1);
                }
            });
        }

        if ($request->has('for') && $request->get('for')) {
            $for = $request->get('for');
            $query = $query->where(function ($q) use ($for) {
                $q->orWhere('payment_mode', 'like', '%' . $for . '%');
            });
        }

        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('refrence_id', 'like', '%' . $refrence . '%');
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


        //        $query1 = $query;
//        $query1 = $query1->where(function($q) use ($user_id){
//                            $q->where('user_id',$user_id)
//                            ->orwhere('user_id',$user_id);
//                        });

        $total['amount'] = $query->orderBy('id', 'DESC')->sum('total_amount');
        $total['fee'] = $query->orderBy('id', 'DESC')->sum('transaction_amount');
        $total_amount_formatted = number_format($total['amount'], 0, '.', ',');
        $total_fee_formatted = number_format($total['fee'], 0, '.', ',');

        $users = $query->orderBy('id', 'DESC')->paginate(20);

        if ($request->ajax()) {
            return view('elements.admin.transactions.transactionHistory', ['allrecords' => $users, 'totalfee' => $total_fee_formatted, 'totalamount' => $total_amount_formatted, 'userInfo' => $userInfo, 'slug' => $userSlug]);
        }
        return view('admin.transactions.transactionHistory', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'totalfee' => $total_fee_formatted, 'totalamount' => $total_amount_formatted, 'userInfo' => $userInfo, 'slug' => $userSlug]);

    }

    public function delete($slug = null)
    {
        if ($slug) {
            Transaction::where('id', $slug)->delete();
            Session::flash('success_message', "Transaction details deleted successfully.");
            return Redirect::to('admin/transactions');
        }
    }
    public function earning(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'earning');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actearnings';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Balance Management';
        $activetab = 'actearnings';
        $query = Transaction::with('User');

        $query = $query->sortable();

        $query = $query->where('status', 1);

        $query = $query->where('transaction_amount', '!=', '0.00');


        /* if ($request->has('chkRecordId') && $request->has('action')) {

            $idList = $request->get('chkRecordId');
            $action = $request->get('action');
            if ($action == "Verify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 1));
                Session::flash('success_message', "Records are verified successfully.");
            } else if ($action == "Unverify") {
                Transaction::whereIn('id', $idList)->update(array('is_verify' => 0));
                Session::flash('success_message', "Records are unverified successfully.");
            } else if ($action == "Delete") {
                Transaction::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        } */

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%')
                    ->orWhere('refrence_id', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('role')) {   // only applies if role is not empty
            $role = $request->get('role');
            $query = $query->whereHas('User', function ($q) use ($role) {
                $q->where('user_type', $role);
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


            return view('elements.admin.transactions.earning', ['allrecords' => $users, 'transactionTotal' => $transactionTotal, 'total' => $total]);
        }
        return view('admin.transactions.earning', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'transactionTotal' => $transactionTotal, 'total' => $total]);
    }

    public function earningFilters(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'earning');
        $input = Input::all();
        $user_role = isset($input['elementId']) ? $input['elementId'] : 'User';
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actearnings';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Balance Management';
        $activetab = 'actearnings';
        if ($user_role == 'User') {
            $query = Transaction::with('User')
                ->whereHas('User', function ($q) {
                    $q->where('user_type', 'User');
                });

            $transactions = $query->get();

        } elseif ($user_role == 'Merchant') {
            $query = Transaction::with('User')
                ->whereHas('User', function ($q) {
                    $q->where('user_type', 'Merchant');
                });

            $transactions = $query->orderBy('id', 'DESC')->paginate(20);
        } elseif ($user_role == 'Agent') {
            $query = Transaction::with('User')
                ->whereHas('User', function ($q) {
                    $q->where('user_type', 'Agent');
                });

            $transactions = $query->orderBy('id', 'DESC')->paginate(20);

        } else {
            $query = Transaction::with('User');
            // $transactions = $query->get();
            $transactions = $query->orderBy('id', 'DESC')->paginate(20);
        }

        $totalQuery = clone $query;
        $total['total'] = $totalQuery->sum('total_amount');

        $totalQuery2 = clone $query;
        $total['total_fee'] = $totalQuery2->sum('transaction_amount');

        return response()->json([
            'transactions' => $transactions,
            'total' => $total
        ]);

    }

    public function adjustWallet($slug = null)
    {
        $pageTitle = 'Adjust User Wallet';
        $activetab = 'actearnings';

        $userDetails = User::where('user_type', '!=', '')->get();
        if ($userDetails) {
            foreach ($userDetails as $userDetail) {
                $users[$userDetail->user_type][$userDetail->id] = $userDetail->name . ' (' . $userDetail->phone . ')';
            }
        }
        //        echo '<pre>';print_r($users);exit;
        $input = Input::all();
        if (!empty($input)) {
            //echo '<pre>';print_r($input);exit;
            $rules = array(
                'user_id' => 'required',
                'service_name' => 'required',
                'amount' => 'required',
                'reason' => 'required',
            );
            $customMessages = [
                'user_id.required' => 'The user field is required field.',
                'service_name.required' => 'The wallet action is required field.',
            ];
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return Redirect::to('admin/transactions/adjustWallet')->withErrors($validator)->withInput();
            } else {
                $userId = $input['user_id'];
                $matchThese = ["users.id" => $input['user_id']];
                $user = DB::table('users')->select('users.*')->where($matchThese)->first();

                if ($input['service_name'] == "Debit" and $input['amount'] != "") {

                    if ($input['amount'] > $user->wallet_balance) {
                        Session::flash('error_message', "Insufficient balance available in the wallet.");
                        return Redirect::to('admin/transactions/adjustWallet')->withErrors($validator)->withInput();
                    }

                    $user_wallet_balance = $user->wallet_balance - $input['amount'];
                    User::where('id', $userId)->update(['wallet_balance' => $user_wallet_balance]);
                    $transType = 2;
                    $type = 'Debited by admin';
                    $refrence_id = time() . rand() . Session::get('adminid');
                    $trans = new Transaction([
                        'user_id' => $userId,
                        'receiver_id' => 0,
                        'add_by' => Session::get('adminid'),
                        'amount' => $input['amount'],
                        'amount_value' => $input['amount'],
                        'total_amount' => $input['amount'],
                        'real_value' => $input['amount'],
                        'trans_type' => $transType,
                        'trans_to' => 'Wallet',
                        'trans_for' => $type,
                        'payment_mode' => $type,
                        'refrence_id' => $refrence_id,
                        'billing_description' => $input['reason'],
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    //                    echo '<pre>';print_r($trans);exit;
                    $trans->save();
                    $TransId = $trans->id;

                    $title = 'Amount Debited';
                    $message = 'Your eWallet has been debited with the amount ' . CURR . ' ' . $input['amount'] . '. Reason: ' . $input['reason'];
                    $device_type = $user->device_type;
                    $device_token = $user->device_token;

                    $this->sendPushNotification($title, $message, $device_type, $device_token);
                    $notif = new Notification([
                        'user_id' => $userId,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                } else if ($input['service_name'] == "Credit" and $input['amount'] != "") {
                    $user_wallet_balance = $user->wallet_balance + $input['amount'];
                    User::where('id', $userId)->update(['wallet_balance' => $user_wallet_balance]);
                    $transType = 1;
                    $type = 'Credited by admin';
                    $refrence_id = time() . rand() . Session::get('adminid');
                    $trans = new Transaction([
                        'user_id' => $userId,
                        'receiver_id' => 0,
                        'add_by' => Session::get('adminid'),
                        'amount_value' => $input['amount'],
                        'amount' => $input['amount'],
                        'total_amount' => $input['amount'],
                        'real_value' => $input['amount'],
                        'trans_type' => $transType,
                        'trans_to' => 'Wallet',
                        'trans_for' => $type,
                        'payment_mode' => $type,
                        'refrence_id' => $refrence_id,
                        'billing_description' => $input['reason'],
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    //                    echo '<pre>';print_r($trans);exit;
                    $trans->save();
                    $TransId = $trans->id;

                    $title = 'Amount Credited';
                    $message = 'Your eWallet has been credited with the amount ' . CURR . ' ' . $input['amount'] . '. Reason: ' . $input['reason'];
                    //$message = 'Your eWallet has been credited with the amount USD '.$input['amount'].'. Please contact administrator for further queries.';
                    $device_type = $user->device_type;
                    $device_token = $user->device_token;

                    $this->sendPushNotification($title, $message, $device_type, $device_token);
                    $notif = new Notification([
                        'user_id' => $userId,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                }

                Session::flash('success_message', "User Balance Updated Successfully.");
                return Redirect::to('admin/transactions/earning');
            }
        }


        return view('admin.transactions.adjustWallet', ['title' => $pageTitle, $activetab => 1, 'users' => $users]);
    }

    public function getBalance(Request $request)
    {
        $user_id = $request->get('id');

        $userInfo = User::where('id', $user_id)->first();
        echo $userInfo->wallet_balance;
        exit;
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


    public function gimacTransation(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'gemic-transation');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actgemic';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'Manage Transactions Gemic';
        $activetab = 'actgemic';
        // DB::enableQueryLog();




        /* $query = Transaction::where('receiver_mobile', '!=', null)
                           ->where('receiver_id', '=', 0)
                           ->sortable(); */

        $query = Transaction::where('transactionType', 'SWAPTOGIMAC')->sortable();

        // Search functionality
        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query->whereHas('ExcelTransaction', function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%' . $keyword . '%');
            });
        }


        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $receiver = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($receiver) {

                $q->orWhereHas('ExcelTransaction', function ($q) use ($receiver) {
                    $q = $q->where('tel_number', 'like', '%' . $receiver . '%');
                });
            });
        }


        if ($request->has('receiver') && $request->get('receiver')) {
            $receiver = $request->get('receiver');
            $query = $query->where(function ($q) use ($receiver) {

                $q = $q->where('issuertrxref', 'like', '%' . $receiver . '%');

            });
        }


        if ($request->has('type')) {
            $type = $request->get('type');
            $query = $query->where(function ($q) use ($type) {
                $q = $q->where('is_verified_by_gimac', 'like', '%' . $type . '%');

            });

        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }

        if ($request->has('to1') && $request->get('to1')) {
            $dateQ1 = explode("/", $request->get('to1'));
            $from1 = $dateQ1[0] . " 00:00:00";
            $too1 = $dateQ1[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from1, $too1) {
                $q->whereBetween('transactions.updated_at', array($from1, $too1));
            });
        }

        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('SUM(amount) as total_amount, SUM(transaction_amount) as total_fee')->first();

        $users = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->ajax()) {
            return view('elements.admin.transactions.gimac', ['allrecords' => $users, 'totalAmount' => $totals]);
        }

        return view('admin.transactions.gimactransaction', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'totalAmount' => $totals]);
    }



    public function remitecTransactions(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'remitec-transactions');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'act_remitec_transactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Remitec Transactions';
        $activetab = 'act_remitec_transactions';

        //  DB::enableQueryLog();
        $query = new RemittanceData();
        $query = $query->sortable();

        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query = $query->where(function ($q) use ($keyword) {

                $q->whereRaw("CONCAT(firstName, ' ', lastName) like ?", ['%' . $keyword . '%']);

            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $keyword = $request->get('receiver');
            $query = $query->where(function ($q) use ($keyword) {

                $q->whereRaw("CONCAT(receiverFirstName, ' ', receiverLastName) like ?", ['%' . $keyword . '%']);

            });
        }

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('senderPhoneNumber', 'like', '%' . $keyword . '%');

            });
        }



        if ($request->has('type')) {
            $type = $request->get('type');
            $query = $query->where(function ($q) use ($type) {
                $q = $q->where('type', 'like', '%' . $type . '%');

            });

        }
        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('transactionId', 'like', '%' . $refrence . '%');
            });
        }


        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('created_at', array($from, $too));
            });
        }

        // if ($request->has('to1') && $request->get('to1')) {
        //     $dateQ1 = explode("/", $request->get('to1'));
        //     $from1 = $dateQ1[0]." 00:00:00";
        //  $too1 = $dateQ1[1]." 23:59:59";

        //          $query = $query->where(function($q) use ($from1,$too1){
        //         $q->whereBetween('updated_at', array($from1, $too1));
        //     });
        // }

        $users = $query->orderBy('id', 'DESC')->paginate(20);


        if ($request->ajax()) {
            //  dd(DB::getQueryLog());
            return view('elements.admin.transactions.remitec_index', ['allrecords' => $users]);
        }
        return view('admin.transactions.remitec_index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users]);
    }

    public function bdaTransactions(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'bda-transactions');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'act_bda_transactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage BDA Transactions';
        $activetab = 'act_bda_transactions';

        //  DB::enableQueryLog();
        $query = new Transaction();
        $query = $query->sortable();


        $query->join('remittance_data', 'transactions.onafriq_bda_ids', '=', 'remittance_data.id')
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactionType', 'SWAPTOBDA')
            ->select('transactions.*', 'remittance_data.transactionId', 'users.name', 'users.phone', 'remittance_data.iban', 'remittance_data.titleAccount as beneficiary', 'remittance_data.reason');

        if ($request->has('beneficiary') && $request->get('beneficiary')) {
            $keyword = $request->get('beneficiary');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('remittance_data.titleAccount', 'like', "%$keyword%");

            });
        }


        if ($request->has('iban') && $request->get('iban')) {
            $keyword = $request->get('iban');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('remittance_data.iban', 'like', "%$keyword%");

            });
        }
        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('remittance_data.transactionId', 'like', "%$refrence%");
            });
        }


        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }


        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('SUM(transactions.amount) as total_amount, SUM(transactions.transaction_amount) as total_fee')->first();

        $users = $query->orderBy('id', 'DESC')->paginate(20);


        if ($request->ajax()) {
            //  dd(DB::getQueryLog());
            return view('elements.admin.transactions.bda_index', ['allrecords' => $users, 'totalAmount' => $totals]);
        }
        return view('admin.transactions.bda_index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'totalAmount' => $totals]);
    }


    public function onafriqTransactions(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'onafriq-transactions');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'act_onafriq_transactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Onafriq Transactions';
        $activetab = 'act_onafriq_transactions';

        //  DB::enableQueryLog();
        $query = new Transaction();
        $query = $query->sortable();

        $query->join('onafriqa_data', 'transactions.onafriq_bda_ids', '=', 'onafriqa_data.id')
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactionType', 'SWAPTOONAFRIQ')
            ->select('transactions.*', 'onafriqa_data.transactionId', 'users.name', 'users.phone', 'onafriqa_data.recipientMsisdn', 'onafriqa_data.recipientName', 'onafriqa_data.recipientSurname', 'onafriqa_data.senderName', 'onafriqa_data.senderSurname', 'onafriqa_data.senderMsisdn', 'onafriqa_data.recipientCountry');


        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');

            $query = $query->where(function ($q) use ($keyword) {
                $q->whereRaw("CONCAT(onafriqa_data.senderName, ' ', onafriqa_data.senderSurname) LIKE ?", ['%' . $keyword . '%']);
            });
        }


        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('onafriqa_data.senderMsisdn', 'like', '%' . $keyword . '%');

            });
        }


        if ($request->has('receiver') && $request->get('receiver')) {
            $keyword = $request->get('receiver');

            $query = $query->where(function ($q) use ($keyword) {
                $q->whereRaw("CONCAT(onafriqa_data.recipientName, ' ', onafriqa_data.recipientSurname) LIKE ?", ['%' . $keyword . '%']);
            });

        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $keyword = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('onafriqa_data.recipientMsisdn', 'like', '%' . $keyword . '%');

            });
        }



        /* if ( $request->has('type')) {
            $type= $request->get('type');
            $query = $query->where(function($q) use ($type) {
                $q = $q->where('type', 'like', '%' . $type . '%');

            }); 
        } */

        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('onafriqa_data.transactionId', 'like', '%' . $refrence . '%');
            });
        }


        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }

        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('SUM(transactions.amount) as total_amount, SUM(transactions.transaction_amount) as total_fee')->first();

        $users = $query->orderBy('id', 'DESC')->paginate(20);


        if ($request->ajax()) {
            //  dd(DB::getQueryLog());
            return view('elements.admin.transactions.onafriq_index', ['allrecords' => $users, 'totalAmount' => $totals]);
        }
        return view('admin.transactions.onafriq_index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'totalAmount' => $totals]);
    }


    public function swapToswapTransation(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'swaptoswap-transation');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actSwap';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }


        $pageTitle = 'Manage Swap To Swap Transaction';
        $activetab = 'actSwap';
        // DB::enableQueryLog();




        // $query = Transaction::all();
        $query = Transaction::where('receiver_id', '!=', 0)->where('payment_mode', '!=', 'Referral')->where('payment_mode', '!=', 'airtelwallet')->sortable();




        // // Search functionality
        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $receiver = $request->get('receiver');
            $query = $query->where(function ($q) use ($receiver) {
                $q
                    ->orWhereHas('Receiver', function ($q) use ($receiver) {
                        $q = $q->where('name', 'like', '%' . $receiver . '%');
                    });
            });
        }

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('phone', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $receiver = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($receiver) {
                $q
                    ->orWhereHas('Receiver', function ($q) use ($receiver) {
                        $q = $q->where('phone', 'like', '%' . $receiver . '%');
                    });
            });
        }

        if ($request->has('type') && $request->get('type')) {
            $type = $request->get('type');
            $query = $query->where(function ($q) use ($type) {
                if ($type == 'Debit') {
                    $q->orWhere('trans_type', 2);
                } elseif ($type == 'Credit') {
                    $q->orWhere('trans_type', 1);
                }
            });
        }

        if ($request->has('for') && $request->get('for')) {
            $for = $request->get('for');
            $query = $query->where(function ($q) use ($for) {
                $q->orWhere('payment_mode', 'like', '%' . $for . '%');
            });
        }

        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('refrence_id', 'like', '%' . $refrence . '%');
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


        // echo"<pre>";print_r($query);
        $users = $query->orderBy('id', 'DESC')->paginate(20);

        if ($request->ajax()) {
            // dd(DB::getQueryLog());

            return view('elements.admin.transactions.swaptoswap', ['allrecords' => $users]);

        }

        return view('admin.transactions.swaptoswap', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users]);
    }


    public function getSummary(Request $request)
    {
        $input = Input::all();

        $user_role = $input['elementId'];

        $fundTransfer = 0;
        $withdrawAmount = 0;
        $sendMoney = 0;
        if ($user_role == 'User') {
            $query = new Transaction();
            $query = $query->WhereHas('User', function ($q) use ($user_role) {
                $q = $q->where('user_type', $user_role);
            });
            $query1 = clone $query;
            $query3 = clone $query;
            $fundTransfer = intval($query1->where('trans_type', 1)->where('payment_mode', 'wallet2wallet')->where('status', 1)->orWhere('trans_type', 2)->where('payment_mode', 'wallet2wallet')->where('status', 1)->sum('transaction_amount'));

            $sendMoney = intval($query3->where('trans_type', 4)->where('payment_mode', 'send_money')->where('status', 1)->sum('transaction_amount'));
        } elseif ($user_role == 'Merchant') {
            $query = new Transaction();
            $query = $query->WhereHas('User', function ($q) use ($user_role) {
                $q = $q->where('user_type', $user_role);
            });
            $query1 = clone $query;
            $query3 = clone $query;
            $fundTransfer = intval($query1->where('trans_type', 1)->where('payment_mode', 'wallet2wallet')->where('status', 1)->sum('transaction_amount'));
            $sendMoney = intval($query3->where('trans_type', 4)->where('payment_mode', 'send_money')->where('status', 1)->sum('transaction_amount'));
        } else {

            $query = new Transaction();
            $query1 = clone $query;
            $query3 = clone $query;
            $fundTransfer = intval($query1->WhereHas('User', function ($q) use ($user_role) {
                $q = $q->where('user_type', $user_role);
            })->where('trans_type', 1)->where('payment_mode', 'Agent Deposit')->where('status', 1)->sum('transaction_amount'));

            //DB::enableQueryLog();
            $sendMoney = intval($query3->WhereHas('Receiver', function ($q) use ($user_role) {
                $q = $q->where('user_type', $user_role);
            })->where('trans_type', 2)->where('payment_mode', 'Withdraw')->where('status', 1)->sum('transaction_amount'));
            //dd(DB::getQueryLog());
        }



        $total_earning = intval($fundTransfer + $withdrawAmount + $sendMoney);

        return ['fundTransfer' => CURR . ' ' . $fundTransfer, 'withdrawAmount' => CURR . ' ' . $withdrawAmount, 'sendMoney' => CURR . ' ' . $sendMoney, 'totalEarning' => CURR . ' ' . $total_earning];
    }


    public function refund($id, Request $request)
    {
        $input = $request->all();
        //    echo"<pre>";print_r($input);die;
        $transaction = Transaction::where('id', $id)->first();
        $trans = new Transaction([
            'user_id' => $transaction->receiver_id,
            'receiver_id' => $transaction->user_id,
            'amount' => $input['refund'],
            'amount_value' => $input['refund'],
            'transaction_amount' => 0,
            'total_amount' => $input['refund'],
            'trans_type' => 1,
            'trans_for' => 'Admin',
            'payment_mode' => 'Refund',
            'refrence_id' => $transaction->id,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $trans->save();

        $senderModel = $transaction->receiver_id == 1 && $transaction->trans_for == 'Admin' ? "App\Models\Admin" : "App\User";
        $senderUser = $senderModel::where('id', $transaction->receiver_id)->first();
        $sender_wallet_amount = $senderUser->wallet_balance - $input['refund'];
        $senderModel::where('id', $transaction->receiver_id)->update(['wallet_balance' => $sender_wallet_amount]);

        $receiverModel = $transaction->user_id == 1 && $transaction->trans_for == 'Admin' ? "App\Models\Admin" : "App\User";
        $recieverUser = $userInfo = $receiverModel::where('id', $transaction->user_id)->first();
        $receiver_wallet_amount = $recieverUser->wallet_balance + $input['refund'];
        $receiverModel::where('id', $transaction->user_id)->update(['wallet_balance' => $receiver_wallet_amount]);


        Session::flash('success_message', "Refund has been successful.");
        return Redirect::to('admin/transactions');
    }

    public static function checkRefund($id)
    {
        $count = Transaction::where('refrence_id', $id)->count();
        return $count;

    }

    public function ExportExcel(Request $request)
    {
        /* $file_name = 'report_' . date('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new ReportExport, $file_name); */


        $sender = $request->input('sender');
        $sender_phone = $request->input('sender_phone');
        $receiver = $request->input('receiver');
        $receiver_phone = $request->input('receiver_phone');
        $for = $request->input('for');
        $refrence = $request->input('refrence');
        $to = $request->input('to');
        $to1 = $request->input('to1');



        // Build the query with filters
        //\DB::enableQueryLog();



        $query = Transaction::query()
            ->leftJoin('users as sender_user', 'transactions.user_id', '=', 'sender_user.id') // Join sender details
            ->leftJoin('users as receiver_user', 'transactions.receiver_id', '=', 'receiver_user.id') // Join receiver details
            ->leftJoin('onafriqa_data as onafriq', 'transactions.excel_trans_id', '=', 'onafriq.excelTransId') // Join receiver details
            ->leftJoin('remittance_data as remittance', 'transactions.excel_trans_id', '=', 'remittance.excel_id') // Join receiver details
            // ->where('transactions.status', 1)
            ->where(function ($q) use ($to) {
                if ($to) {
                    $dates = explode('/', $to);
                    $from1 = "$dates[0] 00:00:00";
                    $too1 = "$dates[1] 23:59:59";
                    $q->whereBetween('transactions.created_at', [$from1, $too1]);
                }
            })
            ->where(function ($q) use ($to1) {
                if ($to1) {
                    $dates = explode('/', $to1);
                    $from1 = "$dates[0] 00:00:00";
                    $too1 = "$dates[1] 23:59:59";
                    $q->whereBetween('transactions.updated_at', [$from1, $too1]);
                }
            })
            ->when($sender, function ($q) use ($sender) {
                $q->where('sender_user.name', 'like', "%$sender%")
                    ->orWhere('onafriq.senderName', 'like', "%$sender%");
            })
            ->when($sender_phone, function ($q) use ($sender_phone) {
                $q->where('sender_user.phone', 'like', "%$sender_phone%")
                    ->orWhere('onafriq.senderMsisdn', 'like', "%$sender_phone%");
            })
            ->when($receiver, function ($q) use ($receiver) {
                $q->where('receiver_user.name', 'like', "%$receiver%")
                    ->orWhere('onafriq.recipientName', 'like', "%$receiver%");
            })
            ->when($receiver_phone, function ($q) use ($receiver_phone) {
                $q->where('receiver_user.phone', 'like', "%$receiver_phone%")
                    ->orWhere('onafriq.recipientMsisdn', 'like', "%$receiver_phone%");
            })
            ->when($for, function ($q) use ($for) {
                $q->where('transactions.payment_mode', 'like', "%$for%");
            })
            ->when($refrence, function ($q) use ($refrence) {
                $q->where('transactions.refrence_id', $refrence)
                    ->orWhere('onafriq.transactionId', $refrence)
                    ->orWhere('remittance.transactionId', $refrence);
            })



            /* ->select(
                'transactions.*',
                'sender_user.name as sender_name',
                'sender_user.phone as sender_phone',
                'receiver_user.name as receiver_name',
                'receiver_user.phone as receiver_phone',
                // 'remittance.transactionId as trans',
            ); */



            ->select(
                'transactions.*',
                'onafriq.transactionId',
                'onafriq.recipientMsisdn',
                'onafriq.recipientName',
                'onafriq.recipientSurname',
                'onafriq.senderMsisdn',
                'onafriq.senderName',
                'onafriq.senderSurname',
                'remittance.iban',
                'remittance.reason',
                'remittance.titleAccount',
                'remittance.transactionId as remitanceTransactionId'
            );

        // Get the filtered records
        $records = $query->groupBy('transactions.id')
            ->orderBy('transactions.created_at', 'DESC')
            ->get();

        //dd(\DB::getQueryLog());
        // Process records for export
        // Generate and return the Excel file
        //dd($records);
        return Excel::download(new ReportExport($records), 'TransactionReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }

    public function TransactionsExportGimac(Request $request)
    {
        // dd($request->all());
        $sender = $request->input('sender');
        $receiver = $request->input('receiver');
        $receiver_phone = $request->input('receiver_phone');
        $type = $request->input('type');
        // \DB::enableQueryLog();
        $query = Transaction::where('transactionType', 'SWAPTOGIMAC')->sortable();


        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query->whereHas('ExcelTransaction', function ($q) use ($keyword) {
                $q->where('first_name', 'like', '%' . $keyword . '%');
            });
        }


        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $receiver = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($receiver) {

                $q->orWhereHas('ExcelTransaction', function ($q) use ($receiver) {
                    $q = $q->where('tel_number', 'like', '%' . $receiver . '%');
                });
            });
        }


        if ($request->has('receiver') && $request->get('receiver')) {
            $receiver = $request->get('receiver');
            $query = $query->where(function ($q) use ($receiver) {

                $q = $q->where('issuertrxref', 'like', '%' . $receiver . '%');

            });
        }


        if ($request->has('type')) {
            $type = $request->get('type');
            $query = $query->where(function ($q) use ($type) {
                $q = $q->where('is_verified_by_gimac', 'like', '%' . $type . '%');

            });

        }
        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }

        if ($request->has('to1') && $request->get('to1')) {
            $dateQ1 = explode("/", $request->get('to1'));
            $from1 = $dateQ1[0] . " 00:00:00";
            $too1 = $dateQ1[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from1, $too1) {
                $q->whereBetween('transactions.updated_at', array($from1, $too1));
            });
        }
        $records = $query->orderBy('transactions.created_at', 'DESC')->get();
        //dd(\DB::getQueryLog());

        return Excel::download(new GimacReportExport($records), 'GimacReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }


    public function TransactionsExportBda(Request $request)
    {
        $beneficiary = $request->input('beneficiary');
        $iban = $request->input('iban');
        $refrence = $request->input('refrence');
        $to = $request->input('to');

        //  DB::enableQueryLog();
        $query = Transaction::query()
            ->join('remittance_data', 'transactions.onafriq_bda_ids', '=', 'remittance_data.id')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactionType', 'SWAPTOBDA')
            ->where(function ($q) use ($to) {
                if ($to) {
                    $dates = explode('/', $to);
                    $from1 = "$dates[0] 00:00:00";
                    $too1 = "$dates[1] 23:59:59";
                    $q->whereBetween('transactions.created_at', [$from1, $too1]);
                }
            })

            ->when($beneficiary, function ($q) use ($beneficiary) {
                $q->where('titleAccount', 'like', "%$beneficiary%");
            })
            ->when($iban, function ($q) use ($iban) {
                $q->where('iban', 'like', "%$iban%");
            })
            ->when($refrence, function ($q) use ($refrence) {
                $q->where('transactionId', 'like', "%$refrence%");
            })
            ->select(
                'transactions.*',
                'remittance_data.transactionId',
                'users.name',
                'users.phone',
                'remittance_data.iban',
                'remittance_data.titleAccount',
                'remittance_data.reason'
            );

        $records = $query->orderBy('transactions.created_at', 'DESC')->get();

        return Excel::download(new BdaReportExport($records), 'BdaReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }

    public function TransactionsExportOnafriq(Request $request)
    {

        $sender = $request->input('sender');
        $sender_phone = $request->input('sender_phone');
        $receiver = $request->input('receiver');
        $receiver_phone = $request->input('receiver_phone');
        $refrence = $request->input('refrence');
        $to = $request->input('to');

        // \DB::enableQueryLog();
        $query = Transaction::query()
            ->join('onafriqa_data', 'transactions.onafriq_bda_ids', '=', 'onafriqa_data.id')
            ->where('transactionType', 'SWAPTOONAFRIQ')
            ->where(function ($q) use ($to) {
                if ($to) {
                    $dates = explode('/', $to);
                    $from1 = "$dates[0] 00:00:00";
                    $too1 = "$dates[1] 23:59:59";
                    $q->whereBetween('transactions.created_at', [$from1, $too1]);
                }
            })
            ->when($sender, function ($q) use ($sender) {
                $q->where('onafriqa_data.name', 'like', "%$sender%");
            })
            ->when($sender_phone, function ($q) use ($sender_phone) {
                $q->where('onafriqa_data.senderMsisdn', 'like', "%$sender_phone%");
            })
            ->when($receiver, function ($q) use ($receiver) {
                $q->where('onafriqa_data.name', 'like', "%$receiver%");
            })
            ->when($receiver_phone, function ($q) use ($receiver_phone) {
                $q->where('onafriqa_data.recipientMsisdn', 'like', "%$receiver_phone%");
            })
            ->when($refrence, function ($q) use ($refrence) {
                $q->where('transactionId', 'like', "%$refrence%");
            })

            ->select(
                'transactions.*',
                'onafriqa_data.transactionId',
                'onafriqa_data.recipientMsisdn',
                'onafriqa_data.recipientName',
                'onafriqa_data.recipientSurname',
                'onafriqa_data.senderName',
                'onafriqa_data.senderSurname',
                'onafriqa_data.senderMsisdn',
                'onafriqa_data.recipientCountry'
            );

        $records = $query->orderBy('transactions.created_at', 'DESC')->get();
        //dd(\DB::getQueryLog());
        return Excel::download(new OnafriqReportExport($records), 'OnafriqReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }

    public function ExportExcelIndividual(Request $request)
    {
        $slug = $request->input('slug');
        //  DB::enableQueryLog();
        $query = new Transaction();
        $query = $query->sortable();
        $userInfo = User::where('slug', $slug)->first();

        $user_id = $userInfo->id;
        $query = $query->where(function ($q) use ($user_id) {
            $q->where('user_id', $user_id)
                ->orwhere('receiver_id', $user_id);
        });
        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('Aggregator', function ($q) use ($keyword) {
                        $q = $q->where('username', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $receiver = $request->get('receiver');
            $query = $query->where(function ($q) use ($receiver) {
                $q
                    ->orWhereHas('Receiver', function ($q) use ($receiver) {
                        $q = $q->where('name', 'like', '%' . $receiver . '%');
                    })
                    ->orWhereHas('Aggregator', function ($q) use ($receiver) {
                        $q = $q->where('username', 'like', '%' . $receiver . '%');
                    });
            });
        }

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {
                $q
                    ->orWhereHas('User', function ($q) use ($keyword) {
                        $q = $q->where('phone', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $receiver = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($receiver) {
                $q
                    ->orWhereHas('Receiver', function ($q) use ($receiver) {
                        $q = $q->where('phone', 'like', '%' . $receiver . '%');
                    });
            });
        }
        if ($request->filled('accountId')) {
            $accountId = $request->get('accountId');

            $query->where('transactions.accountId', $accountId);
        }


        if ($request->has('type') && $request->get('type')) {
            $type = $request->get('type');
            $query = $query->where(function ($q) use ($type) {
                if ($type == 'Debit') {
                    $q->orWhere('trans_type', 2);
                } elseif ($type == 'Credit') {
                    $q->orWhere('trans_type', 1);
                }
            });
        }

        if ($request->has('fors') && $request->get('fors')) {
            $for = $request->get('fors');
            $query = $query->where(function ($q) use ($for) {
                $q->orWhere('payment_mode', 'like', '%' . $for . '%');
            });
        }

        if ($request->has('refrence') && $request->get('refrence')) {
            $refrence = $request->get('refrence');
            $query = $query->where(function ($q) use ($refrence) {
                $q->orWhere('refrence_id', 'like', '%' . $refrence . '%');
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
        $query = $query->orderBy('id', 'DESC');
        $records = $query->get();
        return Excel::download(new IndividualReportExport($records), 'UserTransactionReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');

        /* $userInfo = User::where('slug', $slug)->first();
        $userId = $userInfo->id;
        $file_name = 'report_' . $userInfo->name . '_' . date('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new IndividualReportExport($userId), $file_name); */
    }

    public function referralListing(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'referral-listing');
        if ($isPermitted == false) {
            $pageTitle = 'Referral Listing';
            $activetab = 'actreferral';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Referral';
        $activetab = 'actreferral';

        // Base query with joins
        // DB::enableQueryLog();
        $query = DB::table('transactions as t')
            ->leftJoin('users as receiver', 'receiver.id', '=', 't.receiver_id')
            ->leftJoin('transactions as ref_trans', 'ref_trans.id', '=', 't.referralBy')
            ->leftJoin('users as sender', 'sender.id', '=', 'ref_trans.user_id')
            ->select(
                't.id as transaction_id',
                'sender.name as sender_name',
                'sender.phone as sender_phone',
                'receiver.name as receiver_name',
                'receiver.phone as receiver_phone',
                't.amount',
                't.payment_mode',
                't.total_amount',
                't.status',
                't.created_at',
                't.updated_at',
            );

        // Filters
        if ($request->has('sender') && $request->get('sender')) {
            $keyword = $request->get('sender');
            $query->where(function ($q) use ($keyword) {
                $q->where('sender.name', 'like', "%$keyword%");
            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $receiver = $request->get('receiver');
            $query->where('receiver.name', 'like', "%$receiver%");
        }

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query->where('sender.phone', 'like', "%$keyword%");
        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $receiver = $request->get('receiver_phone');
            $query->where('receiver.phone', 'like', "%$receiver%");
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";
            $query->whereBetween('t.created_at', [$from, $too]);
        }

        if ($request->has('to1') && $request->get('to1')) {
            $dateQ1 = explode("/", $request->get('to1'));
            $from1 = $dateQ1[0] . " 00:00:00";
            $too1 = $dateQ1[1] . " 23:59:59";
            $query->whereBetween('t.updated_at', [$from1, $too1]);
        }

        // Only Referral Payment Mode
        $query->where('t.payment_mode', 'Referral');
        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('SUM(t.amount) as totalAmount')->first();
        // Final paginated result
        $transList = $query->orderBy('t.id', 'DESC')->paginate(20);
        // dd(DB::getQueryLog());
        // Return View
        if ($request->ajax()) {
            $allDataIsBlank = collect($_POST)->except('_token')->filter(function ($item) {
                return !empty($item);
            })->isEmpty();

            return view('elements.admin.transactions.referral-listing', ['allrecords' => $transList, 'total' => $totals]);
        }

        return view('admin.transactions.referral-listing', [
            'title' => $pageTitle,
            $activetab => 1,
            'allrecords' => $transList,
            'total' => $totals
        ]);

    }

    public function TransactionsExportReferral(Request $request)
    {
        $sender = $request->input('sender');
        $sender_phone = $request->input('sender_phone');
        $receiver = $request->input('receiver');
        $receiver_phone = $request->input('receiver_phone');
        $refrence = $request->input('refrence');
        $to = $request->input('to');
        // DB::enableQueryLog();
        $query = DB::table('transactions as t')
            ->leftJoin('users as receiver', 'receiver.id', '=', 't.receiver_id')
            ->leftJoin('transactions as ref_trans', 'ref_trans.id', '=', 't.referralBy')
            ->leftJoin('users as sender', 'sender.id', '=', 'ref_trans.user_id')
            ->where('t.payment_mode', 'Referral')
            ->select(
                't.id as transaction_id',
                'sender.name as sender_name',
                'sender.phone as sender_phone',
                'receiver.name as receiver_name',
                'receiver.phone as receiver_phone',
                't.amount',
                't.created_at',
                't.payment_mode',
                't.total_amount',
                't.status',
            )

            ->when($sender, function ($q) use ($sender) {
                $q->where('sender.name', 'like', "%$sender%");
            })
            ->when($sender_phone, function ($q) use ($sender_phone) {
                $q->where('sender.phone', 'like', "%$sender_phone%");
            })
            ->when($receiver, function ($q) use ($receiver) {
                $q->where('receiver.name', 'like', "%$receiver%");
            })
            ->when($receiver_phone, function ($q) use ($receiver_phone) {
                $q->where('receiver.phone', 'like', "%$receiver_phone%");
            })
            ->when($to, function ($q) use ($to) {
                $dateQ = explode("/", $to);
                $from1 = $dateQ[0] . " 00:00:00";
                $too1 = $dateQ[1] . " 23:59:59"; 
                $q->whereBetween('t.created_at', [$from1, $too1]);
            });

        $records = $query->orderBy('t.id', 'DESC')->get();
        // dd(DB::getQueryLog()); 

        return Excel::download(new ReferralReportExport($records), 'ReferralReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }

    public function AirtelMoneyTransactions(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'airtel-transactions');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'act_airtel_transactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage AIRTEL Transactions';
        $activetab = 'act_airtel_transactions';

        //  DB::enableQueryLog();
        $query = new Transaction();
        $query = $query->sortable();


        $query->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactionType', 'AIRTELMONEY')
            ->select('transactions.*', 'users.name', 'users.phone');

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('receiver_mobile', 'like', "%$keyword%");

            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $keyword = $request->get('receiver');
            $query = $query->where(function ($q) use ($keyword) {
                $q = $q->where('users.name', 'like', "%$keyword%");
            });
        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $keyword = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($keyword) {
                $q = $q->where('users.phone', 'like', "%$keyword%");
            });
        }

        if ($request->has('refrence') && $request->get('refrence')) {
            $keyword = $request->get('refrence');
            $query = $query->where(function ($q) use ($keyword) {
                $q = $q->where('refrence_id', 'like', "%$keyword%");
            });
        }


        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }


        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('SUM(transactions.amount) as total_amount, SUM(transactions.transaction_amount) as total_fee')->first();

        $users = $query->orderBy('id', 'DESC')->paginate(20);


        if ($request->ajax()) {
            return view('elements.admin.transactions.airtel_index', ['allrecords' => $users, 'totalAmount' => $totals]);
        }
        return view('admin.transactions.airtel_index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'totalAmount' => $totals]);
    }


    public function TransactionsExportAIRTEL(Request $request)
    {
        $sender_phone = $request->input('sender_phone');
        $receiver = $request->input('receiver');
        $receiver_phone = $request->input('receiver_phone');
        $refrence = $request->input('refrence');
        $to = $request->input('to');

        //  DB::enableQueryLog();
        $query = Transaction::query()
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where('transactionType', 'AIRTELMONEY')
            ->where(function ($q) use ($to) {
                if ($to) {
                    $dates = explode('/', $to);
                    $from1 = "$dates[0] 00:00:00";
                    $too1 = "$dates[1] 23:59:59";
                    $q->whereBetween('transactions.created_at', [$from1, $too1]);
                }
            })

            ->when($sender_phone, function ($q) use ($sender_phone) {
                $q->where('receiver_mobile', 'like', "%$sender_phone%");
            })
            ->when($receiver, function ($q) use ($receiver) {
                $q->where('users.name', 'like', "%$receiver%");
            })
            ->when($receiver_phone, function ($q) use ($receiver_phone) {
                $q->where('users.phone', 'like', "%$receiver_phone%");
            })
            ->when($refrence, function ($q) use ($refrence) {
                $q->where('refrence_id', 'like', "%$refrence%");
            })
            ->select(
                'transactions.*',
                'users.name',
                'users.phone'
            );

        $records = $query->orderBy('transactions.created_at', 'DESC')->get();

        return Excel::download(new AirtelReportExport($records), 'AirtelReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }

    public function VisaCardPaymentTransactions(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'visa-transactions');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'act_visa_transactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage VISA Transactions';
        $activetab = 'act_visa_transactions';

        //  DB::enableQueryLog();
        $query = new Transaction();
        $query = $query->sortable();


        $query->join('users', 'transactions.user_id', '=', 'users.id')
            ->whereIn('payment_mode', ['CARDPAYMENT', 'TRANSAFEROUT'])
            ->select('transactions.*', 'users.name', 'users.phone');

        if ($request->has('name') && $request->get('name')) {
            $keyword = $request->get('name');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('users.name', 'like', "%$keyword%");

            });
        }

        if ($request->has('phone') && $request->get('phone')) {
            $keyword = $request->get('phone');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('users.phone', $keyword);

            });
        }
        if ($request->has('refrence') && $request->get('refrence')) {
            $keyword = $request->get('refrence');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('transactions.transactionId', $keyword);

            });
        }

        if ($request->has('accountId') && $request->get('accountId')) {
            $keyword = $request->get('accountId');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('transactions.accountId', $keyword);

            });
        }


        if ($request->has('Balance') && $request->get('Balance')) {
            $keyword = $request->get('Balance');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('transactions.runningBalance', 'like', "%$keyword%");

            });
        }


        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }


        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('SUM(transactions.amount) as total_amount, SUM(transactions.transaction_amount) as total_fee')->first();

        $users = $query->orderBy('id', 'DESC')->paginate(20);


        if ($request->ajax()) {
            return view('elements.admin.transactions.visa_index', ['allrecords' => $users, 'totalAmount' => $totals]);
        }
        return view('admin.transactions.visa_index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'totalAmount' => $totals]);
    }

    public function TransactionsExportVISA(Request $request)
    {
        $name = $request->input('name');
        $phone = $request->input('phone');
        $refrence = $request->input('refrence');
        $to = $request->input('to');
        $accountId = $request->input('accountId');
        $query = Transaction::query()
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->where(function ($q) {
                $q->where('payment_mode', 'CARDPAYMENT')
                    ->orWhere('payment_mode', 'TRANSAFEROUT');
            })
            ->where(function ($q) use ($to) {
                if ($to) {
                    $dates = explode('/', $to);
                    $from1 = "$dates[0] 00:00:00";
                    $too1 = "$dates[1] 23:59:59";
                    $q->whereBetween('transactions.created_at', [$from1, $too1]);
                }
            })

            ->when($name, function ($q) use ($name) {
                $q->where('users.name', 'like', "%$name%");
            })
            ->when($phone, function ($q) use ($phone) {
                $q->where('users.phone', 'like', "%$phone%");
            })
            ->when($refrence, function ($q) use ($refrence) {
                $q->where('refrence_id', 'like', "%$refrence%");
            })
            ->when($accountId, function ($q) use ($accountId) {
                $q->where('transactions.accountId', $accountId);
            })
            ->select(
                'transactions.*',
                'users.name',
                'users.phone'
            );

        $records = $query->orderBy('transactions.created_at', 'DESC')->get();
        return Excel::download(new VisaReportExport($records), 'VisaCardReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }

    public function ExternalTransactions(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'external-transactions');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'act_external_transactions';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage External Transactions';
        $activetab = 'act_external_transactions';

        //  DB::enableQueryLog();
        $query = new Transaction();
        $query = $query->sortable();


        $query->join('users', 'transactions.receiver_id', '=', 'users.id')
                ->leftJoin('excel_transactions', 'transactions.excel_trans_id', '=', 'excel_transactions.id')
            ->where('payment_mode', 'External')->where('receiver_id', 746)
            ->select('transactions.*','excel_transactions.first_name as transaction_receiver_name','excel_transactions.name as transaction_receiver_lastname', 'users.name', 'users.phone');

        if ($request->has('sender_phone') && $request->get('sender_phone')) {
            $keyword = $request->get('sender_phone');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('transactions.receiver_mobile', 'like', "%$keyword%");

            });
        }

        if ($request->has('receiver') && $request->get('receiver')) {
            $keyword = $request->get('receiver');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('users.name', 'like', "%$keyword%");

            });
        }

        if ($request->has('receiver_phone') && $request->get('receiver_phone')) {
            $keyword = $request->get('receiver_phone');
            $query = $query->where(function ($q) use ($keyword) {

                $q = $q->where('users.phone', 'like', "%$keyword%");

            });
        }


        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('transactions.created_at', array($from, $too));
            });
        }


        $totalsQuery = clone $query;
        $totals = $totalsQuery->selectRaw('SUM(transactions.amount) as total_amount, SUM(transactions.transaction_amount) as total_fee')->first();

        $users = $query->orderBy('id', 'DESC')->paginate(20);


        if ($request->ajax()) {
            return view('elements.admin.transactions.external_index', ['allrecords' => $users, 'totalAmount' => $totals]);
        }
        return view('admin.transactions.external_index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users, 'totalAmount' => $totals]);
    }

    public function TransactionsExportEXTERNAL(Request $request)
    {
        $sender_phone = $request->input('sender_phone');
        $receiver = $request->input('receiver');
        $receiver_phone = $request->input('receiver_phone');
        $refrence = $request->input('refrence');
        $to = $request->input('to');


        $query = Transaction::query()
            ->leftJoin('users', 'transactions.receiver_id', '=', 'users.id')
            ->where('transactions.receiver_id', 746)
            ->where('transactions.payment_mode', '=', 'External')
            ->where(function ($q) use ($to) {
                if ($to) {
                    $dates = explode('/', $to);
                    $from1 = "$dates[0] 00:00:00";
                    $too1 = "$dates[1] 23:59:59";
                    $q->whereBetween('transactions.created_at', [$from1, $too1]);
                }
            })

            ->when($sender_phone, function ($q) use ($sender_phone) {
                $q->where('receiver_mobile', 'like', "%$sender_phone%");
            })
            ->when($receiver, function ($q) use ($receiver) {
                $q->where('users.name', 'like', "%$receiver%");
            })
            ->when($receiver_phone, function ($q) use ($receiver_phone) {
                $q->where('users.phone', 'like', "%$receiver_phone%");
            })
            ->when($refrence, function ($q) use ($refrence) {
                $q->where('refrence_id', 'like', "%$refrence%");
            })
            ->select(
                'transactions.*',
                'users.name',
                'users.phone'
            );

        $records = $query->orderBy('transactions.created_at', 'DESC')->get();
        return Excel::download(new ExternalReportExport($records), 'ExternalReport' . '_' . date('Y_m_d_H_i_s') . '.xlsx');
    }

}

?>