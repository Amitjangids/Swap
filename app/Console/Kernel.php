<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\updateGimacTransactionStatus::class,
        Commands\updateBdaTransactionStatus::class,
        Commands\updateCemacTransaction::class,
        Commands\updateOnafriqTransactionStatus::class,
        Commands\fetchFileFromGimac::class,
        Commands\updateKycStatus::class,
        Commands\createCardTransactionActivityNew::class,
        Commands\balanceAutoSync::class,
        Commands\updateAirtelMoneyStatus::class,
        // Commands\matchGimacTransaction::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:updateAirtelMoneyStatus')->everyTwoMinutes();
        $schedule->command('command:createCardTransactionActivityNew')->everyMinute();
        $schedule->command('command:balanceAutoSync')->everyMinute();
        $schedule->command('command:updateGimacTransactionStatus')->everyFiveMinutes();
        $schedule->command('command:updateBdaTransactionStatus')->hourly();
        $schedule->command('command:updateCemacTransaction')->everyFiveMinutes();
        $schedule->command('command:updateOnafriqTransactionStatus')->everyMinute();
        $schedule->command('command:updateKycStatus')->everyMinute();
        $schedule->command('command:matchGimacTransaction')->hourly();
        $schedule->command('sftp:updateOnafriqFileUpload')->dailyAt('23:58');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
