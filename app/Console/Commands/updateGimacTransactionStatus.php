<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\SendMailable;
use App\Models\Transaction;
use App\Models\ExcelTransaction;
use App\Models\TransactionLedger;
use DB;
use GuzzleHttp\Client;
use App\User;
use Illuminate\Support\Facades\Log;

class updateGimacTransactionStatus extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateGimacTransactionStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Transaction Status Which Done By GIMAC';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Log::channel('GIMAC')->info("Test");
        $gimac_transaction_list = Transaction::whereNotNull('receiver_mobile')->where('status', 2)->where('transactionType', 'SWAPTOGIMAC')->where('user_id', '!=', 15)->get();
        
        if ($gimac_transaction_list->isEmpty()) {
            echo "No transaction found";
            die;
        }
        $certificate = public_path("MTN Cameroon Issuing CA1.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
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

        $response = $client->request('POST', env('GIMAC_TOKEN_URL'), [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => '*/*',
                'Connection' => 'keep-alive'
            ],
            'form_params' => $options['form_params'],
        ]);

        $body = $response->getBody()->getContents();
        $jsonResponse = json_decode($body);
        $accessToken = $jsonResponse->access_token;

        foreach ($gimac_transaction_list as $transaction) {
            $url = env('GIMAC_PAYMENT_INQUIRY');
            $data = [
                'issuertrxref' => $transaction->issuertrxref,
            ];

            $response = $client->request('POST', $url, [
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
                Log::channel('GIMAC')->info($jsonResponse2[0]->state);
                $state = $jsonResponse2[0]->state;
                if ($state == 'ACCEPTED') {
                    Transaction::where('id', $transaction->id)->update(['status' => 1]);
                    Log::channel('GIMAC')->info("Transaction Accepted");
                } elseif ($state == 'REJECTED') {
                    Log::channel('GIMAC')->info("Transaction Rejected => ".$state);
                    $senderUser = User::where('id', $transaction->user_id)->first();
                    $sender_wallet_amountE = $senderUser->wallet_balance + $transaction->transaction_amount;
                    if ($transaction->payment_mode == 'External') {
                        $debit = new TransactionLedger([
                            'user_id' => $senderUser->id,
                            'opening_balance' => $senderUser->wallet_balance,
                            'amount' => $transaction->amount,
                            'actual_amount' => $transaction->amount,
                            'type' => 1,
                            'trans_id' => $transaction->id,
                            'payment_mode' => 'wallet2wallet',
                            'closing_balance' => $sender_wallet_amountE,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $debit->save();

                        User::where('id', $senderUser->id)->update(['wallet_balance' => $sender_wallet_amountE]);
                        DB::table('admins')->where('id', 1)->decrement('wallet_balance', $transaction->transaction_amount);


                    } else {
                        $sender_wallet_amount = $senderUser->wallet_balance + ($transaction->amount + $transaction->transaction_amount);

                        $debit = new TransactionLedger([
                            'user_id' => $senderUser->id,
                            'opening_balance' => $senderUser->wallet_balance,
                            'amount' => $transaction->amount,
                            'actual_amount' => $transaction->amount,
                            'type' => 1,
                            'trans_id' => $transaction->id,
                            'payment_mode' => 'wallet2wallet',
                            'closing_balance' => $sender_wallet_amount,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $debit->save();

                        User::where('id', $senderUser->id)->update(['wallet_balance' => $sender_wallet_amount]);
                        DB::table('admins')->where('id', 1)->decrement('wallet_balance', $transaction->transaction_amount);

                    }

                    Transaction::where('id', $transaction->id)->update(['status' => 4]);
                    $rejectedStatus = $jsonResponse2[0]->rejectMessage;
                    ExcelTransaction::where('id', $transaction->excel_trans_id)->update(['remarks' => $rejectedStatus]);

                }
            }
        }
    }
}
