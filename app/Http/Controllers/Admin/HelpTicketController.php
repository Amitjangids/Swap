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
use App\Models\HelpTicket;
use App\Models\Admin;
use Mail;
use App\Role;
use App\Mail\SendMailable;

use App\Exports\BulkExport;
use App\Imports\BulkImport;
use Maatwebsite\Excel\Facades\Excel;

class HelpTicketController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_adminlogin');
    }


    public function index(Request $request)
    {

        $pageTitle = 'Manage Help Ticket';
        $activetab = 'acthelpticket';
        $query = new HelpTicket();
        $query = $query->sortable();
        $startDate = $request->input('startDate'); // expected format: Y-m-d
        $endDate = $request->input('endDate');     // expected format: Y-m-d

        //        $query = $query->where('used_status',0);

        /* if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                HelpTicket::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                HelpTicket::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivated successfully.");
            }
        } */

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('ticketId', 'like', '%' . $keyword . '%')
                    ->OrWhere('status', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $too = $dateQ[1] . " 23:59:59";

            $query = $query->where(function ($q) use ($from, $too) {
                $q->whereBetween('created_at', array($from, $too));
            });
        }

        /* if ($request->has('to') && $request->get('to')) {
            $dateQ = explode("/", $request->get('to'));
            $from = $dateQ[0] . " 00:00:00";
            $to = $dateQ[1] . " 23:59:59";

            $query = $query->where(function($q) use ($from, $to) {
                $q->whereBetween('created_at', array($from, $to));
            });
        } */

        $helpTickets = $query->orderBy('id', 'DESC')->paginate(20);

        if ($request->ajax()) {
            return view('elements.admin.helpticket.index', ['allrecords' => $helpTickets]);
        }

        return view('admin.helpticket.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $helpTickets, 'adminList' => $helpTickets]);
    }

    public function viewTelpTicket($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-helpticket');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'acthelpticket';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'View Help Ticket';
        $activetab = 'acthelpticket';
        $roleArr = [];

        $roles = Role::whereNotIn('id', [1, 27])->orderBy('id', 'ASC')->get();
        foreach ($roles as $role) {
            $roleArr[$role->id] = $role->role_name;
        }

        $recordInfo = HelpTicket::with(['User', 'HelpCat'])->where('id', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/help-ticket');
        }

        if (request()->isMethod('post')) {
            $input = request()->all();

            $rules = array(
                'status' => 'required',
            );
            $customMessages = [
                'status.required' => 'The status is required field.',
            ];

            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                $recordInfo->status = $input['status'];
                $recordInfo->comment = $input['comment'] ?? "";

                $recordInfo->save();
                Session::flash('success_message', "Help ticket status updated successfully.");
                return redirect()->to('admin/help-ticket');
            }
        }

        return view('admin.helpticket.view', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }
    public function updateHelpTicketStatus($slug = null)
    {
        if ($slug) {
            HelpTicket::where('id', $slug)->update(array('status' => 'Resolved'));
            Session::flash('success_message', "Help ticket resolved successfully.");
            return redirect()->to('admin/help-ticket');
        }
    }
}

?>