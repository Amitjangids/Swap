<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
use App\Models\Order;
use App\Models\Banner;
use App\Models\Scratchcard;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Card;
use App\Models\Carddetail;
use App\Models\Agentoffer;
use App\Models\Offer;
use App\Models\Transactionfee;
use App\Models\Usertransactionfee;

class TransactionsController extends Controller {

    public function __construct() {
        $this->middleware('userlogedin', ['only' => ['paymentProcess']]);
        $this->middleware('is_userlogin', ['except' => ['paymentProcess']]);
    }

    public function transactionHistory(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Transaction History');

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        $query = new Transaction();
        if ($userInfo->user_type == 'Agent') {
            $query = $query->where('payment_mode', '!=', 'Refund');
        }

        $query = $query->where("user_id", $user_id)->orwhere("receiver_id", "=", $user_id);

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $limit = 30;

        $allrecords = $query->orderBy('id', 'DESC')->paginate($limit, ['*'], 'page', $page);

        if ($request->ajax()) {
            return view('elements.transactions.transactionHistory', ['allrecords' => $allrecords, 'userInfo' => $userInfo, 'page' => $page, 'isajax' => 1]);
        }
// echo '<pre>';print_r($allrecords);exit;
        return view('transactions.transactionHistory', ['title' => $pageTitle, 'userInfo' => $userInfo, 'allrecords' => $allrecords, 'page' => $page, 'limit' => $limit]);
    }

    public function rechargeList(Request $request) {
        $this->getSession();
        $slug = $request->segment(1);
        if ($slug == 'mobile-recharge') {
            $pageTitle = __('message.View Mobile Recharge Cards');
            $headTitle = __('message.Mobile Recharge');
            $name = __('message.Mobile Recharge');
            $card_type = 2;
        } elseif ($slug == 'online-card') {
            $pageTitle = __('message.View Online Cards');
            $headTitle = __('message.Online Cards');
            $name = __('message.Online Cards');
            $card_type = 3;
        } elseif ($slug == 'internet-recharge') {
            $pageTitle = __('message.View Internet Recharge Cards');
            $headTitle = __('message.Internet Recharge');
            $name = __('message.Internet Recharge');
            $card_type = 1;
        }

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        $data = array();
        $cards = Card::where('card_type', $card_type)->where('status', 1)->get();

        if (!empty($cards)) {
            foreach ($cards as $card) {
                $carddetails = Carddetail::where('card_id', $card->id)->where('status', 1)->where('used_status', 0)->get();
                if (count($carddetails) > 0) {
                    $cardData['card_id'] = $card->id;
                    $cardData['slug'] = $card->slug;
                    $cardData['card_image'] = COMPANY_FULL_DISPLAY_PATH . $card->company_image;
                    $data[] = $cardData;
                }
            }
            if (isset($data)) {
                $allrecords = $data;
            } else {
                $allrecords = array();
            }
        } else {
            $allrecords = array();
        }

        return view('transactions.rechargeList', ['title' => $pageTitle, 'allrecords' => $allrecords, 'headTitle' => $headTitle]);
    }

