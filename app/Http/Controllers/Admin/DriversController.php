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
use App\Models\Driver;
use App\Models\DriverActivationCard;
use App\Role;
use App\Models\Country;
use Mail;
use App\Mail\SendMailable;
use Illuminate\Validation\Rule;

class DriversController extends Controller
{

    public function __construct()
    {
        $this->middleware('is_adminlogin');
        //        $this->middleware('is_subadminlogin');
    }

    public function index(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'driver-list');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actdrivers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Drivers';
        $activetab = 'actdrivers';
        $query = new Driver();
        $query = $query->whereIn('status', ['0', '1']);

        $query = $query->sortable();

        if ($request->has('chkRecordId') && $request->has('action')) {
            $idList = $request->get('chkRecordId');
            $action = $request->get('action');

            if ($action == "Activate") {
                Driver::whereIn('id', $idList)->update(array('status' => 1));
                Session::flash('success_message', "Records are activate successfully.");
            } else if ($action == "Deactivate") {
                Driver::whereIn('id', $idList)->update(array('status' => 0));
                Session::flash('success_message', "Records are deactivate successfully.");
            } else if ($action == "Delete") {
                Driver::whereIn('id', $idList)->delete();
                Session::flash('success_message', "Records are deleted successfully.");
            }
        }

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%');
            });
        }

        $drivers = $query->orderBy('id', 'DESC')->paginate(20);

        if ($request->ajax()) {
            return view('elements.admin.drivers.index', ['allrecords' => $drivers]);
        }
        return view('admin.drivers.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $drivers]);
    }

    public function add()
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'add-driver');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actdrivers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add Driver';
        $activetab = 'actdrivers';
        $roleArr = [];
        $input = Input::all();

        if (!empty($input)) {
            $rules = array(
                'name' => 'required|max:50',
                'email' => 'required|email|max:50|unique:drivers',
                'phone' => 'required|min:6|max:10|unique:drivers',
                'companyName' => 'required',
            );
            $customMessages = [
                'name.required' => 'The name is required field.',
                'email.required' => 'The email is required.',
                'email.email' => 'The email must be a valid email address.',
                'email.max' => 'The email may not be greater than 50 characters.',
                'email.unique' => 'This email is already taken.',

                'phone.required' => 'The phone is required.',
                'phone.min' => 'The phone must be at least 6 digits.',
                'phone.max' => 'The phone may not be greater than 10 digits.',
                'phone.unique' => 'This phone number is already taken.',
                'companyName.required' => 'The company name is required.',
            ];
            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return Redirect::to('/admin/drivers/add-driver')->withErrors($validator)->withInput();
            } else {

                $driver = new Driver([
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'phone' => $input['phone'],
                    'companyName' => $input['companyName'],
                    // 'password' => $this->encpassword($input['password']),
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $driver->save();
                Session::flash('success_message', "Driver details saved successfully.");
                return Redirect::to('admin/drivers');
            }
        }
        return view('admin.drivers.add', ['title' => $pageTitle, $activetab => 1]);
    }

    public function edit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-driver');
        if ($isPermitted == false) {
            $pageTitle = 'Not Permitted';
            $activetab = 'actdrivers';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Edit Driver';
        $activetab = 'actdrivers';
        $roleArr = [];

        $roles = Role::whereNotIn('id', [1, 27])->orderBy('id', 'ASC')->get();
        foreach ($roles as $role) {
            $roleArr[$role->id] = $role->role_name;
        }

        $recordInfo = Driver::where('id', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/drivers');
        }

        if (request()->isMethod('post')) {
            $input = request()->all();

            $rules = array(
                'name' => 'required|max:50',
                'companyName' => 'required',
                'email' => [
                    'required',
                    'email',
                    'max:50',
                    Rule::unique('drivers')->ignore($recordInfo->id),
                ],
                'phone' => [
                    'required',
                    'min:6',
                    'max:10',
                    Rule::unique('drivers')->ignore($recordInfo->id),
                ],
            );
            $customMessages = [
                'name.required' => 'The name is required field.',
                'email.required' => 'The email is required.',
                'email.email' => 'The email must be a valid email address.',
                'email.max' => 'The email may not be greater than 50 characters.',
                'email.unique' => 'This email is already taken.',

                'phone.required' => 'The phone is required.',
                'phone.min' => 'The phone must be at least 6 digits.',
                'phone.max' => 'The phone may not be greater than 10 digits.',
                'phone.unique' => 'This phone number is already taken.',
                'companyName.required' => 'The company name is required.',
            ];

            $validator = Validator::make($input, $rules, $customMessages);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                $recordInfo->name = $input['name'];
                $recordInfo->email = $input['email'];
                $recordInfo->phone = $input['phone'];
                $recordInfo->companyName = $input['companyName'];

                $recordInfo->save();
                Session::flash('success_message', "Driver details updated successfully.");
                return redirect()->to('admin/drivers');
            }
        }

        return view('admin.drivers.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }


    public function activate($slug = null)
    {
        if ($slug) {
            Driver::where('id', $slug)->update(array('status' => '1'));
            return view('elements.admin.update_status', ['action' => 'admin/driver/deactivate/' . $slug, 'status' => 1, 'id' => $slug]);
        }
    }

    public function deactivate($slug = null)
    {
        if ($slug) {
            Driver::where('id', $slug)->update(array('status' => '0'));
            return view('elements.admin.update_status', ['action' => 'admin/driver/activate/' . $slug, 'status' => 0, 'id' => $slug]);
        }
    }

    public function delete($slug = null)
    {
        if ($slug) {
            $update = Driver::where('id', $slug)->update(['status' => 2]);
            Session::flash('success_message', "Driver details deleted successfully.");
            echo $update;
            return Redirect::to('admin/drivers');
        }
    }
    public function viewActivationCard(Request $request, $slug = null)
    {
        $pageTitle = 'Manage Activation Cards';
        $activetab = 'actActCard';

        // Start the query builder
        $query = DriverActivationCard::query()
            ->select('driver_activation_cards.*','users.name as firstName', 'users.lastName', 'users.accountId', 'drivers.name as driverName','users.cardType')
            ->join('users', 'users.id', '=', 'driver_activation_cards.userId')
            ->join('drivers', 'drivers.id', '=', 'driver_activation_cards.driverId')
            ->where('driver_activation_cards.driverId', $slug)
            ->sortable();

        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query->where(function ($q) use ($keyword) {
                    $q->whereRaw("CONCAT(users.name, ' ', users.lastName) LIKE ?", '%' . $keyword . '%')
                    ->orWhere('drivers.name', 'like', '%' . $keyword . '%')
                    ->orWhere('users.accountId', 'like', '%' . $keyword . '%');
            });
        }
        $allrecords = $query->orderBy('driver_activation_cards.id', 'DESC')->paginate(20);
        return view('admin.drivers.view-activation-card', [
            'title' => $pageTitle,
            $activetab => 1,
            'allrecords' => $allrecords
        ]);
    }


}

?>