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
use App\Models\Banner;
use App\Models\Country;
use Mail;
use App\Mail\SendMailable;

class BannersController extends Controller {

    public function __construct() {
        $this->middleware('is_adminlogin');
    }

    public function index(Request $request) {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'banners');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actbanners';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        } 
        $pageTitle = 'Manage Banners';
        $activetab = 'actbanners';
        $query = new Banner();
        $query = $query->sortable();

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                Banner::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Banner::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Banner::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function($q) use ($keyword) {
                $q->where('banner_name', 'like', '%' . $keyword . '%');
            });
        }

        $banners = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->ajax()) {
            return view('elements.admin.banners.index', ['allrecords' => $banners]);
        }
        return view('admin.banners.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $banners]);
    }

    public function getcategorylist($id = null) {
        if ($id == 'Agent') {
            $categoryType = array(
                'Internet Recharge' => 'Internet Recharge',
                'Mobile Recharge' => 'Mobile Recharge',
                'Online Card' => 'Online Card',
            );
            return view('elements.admin.categorylist', ['categoryType' => $categoryType]);
        } else if ($id == 'Merchant') {
            $categoryType = array(
                'Internet Recharge' => 'Internet Recharge',
                'Mobile Recharge' => 'Mobile Recharge',
                'Online Card' => 'Online Card',
            );
            return view('elements.admin.categorylist', ['categoryType' => $categoryType]);
        } else {
            global $categoryType;
            return view('elements.admin.categorylist', ['categoryType' => $categoryType]);
        }
    }

    public function add() {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-banners');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actbanners';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        } 
        $pageTitle = 'Add Banner';
        $activetab = 'actbanners';

        $input = Input::all();
        if (!empty($input)) {
           
            $rules = array(
                'banner_name' => 'required|max:50',
                'banner_image' => 'required|dimensions:width>150,height>145|mimes:jpeg,png,jpg|max:2048',
                // 'category' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/banners/add-banners')->withErrors($validator)->withInput();
            } else {

                if (Input::hasFile('banner_image')) {
                    $file = Input::file('banner_image');
                    $uploadedFileName = $this->uploadImage($file, BANNER_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, BANNER_FULL_UPLOAD_PATH, BANNER_SMALL_UPLOAD_PATH, BANNER_MW, BANNER_MH);
                    $input['banner_image'] = $uploadedFileName;
                } else {
                    unset($input['banner_image']);
                }

                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['slug'] = $this->createSlug($input['banner_name'], 'banners');
                $serialisedData['status'] = 1;
                Banner::insert($serialisedData);

                Session::flash('success_message', "Banner details saved successfully.");
                return Redirect::to('admin/banners');
            }
        }
        return view('admin.banners.add', ['title' => $pageTitle, $activetab => 1]);
    }

    public function edit($slug = null) {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-banners');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'actbanners';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        } 
        $pageTitle = 'Edit Banner';
        $activetab = 'actbanners';

        $recordInfo = Banner::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/banners');
        }
        
        if ($recordInfo->user_type == 'Agent') {
            $categoryType = array(
                'Internet Recharge' => 'Internet Recharge',
                'Mobile Recharge' => 'Mobile Recharge',
                'Online Card' => 'Online Card',
            );
            $categoryType = $categoryType;
        } else if ($recordInfo->user_type == 'Merchant') {
            $categoryType = array(
                'Internet Recharge' => 'Internet Recharge',
                'Mobile Recharge' => 'Mobile Recharge',
                'Online Card' => 'Online Card',
            );
            $categoryType = $categoryType;
        } else {

            $categoryType = array(
                'Internet Recharge' => 'Internet Recharge',
                'Mobile Recharge' => 'Mobile Recharge',
                'Online Card' => 'Online Card',
                'Deposit' => 'Deposit',
                'Online Shopping' => 'Online Shopping',
                'Transaction' => 'Transaction',
                'Withdraw' => 'Withdraw',
                'Receive Monery' => 'Receive Monery',
                'Send Money' => 'Send Money',
                'Shop Payment' => 'Shop Payment',
            );
            $categoryType = $categoryType;
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = array(
                'banner_name' => 'required|max:50',
                // 'category' => 'required',
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/banners/edit-banners/' . $slug)->withErrors($validator)->withInput();
            } else {

                if (Input::hasFile('banner_image')) {
                    $file = Input::file('banner_image');
                    $uploadedFileName = $this->uploadImage($file, BANNER_FULL_UPLOAD_PATH);
                    $this->resizeImage($uploadedFileName, BANNER_FULL_UPLOAD_PATH, BANNER_SMALL_UPLOAD_PATH, BANNER_MW, BANNER_MH);
                    $input['banner_image'] = $uploadedFileName;
                    @unlink(BANNER_FULL_UPLOAD_PATH . $recordInfo->banner_image);
                } else {
                    unset($input['banner_image']);
                }


                $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
                Banner::where('id', $recordInfo->id)->update($serialisedData);
                Session::flash('success_message', "Banner details updated successfully.");
                return Redirect::to('admin/banners');
            }
        }
        return view('admin.banners.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo,'categoryType' => $categoryType]);
    }

    public function activate($slug = null) {
        if ($slug) {
            Banner::where('slug', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_status', ['action' => 'admin/banners/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null) {
        if ($slug) {
            Banner::where('slug', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_status', ['action' => 'admin/banners/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null) {
        if ($slug) {
            Banner::where('slug', $slug)->delete();
            Session::flash('success_message', "banner details deleted successfully.");
            return Redirect::to('admin/banners');
        }
    }

}

?>