    public function rechargeCard($slug = null, Request $request) {
        $this->getSession();

        $pageTitle = __('message.View Cards List');
        $cardValue = Card::where('slug', $slug)->first();
        $card_id = $cardValue->id;

        $user_id = Session::get('user_id');
        $userDetail = User::where('id', $user_id)->first();

        if ($cardValue->card_type == 1) {
            $type = __('message.Internet Card');
            $headTitle = __('message.Internet Recharge');
        } else if ($cardValue->card_type == 2) {
            $type = __('message.Mobile Card');
            $headTitle = __('message.Mobile Recharge');
        } else if ($cardValue->card_type == 3) {
            $type = __('message.Online Card');
            $headTitle = __('message.Online Cards');
        }

        $agentOffer = Agentoffer::where('user_id', $user_id)->where('type', $type)->where('status', 1)->first();
        // $offer = Offer::where('type', $type)->where('status', 1)->first();

        $allrecords = array();
        $carddetails = Carddetail::where('card_id', $card_id)->where('status', 1)->where('used_status', 0)->groupBy('real_value')->get();

        if (!empty($carddetails)) {
            foreach ($carddetails as $card) { //echo '<pre>';print_r($cardValue);
                $cardData['card_id'] = $card->id;
                $cardData['card_name'] = $cardValue->company_name;
                $cardData['currency'] = $card->currency;
                // $cardData['currency'] = $cardValue->currency;
                // $cardData['real_value'] = number_format($card->real_value, 2);
                // $cardData['card_value'] = number_format($card->card_value, 2);
                // if ($userDetail->user_type == 'Agent') {
                //     if (!empty($agentOffer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $agentOffer->offer) / 100)), 2);
                //     } elseif (!empty($offer)) {
                //         $cardData['card_value'] = number_format(($card->card_value - (($card->card_value * $offer->offer) / 100)), 2);
                //     }
                // }

                $cardData['real_value'] = $this->numberFormatPrecision($card->real_value, 2, '.');
                $cardData['card_value'] = $this->numberFormatPrecision($card->card_value, 2, '.');

                if ($userDetail->user_type == 'Agent') {
                    if (!empty($agentOffer)) {
                        $cardData['card_value'] = $this->numberFormatPrecision(($card->agent_card_value - (($card->agent_card_value * $agentOffer->offer) / 100)), 2);
                    } else {
                        $cardData['card_value'] = $this->numberFormatPrecision($card->agent_card_value, 2, '.');
                    }
                }

                $cardData['card_description'] = $card->description ? $card->description : '';
                $cardData['instruction'] = $card->instruction ? $card->instruction : '';
                $allrecords[] = $cardData;
            }
        }
// echo '<pre>';print_r($allrecords);exit;
        return view('transactions.rechargeCard', ['title' => $pageTitle, 'allrecords' => $allrecords, 'cardDetail' => $cardValue, 'headTitle' => $headTitle]);
    }

    public function buyCard(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Buy Card');

        $user_id = Session::get('user_id');

        $input = Input::all();
        if (!empty($input)) {

            $card_id = $input['card_id'];
            $card_value = $input['card_value'];

            $data = array();
            $carddetail = Carddetail::where('id', $card_id)->where('status', 1)->where('used_status', 0)->first();
            $userInfo = User::where('id', $user_id)->first();
//            if (empty($userInfo)) {
//                $error = __('message.You are not verified OR KYC is not approved.');
//                $statusArr = array("status" => "Error", "reason" => $error);
//                echo json_encode($statusArr);
//                exit;
//            }
//            echo '<pre>';
//            print_r($carddetail->Card);
//            exit;
            if (!empty($carddetail)) {
                if ($userInfo->wallet_balance >= $card_value) {
                    $card_type = $carddetail->Card->card_type;
                    if ($card_type == 1) {
                        $type = 'Internet Recharge';
                        $title = __('message.Buy Internet Card');
                        $message = __("message.success_purchase", ['cost' => $carddetail->currency . " " . $carddetail->real_value, 'company' => $carddetail->Card->company_name, 'serial' => $carddetail->serial_number, 'pin' => $carddetail->pin_number, 'instruction' => $carddetail->instruction]);
//                        $message = __('message.Internet');"Successful purchase of recharge PIN equivalent to " . CURR . " " . $carddetail->card_value . " from System. " . $carddetail->Card->company_name . " " . $carddetail->card_value . " " . CURR . " Serial No " . $carddetail->serial_number . " Pin: " . $carddetail->pin_number . " " . $carddetail->instruction;
                        $successMessage = __('message.Pin:') . " <span id='copy_txt'>" . $carddetail->pin_number . "</span><br> " . __('message.S.No.:') . " " . $carddetail->serial_number;
                    } else if ($card_type == 2) {
                        $type = 'Mobile Recharge';
                        $title = __('message.Buy Mobile Card');
                        $message = __("message.success_purchase", ['cost' => $carddetail->currency . " " . $carddetail->real_value, 'company' => $carddetail->Card->company_name, 'serial' => $carddetail->serial_number, 'pin' => $carddetail->pin_number, 'instruction' => $carddetail->instruction]);
//                        $message = __('message.Internet');"Successful purchase of recharge PIN equivalent to " . CURR . " " . $carddetail->card_value . " from System. " . $carddetail->Card->company_name . " " . $carddetail->card_value . " " . CURR . " Serial No " . $carddetail->serial_number . " Pin: " . $carddetail->pin_number . " " . $carddetail->instruction;
                        $successMessage = __('message.Recharge PIN:') . " <span id='copy_txt'>" . $carddetail->pin_number . "</span><br>" . __('message.S.No.:') . " " . $carddetail->serial_number . '<br> ' . __('message.Dial') . ': ' . $carddetail->instruction . ' ' . __('message.to recharge') . '';
                    } else if ($card_type == 3) {
                        $type = 'Online Card';
                        $title = __('message.Buy Online Card');
                        $message = __("message.success_purchase", ['cost' => $carddetail->currency . " " . $carddetail->real_value, 'company' => $carddetail->Card->company_name, 'serial' => $carddetail->serial_number, 'pin' => $carddetail->pin_number, 'instruction' => $carddetail->instruction]);
//                        $message = __('message.Internet');"Successful purchase of recharge PIN equivalent to " . CURR . " " . $carddetail->card_value . " from System. " . $carddetail->Card->company_name . " " . $carddetail->card_value . " " . CURR . " Serial No " . $carddetail->serial_number . " Pin: " . $carddetail->pin_number . " " . $carddetail->instruction;
                        $successMessage = __('message.Pin:') . " <span id='copy_txt'>" . $carddetail->pin_number . "</span><br> " . __('message.S.No.:') . " " . $carddetail->serial_number;
                    }

                    $refrence_id = time() . rand() . '-' . $card_id;
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => 0,
                        'amount' => $card_value,
                        'amount_value' => $carddetail->card_value,
                        'real_value' => $carddetail->real_value,
                        'transaction_amount' => 0,
                        'total_amount' => $card_value,
                        'trans_type' => 2,
                        'trans_to' => 'Wallet',
                        'company_name' => $carddetail->Card->company_name,
                        'trans_for' => $type,
                        'payment_mode' => $type,
                        'refrence_id' => $refrence_id,
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();
                    $TransId = $trans->id;

                    Carddetail::where('id', $carddetail->id)->update(array('used_status' => 1, 'used_by' => $user_id, 'used_date' => date('Y-m-d H:i:s')));

                    $sender_wallet_amount = $userInfo->wallet_balance - $card_value;
                    User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                    $result['serial_number'] = $carddetail->serial_number;
                    $result['pin_number'] = $carddetail->pin_number;
                    $result['instruction'] = $carddetail->instruction;

//                    $title = "Buy Internet Card";
//                    $message = "Successful purchase of recharge PIN equivalent to " . CURR . " " . $carddetail->card_value . " from System. " . $carddetail->Card->company_name . " " . $carddetail->card_value . " " . CURR . " Serial No " . $carddetail->serial_number . " Pin: " . $carddetail->pin_number . " " . $carddetail->instruction;
                    $device_type = $userInfo->device_type;
                    $device_token = $userInfo->device_token;

//                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $userInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                    $statusArr = array("status" => "Success", "reason" => $successMessage);
                    echo json_encode($statusArr);
                    exit;
                } else {
                    $error = __('message.Insufficient Balance.');
                    $statusArr = array("status" => "Error", "reason" => $error);
                    echo json_encode($statusArr);
                    exit;
                }
            } else {
                $error = __('message.Online card not available');
                $statusArr = array("status" => "Error", "reason" => $error);
                echo json_encode($statusArr);
                exit;
            }
        }
    }

