<?php
namespace App\Http\Controllers\Admin;
use App\Models\ReferralSetting;
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
use App\Models\Transactionfee;
use App\Models\Usertransactionfee;
use Illuminate\Support\Str;
use App\Kyc;
use Mail;
use App\Mail\SendMailable;

class TransactionfeesController extends Controller
{
    public function __construct()
    {
        $this->middleware('is_adminlogin');
    }

    public function index(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'transactionfees');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'acttranfees';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Transactionfees';
        $activetab = 'acttranfees';
        $query = new Transactionfee();
        $query = $query->sortable();


        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('transaction_type', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('last_updated_by')) {
            $last_updated_by = $request->get('last_updated_by');
            $query = $query->where(function ($q) use ($last_updated_by) {
                if ($last_updated_by != '') {
                    $q->where('last_updated_by', $last_updated_by);
                }
            });
        }

        $users = $query->orderBy('transaction_type', 'ASC')->paginate(20);
        if ($request->ajax()) {
            return view('elements.admin.transactionfees.index', ['allrecords' => $users]);
        }
        return view('admin.transactionfees.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users]);
    }

    public function delete($slug = null)
    {
        if ($slug) {
            Transactionfee::where('id', $slug)->delete();
            Session::flash('success_message', "Transactionfee details deleted successfully.");
            return Redirect::to('admin/transactionfees');
        }
    }




    public function edit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-transactionfees');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'acttranfees';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Edit Transaction Fee';
        $activetab = 'acttranfees';

        $recordInfo = Transactionfee::where('slug', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/transactionfees');
        }

