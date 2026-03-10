<?php
namespace App\Services;

use GuzzleHttp\Client;
use App\Services\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VirtualCardService extends Service
{
    public $client;

    public function __construct()
    {
        $this->client = new Client(); // initialize Guzzle client
        $this->programId = "376";
        $this->token = "YjQ4OTFkYmItMmUxYy00YTdmLWIzZTQtYmY4NjI5NTQ5Yzk3OkIpcWclak1hUSZuVGFlOWV2KlE/";
        $this->credentials = base64_encode('b4891dbb-2e1c-4a7f-b3e4-bf8629549c97' . ':' . 'B)qg%jMaQ&nTae9ev*Q?');
    }

    public function checkConnection()
    {
        $requestId = Str::random(3);
        try {
            $url = 'https://cards-sbx.onafriqservices.com/rest/api/v1/ping';
            $headers = [
                'programId' => $this->programId,
                'requestId' => $requestId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $this->credentials
            ];
            $payload = [
                'pingId' => '1'
            ];

            // Log full request details
            echo '<pre> ---- URL ---';
            print_r($url);
            echo ' ---- headers ---';
            print_r($headers);
            echo ' ---- payload ---';
            print_r($payload);

            // Send the request
            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'json' => $payload
            ]);

            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            // Log response
            Log::info('Response:', $decoded);
            print_r($body);
            exit;

            return ['status' => true, 'data' => $decoded, 'message' => 'Request successful.'];

        } catch (\Throwable $th) {
            // Log error
            Log::error('Error during checkConnection: ' . $th->getMessage());
            return ['status' => false, 'data' => null, 'message' => $th->getMessage()];
        }
    }

    public function registerVirtualCard()
    {
        $requestId = Str::random(3);
        
        // try {
            $url = 'https://cards-sbx.onafriqservices.com/rest/api/v1/accounts/virtual';  // API URL
            $headers = [
                'programId' => $this->programId,  // Program ID
                'requestId' => $requestId,  // Random Request ID
                'Content-Type' => 'application/json',  // Content Type
                'Accept' => 'application/json',  // Accept response in JSON
                'Authorization' => 'Basic ' . $this->credentials  // Basic Authorization Header
            ];
            
            $payload = [
                'accountSource' => 'ROAMWARE',
                'address1' => 'Jaipur',
                'birthDate' => '31-OCT-2000',
                'city' => 'Jaipur',
                'country' => 'US',
                'emailAddress' => 'madan@mailinator.com',
                'firstName' => 'madan',
                'idType' => '1',
                'idValue' => 'Passport',
                'lastName' => 'saini',
                'mobilePhoneNumber' => [
                    'countryCode' => '1',
                    'number' => '78945612'
                ],
                'preferredName' => 'John Doe',
                'referredBy' => '12741481',
                'stateRegion' => 'AR',
                'subCompany' => '12741481',
                'expirationDate' => 'JUN',
                'middleName' => 'm',
                'otherAccountId' => '<string>',
                'otherCompanyName' => '<string>',
                'address2' => '<string>',
                'address3' => '<string>',
                'postalCode' => '302018',
                'alternatePhoneNumber' => [
                    'countryCode' => '1',
                    'number' => '78945612'
                ],
                'return' => 'RETURNPASSCODE',
                'solId' => '123',
                'bvn' => '24928409'
            ];

            // Log full request details
            echo '<pre> ---- URL ---';
            print_r($url);
            echo ' ---- headers ---';
            print_r($headers);
            echo ' ---- payload ---';
            print_r($payload);           

            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'json' => $payload
            ]);

            $body = $response->getBody()->getContents();
            echo '<pre>';print_r($body);
            exit;
            $decoded = json_decode($body, true);

            // Log response
            Log::info('Response:', $decoded);
            // Decode the response
            $decoded = json_decode($body, true);
            echo '<pre>';print_r($response);
            echo '<pre>';print_r($body);
            exit;
            exit;

            return ['status' => true, 'data' => $decoded, 'message' => 'Request successful.'];

        // } catch (\Throwable $th) {
        //     // Log error
        //     Log::error('Error during checkConnection: ' . $th->getMessage());
        //     return ['status' => false, 'data' => null, 'message' => $th->getMessage()];
        // }
    }

}