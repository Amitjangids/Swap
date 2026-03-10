<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\SendMailable;
use App\Models\Transaction;
use App\Models\ExcelTransaction;
use App\Models\TransactionLedger;
use App\Services\ReferralService;
use DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\User;
use Illuminate\Support\Facades\Log;
use App\Models\UserCard;
use App\Services\CardService;

class updateCemacTransaction extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateCemacTransaction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Transaction Status Which Done By CEMAC';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $cardService;
    public function __construct(ReferralService $referralService, CardService $cardService)
    {
        parent::__construct();
        $this->referralService = $referralService;
        $this->cardService = $cardService;
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Log::channel('GIMAC')->info("Test");
        $gimac_transaction_list = Transaction::whereIn('transactionType', ['SWAPTOCEMAC', 'SWAPTOOUTCEMAC'])
            ->whereIn('status', [2, 7])
            ->where('swapDomainName', '!=', 'UAT_SERVER')
            ->orderBy('id', 'desc')
            ->get();

        if ($gimac_transaction_list->isEmpty()) {
            echo "No transaction found";
            die;
        }
        $certificate = public_path("MTN Cameroon Issuing CA1.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);

        // 1. Get Token with Try-Catch
        try {
            $tokenResponse = $client->request('POST', env('GIMAC_TOKEN_URL'), [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => '*/*',
                    'Connection' => 'keep-alive'
                ],
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => env('GIMAC_CLIENT_ID'),
                    'client_secret' => env('GIMAC_CLIENT_SECRET'),
                    'scope' => 'read',
                    'username' => env('GIMAC_USER_NAME'),
                    'password' => env('GIMAC_PASSWORD'),
                    'expires_in' => 86400,
                ],
            ]);

            $accessToken = json_decode($tokenResponse->getBody()->getContents())->access_token;
        } catch (RequestException $e) {
            Log::channel('GIMAC')->error("Token Request Failed: " . $e->getMessage());
            return; // Stop execution if token fetch fails
        }

        foreach ($gimac_transaction_list as $transaction) {
            $userCard = UserCard::where('userId', $transaction->user_id)->where('cardType', 'PHYSICAL')->first();
            if ($transaction->payment_mode == "INCOMMING PAYMENT" && in_array($transaction->paymentType, ['REQUESTTOPAY', 'WALLETTOWALLET', 'WALLETTOACCOUNT', 'WALLETINCOMMING', 'INCACCREMIT'])) {
                $senderUser = User::find($transaction->receiver_id);
            } else {
                $senderUser = User::find($transaction->user_id);
            }
            $url = env('GIMAC_PAYMENT_INQUIRY');
            $data = [
                'issuertrxref' => $transaction->issuertrxref,
            ];

            try {
                $response = $client->request('POST', $url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $accessToken
                    ],
                    'json' => $data,
                ]);

                $jsonResponse = json_decode($response->getBody()->getContents());
                Log::channel('GIMAC')->info(["Transaction Accepted External Response " => $jsonResponse]);
                $statusCode = $response->getStatusCode();

                if ($statusCode === 200 && isset($jsonResponse[0]->state)) {
                    $state = $jsonResponse[0]->state;
                    $sender_wallet_amount = $transaction->amount + $transaction->transaction_amount;

                    if ($state === 'ACCEPTED') {
                        if ($transaction->payment_mode == 'External') {
                            Transaction::where('id', $transaction->id)->update([
                                'status' => 1,
                            ]);
                            $receiverUser = User::find($transaction->receiver_id);
                            $sender_wallet_amountE = $receiverUser->wallet_balance + $transaction->amount;
                            $credit = new TransactionLedger([
                                'user_id' => $receiverUser->id,
                                'opening_balance' => $receiverUser->wallet_balance,
                                'amount' => $transaction->amount,
                                'actual_amount' => $transaction->amount,
                                'type' => 1,
                                'excelTransId' => $transaction->excel_trans_id,
                                'trans_id' => $transaction->id,
                                'payment_mode' => 'External',
                                'closing_balance' => $sender_wallet_amountE,
                                'created_at' => date(DATE_TIME_FORMAT),
                                'updated_at' => date(DATE_TIME_FORMAT),
                            ]);
                            $credit->save();
                            User::where('id', $transaction->receiver_id)->decrement('wallet_balance', $transaction->amount);
                            Log::channel('GIMAC')->info("Transaction Accepted External");
                        } else {
                            if (in_array($transaction->paymentType, ['REQUESTTOPAY', 'WALLETINCOMMING', 'INCACCREMIT'])) {
                                $newBalance = $transaction->remainingWalletBalance + $transaction->amount;
                                Transaction::where('id', $transaction->id)->update([
                                    'status' => 1,
                                    'trans_type' => 1,
                                    'remainingWalletBalance' => $newBalance
                                ]);
                                User::where('id', $senderUser->id)->increment('wallet_balance', $transaction->amount);
                                if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                                    $postData = json_encode([
                                        "currencyCode" => "XAF",
                                        "last4Digits" => $userCard->last4Digits,
                                        "referenceMemo" => "Settlement",
                                        "transferAmount" => $transaction->total_amount,
                                        "transferType" => "WalletToCard",
                                        "mobilePhoneNumber" => "241{$senderUser->phone}"
                                    ]);
                                    $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                                }
                                Log::channel('GIMAC')->info("Transaction Accepted Increment");
                            } else {
                                Transaction::where('id', $transaction->id)->update([
                                    'status' => 1,
                                    'trans_type' => 2,
                                    'remainingWalletBalance' => $senderUser->wallet_balance,
                                    'afterBalance' => $senderUser->wallet_balance,
                                    'runningBalance' => $senderUser->wallet_balance

                                ]);
                                User::where('id', $senderUser->id)->decrement('holdAmount', $sender_wallet_amount);
                                DB::table('admins')->where('id', 1)->increment('wallet_balance', $transaction->transaction_amount);
                                Log::channel('GIMAC')->info("Transaction Accepted Decrement");
                            }
                        }

                    } elseif ($state === 'REJECTED') {
                        if ($transaction->payment_mode == 'External') {
                            Transaction::where('id', $transaction->id)->update([
                                'status' => 4,
                            ]);
                        } else {
                            User::where('id', $senderUser->id)->decrement('holdAmount', $transaction->total_amount);
                            User::where('id', $senderUser->id)->increment('wallet_balance', $transaction->total_amount);
                            Transaction::where('id', $transaction->id)->update(['status' => 4]);

                            if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                                $postData = json_encode([
                                    "currencyCode" => "XAF",
                                    "last4Digits" => $userCard->last4Digits,
                                    "referenceMemo" => "Settlement",
                                    "transferAmount" => $transaction->total_amount,
                                    "transferType" => "WalletToCard",
                                    "mobilePhoneNumber" => "241{$senderUser->phone}"
                                ]);
                                $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                            }

                            Log::channel('GIMAC')->info("Transaction Rejected => " . $state);

                        }
                    }
                }
            } catch (RequestException $e) {
                // Log specific GIMAC transaction error with issuertrxref for traceability
                Log::channel('GIMAC')->error("Inquiry Failed for trxref {$transaction->issuertrxref}: " . $e->getMessage());
            }
        }
    }
}
