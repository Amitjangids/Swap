<?php
namespace App\Http\Controllers;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\User;
use App\Models\Transaction;
use App\Models\Country;
use App\Models\WalletManager;
use App\Models\UploadedExcel;
use App\Models\RemittanceData;
use App\Models\OnafriqaData;
use App\Models\ExcelTransaction;
use DB;
use Validator;
use GuzzleHttp\Client;
use App\Models\Issuertrxref;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use App\Services\SmsService;
use App\Services\GimacApiService;
use App\Services\CardService;
use App\Services\ReferralService;


class BulkPaymentController extends Controller
{
    private $apiUrl = APIURL;
    private $authString;
    public $smsService;
    public $gimacApiService;
    public $cardService;
    public function __construct(SmsService $smsService, GimacApiService $gimacApiService, CardService $cardService, ReferralService $referralService)
    {
        $this->authString = base64_encode(CORPORATECODE . ':' . CORPORATEPASS);
        $this->smsService = $smsService;
        $this->gimacApiService = $gimacApiService;
        $this->cardService = $cardService;
        $this->referralService = $referralService;
    }

    private function getStatusText($status)
    {
        $statusArr = array('1' => 'Completed', '2' => 'Pending', '3' => 'Failed', '4' => 'Rejected', '5' => 'Refund', '6' => 'Refund Completed');
        return $statusArr[$status];
    }

