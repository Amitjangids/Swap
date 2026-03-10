<?php
namespace App\Services;
use App\Services\Service;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use DB;
class SmsService extends Service
{

    public function createOneTimePasscode()
    {
        $url = SMS_URL . '/applications';
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . OTP_API_KEY,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                        'name' => '2fa test application',
                        'enabled' => true,
                        'configuration' => [
                            'pinAttempts' => 10,
                            'allowMultiplePinVerifications' => true,
                            'pinTimeToLive' => '15m',
                            'verifyPinLimit' => '1/3s',
                            'sendPinPerApplicationLimit' => '100/1d',
                            'sendPinPerPhoneNumberLimit' => '10/1d',
                        ],
                    ]);

            // Check if the request was successful
            if ($response->successful()) {

                /* $response = response()->json($response->json()); 
                return ($response instanceof \Illuminate\Http\JsonResponse) ? $response->getData(true) : []; */

                return response()->json($response->json())->getData(true);

            } else {
                return response()->json([
                    'error' => $response->status(),
                    'message' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function sendTwoFactorMessage($appId)
    {
        $url = SMS_URL . "/applications/{$appId}/messages";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . OTP_API_KEY,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                        'pinType' => 'NUMERIC',
                        'messageText' => 'Your pin is {{pin}}',
                        'pinLength' => 6,
                        'senderId' => 'ServiceSMS',
                    ]);

            // Check response
            if ($response->successful()) {
                return response()->json($response->json())->getData(true);
            } else {
                return response()->json([
                    'error' => $response->status(),
                    'message' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deliverTwoFactorPasscode($applicationId, $messageId, $to)
    {
        $url = SMS_URL . '/pin';
        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . OTP_API_KEY,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                        'applicationId' => $applicationId,
                        'messageId' => $messageId,
                        'from' => SMS_FROM_SENDER_NO,  // Change if needed
                        'to' => $to, // Recipient's phone number
                    ]);

            // Check response
            if ($response->successful()) {
                return response()->json($response->json())->getData(true);
            } else {
                return response()->json([
                    'error' => $response->status(),
                    'message' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyOtpPasscode($pinId, $pinCode)
    {
        $url = SMS_URL . "/pin/{$pinId}/verify";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'App ' . OTP_API_KEY,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                        'pin' => $pinCode
                    ]);

            // Check response
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendLoginRegisterOtp($code, $phoneNo)
    {
        // return ['status' => true];
        // Define API headers
        $headers = [
            'Authorization' => 'App ' . OTP_API_KEY,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Send SMS OTP
        /* $smsResponse = Http::withHeaders($headers)->post('https://v3ed3m.api.infobip.com/sms/2/text/advanced', [
            'messages' => [
                [
                    'destinations' => [['to' => "241$phoneNo"]], //61006048
                    'from' => SMS_FROM_SENDER_NO,
                    'text' => "Your verification code is : $code",
                ],
            ],
        ]);

        if (!$smsResponse->successful()) {
            return [
                'status' => false,
                'message' => 'Failed to send SMS OTP',
                'error' => $smsResponse->json(),
            ];
        } */

        // Send WhatsApp OTP
        $whatsappResponse = Http::withHeaders($headers)->post('https://v3ed3m.api.infobip.com/whatsapp/1/message/template', [
            'messages' => [
                [
                    'from' => '447860028687', 
                    'to' => "241$phoneNo",
                    'messageId' => Str::uuid(),
                    'content' => [
                        'templateName' => 'swap_otp_verification',
                        'templateData' => [
                            'body' => ['placeholders' => [$code]],
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'parameter' => $code
                                ]
                            ]
                        ],
                        'language' => 'fr',
                    ],
                ],
            ],
        ]);


        /* {"messages":[{"from":"","to":"","content":{"templateName":"swap_otp_verification","templateData":{"body":{"placeholders":[]},"buttons":[{"type":"URL","parameter":""}]},"language":"fr"}}]} */

        if (!$whatsappResponse->successful()) {
            return [
                'status' => false,
                'message' => 'Failed to send WhatsApp OTP',
                'error' => $whatsappResponse->json(),
            ];
        }


        $existingTmpUser = DB::table('tempuser')->where('phone', $phoneNo)->first();

        if (isset($existingTmpUser) && !empty($existingTmpUser)) {
            DB::table('tempuser')->where('phone', $phoneNo)->update([
                'otpCode' => $code,
                'updated_at' => now(),
            ]);
        } else {

            DB::table('tempuser')->insert([
                'phone' => $phoneNo,
                'otpCode' => $code,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['status' => true];

        /* $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://v3ed3m.api.infobip.com/whatsapp/1/message/text',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "{\"from\":\"441134960000\",\"to\":\"447860099299\",\"messageId\":\"a28dd97c-1ffb-4fcf-99f1-0b557ed381da\",\"content\":{\"text\":\"Your verification code is : $code\"},\"callbackData\":\"Callback data\"}",
            CURLOPT_HTTPHEADER => array(
                'Authorization: App ' . OTP_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ]);

        $response = curl_exec($curl);

        curl_close($curl);  */
    }


}