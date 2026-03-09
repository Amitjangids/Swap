<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\SendMailable;
use App\Models\Transaction;
use App\Models\ExcelTransaction;
use DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
ini_set('memory_limit', '256M');
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class matchGimacTransaction extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:matchGimacTransaction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match Daily Transaction With Gimac';

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
        $currentDateTime = Carbon::now()->subDay();
        /* $lastFiveDays = Carbon::now()->subDay(5);
        $yestDate = $lastFiveDays->format('Ymd'); */

        $fromDateA = Carbon::now()->subDays(5)->toDateString();
        $toDateA = Carbon::yesterday()->toDateString();

        // Generate an array of dates between fromDateA and toDateA
        $dateRange = [];
        $currentDate = Carbon::parse($fromDateA);
        $endDate = Carbon::parse($toDateA);

        while ($currentDate->lte($endDate)) {
            $dateRange[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        $getRecordCountA = Transaction::whereIn(DB::raw('DATE(updated_at)'), $dateRange)
            ->where('is_verified_by_gimac', 0)
            ->where('transactionType', 'SWAPTOGIMAC')
            ->where('status', 1)
            ->count();


        // $getRecordCount = Transaction::whereDate('updated_at', $yestDate)->where('is_verified_by_gimac', 0)->where('transactionType', 'SWAPTOGIMAC')->count();

        if ($getRecordCountA > 0) {
            $formattedDateTime = $currentDateTime->format('Ymd');
            $filename = "INCO_PSIG_14007_{$formattedDateTime}120000.XML";
            if (Storage::disk('sftp')->exists("/$filename")) {
                $xmlString = Storage::disk('sftp')->get("/$filename");

                $xml = new \SimpleXMLElement($xmlString);

                $retrievalReferenceNumbers = [];
                foreach ($xml->Body->Association as $association) {
                    foreach ($association->Transaction as $transaction) {
                        $retrievalReferenceNumber = (string) $transaction->RetrievalReferenceNumber;
                        $retrievalReferenceNumbers[] = $retrievalReferenceNumber;
                    }
                }

                if (!empty($retrievalReferenceNumbers)) {
                    $updatedRecords = Transaction::whereIn('issuertrxref', $retrievalReferenceNumbers)->update(['is_verified_by_gimac' => '1']);
                    Log::channel('GIMAC')->info("VERIFIED RECORDS");
                    if ($updatedRecords > 0) {
                        Log::channel('GIMAC')->info("$updatedRecords records have been verified successfully by gimac");
                    } else {
                        Log::channel('GIMAC')->info("No records were updated.");
                    }
                } else {
                    Log::channel('GIMAC')->info("No record is exist");
                }
            } else {
                Log::channel('GIMAC')->info("File not found on SFTP:" . $filename);
            }
        } else {
            // Log::info("No records found.");
        }
        /*  $xmlString = File::get(public_path("/assets/gimac_xml/$filename"));

         $xml = new \SimpleXMLElement($xmlString);
         Log::info($xml->Body->Association);

         $retrievalReferenceNumbers = [];

         foreach ($xml->Body->Association as $association) {
             foreach ($association->Transaction as $transaction) {
                 $retrievalReferenceNumber = (string) $transaction->RetrievalReferenceNumber;
                 $retrievalReferenceNumbers[] = $retrievalReferenceNumber;
             }
         }

         if (!empty($retrievalReferenceNumbers)) {
             $updatedRecords = Transaction::whereIn('issuertrxref', $retrievalReferenceNumbers)->update(['is_verified_by_gimac' => '1']);
             if ($updatedRecords > 0) {
                 echo $updatedRecords . ' records have been verified successfully by gimac';
             } else {
                 echo 'No records were updated.';
             }
         } else {
             echo 'No record is exist';
         } */

    }
}
