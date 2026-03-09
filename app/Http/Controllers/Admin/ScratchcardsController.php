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
use App\Models\Scratchcard;
use App\Models\Country;
use App\Models\Admin;
use Mail;
use App\Mail\SendMailable;

class ScratchcardsController extends Controller {

    public function __construct() {
        $this->middleware('is_adminlogin');
    }

    public function index(Request $request) {
        $access = $this->getRoles(Session::get('adminid'),6);
        if($access == 0){
            return Redirect::to('admin/admins/dashboard');
        }
        
        $adminList = Admin::where('status',1)->orderBy('username', 'ASC')->pluck('username','id')->all();        
        
        $pageTitle = 'Manage Scratchcards';
        $activetab = 'actscratchcards';
        $query = new Scratchcard();
        $query = $query->sortable();
        $query = $query->where('used_status',0);

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
                $q->where('card_number', 'like', '%' . $keyword . '%');
            });
        }
        
        if ($request->has('last_updated_by')) {
            $last_updated_by = $request->get('last_updated_by');
            $query = $query->where(function($q) use ($last_updated_by) {
                if($last_updated_by != ''){
                    $q->where('last_updated_by', $last_updated_by);
                }                
            });
        }

        if ($request->has('purchase_status')) {
            $purchase_status = $request->get('purchase_status');
            $query = $query->where(function($q) use ($purchase_status) {
                if($purchase_status != ''){
                    if($purchase_status == 1){ 
                        $q->whereNotNull('purchase_by_id');
                    } else{
                        // $q->where('used_status',1);
                        $q->whereNull('purchase_by_id');
                    }                    
                }
                
            });
        }
        
        if ($request->has('used_status')) {
            $used_status = $request->get('used_status');
            $query = $query->where(function($q) use ($used_status) {
                if($used_status != ''){
                    $q->where('used_status', $used_status);
                }                
            });
        }
        
        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0]." 00:00:00";
		 $to = $dateQ[1]." 23:59:59";
                 
                 $query = $query->where(function($q) use ($from,$to){
                $q->whereBetween('created_at', array($from, $to));
            });
        }

        if ($request->has('to1') && $request->get('to1')) {
            $dateQ1 = explode("/", $request->get('to1'));
            $from1 = $dateQ1[0]." 00:00:00";
         $to1 = $dateQ1[1]." 23:59:59";
                 
                 $query = $query->where(function($q) use ($from1,$to1){
                $q->whereBetween('expiry_date', array($from1, $to1));
            });
        }

        $scratchcards = $query->orderBy('id', 'DESC')->paginate(20);
        
        $total['total'] = $query->sum('card_value');
        
        $total['unused_value'] = $query->where('used_status', 0)->sum('card_value');
        $total['used_value'] = $total['total'] - $total['unused_value'];
        
        if ($request->ajax()) {
            return view('elements.admin.scratchcards.index', ['allrecords' => $scratchcards,'total' => $total,'adminList'=>$adminList]);
        }
        return view('admin.scratchcards.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $scratchcards,'total' => $total,'adminList'=>$adminList]);
    }
    
    public function usedcard(Request $request) {
        $access = $this->getRoles(Session::get('adminid'),10);
        if($access == 0){
            return Redirect::to('admin/admins/dashboard');
        }
        
        $pageTitle = 'Manage Used Scratchcards';
        $activetab = 'actscratchcards';
        $query = new Scratchcard();
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
                $q->where('card_number', 'like', '%' . $keyword . '%');
            });
        }
        
