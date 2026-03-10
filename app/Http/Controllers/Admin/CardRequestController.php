<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CardRequest;
use Session;
use Redirect;
use Validator;
use App\Models\User;
use App\Models\UserCard;
use DateTime;
use DateTimeZone;
use App\Services\CardService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;

class CardRequestController extends Controller
{
    public $cardService;

    public function __construct(CardService $cardService)
    {
        $this->middleware('is_adminlogin');
        $this->cardService = $cardService;
    }

    public function cardRequestList(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'card-request-list');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Card Request List';
        $activetab = 'actcardrequestlist';
        $query = CardRequest::with(['user', 'userCard' => function ($q) {
        $q->where('cardType', 'PHYSICAL');}])->sortable();

        if ($request->has('keyword')) {
            $keyword = $request->keyword;

            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                    ->orWhere('lastName', 'like', "%$keyword%")
                    ->orWhere('phone', 'like', "%$keyword%")
                    ->orWhere('email', 'like', "%$keyword%");
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";
            $query = $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        // dd($query->get());
        $companies = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->input('page')) {
            $page = $request->get('page');
        } else {
            $page = 1;
        } 
        if ($request->ajax() || $page > 1) {
            return view('elements.admin.card-request.list', ['allrecords' => $companies, 'page' => $page]);
        }
        return view('admin.card-request.list', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $companies, 'page' => $page]);
    }

    public function cardAssignOld(Request $request, $id)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'card-assign');
        if (!$isPermitted) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, 'activetab' => $activetab]);
        }

        $pageTitle = 'Edit Card Assign';
        $activetab = 'actcardassignlist';
        $recordInfo = CardRequest::with('user')->where('id', $id)->first();

        if (!$recordInfo) {
            Session::flash('error_message', "Record not found!");
            return Redirect::back();
        }
        $user = User::where('id', $recordInfo->user_id)->first();
        if (!$user) {
            Session::flash('error_message', "User not found!");
            return Redirect::back();
        }

        if (!$user->selfie_image || !$user->identity_front_image || !$user->identity_back_image) {
            $dt = new DateTime('now', new DateTimeZone('UTC'));
            $currentTimestamp = $dt->format("Y-m-d\TH:i:s.v\Z");
            $api_key = SMILE_API_KEY;
            $partner_id = SMILE_PARTNER_ID;
            global $getStateId;
            $message = $currentTimestamp . $partner_id . "sid_request";
            $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));


            $userSlug = $user->unique_key;
            $userJobId = $user->jobId;
            $user_id = $user->id;
            $curl = curl_init();
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
                         "image_links": true,
                         "history": false
                    }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $responseData = json_decode($response);
            if (isset($responseData->image_links) && $responseData->image_links == true) {
                $user->selfie_image = $responseData->image_links->selfie_image ?? null;
                $user->identity_front_image = $responseData->image_links->id_card_image ?? null;
                $user->identity_back_image = $responseData->image_links->id_card_back ?? null;
                $user->save();
            }
        }

        if ($request->isMethod('post')) {
            $input = $request->all();
            $rules = [
                'accountId' => 'required|numeric|digits_between:7,15',
                'last4Digits' => 'required|digits:4',
            ];

            $customMessages = [
                'accountId.required' => 'Account ID is required.',
                'accountId.numeric' => 'Account ID must contain only numbers.',
                'accountId.digits_between' => 'Account ID must be between 7 and 12 digits.',



                'last4Digits.required' => 'Last 4 digits are required.',
                'last4Digits.digits' => 'Last 4 digits must be exactly 4 digits.',
            ];

            $validator = Validator::make($input, $rules, $customMessages);

            if ($validator->fails()) {
                return Redirect::back()->withErrors($validator)->withInput();
            }

            if ($user->kyc_status == "completed") {
                if ($user->requestReplaceCard == "REPLACEPHYSICAL") {
                    $postData = json_encode([
                        "chargeFee" => false,
                        "last4" => $user->last4Digits,
                        "replaceDueToFraud" => true,
                        "replacementCardType" => "Physical",
                        "newAccountId" => $input['accountId'],
                    ]);

                    $getResponse = $this->cardService->replaceCardService($postData, $user->accountId);
                    Log::info('Replace Card Response: ', ['response' => $getResponse, 'account' => $user->accountId]);

                    if (isset($getResponse) && $getResponse['status'] == false) {
                        Session::flash('error_message', 'Card replace failed at API level ' . $getResponse['message']);
                        Log::info('Physical Card Creation Error: ', ['response' => $getResponse]);
                        return Redirect::to('admin/card-request/list');

                    }
                } else {
                    global $getStateId;
                    $countryVal = $getStateId[$user->country] ?? 0;
                    $postData = json_encode([
                        "accountId" => $input['accountId'],
                        "accountSource" => "OTHER",
                        "address1" => $user->address1,
                        "birthDate" => strtoupper(Carbon::parse($user->dob)->format('d-M-Y')),
                        "city" => DB::table('province_city')->where('id', $user->city)->first()->name,
                        "country" => "GA",
                        "emailAddress" => !empty($user->email) ? $user->email : "test@mailinator.com",
                        "firstName" => "{$user->name}",
                        "idType" => "1",
                        "idValue" => $user->national_identity_number,
                        "lastName" => "{$user->lastName}",
                        "mobilePhoneNumber" => [
                            "countryCode" => "241",
                            "number" => $user->phone
                        ],
                        "preferredName" => $user->name . ' ' . $user->lastName,
                        "stateRegion" => $countryVal,
                        "subCompany" => ONAFRIQ_SUBCOMPANY_PHY
                    ]);

                    $getResponse = $this->cardService->saveCardPhysical($postData);
                    if ($getResponse['status'] === false) {
                        Session::flash('error_message', 'Card creation failed at API level ' . $getResponse['data']['detail']);
                        Log::info('Physical Card Creation Error: ', ['response' => $getResponse]);
                        return Redirect::to('admin/card-request/list');
                    }
                    Log::info('Physical Card Creation Response: ', ['response' => $getResponse, 'account' => $input['accountId']]);
                }


                $getCardBalance = $this->cardService->getCardBalance($user->accountId, $user->cardType);
                if (isset($getCardBalance) && $getCardBalance['data']['balance'] > 0) {
                    $postData = json_encode([
                        "currencyCode" => "XAF",
                        "last4Digits" => $user->last4Digits,
                        "referenceMemo" => "Card to wallet",
                        "transferAmount" => $getCardBalance['data']['balance'],
                        "transferType" => "CardToWallet",
                        "mobilePhoneNumber" => "241{$user->phone}",
                    ]);
                    $this->cardService->addWalletCardTopUp($postData, $user->accountId, $user->cardType);
                    User::where(['id' => $user->id, 'accountId' => $user->accountId])->increment('wallet_balance', $getCardBalance['data']['balance']);

                    $postDeactive = json_encode([
                        "chargeFee" => false,
                        "last4Digits" => $user->last4Digits,
                        "mobilePhoneNumber" => "241$user->phone",
                        "newCardStatus" => "Deactivated",
                    ]);

                    $getResponse = $this->cardService->cardDeactivate($postDeactive, $user->accountId, $user->cardType);
                    Log::info(['$getResponse' => $getResponse]);
                }
                if ($user->requestReplaceCard == "REPLACEPHYSICAL") {

                    User::where('id', $user->id)->update([
                        'accountId' => $input['accountId'] ?? null,
                        'last4Digits' => $input['last4Digits'] ?? null,
                        'cardType' => 'REPLACEPHYSICAL',
                        'alreadyReplace' => 'REPLACECARD'
                    ]);
                } else {

                    $user->cardType = "PHYSICAL";
                    $user->accountId = $input['accountId'] ?? null;
                    $user->last4Digits = $input['last4Digits'] ?? null;
                    $user->save();
                    $recordInfo->status = 1;
                    $recordInfo->save();
                }

                Session::flash('success_message', "Card request approved successfully.");
                return Redirect::to('admin/card-request/list');
            } else {
                Session::flash('error_message', "Your KYC not completed.");
                return Redirect::to('admin/card-request/list');
            }

        }

        return view('admin.card-request.card-assign', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo, 'userInfo' => $user, 'imageData' => $responseData->image_links ?? null]);
    }

    public function cardAssign(Request $request, $id)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'card-assign');
        if (!$isPermitted) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actconfigrole';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, 'activetab' => $activetab]);
        }

        $pageTitle = 'Edit Card Assign';
        $activetab = 'actcardassignlist';
        $recordInfo = CardRequest::with('user')->where('id', $id)->first();

        if (!$recordInfo) {
            Session::flash('error_message', "Record not found!");
            return Redirect::back();
        }
        $user = User::where('id', $recordInfo->user_id)->first();
        if (!$user) {
            Session::flash('error_message', "User not found!");
            return Redirect::back();
        }
