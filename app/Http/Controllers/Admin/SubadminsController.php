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
use App\Models\Admin;
use App\Role;
use App\Models\Country;
use Mail;
use App\Mail\SendMailable;


class SubadminsController extends Controller {

    public function __construct() {
        $this->middleware('is_adminlogin');
//        $this->middleware('is_subadminlogin');
    }

    public function index(Request $request) {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'subadmins');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actsubadmins';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        } 

        $pageTitle = 'Manage Sub Admins';
        $activetab = 'actsubadmins';
        $query = new Admin();
        $query = $query->whereIn('status',[ '0','1']);
       
        $query = $query->sortable();
        $query = $query->where('id', '!=', '1')->where('parent_id', '0');

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                Admin::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activate successfully.");
            } else if ($action == "Deactivate") {
                Admin::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivate successfully.");
            } else if ($action == "Delete") {
                Admin::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function($q) use ($keyword) {
                $q->where('username', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        $subadmins = $query->orderBy('id', 'DESC')->paginate(20);
        $department = Role::whereIn('id', $subadmins->pluck('role_id'))->get();

      
        if ($request->ajax()) {
            return view('elements.admin.subadmins.index', ['allrecords' => $subadmins]);
        }
        return view('admin.subadmins.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $subadmins,'departments' => $department]);
    }

    public function add() {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-subadmins');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actsubadmins';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        } 

        $pageTitle = 'Add Sub Admin';
        $activetab = 'actsubadmins';
        $roleArr = [];
        $input = Input::all();
        $roles = Role::whereNotIn('id',[1,27])->orderBy('id','ASC')->get();
                
        $roleArr = [];
        foreach ($roles as $role)
        {
        $roleArr[$role->id] = $role->role_name;	
        }

        if (!empty($input)) { 
            $rules = array(
                'username' => 'required|max:50|unique:admins',
                'password' => 'required|min:8',
                'confirm_password' => 'required|same:password',
                'role_id' => 'required',
            );
            $customMessages = [
                'role_id.required' => 'The role access is required field.',
            ];
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return Redirect::to('/admin/subadmins/add-subadmins')->withErrors($validator)->withInput();
            } else {
               
                $role_ids =  $input['role_id'];
             
                $slug = $this->createSlug($input['username'], 'admins');
                $admin = new Admin([
                    'username' => $input['username'],
                    'email' => $input['email'],
                    'role_id' => $role_ids,
                    'password' => $this->encpassword($input['password']),
                    'status' => 1,
                    'activation_status' => 1,
                    'slug' => $slug,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $admin->save();

                $name = $input['username'];
                $emailId = $input['email'];
                $new_password = $input['password'];

                $emailTemplate = DB::table('emailtemplates')->where('id', 2)->first();
                $toRepArray = array('[!email!]', '[!name!]', '[!username!]', '[!password!]', '[!HTTP_PATH!]', '[!SITE_TITLE!]');
                $fromRepArray = array($emailId, $name, $name, $new_password, HTTP_PATH, SITE_TITLE);
                $emailSubject = str_replace($toRepArray, $fromRepArray, $emailTemplate->subject);
                $emailBody = str_replace($toRepArray, $fromRepArray, $emailTemplate->template);
                //Mail::to($emailId)->send(new SendMailable($emailBody,$emailSubject));

                Session::flash('success_message', "Subadmin user details saved successfully.");
                return Redirect::to('admin/subadmins');
            }
        }
        return view('admin.subadmins.add', ['title' => $pageTitle, $activetab => 1, 'roleList'=>$roleArr]);
    }

    public function edit($slug = null) {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-subadmins');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actsubadmins';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        } 
    
        $pageTitle = 'Edit Sub Admin';
        $activetab = 'actsubadmins';
        $roleArr = [];
    
        $roles = Role::whereNotIn('id',[1,27])->orderBy('id','ASC')->get();
        foreach ($roles as $role) {
            $roleArr[$role->id] = $role->role_name;	
        }
    
        $recordInfo = Admin::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/subadmins');
        }
    
        if (request()->isMethod('post')) {
            $input = request()->all();
    
            $rules = [
                'username' => 'required|max:50|unique:admins,username,'.$recordInfo->id,
                'password' => 'nullable|min:8',
                'confirm_password' => 'nullable|same:password',
                'role_id' => 'required',
            ];
    
            $customMessages = [
                'role_id.required' => 'The role access is required field.',
            ];
    
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                $recordInfo->username = $input['username'];
                $recordInfo->email = $input['email'];
                $recordInfo->role_id = $input['role_id'];
    
                if (!empty($input['password'])) {
                    $recordInfo->password = $this->encpassword($input['password']);
                }
    
                $recordInfo->save();
                Session::flash('success_message', "Subadmin user details updated successfully.");
                return redirect()->to('admin/subadmins');
            }
        }
    
        return view('admin.subadmins.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo, 'roleList' => $roleArr]);
    }    
    

    public function activate($slug = null) {
        if ($slug) {
            Admin::where('slug', $slug)->update(array('status' => '1','activation_status' => '1'));
            return view('elements.admin.update_status', ['action' => 'admin/subadmins/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null) {
        if ($slug) {
            Admin::where('slug', $slug)->update(array('status' => '0','activation_status' => '0'));
            return view('elements.admin.update_status', ['action' => 'admin/subadmins/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null) {
        if ($slug) {
            $update=Admin::where('slug', $slug)->update(['status' => 2]);
            Session::flash('success_message', "Subadmin user details deleted successfully.");
            echo $update; 
            return Redirect::to('admin/subadmins');
        }
    }

}

?>