<?php
namespace App\Services;
use DB;
use GuzzleHttp\Client;
use App\Services\Service;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CardService extends Service
{

    public function __construct()
    {
        $this->programId = ONAFRIQ_PROGRAMID;
        $this->auth = ONAFRIQ_AUTH;
        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    protected function buildHeaders($requestId, $type)
    {
        if ($type == 'PHYSICAL') {
            $this->programId = ONAFRIQ_PROGRAMID_PHY;
        }
        if ($type == 'VIRTUAL') {
            $this->programId = ONAFRIQ_PROGRAMID;
        }
        return array_merge($this->headers, [
            'programId: ' . $this->programId,
            'requestId: ' . $requestId,
            'Authorization: ' . $this->auth,
        ]);
    }

    function generateRequestId()
    {
        return uniqid('req_', true); // e.g. req_652f65dfb5c892.86739077
    }

    public function saveCardVirtual($postData)
    {
        try {
            $accountRequestId = $this->generateRequestId();
            // Define headers
            $headers1 = [
                'programId:' . ONAFRIQ_PROGRAMID,
                'requestId: ' . $accountRequestId,
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization:' . ONAFRIQ_AUTH
            ];

            $curl = curl_init();


            curl_setopt_array($curl, array(
                CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/virtual',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => $headers1,
            ));

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                return ["status" => false, "message" => curl_error($curl), "data" => null];
            } else {
                $data = json_decode($response, true);
                return ["status" => true, "message" => "success", "data" => $data];
            }
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }

    public function saveCardPhysical($postData)
    {
        try {
            $requestId = $this->generateRequestId();
            $headers = $this->buildHeaders($requestId, 'PHYSICAL');

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/instant',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30, // ⏱ recommended timeout
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => $headers,
            ]);

            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                $error = curl_error($curl);
                curl_close($curl);

                return [
                    'status' => false,
                    'message' => "cURL error: $error",
                    'data' => null,
                ];
            }
            curl_close($curl);
            $data = json_decode($response, true);
            Log::info($data);

            if (!empty($data['registrationAccountId']) && !empty($data['registrationLast4Digits'])) {
                return [
                    'status' => true,
                    'message' => 'Card created successfully',
                    'data' => $data,
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Card creation failed at API level',
                    'data' => $data,
                ];
            }

        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => 'Exception: ' . $th->getMessage(),
                'data' => null,
            ];
        }
    }

    public function sendCardRequest($type, $accountId, $pin, $method)
    {
        try {
            // dd($type, $accountId, $pin, $method);
            $requestId = $this->generateRequestId();
            $headers = $this->buildHeaders($requestId, 'PHYSICAL');

            if ($type == "CHANGEPIN") {
                $url = ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '/pin-change';
            } else {
                $url = ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '/pin-check';
            }
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_POSTFIELDS => json_encode($pin),
                CURLOPT_HTTPHEADER => $headers
            ]);


            Log::info(['Pin change request'=>$type, "accountId"=>$accountId, 'pin'=>$pin, 'method'=>$method]);
            $response = curl_exec($curl);
            Log::info(['Pin change response'=>$response]);
            if (curl_errno($curl)) {
                return ["status" => false, "message" => curl_error($curl), "data" => null];
            }
            curl_close($curl);

            $data = json_decode($response, true);
            return ["status" => true, "message" => "Success", "data" => $data];
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }
    public function replaceCardService($postData, $accountId)
    {
        // try {
        $requestId = $this->generateRequestId();
        $headers = $this->buildHeaders($requestId, 'PHYSICAL');

        $getCustomerData = $this->getCustomerData($accountId, 'PHYSICAL');
        if (
            !isset($getCustomerData['data']) ||
            !isset($getCustomerData['data']['cardStatus']) ||
            !in_array($getCustomerData['data']['cardStatus'], ['LC', 'SC', 'EX', 'IA'])
        ) {
            return [
                "status" => false,
                "message" => "You are not allowed to replace your card.",
                "data" => null
            ];
        }


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '/replace-card',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);

            return [
                'status' => false,
                'message' => "cURL error: $error",
                'data' => null,
            ];
        }
        curl_close($curl);
        $data = json_decode($response, true);
        Log::info('Replace card');
        Log::info($data);
        if (!empty($data['registrationAccountId']) && !empty($data['registrationLast4Digits'])) {
            return [
                'status' => true,
                'message' => 'Card replace successfully',
                'data' => $data,
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Card creation failed',
                'data' => $data,
            ];
        }

        /* } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => 'Exception: ' . $th->getMessage(),
                'data' => null,
            ];
        } */
    }

    public function addWalletCardTopUp($postData, $accountId, $type)
    {
        // try {
        $requestId = $this->generateRequestId();
        $headers = $this->buildHeaders($requestId, $type);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '/transactions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            Log::info(["cURL error " => $error]);
            return [
                'status' => false,
                'message' => "cURL error: $error",
                'data' => null,
            ];
        }
        curl_close($curl);
        $data = json_decode($response, true);
        Log::info(["accountId"=>$accountId,"Wallet add amount response " => $data]);
        if (!empty($data['transactionId'])) {
            return [
                'status' => true,
                'message' => 'Amount added successfully',
                'data' => $data,
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Amount added failed',
                'data' => $data,
            ];
        }

        /* } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => 'Exception: ' . $th->getMessage(),
                'data' => null,
            ];
        } */
    }

    public function getCustomerData($accountId, $type)
    {
        try {
            $requestId = $this->generateRequestId();
            $headers = $this->buildHeaders($requestId, $type);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '?columnSet=customerData',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                return ["status" => false, "message" => curl_error($curl), "data" => null];
            } else {
                $data = json_decode($response, true);
                return ["status" => true, "message" => "success", "data" => $data];
            }
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }

    public function cardLockUnlock($postData, $accountId, $type)
    {
        try {
            $requestId = $this->generateRequestId();
            $headers = $this->buildHeaders($requestId, $type);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '/status',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);
            Log::info(['accountId'=>$accountId]);
            if (curl_errno($curl)) {
                return ["status" => false, "message" => curl_error($curl), "data" => null];
            } else {
                Log::info("Card Lock And Unlock",['Account Id'=>$accountId,'Response'=>$response]);
                $data = json_decode($response, true);
                return ["status" => true, "message" => "success", "data" => $data];
            }
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }


    public function getCardBalance($accountId, $type)
    {
        try {
            $requestId = $this->generateRequestId();
            $headers = $this->buildHeaders($requestId, $type);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '/balance',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                return ["status" => false, "message" => curl_error($curl), "data" => null];
            } else {
                $data = json_decode($response, true);
                return ["status" => true, "message" => "success", "data" => $data];
            }
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }

    public function cardDeactivate($postData, $accountId, $type)
    {
        try {
            $requestId = $this->generateRequestId();
            $headers = $this->buildHeaders($requestId, $type);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => ONAFRIQ_CARD_URL . '/api/v1/accounts/' . $accountId . '/status',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                return ["status" => false, "message" => curl_error($curl), "data" => null];
            } else {
                $data = json_decode($response, true);
                return ["status" => true, "message" => "success", "data" => $data];
            }
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }


    /* public function cardAndWalletBalanceUpdate($amountType,$amount)
    {
        $userId = Auth::user()->id;
        $user = User::where('id', $userId)->first();
        $getCard = UserCard::where('userId', $userId)->where('cardType', 'VIRTUAL')->first();
        if (!$getCard) {
            $response = [
                "status" => "Failed",
                "reason" => "Physical card not found.",
            ];
            $encryptedResponse = $this->encryptContent(json_encode($response));
            return response()->json($encryptedResponse, 200);
        } else if ($getCard->cardStatus == "Inactive") {
            $response = [
                "status" => "Failed",
                "reason" => "Physical card not active.",
            ];
            $encryptedResponse = $this->encryptContent(json_encode($response));
            return response()->json($encryptedResponse, 200);
        } else if ($getCard->cardStatus == "Active") {
            $postData = json_encode([
                "currencyCode" => "XAF",
                "last4Digits" => $user->last4Digits,
                "referenceMemo" => 'Settlement',
                "transferAmount" => $amount,
                "transferType" => $amountType === "CREDIT" ? "WalletToCard" : "CardToWallet",
                "mobilePhoneNumber" => "241{$user->phone}",
            ]);
            $this->addWalletCardTopUp($postData, $getCard->accountId, type: $getCard->cardType);
        }

    } */
}