    public function buyCashCard(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Buy Cash Card');

        $user_id = Session::get('user_id');

        $input = Input::all();
        if (!empty($input)) {

            $card_id = $input['card_id'];
            $card_value = $input['card_value'];

            $data = array();
            $carddetail = Scratchcard::where('id', $card_id)->where('status', 1)->where('used_status', 0)->first();
            $userInfo = User::where('id', $user_id)->first();
//            if (empty($userInfo)) {
//                $error = __('message.You are not verified OR KYC is not approved.');
//                $statusArr = array("status" => "Error", "reason" => $error);
//                echo json_encode($statusArr);
//                exit;
//            }
//            echo '<pre>';
//            print_r($carddetail->Card);
//            exit;
            if (!empty($carddetail)) {
                $cardNumber = $carddetail->card_number;

                if ($userInfo->wallet_balance >= $card_value) {

                    $refrence_id = time() . rand() . '-' . $card_id;
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => 0,
                        'amount' => $card_value,
                        'amount_value' => $carddetail->card_value,
                        'transaction_amount' => 0,
                        'total_amount' => $carddetail->card_value,
                        'real_value' => $carddetail->real_value,
                        'trans_type' => 2,
                        'trans_to' => 'Wallet',
                        'trans_for' => 'Cash Card',
                        'payment_mode' => 'Cash Card',
                        'refrence_id' => $refrence_id,
                        'status' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $trans->save();
                    $TransId = $trans->id;

                    $sender_wallet_amount = $userInfo->wallet_balance - $card_value;
                    User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                    Scratchcard::where('card_number', $cardNumber)->update(array('purchase_by_id' => $user_id));

                    $title = __('message.Buy Cash Card');
                    $message = __("message.buy_cash_card", ['cost' => CURR . " " . $carddetail->real_value, 'card_number' => $carddetail->card_number]);
//                    $message = __('message.Internet');"Successful purchase of cash card equivalent to " . CURR . " " . $carddetail->card_value . " from System." . $carddetail->card_value . " " . CURR . " Card Number " . $carddetail->card_number;
                    $device_type = $userInfo->device_type;
                    $device_token = $userInfo->device_token;

//                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $userInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();

                    $successMessage = __('message.Recharge PIN:') . " <br><span id='copy_txt'>" . $carddetail->card_number . "</span>";
                    $statusArr = array("status" => "Success", "reason" => $successMessage);
                    echo json_encode($statusArr);
                    exit;
                } else {
                    $error = __('message.Insufficient Balance.');
                    $statusArr = array("status" => "Error", "reason" => $error);
                    echo json_encode($statusArr);
                    exit;
                }
            } else {
                $error = __('message.Online card not available');
                $statusArr = array("status" => "Error", "reason" => $error);
                echo json_encode($statusArr);
                exit;
            }
        }
    }

