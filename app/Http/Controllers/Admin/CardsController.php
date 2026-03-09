<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Cookie;
use Session;
use Redirect;
use Input;
use Validator;
use DB;
use IsAdmin;
use App\Models\Card;
use App\Models\Carddetail;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Admin;
use Mail;
use App\Mail\SendMailable;

use App\Exports\BulkExport;
use App\Imports\BulkImport;
use Maatwebsite\Excel\Facades\Excel;

class CardsController extends Controller {

    public function __construct() {
        $this->middleware('is_adminlogin');
    }

    public function importCards(Request $request,$cslug = null){
        ini_set('memory_limit', -1);
        $access = $this->getRoles(Session::get('adminid'), 7);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $pageTitle = 'Add Card';
        $activetab = 'actcards';

        $recordInfo = Card::where('slug', $cslug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/cards');
        }

        $currencyList = Currency::getCurrencyList();

        $input = Input::all();
        if (!empty($input)) { 
            $rules = array(
                'csv_file' => 'required|mimes:xls',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cards/importcards/'.$cslug)->withErrors($validator)->withInput();
            } else {

                // if (Input::hasFile('csv_file')) {
                //     $file = Input::file('csv_file');
                //     $uploadedFileName = $this->uploadImage($file, DOCUMENT_UPLOAD_PATH);
                //     // $this->resizeImage($uploadedFileName, COMPANY_FULL_UPLOAD_PATH, COMPANY_SMALL_UPLOAD_PATH, COMPANY_MW, COMPANY_MH);
                //     //$input['company_image'] = $uploadedFileName;
                // }

         //        $delimiter =",";
         // $f = fopen('php://memory', 'w'); //Save report in csv
         // header('Content-Type: text/csv');
         // $filename = "transfer-report-".date('d-m-Y H:i:s').".xls";
         // header('Content-Disposition: attachment; filename="'.$uploadedFileName.'";');

         // $target_file = DOCUMENT_UPLOAD_PATH.'/'.$uploadedFileName;
         
         // //ob_end_clean();
         // // ob_start();
         // $line = array("Trans. ID","Name","Amount","Currency","Reference ID","Status");
         // fputcsv($f, $line, $delimiter); //save report csv
         
         // $csv = Excel::toArray(new Importer, target_file);

         // $data = Excel::toArray(new BulkImport,request()->file('file'));
                // $data = (new BulkImport)->collection(request()->file('csv_file'));

                Session::put('excel_card', $recordInfo->id);
                $data = Excel::import(new BulkImport, request()->file('csv_file'));

                // $data = Excel::import(request()->file('csv_file'), null, \Maatwebsite\Excel\Excel::XLS);
                // $data = Excel::raw(request()->file('csv_file'), Excel::XLSX);

     // $data = Excel::import($path);
                 
//         // $csv = array_map('str_getcsv', file($target_file));
//exit;
//$errorFinal = '';
//        for ($i=1;$i<Count($csv);$i++){
//
//            $error = '';
//            $input = array();
//            if($csv[$i][0] == ''){
//                $error .='Serial Code is required field for row '.$i;
//                
//            } else if($csv[$i][1] == ''){
//                $error .='Pin Number is required field for row '.$i;
//            } else if($csv[$i][2] == ''){
//                $error .='Currency For Value is required field for row '.$i;
//            } else if($csv[$i][3] == ''){
//                $error .='Value is required field for row '.$i;
//            } else if($csv[$i][4] == ''){
//                $error .='Card Cost (IQD) is required field for row '.$i;
//            } else if($csv[$i][5] == ''){
//                $error .='Card Cost For Agent (IQD) is required field for row '.$i;
//            } else if($csv[$i][6] == ''){
//                $error .='Instruction is required field for row '.$i;
//                
//            }
//
//if(!in_array(strtoupper($csv[$i][2]), $currencyList)){
//                $error .='Currency For Value is invalid for row '.$i;
//            }
//
//            if(!is_numeric($csv[$i][3])){
//                $error .='Value should be numeric value for row '.$i;
//            }
//            if(!is_numeric($csv[$i][4])){
//                $error .='Card Cost (IQD) should be numeric value for row '.$i;
//            }
//            if(!is_numeric($csv[$i][5])){
//                $error .='Card Cost For Agent (IQD) should be numeric value for row '.$i;
//            }
//
//            if(!empty($error)){
//                $errorFinal .=$error.'<br>';
//                continue;
//            } else{
//
//$input['card_id'] = $recordInfo->id;
//                $input['serial_number'] = $csv[$i][0];
//                $input['pin_number'] = $csv[$i][1];
//                $input['currency'] = $csv[$i][2];
//                $input['real_value'] = $csv[$i][3];
//                $input['card_value'] = $csv[$i][4];
//                $input['agent_card_value'] = $csv[$i][5];
//                $input['instruction'] = $csv[$i][6];
//
//                $serialisedData = $this->serialiseFormData($input);
//                $serialisedData['status'] = 1;
//                $serialisedData['last_updated_by'] = Session::get('adminid');
//                Carddetail::insert($serialisedData);
//
//            }
//
//
//
//        }

$excel_message = Session::get('excel_message');
        if(!empty($excel_message)){

            return Redirect::to('/admin/cards/importcards/'.$cslug)->withErrors($excel_message);
            Session::forget('excel_message');
        } else{
             Session::flash('success_message', "Card details saved successfully.");
         }

               
                return Redirect::to('/admin/cards/importcards/'.$cslug);
            }
        }
        return view('admin.cards.importCards', ['title' => $pageTitle, $activetab => 1,'cslug' => $cslug,'recordInfo' =>$recordInfo]);
    }

