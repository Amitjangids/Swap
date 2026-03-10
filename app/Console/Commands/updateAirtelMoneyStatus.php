<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\SendMailable;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\TransactionLedger;
use DB;
use App;
use GuzzleHttp\Client;
use App\User;
use App\Services\AirtelMoneyService;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;
use DateTime;
use App\Services\CardService;
use App\Models\UserCard;

class updateAirtelMoneyStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateAirtelMoneyStatus';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Transaction Status Which Done By Airtel';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $airtelMoneyService;
    protected $firebaseNotificationService;
    public $cardService;
    public function __construct(AirtelMoneyService $airtelMoneyService, CardService $cardService)
    {
        parent::__construct();
        $this->airtelMoneyService = $airtelMoneyService;
        $this->firebaseNotificationService = new FirebaseService();
        $this->cardService = $cardService;
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $transaction_list = Transaction::where('status', 2)->where('transactionType', 'AIRTELMONEY')->where('swapDomainName','!=','UAT_SERVER')->get();
        if ($transaction_list->isEmpty()) {
            echo "No transaction found";
            die;
        }

        $getLang = DB::table('app_language')->value('lang') ?? 'en';
        App::setLocale($getLang);

        foreach ($transaction_list as $transaction) {

            $createdAt = new DateTime($transaction->created_at);
            $now = new DateTime();
            $diffInMinutes = ($now->getTimestamp() - $createdAt->getTimestamp()) / 60;


            if (!empty($transaction->airtelTransId) && $transaction->airtelTransId != null) {
                $payloadRefund = [
                    "airtelTransId" => $transaction->airtelTransId,
                ];

                $getResponse = $this->airtelMoneyService->transactionEnquiry($payloadRefund);
                $responseData = $getResponse['data']['status']['response_code'];
                $airtelMoneyId = $getResponse['data']['data']['transaction']['airtel_money_id'] ?? null;
                $message = $getResponse['data']['data']['transaction']['message'] ?? null;
                // $responseData = "DP00800001001";
                $senderUser = User::where('id', $transaction->user_id)->first();
                if ($responseData == "DP00800001001") {
                    Log::info(["Payment Success " => $getResponse]);
                    $sender_wallet_amount = $senderUser->wallet_balance + $transaction->amount;

                    $debit = new TransactionLedger([
                        'user_id' => $senderUser->id,
                        'opening_balance' => $senderUser->wallet_balance,
                        'amount' => $transaction->amount,
                        'actual_amount' => $transaction->amount,
                        'type' => 1,
                        'trans_id' => $transaction->id,
                        'payment_mode' => 'airtelmoney',
                        'closing_balance' => $sender_wallet_amount,
                        'created_at' => date(DATE_TIME_FORMAT),
                        'updated_at' => date(DATE_TIME_FORMAT),
                    ]);
                    $debit->save();

                    User::where('id', $senderUser->id)->update(['wallet_balance' => $sender_wallet_amount]);
                    DB::table('admins')->where('id', 1)->increment('wallet_balance', $transaction->transaction_amount);
                    Transaction::where('id', $transaction->id)->update(['airtelMoneyId' => $airtelMoneyId, 'airtelStatusDescription' => $message, 'status' => 1, 'remainingWalletBalance' => $sender_wallet_amount,'afterBalance'=>$sender_wallet_amount]);

                    $userCard = UserCard::where('userId', $senderUser->id)->where('cardType', 'PHYSICAL')->first();
                    if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                        $postData = json_encode(["currencyCode" => "XAF","last4Digits" => $userCard->last4Digits,"referenceMemo" => "Settlement","transferAmount" => $transaction->amount,
                            "transferType" => "WalletToCard","mobilePhoneNumber" => "241{$senderUser->phone}"]);
                        $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                    }

                    $title = __('message_app.payment_received_title', [
                        'amount' => $transaction->amount
                    ]);
                    $message = __('message_app.payment_received_message', [
                        'amount' => $transaction->amount
                    ]);
                    $device_token = $senderUser->device_token;
                    $device_type = $senderUser->device_type;

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'Transaction',
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
                        'user_id' => $senderUser->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date(DATE_TIME_FORMAT),
                        'updated_at' => date(DATE_TIME_FORMAT),
                    ]);
                    $notif->save();

                } else if ($responseData == "DP00800001005" && $diffInMinutes >= 10) {
                    Log::info(["Payment Waiting" => $getResponse]);
                    Transaction::where('id', $transaction->id)->update(['airtelMoneyId' => $airtelMoneyId, 'airtelStatusDescription' => $message, 'status' => 3,'afterBalance'=>$senderUser->wallet_balance]);
                } else if (
                    $responseData == "DP00800001010" ||
                    $responseData == "DP00800001002" ||
                    $responseData == "DP00800001003" ||
                    $responseData == "DP00800001004" ||
                    $responseData == "DP00800001000" ||
                    $responseData == "DP00800001007" ||
                    $responseData == "DP00800001010"
                ) {
                    Log::info(["Payment failed response" => $getResponse]);
                    Transaction::where('id', $transaction->id)->update(['airtelMoneyId' => $airtelMoneyId, 'airtelStatusDescription' => $message, 'status' => 3,'afterBalance'=>$senderUser->wallet_balance]);
                }
            }
        }
    }
}
