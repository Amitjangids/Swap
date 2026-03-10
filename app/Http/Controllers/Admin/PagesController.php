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
use App\Models\Page;
use App\Models\Feature;
use Mail;
use App\Mail\SendMailable;

class PagesController extends Controller {    
    public function __construct() {
        $this->middleware('is_adminlogin');
    }
    
    public function index(Request $request){
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'pages');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actpages';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Manage Pages'; 
        $activetab = 'actpages';
        $query = new Page();
        // $query = $query->where('status',1);
        $query = $query->sortable();

        
        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');
            if ($action == "Activate") {
                Page::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Page::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Page::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            } 
        }
        
        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function($q) use ($keyword){
                $q->where('first_name', 'like', '%'.$keyword.'%')
                ->orWhere('last_name', 'like', '%'.$keyword.'%');
            });
        }
        
        $pages = $query->orderBy('id','DESC')->paginate(20);
        if($request->ajax()){
            return view('elements.admin.pages.index', ['allrecords'=>$pages]);
        }
        return view('admin.pages.index', ['title'=>$pageTitle, $activetab=>1,'allrecords'=>$pages]);
    }

    public function edit($slug=null){
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-pages');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actpages';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        
        $pageTitle = 'Edit Page'; 
        $activetab = 'actpages';
        $countrList = array('1'=>'India', '2'=>'USA', '3'=>'AUS');
        
        $recordInfo = Page::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/pages');
        }
        
        $input = Input::all();
        if (!empty($input)) {
            $rules = array(
               'title' => 'required',
               'description'=>'required',
            );
            $validator = Validator::make($input, $rules);             
            if ($validator->fails()) {
                return Redirect::to('/admin/pages/edit-pages/'.$slug)->withErrors($validator)->withInput();
            } else {
                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                Page::where('id', $recordInfo->id)->update($serialisedData);
                Session::flash('success_message', "Page details updated successfully.");
                return Redirect::to('admin/pages');
            }           
        }        
        return view('admin.pages.edit', ['title'=>$pageTitle, $activetab=>1, 'countrList'=>$countrList, 'recordInfo'=>$recordInfo]);
    }    
    
    public function pageimages(){ 
        $file = Input::file('upload');
        $uploadedFileName = $this->uploadImage($file, CK_IMAGE_UPLOAD_PATH);
        echo "<span style='font-size: 12px; color: #f00; font-weight: bold;'>Copy below URL and Paste it in Image Info tab and than click OK button:</span> <span style='float: left; font-size: 13px; margin: 2px 0 0; width: 100%;'>" . CK_IMAGE_DISPLAY_PATH . $uploadedFileName.'</span>'; 
        exit;
    }
    
    public function homeFeatures(Request $request){
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'homeFeatures');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actpages';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
       
        $pageTitle = 'Enable/Disable Features'; 
        $activetab = 'actfeatures';
        $query = new Feature();
        $query = $query->sortable();
        
        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');
            if ($action == "Activate") {
                Feature::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Feature::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Feature::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            } 
        }
        
        $features = $query->orderBy('name','ASC')->paginate(100);
        if($request->ajax()){
            return view('elements.admin.pages.homeFeatures', ['allrecords'=>$features]);
        }
        return view('admin.pages.homeFeatures', ['title'=>$pageTitle, $activetab=>1,'allrecords'=>$features]);
    }
    
    public function activateFeature($slug = null) {
        if ($slug) {
            Feature::where('slug', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_feature', ['action' => 'admin/pages/deactivateFeature/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivateFeature($slug = null) {
        if ($slug) {
            Feature::where('slug', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_feature', ['action' => 'admin/pages/activateFeature/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }
    
}
?>