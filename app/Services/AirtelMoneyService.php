<?php
namespace App\Services;
use DB;
use GuzzleHttp\Client;
use App\Services\Service;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AirtelMoneyService extends Service
{
    public function __construct()
    {
        /* $this->url = env('AIRTEL_PAYMENT_URL');
        $this->clientId = env('CLIENT_ID');
        $this->clientSecret = env('CLIENT_SECRET');
        $this->headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ]; */
    }

    protected function generateAccessToken()
    {
        $payload = [
            'client_id' => env('CLIENT_ID'),
            'client_secret' => env('CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => env('AIRTEL_PAYMENT_URL') . '/auth/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: */*',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $token = json_decode($response, true)['access_token'];

        if (!$token) {
            return ["status" => false, "message" => "Failed to get access token", "data" => null];
        }
        return $token;
    }

    public function requestPayment($postData)
    {
        try {
            /* $encrypted = $this->encryptPayload($postData);
            $signature = $encrypted['signature'];
            $key = $encrypted['key']; */
            $accessToken = $this->generateAccessToken();
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => env('AIRTEL_PAYMENT_URL') . '/merchant/v1/payments/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => array(
                    'Accept: */*',
                    'Content-Type: application/json',
                    'X-Country: ' . $postData['transaction']['country'],
                    'X-Currency: ' . $postData['transaction']['currency'],
                    'Authorization: Bearer ' . $accessToken,
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            $data = json_decode($response, true);
            return ["status" => true, "message" => "success", "data" => $data];

        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }

    /* private function encryptPayload($data)
    { 
        $key = base64_encode('fake-encryption-key'); // placeholder
        $signature = base64_encode(hash('sha256', json_encode($data))); // example only
        return [
            'key' => $key,
            'signature' => $signature,
        ];
    } */
    public function transactionEnquiry($postData)
    {
        try {
            $accessToken = $this->generateAccessToken();
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => env('AIRTEL_PAYMENT_URL') . '/standard/v1/payments/' . $postData['airtelTransId'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => "",
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-Country: GA',
                    'X-Currency: CFA',
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $data = json_decode($response, true);

            return [
                'status' => isset($data['status']['success']) ? $data['status']['success'] : false,
                'message' => $data['status']['message'] ?? 'Unknown response',
                'data' => $data
            ];
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }

    public function refundPayment($postData)
    {
        /* try {
            $accessToken = $this->generateAccessToken();

            $payload = json_encode([
                "transaction" => [
                    "airtel_money_id" => $postData['transaction']['airtel_money_id'],
                ],
            ]);

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => env('AIRTEL_PAYMENT_URL') .'/standard/v1/payments/refund',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-Country: '.$postData['transCountry'],
                    'X-Currency: '.$postData['transCurrency'],
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $data = json_decode($response, true);
            dd($data);
            return [
                'status' => isset($data['status']['success']) ? $data['status']['success'] : false,
                'message' => $data['status']['message'] ?? 'Unknown response',
                'data' => $data
            ];
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        } */

        try {
            $accessToken = $this->generateAccessToken();
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => env('AIRTEL_PAYMENT_URL') . '/standard/v1/payments/' . $postData['transaction']['airtel_money_id'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => "",
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-Country: ' . $postData['transCountry'],
                    'X-Currency: ' . $postData['transCurrency'],
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            $data = json_decode($response, true);

            return [
                'status' => isset($data['status']['success']) ? $data['status']['success'] : false,
                'message' => $data['status']['message'] ?? 'Unknown response',
                'data' => $data
            ];
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }

    public function transPaymentSummary($postData)
    {
        try {

            $accessToken = $this->generateAccessToken();

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env('AIRTEL_PAYMENT_URL') . '/merchant/v1/transactions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    "Authorization: Bearer $accessToken",
                    'x-country: GA',
                    'x-currency: CFA',
                ),
            ));

            $response = curl_exec($curl);

            dd($response);
            curl_close($curl);
            $data = json_decode($response, true);
            return ["status" => true, "message" => "success", "data" => $data];
        } catch (\Throwable $th) {
            return ['status' => false, 'message' => $th->getMessage(), 'data' => null];
        }
    }

}


