<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Transaction;
use App\Models\UserCard;
use App\Services\CardService;

class balanceAutoSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:balanceAutoSync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto balance sync.';

    protected $programId;
    protected $auth;
    protected $headers;

    /**
     * Constructor
     */
    public function __construct(CardService $cardService)
    {
        parent::__construct();
        $this->cardService = $cardService;
    }
    public function handle()
    {
        try {

            $userCard = UserCard::where('cardType', 'PHYSICAL')->get();

            if ($userCard->isEmpty()) {
                Log::info('No physical cards found');
            }

            foreach ($userCard as $trans) {

                $userDetail = User::where('id', $trans->userId)->first();
                $activeCard = UserCard::where('userId', $userDetail->id)->where('cardType', 'PHYSICAL')->where('cardStatus', 'Active')->first();


                $userDetail = User::find($trans->userId);
                if (!$userDetail) {
                    Log::info('User not found', ['userId' => $trans->userId]);
                    continue;
                }

                if (!$activeCard) {
                    continue;
                }

                $getCardBalance = $this->cardService->getCardBalance($trans->accountId, 'PHYSICAL');
                $wallet = $userDetail->wallet_balance ?? 0;
                $card = $getCardBalance['data']['balance'] ?? 0;

                $amount = 0;

                if ($card > $wallet) {
                    $transferType = 'CardToWallet';
                    $amount = $card - $wallet;
                } elseif ($wallet > $card) {
                    $transferType = 'WalletToCard';
                    $amount = $wallet - $card;
                } else {
                    $transferType = "";
                    continue;
                }
                if ($amount > 0) {
                    if ($transferType === "CardToWallet") {
                        Log::info(['Account Id' => $trans->accountId,'wallet' => $wallet,'card' => $card,'transferType'=>'CardToWallet']);
                        User::where('id', $trans->userId)->increment('wallet_balance', $amount);
                        Log::info(['Wallet Balance Added ' => $amount, 'userId' => $trans->userId]);
                    } else {
                        Log::info(['Account Id' => $trans->accountId,'wallet' => $wallet,'card' => $card,'transferType'=>'WalletToCard']);
                        User::where('id', $trans->userId)->decrement('wallet_balance', $amount);
                        Log::info(['Wallet Balance Deduct' => $amount, 'userId' => $trans->userId]);
                    }
                }
            }
        } catch (\Throwable $th) {
            Log::info('Wallet Balance Sync Failed: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);
        }
    }
}
