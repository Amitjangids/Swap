<?php

namespace App\Http\Controllers;

use App\Models\DriverActivationCard;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\User;
use Session;
use App\Models\Admin;
use App\Models\Walkthrough;
use App\Models\Banner;
use App\Models\Card;
use App\Models\Carddetail;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Scratchcard;
use App\Models\Notification;
use App\Models\Agentoffer;
use App\Models\Offer;
use App\Models\Transactionfee;
use App\Models\Usertransactionfee;
use App\Models\Order;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Feature;
use App\Models\Userfeature;
use App\Models\FeeApply;
use App\Models\Errorrecords;
use App\Models\GeneratedQrCode;
use App\Models\WalletManager;
use App\Models\TransactionLimit;
use App\Models\TransactionLedger;
use App\Models\RemittanceData;
use App\Models\OnafriqaData;
use App\Models\ExcelTransaction;
use App\Models\CardContent;
use App\Models\Iban;
use App\Models\HelpTicket;
use App\Models\UserCard;
use App\Walletlimit;
use DB;
use Input;
use Validator;
use App;
use Illuminate\Support\Facades\Artisan;
use PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use ZipArchive;
use GuzzleHttp\Client;
use DateTime;
use App\Models\Issuertrxref;
use Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use SimpleXMLElement;
use App\Services\SmsService;
use App\Services\GimacApiService;
use App\Services\CardService;
use App\Services\AirtelMoneyService;
use App\Services\ReferralService;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use DateTimeZone;
use DateInterval;
use App\Models\CardRequest;


class ApiController extends Controller
{

    private $apiUrl = APIURL;
    private $authString;
    public $smsService;
    public $gimacApiService;
    public $cardService;
    public $airtelMoneyService;
    protected $firebaseNotificationService;
    public function __construct(SmsService $smsService, GimacApiService $gimacApiService, CardService $cardService, ReferralService $referralService, AirtelMoneyService $airtelMoneyService)
    {
        $this->authString = base64_encode(CORPORATECODE . ':' . CORPORATEPASS);
        $this->smsService = $smsService;
        $this->gimacApiService = $gimacApiService;
        $this->cardService = $cardService;
        $this->airtelMoneyService = $airtelMoneyService;
        $this->referralService = $referralService;
        $this->firebaseNotificationService = new FirebaseService();
        $this->lang = DB::table('app_language')->value('lang') ?? 'en';
        App::setLocale($this->lang);
    }

    private function encryptContent($content)
    {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $cipher = $encryption->encryptPlainTextWithRandomIV(
            $content,
            $secretyKey
        );
        return $cipher;
    }

    private function decryptContent($content)
    {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $plainText = $encryption->decryptCipherTextWithRandomIV(
            $content,
            $secretyKey
        );
        $plainText = $plainText . PHP_EOL;

        return json_decode($plainText);
    }

