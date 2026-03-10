<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use DB;
use GuzzleHttp\Client;
use App\User;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use DateTimeZone;
use Carbon\Carbon;
use DateTime;
use App;
use App\Services\CardService;
use App\Services\FirebaseService;
use App\Models\UserCard;

class updateKycStatus extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateKycStatus';
    public $cardService;
    protected $firebaseNotificationService;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update KYC status of users and process card creation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CardService $cardService)
    {
        $this->cardService = $cardService;
        $this->firebaseNotificationService = new FirebaseService();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $getLang = DB::table('app_language')->value('lang') ?? 'en';
        App::setLocale($getLang);
        // $users = User::where("kyc_status", 'pending')->where('id',664)->orderBy('id', 'DESC')->get();
        $users = User::where("kyc_status", 'pending')->orderBy('id', 'DESC')->get();
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $currentTimestamp = $dt->format("Y-m-d\TH:i:s.v\Z");
        $api_key = SMILE_API_KEY;
        $partner_id = SMILE_PARTNER_ID;
        global $getStateId;
        $message = $currentTimestamp . $partner_id . "sid_request";
        $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));

        foreach ($users as $user) {
            $countryVal = $getStateId[$user->country] ?? 0;
            $userSlug = $user->unique_key;
            $userJobId = $user->jobId;
            $userId = $user->id;
            $curl = curl_init();
            // Log::info($userSlug);
            curl_setopt_array($curl, array(
                CURLOPT_URL => SMILE_PATH . '/job_status',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "signature": "' . $signature . '",
                    "timestamp": "' . $currentTimestamp . '",
                     "user_id": "' . $userSlug . '",
                     "job_id": "' . $userJobId . '",
                     "partner_id": "' . $partner_id . '",
                     "image_links": false,
                     "history": false
                }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $responseData = json_decode($response);
            $job_complete = $responseData->job_complete ?? "";
            $job_success = $responseData->job_success ?? "";

            if ($job_complete == 1 && $job_success == true) {
                $ibanData = DB::table('iban_generated_lists')->where('status', 'available')->first();

                if (!$ibanData) {
                    Log::info('No available IBAN');
                }


                $iban = $ibanData->iban ?? "";
                $dob = (isset($responseData->result->DOB) && $responseData->result->DOB != "Not Available") ? strtoupper(Carbon::parse($responseData->result->DOB)->format('d-M-Y')) : "01-JAN-2000";
                $dobU = (isset($responseData->result->DOB) && $responseData->result->DOB != "Not Available") ? strtoupper(Carbon::parse($responseData->result->DOB)->format('Y-m-d')) : "2000-01-01";
                User::where('id', $userId)->update(["ibanNumber" => $iban, 'kyc_status' => 'completed', 'dob' => $dobU, 'national_identity_type' => $responseData->result->IDType, 'national_identity_number' => $responseData->result->IDNumber]);
                if ($iban) {
                    DB::table('iban_generated_lists')->where('id', $ibanData->id)->update(['status' => 'assigned']);
                }
                // $preferredName = substr("{$user->name} {$user->lastName}", 0, 18);
                $preferredName = $this->formatPreferredName($user->name, $user->lastName);
                $firstName = $this->formatPreferredName($user->name, '');
                $lastName = $this->formatPreferredName($user->lastName, '');

                $postData = json_encode([
                    "accountSource" => "OTHER",
                    "address1" => $user->address1,
                    // "birthDate" => strtoupper(Carbon::parse($responseData->result->DOB)->format('d-M-Y')),
                    "birthDate" => $dob,
                    "city" => DB::table('province_city')->where('id', $user->city)->first()->name,
                    // "country" => DB::table('province_data')->where('id', $user->country)->first()->name,
                    "country" => "GA",
                    "emailAddress" => !empty($user->email) ? $user->email : "test@mailinator.com",

                    "firstName" => $firstName,
                    "idType" => "1",
                    "idValue" => $responseData->result->IDNumber,
                    "lastName" => $lastName,
                    "mobilePhoneNumber" => [
                        "countryCode" => "241",
                        "number" => $user->phone
                    ],
                    "preferredName" => $preferredName,
                    "referredBy" => ONAFRIQ_SUBCOMPANY,
                    "stateRegion" => $countryVal,
                    "subCompany" => ONAFRIQ_SUBCOMPANY,
                    "return" => "RETURNPASSCODE"
                ]);

                Log::info($postData);

                $getResponse = $this->cardService->saveCardVirtual($postData);
                Log::info($getResponse);
                if ($getResponse['status'] == true) {
                    $registrationAccountId = $getResponse['data']['registrationAccountId'] ?? 0;
                    $registrationLast4Digits = $getResponse['data']['registrationLast4Digits'] ?? "";
                    $registrationPassCode = $getResponse['data']['registrationPassCode'] ?? "";
                    User::where('id', $userId)->update(['accountId' => $registrationAccountId, 'last4Digits' => $registrationLast4Digits, 'passCode' => $registrationPassCode, 'cardType' => 'VIRTUAL']);

                    UserCard::create([
                        'userId' => $userId,
                        'accountId' => $registrationAccountId,
                        'last4Digits' => $registrationLast4Digits,
                        'passCode' => $registrationPassCode,
                        'cardType' => 'VIRTUAL'
                    ]);
                    Log::info("Card added $userId");

                    $title = __('message_app.kyc_success_title');
                    $message = __('message_app.kyc_success_message');
                    $device_token = $user->device_token;
                    $device_type = $user->device_type;

                    $data1 = [
                        'title' => $title,
                        'message' => $message,
                        'id' => "",
                        'type' => 'KYC',
                    ];

                    if ($device_type && $device_token) {
                        $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                            $device_token,
                            $title,
                            $message,
                            $data1,
                            $device_type
                        );
                    }

                    $notif = new Notification([
                        'user_id' => $user->id,
                        'notif_title' => $title,
                        'notif_body' => $message,
                        'created_at' => date(DATE_TIME_FORMAT),
                        'updated_at' => date(DATE_TIME_FORMAT),
                    ]);
                    $notif->save();

                } else {
                    Log::info("Card not added $userId");
                }

            } else if ($job_complete == 1 && $job_success == '') {
                User::where('id', $userId)->update(['kyc_status' => 'rejected']);

                $title = __('message_app.kyc_rejected_title');
                $message = __('message_app.kyc_rejected_message');
                $device_token = $user->device_token;
                $device_type = $user->device_type;

                $data1 = [
                    'title' => $title,
                    'message' => $message,
                    'id' => "",
                    'type' => 'KYC',
                ];

                if ($device_type && $device_token) {
                    $response = $this->firebaseNotificationService->sendPushNotificationToToken(
                        $device_token,
                        $title,
                        $message,
                        $data1,
                        $device_type
                    );
                }

                $notif = new Notification([
                    'user_id' => $user->id,
                    'notif_title' => $title,
                    'notif_body' => $message,
                    'created_at' => date(DATE_TIME_FORMAT),
                    'updated_at' => date(DATE_TIME_FORMAT),
                ]);
                $notif->save();

                Log::info('Kyc Rejected');
            }
        }
    }

    public function formatPreferredName($name, $lastName)
    {
        $fullName = trim($name . ' ' . $lastName);

        // Ensure UTF-8
        $fullName = mb_convert_encoding($fullName, 'UTF-8', 'auto');

        // Convert accents → ASCII
        $fullName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fullName);

        // Remove characters not allowed by regex
        $fullName = preg_replace('/[^a-zA-Z0-9\s]/', '', $fullName);

        // Normalize spaces
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

        // Enforce length
        $fullName = substr($fullName, 0, 18);
        return $fullName;
    }
}
