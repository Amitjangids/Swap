<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Transaction;
use App\Models\UserCard;

class createCardTransactionActivityNew extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:createCardTransactionActivityNew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and insert card transaction activity from Onafriq daily.';

    protected $programId;
    protected $auth;
    protected $headers;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    /**
     * Build dynamic headers for Onafriq API calls
     */
    protected function buildHeaders($requestId, $type)
    {

        return array_merge($this->headers, [
            'programId: ' . ONAFRIQ_PROGRAMID,
            'requestId: ' . $requestId,
            'Authorization: ' . ONAFRIQ_AUTH,
        ]);
    }

    /**
     * Generate unique request ID
     */
    protected function generateRequestId()
    {
        return uniqid('req_', true);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $currentDate = strtoupper(now()->format('d-M-Y'));

            // $users = User::whereNotNull('accountId')->get();

            /* $users = User::whereNotNull('accountId')
                ->where('created_at', '>=', '2025-12-14 00:00:00')
                ->orderBy('created_at')
                ->get(); */

            /* $transactionsData = Transaction::whereIn('transactionType', ['CARDPAYMENT', 'TRANSAFEROUT'])->where('status',2)
                ->where('created_at', '>=', '2025-12-14 00:00:00')
                ->orderBy('created_at')
                ->get(); */

            $transactionsData = UserCard::where('created_at', '>=', '2025-12-14 00:00:00')
                ->orderBy('created_at')
                ->get();

            if ($transactionsData->isEmpty()) {
                // Log::info('No users found with Onafriq account IDs.');
            }


            foreach ($transactionsData as $trans) {
                $accountId = $trans->accountId;
                $type = $trans->cardType;
                $requestId = $this->generateRequestId();

                $headers = $this->buildHeaders($requestId, $type);
                $query = http_build_query([
                    'StartDate' => $currentDate,
                    'EndDate' => $currentDate, 
                    'ExtendedData' => "true",

                ]);

                $url = ONAFRIQ_CARD_URL . "/api/v1/accounts/{$accountId}/transactions?" . $query;

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => $headers,
                ]);

                $response = curl_exec($curl);

                if (curl_errno($curl)) {
                    curl_close($curl);
                    continue;
                }

                curl_close($curl);

                $data = json_decode($response, true);

                if (!isset($data['transactionActivities'])) {
                    continue;
                }

                $transactions = $data['transactionActivities'];
                $inserted = 0;

                foreach ($transactions as $txn) {

                    $existData = DB::table('transactions')
                        ->where('id', $txn['referenceInformation'])
                        ->exists();
                    $userCard = UserCard::where('accountId', $accountId)->first();
                    if ($existData) {

                        DB::table('transactions')
                            ->where('id', $txn['referenceInformation'])
                            ->update([
                                'user_id' => $trans->userId,
                                'transactionId' => $txn['transactionId'] ?? null,
                                'accountId' => $accountId,
                                'transactionDate' => $txn['transactionDate'] ?? null,
                                'transactionTime' => $txn['transactionTime'] ?? null,
                                'amount' => abs($txn['baseAmount'] ?? 0),
                                'amount_value' => abs(($txn['baseAmount'] ?? 0) + ($txn['fee'] ?? 0)),
                                'transaction_amount' => abs($txn['fee'] ?? 0),
                                'total_amount' => abs($txn['totalAmount'] ?? 0),
                                'runningBalance' => $txn['runningBalance'] ?? 0,
                                'description' => $txn['transactionDesc'] ?? null,
                                'referenceInformation' => $txn['referenceInformation'] ?? null,
                                'merchantName' => $txn['extendedInformation']['merchantName'] ?? null,
                                'merchantID' => $txn['extendedInformation']['merchantID'] ?? null,
                                'merchantCountry' => $txn['merchantCountry'] ?? null,
                                'status' => 1,
                                'cardType' => $userCard->cardType,
                                'transactionType' => $txn['baseAmount'] > 0 ? "CARDPAYMENT" : "TRANSAFEROUT",
                                'entryType' => "API",
                                'payment_mode' => $txn['baseAmount'] > 0 ? "CARDPAYMENT" : "TRANSAFEROUT",
                                'refrence_id' => $txn['transactionId'],
                                'updated_at' => now(),
                            ]);
                    }

                    $exists = DB::table('transactions')
                        ->where('transactionId', $txn['transactionId'])
                        ->where('accountId', $accountId)
                        ->exists();
                    $previous = DB::table('transactions')
                        ->where('accountId', $accountId)->where('status', 1)
                        ->orderBy('id', 'desc')
                        ->first();

                    $prevBalance = (isset($previous) && isset($previous->runningBalance))
                        ? $previous->runningBalance
                        : 0;

                    $txnAmount = abs($txn['baseAmount'] + $txn['fee']);


                    if (!$exists) {
                        if ($txn['referenceInformation'] !== "Settlement" && $txn['referenceInformation'] !== "Auto Balance Sync") {
                            DB::table('transactions')->insert([
                                'user_id' => $trans->userId,
                                'transactionId' => $txn['transactionId'],
                                'accountId' => $accountId,
                                'transactionDate' => $txn['transactionDate'] ?? null,
                                'transactionTime' => $txn['transactionTime'] ?? null,
                                'amount' => abs($txn['baseAmount']) ?? 0,
                                'amount_value' => abs($txn['baseAmount'] + $txn['fee']) ?? 0,
                                'transaction_amount' => abs($txn['fee']) ?? 0,
                                'total_amount' => abs($txn['totalAmount']) ?? 0,
                                'runningBalance' => $txn['runningBalance'] ?? 0,
                                'description' => $txn['transactionDesc'] ?? null,
                                'referenceInformation' => $txn['referenceInformation'] ?? null,
                                'merchantName' => $txn['extendedInformation']['merchantName'] ?? null,
                                'merchantID' => $txn['extendedInformation']['merchantID'] ?? null,
                                'merchantCountry' => $txn['merchantCountry'] ?? null,
                                'status' => 1,
                                'trans_type' => ($txn['baseAmount'] > 0 ? 1 : 2),
                                'transactionType' => $txn['baseAmount'] > 0 ? "CARDPAYMENT" : "TRANSAFEROUT",
                                'entryType' => "API",
                                'beforeVirtualBalance' => ($userCard->cardType === "VIRTUAL" && isset($prevBalance)) ? $prevBalance : 0,
                                'afterVirtualBalance' => $userCard->cardType === "VIRTUAL" ? ($txn['baseAmount'] > 0 ? $prevBalance + $txnAmount : $prevBalance - $txnAmount) : 0,
                                'beforeBalance' => $userCard->cardType === "PHYSICAL" ? $prevBalance : 0,
                                'afterBalance' => $userCard->cardType === "PHYSICAL" ? ($txn['baseAmount'] > 0 ? $prevBalance + $txnAmount : $prevBalance - $txnAmount) : 0,
                                'payment_mode' => $txn['baseAmount'] > 0 ? "CARDPAYMENT" : "TRANSAFEROUT",
                                'refrence_id' => $txn['transactionId'],
                                'cardType' => $userCard->cardType,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            if (isset($userCard) && $userCard->cardType == "PHYSICAL" && $userCard->cardStatus == "Active") {
                                if ($txn['baseAmount'] > 0) {
                                    Log::info(['Increment' => abs($txn['baseAmount'] + $txn['fee'])]);
                                    User::where('id', $trans->userId)->increment('wallet_balance', abs($txn['baseAmount'] + $txn['fee']) ?? 0);
                                } else {
                                    Log::info(['Decrement' => abs($txn['baseAmount'] + $txn['fee'])]);
                                    User::where('id', $trans->userId)->decrement('wallet_balance', abs($txn['baseAmount'] + $txn['fee']) ?? 0);
                                }
                            }
                            $inserted++;
                        }
                    }
                }

            }
        } catch (\Throwable $th) {
            Log::info('Onafriq transaction sync failed: ' . $th->getMessage());
        }
    }
}
