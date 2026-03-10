<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserCard;
use App\Services\CardService;

class BalanceAutoSync extends Command
{
    protected $signature = 'command:balanceAutoSync';
    protected $description = 'Auto balance sync.';

    protected $cardService;

    public function __construct(CardService $cardService)
    {
        parent::__construct();
        $this->cardService = $cardService;
    }

    public function handle()
    {
        try {

            $userCards = UserCard::where('cardType', 'PHYSICAL')->get();

            if ($userCards->isEmpty()) {
                Log::info('No physical cards found');
                return;
            }

            foreach ($userCards as $card) {
                $this->processCard($card);
            }

        } catch (\Throwable $th) {

            Log::error('Wallet Balance Sync Failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
        }
    }

    private function processCard($card)
    {
        $user = User::find($card->userId);

        if (!$user) {
            Log::info('User not found', ['userId' => $card->userId]);
            return;
        }

        $activeCard = UserCard::where('userId', $user->id)
            ->where('cardType', 'PHYSICAL')
            ->where('cardStatus', 'Active')
            ->first();

        if (!$activeCard) {
            return;
        }

        $cardBalanceResponse = $this->cardService->getCardBalance($card->accountId, 'PHYSICAL');

        $wallet = $user->wallet_balance ?? 0;
        $cardBalance = $cardBalanceResponse['data']['balance'] ?? 0;

        $this->syncBalance($user, $card, $wallet, $cardBalance);
    }

    private function syncBalance($user, $card, $wallet, $cardBalance)
    {
        if ($cardBalance == $wallet) {
            return;
        }

        if ($cardBalance > $wallet) {
            $amount = $cardBalance - $wallet;

            Log::info([
                'Account Id' => $card->accountId,
                'wallet' => $wallet,
                'card' => $cardBalance,
                'transferType' => 'CardToWallet'
            ]);

            User::where('id', $user->id)->increment('wallet_balance', $amount);

            Log::info([
                'Wallet Balance Added' => $amount,
                'userId' => $user->id
            ]);

            return;
        }

        $amount = $wallet - $cardBalance;

        Log::info([
            'Account Id' => $card->accountId,
            'wallet' => $wallet,
            'card' => $cardBalance,
            'transferType' => 'WalletToCard'
        ]);

        User::where('id', $user->id)->decrement('wallet_balance', $amount);

        Log::info([
            'Wallet Balance Deduct' => $amount,
            'userId' => $user->id
        ]);
    }
}