//        if ($request->has('used_status')) {
//            $used_status = $request->get('used_status');
//            $query = $query->where(function($q) use ($used_status) {
//                if($used_status != ''){
//                    $q->where('used_status', $used_status);
//                }
//                
//            });
//        }
        if ($request->has('purchase_status')) {
            $purchase_status = $request->get('purchase_status');
            $query = $query->where(function($q) use ($purchase_status) {
                if($purchase_status != ''){
                    if($purchase_status == 1){ 
                        $q->whereNotNull('purchase_by_id');
                    } else{
                        // $q->where('used_status',1);
                        $q->where('used_status',1)->whereNull('purchase_by_id');
                    }                    
                } else{
                    $q->where('used_status',1)->orWhere('purchase_by_id','!=',null);
                }
                
            });
        } else{
            $query = $query->where('used_status',1)->orWhere('purchase_by_id','!=',null);
        }
        
        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0]." 00:00:00";
		 $to = $dateQ[1]." 23:59:59";
                 
                 $query = $query->where(function($q) use ($from,$to){
                $q->whereBetween('updated_at', array($from, $to));
            });
        }

        $scratchcards = $query->orderBy('updated_at', 'DESC')->paginate(20);
        
        $total['total'] = $query->sum('real_value');
        
        $total['unused_value'] = $query->where('used_status', 0)->sum('real_value');
        $total['used_value'] = $total['total'] - $total['unused_value'];
        if ($request->ajax()) {
            return view('elements.admin.scratchcards.usedcard', ['allrecords' => $scratchcards,'total' => $total]);
        }
        return view('admin.scratchcards.usedcard', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $scratchcards,'total' => $total]);
    }

    public function add() {
        $access = $this->getRoles(Session::get('adminid'),6);
        if($access == 0){
            return Redirect::to('admin/admins/dashboard');
        }
        
        $pageTitle = 'Add Scratchcard';
        $activetab = 'actscratchcards';

//        $bytes = random_bytes(8);
//        $uniqueCardNumber = strtoupper(bin2hex($bytes));
//        $chkCard = Scratchcard::where('card_number', $uniqueCardNumber)->first();
//        if($chkCard){
//            $bytes = random_bytes(8);
//            $uniqueCardNumber = strtoupper(bin2hex($bytes));
//        }        

        $input = Input::all();
        if (!empty($input)) { 
            $rules = array(
                'real_value' => 'required',
                'card_value' => 'required',
//                'agent_card_value' => 'required',
                'number_of_cards' => 'required',
                'expiry_date' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/scratchcards/add')->withErrors($validator)->withInput();
            } else {
                $number_of_cards = $input['number_of_cards'];
                unset($input['number_of_cards']);
                
//                $chkCard = Scratchcard::orderBy('id', 'DESC')->first();
//                $carValue = substr($chkCard->card_number, -8);
                
                for($i=0;$i<$number_of_cards;$i++){
                    $serialisedData = $this->serialiseFormData($input);
                    $bytes = random_bytes(8);
                    $uniqueCardNumber = strtoupper(bin2hex($bytes));                    
                    
                    $serialisedData['card_number'] = $uniqueCardNumber;
                    $serialisedData['status'] = 1;
                    $serialisedData['last_updated_by'] = Session::get('adminid');
                    Scratchcard::insert($serialisedData);
                }

//                $serialisedData = $this->serialiseFormData($input);
//                $serialisedData['card_number'] = $uniqueCardNumber;
//                $serialisedData['status'] = 1;
//                Scratchcard::insert($serialisedData);

                Session::flash('success_message', "Scratch card details saved successfully.");
                return Redirect::to('admin/scratchcards');
            }
        }
        return view('admin.scratchcards.add', ['title' => $pageTitle, $activetab => 1]);
    }

    public function edit($id = null) {
        $access = $this->getRoles(Session::get('adminid'),6);
        if($access == 0){
            return Redirect::to('admin/admins/dashboard');
        }
        
        $pageTitle = 'Edit Scratchcard';
        $activetab = 'actscratchcards';

        $recordInfo = Scratchcard::where('id', $id)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/scratchcards');
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'real_value' => 'required',
                'card_value' => 'required',
//                'agent_card_value' => 'required',
                'expiry_date' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/scratchcards/edit/' . $id)->withErrors($validator)->withInput();
            } else {

                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                $serialisedData['last_updated_by'] = Session::get('adminid');
                Scratchcard::where('id', $recordInfo->id)->update($serialisedData);
                Session::flash('success_message', "Scratch card details updated successfully.");
                return Redirect::to('admin/scratchcards');
            }
        }
        return view('admin.scratchcards.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    public function activate($slug = null) {
        if ($slug) {
            Scratchcard::where('id', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_status', ['action' => 'admin/scratchcards/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null) {
        if ($slug) {
            Scratchcard::where('id', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_status', ['action' => 'admin/scratchcards/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null) {
        if ($slug) {
            Scratchcard::where('id', $slug)->delete();
            Session::flash('success_message', "Scratch card details deleted successfully.");
            return Redirect::to('admin/scratchcards');
        }
    }

}

?>