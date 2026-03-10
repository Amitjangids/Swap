<?php
namespace App\Services;
use App\Models\ReferralSetting;
use App\Models\Transaction;
use App\Models\User;
use DB;
use GuzzleHttp\Client;
use App\Services\Service;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ReferralService extends Service
{

    public function __construct()
    {
        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }
    public function referralFeeAmount($user, $total_fees, $transType)
    {
        try {
            $refSetting = ReferralSetting::where('type', $transType)->first();

            /* $commission = ($refSetting->fee_type === '0')
                ? ($total_fees * $refSetting->fee_value / 100)
                : $refSetting->fee_value; */
                
            $commission = ($total_fees * $refSetting->fee_value / 100) ;


            if (!empty($user->referralBy)) {
                $referralByUser = User::find($user->referralBy);
                if ($referralByUser) {
                    $referralFeeAmount = $commission;
                    $total_fees = $total_fees - $referralFeeAmount;
                    $referralByUserId = $referralByUser->id;
                }
            }
            $data = [
                'referralPercentage' => $refSetting->fee_value,
                'referralFeeAmount' => $referralFeeAmount,
                'total_fees' => $total_fees,
                'referralByUserId' => $referralByUserId
            ];

            return ['status' => false, 'message' => "Fatched data", 'data' => $data];
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }
    public function addReferralAmountByPayment($user, $userId, $amount, $transactionId)
    {
        try {
            if ($user->referralBy) {
                $referralFeeAmount = round($amount, 2);
                if ($referralFeeAmount > 0) {
                    $transRef = new Transaction([
                        'user_id' => 0,
                        'receiver_id' => $userId,
                        'receiver_mobile' => '',
                        'amount' => $amount,
                        'amount_value' => $amount,
                        'transaction_amount' => 0,
                        'total_amount' => $amount,
                        'trans_type' => 1,
                        'excel_trans_id' => 0,
                        'payment_mode' => 'Referral',
                        'status' => 1,
                        'refrence_id' => '',
                        'billing_description' => "Fund Transfer-" . time() . rand(),
                        'onafriq_bda_ids' => 0,
                        'transactionType' => '',
                        'entryType' => 'API',
                        'referralBy' => $transactionId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $transRef->save();
                }
            }
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }
}