        $input = Input::all();
        if (!empty($input)) {
            // Define validation rules
            $rules = [
                'type' => 'required',
                'amount_slab' => 'required',
                'feecharge' => 'required|numeric',
            ];

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/transactionfees/edit/' . $slug)->withErrors($validator)->withInput();
            } else {
                // Fetch existing data for the transaction fee
                $feeTransactionType = $input['type'];
                $data = DB::table('amount_slab')
                    ->whereRaw("CONCAT(min_amount, '-', max_amount) = ?", [$input['amount_slab']])
                    ->first();
                if ($input['feetype'] == '1' && $input['feecharge'] > $data->min_amount) {
                    return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors("Flat rate amount can't be greater than the minimum slab amount.");
                }

                if ($input['feetype'] == '0' && $input['feecharge'] >= 100) {
                    return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors("Percentage can't be greater than or equal to 100%.");
                }

                // Check if the combination already exists
                $existingRecord = DB::table('transactionfees')
                    ->where('transaction_type', $feeTransactionType)
                    ->where('slabId', $data->id)
                    ->first();

                if ($existingRecord && ($existingRecord->id != $recordInfo->id)) {
                    Session::flash('error_message', 'This combination already exists. Please select a different amount slab!');
                } else {
                    // Prepare data for updating
                    $updatedData = [
                        'transaction_type' => $feeTransactionType,
                        'slabId' => $data->id,
                        'min_amount' => $data->min_amount,
                        'max_amount' => $data->max_amount,
                    ];

                    // Update only the specified fields
                    if (isset($input['feetype'])) {
                        $updatedData['fee_type'] = $input['feetype'];
                    }
                    if (isset($input['feecharge'])) {
                        $updatedData['fee_amount'] = $input['feecharge'];
                    }

                    // Update the transaction fee record
                    Transactionfee::where('id', $recordInfo->id)->update($updatedData);

                    Session::flash('success_message', "Transaction fee details updated successfully.");
                    return Redirect::to('admin/transactionfees');
                }
            }
        }

        return view('admin.transactionfees.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }


    // public function edit($slug = null) {
    //     $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-transactionfees');
    //     if ($isPermitted == false) {
    //         $pageTitle = 'banners';
    //         $activetab = 'acttranfees';
    //         return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
    //     } 

    //     $pageTitle = 'Edit Transaction Fee';
    //     $activetab = 'acttranfees';

    //     $recordInfo = Transactionfee::where('slug', $slug)->first();
    //     if (empty($recordInfo)) {
    //         return Redirect::to('admin/transactionfees');
    //     }

    //     $input = Input::all();
    //     if (!empty($input)) {
    //         // Define validation rules
    //         $rules = [
    //             'type' => 'required',
    //             'amount_slab' => 'required',
    //             'feecharge' => 'required|numeric',
    //         ];

    //         // Add validation rule to ensure max_amount is greater than min_amount
    //         $rules['max_amount'] = 'required|numeric|min:' . $input['min_amount'];

    //         $validator = Validator::make($input, $rules);
    //         if ($validator->fails()) {
    //             return Redirect::to('/admin/transactionfees/edit/' . $slug)->withErrors($validator)->withInput();
    //         } else {
    //             // Fetch existing data for the transaction fee
    //             $feeTransactionType = $input['type'];
    //             $data = DB::table('amount_slab')
    //                 ->whereRaw("CONCAT(min_amount, '-', max_amount) = ?", [$input['amount_slab']])
    //                 ->first();
    //             if ($input['feetype'] == '1' && $input['feecharge'] > $data->min_amount) {
    //                 return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors( "Flat rate amount can't be greater than the minimum slab amount.");
    //             }

    //             if ($input['feetype'] == '0' && $input['feecharge'] >= 100) {
    //                 return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors( "Percentage can't be greater than or equal to 100%.");
    //             }

    //             // Check if the combination already exists
    //             $existingRecord = DB::table('transactionfees')
    //                 ->where('transaction_type', $feeTransactionType)
    //                 ->where('slabId', $data->id)
    //                 ->first();

    //             if ($existingRecord && ($existingRecord->id != $recordInfo->id)) {
    //                 Session::flash('error_message', 'This combination already exists. Please select a different amount slab!');
    //             } else {
    //                 // Prepare data for updating
    //                 $updatedData = [
    //                     'transaction_type' => $feeTransactionType,
    //                     'slabId' => $data->id,
    //                     'min_amount' => $data->min_amount,
    //                     'max_amount' => $data->max_amount,
    //                 ];

    //                 // Update only the specified fields
    //                 if (isset($input['feetype'])) {
    //                     $updatedData['fee_type'] = $input['feetype'];
    //                 }
    //                 if (isset($input['feecharge'])) {
    //                     $updatedData['fee_amount'] = $input['feecharge'];
    //                 }

    //                 // Update the transaction fee record
    //                 Transactionfee::where('id', $recordInfo->id)->update($updatedData);

    //                 Session::flash('success_message', "Transaction fee details updated successfully.");
    //                 return Redirect::to('admin/transactionfees');
    //             }
    //        }
    //     }

    //     return view('admin.transactionfees.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    // }

    // public function edit($slug = null) {
    //     $isPermitted = $this->validatePermission(Session::get('admin_role'), 'edit-transactionfees');
    //     if ($isPermitted == false) {
    //         $pageTitle = 'banners';
    //         $activetab = 'acttranfees';
    //         return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
    //     } 

    //     $pageTitle = 'Edit Transaction Fee';
    //     $activetab = 'acttranfees';

    //     $recordInfo = Transactionfee::where('slug', $slug)->first();
    //     if (empty($recordInfo)) {
    //         return Redirect::to('/admin/transactionfees');
    //     }

    //     $input = Input::all();

    //     // $min=$input['min'];
    //     // $max=$input['max'];

    //     if (!empty($input)) {
    //         // Define validation rules
    //         $rules = [
    //             'type' => 'required',
    //             'amount_slab' => 'required',
    //             'min_amount' => 'required|numeric',
    //             'max_amount' => 'required|numeric|gt:min_amount', // Ensure max_amount is greater than min_amount
    //             'feecharge' => 'required|numeric',
    //         ];

    //         $validator = Validator::make($input, $rules);
    //         $validator->sometimes('max_amount', 'gt:min_amount', function ($input) {
    //             // Add a custom validation rule to check if max_amount is greater than min_amount
    //             return $input->min_amount < $input->max_amount;
    //         });

    //         if ($validator->fails()) {
    //             return Redirect::to('/admin/transactionfees/edit/' . $slug)->withErrors($validator)->withInput();
    //         } else {

    //             // Fetch existing data for the transaction fee
    //             $feeTransactionType = $input['type'];
    //             $data = DB::table('amount_slab')
    //                 ->whereRaw("CONCAT(min_amount, '-', max_amount) = ?", [$input['amount_slab']])
    //                 ->first();
    //             if ($input['feetype'] == '1' && $input['feecharge'] > $data->min_amount) {
    //                 return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors( "Flat rate amount can't be greater than the minimum slab amount.");
    //             }

    //             if ($input['feetype'] == '0' && $input['feecharge'] >= 100) {
    //                 return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors( "Percentage can't be greater than or equal to 100%.");
    //             }

    //             // Check if the combination already exists
    //             $existingRecord = DB::table('transactionfees')
    //                 ->where('transaction_type', $feeTransactionType)
    //                 ->where('slabId', $data->id)
    //                 ->first();

    //             if ($existingRecord && ($existingRecord->id != $recordInfo->id)) {
    //                 Session::flash('error_message', 'This combination already exists. Please select a different amount slab!');
    //             } else {
    //                 // Prepare data for updating
    //                 $updatedData = [
    //                     'transaction_type' => $feeTransactionType,
    //                     'slabId' => $data->id,
    //                     'min_amount' => $data->min_amount,
    //                     'max_amount' => $data->max_amount,
    //                 ];

    //                 // Update only the specified fields
    //                 if (isset($input['feetype'])) {
    //                     $updatedData['fee_type'] = $input['feetype'];
    //                 }
    //                 if (isset($input['feecharge'])) {
    //                     $updatedData['fee_amount'] = $input['feecharge'];
    //                 }

    //                 // Update the transaction fee record
    //                 Transactionfee::where('id', $recordInfo->id)->update($updatedData);

    //                 Session::flash('success_message', "Transaction fee details updated successfully.");
    //                 return Redirect::to('/admin/transactionfees/');
    //             }
    //        }
    //     }

    //     return view('admin.transactionfees.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    // }




    //     public function transactionfee($slug = null, Request $request){
//         // $access = $this->getRoles(Session::get('adminid'),2);
//         // if($access == 0){
//         //     return Redirect::to('admin/admins/dashboard');
//         // }

    //         $pageTitle = 'Manage Transaction Fees'; 
//         $activetab = 'actusers';
//         $query = new Usertransactionfee();
//         $query = $query->sortable();

    //         $userInfo = User::where('slug', $slug)->first();
//         if($userInfo->user_type == 'Merchant' || $userInfo->user_type == 'agent'){
//             $uType = strtolower($userInfo->user_type).'s';
//         } else{
//             $uType = 'users';
//         }
//         $activetab = 'act'.$uType;
//         if ($request->has('keyword')) {
//             $keyword = $request->get('keyword');
//             $query = $query->where(function($q) use ($keyword) {
//                 $q->where('transaction_type', 'like', '%' . $keyword . '%');
//             });
//         }

    //         $fees = $query->where('user_id',$userInfo->id)->orderBy('id','DESC')->paginate(20);

    //         if($request->ajax()){
//             return view('elements.admin.transactionfees.transactionfee', ['allrecords'=>$fees]);
//         }
//         return view('admin.transactionfees.transactionfee', ['title'=>$pageTitle, $activetab=>1,'allrecords'=>$fees,'userInfo'=>$userInfo]);
//     }

    //     public function addfee($slug = null) {


    //         $pageTitle = 'Add User Transaction Fee';


    //         $userInfo = User::where('slug', $slug)->first();
//         if (empty($userInfo)) {
//             return Redirect::to('admin/agents');
//         }

    //         if($userInfo->user_type == 'Merchant' || $userInfo->user_type == 'agent'){
//             $uType = strtolower($userInfo->user_type).'s';
//         } else{
//             $uType = 'users';
//         }
//         $activetab = 'act'.$uType;
//         $input = Input::all();
//         if (!empty($input)) {
// //echo '<pre>';print_r($input);exit;
//             $rules = array(
//                 'transaction_type' => 'required',
//                 'user_charge' => 'required',
//             );
//             $validator = Validator::make($input, $rules);
//             if ($validator->fails()) {
//                 return Redirect::to('/admin/transactionfees/addfee/' . $slug)->withErrors($validator)->withInput();
//             } else {

    //                 $offerInfo = Usertransactionfee::where('user_id', $userInfo->id)->where('transaction_type', $input['transaction_type'])->first();
//                 if($offerInfo){
//                     return Redirect::to('/admin/transactionfees/addfee/' . $slug)->withErrors('Transaction fee already exist for selected user.');
//                 }

    //                 $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit

    //                 $serialisedData['user_id'] = $userInfo->id;
//                 $serialisedData['status'] = 1;
//                 $serialisedData['slug'] = time();
//                 $serialisedData['created_at'] = date('Y-m-d H:i:s');
//                 //echo '<pre>';print_r($serialisedData);exit;
//                 Usertransactionfee::insert($serialisedData);           

    //                 Session::flash('success_message', "User transaction fee details added successfully.");
//                 return Redirect::to('admin/transactionfees/transactionfee/'.$slug);
//             }
//         }
//         return view('admin.transactionfees.addfee', ['title' => $pageTitle, $activetab => 1, 'userInfo' => $userInfo,'slug'=>$slug]);
//     }

    //     public function deleteFee($tSlug = null, $slug=null){
//         if($slug){
//             Usertransactionfee::where('id', $slug)->delete();
//             Session::flash('success_message', "Transaction Fee details deleted successfully.");
//             return Redirect::to('admin/transactionfees/transactionfee/'.$tSlug);
//         }
//     }    

    //     public function editFee($uSlug = null, $slug = null) {
//         // $access = $this->getRoles(Session::get('adminid'),2);
//         // if($access == 0){
//         //     return Redirect::to('admin/admins/dashboard');
//         // }

    //         $pageTitle = 'Edit Transaction Fee';
// //        $activetab = 'actusers';

    //         $userInfo = User::where('slug', $uSlug)->first();
//         if (empty($userInfo)) {
//             return Redirect::to('admin/users');
//         }

    //         if($userInfo->user_type == 'Merchant' || $userInfo->user_type == 'agent'){
//             $uType = strtolower($userInfo->user_type).'s';
//         } else{
//             $uType = 'users';
//         }
//         $activetab = 'act'.$uType;
//         $recordInfo = Usertransactionfee::where('slug', $slug)->first();
//         if (empty($recordInfo)) {
//             return Redirect::to('admin/transactionfees/transactionfee/'.$uSlug);
//         }

    //         $input = Input::all();
//         if (!empty($input)) {
// //echo '<pre>';print_r($input);exit;
//             $rules = array(
//                 'user_charge' => 'required',
// //                'user_charge' => 'required',
//             );
//             $validator = Validator::make($input, $rules);
//             if ($validator->fails()) {
//                 return Redirect::to('/admin/transactionfees/editFee/' . $uSlug . '/'. $slug)->withErrors($validator)->withInput();
//             } else {

    //                 $serialisedData = $this->serialiseFormData($input, 1); //send 1 for edit
//                 Usertransactionfee::where('id', $recordInfo->id)->update($serialisedData);                

    //                 Session::flash('success_message', "Transaction fee details updated successfully.");
//                 return Redirect::to('admin/transactionfees/transactionfee/'.$uSlug);
//             }
//         }
//         return view('admin.transactionfees.editfee', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo,'userInfo'=>$userInfo]);
//     }

    //     public function activatefee($slug = null) {
//         if ($slug) {
//             Usertransactionfee::where('id', $slug)->update(array('status' => '1'));
//             return view('elements.admin.active_status', ['action' => 'admin/transactionfees/deactivatefee/' . $slug, 'status' => 1, 'id' => $slug]);
//         }
//     }

    //     public function deactivatefee($slug = null) {
//         if ($slug) {
//             Usertransactionfee::where('id', $slug)->update(array('status' => '0'));
//             return view('elements.admin.active_status', ['action' => 'admin/transactionfees/activatefee/' . $slug, 'status' => 0, 'id' => $slug]);
//         }
//     }


    public function amountSlab(Request $request)
    {

        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'amountSlab');
        if ($isPermitted == false) {
            $pageTitle = 'Manage Slab';
            $activetab = 'acttranfees1';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Slab';
        $activetab = 'acttranfees1';
        $perPage = 20;
        $recordInfo = DB::table('amount_slab')->orderBy('min_amount', 'asc')->paginate($perPage);

        if ($request->ajax()) {
            return view('elements.admin.transactionfees.slablist', ['allrecords' => $recordInfo]);
        }
        return view('admin.transactionfees.slablist', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $recordInfo]);
    }



    // public function addSlab(Request $request) {
    //     $isPermitted = $this->validatePermission(Session::get('admin_role'), 'addSlab');
    //     if ($isPermitted == false) {
    //         $pageTitle = 'Add Slab';
    //         $activetab = 'acttranfees1';
    //         return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
    //     }

    //     $pageTitle = 'Add Slab';
    //     $activetab = 'acttranfees1';
    //     $input = Input::all();

    //     if (!empty($input)) {
    //         $rules = array(
    //             'min' => 'required|numeric',
    //             'max' => 'required|numeric',
    //         );

    //         $validator = Validator::make($input, $rules);
    //         if ($validator->fails()) {
    //             return Redirect::to('/admin/transactionfees/addSlab')->withErrors($validator);
    //         } else {

    //             $mininput = $request->input('min');
    //             $maxinput = $request->input('max');

    //             $amount_slab = DB::table('amount_slab')->where('min_amount','<=',$mininput)->where('max_amount','>=',$maxinput)->count();
    //             if($amount_slab > 0)
    //             {
    //                 Session::flash('error_message', 'Slab already exists!');
    //                 return Redirect::to('/admin/transactionfees/addSlab')->withInput()->withErrors($validator);
    //             }

    //             $amount_slab1 = DB::table('amount_slab')->where('max_amount','>=',$mininput)->where('min_amount','<=',$maxinput)->count();
    //             if($amount_slab1 > 0)
    //             {
    //                 Session::flash('error_message', 'Slab already exists!');
    //                 return Redirect::to('/admin/transactionfees/addSlab')->withInput()->withErrors($validator);
    //             }

    //             DB::table('amount_slab')->insertOrIgnore([
    //                 'min_amount' => $mininput,
    //                 'max_amount' => $maxinput,
    //             ]);
    //             Session::flash('success_message', "Slab created successfully.");
    //             return Redirect::to('/admin/transactionfees/amountSlab');
    //         }
    //     }

    //     return view('admin.transactionfees.addslab', ['title' => $pageTitle, $activetab=>1]);
    // }



    // public function editSlab(Request $request,$id) {
    //     $isPermitted = $this->validatePermission(Session::get('admin_role'), 'addSlab');
    //     if ($isPermitted == false) {
    //         $pageTitle = 'Edit Slab';
    //         $activetab = 'acttranfees1';
    //         return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
    //     }

    //     $pageTitle = 'Edit Slab';
    //     $activetab = 'acttranfees1';
    //     $userInfo =  DB::table('amount_slab')->where('id',$id)->first();

    //     $input = Input::all();

    //     if (!empty($input)) {
    //         $rules = array(
    //             'min' => 'required|numeric',
    //             'max' => 'required|numeric',
    //         );

    //         $validator = Validator::make($input, $rules);
    //         if ($validator->fails()) {
    //             return Redirect::to('/admin/transactionfees/editSlab/'.$id)->withErrors($validator);
    //         } else {

    //             // echo "<pre>";
    //             // print_r($input);
    //             $mininput = $request->input('min');
    //             $maxinput = $request->input('max');
    //             $amount_slab = DB::table('amount_slab')->where('min_amount','>=',$mininput)->where('max_amount','<=',$maxinput)->where('id','!=',$id)->count();
    //             if($amount_slab > 0)
    //             {
    //                 Session::flash('error_message', 'Slab already exists!');
    //                 return Redirect::to('/admin/transactionfees/editSlab/'.$id)->withInput()->withErrors($validator);
    //             }

    //             $amount_slab1 = DB::table('amount_slab')->where('id','!=',$id)->get();
    //             foreach($amount_slab1 as $slab)
    //             {
    //                if($mininput >= $slab->min_amount && $maxinput <= $slab->max_amount || $maxinput  >= $slab->min_amount && $mininput <= $slab->max_amount)
    //                {
    //                 Session::flash('error_message', 'Slab already exists!');
    //                 return Redirect::to('/admin/transactionfees/editSlab/'.$id)->withInput()->withErrors($validator);
    //                }
    //             }


    //             DB::table('amount_slab')->where('id', $id)->update([
    //                 'min_amount' => $mininput,
    //                 'max_amount' => $maxinput,
    //             ]);
    //             Session::flash('success_message', "Slab has been updated successfully.");
    //             return Redirect::to('/admin/transactionfees/amountSlab');


    //         }
    //     }


    //     return view('admin.transactionfees.editSlab', ['title' => $pageTitle, $activetab=>1,'allrecords'=>$userInfo]);
    // }

    public function addSlab(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'addSlab');
        if ($isPermitted == false) {
            $pageTitle = 'Add Slab';
            $activetab = 'acttranfees1';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add Slab';
        $activetab = 'acttranfees1';
        $input = Input::all();

        if (!empty($input)) {
            $rules = array(
                'min' => 'required|numeric',
                'max' => 'required|numeric',
            );

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/transactionfees/addSlab')->withErrors($validator);
            } else {

                $mininput = $request->input('min');
                $maxinput = $request->input('max');
                if ($maxinput <= $mininput) {
                    // return redirect('/admin/transactionfees/editSlab/'.$id)->withErrors(['error_message' => 'Max amount must be greater than the min amount']);
                    return redirect()->to('admin/transactionfees/amountSlab')->withErrors("Max amount must be greater than the min amount.");
                } else {

                    $amount_slab = DB::table('amount_slab')->where('min_amount', '<=', $mininput)->where('max_amount', '>=', $maxinput)->count();
                    if ($amount_slab > 0) {
                        Session::flash('error_message', 'Slab already exists!');
                        return Redirect::to('/admin/transactionfees/addSlab')->withInput()->withErrors($validator);
                    }

                    $amount_slab1 = DB::table('amount_slab')->where('max_amount', '>=', $mininput)->where('min_amount', '<=', $maxinput)->count();
                    if ($amount_slab1 > 0) {
                        Session::flash('error_message', 'Slab already exists!');
                        return Redirect::to('/admin/transactionfees/addSlab')->withInput()->withErrors($validator);
                    }

                    DB::table('amount_slab')->insertOrIgnore([
                        'min_amount' => $mininput,
                        'max_amount' => $maxinput,
                    ]);
                    Session::flash('success_message', "Slab created successfully.");
                    return Redirect::to('/admin/transactionfees/amountSlab');
                }
            }
        }

        return view('admin.transactionfees.addslab', ['title' => $pageTitle, $activetab => 1]);
    }



    // public function editSlab(Request $request,$id) {
    //     $isPermitted = $this->validatePermission(Session::get('admin_role'), 'addSlab');
    //     if ($isPermitted == false) {
    //         $pageTitle = 'Edit Slab';
    //         $activetab = 'acttranfees1';
    //         return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
    //     }

    //     $pageTitle = 'Edit Slab';
    //     $activetab = 'acttranfees1';
    //     $userInfo =  DB::table('amount_slab')->where('id',$id)->first();

    //     $input = Input::all();

    //     if (!empty($input)) {
    //         $rules = array(
    //             'min' => 'required|numeric',
    //             'max' => 'required|numeric',
    //         );

    //         $validator = Validator::make($input, $rules);
    //         if ($validator->fails()) {
    //             return Redirect::to('/admin/transactionfees/editSlab/'.$id)->withErrors($validator);
    //         } else {

    //             // echo "<pre>";
    //             // print_r($input);
    //             $mininput = $request->input('min');
    //             $maxinput = $request->input('max');
    //             $amount_slab = DB::table('amount_slab')->where('min_amount','>=',$mininput)->where('max_amount','<=',$maxinput)->where('id','!=',$id)->count();
    //             if($amount_slab > 0)
    //             {
    //                 Session::flash('error_message', 'Slab already exists!');
    //                 return Redirect::to('/admin/transactionfees/editSlab/'.$id)->withInput()->withErrors($validator);
    //             }

    //             $amount_slab1 = DB::table('amount_slab')->where('id','!=',$id)->get();
    //             foreach($amount_slab1 as $slab)
    //             {
    //                if($mininput >= $slab->min_amount && $maxinput <= $slab->max_amount || $maxinput  >= $slab->min_amount && $mininput <= $slab->max_amount)
    //                {
    //                 Session::flash('error_message', 'Slab already exists!');
    //                 return Redirect::to('/admin/transactionfees/editSlab/'.$id)->withInput()->withErrors($validator);
    //                }
    //             }


    //             DB::table('amount_slab')->where('id', $id)->update([
    //                 'min_amount' => $mininput,
    //                 'max_amount' => $maxinput>$mininput,
    //             ]);
    //             Session::flash('success_message', "Slab has been updated successfully.");
    //             return Redirect::to('/admin/transactionfees/amountSlab');


    //         }
    //     }


    //     return view('admin.transactionfees.editSlab', ['title' => $pageTitle, $activetab=>1,'allrecords'=>$userInfo]);
    // }

    public function editSlab(Request $request, $id)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'addSlab');
        if ($isPermitted == false) {
            $pageTitle = 'Edit Slab';
            $activetab = 'acttranfees1';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Edit Slab';
        $activetab = 'acttranfees1';
        $userInfo = DB::table('amount_slab')->where('id', $id)->first();

        $input = $request->all();
        // echo"<pre>";print_r($input );die;
        if (!empty($input)) {
            $rules = [
                'min' => 'required|numeric',
                'max' => 'required|numeric|min:' . $input['min'], // max should be greater than or equal to min
            ];

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                $mininput = $input['min'];
                $maxinput = $input['max'];
                if ($maxinput <= $mininput) {
                    // return redirect('/admin/transactionfees/editSlab/'.$id)->withErrors(['error_message' => 'Max amount must be greater than the min amount']);
                    return redirect()->to('admin/transactionfees/amountSlab')->withErrors("Max amount must be greater than the min amount.");
                } else {
                    // Check if the slab already exists
                    $amount_slab = DB::table('amount_slab')
                        ->where('min_amount', '>=', $mininput)
                        ->where('max_amount', '<=', $maxinput)
                        ->where('id', '!=', $id)
                        ->count();

                    if ($amount_slab > 0) {
                        $validator->errors()->add('max', 'Slab already exists!');
                        return redirect()->back()->withErrors($validator)->withInput();
                    }

                    // Check if the new slab overlaps with existing slabs
                    $amount_slab1 = DB::table('amount_slab')->where('id', '!=', $id)->get();
                    foreach ($amount_slab1 as $slab) {
                        if (
                            ($mininput >= $slab->min_amount && $mininput <= $slab->max_amount) ||
                            ($maxinput >= $slab->min_amount && $maxinput <= $slab->max_amount)
                        ) {
                            $validator->errors()->add('max', 'Slab overlaps with an existing slab!');
                            return redirect()->back()->withErrors($validator)->withInput();
                        }
                    }

                    // Update the slab
                    DB::table('amount_slab')->where('id', $id)->update([
                        'min_amount' => $mininput,
                        'max_amount' => $maxinput,
                    ]);

                    Session::flash('success_message', "Slab has been updated successfully.");
                    return redirect('/admin/transactionfees/amountSlab');
                }
            }
        }



        return view('admin.transactionfees.editSlab', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $userInfo]);
    }



    public function addConfiguration()
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'addConfiguration');
        if ($isPermitted == false) {
            $pageTitle = 'banners';
            $activetab = 'acttranfees';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Add Slab';
        $activetab = 'acttranfees';
        $input = Input::all();

        //  echo"<pre>";print_r($input);die;
        if (!empty($input)) {
            $rules = array(
                'type' => 'required',
                'amount_slab' => 'required',
                'feetype' => 'required',
                'feecharge' => 'required|numeric',
            );

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/transactionfees/addConfiguration')->withErrors($validator);
            } else {

                $feeTransactionType = $input['type'];
                $amountSlabId = $input['amount_slab'];

                $existingRecord = DB::table('transactionfees')
                    ->where('transaction_type', $feeTransactionType)
                    ->where('slabId', $amountSlabId)
                    ->first();

                if ($existingRecord) {
                    Session::flash('error_message', 'This combination already exists. Please select a different amount slab!');
                } else {
                    $slug = $this->createSlug($feeTransactionType, 'transactionfees');

                    $data = DB::table('amount_slab')->where('id', $input['amount_slab'])->first();

                    if ($input['feetype'] == '1' && $input['feecharge'] > $data->min_amount) {
                        return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors("Flat rate amount can't be greater than the minimum slab amount.");
                    }

                    if ($input['feetype'] == '0' && $input['feecharge'] >= 100) {
                        return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors("Percentage can't be greater than or equal to 100%.");
                    }


                    $user = new Transactionfee([
                        'transaction_type' => $feeTransactionType,
                        'slabId' => $amountSlabId,
                        'min_amount' => $data->min_amount,
                        'max_amount' => $data->max_amount,
                        'fee_type' => $input['feetype'],
                        'fee_amount' => $input['feecharge'],
                        'slug' => $slug,
                    ]);

                    $user->save();
                    Session::flash('success_message', "Fee has been save successfully.");
                }
                return Redirect::to('/admin/transactionfees');
            }
        }



        return view('admin.transactionfees.add', ['title' => $pageTitle, $activetab => 1]);
    }

    public function referralSetting(Request $request)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'referral-setting');
        if ($isPermitted == false) {
            $pageTitle = 'Referral Setting';
            $activetab = 'actreferral';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Manage Transactionfees';
        $activetab = 'actreferralfees';
        $query = new ReferralSetting();
        $query = $query->sortable();


        if ($request->has('keyword')) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($q) use ($keyword) {
                $q->where('type', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->has('last_updated_by')) {
            $last_updated_by = $request->get('last_updated_by');
            $query = $query->where(function ($q) use ($last_updated_by) {
                if ($last_updated_by != '') {
                    $q->where('last_updated_by', $last_updated_by);
                }
            });
        }

        $users = $query->orderBy('type', 'ASC')->paginate(20);
        if ($request->ajax()) {
            return view('elements.admin.referral-setting.index', ['allrecords' => $users]);
        }
        return view('admin.referral-setting.index', ['title' => $pageTitle, $activetab => 1, 'allrecords' => $users]);
    }

    public function referralSettingEdit($slug = null)
    {
        $isPermitted = $this->validatePermission(Session::get('admin_role'), 'referral-setting-edit');
        if ($isPermitted == false) {
            $pageTitle = 'Referral Setting Edit';
            $activetab = 'actreferralsetting';
            return view('admin.admins.notPermitted', ['title' => $pageTitle, $activetab => 1]);
        }

        $pageTitle = 'Edit Referral Setting';
        $activetab = 'actreferralsetting';

        $recordInfo = ReferralSetting::where('id', $slug)->first();
        if (empty($recordInfo)) {
            return Redirect::to('admin/referral-setting');
        }

        $input = Input::all();
        if (!empty($input)) {
            // Define validation rules
            $rules = [
                'type' => 'required',
                'fee_value' => 'required',
            ];

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return Redirect::to('/admin/referral-setting-edit/' . $slug)->withErrors($validator)->withInput();
            } else {
                /* $feeTransactionType = $input['fee_type'];
                if ($input['fee_type'] == '0' && $input['fee_value'] >= 100) {
                    // return redirect()->to('/admin/transactionfees/addConfiguration')->withErrors( "Percentage can't be greater than or equal to 100%.");
                    return redirect()->to('/admin/referral-setting-edit/' . $slug)->withErrors("Percentage can't be greater than or equal to 100%.");
                } */
                // Prepare data for updating
                $updatedData = [
                    'type' =>  $input['type'],
                    'fee_type' => 1,
                    'fee_value' => $input['fee_value'],
                ];

                // Update the transaction fee record
                ReferralSetting::where('id', $recordInfo->id)->update($updatedData);

                Session::flash('success_message', "Referral fee details updated successfully.");
                return Redirect::to('admin/referral-setting');
            }
        }

        return view('admin.referral-setting.edit', ['title' => $pageTitle, $activetab => 1, 'recordInfo' => $recordInfo]);
    }


}
?>