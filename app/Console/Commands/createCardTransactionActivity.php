<?php

namespace App\Console\Commands;

use App\Models\UserCard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User; 

class createCardTransactionActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:createCardTransactionActivity';

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

            // $users = User::whereNotNull('accountId')->where('accountId','24602399')->get();
            $users = UserCard::where('cardType','PHYSICAL')->where('created_at', '>=', '2025-12-14 00:00:00')
                ->orderBy('created_at')
                ->get();


            if ($users->isEmpty()) {
                // Log::info('No users found with Onafriq account IDs.');
            }


            foreach ($users as $user) {
                $accountId = $user->accountId;
                $type = $user->cardType;
                $requestId = $this->generateRequestId();

                $headers = $this->buildHeaders($requestId, $type);
                $query = http_build_query([
                    'StartDate' => "13-JAN-2026",
                    'EndDate' => '13-JAN-2026',
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

                // $data = json_decode('{"transactionActivities": [{"transactionId": 342671463,"transactionDate": "17-JAN-2024","transactionTime": "17:38:55","transactionTimeHH24": "17:38:55","baseAmount": 1000,"fee": 100.99,"totalAmount": 1100.99,"runningBalance": 2000,"transactionDesc": "Purchase","referenceInformation": "string","merchantCountry": "string","extendedInformation": {"terminalID": "string","acquirerBin": 800001,"merchantName": "string","merchantID": "string","mccCode": 5732,"merchantCity": "string","posEntryCode": 81,"cardholderIdMethod": 0,"externalReferenceNumber": "string","currencyMarkupPercent": 0,"baseIIStatus": "string","transCurrencyCode": 840,"cashbackTransAmount": 0,"cashbackBillingAmount": 0,"partialTransAmount": 0,"partialBillingAmount": 0}}]}', true);

                if (!isset($data['transactionActivities'])) {
                    // Log::info("No transactions found for account {$accountId} on {$currentDate}");
                    continue;
                }

                $transactions = $data['transactionActivities'];
                $inserted = 0;

                foreach ($transactions as $txn) {

                    Log::info(["transaction" => $txn]);
                     
                }

            }
        } catch (\Throwable $th) {
            Log::info('Onafriq transaction sync failed: ' . $th->getMessage());
        }
    }
}
