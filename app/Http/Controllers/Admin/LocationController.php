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
use App\Role;
use App\Models\Location;
use Mail;
use App\Mail\SendMailable;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_adminlogin');
        //        $this->middleware('is_subadminlogin');
    }

    public function index(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'location-list');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actlocations';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Location';
        $activetab = 'actlocations';
        $query = new Location(); 


        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Delete") {
                Location::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }


        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('address', 'like', '%' . $keyword . '%')
                    ->orWhere('telephone', 'like', '%' . $keyword . '%')
                    ->orWhere('latitude', 'like', '%' . $keyword . '%')
                    ->orWhere('longitude', 'like', '%' . $keyword . '%');
            });
        }

        $locations = $query->orderBy('id', 'DESC')->paginate(20);

        if ($request->ajax()) {
            return view('elements.admin.locations.index', ['allrecords' => $locations]);
        }
        return view('admin.locations.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $locations]);
    }

    public function add()
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-location');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actlocations';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add Location';
        $activetab = 'actlocations';
        $roleArr = [];
        $input = Input::all();

        if (!empty($input)) {
            $rules = array(
                'name' => 'required|max:50',
                'address' => 'required',
                'telephone' => 'required|min:6|max:10',
                'latitude' => 'required',
                'longitude' => 'required',
            );
            $customMessages = [
                'name.required' => 'The name is required field.',
                'address.required' => 'The address is required.',
                'telephone.required' => 'The telephone is required.',
                'telephone.min' => 'The telephone must be at least 6 digits.',
                'telephone.max' => 'The telephone may not be greater than 10 digits.',
                'latitude.required' => 'The latitude is required.',
                'longitude.required' => 'The longitude is required.',
            ];

            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return Redirect::to('/admin/locations/add-location')->withErrors($validator)->withInput();
            } else {

                $location = new Location();
                $location->name = $input['name'];
                $location->address = $input['address'];
                $location->telephone = $input['telephone'];
                $location->latitude = $input['latitude'];
                $location->longitude = $input['longitude'];
                $location->created_at = date('Y-m-d H:i:s');
                $location->updated_at = date('Y-m-d H:i:s');

                $location->save();
                Session::flash('success_message', "Location saved successfully.");
                return Redirect::to('admin/locations');
            }
        }
        return view('admin.locations.add', ['title' => $pageTitle, $activetab => 1]);
    }

    public function edit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-location');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actlocations';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Edit Location';
        $activetab = 'actlocations';

        $recordInfo = Location::where('id', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/locations');
        }

        if (request()->isMethod('post')) {
            $input = request()->all();

            $rules = array(
                'name' => 'required|max:50',
                'address' => 'required',
                'telephone' => 'required|min:6|max:10',
                'latitude' => 'required',
                'longitude' => 'required',
            );
            $customMessages = [
                'name.required' => 'The name is required field.',
                'address.required' => 'The address is required.',
                'telephone.required' => 'The telephone is required.',
                'telephone.min' => 'The telephone must be at least 6 digits.',
                'telephone.max' => 'The telephone may not be greater than 10 digits.',
                'latitude.required' => 'The latitude is required.',
                'longitude.required' => 'The longitude is required.',
            ];

            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                $recordInfo->name = $input['name'];
                $recordInfo->address = $input['address'];
                $recordInfo->telephone = $input['telephone'];
                $recordInfo->latitude = $input['latitude'];
                $recordInfo->longitude = $input['longitude'];

                $recordInfo->save();
                Session::flash('success_message', "Location updated successfully.");
                return redirect()->to('admin/locations');
            }
        }

        return view('admin.locations.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

    public function delete($slug = null)
    {
        if ($slug) {
            $update = Location::where('id', $slug)->delete();
            Session::flash('success_message', "Location deleted successfully.");
            echo $update;
            return Redirect::to('admin/locations');
        }
    }


}

?>