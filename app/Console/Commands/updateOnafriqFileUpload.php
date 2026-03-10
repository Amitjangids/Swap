<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use App\Mail\SendMailable;
use App\Models\Transaction;
use App\Models\ExcelTransaction;
use App\Models\TransactionLedger;
use App\Models\RemittanceData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OnafriqTransaction;


class updateOnafriqFileUpload extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sftp:updateOnafriqFileUpload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload Onafriq Transaction';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
{
    // Define the file name with timestamp
    $fileName = 'SWAPGABON_' . now()->format('Y_m_d') . '_01.csv';
    
    // Use the public/uploads directory for file storage
    $filePath = storage_path('app/public/' . $fileName); // Store file in public/uploads directory

    try {
        // Export transaction data to Excel
        Excel::store(new OnafriqTransaction, 'public/' . $fileName);

        //$this->info("Transaction list generated: {$fileName}");

        // SFTP Upload
        $this->info("Uploading file to SFTP server...");
        if (Storage::disk('public')->put('/' . $fileName, file_get_contents(storage_path('app/public/' . $fileName)))) {
            //Log::info("OUR server");
            //$this->info('OUR');
            Log::channel('ONAFRIQ')->info('Uploading file to SFTP server...');
        }

        if (Storage::disk('sftp1')->put($fileName, file_get_contents($filePath))) {
            //Log::info("Client.");
            //$this->info('File uploaded "Client.');
        } else {
            Log::channel('ONAFRIQ')->info("Failed to upload to clinet SFTP server.");
        }

        // Cleanup - Optionally remove the local file after upload
        // unlink($localFilePath);

        return Command::SUCCESS;
    } catch (\Exception $e) {
        Log::channel('ONAFRIQ')->info('Error generating or uploading transaction list: ' . $e->getMessage());
        return Command::FAILURE;
    }
}

}
