<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\User;
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
use App\Models\Feature;
use App\Models\Userfeature;
use App\Models\Errorrecords;
use App\Models\GeneratedQrCode;
use App\Models\RemittanceData;
use DB;
use Input;
use Validator;
use App;
use Illuminate\Support\Facades\Artisan;
use PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use ZipArchive;
use Illuminate\Validation\Rule;
use GuzzleHttp\Client;

class ThirdPartyApiController extends Controller
{

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

    private function decryptContentString($content)
    {
        $encryption = new \MrShan0\CryptoLib\CryptoLib();
        $secretyKey = SECRET_KEY;

        $plainText = $encryption->decryptCipherTextWithRandomIV(
            $content,
            $secretyKey
        );
        $plainText = $plainText . PHP_EOL;

        return $plainText;
    }

    private function generateNumericOTP($n)
    {
        $generator = "1357902468";
        $result = "";

        for ($i = 1; $i <= $n; $i++) {
            $result .= substr($generator, rand() % strlen($generator), 1);
        }
        return $result;
    }

    private function generateQRCode($qrString, $user_id)
    {
        $output_file = "uploads/qr-code/" . $user_id . "-qrcode-" . time() . ".png";
        $image = \QrCode::format("png")
            ->size(200)
            ->errorCorrection("H")
            ->generate($qrString, base_path() . "/public/" . $output_file);
        return $output_file;
    }

    private function generateUniqueKey($length = 10, $column = 'unique_key', $model)
    {
        do {
            $key = Str::random($length);
        } while ($model::where($column, $key)->exists());

        return $key;
    }

    function asDollars($value)
    {
        // if ($value < 0) {
        //     return "-" . asDollars(-$value);
        // }
        return number_format($value, 2);
    }

    public function bankTransfer(Request $request)
    {

        $input = Input::all();

        $validate_data = [
            'product' => 'required|string',
            'iban' => 'required|string',
            'titleAccount' => 'required|string',
            'amount' => 'required|numeric',
            'partnerreference' => 'required|string',
            'reason' => 'required|string|max:30'
        ];

        $customMessages = [
            'product.required' => 'Product name is required.',
            'product.string' => 'Product name must be a string.',
            'iban.required' => 'Iban number is required.',
            'iban.string' => 'Iban number must be a string.',
            'titleAccount.required' => 'Title account is required.',
            'titleAccount.string' => 'Title account must be a string.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'partnerreference.required' => 'Partner reference is required.',
            'partnerreference.string' => 'Partner reference must be a string.',
            'reason.required' => 'Reason is required.',
            'reason.string' => 'Reason must be a string.',
            'reason.max' => 'Reason must not exceed 3 characters.',
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            return response()->json($statusArr, 200);
        }

        $remittanceData = new RemittanceData();
        $remittanceData->transactionId = $this->generateAndCheckUnique();
        $remittanceData->senderType = $request->has('senderType') ? $input['senderType'] : '';
        $remittanceData->firstName = $request->has('firstName') ? $input['firstName'] : '';
        $remittanceData->lastName = $request->has('lastName') ? $input['lastName'] : '';
        $remittanceData->businessName = $request->has('businessName') ? $input['businessName'] : '';
        $remittanceData->idType = $request->has('idType') ? $input['idType'] : '';
        $remittanceData->idNumber = $request->has('idNumber') ? $input['idNumber'] : '';
        $remittanceData->senderPhoneNumber = $request->has('senderPhoneNumber') ? $input['senderPhoneNumber'] : '';
        $remittanceData->senderAddress = $request->has('senderAddress') ? $input['senderAddress'] : '';
        $remittanceData->receiverType = $request->has('receiverType') ? $input['receiverType'] : '';
        $remittanceData->receiverFirstName = $request->has('receiverFirstName') ? $input['receiverFirstName'] : '';
        $remittanceData->receiverLastName = $request->has('receiverLastName') ? $input['receiverLastName'] : '';
        $remittanceData->receiverBusinessName = $request->has('receiverBusinessName') ? $input['receiverBusinessName'] : '';
        $remittanceData->deliveryMethod = $request->has('deliveryMethod') ? $input['deliveryMethod'] : '';
        $remittanceData->bankName = $request->has('bankName') ? $input['bankName'] : '';
        $remittanceData->bankCountryCode = $request->has('bankCountryCode') ? $input['bankCountryCode'] : '';
        $remittanceData->codeBank = $request->has('codeBank') ? $input['codeBank'] : '';
        $remittanceData->codeAgence = $request->has('codeAgence') ? $input['codeAgence'] : '';
        $remittanceData->numeroDeCompte = $request->has('numeroDeCompte') ? $input['numeroDeCompte'] : '';
        $remittanceData->cleRib = $request->has('cleRib') ? $input['cleRib'] : '';
        $remittanceData->transactionDescription = $request->has('transactionDescription') ? $input['transactionDescription'] : '';
        $remittanceData->transactionSourceAmount = $request->has('transactionSourceAmount') ? $input['transactionSourceAmount'] : '';
        $remittanceData->sourceCurrency = $request->has('sourceCurrency') ? $input['sourceCurrency'] : '';
        $remittanceData->transactionTargetAmount = $request->has('transactionTargetAmount') ? $input['transactionTargetAmount'] : '';
        $remittanceData->targetCurrency = $request->has('targetCurrency') ? $input['targetCurrency'] : '';

        $remittanceData->product = $request->has('product') ? $input['product'] : '';
        $remittanceData->iban = $request->has('iban') ? $input['iban'] : '';
        $remittanceData->titleAccount = $request->has('titleAccount') ? $input['titleAccount'] : '';
        $remittanceData->amount = $request->has('amount') ? $input['amount'] : '';
        $remittanceData->partnerreference = $request->has('partnerreference') ? $input['partnerreference'] : '';
        $remittanceData->reason = $request->has('reason') ? $input['reason'] : '';

        // Save the data to the database
        $remittanceData->save();
        return response()->json(['status' => 'Success', 'message' => 'Data saved successfully', 'transactionId' => $remittanceData->transactionId], 200);
    }