    public function buyBalance(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Buy Balance');

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        return view('transactions.buyBalance', ['title' => $pageTitle]);
    }

    public function sellBalance(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Sell Balance');

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        return view('transactions.sellBalance', ['title' => $pageTitle]);
    }

    public function refund(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Refund Payment');

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        return view('transactions.refund', ['title' => $pageTitle]);
    }

    public function buyRequestList(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Pending Requests');
        $user_id = Session::get('user_id');

        $userInfo = User::where('id', $user_id)->first();
        /* Payment_mode= Withdraw/Agent Deposit */
        /* trans_type= 4 */
//        echo '<pre>';print_r($requests);exit;
        $query = new Transaction();
        $query = $query->where('payment_mode', 'Withdraw');

        $query = $query->where("receiver_id", $user_id);
        $query = $query->where("status", 2);
        $query = $query->where("trans_type", 4);

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $limit = 30;

        $requests = $query->orderBy('id', 'DESC')->paginate($limit, ['*'], 'page', $page);

        if ($request->ajax()) {
            return view('elements.transactions.buyRequestList', ['requests' => $requests, 'page' => $page, 'isajax' => 1]);
        }

        return view('transactions.buyRequestList', ['title' => $pageTitle, 'requests' => $requests, 'page' => $page, 'limit' => $limit]);
    }

    public function sellRequestList(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Pending Requests');
        $user_id = Session::get('user_id');

        $userInfo = User::where('id', $user_id)->first();
        /* Payment_mode= Withdraw/Agent Deposit */
        /* trans_type= 4 */
        $query = new Transaction();
        $query = $query->where('payment_mode', 'Agent Deposit');

        $query = $query->where("receiver_id", $user_id);
        $query = $query->where("status", 2);
        $query = $query->where("trans_type", 4);

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $limit = 30;

        $requests = $query->orderBy('id', 'DESC')->paginate($limit, ['*'], 'page', $page);

        if ($request->ajax()) {
            return view('elements.transactions.sellRequestList', ['requests' => $requests, 'page' => $page, 'isajax' => 1]);
        }

        return view('transactions.sellRequestList', ['title' => $pageTitle, 'requests' => $requests, 'page' => $page, 'limit' => $limit]);
    }

