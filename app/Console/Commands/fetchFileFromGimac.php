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


class fetchFileFromGimac extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:fetchFileFromGimac';

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
        Log::info("Test");
        try {
            $currentDateTime = Carbon::now();
            $formattedDateTime = $currentDateTime->format('YmdHis');
            Log::info('Running fetchFileFromGimac');
            $filename = 'INCO_PSIG_14007_'.$formattedDateTime.'.XML';
            Storage::disk('sftp')->exists('/Incoming/'.$filename);
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }

        return 0;
    }
}
