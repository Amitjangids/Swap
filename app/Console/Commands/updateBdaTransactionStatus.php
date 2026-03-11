<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\SendMailable;
use App\Models\Transaction;
use App\Models\ExcelTransaction;
use App\Models\TransactionLedger;
use App\Models\RemittanceData;
use App\Services\ReferralService;
use App\Models\UserCard;
use DB;
use GuzzleHttp\Client;
use App\User;
use Illuminate\Support\Facades\Log;

class updateBdaTransactionStatus extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateBdaTransactionStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Transaction Status Which Done By Bda';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ReferralService $referralService)
    {
        parent::__construct();
        $this->referralService = $referralService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //$bda_transaction_list = Transaction::where('bda_status', 2)->where('transactionType', 'SWAPTOBDA')->get();
        $bda_transaction_list = Transaction::where('bda_status', 2)->where('transactionType', 'SWAPTOBDA')->get();
        if ($bda_transaction_list->isEmpty()) {
            echo "No transaction found";
            die;
        }

        $certificate = public_path("CA Bundle.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
        foreach ($bda_transaction_list as $transaction) {
            //$remittanceData =  RemittanceData::where('excel_id',$transaction->excel_trans_id)->first();

            $remittanceData = RemittanceData::where(
                $transaction->entryType == "API" ? 'trans_app_id' : 'excel_id',
                $transaction->entryType == "API" ? $transaction->id : $transaction->excel_trans_id
            )->first();
            if (isset($remittanceData->referenceLot) && $remittanceData->referenceLot != null) {
                // Log::channel('gimacLogs')->info("Server Error :", ['Lot' => $remittanceData->referenceLot, 'Excel' => $transaction->excel_trans_id, 'entryType' => $transaction->entryType, 'transaction_id' => $transaction->id]);

                /* Production Start */
                $response = $client->get(env('BDA_URL') . $remittanceData->referenceLot, [
                    'headers' => [
                        'x-api-key' => env('XAPIKEY'),
                        'x-client-id' => env('XCLIENTID'),
                    ],
                ]);
 
                $responseBody = json_decode($response->getBody(), true);
                if (isset($responseBody['statut']) && $responseBody['statut'] == 'TRAITE') {
                    $senderUser = User::where('id', $transaction->user_id)->first();
                    Log::channel('BDA')->info("Transaction ID {$remittanceData->referenceLot} updated to SUCCESS");
                    Transaction::where('id', $transaction->id)->update(['status' => 1, 'bda_status' => 0,'remainingWalletBalance'=>($senderUser->wallet_balance-$transaction->total_amount),'afterBalance' => ($senderUser->wallet_balance-$transaction->total_amount)]);
                    RemittanceData::where('excel_id', $transaction->excel_trans_id)->update(['status' => 'SUCCESS']);

                    if ($transaction->payment_mode == 'External') {
                        $receiverUser = User::find($transaction->receiver_id);
                            $sender_wallet_amount = $receiverUser->wallet_balance + $transaction->amount;
                            $credit = new TransactionLedger([
                                'user_id' => $receiverUser->id,
                                'opening_balance' => $receiverUser->wallet_balance,
                                'amount' => $transaction->amount,
                                'actual_amount' => $transaction->amount,
                                'type' => 1,
                                'excelTransId' =>$transaction->excel_trans_id,
                                'trans_id' => $transaction->id,
                                'payment_mode' => 'External',
                                'closing_balance' => $sender_wallet_amount,
                                'created_at' => date(DATE_TIME_FORMAT),
                                'updated_at' => date(DATE_TIME_FORMAT),
                            ]);
                            $credit->save();

                        User::where('id', $transaction->receiver_id)->increment('wallet_balance', $transaction->amount);
                        /* Card Payment Start */
                        $userCard = UserCard::where('userId', $receiverUser->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();
                        if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                            $postData = json_encode([
                                "currencyCode" => "XAF",
                                "last4Digits" => $userCard->last4Digits,
                                "referenceMemo" => "Settlement",
                                "transferAmount" => $transaction->amount,
                                "transferType" => "CardToWallet",
                                "mobilePhoneNumber" => "241{$receiverUser->phone}"
                            ]);
                            $this->cardService->addWalletCardTopUp($postData, $userCard->accountId, $userCard->cardType);
                        }
                        /* Card Payment End */
                        Log::info("Transaction Accepted");
                    } else {
                        User::where('id', $transaction->user_id)->decrement('holdAmount', $transaction->total_amount);
                        DB::table('admins')->where('id', 1)->increment('wallet_balance', $transaction->transaction_amount);
                    }


                    // } elseif (isset($responseBody['statut']) &&  $responseBody['statut'] == "ERRONE" || $responseBody['statut'] == "REJETE") {
                } elseif (isset($responseBody['statut']) && ($responseBody['statut'] == "ERRONE" || $responseBody['statut'] == "REJETE")) {

                    $userRec = User::where('id', $transaction->user_id)->first();

                    if ($transaction->payment_mode == 'External') {
                        
                    } else {

                        $total_amount = $transaction->amount + $transaction->transaction_amount;

                        $opening_balance_sender3 = $userRec->wallet_balance;
                        $closing_balance_sender3 = $opening_balance_sender3 + $total_amount;

                        $credit = new TransactionLedger([
                            'user_id' => $transaction->user_id,
                            'opening_balance' => $opening_balance_sender3,
                            'amount' => $transaction->amount,
                            'fees' => $transaction->transaction_amount,
                            'actual_amount' => $transaction->total_amount,
                            'type' => 1,
                            'excelTransId' => $transaction->excel_trans_id,
                            'trans_id' => $transaction->id,
                            'payment_mode' => 'wallet2wallet',
                            'closing_balance' => $closing_balance_sender3,
                            'created_at' => date(DATE_TIME_FORMAT),
                            'updated_at' => date(DATE_TIME_FORMAT),
                        ]);
                        $credit->save();

                        DB::table('users')->where('id', $transaction->user_id)->increment('wallet_balance', $transaction->total_amount);
                        DB::table('users')->where('id', $transaction->user_id)->decrement('holdAmount', $transaction->total_amount);
                    }

                    Transaction::where('id', $transaction->id)->update(['status' => 4, 'bda_status' => 0]);
                    RemittanceData::where('excel_id', $transaction->excel_trans_id)->update(['status' => 'REJETE']);
                    ExcelTransaction::where('id', $transaction->excel_trans_id)->update(['remarks' => 'REJETE']);
                } elseif (isset($responseBody['statut']) && $responseBody['statut'] == "EN_ATTENTE_REGLEMENT") {
                    if ($remittanceData->status == "EN_ATTENTE_REGLEMENT") {

                    } else {
                        RemittanceData::where('excel_id', $transaction->excel_trans_id)->update(['status' => 'EN_ATTENTE_REGLEMENT']);
                    }
                }
            }
        }
    }
}
