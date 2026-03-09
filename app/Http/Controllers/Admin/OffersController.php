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
use App\User;
use App\Models\Offer;
use App\Models\Agentoffer;
use App\Models\Admin;
use App\Kyc;
use Mail;
use App\Mail\SendMailable;

class OffersController extends Controller {    
    public function __construct() {
        $this->middleware('is_adminlogin');
    }
    
    public function index(Request $request){
        $pageTitle = 'Manage Offers'; 
        $activetab = 'actoffers';
        $query = new Offer();
        $query = $query->sortable();
        
        
        
        $offers = $query->orderBy('id','DESC')->paginate(20);
        if($request->ajax()){
            return view('elements.admin.offers.index', ['allrecords'=>$offers]);
        }
        return view('admin.offers.index', ['title'=>$pageTitle, $activetab=>1,'allrecords'=>$offers]);
    }
    
    public function delete($slug=null){
        if($slug){
            Offer::where('id', $slug)->delete();
            Session::flash('success_message', "Offer details deleted successfully.");
            return Redirect::to('admin/offers');
        }
    }    
    
    public function edit($slug = null) {
        $pageTitle = 'Edit Offer';
        $activetab = 'actoffers';

        $recordInfo = Offer::where('id', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/offers');
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'offer' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/offers/edit/' . $slug)->withErrors($validator)->withInput();
            } else {
                
                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                
                
                Offer::where('id', $recordInfo->id)->update($serialisedData);                
                
                Session::flash('success_message', "Agent offer details updated successfully.");
                return Redirect::to('admin/offers');
            }
        }
        return view('admin.offers.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }
    
    public function activate($slug = null) {
        if ($slug) {
            Offer::where('id', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_status', ['action' => 'admin/offers/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null) {
        if ($slug) {
            Offer::where('id', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_status', ['action' => 'admin/offers/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }
    
    public function agentoffers($slug = null, Request $request){
        $pageTitle = 'Manage Offers'; 
        $activetab = 'actagents';
        $query = new Agentoffer();
        $query = $query->sortable();
        
        $userInfo = User::where('slug', $slug)->first();
                
        $offers = $query->where('user_id',$userInfo->id)->orderBy('id','DESC')->paginate(20);
        if($request->ajax()){
            return view('elements.admin.offers.agentoffers', ['allrecords'=>$offers,'userInfo'=>$userInfo]);
        }
        return view('admin.offers.agentoffers', ['title'=>$pageTitle, $activetab=>1,'allrecords'=>$offers,'userInfo'=>$userInfo]);
    }

   
    public function addagentoffer($slug = null) {
        $pageTitle = 'Add Agent Offer';
        $activetab = 'actagents';

        $userInfo = User::where('slug', $slug)->first();
        if (empty($userInfo)) {
            return Redirect::to('admin/agents');
        }

        $input = Input::all();
        if (!empty($input)) {
//echo '<pre>';print_r($input);exit;
            $rules = array(
                'type' => 'required',
                'offer' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/offers/addagentoffer/' . $slug)->withErrors($validator)->withInput();
            } else {
                
                $offerInfo = Agentoffer::where('user_id', $userInfo->id)->where('type', $input['type'])->first();
                if($offerInfo){
                    return Redirect::to('/admin/offers/addagentoffer/' . $slug)->withErrors('Offer already exist for selected agent.');
                }
                
                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                
                $serialisedData['user_id'] = $userInfo->id;
                $serialisedData['status'] = 1;
                $serialisedData['created_at'] = date('Y-m-d H:i:s');
                //echo '<pre>';print_r($serialisedData);exit;
                Agentoffer::insert($serialisedData);           
                
                Session::flash('success_message', "Agent offer details added successfully.");
                return Redirect::to('admin/offers/agentoffers/'.$slug);
            }
        }
        return view('admin.offers.addagentoffer', ['title' => $pageTitle, $activetab => 1, 'userInfo' => $userInfo,'slug'=>$slug]);
    }
    
    public function editagentoffer($uslug = null,$slug = null) { 
        $pageTitle = 'Edit Agent Offer';
        $activetab = 'actagents';

        $userInfo = User::where('slug', $uslug)->first();
        if (empty($userInfo)) {
            return Redirect::to('admin/agents');
        }
        
        $recordInfo = Agentoffer::where('id', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/agentoffers/'.$uslug);
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'offer' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/offers/editagentoffer/' . $uslug . '/' .  $slug)->withErrors($validator)->withInput();
            } else {
                
                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                
                
                Agentoffer::where('id', $recordInfo->id)->update($serialisedData);                
                
                Session::flash('success_message', "Agent offer details updated successfully.");
                return Redirect::to('admin/offers/agentoffers/'.$uslug);
            }
        }
        return view('admin.offers.editagentoffer', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo,'userInfo' => $userInfo]);
    }
    
    public function activateoffer($slug = null) {
        if ($slug) {
            Agentoffer::where('id', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_status', ['action' => 'admin/offers/deactivateoffer/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivateoffer($slug = null) {
        if ($slug) {
            Agentoffer::where('id', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_status', ['action' => 'admin/offers/activateoffer/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }
    
    public function deleteagentoffer($slug = null,$id = null) {
        if ($id) {
            Agentoffer::where('id', $id)->delete();
            Session::flash('success_message', "Agent offer details deleted successfully.");
            return Redirect::to('admin/offers/agentoffers/'.$slug);
        }
    }
}
?>