    public function ecobankLocationList(Request $request)
    {
        $requestData = $this->decryptContent($request->req);
        $page = $requestData->page ?? 1;
        $limit = $requestData->limit ?? 10;
        $search = $requestData->search ?? '';

        $offset = ($page - 1) * $limit;

        $query = DB::table('ecobank_location')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });

        $totalRecords = $query->count();

        $countryList = $query
            ->orderBy('created_at', 'desc')->select('id', 'name', 'address', 'latitude', 'longitude', 'telephone', 'googleMap')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $response = [
            "status" => "Success",
            "reason" => __("message_app.Fetched record successfully"),
            "total_records" => $totalRecords,
            "total_page" => ceil($totalRecords / $limit),
            "current_page" => $page,
            "data" => $countryList
        ];
        $encryptedResponse = $this->encryptContent(json_encode($response));
        return response()->json($encryptedResponse, 200);
    }

    public function profileDocumentDetail(Request $request)
    {

        $requestData = $this->decryptContent($request->req);
        $userId = Auth::user()->id;
        /* $page = max((int) ($requestData->page ?? 1), 1);
        $limit = max((int) ($requestData->limit ?? 10), 1);
        $offset = ($page - 1) * $limit; */

        $query = DB::table('travel_documents')->where('userId', $userId);
        $totalRecords = $query->count();

        $page = max($requestData->page, 1);
        $limit = max($requestData->limit, 1);

        $totalPages = ceil($totalRecords / $limit);
        if ($page > $totalPages) {
            $response = [
                "status" => "Success",
                "reason" => __("message_app.no_record_found"),
            ];
            $encryptedResponse = $this->encryptContent(json_encode($response));
            return response()->json($encryptedResponse, 200);
        }

        $offset = ($page - 1) * $limit;

        $data = $query
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();


        /* $data = DB::table('travel_documents')->where('userId', $userId)->offset($offset)->limit($limit)->orderBy('id', 'desc')->get();
        $totalRecords = $data->count(); */




        $summaryDocuments = [];

        foreach ($data as $key => $record) {

            $documentDetails = [];

            if (!empty($record->passport)) {
                $ext = pathinfo($record->passport, PATHINFO_EXTENSION);
                $documentDetails[] = [
                    "type" => "passport",
                    "label" => __("message_app.Passport:"),
                    "labelWithExtension" => "Passport.$ext",
                    "filePath" => $record->passport ? PASSPORT_PATH . $record->passport : "",
                    "statusNew" => ucfirst($record->status),
                    "status" => $record->status
                ];
            }

            if (!empty($record->ticket)) {
                $ext = pathinfo($record->ticket, PATHINFO_EXTENSION);
                $documentDetails[] = [
                    "type" => "ticket",
                    "label" => __("message_app.Airline Ticket:"),
                    "labelWithExtension" => "Airline Ticket.$ext",
                    "filePath" => $record->ticket ? TICKET_PATH . $record->ticket : "",
                    "statusNew" => ucfirst($record->status),
                    "status" => $record->status,
                ];
            }

            if (!empty($record->visa)) {
                $ext = pathinfo($record->visa, PATHINFO_EXTENSION);
                $documentDetails[] = [
                    "type" => "visa",
                    "label" => __("message_app.Stamped Visa Entry:"),
                    "labelWithExtension" => "Stamped Visa Entry.$ext",
                    "filePath" => $record->visa ? VISA_PATH . $record->visa : "",
                    "statusNew" => ucfirst($record->status),
                    "status" => $record->status

                ];
            }

            $summaryDocuments[] = [
                "id" => $record->id,
                "title" => "Document " . ($key + 1) . "_" . __("message_app.Trip"),
                "status" => $record->status,
                "statusNew" => ucfirst($record->status),
                "uploadedAt" => date('d M | h:i A', strtotime($record->created_at)),
                "documentDetails" => $documentDetails
            ];
        }

        $response = [
            "status" => "Success",
            "reason" => __("message_app.Fetched record successfully"),
            "total_records" => $totalRecords,
            "total_page" => ceil($totalRecords / $limit),
            "current_page" => $page,
            "data" => $summaryDocuments
        ];
        $encryptedResponse = $this->encryptContent(json_encode($response));
        return response()->json($encryptedResponse, 200);
    }

    public function profileDocumentUpload(Request $request)
    {
        $userId = Auth::user()->id;
        /*  $request->validate([
             'passport' => 'required|file|mimes:png,jpeg,pdf',
             'ticket' => 'required|file|mimes:png,jpeg,pdf',
             'visa' => 'required|file|mimes:png,jpeg,pdf',
         ]); */

        $input = [
            "passport" => $request->passport ?? null,
            "ticket" => $request->ticket ?? null,
            "visa" => $request->visa ?? null,
        ];

        $validator = Validator::make($input, [
            'passport' => 'required|file|mimes:png,jpeg,pdf',
            'ticket' => 'required|file|mimes:png,jpeg,pdf',
            'visa' => 'required|file|mimes:png,jpeg,pdf',
        ], [
            'passport.required' => __('message_app.passport_required'),
            'passport.file' => __('message_app.passport_file'),
            'passport.mimes' => __('message_app.passport_mimes'),

            'ticket.required' => __('message_app.ticket_required'),
            'ticket.file' => __('message_app.ticket_file'),
            'ticket.mimes' => __('message_app.ticket_mimes'),

            'visa.required' => __('message_app.visa_required'),
            'visa.file' => __('message_app.visa_file'),
            'visa.mimes' => __('message_app.visa_mimes'),
        ]);
        
        if ($validator->fails()) {
            $messages = $validator->messages();
            $firstErrorMessage = $messages->first();
            $statusArr = [
                "status" => "Failed",
                "reason" => $firstErrorMessage,
            ];
            $json = json_encode($statusArr);
            $responseData = $this->encryptContent($json);
            return response()->json($responseData, 200);
        }


        $data = [];
        if (!$request->hasFile('passport') && !$request->hasFile('ticket') && !$request->hasFile('visa')) {
            $statusRes = [
                "status" => "Failed",
                "reason" => __("message_app.Atleast one file upload"),
            ];
            $encryptedResponse = $this->encryptContent(json_encode($statusRes));
            return response()->json($encryptedResponse, 200);
        }

        if ($request->hasFile('passport')) {
            $file = $request->file('passport');
            $data['passport'] = $this->uploadImage($file, PASSPORT_PATH);
        }

        if ($request->hasFile('ticket')) {
            $file = $request->file('ticket');
            $data['ticket'] = $this->uploadImage($file, TICKET_PATH);
        }

        if ($request->hasFile('visa')) {
            $file = $request->file('visa');
            $data['visa'] = $this->uploadImage($file, VISA_PATH);
        }
        $data['userId'] = $userId;
        $data['status'] = 'pending';

        DB::table('travel_documents')->insert($data);



        $response = [
            "status" => "Success",
            "reason" => __("message_app.Travel documents uploaded successfully"),
        ];
        $encryptedResponse = $this->encryptContent(json_encode($response));
        return response()->json($encryptedResponse, 200);
    }
}