    public function cancelAcceptRequest(Request $request) {
        $this->getSession();
        $user_id = Session::get('user_id');
        $request_id = $request->trans_id;
        $request_type = $request->request_type;

//        $userInfo = User::where('id', $user_id)->first();
        $userInfo = User::where('id', $user_id)->first();
//        if (empty($userInfo)) {
//            $error = __('message.You are not verified OR KYC is not approved.');
//            $statusArr = array("status" => "Error", "reason" => $error);
//            echo json_encode($statusArr);
//            exit;
//        }

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
                        $wallet_balance = $receiverInfo->wallet_balance + ($requestDetail->amount - $transactionFee);
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
                        $error = __('message.Insufficient Balance.');
                        $statusArr = array("status" => "Error", "reason" => $error);
                        echo json_encode($statusArr);
                        exit;
                    }
                } else {
                    Transaction::where('id', $request_id)->update(array('status' => 1, 'trans_type' => $type));

                    $receiverInfo = User::where('id', $requestDetail->user_id)->first();
                    $userInfo = User::where('id', $user_id)->first();
                    $wallet_balance = $userInfo->wallet_balance + $requestDetail->amount;
                    User::where('id', $userInfo->id)->update(array('wallet_balance' => $wallet_balance));

                    $title = __('message.Congratulations!');
//                    $message = __('message.Internet')"Congratulations! Your request successfully accepted for withdraw of amount " . $requestDetail->amount;
                    $message = __("message.accept_withdraw_request", ['cost' => CURR . " " . $requestDetail->amount]);
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

//                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $receiverInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                }
                $successMessage = __('message.Request Accepted Successfully');
                $statusArr = array("status" => "Success", "reason" => $successMessage);
                echo json_encode($statusArr);
                exit;
            } else {
                if ($type != 1) {
                    $userInfo = User::where('id', $requestDetail->user_id)->first();
                    $wallet_balance = $userInfo->wallet_balance + $requestDetail->total_amount;
                    User::where('id', $userInfo->id)->update(array('wallet_balance' => $wallet_balance));

                    $trans_id = time();
                    $refrence_id = 'Trans-' . $request_id;
                    $trans = new Transaction([
                        'user_id' => $user_id,
                        'receiver_id' => $userInfo->id,
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

                    $title = __('message.Congratulations!');
//                    $message = __('message.Internet')"Congratulations! Your request rejected for withdraw of amount " . $requestDetail->amount;
                    $message = __("message.reject_withdraw_request", ['cost' => CURR . " " . $requestDetail->amount, 'username' => $userInfo->name]);
                    $device_type = $userInfo->device_type;
                    $device_token = $userInfo->device_token;

//                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                    $notif = new Notification([
                        'user_id' => $userInfo->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $notif->save();
                } else {
                    $receiverInfo = User::where('id', $requestDetail->user_id)->first();

                    $title = __('message.Congratulations!');
                    $message = __("message.reject_deposit_request", ['cost' => CURR . " " . $requestDetail->amount, 'username' => $userInfo->name]);
//                    $message = __('message.Internet')"Congratulations! Your request accepted for deposit of amount " . $requestDetail->amount;
                    $device_type = $receiverInfo->device_type;
                    $device_token = $receiverInfo->device_token;

//                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

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
                $successMessage = __('message.Request Rejected Successfully');
                $statusArr = array("status" => "Success", "reason" => $successMessage);
                echo json_encode($statusArr);
                exit;
            }

            return response()->json($statusArr, 200);
        } else {
            $error = __('message.Invalid request id.');
            $statusArr = array("status" => "Error", "reason" => $error);
            echo json_encode($statusArr);
            exit;
        }
    }

    public function receiveByQr(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Receive Money');

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        return view('transactions.receiveByQr', ['title' => $pageTitle, 'userInfo' => $userInfo]);
    }

    public function buyBalanceQr(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Buy Balance');

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        return view('transactions.buyBalanceQr', ['title' => $pageTitle, 'userInfo' => $userInfo]);
    }

    public function merchantTransaction(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Transaction History');

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();

        $query = new Transaction();
        $NewDate = Date('Y-m-d', strtotime('-15 days'));
        $query = $query->where("created_at", '>=', $NewDate);

        $query = $query->where('payment_mode', '!=', 'Refund');
        $query = $query->where('payment_mode', '!=', 'Withdraw');
        $query = $query->where('payment_mode', '!=', 'Send Money');
        $query = $query->where('trans_type', 2);
        $query = $query->where('refund_status', 0);
        $query = $query->where('receiver_id', $user_id);

        if ($request->has('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        }

        $limit = 30;

        $allrecords = $query->orderBy('id', 'DESC')->paginate($limit, ['*'], 'page', $page);

        if ($request->ajax()) {
            return view('elements.transactions.merchantTransaction', ['allrecords' => $allrecords, 'userInfo' => $userInfo, 'page' => $page, 'isajax' => 1]);
        }

        return view('transactions.merchantTransaction', ['title' => $pageTitle, 'userInfo' => $userInfo, 'allrecords' => $allrecords, 'page' => $page, 'limit' => $limit]);
    }

    public function checkRefund() {

        $input = Input::all();
        if (!empty($input)) { //echo '<pre>';print_r($input);
            $trans_id = $input['trans_id'];

            $user_id = Session::get('user_id');
            $userInfo = User::where('id', $user_id)->first();

            $request = Transaction::where('id', $trans_id)->first();

            //echo '<pre>';print_r($userInfo);
            // echo '<pre>';print_r($receiverInfo);exit;
            if ($userInfo->trans_pay_by != 'User') {
                $receiverInfo = User::where('id', $request->user_id)->first();
                $amount = $request->amount;

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

                // $transactionFee = $this->getRefundFee($userInfo->id, $request->amount);
                $transactionFee = $transFee;
                $totalAmt = $request->amount + $transactionFee;

                echo __("message.merchant_refund", ['cost' => CURR . " " . $request->amount, 'username' => $receiverInfo->name, 'recieverName' => $receiverInfo->name, 'fee' => CURR . ' ' . $this->numberFormatPrecision($transactionFee, 2), 'paidAmt' => CURR . " " . $totalAmt]);
            } else {
                $receiverInfo = User::where('id', $request->user_id)->first();
                $amount = $request->amount;

                $userFee = Usertransactionfee::where('user_id', $request->user_id)->where('transaction_type', 'Refund')->where('status', 1)->first();
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

                // $transactionFee = $this->getRefundFee($userInfo->id, $request->amount);
                $transactionFee = $transFee;
                $totalAmt = $request->amount - $transactionFee;

                //echo $receiverInfo->name;

                echo __("message.user_refund", ['cost' => CURR . " " . $request->amount, 'username' => $receiverInfo->name, 'recieverName' => $receiverInfo->name, 'fee' => CURR . ' ' . $this->numberFormatPrecision($transactionFee, 2), 'paidAmt' => CURR . " " . $totalAmt]);
            }
            exit;
        }
    }

    public function refundPayment(Request $request) {
        $this->getSession();

        $user_id = Session::get('user_id');
        $transaction_id = $request->trans_id;

        $userInfo = User::where('id', $user_id)->first();

        $requestDetail = Transaction::where('id', $transaction_id)->first();

        if (!empty($requestDetail)) {



            if ($userInfo->trans_pay_by == 'User') {

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
                $receiverInfo = User::where('id', $requestDetail->receiver_id)->first();
                $wallet_balance = $receiverInfo->wallet_balance - $requestDetail->amount;
                User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                $senderInfo = User::where('id', $requestDetail->user_id)->first();
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
            } else {

                $receiverInfo = User::where('id', $requestDetail->receiver_id)->first();
                // $transactionFee = $this->getRefundFee($receiverInfo->id, $requestDetail->amount);

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
                $totalAmt = $requestDetail->amount + $transFee;

                $wallet_balance = $receiverInfo->wallet_balance - $totalAmt;
                User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                $senderInfo = User::where('id', $requestDetail->user_id)->first();
                $sender_wallet_balance = $senderInfo->wallet_balance + $requestDetail->amount;
                User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                $trans_id = time();
                $refrence_id = 'Trans-' . $transaction_id;
                $trans = new Transaction([
                    'user_id' => $senderInfo->id,
                    'receiver_id' => $receiverInfo->id,
                    'amount' => $requestDetail->amount,
                    'amount_value' => $requestDetail->amount,
                    'transaction_amount' => $transFee,
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





            $successMessage = __('message.Amount Refunded Successfully');
            $statusArr = array("status" => "Success", "reason" => $successMessage);
            echo json_encode($statusArr);
            exit;
        } else {
            $error = __('message.Invalid request id.');
            $statusArr = array("status" => "Error", "reason" => $error);
            echo json_encode($statusArr);
            exit;
        }
    }

    public function paymentProcess($slug = null, Request $request) {
//        $this->getSession();
        $pageTitle = 'Payment Checkout';

        $user_id = Session::get('user_id');
        $userInfo = User::where('id', $user_id)->first();
        $orderInfo = Order::where('slug', $slug)->first();

        return view('transactions.paymentProcess', ['title' => $pageTitle, 'orderId' => $orderInfo->id]);
    }

    public function checkOrderStatus(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Check Order Status');

        $user_id = Session::get('user_id');
        $order_id = $request->id;

        $orderInfo = Order::where('id', $order_id)->first();
        echo $orderInfo->status;
        exit;
    }

    public function sendRefund(Request $request) {
        $this->getSession();
        $pageTitle = __('message.Refund');

        $user_id = Session::get('user_id');

        $input = Input::all();
        if (!empty($input)) {
            $phone = $input['phone'];
            $amount = $input['amount'];
            $transactionFee = $input['trans_fee'];

            if ($phone == "" or!is_numeric($phone)) {
                $statusArr = array("status" => "Failed", "reason" => __("message.Invalid Phone Number."));
                return response()->json($statusArr, 200);
            } else {
                $matchThese = ["users.phone" => $phone];
                $userInfo = $recieverUser = DB::table('users')->where($matchThese)->first();

                if (!empty($recieverUser)) {

                    if ($recieverUser->id == $user_id) {
                        $error = __("message.You can not send fund for own account.");
                        $statusArr = array('result' => 0, "status" => "Error", 'message' => $error);
                        echo json_encode($statusArr);
                        exit;
                    }

//                $matchThese = ["users.id" => $request->user_id, "users.is_verify" => 1, "users.is_kyc_done" => 1];
                    $matchThese = ["users.id" => $user_id];
                    $userDetail = $senderUser = DB::table('users')->where($matchThese)->first();

//                if (empty($senderUser)) {
//                    $statusArr = array("status" => "Failed", "reason" => "You are not verified OR KYC is not approved.");
//                    return response()->json($statusArr, 200);
//                }


                    $senderUserType = $senderUser->user_type;
                    $receiverUserType = $recieverUser->user_type;

                    if ($senderUserType == 'Merchant') {
                        if ($receiverUserType == 'Merchant') {

                            $totalAmt = number_format(($amount + $transactionFee), 2);

                            if ($totalAmt > $senderUser->wallet_balance) {
                                $error = __("message.Insufficient Balance.");
                                $statusArr = array('result' => 0, "status" => "Error", 'message' => $error);
                                echo json_encode($statusArr);
                                exit;
                            }

                            $trans_id = time();
                            $refrence_id = time() . rand() . $user_id;
                            $trans = new Transaction([
                                'user_id' => $user_id,
                                'receiver_id' => $recieverUser->id,
                                'amount' => $amount,
                                'amount_value' => $amount,
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
                            User::where('id', $user_id)->update(['wallet_balance' => $sender_wallet_amount]);

                            $reciever_wallet_amount = $recieverUser->wallet_balance + $amount;
                            User::where('id', $recieverUser->id)->update(['wallet_balance' => $reciever_wallet_amount]);

                            $title = __("message.debit_title", ['cost' => CURR . " " . $totalAmt]);
                            $message = __("message.debit_message", ['cost' => CURR . " " . $totalAmt, 'username' => $recieverUser->name]);
                            $device_type = $senderUser->device_type;
                            $device_token = $senderUser->device_token;

//                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $senderUser->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();

                            $title = __("message.credit_title", ['cost' => CURR . " " . $amount]);
                            $message = __("message.credit_message", ['cost' => CURR . " " . $amount, 'username' => $senderUser->name]);
                            $device_type = $recieverUser->device_type;
                            $device_token = $recieverUser->device_token;

//                            $this->sendPushNotification($title, $message, $device_type, $device_token);

                            $notif = new Notification([
                                'user_id' => $recieverUser->id,
                                'notif_title' => $title,
                                'notif_body' => $message,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $notif->save();

                            $error = __("message.Sent Successfully");
                            $statusArr = array('result' => 1, "status" => "Success", 'message' => $error);
                            echo json_encode($statusArr);
                            exit;
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

                                $error = __("message.Sent Successfully");
                                $statusArr = array('result' => 1, "status" => "Success", 'message' => $error);
                                echo json_encode($statusArr);
                                exit;
                            } else {
                                $error = __("message.Insufficient Balance.");
                                $statusArr = array('result' => 0, "status" => "Error", 'message' => $error);
                                echo json_encode($statusArr);
                                exit;
                            }
                        } else {
                            $paymentType = 'Refund';
                            // echo $senderUser->trans_pay_by;
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
                                    $error = __("message.Insufficient Balance.");
                                    $statusArr = array('result' => 0, "status" => "Error", 'message' => $error);
                                    echo json_encode($statusArr);
                                    exit;
                                }

                                $receiverInfo = $senderUser;
                                $wallet_balance = $receiverInfo->wallet_balance - $totalAmt;
                                User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                                $senderInfo = $recieverUser;
                                $sender_wallet_balance = $senderInfo->wallet_balance + $amount;
                                User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                                $trans_id = time();
                                $refrence_id = time() . rand() . $user_id;
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
                                    $error = __("message.Insufficient Balance.");
                                    $statusArr = array('result' => 0, "status" => "Error", 'message' => $error);
                                    echo json_encode($statusArr);
                                    exit;
                                }


                                $wallet_balance = $receiverInfo->wallet_balance - $amount;
                                User::where('id', $receiverInfo->id)->update(array('wallet_balance' => $wallet_balance));

                                $senderInfo = $recieverUser;
                                $sender_wallet_balance = $senderInfo->wallet_balance + $totalAmt;
                                User::where('id', $senderInfo->id)->update(array('wallet_balance' => $sender_wallet_balance));

                                $trans_id = time();
                                $refrence_id = time() . rand() . $user_id;
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

                            $successMessage = __('message.Amount Refunded Successfully');
                            $statusArr = array('result' => 1, "status" => "Success", "reason" => $successMessage);
                            echo json_encode($statusArr);
                            exit;
                        }
                    }
                } else {
                    $error = __("message.Receiver not found.");
                    $statusArr = array('result' => 0, "status" => "Error", 'message' => $error);
                    echo json_encode($statusArr);
                    exit;
                }
            }
        }



        return view('transactions.sendRefund', ['title' => $pageTitle]);
    }

    public function getRefundFee($payerId = null, $amount = null) {
        $userInfo = User::where('id', $payerId)->first();

        $userFee = Usertransactionfee::where('user_id', $payerId)->where('transaction_type', 'Refund')->where('status', 1)->first();
        if (empty($userFee)) {
            $fees = Transactionfee::where('transaction_type', 'Refund')->where('status', 1)->first();

            if (!empty($fees)) {
                if ($userInfo->user_type == 'Merchant') {
                    $transFee = $this->numberFormatPrecision((($amount * $fees->merchant_charge) / 100), 2);
                } else {
                    $transFee = $this->numberFormatPrecision((($amount * $fees->user_charge) / 100), 2);
                }
            }
        } else {
            if (!empty($userFee)) {
                $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
            }
        }

        return $transFee;
    }

    private function sendPushNotification($title, $message, $device_type, $device_token) {

        if (strtolower($device_type) != "web") {


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
        } else {
            return 1;
        }
    }

    public function cancelAcceptWithdrawRequest(Request $request) {
        $this->getSession();
        $user_id = Session::get('user_id');
        $request_id = $request->trans_id;
        $request_type = $request->request_type;

        $userInfo = User::where('id', $user_id)->first();

        $requestDetail = Transaction::where('id', $request_id)->where('user_id', $user_id)->where('trans_type', 4)->first();
        if (!empty($requestDetail)) {

            if ($request_type == 'Accept') {
                if ($userInfo->wallet_balance >= $requestDetail->amount) {
                    $transFee = 0;
                    Transaction::where('id', $request_id)->update(array('status' => 1, 'trans_type' => 2));

                    $userFee = Usertransactionfee::where('user_id', $requestDetail->user_id)->where('transaction_type', 'Merchant Withdraw')->where('status', 1)->first();
                    if (empty($userFee)) {
                        $fees = Transactionfee::where('transaction_type', 'Merchant Withdraw')->where('status', 1)->first();

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

                    $receiverInfo = User::where('id', $requestDetail->receiver_id)->first();
                    $wallet_balance = $receiverInfo->wallet_balance + ($requestDetail->amount - $transactionFee);
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
                    $error = __('message.Insufficient Balance.');
                    $statusArr = array("status" => "Error", "reason" => $error);
                    echo json_encode($statusArr);
                    exit;
                }
                $successMessage = __('message.Request Accepted Successfully');
                $statusArr = array("status" => "Success", "reason" => $successMessage);
                echo json_encode($statusArr);
                exit;
            } else {
                
                $receiverInfo = User::where('id', $requestDetail->receiver_id)->first();

                $title = __('message.Congratulations!');
//                    $message = __('message.Internet')"Congratulations! Your request rejected for withdraw of amount " . $requestDetail->amount;
                $message = __("message.reject_withdraw_request", ['cost' => CURR . " " . $requestDetail->amount, 'username' => $userInfo->name]);
                $device_type = $userInfo->device_type;
                $device_token = $userInfo->device_token;

//                $result = $this->sendPushNotification($title, $message, $device_type, $device_token);

                $notif = new Notification([
                    'user_id' => $receiverInfo->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $notif->save();
                
                Transaction::where('id', $request_id)->update(array('status' => 4));
                $successMessage = __('message.Request Rejected Successfully');
                $statusArr = array("status" => "Success", "reason" => $successMessage);
                echo json_encode($statusArr);
                exit;
            }

            return response()->json($statusArr, 200);
        } else {
            $error = __('message.Invalid request id.');
            $statusArr = array("status" => "Error", "reason" => $error);
            echo json_encode($statusArr);
            exit;
        }
    }

}

?>