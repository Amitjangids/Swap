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
use App\Models\CardContent;
use App\Models\Country;
use Mail;
use App\Mail\SendMailable;

class CardcontentController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_adminlogin');
    }

    public function index(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'cardcontents');
        if ($isPermitted == false) {
            $pageTitle = 'cardcontents';
            $activetab = 'actcardcontents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Manage Card Content';
        $activetab = 'actcardcontents';
        $query = new CardContent();
        $query = $query->sortable();

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                CardContent::whereIn('id', $idList)->update(array('status' => 'Active'));
                Session::flash('success_message', "Records are activated successfully.");
            } else if ($action == "Deactivate") {
                CardContent::whereIn('id', $idList)->update(array('status' => 'Inactive'));
                Session::flash('success_message', "Records are deactivated successfully.");
            } else if ($action == "Delete") {
                CardContent::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%');
            });
        }

        $allRecords = $query->orderBy('id', 'DESC')->paginate(20);
        if ($request->ajax()) {
            return view('elements.admin.card_content.index', ['allrecords' => $allRecords]);
        }
        return view('admin.card_content.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $allRecords]);
    }

    public function add()
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-card-content');
        if ($isPermitted == false) {
            $pageTitle = 'cardcontents';
            $activetab = 'actcardcontents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Add Card Content';
        $activetab = 'actcardcontents';

        $input = Input::all();
        if (!empty($input)) {

            $rules = [
                'title' => 'required|max:100',
                'description' => 'required',
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cardcontents/add-card-content')->withErrors($validator)->withInput();
            } else {
                
                $serialisedData = $this->serialiseFormData($input);
                $serialisedData['status'] = 0;
                if (isset($input['description']) && is_array($input['description'])) {
                    $serialisedData['description'] = json_encode(array_values(array_filter($input['description'])));
                }
                CardContent::insert($serialisedData);

                Session::flash('success_message', "Card content saved successfully.");
                return Redirect::to('admin/cardcontents');
            }
        }
        return view('admin.card_content.add', ['title' => $pageTitle, $activetab => 1]);
    }

    public function edit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-card-content');
        if ($isPermitted == false) {
            $pageTitle = 'cardcontents';
            $activetab = 'actcardcontents';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }
        $pageTitle = 'Edit Card Content';
        $activetab = 'actcardcontents';

        $recordInfo = CardContent::where('id', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/cardcontents');
        }

        $input = Input::all();
        if (!empty($input)) {

            $rules = [
                'title' => 'required|max:100',
                'description' => 'required',
            ];
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/cardcontents/cardcontents/' . $slug)->withErrors($validator)->withInput();
            } else {
                $serialisedData = $this->serialiseFormData($input, 1);
                if (isset($input['description']) && is_array($input['description'])) {
                    $serialisedData['description'] = json_encode(array_values(array_filter($input['description'])));
                }
                CardContent::where('id', $recordInfo->id)->update($serialisedData);
                Session::flash('success_message', "Card content updated successfully.");
                return Redirect::to('admin/cardcontents');
            }
        }
        return view('admin.card_content.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    /* public function activate($slug = null)
    {
        if ($slug) {
            CardContent::where('slug', $slug)->update(array('status' => '1'));
            return view('elements.admin.active_status', ['action' => 'admin/cardcontents/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null)
    {
        if ($slug) {
            CardContent::where('slug', $slug)->update(array('status' => '0'));
            return view('elements.admin.active_status', ['action' => 'admin/cardcontents/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null)
    {
        if ($slug) {
            CardContent::where('slug', $slug)->delete();
            Session::flash('success_message', "Card content details deleted successfully.");
            return Redirect::to('admin/cardcontents');
        }
    } */

}

?>