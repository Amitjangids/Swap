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
use App\Models\Contact;
use Mail;
use App\Mail\SendMailable;

class ContactsController extends Controller {    
    public function __construct() {
        $this->middleware('is_adminlogin');
    }
    
    public function index(Request $request){
        $contactTitle = 'Manage Contacts'; 
        $activetab = 'actcontacts';
        $query = new Contact();
        $query = $query->sortable();
        
        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');
            if ($action == "Activate") {
                Contact::whereIn('id', $idList)->update(array('status' => 1, 'activation_status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                Contact::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                Contact::whereIn('id', $idList)->delete();
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
        
        $contacts = $query->orderBy('id','DESC')->paginate(20);
        if($request->ajax()){
            return view('elements.admin.contacts.index', ['allrecords'=>$contacts]);
        }
        return view('admin.contacts.index', ['title'=>$contactTitle, $activetab=>1,'allrecords'=>$contacts]);
    }
    
}
?>