    public function walletTransfer(Request $request)
    {

        $input = Input::all();

        $validate_data = [
            'senderType' => 'required|in:I,C',
            'firstName' => $request->input('senderType') == 'I' ? 'required_if:senderType,I|string|max:255' : 'nullable',
            'lastName' => $request->input('senderType') == 'I' ? 'required_if:senderType,I|string|max:255' : 'nullable',
            'idType' => $request->input('senderType') == 'I' ? 'required_if:senderType,I|string|max:255' : 'nullable',
            'idNumber' => $request->input('senderType') == 'I' ? 'required_if:senderType,I|string|max:255' : 'nullable',
            'businessName' => $request->input('senderType') == 'C' ? 'required_if:senderType,C|string|max:255' : 'nullable',
            'senderPhoneNumber' => 'required|string|max:20',
            'senderAddress' => 'required|string|max:255',
            'receiverType' => 'required|in:I,C',
            'receiverFirstName' => $request->input('receiverType') == 'I' ? 'required_if:receiverType,I|string|max:255' : 'nullable',
            'receiverLastName' => $request->input('receiverType') == 'I' ? 'required_if:receiverType,I|string|max:255' : 'nullable',
            'receiverBusinessName' => $request->input('receiverType') == 'C' ? 'required_if:receiverType,C|string|max:255' : 'nullable',
            'deliveryMethod' => 'required|string|max:255',
            'walletSource' => 'required|string|max:255',
            'walletDestination' => 'required|string|max:255', // Adjusted max length to 4 based on the provided examples
            'walletManager' => 'required|string|max:255',
            'transactionDescription' => 'required|string|max:255',
            'transactionSourceAmount' => 'required|numeric',
            'sourceCurrency' => 'required|string|max:3',
            'transactionTargetAmount' => 'required|numeric',
            'targetCurrency' => 'required|string|max:3',
        ];

        $customMessages = [
            'senderType.required' => 'The sender type field is required.',
            'senderType.in' => 'The sender type must be either "I" or "C".',
            'firstName.required' => 'The first name field is required.',
            'firstName.string' => 'The first name must be a string.',
            'firstName.max' => 'The first name must not exceed 255 characters.',
            'lastName.required' => 'The last name field is required.',
            'lastName.string' => 'The last name must be a string.',
            'lastName.max' => 'The last name must not exceed 255 characters.',
            'businessName.required' => 'The business name field is required.',
            'businessName.string' => 'The business name must be a string.',
            'businessName.max' => 'The business name must not exceed 255 characters.',
            'idType.required' => 'The ID type field is required.',
            'idType.string' => 'The ID type must be a string.',
            'idType.max' => 'The ID type must not exceed 255 characters.',
            'idNumber.required' => 'The ID number field is required.',
            'idNumber.string' => 'The ID number must be a string.',
            'idNumber.max' => 'The ID number must not exceed 255 characters.',
            'senderPhoneNumber.required' => 'The sender phone number field is required.',
            'senderPhoneNumber.string' => 'The sender phone number must be a string.',
            'senderPhoneNumber.max' => 'The sender phone number must not exceed 20 characters.',
            'senderAddress.required' => 'The sender address field is required.',
            'senderAddress.string' => 'The sender address must be a string.',
            'senderAddress.max' => 'The sender address must not exceed 255 characters.',
            'receiverType.required' => 'The receiver type field is required.',
            'receiverType.string' => 'The receiver type must be a string.',
            'receiverType.max' => 'The receiver type must not exceed 255 characters.',
            'receiverFirstName.required' => 'The receiver first name field is required.',
            'receiverFirstName.string' => 'The receiver first name must be a string.',
            'receiverFirstName.max' => 'The receiver first name must not exceed 255 characters.',
            'receiverLastName.required' => 'The receiver last name field is required.',
            'receiverLastName.string' => 'The receiver last name must be a string.',
            'receiverLastName.max' => 'The receiver last name must not exceed 255 characters.',
            'deliveryMethod.required' => 'The delivery method field is required.',
            'deliveryMethod.string' => 'The delivery method must be a string.',
            'deliveryMethod.max' => 'The delivery method must not exceed 255 characters.',
            'walletSource.required' => 'The bank name field is required.',
            'walletSource.string' => 'The bank name must be a string.',
            'walletSource.max' => 'The bank name must not exceed 255 characters.',
            'walletDestination.required' => 'The bank country code field is required.',
            'walletDestination.string' => 'The bank country code must be a string.',
            'walletDestination.max' => 'The bank country code must not exceed 255 characters.',
            'walletManager.required' => 'The code bank field is required.',
            'walletManager.string' => 'The code bank must be a string.',
            'walletManager.max' => 'The code bank must not exceed 255 characters.',
            'transactionDescription.required' => 'The transaction description field is required.',
            'transactionDescription.string' => 'The transaction description must be a string.',
            'transactionDescription.max' => 'The transaction description must not exceed 255 characters.',
            'transactionSourceAmount.required' => 'The transaction source amount field is required.',
            'transactionSourceAmount.numeric' => 'The transaction source amount must be a number.',
            'sourceCurrency.required' => 'The source currency field is required.',
            'sourceCurrency.string' => 'The source currency must be a string.',
            'sourceCurrency.max' => 'The source currency must not exceed 3 characters.',
            'transactionTargetAmount.required' => 'The transaction target amount field is required.',
            'transactionTargetAmount.numeric' => 'The transaction target amount must be a number.',
            'targetCurrency.required' => 'The target currency field is required.',
            'targetCurrency.string' => 'The target currency must be a string.',
            'targetCurrency.max' => 'The target currency must not exceed 3 characters.',
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            return response()->json($statusArr, 200);
        }

        $remittanceData = new RemittanceData();
        $remittanceData->transactionId = $this->generateAndCheckUnique();
        $remittanceData->senderType = $input['senderType'];
        $remittanceData->firstName = $input['firstName'];
        $remittanceData->lastName = $input['lastName'];
        $remittanceData->businessName = $input['businessName'];
        $remittanceData->idType = $input['idType'];
        $remittanceData->idNumber = $input['idNumber'];
        $remittanceData->senderPhoneNumber = $input['senderPhoneNumber'];
        $remittanceData->senderAddress = $input['senderAddress'];
        $remittanceData->receiverType = $input['receiverType'];
        $remittanceData->receiverFirstName = $input['receiverFirstName'];
        $remittanceData->receiverLastName = $input['receiverLastName'];
        $remittanceData->receiverBusinessName = $input['receiverBusinessName'];
        $remittanceData->deliveryMethod = $input['deliveryMethod'];
        $remittanceData->walletSource = $input['walletSource'];
        $remittanceData->walletDestination = $input['walletDestination'];
        $remittanceData->walletManager = $input['walletManager'];
        $remittanceData->transactionDescription = $input['transactionDescription'];
        $remittanceData->transactionSourceAmount = $input['transactionSourceAmount'];
        $remittanceData->sourceCurrency = $input['sourceCurrency'];
        $remittanceData->transactionTargetAmount = $input['transactionTargetAmount'];
        $remittanceData->targetCurrency = $input['targetCurrency'];
        $remittanceData->type = 'wallet_transfer';
        // Save the data to the database
        $remittanceData->save();
        return response()->json(['status' => 'Success', 'message' => 'Data saved successfully', 'transactionId' => $remittanceData->transactionId], 200);
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

    public function getTransactionStatus(Request $request)
    {
        $input = Input::all();
        $validate_data = [
            'transactionId' => 'required',
        ];
        $customMessages = [
            'transactionId.required' => 'Transaction ID is mandatory',
        ];

        $validator = Validator::make($input, $validate_data, $customMessages);
        if ($validator->fails()) {
            $messages = $validator->messages();
            $statusArr = [
                "status" => "Failed",
                "reason" => implode('<br>', $messages->all()),
            ];
            return response()->json($statusArr, 200);
        }

        $transactionId = $input['transactionId'];

        $is_exist = RemittanceData::where('transactionId', $transactionId)->count();
        if ($is_exist == 0) {
            $statusArr = [
                "status" => "Failed",
                "reason" => 'Please provide a valid transaction ID',
            ];
            return response()->json($statusArr, 200);
        }

        $record = RemittanceData::where('transactionId', $transactionId)->first();

        return response()->json(['transactionId' => $record->transactionId, 'status' => $record->status], 200);
    }

    public function bdaTransfer(Request $request)
    {

        $input = Input::all();
        //        echo '<pre>';print_r($input);
        $data = $input;

        $certificate = public_path("CA Bundle.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
        try {
            $response = $client->post('https://apps.bda-net.ci/transfert/v2.0/lots', [
                'json' => $data,
                'headers' => [
                    'x-api-key' => 'RZdJqzjrkVoapWaRCjGmIRUFsjnp7OVE8xKFP1EX+aq/dhdza8qyOEBmN7GP+S2oWw7GRv17ZKizhZp0/C8cbE1rQCcHQg3Wk0JZVBH/bjrMCyhUcd0h1YM5sHE/6OFQv3Q9mv/rLz/vhercH8lLMuqoF73Wc7B2ECdvej5/W5Eg/CmEEeMjhXrTw2N/ZWd9JKzNNLXT7uh7HU24r9WuHmKBYlADzCCgzY3eT5IYeTaW5NF+d34kUIY6wttCOJvk',
                    'x-client-id' => 'a1ccdfb1-400a-4d20-ac93-7bd148da0957',
                ],
            ]);

            /* $response = $client->post('https://survey-apps.bda-net.ci/transfert/', [
                'json' => $data,
                'headers' => [
                    'x-api-key' => 'RZdJqzjrkVoapWaRCjGmIUDJLgQPSqXAAaJ8y3ne/dvGoSzzFdJz6T0R0cRazL4wSyExYteJEHu4Xh3DhCMoguG9rlBFfVI+yx8fWtYLdpYv/vO3IdqHeOco+jKI3CrZNmWPlwWZVfqkNZqEaXEfCRBC0L30mrn2mXcQMfveaHmWUN0OeaPbWWS2Cgd34+cj7Qay29jkKbihNiIAPunatQ==',
                    'x-client-id' => '7766694c-3bb2-4f35-ab50-2b9a34d95ba6',
                ],
            ]); */
            

            $responseBody = json_decode($response->getBody(), true);
            return $responseBody;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function bdaTransferTest(Request $request)
    {

        $input = Input::all();
        //        echo '<pre>';print_r($input);
        $data = $input;

        $certificate = public_path("CA Bundle.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
        try {
            $response = $client->post('https://survey-apps.bda-net.ci/transfert/v2.0/virements', [
                'json' => $data,
                'headers' => [
                    'x-api-key' => 'RZdJqzjrkVoapWaRCjGmIQkukdOL8e39JQrmW+gH9B+DIjnJbEh1AmUV26OLPAjblWS8jkjAo9j6pMHJOx/sMoPtkB32ha/brVKNJrT3++Qpu+qFa1T2mPVGqKgeGUOGM1QxU71Ts0xnsGpq7IQfX2IA3YGYnJhS8fD+Ggvf2N4KHz9qH6+/Yuj9lxtUNyEN1x57YFkogOjPLqvgdfVk3fbl4p5UgxZyEF+RUiPojpsgsMPfM3dewwd7ysgwlzLv',
                    'x-client-id' => '9ca1a01c-a55a-4c1c-a5b9-ec09b5aea768',
                ],
            ]);
            $responseBody = json_decode($response->getBody(), true);
            return $responseBody;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function bdaTransferStatus(Request $request)
    {

        //        $input = Input::all();
//        echo '<pre>';print_r($input);
//        $data = $input;

        $certificate = public_path("CA Bundle.crt");
        $client = new Client([
            'verify' => $certificate,
        ]);
        try {
            $response = $client->get('https://apps.bda-net.ci/transfert/v2.0/lots/SWAP8611', [
                'headers' => [
                    'x-api-key' => 'RZdJqzjrkVoapWaRCjGmIRUFsjnp7OVE8xKFP1EX+aq/dhdza8qyOEBmN7GP+S2oWw7GRv17ZKizhZp0/C8cbE1rQCcHQg3Wk0JZVBH/bjrMCyhUcd0h1YM5sHE/6OFQv3Q9mv/rLz/vhercH8lLMuqoF73Wc7B2ECdvej5/W5Eg/CmEEeMjhXrTw2N/ZWd9JKzNNLXT7uh7HU24r9WuHmKBYlADzCCgzY3eT5IYeTaW5NF+d34kUIY6wttCOJvk',
                    'x-client-id' => 'a1ccdfb1-400a-4d20-ac93-7bd148da0957',
                ],
            ]);
            $responseBody = json_decode($response->getBody(), true);
            return $responseBody;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

}