    public function index(Request $request) {
        //  dd($request);
        $access = $this->getRoles(Session::get('adminid'), 7);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $adminList = Admin::where('status', 1)->orderBy('username', 'ASC')->pluck('username', 'id')->all();

        $pageTitle = 'Manage Cards';
        $activetab = 'actcards';
        $query = new Card();
        $query = $query->sortable();

//        $query = $query->where('used_status',0);

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                Card::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Card::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Card::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function($q) use ($keyword) {
                $q->where('company_name', 'like', '%' . $keyword . '%');
            });
        }

        
        if ($request->has('last_updated_by')) {
            $last_updated_by = $request->get('last_updated_by');
            $query = $query->where(function($q) use ($last_updated_by) {
                if ($last_updated_by != '') {
                    $q->where('last_updated_by', $last_updated_by);
                }
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";

            $query = $query->where(function($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        $cards = $query->orderBy('id', 'DESC')->paginate(20);
        
        if ($request->ajax()) {
            return view('elements.admin.cards.index', ['allrecords' => $cards]);
        }
      
        return view('admin.cards.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $cards, 'adminList' => $adminList]);
    }

    public function add() {
        $access = $this->getRoles(Session::get('adminid'), 7);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $pageTitle = 'Add Card';
        $activetab = 'actcards';

        $input = Input::all();
        if (!empty($input)) {
            $rules = array(
                'card_type' => 'required',
                'company_name' => 'required|max:50',
                'company_image' => 'required|mimes:jpeg,png,jpg',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cards/add')->withErrors($validator)->withInput();
            } else {

                if (Input::hasFile('company_image')) {
                    $file = Input::file('company_image');
                    $uploadedFileName = $this->uploadImage($file, COMPANY_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, COMPANY_FULL_UPLOAD_PATH, COMPANY_SMALL_UPLOAD_PATH, COMPANY_MW, COMPANY_MH);
                    $input['company_image'] = $uploadedFileName;
                } else {
                    unset($input['company_image']);
                }

                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['slug'] = $this->createSlug($input['company_name'], 'cards');
                $serialisedData['last_updated_by'] = Session::get('adminid');
                $serialisedData['status'] = 1;
                Card::insert($serialisedData);

                Session::flash('success_message', "Card details saved successfully.");
                return Redirect::to('admin/cards');
            }
        }
        return view('admin.cards.add', ['title' => $pageTitle, $activetab => 1]);
    }

    public function edit($slug = null) {
        $access = $this->getRoles(Session::get('adminid'), 7);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $pageTitle = 'Edit Card';
        $activetab = 'actcards';

        $recordInfo = Card::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/cards');
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'card_type' => 'required',
                'company_name' => 'required|max:50',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cards/edit/' . $slug)->withErrors($validator)->withInput();
            } else {

                if (Input::hasFile('company_image')) {
                    $file = Input::file('company_image');
                    $uploadedFileName = $this->uploadImage($file, COMPANY_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, COMPANY_FULL_UPLOAD_PATH, COMPANY_SMALL_UPLOAD_PATH, COMPANY_MW, COMPANY_MH);
                    $input['company_image'] = $uploadedFileName;
                    @unlink(COMPANY_FULL_UPLOAD_PATH . $recordInfo->company_image);
                } else {
                    unset($input['company_image']);
                }

                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                $serialisedData['last_updated_by'] = Session::get('adminid');
                Card::where('id', $recordInfo->id)->update($serialisedData);
                Session::flash('success_message', "card details updated successfully.");
                return Redirect::to('admin/cards');
            }
        }
        return view('admin.cards.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    public function activate($slug = null) {
        if ($slug) {
            Card::where('slug', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_status', ['action' => 'admin/cards/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null) {
        if ($slug) {
            Card::where('slug', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_status', ['action' => 'admin/cards/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null) {
        if ($slug) {
            Card::where('slug', $slug)->delete();
            Session::flash('success_message', "Card details deleted successfully.");
            return Redirect::to('admin/cards');
        }
    }

    public function carddetail($cslug = null, Request $request) {
        $access = $this->getRoles(Session::get('adminid'), 7);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $adminList = Admin::where('status', 1)->orderBy('username', 'ASC')->pluck('username', 'id')->all();

        $pageTitle = 'Manage Cards Details';
        $activetab = 'actcards';

        $cardInfo = Card::where('slug', $cslug)->first();

        $query = new Carddetail();
        $query = $query->sortable();
        $query = $query->where('card_id', $cardInfo->id);$query = $query->where('used_status',0);
        

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                Carddetail::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Carddetail::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Carddetail::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function($q) use ($keyword) {
                $q->where('serial_number', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('last_updated_by')) {
            $last_updated_by = $request->get('last_updated_by');
            $query = $query->where(function($q) use ($last_updated_by) {
                if ($last_updated_by != '') {
                    $q->where('last_updated_by', $last_updated_by);
                }
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";

            $query = $query->where(function($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        $cards = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->ajax()) {
            return view('elements.admin.cards.carddetail', ['allrecords' => $cards, 'cslug' => $cslug]);
        }
        return view('admin.cards.carddetail', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $cards, 'cslug' => $cslug, 'adminList' => $adminList]);
    }

    public function addcarddetail($cslug = null) {
        $access = $this->getRoles(Session::get('adminid'), 7);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $currencyList = Currency::getCurrencyList();

        $pageTitle = 'Add Card Detail';
        $activetab = 'actcards';

        $cardInfo = Card::where('slug', $cslug)->first();
        if (empty($cardInfo)) {
            return Redirect::to('admin/cards/');
        }
        $input = Input::all();
        if (!empty($input)) {
            $rules = array(
                'serial_number' => 'required',
                'pin_number' => 'required',
                'card_value' => 'required',
                'instruction' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cards/addcarddetail')->withErrors($validator)->withInput();
            } else {

                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['card_id'] = $cardInfo->id;
                $serialisedData['status'] = 1;
                $serialisedData['last_updated_by'] = Session::get('adminid');
                Carddetail::insert($serialisedData);

                Session::flash('success_message', "Card details saved successfully.");
                return Redirect::to('admin/cards/carddetail/' . $cslug);
            }
        }
        return view('admin.cards.addcarddetail', ['title' => $pageTitle, $activetab => 1, 'cslug' => $cslug, 'currencyList' => $currencyList]);
    }

    public function editcarddetail($cslug = null, $id = null) {
        $access = $this->getRoles(Session::get('adminid'), 7);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $currencyList = Currency::getCurrencyList();

        $pageTitle = 'Edit Card';
        $activetab = 'actcards';

        $recordInfo = Carddetail::where('id', $id)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/cards/carddetail/' . $cslug);
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'serial_number' => 'required',
                'pin_number' => 'required',
                'card_value' => 'required',
                'instruction' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cards/editcarddetail/' . $cslug . '/' . $id)->withErrors($validator)->withInput();
            } else {

                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                $serialisedData['last_updated_by'] = Session::get('adminid');
                Carddetail::where('id', $recordInfo->id)->update($serialisedData);
                Session::flash('success_message', "Card details updated successfully.");
                return Redirect::to('admin/cards/carddetail/' . $cslug);
            }
        }
        return view('admin.cards.editcarddetail', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo, 'cslug' => $cslug, 'currencyList' => $currencyList]);
    }

    public function activatecarddetail($slug = null) {
        if ($slug) {
            Carddetail::where('id', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_status', ['action' => 'admin/cards/deactivatecarddetail/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivatecarddetail($slug = null) {
        if ($slug) {
            Carddetail::where('id', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_status', ['action' => 'admin/cards/activatecarddetail/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function deletecarddetail($cslug = null, $slug = null) {
        if ($slug) {
            Carddetail::where('id', $slug)->delete();
            Session::flash('success_message', "Card details deleted successfully.");
            return Redirect::to('admin/cards/carddetail/' . $cslug);
        }
    }

    public function cardoffers($cslug = null, $cid = null, Request $request) {
        $access = $this->getRoles(Session::get('adminid'), 9);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $pageTitle = 'Manage Offers List';
        $activetab = 'actcards';

        $cardInfo = Card::where('slug', $cslug)->first();

        $query = new Carddetail();
        $query = $query->sortable();
        $query = $query->where('card_id', $cardInfo->id);

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                Carddetail::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Carddetail::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Carddetail::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function($q) use ($keyword) {
                $q->where('serial_number', 'like', '%' . $keyword . '%');
            });
        }

        $cards = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->ajax()) {
            return view('elements.admin.cards.cardoffers', ['allrecords' => $cards, 'cslug' => $cslug, 'cid' => $cid]);
        }
        return view('admin.cards.cardoffers', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $cards, 'cslug' => $cslug, 'cid' => $cid]);
    }

    public function addcardoffer($cslug = null, $cid = null) {
        $access = $this->getRoles(Session::get('adminid'), 9);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $pageTitle = 'Add Card Detail';
        $activetab = 'actcards';

        $cardInfo = Card::where('slug', $cslug)->first();

        $input = Input::all();
        if (!empty($input)) {
            $rules = array(
                'serial_number' => 'required',
                'pin_number' => 'required',
                'card_value' => 'required',
                'instruction' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cards/addcardoffer')->withErrors($validator)->withInput();
            } else {

                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['card_id'] = $cardInfo->id;
                $serialisedData['status'] = 1;
                Carddetail::insert($serialisedData);

                Session::flash('success_message', "Card details saved successfully.");
                return Redirect::to('admin/cards/cardoffers/' . $cslug . '/' . $cid);
            }
        }
        return view('admin.cards.addcardoffer', ['title' => $pageTitle, $activetab => 1, 'cslug' => $cslug, 'cid' => $cid]);
    }

    public function editcardoffer($cslug = null, $id = null) {
        $access = $this->getRoles(Session::get('adminid'), 9);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $pageTitle = 'Edit Card';
        $activetab = 'actcards';

        $recordInfo = Carddetail::where('id', $id)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/cards/carddetail/' . $cslug);
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'serial_number' => 'required',
                'pin_number' => 'required',
                'card_value' => 'required',
                'instruction' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cards/editcarddetail/' . $cslug . '/' . $id)->withErrors($validator)->withInput();
            } else {

                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                Carddetail::where('id', $recordInfo->id)->update($serialisedData);
                Session::flash('success_message', "Card details updated successfully.");
                return Redirect::to('admin/cards/carddetail/' . $cslug);
            }
        }
        return view('admin.cards.editcarddetail', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo, 'cslug' => $cslug]);
    }

    public function usedcard(Request $request) {
        $access = $this->getRoles(Session::get('adminid'), 10);
        if ($access == 0) {
            return Redirect::to('admin/admins/dashboard');
        }

        $pageTitle = 'Manage Recharge Cards';
        $activetab = 'actcards';
        $query = new Carddetail();
        $query = $query->sortable();
       $query = $query->where('used_status',1);

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                Scratchcard::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Scratchcard::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Scratchcard::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function($q) use ($keyword) {
                $q->where('serial_number', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('card_type') && $request->get('card_type')) {
            $card_type = $request->get('card_type');
            $query = $query->where(function($q) use ($card_type) {
                $q
                        ->orWhereHas('Card', function($q) use ($card_type) {
                            $q = $q->where('card_type', 'like', '%' . $card_type . '%');
                        });
            });
        }

        if ($request->has('used_status')) {
            $used_status = $request->get('used_status');
            $query = $query->where(function($q) use ($used_status) {
                if ($used_status != '') {
                    $q->where('used_status', $used_status);
                }
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";

            $query = $query->where(function($q) use ($from, $to) {
                $q->whereBetween('used_date', array($from, $to));
            });
        }

        $cards = $query->orderBy('used_date', 'DESC')->paginate(20);

//        $query1 = $query;
//        $query2 = $query;
        $total['total'] = $query->sum('card_value');

        $total['unused_value'] = $query->where('used_status', 0)->sum('card_value');
        $total['used_value'] = $total['total'] - $total['unused_value'];

        if ($request->ajax()) {
            return view('elements.admin.cards.usedcard', ['allrecords' => $cards, 'total' => $total]);
        }
        return view('admin.cards.usedcard', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $cards, 'total' => $total]);
    }

    public function addcards($id = null) {
        for ($i = 0; $i < 20; $i++) {
            $bytes = random_bytes(8);
            $uniqueNumber = strtoupper(bin2hex($bytes));
            $bytes1 = random_bytes(8);
            $uniqueNumber1 = strtoupper(bin2hex($bytes1));

            $serialisedData['card_id'] = $id;
            $serialisedData['serial_number'] = $uniqueNumber;
            $serialisedData['pin_number'] = $uniqueNumber1;
            $serialisedData['currency'] = 'AUD';
            $serialisedData['real_value'] = 50;
            $serialisedData['card_value'] = 60;
            $serialisedData['status'] = 1;
            $serialisedData['instruction'] = '*123#';
            $serialisedData['created_at'] = date('Y-m-d H:i:s');
            $serialisedData['updated_at'] = date('Y-m-d H:i:s');
            Carddetail::insert($serialisedData);
        }
        echo 'card added successfully';
        exit;
    }

}

?>