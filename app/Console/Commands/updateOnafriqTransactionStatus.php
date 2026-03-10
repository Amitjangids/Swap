<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\SendMailable;
use App\Models\Transaction;
use App\Models\ExcelTransaction;
use App\Models\TransactionLedger;
use App\Models\OnafriqaData;
use DB;
use GuzzleHttp\Client;
use App\User;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use DateTime;

class updateOnafriqTransactionStatus extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateOnafriqTransactionStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Transaction Status Which Done By Onafriq';

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
        // Log::channel('ONAFRIQ')->info("Test");
        $onafriq_transaction_list = Transaction::where('bda_status', 5)->where('transactionType', 'SWAPTOONAFRIQ')->get();
        if ($onafriq_transaction_list->isEmpty()) {
            echo "No transaction found";
            die;
        }

        /* $certificate = public_path("CA Bundle.crt");
        $client = new Client([
            'verify' => $certificate,
        ]); */
        foreach ($onafriq_transaction_list as $transaction) {

            $onafriqData = OnafriqaData::where(
                $transaction->entryType == "API" ? 'trans_app_id' : 'excelTransId',
                $transaction->entryType == "API" ? $transaction->id : $transaction->excel_trans_id
            )->first();

            /* Log::info("Transaction ID === {$onafriqData->transactionId} ".$transaction->id); 
            $this->info("Transaction ID === {$onafriqData->transactionId} ".$transaction->id); */

            if ($onafriqData) {
                $postData = '
                <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                    <soap:Body>
                    <ns:get_trans_status xmlns:ns="http://ws.mfsafrica.com">
                    <ns:login>
                    <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                    <ns:password>' . CORPORATEPASS . '</ns:password>
                    </ns:login>
                    <ns:trans_id>' . $onafriqData->transactionId . '</ns:trans_id>
                    </ns:get_trans_status>
                    </soap:Body>
                    </soap:Envelope>';
                $getResponse = $this->sendCurlRequest($postData, 'urn:get_trans_status');
                //Log::info("$onafriqData->transactionId");
                $xml12 = new SimpleXMLElement($getResponse);

                $xml12->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
                $xml12->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

                $namespaces2 = $xml12->getNamespaces(true);
                $axNamespace1 = '';
                foreach ($namespaces2 as $prefix1 => $namespace2) {
                    if (strpos($namespace2, 'http://mfs/xsd') !== false) {
                        $axNamespace1 = $prefix1;
                        break;
                    }
                }

                $xml12->registerXPathNamespace($axNamespace1, 'http://mfs/xsd');
                $status2 = $xml12->xpath('//' . $axNamespace1 . ':code')[0];
                $e_trans_id2 = (string) $xml12->xpath('//' . $axNamespace1 . ':e_trans_id')[0];
                $statusCode2 = (string) $status2->xpath('' . $axNamespace1 . ':status_code')[0];

                $createdAt = new DateTime($onafriqData->created_at);
                $now = new DateTime();
                $diffInMinutes = ($now->getTimestamp() - $createdAt->getTimestamp()) / 60;
                // Log::info($statusCode2.'--------'.$onafriqData->transactionId);
                // Check if it's 60 minutes or more
                Log::info($createdAt->getTimestamp());
                Log::info($now->getTimestamp());
                Log::info($statusCode2);
                Log::info($diffInMinutes);
                $userRec = User::where('id', $transaction->user_id)->first();
                if ($statusCode2 === 'MR101') {
                    //$getStatusres = $this->transCom($e_trans_id2);

                    Log::channel('ONAFRIQ')->info("Check logs direct");

                    Transaction::where('id', $transaction->id)->update(['bda_status' => 0, 'status' => 1]);

                    if ($transaction->payment_mode == 'External') {


                        $receiverUser = User::find($transaction->receiver_id);
                            $sender_wallet_amountE = $receiverUser->wallet_balance + $transaction->amount;
                            $credit = new TransactionLedger([
                                'user_id' => $receiverUser->id,
                                'opening_balance' => $receiverUser->wallet_balance,
                                'amount' => $transaction->amount,
                                'actual_amount' => $transaction->amount,
                                'type' => 1,
                                'excelTransId' =>$transaction->excel_trans_id,
                                'trans_id' => $transaction->id,
                                'payment_mode' => 'External',
                                'closing_balance' => $sender_wallet_amountE,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            $credit->save();


                        OnafriqaData::where('excelTransId', $transaction->excel_trans_id)->update(['status' => 'success']);
                        User::where('id', $transaction->receiver_id)->increment('wallet_balance', $transaction->amount);
                    } else {
                        $sender_wallet_amount = $userRec->holdAmount - $transaction->total_amount;
                        User::where('id', $transaction->user_id)->update(['holdAmount' => $sender_wallet_amount]);
                        DB::table('admins')->where('id', 1)->increment('wallet_balance', $transaction->transaction_amount);
                        OnafriqaData::where('excelTransId', $transaction->excel_trans_id)->update(['status' => 'success']);
                        Log::channel('ONAFRIQ')->info("Success");
                    }


                } elseif ($diffInMinutes > 120) {
                    if ($statusCode2 == 'MR108' || strpos($statusCode2, 'ER') !== false) {
                        $getStatusres = $this->cancelTrans($onafriqData->transactionId);
                        Log::channel('ONAFRIQ')->info("Cancel log status => " . $getStatusres);
                        if ($getStatusres == "MR109" || $getStatusres == "MR101" || $getStatusres == "ER111") {
                            $total_amount = $transaction->amount + $transaction->transaction_amount;
                            Transaction::where('id', $transaction->id)->update(['bda_status' => 0, 'status' => 3]);
                            OnafriqaData::where('excelTransId', $transaction->excel_trans_id)->update(['status' => 'cancel']);


                            if ($transaction->payment_mode == 'External') {
                            } else {

                                $opening_balance_sender2 = $userRec->wallet_balance;
                                $closing_balance_sender2 = $opening_balance_sender2 + $total_amount;

                                $credit = new TransactionLedger([
                                    'user_id' => $transaction->user_id,
                                    'opening_balance' => $opening_balance_sender2,
                                    'amount' => $transaction->amount,
                                    'fees' => $transaction->transaction_amount,
                                    'actual_amount' => $transaction->total_amount,
                                    'type' => 1,
                                    'excelTransId' => $transaction->excel_trans_id,
                                    'trans_id' => $transaction->id,
                                    'payment_mode' => 'wallet2wallet',
                                    'closing_balance' => $closing_balance_sender2,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $credit->save();

                                DB::table('users')->where('id', $transaction->user_id)->increment('wallet_balance', $total_amount);
                                DB::table('users')->where('id', $transaction->user_id)->decrement('holdAmount', $total_amount);
                            }
                        }
                    }
                } else {
                    if (strpos($statusCode2, 'ER') !== false) {
                        $getStatusres = $this->cancelTrans($onafriqData->transactionId);
                        Log::channel('ONAFRIQ')->info("Cancel log status => " . $getStatusres);
                        if ($getStatusres == "MR109" || $getStatusres == "MR101" || $getStatusres == "ER111") {
                            $total_amount = $transaction->amount + $transaction->transaction_amount;
                            Transaction::where('id', $transaction->id)->update(['bda_status' => 0, 'status' => 3]);
                            OnafriqaData::where('excelTransId', $transaction->excel_trans_id)->update(['status' => 'cancel']);


                            if ($transaction->payment_mode == 'External') {

                            } else {

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
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $credit->save();

                                DB::table('users')->where('id', $transaction->user_id)->increment('wallet_balance', $total_amount);
                                DB::table('users')->where('id', $transaction->user_id)->decrement('holdAmount', $total_amount);
                            }
                        }
                    }
                }
            }
        }
    }

    public function cancelTrans($transId)
    {
        $postData = '
            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Body>
                    <ns:cancel_trans xmlns:ns="http://ws.mfsafrica.com">
                    <ns:login>
                        <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                            <ns:password>' . CORPORATEPASS . '</ns:password>
                    </ns:login>
                    <ns:trans_id>' . $transId . '</ns:trans_id>
                    </ns:cancel_trans>
                </soap:Body>
                </soap:Envelope>';

        $getResponse = $this->sendCurlRequest($postData, 'urn:cancel_trans');
        Log::info((string) $postData);
        Log::info($getResponse);
        $xml12 = new SimpleXMLElement($getResponse);
        $xml12->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml12->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

        $namespaces2 = $xml12->getNamespaces(true);
        $axNamespace1 = '';
        foreach ($namespaces2 as $prefix1 => $namespace2) {
            if (strpos($namespace2, 'http://mfs/xsd') !== false) {
                $axNamespace1 = $prefix1;
                break;
            }
        }
        $xml12->registerXPathNamespace($axNamespace1, 'http://mfs/xsd');
        $status3 = $xml12->xpath('//' . $axNamespace1 . ':code')[0];
        $statusCode3 = (string) $status3->xpath('' . $axNamespace1 . ':status_code')[0];
        return $statusCode3;
    }
    public function transCom($transId)
    {
        $postDataTrans =
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Body>
                <ns:trans_com xmlns:ns="http://ws.mfsafrica.com">
                <ns:login>
                <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                <ns:password>' . CORPORATEPASS . '</ns:password>
                </ns:login>
                <ns:trans_id>' . $transId . '</ns:trans_id>
                </ns:trans_com>
                </soap:Body>
                </soap:Envelope>';

        $getResponseTrans = $this->sendCurlRequest($postDataTrans, 'urn:trans_com');

        $xml2 = new SimpleXMLElement($getResponseTrans);

        $xml2->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml2->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

        $namespaces2 = $xml2->getNamespaces(true);
        $axNamespace2 = '';
        foreach ($namespaces2 as $prefix2 => $namespace3) {
            if (strpos($namespace3, 'http://mfs/xsd') !== false) {
                $axNamespace2 = $prefix2;
                break;
            }
        }
        $xml2->registerXPathNamespace($axNamespace2, 'http://mfs/xsd');

        $status2 = $xml2->xpath('//' . $axNamespace2 . ':code')[0];
        $statusCode2 = (string) $status2->xpath('' . $axNamespace2 . ':status_code')[0];
        return $statusCode2;
    }

    private function sendCurlRequest($postData, $soapAction)
    {
        $this->authString = base64_encode(CORPORATECODE . ':' . CORPORATEPASS);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => APIURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ' . $soapAction,
                'Authorization: Basic ' . $this->authString,
                'Cookie: GCLB=CJHI84va1Ji4BhAD',
                'User-Agent:' . CORPORATECODE
            ),
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        } else {
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_status != 200) {
                echo "Request failed with status: " . $http_status;
            } else {
                return $response;
            }
        }
        curl_close($curl);
        /* Log::info("Transaction ID === {$statusCode2} ".$message2.' MSGG '.$diffInMinutes); 
            $this->info("Transaction ID === {$statusCode2} ".$message2.' Ms '.$diffInMinutes); */
    }
}