    private function getUserDataByIban($id)
    {
        $data = RemittanceData::where('id', $id)->first();
        return $data;
    }
    private function arrayToXml($array, $rootElement = null, $xml = null)
    {
        $_xml = $xml;

        if ($_xml === null) {
            $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<response/>');
        }

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->arrayToXml($v, $k, $_xml->addChild($k));
            } else {
                $_xml->addChild($k, htmlspecialchars($v));
            }
        }

        return $_xml->asXML();
    }
    public function generateAndCheckUnique()
    {
        do {
            // Generate a random alphanumeric string
            $randomString = $this->generateRandomAlphaNumeric();

            // Check if the generated string exists in the transactions table
            $exists = RemittanceData::where('transactionID', $randomString)->exists();
        } while ($exists);

        return $randomString;
    }
    private function generateRandomAlphaNumeric()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $shuffled = str_shuffle($characters);
        return substr($shuffled, 0, 10);
    }

    public function generateString()
    {
        // Generate components
        $part1 = rand(1000, 9999); // Random 4-digit number
        $part2 = 'PR'; // Fixed string
        $part3 = rand(10000, 99999); // Random 5-digit number

        // Combine parts
        return $part1 . $part2 . $part3;
    }

    private function sendCurlRequest($postData, $soapAction)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ' . $soapAction,
                'Authorization: Basic ' . $this->authString,
                'Cookie: GCLB=CJHI84va1Ji4BhAD',
                'User-Agent:' . CORPORATECODE
            ),
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        } else {
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_status != 200) {
                echo "Request failed with status: " . $http_status;
            } else {
                return $response;
            }
        }

        curl_close($curl);
    }

    public function getCountryStatus($countryShortName)
    {

        switch ($countryShortName) {
            case 'Benin':
                return "BJ";
            case 'Burkina Faso':
                return "BF";
            case 'Guinea Bissau':
                return "GW";
            case 'Mali':
                return "ML";
            case 'Niger':
                return "NE";
            case 'Senegal':
                return "SN";
            case 'Togo':
                return "TG";
            case 'Cameroon':
                return "CM";
            case 'Ivoiry Coast':
                return "CI";
            case 'Gabon':
                return "GA";
            case 'France':
                return "FR";

            default:
                return "-";
        }
    }

    public function makeBulkPayment(Request $request)
    {
        $merchantKey = $request->input('merchantId');
        $orders = $request->input('orders');

        if (!$merchantKey) {
            return response()->json(['error' => 'Merchant ID is required'], 400);
        }

        if (!is_array($orders) || empty($orders)) {
            return response()->json(['error' => 'Orders must be a non-empty array'], 400);
        }

        $onafriqCountries = [
            'BJ' => 'Benin',
            'BF' => 'Burkina Faso',
            'GW' => 'Guinea Bissau',
            'NE' => 'Niger',
            'SN' => 'Senegal',
            'ML' => 'Mali',
            'TG' => 'Togo',
            'CI' => 'Ivoiry Coast',
        ];


        $getMerchantData = User::where('merchantKey', $merchantKey)->first();

        $userId = $getMerchantData->id;
        $type = "";
        if (!$getMerchantData) {
            return response()->json(['status' => false, 'message' => 'Merchant ID does not exist'], 401);
        }

        $orderIds = collect($orders)->pluck('orderId')->filter()->toArray();

        $existingOrders = Transaction::whereIn('orderId', $orderIds)->pluck('orderId');
        if ($existingOrders->isNotEmpty()) {
            return response()->json([
                'error' => 'Some order IDs already exist in database',
                'duplicates' => $existingOrders->values()
            ], 400);
        }

        $input = $request->all();
        if ($request->isMethod('post')) {

            foreach ($orders as $order) {
                $orderId = $order['orderId'] ?? null;
                $countryId = $order['countryId'] ?? null;
                $iban = $order['ibanNumber'] ?? null; // optional if included later
                $type = null;

                // Validate orderId
                if (empty($orderId)) {
                    return response()->json([
                        'error' => 'Invalid order ID.',
                        'orderId' => $orderId
                    ], 400);
                }


                /* if (!empty($countryId)) {
                    // Check against DB
                    $countryExists = Country::where('name', 'LIKE', '%' . $countryId . '%')->exists();

                    if ($countryExists) {
                        $type = 'GIMAC';
                    } elseif (!empty($countryId) && !empty($iban)) {
                        $type = 'BDA';
                    } elseif (in_array($countryId, $onafriqCountries)) {
                        $type = 'ONAFRIQ';
                    } else {
                        $type = 'UNKNOWN';
                    }
                } */
                foreach ($orders as $order) {
                    $countryId = $order['countryId'] ?? null;

                    if (!empty($countryId)) {
                        $countryExists = Country::where('name', 'LIKE', '%' . $countryId . '%')->exists();

                        if ($countryExists) {
                            $type = 'GIMAC';
                        } elseif (!empty($countryId) && !empty($iban)) {
                            $type = 'BDA';
                        } elseif (in_array($countryId, $onafriqCountries)) {
                            $type = 'ONAFRIQ';
                        } else {
                            return response()->json([
                                'error' => "Invalid country: {$countryId}"
                            ], 400);
                        }
                    } else {
                        return response()->json([
                            'error' => 'Country ID is missing in one of the orders'
                        ], 400);
                    }
                }
            }

            $rules = [];
            $messages = [];
            if ($type == "UNKNOWN") {
                return response()->json([
                    'status' => false,
                    'message' => "Payment mode is not valid"
                ], 404);
            }
            if (isset($type)) {
                switch ($type) {
                    case 'GIMAC':
                        $rules = [
                            'orders' => 'required|array|min:1',
                            'orders.*.countryId' => 'required',
                            'orders.*.walletManagerId' => 'required',
                            'orders.*.firstName' => 'required',
                            'orders.*.name' => 'required',
                            'orders.*.amount' => 'required|numeric|min:1|max:99999999',
                            'orders.*.phone' => 'required',
                            'orders.*.senderName' => 'required',
                            'orders.*.senderLastName' => 'required',
                            'orders.*.senderIDType' => 'required',
                            'orders.*.senderIDNumber' => 'required',
                        ];

                        $messages = [
                            'orders.*.countryId.required' => 'Country field can\'t be left blank',
                            'orders.*.walletManagerId.required' => 'Wallet manager field can\'t be left blank',
                            'orders.*.firstName.required' => 'First name field can\'t be left blank',
                            'orders.*.name.required' => 'Last name field can\'t be left blank',
                            'orders.*.amount.required' => 'Amount field can\'t be left blank',
                            'orders.*.phone.required' => 'Wallet manager field can\'t be left blank',
                            'orders.*.senderName.required' => 'Sender Name field can\'t be left blank',
                            'orders.*.senderLastName.required' => 'Sender Last Name field can\'t be left blank',
                            'orders.*.senderIDType.required' => 'Sender Id Type field can\'t be left blank',
                            'orders.*.senderIDNumber.required' => 'Sender Id Number field can\'t be left blank',

                        ];
                        break;

                    /* case 'BDA':
                        $rules = [
                            'orders' => 'required|array|min:1',
                            'orders.*.countryId' => 'required',
                            'orders.*.beneficiary' => ['required', 'max:50'],
                            'orders.*.ibanNumber' => ['required', 'regex:/^[a-zA-Z0-9]+$/', 'min:24', 'max:30'],
                            'orders.*.reason' => 'required',
                            'orders.*.amount' => 'required|numeric|min:1|max:99999999',
                        ];

                        $messages = [
                            'orders.*.countryId.required' => 'Country field can\'t be left blank',
                            'orders.*.beneficiary.required' => 'Beneficiary can\'t be left blank',
                            'orders.*.ibanNumber.required' => 'Iban number field can\'t be left blank',
                            'orders.*.ibanNumber.min' => 'Iban number min length is 24',
                            'orders.*.ibanNumber.max' => 'Iban number max length is 30',
                            'orders.*.reason.required' => 'Reason field can\'t be left blank',
                            'orders.*.amount.required' => 'Amount field can\'t be left blank',
                            'orders.*.amount.min' => 'Amount must be at least 1',
                            'orders.*.amount.max' => 'Amount maximum 99999999',
                        ];
                        break;

                    case 'ONAFRIQ':
                        $rules = [
                            'orders' => 'required|array|min:1',
                            'orders.*.countryId' => 'required',
                            'orders.*.walletManagerId' => 'required',
                            'orders.*.recipientPhone' => 'required',
                            'orders.*.recipientFirstName' => 'required',
                            'orders.*.recipientSurname' => 'required',
                            'orders.*.amount' => 'required|numeric|min:500|max:1500000',
                            'orders.*.senderCountry' => 'required',
                            'orders.*.senderPhone' => 'required',
                            'orders.*.senderName' => 'required',
                            'orders.*.senderSurname' => 'required',
                            'orders.*.senderAddress' => 'required_if:countryId,Mali,Senegal',
                            'orders.*.senderIdType' => 'required_if:countryId,Mali,Senegal',
                            'orders.*.senderIdNumber' => 'required_if:countryId,Mali,Senegal',
                            'orders.*.senderDob' => 'required_if:countryId,Mali,Senegal,Burkina Faso',
                        ];

                        $messages = [
                            'orders.*.countryId.required' => 'Recipient Country field can\'t be left blank',
                            'orders.*.walletManagerId.required' => 'Wallet Manager field can\'t be left blank',
                            'orders.*.recipientPhone.required' => 'Recipient Phone Number field can\'t be left blank',
                            'orders.*.recipientFirstName.required' => 'Recipient First Name field can\'t be left blank',
                            'orders.*.recipientSurname.required' => 'Recipient Surname field can\'t be left blank',
                            'orders.*.amount.required' => 'Amount field can\'t be left blank',
                            'orders.*.amount.min' => 'The amount must be at least 500',
                            'orders.*.amount.max' => 'The amount maximum 1500000',
                            'orders.*.senderCountry.required' => 'Sender Country field can\'t be left blank',
                            'orders.*.senderPhone.required' => 'Sender Phone Number field can\'t be left blank',
                            'orders.*.senderName.required' => 'Sender Name field can\'t be left blank',
                            'orders.*.senderSurname.required' => 'Sender Surname field can\'t be left blank',
                            'orders.*.senderAddress.required' => 'Sender Address field can\'t be left blank',
                            'orders.*.senderIdType.required' => 'Sender Id Type field can\'t be left blank',
                            'orders.*.senderIdNumber.required' => 'Sender Id Number field can\'t be left blank',
                            'orders.*.senderDob.required' => 'Sender Dob field can\'t be left blank',
                        ];
                        break; */
                }
            }

            $validator = Validator::make($input, $rules, $messages);
            if ($validator->fails()) {
                $errorMessage = $validator->errors()->first();
                return response()->json([
                    'status' => false,
                    'message' => $errorMessage
                ], 400);
            }

            $refrence_id = time() . rand();
            $certificate = public_path("MTN Cameroon Issuing CA1.crt");
            $client = new Client([
                'verify' => $certificate,
            ]);

            if ($type == 'GIMAC') {
                try {
                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => $userId,
                        'parent_id' => $userId,
                        'excel' => 'External Gimac',
                        'reference_id' => $refrence_id,
                        'no_of_records' => count($input['orders']),
                        'totat_amount' => collect($input['orders'])->sum('amount'),
                        'type' => 1,
                        'status' => 5,
                        'total_fees' => 0,
                        'approved_by' => $userId,
                        'remarks' => $input['comment'] ?? ''
                    ]);

                    foreach ($input['orders'] as $order) {
                        $tomember_id = $order['walletManagerId'];
                        $totalAmount = $order['amount'];

                        $tomemberData = WalletManager::where('name', $tomember_id)->first();
                        $country = Country::where('name', $order['countryId'])->first();
                        $tomember = $tomemberData->tomember ?? '';
                        if (!$tomember) {
                            return response()->json([
                                'status' => false,
                                'message' => "Wallet manager not valid"
                            ], 400);
                        }
                        $first = $order['firstName'] ?? ($getMerchantData->name ?? '');
                        $name = $order['name'] ?? '-';

                        $excelData = ExcelTransaction::create([
                            'excel_id' => $uploadedExcel->id,
                            'parent_id' => $userId,
                            'submitted_by' => 0,
                            'first_name' => $first,
                            'name' => $name,
                            'comment' => $input['comment'] ?? '',
                            'country_id' => $country->id,
                            'wallet_manager_id' => $tomemberData->id ?? "",
                            'tel_number' => $order['phone'] ?? '',
                            'amount' => $totalAmount,
                            'fees' => 0,
                            'bdastatus' => "GIMAC"
                        ]);

                        // Generate timestamp + issuer ref
                        $timestamp = now()->timestamp;
                        $last_record = Issuertrxref::orderBy('id', 'desc')->value('issuertrxref');
                        $next_issuertrxref = $last_record ? $last_record + 1 : '140071';

                        $data = [
                            'intent' => 'mobile_transfer',
                            'createtime' => $timestamp,
                            'walletsource' => $order['phone'] ?? '',
                            'walletdestination' => $getMerchantData->phone,
                            'issuertrxref' => $next_issuertrxref,
                            'amount' => $totalAmount,
                            'currency' => '950',
                            'description' => 'money transfer',
                            'tomember' => $tomember,
                        ];


                        Log::info("GIMAC Payment Request:", $data);

                        if (empty($accessToken)) {
                            $options = [
                                'form_params' => [
                                    'grant_type' => 'password',
                                    'client_id' => env('GIMAC_CLIENT_ID'),
                                    'client_secret' => env('GIMAC_CLIENT_SECRET'),
                                    'scope' => 'read',
                                    'username' => env('GIMAC_USER_NAME'),
                                    'password' => env('GIMAC_PASSWORD'),
                                    'expires_in' => 86400,
                                ],
                            ];
                            $response = $client->request('POST', env('GIMAC_TOKEN_URL'), $options);
                            $jsonResponse = json_decode($response->getBody()->getContents());
                            $accessToken = $jsonResponse->access_token ?? '';
                        }

                        $response = $client->request('POST', env('GIMAC_PAYMENT_URL'), [
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Authorization' => "Bearer $accessToken"
                            ],
                            'json' => $data,
                        ]);

                        $jsonResponse2 = json_decode($response->getBody()->getContents());
                        $statusCode = $response->getStatusCode();

                        if ($statusCode == 200) {
                            Issuertrxref::create(['issuertrxref' => $next_issuertrxref, 'messages' => 'successfull']);
                            $state = $jsonResponse2->state ?? 'FAILED';
                            $status = $state == 'ACCEPTED' ? 1 : 2;

                            if ($state == 'REJECTED') {
                                ExcelTransaction::where('id', $excelData->id)->update([
                                    'remarks' => $jsonResponse2->rejectMessage ?? 'Rejected'
                                ]);
                                continue; // move to next order
                            }

                            $trans = Transaction::create([
                                'user_id' => 0,
                                'receiver_id' => $getMerchantData->id,
                                'receiver_mobile' => $order['phone'] ?? '',
                                'amount' => $totalAmount,
                                'amount_value' => $totalAmount,
                                'transaction_amount' => 0,
                                'total_amount' => $totalAmount,
                                'trans_type' => 1,
                                'excel_trans_id' => $excelData->id,
                                'payment_mode' => 'External',
                                'status' => $status,
                                'refrence_id' => $jsonResponse2->issuertrxref ?? $next_issuertrxref,
                                'billing_description' => 'Fund Transfer-' . $refrence_id,
                                'tomember' => $tomember,
                                'acquirertrxref' => $jsonResponse2->acquirertrxref ?? '',
                                'issuertrxref' => $jsonResponse2->issuertrxref ?? '',
                                'vouchercode' => $jsonResponse2->vouchercode ?? '',
                                'transactionType' => 'SWAPTOCEMAC',
                                'orderId' => $order['orderId'] ?? null,
                                'senderName' => $order['senderName'] ?? null,
                                'senderLastName' => $order['senderLastName'] ?? null,
                                'senderIDType' => $order['senderIDType'] ?? null,
                                'senderIDNumber' => $order['senderIDNumber'] ?? null,
                                'walletsource' => $order['phone'] ?? null,
                            ]);

                            if ($state == 'ACCEPTED') {
                                $opening_balance_senderG = $getMerchantData->wallet_balance;

                                $credit = new TransactionLedger([
                                    'user_id' => $getMerchantData->id,
                                    'opening_balance' => $opening_balance_senderG,
                                    'amount' => $totalAmount,
                                    'fees' => 0,
                                    'actual_amount' => $totalAmount,
                                    'type' => 1,
                                    'excelTransId' => $excelData->id,
                                    'trans_id' => $trans->id,
                                    'payment_mode' => 'External',
                                    'closing_balance' => $opening_balance_senderG + $totalAmount,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $credit->save();
                                DB::table('users')->where('id', $userId)->increment('wallet_balance', $totalAmount);
                                $wallet_balance = $getMerchantData->wallet_balance + $totalAmount;
                                Transaction::where("id", $trans->id)->update([
                                    'remainingWalletBalance' => $wallet_balance
                                ]);
                            }
                        }
                        DB::commit();
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Transaction completed'
                    ], 200);
                } catch (\Exception $e) {
                    $last_record = Issuertrxref::orderBy('id', 'desc')->value('issuertrxref');
                    $next_issuertrxref = ($last_record !== null) ? $last_record + 1 : 140071;
                    $errorMessage = 'Gimac Server Error'; // default fallback message

                    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                        $contents = (string) $e->getResponse()->getBody();
                        $jsonResponse = json_decode($contents, true);

                        if (!empty($jsonResponse['error_description'])) {
                            $errorMessage = $jsonResponse['error_description'];
                        } else {
                            $errorMessage = 'Unable to extract error_description';
                        }
                    }
                    Issuertrxref::create([
                        'issuertrxref' => $next_issuertrxref,
                        'messages' => $errorMessage,
                    ]);
                    /* ExcelTransaction::where('id', $excelData->id)->update([
                        'remarks' => $errorMessage,
                    ]); */

                    return response()->json([
                        'status' => false,
                        'message' => $errorMessage,
                    ], 400);
                }


            }
            /* elseif ($type == 'ONAFRIQ') {
                try {

                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => $userId,
                        'parent_id' => $userId,
                        'excel' => 'External ONAFRIQ',
                        'reference_id' => $refrence_id,
                        'no_of_records' => count($input['orders']),
                        'totat_amount' => collect($input['orders'])->sum('amount'),
                        'type' => 0,
                        'status' => 5,
                        'total_fees' => 0,
                        'approved_by' => $userId,
                        'remarks' => $input['comment'] ?? ''
                    ]);

                    foreach ($input['orders'] as $order) {
                        $tomember_id = $order['walletManagerId'];
                        $totalAmount = $order['amount'];

                        $tomemberData = DB::table('wallet_manager_onafriq')->where('name', $tomember_id)->first();
                        $country = DB::table('countries_onafriq')->where('name', $order['countryId'])->first();


                        $first = $order['firstName'] ?? ($getMerchantData->name ?? '');
                        $name = $order['name'] ?? '-';

                        $excelData = ExcelTransaction::create([
                            'excel_id' => $uploadedExcel->id,
                            'parent_id' => $userId,
                            'submitted_by' => 0,
                            'first_name' => $first,
                            'name' => $name,
                            'comment' => '',
                            'country_id' => $country->id,
                            'wallet_manager_id' => $tomemberData->id ?? "",
                            'tel_number' => '',
                            'amount' => $totalAmount,
                            'fees' => 0,
                            'bdastatus' => "ONAFRIQ"
                        ]);

                        $onafriqaDataA = new OnafriqaData();
                        $onafriqaDataA->amount = $totalAmount;
                        $onafriqaDataA->recipientCountry = $this->getCountryStatus($order['countryId']);
                        $onafriqaDataA->walletManager = $order['walletManagerId'];
                        $onafriqaDataA->recipientMsisdn = $order['recipientPhone'];
                        $onafriqaDataA->recipientName = $order['recipientFirstName'];
                        $onafriqaDataA->recipientSurname = $order['recipientSurname'];
                        $onafriqaDataA->senderCountry = $this->getCountryStatus($order['senderCountry']);
                        $onafriqaDataA->senderMsisdn = $order['senderPhone'];
                        $onafriqaDataA->senderName = $order['senderName'];
                        $onafriqaDataA->senderSurname = $order['senderSurname'];
                        $onafriqaDataA->senderAddress = $order['senderAddress'] ?? "";
                        $onafriqaDataA->senderDob = $order['senderDob'] ?? "";
                        $onafriqaDataA->senderIdType = $order['senderIdType'] ?? "";
                        $onafriqaDataA->senderIdNumber = $order['senderIdNumber'] ?? "";
                        $onafriqaDataA->recipientCurrency = 'XOF';
                        $onafriqaDataA->thirdPartyTransactionId = $this->generateAndCheckUnique();
                        $onafriqaDataA->status = 'pending';
                        $onafriqaDataA->excelTransId = $excelData->id;
                        $onafriqaDataA->userId = $userId;
                        $onafriqaDataA->fromMSISDN = $order['senderPhone'];
                        $onafriqaDataA->save();


                        $postData = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                <soap:Body>
                                <ns:account_request xmlns:ns="http://ws.mfsafrica.com">
                                <ns:login>
                                <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                                <ns:password>' . CORPORATEPASS . '</ns:password>
                                </ns:login>
                                <ns:to_country>' . $order['countryId'] . '</ns:to_country>
                                <ns:msisdn>' . $order['recipientPhone'] . '</ns:msisdn>
                                </ns:account_request>
                                </soap:Body>
                                </soap:Envelope>';
                        $getResponse = $this->sendCurlRequest($postData, 'urn:account_request');
                        Log::info("Request => $postData");
                        Log::info("Response => $getResponse");
                        $xml = new SimpleXMLElement($getResponse);

                        $xml->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
                        $xml->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

                        $namespaces = $xml->getNamespaces(true);
                        $axNamespace = '';
                        foreach ($namespaces as $prefix => $namespace) {
                            if (strpos($namespace, 'http://mfs/xsd') !== false) {
                                $axNamespace = $prefix;
                                break;
                            }
                        }
                        $xml->registerXPathNamespace($axNamespace, 'http://mfs/xsd');
                        $status = $xml->xpath('//' . $axNamespace . ':status')[0];
                        $statusCode = (string) $status->xpath('' . $axNamespace . ':status_code')[0];
                        if ($statusCode == "Active") {
                            $getOnfi = OnafriqaData::where('excelTransId', $excelData->id)->where('status', 'pending')->first();
                            $postDataRemit = '
                            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                <soap:Body>
                                    <ns:mm_remit_log xmlns:ns="http://ws.mfsafrica.com">
                                    <ns:login>
                                        <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code> 
                                        <ns:password>' . CORPORATEPASS . '</ns:password> 
                                    </ns:login>
                                    <ns:receive_amount>
                                        <ns:amount>' . $getOnfi->amount . '</ns:amount> 
                                        <ns:currency_code>' . $getOnfi->recipientCurrency . '</ns:currency_code> 
                                    </ns:receive_amount>
                                    <ns:sender>
                                        <ns:address>' . ($getOnfi->senderAddress ?: "") . '</ns:address>
                                        <ns:city>string</ns:city>
                                        <ns:date_of_birth>' . ($getOnfi->senderDob ?: "") . '</ns:date_of_birth>
                                        <ns:document>
                                        <ns:id_country>string</ns:id_country>
                                        <ns:id_expiry>string</ns:id_expiry>
                                        <ns:id_number>' . ($getOnfi->senderIdNumber ?: "") . '</ns:id_number>
                                        <ns:id_type>' . ($getOnfi->senderIdType ?: "") . '</ns:id_type>
                                        </ns:document>
                                        <ns:email>string</ns:email>
                                        <ns:from_country>' . $getOnfi->senderCountry . '</ns:from_country>
                                        <ns:msisdn>' . $getOnfi->senderMsisdn . '</ns:msisdn>
                                        <ns:name>' . $getOnfi->senderName . '</ns:name>
                                        <ns:place_of_birth>string</ns:place_of_birth>
                                        <ns:postal_code>string</ns:postal_code>
                                        <ns:state>string</ns:state>
                                        <ns:surname>' . $getOnfi->senderSurname . '</ns:surname>
                                    </ns:sender>
                                    <ns:recipient>
                                        <ns:address>string</ns:address>
                                        <ns:city>string</ns:city>
                                        <ns:date_of_birth>string</ns:date_of_birth>
                                        <ns:document>
                                        <ns:id_country>string</ns:id_country>
                                        <ns:id_expiry>string</ns:id_expiry>
                                        <ns:id_number>string</ns:id_number>
                                        <ns:id_type>string</ns:id_type>
                                        </ns:document>
                                        <ns:email>string</ns:email>
                                        <ns:msisdn>' . $getOnfi->recipientMsisdn . '</ns:msisdn>
                                        <ns:name>' . $getOnfi->recipientName . '</ns:name>
                                        <ns:postal_code>string</ns:postal_code>
                                        <ns:state>string</ns:state>
                                        <ns:status>
                                        <ns:status_code>string</ns:status_code>
                                        </ns:status>
                                        <ns:surname>' . $getOnfi->recipientSurname . '</ns:surname>
                                        <ns:to_country>' . $getOnfi->recipientCountry . '</ns:to_country>
                                    </ns:recipient>
                                    <ns:third_party_trans_id>' . $getOnfi->thirdPartyTransactionId . '</ns:third_party_trans_id>
                                    <ns:reference>string</ns:reference>
                                    <ns:source_of_funds>string</ns:source_of_funds>
                                    <ns:purpose_of_transfer>string</ns:purpose_of_transfer>
                                    </ns:mm_remit_log>
                                </soap:Body>
                            </soap:Envelope>';

                            $getResponseRemit = $this->sendCurlRequest($postDataRemit, 'urn:mm_remit_log');
                            Log::info("Request => $postDataRemit");
                            Log::info("Response => $getResponseRemit");
                            $xml1 = new SimpleXMLElement($getResponseRemit);

                            $xml1->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
                            $xml1->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');
                            $namespaces1 = $xml1->getNamespaces(true);
                            $axNamespace1 = '';
                            foreach ($namespaces1 as $prefix1 => $namespace2) {
                                if (strpos($namespace2, 'http://mfs/xsd') !== false) {
                                    $axNamespace1 = $prefix1;
                                    break;
                                }
                            }
                            $xml1->registerXPathNamespace($axNamespace1, 'http://mfs/xsd');
                            $status1 = $xml1->xpath('//' . $axNamespace1 . ':status')[0];
                            $mfs_trans_id = (string) $xml1->xpath('//' . $axNamespace1 . ':mfs_trans_id')[0];
                            $partner_code = (string) $xml1->xpath('//' . $axNamespace1 . ':partner_code')[0];
                            $statusCode1 = (string) $status1->xpath('' . $axNamespace1 . ':code/' . $axNamespace1 . ':status_code')[0];
                            $statusMessage = (string) $status1->xpath('ax21:message')[0];
                            $receiveAmount = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':amount')[0];
                            $currencyCode = (string) $xml1->xpath('//' . $axNamespace1 . ':receive_amount/' . $axNamespace1 . ':currency_code')[0];


                            if ($statusCode1 == "MR104" && $statusMessage == "Log Success" && $mfs_trans_id != "") {
                                $postDataTrans = '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                                    <soap:Body>
                                    <ns:trans_com xmlns:ns="http://ws.mfsafrica.com">
                                    <ns:login>
                                    <ns:corporate_code>' . CORPORATECODE . '</ns:corporate_code>
                                    <ns:password>' . CORPORATEPASS . '</ns:password>
                                    </ns:login>
                                    <ns:trans_id>' . $mfs_trans_id . '</ns:trans_id>
                                    </ns:trans_com>
                                    </soap:Body>
                                    </soap:Envelope>';
                                $getResponseTrans = $this->sendCurlRequest($postDataTrans, 'urn:trans_com');
                                Log::info("Request => $postDataTrans");
                                Log::info("Response => $getResponseTrans");
                                $xml2 = new SimpleXMLElement($getResponseTrans);
                                $xml2->registerXPathNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
                                $xml2->registerXPathNamespace('ns', 'http://ws.mfsafrica.com');

                                $namespaces2 = $xml2->getNamespaces(true);
                                $axNamespace2 = '';
                                foreach ($namespaces2 as $prefix2 => $namespace3) {
                                    if (strpos($namespace3, 'http://mfs/xsd') !== false) {
                                        $axNamespace2 = $prefix2;
                                        break;
                                    }
                                }
                                $xml2->registerXPathNamespace($axNamespace2, 'http://mfs/xsd');
                                $status2 = $xml2->xpath('//' . $axNamespace2 . ':code')[0];
                                $e_trans_id2 = (string) $xml2->xpath('//' . $axNamespace2 . ':e_trans_id')[0];
                                $message2 = (string) $xml2->xpath('//' . $axNamespace2 . ':message')[0];
                                $statusCode2 = (string) $status2->xpath('' . $axNamespace2 . ':status_code')[0];

                                if ($statusCode2 === 'MR101') {
                                    $refrence_id = time();
                                    $trans = new Transaction([
                                        'user_id' => $userId,
                                        'receiver_id' => '0', //$getReceiver['id'],
                                        'receiver_mobile' => '', //$getReceiver['phone'],
                                        'amount' => $totalAmount,
                                        'amount_value' => $totalAmount,
                                        'transaction_amount' => 0,
                                        'total_amount' => $totalAmount,
                                        'trans_type' => 1,
                                        'excel_trans_id' => $excelT->id,
                                        'payment_mode' => 'External',
                                        'status' => 1,
                                        'refrence_id' => '',
                                        'billing_description' => "Fund Transfer-$refrence_id",
                                        'tomember' => '',
                                        'acquirertrxref' => '',
                                        'issuertrxref' => '',
                                        'vouchercode' => '',
                                        'onafriq_bda_ids' => $getOnfi->id,
                                        'transactionType' => 'SWAPTOONAFRIQ',
                                        'orderId' => $orderId,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]);
                                    $trans->save();


                                    OnafriqaData::where('excelTransId', $excelT->id)->update(['transactionId' => $mfs_trans_id, 'status' => 'success', 'partnerCode' => $partner_code, 'userId' => $userId]);
                                    ExcelTransaction::where('id', $excelTrans->id)->update(['approved_by' => $userId, 'approver_id' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                                    DB::table('users')->where('id', $userId)->increment('wallet_balance', $totalAmount);

                                } else {
                                    if ($statusCode2 == 'MR108' || $statusCode2 == 'MR103' || $statusCode2 == 'MR102') {
                                        $refrence_id = time();
                                        $trans = new Transaction([
                                            'user_id' => $userId,
                                            'receiver_id' => 0,
                                            'receiver_mobile' => '',
                                            'amount' => $totalAmount,
                                            'amount_value' => $totalAmount,
                                            'transaction_amount' => 0,
                                            'total_amount' => $totalAmount,
                                            'trans_type' => 1,
                                            'excel_trans_id' => $excelT->id,
                                            'payment_mode' => 'External',
                                            'status' => 2,
                                            'refrence_id' => '',
                                            'bda_status' => 5,
                                            'billing_description' => 'Fund Transfer-' . $refrence_id,
                                            'tomember' => '',
                                            'acquirertrxref' => '',
                                            'issuertrxref' => '',
                                            'vouchercode' => '',
                                            'onafriq_bda_ids' => $getOnfi->id,
                                            'transactionType' => 'SWAPTOONAFRIQ',
                                            'orderId' => $orderId,
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s'),
                                        ]);
                                        $trans->save();

                                        OnafriqaData::where('excelTransId', $excelData->id)->update(['transactionId' => $mfs_trans_id, 'partnerCode' => $partner_code]);
                                        return response()->json([
                                            'status' => true,
                                            'message' => "Payment Inprogress"
                                        ], 200);


                                    } else {
                                        ExcelTransaction::where('id', $excelData->id)->update(['remarks' => 'Subscriber not authorized to receive amount', 'approved_by' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                                        return response()->json([
                                            'status' => false,
                                            'message' => "Payment failed"
                                        ], 400);
                                    }
                                }
                            } else {
                                ExcelTransaction::where('id', $excelData->id)->update(['remarks' => 'Transaction not execute', 'approved_by' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);

                                return response()->json([
                                    'status' => false,
                                    'message' => "Payment failed remit log not response success"
                                ], 400);
                            }
                        } else {
                            ExcelTransaction::where('id', $excelData->id)->update(['remarks' => 'Recipient phone number not active ', 'approved_by' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                            return response()->json([
                                'status' => false,
                                'message' => "Recipient phone number not active"
                            ], 400);
                        }

                        DB::commit();
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Transaction completed'
                    ], 200);
                } catch (\Exception $e) {
                    dd($e);
                    $errorMessage = 'ONAFRIQ Server Error';
                    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                        $response = $e->getResponse();
                        $contents = (string) $response->getBody();

                        $jsonResponse = json_decode($contents, true);
                        $errorMessage = $jsonResponse['error_description'] ?? __('Unable to extract error_description');
                    }
                    return response()->json([
                        'status' => false,
                        'message' => $errorMessage,
                    ], 400);
                }
            } elseif ($type == 'BDA') {
                try {

                    $uploadedExcel = UploadedExcel::create([
                        'user_id' => $userId,
                        'parent_id' => $userId,
                        'excel' => 'External BDA',
                        'reference_id' => $refrence_id,
                        'no_of_records' => count($input['orders']),
                        'totat_amount' => collect($input['orders'])->sum('amount'),
                        'type' => 1,
                        'status' => 5,
                        'total_fees' => 0,
                        'approved_by' => $userId,
                        'remarks' => $input['comment'] ?? ''
                    ]);

                    foreach ($input['orders'] as $order) {
                        $totalAmount = $order['amount'];
                        $country = Country::where('name', $order['countryId'])->first();

                        $excelTrans = ExcelTransaction::create([
                            'excel_id' => $uploadedExcel->id,
                            'parent_id' => $userId,
                            'submitted_by' => 0,
                            'first_name' => "~~~", //$userName,
                            'name' => '',
                            'country_id' => '',
                            'wallet_manager_id' => '',
                            'tel_number' => '',
                            'amount' => $totalAmount,
                            'fees' => 0,
                            'comment' => $order['reason'],
                            'bdastatus' => 'BDA'
                        ]);


                        $remittanceData = new RemittanceData();
                        $getLstNo = RemittanceData::orderBy('id', 'desc')->select('referenceLot')->first();
                        if (empty($getLstNo)) {
                            $refNoLo = 'SWAP9999';
                        } else {
                            preg_match('/([a-zA-Z]+)([0-9]+)/', $getLstNo->referenceLot, $matches);
                            $incrementedPart = (int) $matches[2] + 1;
                            $newReferenceLot = $matches[1] . $incrementedPart;
                            $refNoLo = $newReferenceLot;
                        }
                        $remittanceData->transactionId = $this->generateAndCheckUnique();
                        $remittanceData->product = 'SWAP';
                        $remittanceData->iban = $order['ibanNumber'];
                        $remittanceData->titleAccount = $order['beneficiary'];
                        $remittanceData->amount = $totalAmount;
                        $remittanceData->partnerreference = $this->generateString();
                        $remittanceData->reason = $order['reason'];
                        $remittanceData->userId = $getMerchantData->id;
                        $remittanceData->referenceLot = $refNoLo;
                        $remittanceData->type = 'bank_transfer';
                        $remittanceData->excel_id = $excelTrans->id;
                        $remittanceData->save();

                        $details = RemittanceData::where('id', $remittanceData->id)->first();
                        $url = env('BDA_PAYMENT_URL');
                        $data = [
                            'referenceLot' => $details->referenceLot,
                            'nombreVirement' => 1,
                            'montantTotal' => $details->amount,
                            'produit' => $details->product,
                            'virements' => [
                                [
                                    'ibanCredit' => $details->iban,
                                    'intituleCompte' => $details->titleAccount,
                                    'montant' => $details->amount,
                                    'referencePartenaire' => $details->partnerreference,
                                    'motif' => $details->reason,
                                    'typeVirement' => 'RTGS'
                                ]
                            ]
                        ];

                        $response = $client->request('POST', $url, [
                            'headers' => [
                                'Content-Type' => 'application/json'
                            ],
                            'json' => $data,
                        ]);

                        $body = $response->getBody()->getContents();
                        $jsonResponse2 = json_decode($body);
                        $statusCode = $response->getStatusCode();
                        Log::channel('BDA')->info("Response :", ['Bda Response' => $jsonResponse2]);
                        if ($statusCode == 200) {

                            $statut = $jsonResponse2->statut;
                            if ($statut === 'REJETE') {
                                Log::chennel('BDA')->info($statut);
                                RemittanceData::where('excel_id', $excelTrans->id)->update(['status' => $statut]);
                                ExcelTransaction::where('id', $excelTrans->id)->update(['remarks' => 'Rejected', 'approved_by' => $userId, 'approver_id' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                                continue; // move to next order
                            }

                            if ($statut == 'EN_ATTENTE' || $statut == 'EN_ATTENTE_REGLEMENT') {
                                $trans = new Transaction([
                                    'user_id' => $userId,
                                    'receiver_id' => 0,
                                    'receiver_mobile' => '',
                                    'amount' => $totalAmount,
                                    'amount_value' => $totalAmount,
                                    'transaction_amount' => 0,
                                    'total_amount' => $totalAmount,
                                    'trans_type' => 1,
                                    'excel_trans_id' => $excelTrans->id,
                                    'payment_mode' => 'External',
                                    'status' => 2,
                                    'bda_status' => 2,
                                    'refrence_id' => '',
                                    'billing_description' => 'Fund Transfer-' . $refrence_id,
                                    'tomember' => '',
                                    'acquirertrxref' => '',
                                    'issuertrxref' => '',
                                    'vouchercode' => '',
                                    'onafriq_bda_ids' => $details->id,
                                    'transactionType' => 'SWAPTOBDA',
                                    'orderId' => $order['orderId'],
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                                $trans->save();
                                RemittanceData::where('excel_id', $excelTrans->id)->update(['status' => $statut]);
                                ExcelTransaction::where('id', $excelTrans->id)->update(['approved_by' => $userId, 'approver_id' => $userId, 'approved_by_merchant' => $userId, 'approved_merchant_date' => now()]);
                            }
                        }
                        DB::commit();
                    }
                    return response()->json([
                        'status' => true,
                        'message' => 'Transaction completed'
                    ], 200);
                } catch (\Exception $e) {
                    $errorMessage = 'BDA Server Error';
                    if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                        $response = $e->getResponse();
                        $contents = (string) $response->getBody();

                        $jsonResponse = json_decode($contents, true);
                        $errorMessage = $jsonResponse['error_description'] ?? __('Unable to extract error_description');
                    }
                    return response()->json([
                        'status' => false,
                        'message' => $errorMessage,
                    ], 400);
                }
            } */
        }
    }






}