// dd($user);
        if ($user->selfie_image || $user->identity_front_image || $user->identity_back_image) {
            
            $dt = new DateTime('now', new DateTimeZone('UTC'));
            $currentTimestamp = $dt->format("Y-m-d\TH:i:s.v\Z");
            $api_key = SMILE_API_KEY;
            $partner_id = SMILE_PARTNER_ID;
            global $getStateId;
            $message = $currentTimestamp . $partner_id . "sid_request";
            $signature = base64_encode(hash_hmac('sha256', $message, $api_key, true));


            $userSlug = $user->unique_key;
            $userJobId = $user->jobId;
            $user_id = $user->id;
            $curl = curl_init();
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
                         "image_links": true,
                         "history": false
                    }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $responseData = json_decode($response);
            if (isset($responseData->image_links) && $responseData->image_links == true) {
                $user->selfie_image = $responseData->image_links->selfie_image ?? null;
                $user->identity_front_image = $responseData->image_links->id_card_image ?? null;
                $user->identity_back_image = $responseData->image_links->id_card_back ?? null;
                $user->save();
            }
        }

        if ($request->isMethod('post')) {
            $input = $request->all();
            $rules = [
                'accountId' => 'required|numeric|digits_between:7,15',
                'last4Digits' => 'required|digits:4',
            ];

            $customMessages = [
                'accountId.required' => 'Account ID is required.',
                'accountId.numeric' => 'Account ID must contain only numbers.',
                'accountId.digits_between' => 'Account ID must be between 7 and 12 digits.',
                'last4Digits.required' => 'Last 4 digits are required.',
                'last4Digits.digits' => 'Last 4 digits must be exactly 4 digits.',
            ];

            $validator = Validator::make($input, $rules, $customMessages);

            if ($validator->fails()) {
                return Redirect::back()->withErrors($validator)->withInput();
            }

            if ($user->kyc_status == "completed") {
                if ($user->requestReplaceCard == "REPLACEPHYSICAL") {
                    $postData = json_encode([
                        "chargeFee" => false,
                        "last4" => $user->last4Digits,
                        "replaceDueToFraud" => true,
                        "replacementCardType" => "Physical",
                        "newAccountId" => $input['accountId'],
                    ]);

                    $getResponse = $this->cardService->replaceCardService($postData, $user->accountId);
                    Log::info('Replace Card Response: ', ['response' => $getResponse, 'account' => $user->accountId]);

                    if (isset($getResponse) && $getResponse['status'] == false) {
                        Session::flash('error_message', 'Card replace failed at API level ' . $getResponse['message']);
                        Log::info('Physical Card Creation Error: ', ['response' => $getResponse]);
                        return Redirect::to('admin/card-request/list');

                    }
                } else {
                    global $getStateId;
                    $countryVal = $getStateId[$user->country] ?? 0;
                    $postData = json_encode([
                        "accountId" => $input['accountId'],
                        "accountSource" => "OTHER",
                        "address1" => $user->address1,
                        "birthDate" => strtoupper(Carbon::parse($user->dob)->format('d-M-Y')),
                        "city" => DB::table('province_city')->where('id', $user->city)->first()->name,
                        "country" => "GA",
                        "emailAddress" => !empty($user->email) ? $user->email : "test@mailinator.com",
                        "firstName" => "{$user->name}",
                        "idType" => "1",
                        "idValue" => $user->national_identity_number,
                        "lastName" => "{$user->lastName}",
                        "mobilePhoneNumber" => [
                            "countryCode" => "241",
                            "number" => $user->phone
                        ],
                        "preferredName" => $user->name . ' ' . $user->lastName,
                        "stateRegion" => $countryVal,
                        "subCompany" => ONAFRIQ_SUBCOMPANY_PHY
                    ]);

                    $getResponse = $this->cardService->saveCardPhysical($postData);
                    if ($getResponse['status'] === false) {
                        Session::flash('error_message', 'Card creation failed at API level ' . $getResponse['data']['detail']);
                        Log::info('Physical Card Creation Error: ', ['response' => $getResponse]);
                        return Redirect::to('admin/card-request/list');
                    }
                    Log::info('Physical Card Creation Response: ', ['response' => $getResponse, 'account' => $input['accountId']]);
                }


                if ($user->requestReplaceCard == "REPLACEPHYSICAL") {
                    $getCardBalance = $this->cardService->getCardBalance($user->accountId, $user->cardType);
                    if (isset($getCardBalance) && $getCardBalance['data']['balance'] > 0) {
                        $postData = json_encode([
                            "currencyCode" => "XAF",
                            "last4Digits" => $user->last4Digits,
                            "referenceMemo" => "Card to wallet",
                            "transferAmount" => $getCardBalance['data']['balance'],
                            "transferType" => "CardToWallet",
                            "mobilePhoneNumber" => "241{$user->phone}",
                        ]);
                        $this->cardService->addWalletCardTopUp($postData, $user->accountId, $user->cardType);

                        User::where(['id' => $user->id, 'accountId' => $user->accountId])->increment('wallet_balance', $getCardBalance['data']['balance']);
                        Log::info(['Physical Card Amount detduct after deactivate'=>$getCardBalance['data']['balance']]);

                        $postDeactive = json_encode([
                            "chargeFee" => false,
                            "last4Digits" => $user->last4Digits,
                            "mobilePhoneNumber" => "241$user->phone",
                            "newCardStatus" => "Deactivated",
                        ]);

                        $getResponse = $this->cardService->cardDeactivate($postDeactive, $user->accountId, $user->cardType);
                        Log::info(['Physical Card Deactivated replace time' => $getResponse]);
                    }
                }
                if ($user->requestReplaceCard == "REPLACEPHYSICAL") {

                    User::where('id', $user->id)->update([
                        'accountId' => $input['accountId'] ?? null,
                        'last4Digits' => $input['last4Digits'] ?? null,
                        'cardType' => 'REPLACEPHYSICAL',
                        'alreadyReplace' => 'REPLACECARD'
                    ]);


                    UserCard::where('userId', $user->id)->where('cardType', "PHYSICAL")->update([
                        'accountId' => $input['accountId'] ?? null,
                        'last4Digits' => $input['last4Digits'] ?? null,
                        'cardType' => 'PHYSICAL'
                    ]);

                } else {

                    $user->cardType = "PHYSICAL";
                    $user->accountId = $input['accountId'] ?? null;
                    $user->last4Digits = $input['last4Digits'] ?? null;
                    $user->save();
                    $recordInfo->status = 1;
                    $recordInfo->save();

                    UserCard::create([
                        'userId' => $user->id,
                        'accountId' => $input['accountId'] ?? null,
                        'last4Digits' => $input['last4Digits'] ?? null,
                        'cardType' => 'PHYSICAL'
                    ]);
                }

                Session::flash('success_message', "Card request approved successfully.");
                return Redirect::to('admin/card-request/list');
            } else {
                Session::flash('error_message', "Your KYC not completed.");
                return Redirect::to('admin/card-request/list');
            }

        }

        return view('admin.card-request.card-assign', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo, 'userInfo' => $user, 'imageData' => $responseData->image_links ?? null]);
    }
}
