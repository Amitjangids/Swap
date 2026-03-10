<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mail;
use DB;
use Session;
use Redirect;
use Input;
use App\Models\Gig;
use App\Models\User;
use App\Models\Myorder;
use App\Models\Transaction;
use App\Models\WithdrawRequest;
use App\Models\Notification;
use App\Models\Usertransactionfee;
use App\Models\Transactionfee;

class HomesController extends Controller {

    public function chooseAccount() {
        $this->getSession();
        $pageTitle = __('message.Choose Account');

        return view('homes.chooseAccount', ['title' => $pageTitle]);
    }

    public function payment_api() {
        $pageTitle = 'Payment API';

        return view('homes.payment_api', ['title' => $pageTitle]);
    }

    public function withdrawAPI(Request $request) {

        $merchant_id = $request->get('merchant_key');
        $user_phone = $request->get('user_phone');
        $amount = $request->get('amount');
        $order_id = $request->get('order_id');



        $user_name = $order_amount = $user_id = $user = '';
        $merchant_user = User::where('api_key', $merchant_id)->first();
//echo '<pre>';print_r($merchant_user);
        if (empty($amount)) {
            $amount = 0;
        } else {
            if ($amount > 0) {
                $amount = (abs($amount));
            } else {
                $amount = 0;
            }
        }

        if ($amount == 0) {
            $statusArr = array("status" => "Failed", "Reason" => "Please enter valid amount");
            $json = json_encode($statusArr);
            echo $json;
            exit;
        }

        if (empty($order_id)) {
            $statusArr = array("status" => "Failed", "Reason" => "Please enter valid order id");
            $json = json_encode($statusArr);
            echo $json;
            exit;
        }

        if (empty($merchant_user)) {
            $statusArr = array("status" => "Failed", "Reason" => "Please enter valid merchant key");
            $json = json_encode($statusArr);
            echo $json;
            exit;
        } else {
            $merchant_name = $merchant_user->name;
        }

        $user = User::where('phone', $user_phone)->first();
        if (empty($user)) {
            $statusArr = array("status" => "Failed", "Reason" => "Please enter valid user phone number");
            $json = json_encode($statusArr);
            echo $json;
            exit;
        } else {
            $user_name = $user->name;
        }
        
        $trans = Transaction::where('refrence_id', $order_id)->where('status', 1)->first();
        if (!empty($trans)) {
            $statusArr = array("status" => "Success", "Reason" => "Withdrawal request completed successfully.",'transaction_id' => $trans->id,'order_id'=>$order_id);
            $json = json_encode($statusArr);
            echo $json;
            exit;
        } else {
            $user_name = $user->name;
        }

        $conversion_fee_amount = 0;
        $transFee = 0;


        if ($merchant_user->wallet_balance >= $amount) {
//echo $merchant_user->newwithdrawal_trans_pay_by;exit;
            if ($merchant_user->newwithdrawal_trans_pay_by == 'User') {
                $chargeBy = 'User';
                $payerid=$user->id;
             } else {
                $chargeBy = 'Merchant';
                $payerid=$merchant_user->id;
              }



            $userFee = Usertransactionfee::where('user_id', $payerid)->where('transaction_type', 'Merchant Withdraw')->where('status', 1)->first();
            if (empty($userFee)) {
                $fees = Transactionfee::where('transaction_type', 'Merchant Withdraw')->where('status', 1)->first();

                if (!empty($fees)) {

                    if ($chargeBy == 'Merchant') {
                        $chargeFee = $fees->merchant_charge;
                    } else {

                        if($user->user_type=="Agent"){
                            $chargeFee = $fees->agent_charge;

                        }else if($user->user_type=="Individual"){ 
                        $chargeFee = $fees->user_charge;

                        }else if($user->user_type=="Merchant"){ 
                            $chargeFee = $fees->merchant_charge;
    
                            }
                        
                    }

                    $transFee = $this->numberFormatPrecision((($amount * $chargeFee) / 100), 2);
                }
            } else {
                if (!empty($userFee)) {
                    $transFee = $this->numberFormatPrecision((($amount * $userFee->user_charge) / 100), 2);
                }
            }



            $transactionFee = $transFee;
            $ttlAmt = $amount;
if ($chargeBy == 'Merchant') {


            $user_balance = $user->wallet_balance + $amount;
            User::where('id', $user->id)->update(array('wallet_balance' => $user_balance));

            $merchant_balance = $merchant_user->wallet_balance - ($amount+ $transactionFee);
            User::where('id', $merchant_user->id)->update(array('wallet_balance' => $merchant_balance));

}else{

 $user_balance = $user->wallet_balance + ($amount - $transactionFee);
            User::where('id', $user->id)->update(array('wallet_balance' => $user_balance));

            $merchant_balance = $merchant_user->wallet_balance - $amount;
            User::where('id', $merchant_user->id)->update(array('wallet_balance' => $merchant_balance));



}

$ttlAmts = $amount;
            $refrence_id = $order_id;
            $trans = new Transaction([
                "user_id" => $merchant_user->id,
                "receiver_id" => $user->id,
                'amount' => $ttlAmt,
                'amount_value' => $ttlAmt,
                'transaction_amount' => $transactionFee,
                'total_amount' => $ttlAmts,
                "currency" => $user->currency,
                "trans_type" => 2,
                "payment_mode" => 'Merchant Withdraw',
                "refrence_id" => $refrence_id,
                "user_close_bal" => $user_balance,
                'fee_pay_by'=>$chargeBy,
                "status" => 1,
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);
            $trans->save();
            $TransId = $trans->id;

            $userInfo = $user;
            $user_name = $userInfo->name;

            $uname = trim($user_name);


            if ($chargeBy == 'User') {
                $amount=$amount;
            }else{

                $amount=$amount+$transactionFee;
            }


            $title = __("message.debit_title", ['cost' => CURR . " " . $amount]);
            $message = __("message.debit_message", ['cost' => CURR . " " . $amount, 'username' => $user->name]);

            $device_type = $merchant_user->device_type;
            $device_token = $merchant_user->device_token;

//                                $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                'user_id' => $merchant_user->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();
            if ($chargeBy == 'Merchant') {
                $ttlAmt=$ttlAmt;
            }else{
                $ttlAmt=$ttlAmt-$transactionFee;

            }


            $title = __("message.credit_title", ['cost' => CURR . " " . $ttlAmt]);
            $message = __("message.credit_message", ['cost' => CURR . " " . $ttlAmt, 'username' => $merchant_user->name]);
            $device_type = $user->device_type;
            $device_token = $user->device_token;

//                                $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                'user_id' => $user->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            $statusArr = array("status" => "Success", "Reason" => "Withdrawal request completed successfully.",'transaction_id' => $TransId,'order_id'=>$order_id);
            $json = json_encode($statusArr);
            echo $json;
            exit;
            
        } else {
            Session::flash('error_message', "Merchant Don't have sufficient balance.");
        }

        return view('homes.withdraw_api', ['title' => $pageTitle]);
    }

    public function withdrawAPI_old() {

        $pageTitle = 'Withdraw API';

        return view('homes.withdraw_api', ['title' => $pageTitle]);
    }

    public function charges(Request $request) {
        // $input = Input::all();
        //     print_r($input);die;
        Session::forget('reqStr');

        $order_id = $request->get('order_id');
        $order_amount = $request->get('order_amount');
        $merchant_id = $request->get('merchant_key');
        $url = $request->get('return_url');
        $currency_code = $request->get('currency_code');
//

        $base64Req = base64_encode($order_id . '###' . $order_amount . '###' . $merchant_id . '###' . $currency_code . '###' . $url);

        Session::put('reqStr', $base64Req);
        return redirect::to('payBySatpay');
    }

    public function charges1(Request $request) {

        if ($request->has('user_id')) {
            $user_id = $request->get('user_id');
        }

        if ($request->has('order_id')) {
            $order_id = $request->get('order_id');
        }

        if ($request->has('order_amount')) {
            $order_amount = $request->get('order_amount');
        }

        if ($order_amount == "" or!is_numeric($order_amount)) {
            $statusArr = array("status" => "Failed", "reason" => "Invalid Amount.");
            return response()->json($statusArr, 200);
        }

        if ($user_id) {
            $userInfo = User::where('id', $user_id)->first();
            if (!empty($userInfo)) {
//                $referer = $_SERVER['HTTP_REFERER'];
                $referer = 'Test';

                $serialisedData = array();
                $serialisedData['merchant_id'] = $user_id;
                $serialisedData['order_id'] = $order_id;
                $serialisedData['amount'] = $order_amount;
                $serialisedData['referer'] = $referer;
                $serialisedData['status'] = 0;
                $slug = $serialisedData['slug'] = $user_id . $order_id . time();
                $serialisedData['created_at'] = date('Y-m-d H:i:s');
                $serialisedData['updated_at'] = date('Y-m-d H:i:s');

//                $user_id = $orderInfo->user_id;
//                $order_id = $orderInfo->order_id;
                $amt = $order_amount;

                $qrString = $user_id . "##" . $order_id . "##" . $amt;
                $qrCode = $this->generateQRCode($qrString, $user_id);
//                $qrCode = '';
                $serialisedData['qr_code'] = $qrCode;

                Order::insert($serialisedData);

                return Redirect::to('onlineShopping/' . $slug);
            } else {
                $statusArr = array("status" => "Failed", "reason" => "User not valid.");
                return response()->json($statusArr, 501);
            }
        }
    }

    public function paymentsuccess($reqStr) {
        $req = base64_decode($reqStr);

        $reqArr = explode('###', $req);
        $trans_id = $reqArr[0];
        if (isset($reqArr[1])) {
            $order_amount = $reqArr[1];
        }
        if (isset($reqArr[2])) {
            $order_id = $reqArr[2];
        }

//         $make_call = callAPI('POST', 'https://www.dafribank.com/api-transaction-detail', $order_id);
// $response = json_decode($make_call, true);
// echo '<pre>';print_r($response);exit;

        echo 'Payment completed for Transaction ID : ' . $trans_id . ' for Order ID : ' . $order_id;
        exit;
    }

    public function apiTransactionDetail(Request $request) {
        $post = $request->all();
        $traction_id = $post['transaction_id'];
        $traction_responce = Transaction::where('refrence_id', $traction_id)->first();
        $trans_type = $status = '';
        if ($traction_responce->trans_type == '1') {
            $trans_type = 'Credit';
        } else if ($traction_responce->trans_type == '2') {
            $trans_type = 'Debit';
        } else if ($traction_responce->trans_type == '3') {
            $trans_type = 'Topup';
        } else if ($traction_responce->trans_type == '4') {
            $trans_type = 'Request';
        }
        //  1=Success;2=PENDING;3=Cancelled;4=Failed;5=Error;6=Abandoned;7=PendingInvestigation 
        if ($traction_responce->status == '1') {
            $status = 'Success';
        } else if ($traction_responce->status == '2') {
            $status = 'Pending';
        } else if ($traction_responce->status == '3') {
            $status = 'Cancelled';
        } else if ($traction_responce->status == '4') {
            $status = 'Failed';
        } else if ($traction_responce->status == '5') {
            $status = 'Error';
        } else if ($traction_responce->status == '6') {
            $status = 'Abandoned';
        } else if ($traction_responce->status == '7') {
            $status = 'PendingInvestigation';
        } else {
            
        }


        $transaction_final = array();
        $transaction_final['transaction_id'] = $traction_responce->refrence_id;
        $transaction_final['order_id'] = $traction_responce->billing_description;
        //$transaction_final['amount'] = $traction_responce->amount;
        //$transaction_final['fees'] = $traction_responce->fees;
        //$transaction_final['receiver_fees'] = $traction_responce->receiver_fees != '' ? $traction_responce->receiver_fees:0.00;
        //$transaction_final['sender_fees'] = $traction_responce->sender_fees != '' ? $traction_responce->sender_fees:0.00;
        //$transaction_final['currency'] = $traction_responce->currency;
        //$transaction_final['trans_type'] = $trans_type;
        // $transaction_final['billing_description'] = $traction_responce->billing_description;
        //$transaction_final['reference_note'] =$traction_responce->reference_note != '' ? $traction_responce->reference_note:'';
        $transaction_final['status'] = $status;
        $transaction_final['date_time'] = date('d-m-Y H:i:s', strtotime($traction_responce->created_at));


        echo json_encode($transaction_final);
        die;
    }

    public function merchantWithdrawal(Request $request) {
        $merchant_id = $request->get('merchant_key');
        $user_phone = $request->get('user_phone');
        $amount = $request->get('amount');
        $remark = $request->get('remark');
        $return_url = $request->get('return_url');
        $order_id = $request->get('order_id');

        $base64Req = base64_encode($merchant_id . '###' . $user_phone . '###' . $amount . '###' . $remark . '###' . $return_url . '###' . $order_id);
        Session::put('reqwithdrawStr', $base64Req);
        return redirect::to('withdraw-payment');
    }

    public function withdrawPayment() {
        $pageTitle = 'Withdraw Request';

        $reqwithdrawStr = Session::get('reqwithdrawStr');
        $req = base64_decode($reqwithdrawStr);

        $reqArr = explode('###', $req);
//        print_r($reqArr);die;
        $merchant_id = $reqArr[0];
        $user_phone = $reqArr[1];
        $amount = $reqArr[2];
        $remark = $reqArr[3];
        $return_url = $reqArr[4];
        $order_id = $reqArr[5];

        $user_name = $order_amount = $user_id = $user = '';
        $merchant_user = User::where('api_key', $merchant_id)->first();

        if (empty($amount)) {
            $amount = 0;
        } else {
            if ($amount > 0) {
                $amount = (abs($amount));
            } else {
                $amount = 0;
            }
        }

        if (empty($order_id)) {
            $order_id = 'N/A';
        }
        $merchant_name = '';
//echo '<pre>';print_r($merchant_user);exit;
        if (empty($merchant_user)) {
//            Session::flash('error_message', 'Invalid Merchant ID');
            $user_id = 'N/A';
            $user_name = 'N/A';
        } else {
            $merchant_name = $merchant_user->name;
        }

        $user = User::where('phone', $user_phone)->first();
        //print_r($user);die;
        if (empty($user)) {
//            Session::flash('error_message', 'Invalid User ID');
            $user_id = 'N/A';
            $user_name = 'N/A';
        } else {
            $user_name = $user->name;
        }

        $input = Input::all();
        if (!empty($input['submit'])) {
            $conversion_fee_amount = 0;

//            if ($merchant_user->wallet_balance >= $amount) {
            $usrWallet = $merchant_user->wallet_balance;

            $refrence_id = $order_id;
            $trans = new Transaction([
                "user_id" => $merchant_user->id,
                "receiver_id" => $user->id,
                'amount' => $amount,
                'amount_value' => $amount,
                'transaction_amount' => 0,
                'total_amount' => $amount,
                "currency" => $user->currency,
                "trans_type" => 4,
                "payment_mode" => 'Merchant Withdraw',
                "refrence_id" => $refrence_id,
                "user_close_bal" => $usrWallet,
                "fee_pay_by" => $amount,
                "status" => 2,
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);
            $trans->save();
            $TransId = $trans->id;

//                $wrq = new WithdrawRequest([
//                    'user_id' => $user->id,
//                    'req_type' => 'Merchant',
//                    'user_name' => $user_name,
//                    'agent_id' => $merchant_user->id,
//                    'amount' => $amount,
//                    'created_at' => date('Y-m-d H:i:s'),
//                    'updated_at' => date('Y-m-d H:i:s'),
//                ]);
//                $wrq->save();
//                $withdrawReqID = $wrq->id;

            $userInfo = $user;
            $user_name = $userInfo->name;

            $uname = trim($user_name);

            $title = 'Merchant Withdrawal Request Received';
            $message = "Congratulations! You've received " . CURR . " " . $amount . " Withdrawal Request from " . $uname;
//                                    $message = __("message.receive_shopping_payment", ['cost' => CURR . " " . $orderInfo->amount, 'username' => $senderUser->name]);
            $device_type = $merchant_user->device_type;
            $device_token = $merchant_user->device_token;

            $this->sendPushNotification($title, $message, $device_type, $device_token);

            $notif = new Notification([
                'user_id' => $merchant_user->id,
                'notif_title' => $title,
                'notif_body' => $message,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $notif->save();

            $base64Result = base64_encode($TransId . '###Success' . '###' . $order_id);
            return Redirect::to($return_url . '/' . $base64Result);
//            } else {
//                Session::flash('error_message', "Merchant Don't have sufficient balance.");
//            }
        }



        return view('homes.withdrawPayment', ['title' => $pageTitle, 'order_id' => $order_id, 'amount' => $amount, 'user_id' => $user_id, 'user_name' => $user_name, 'merchant_user' => $merchant_user, 'merchant_name' => $merchant_name, 'user' => $user]);
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

}

?>