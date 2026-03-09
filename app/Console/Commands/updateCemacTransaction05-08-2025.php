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
use GuzzleHttp\Exception\RequestException;
use App\User;
use Illuminate\Support\Facades\Log;

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
        $gimac_transaction_list = Transaction::whereIn('transactionType', ['SWAPTOCEMAC', 'SWAPTOOUTCEMAC'])
            ->where('status', 2)
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
            $tokenResponse = $client->request('POST', env('GIMAC_TOKEN_URL_TEST'), [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => '*/*',
                    'Connection' => 'keep-alive'
                ],
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => env('GIMAC_CLIENT_ID_TEST'),
                    'client_secret' => env('GIMAC_CLIENT_SECRET_TEST'),
                    'scope' => 'read',
                    'username' => env('GIMAC_USER_NAME_TEST'),
                    'password' => env('GIMAC_PASSWORD_TEST'),
                    'expires_in' => 86400,
                ],
            ]);

            $accessToken = json_decode($tokenResponse->getBody()->getContents())->access_token;
        } catch (RequestException $e) {
            Log::channel('GIMAC')->error("Token Request Failed: " . $e->getMessage());
            return; // Stop execution if token fetch fails
        }

        foreach ($gimac_transaction_list as $transaction) {
            $senderUser = User::find($transaction->user_id);
            $url = env('GIMAC_PAYMENT_INQUIRY_TEST');
            $data = [
                'issuertrxref' => $transaction->issuertrxref,
            ];

            // 2. Inquiry API with Try-Catch
            try {
                $response = $client->request('POST', $url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $accessToken
                    ],
                    'json' => $data,
                ]);

                $jsonResponse = json_decode($response->getBody()->getContents());
                $statusCode = $response->getStatusCode();

                if ($statusCode === 200 && isset($jsonResponse[0]->state)) {
                    $state = $jsonResponse[0]->state;
                    $sender_wallet_amount = $transaction->amount + $transaction->transaction_amount;

                    if ($state === 'ACCEPTED') {
                        if (in_array($transaction->paymentType, ['REQUESTTOPAY', 'WALLETINCOMMING'])) {
                            $newBalance = $transaction->remainingWalletBalance + $transaction->amount;
                            Transaction::where('id', $transaction->id)->update([
                                'status' => 1,
                                'trans_type' => 1,
                                'remainingWalletBalance' => $newBalance
                            ]);
                            User::where('id', $senderUser->id)->increment('wallet_balance', $transaction->amount);
                        } else {
                            Transaction::where('id', $transaction->id)->update([
                                'status' => 1,
                                'trans_type' => 2
                            ]);
                            Log::channel('GIMAC')->info("Transaction Accepted");
                            User::where('id', $senderUser->id)->decrement('holdAmount', $sender_wallet_amount);
                            DB::table('admins')->where('id', 1)->increment('wallet_balance', $transaction->transaction_amount);
                        }
                    } elseif ($state === 'REJECTED') {
                        Log::channel('GIMAC')->info("Transaction Rejected => " . $state);
                        Transaction::where('id', $transaction->id)->update(['status' => 4]);
                    }
                }
            } catch (RequestException $e) {
                // Log specific GIMAC transaction error with issuertrxref for traceability
                Log::channel('GIMAC')->error("Inquiry Failed for trxref {$transaction->issuertrxref}: " . $e->getMessage());
            }
        }
    }
}
