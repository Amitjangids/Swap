<?php

namespace App\Imports;

use App\Models\Bulk;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Cookie;
use Session;
use Redirect;
use Input;
use Validator;
use DB;
use IsAdmin;
use App\Models\Card;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Admin;
use Mail;
use App\Mail\SendMailable;
use App\Models\Carddetail;

class BulkImport implements ToCollection {

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $csv) {

        $currencyList = Currency::getCurrencyList();

        $excel_cardId = Session::get('excel_card');
        $recordInfo = Card::where('id', $excel_cardId)->first();
        

        $cslug = $recordInfo->slug;

        $errorFinal = '';
        $data = array();

        for ($i = 1; $i < Count($csv); $i++) {

            $error = '';
            $input = array();
            if ($csv[$i][0] == '') {
                $error .= 'Serial Code is required field for row ' . $i . '\n';
            } else if ($csv[$i][1] == '') {
                $error .= 'Pin Number is required field for row ' . $i . '\n';
            } else if ($csv[$i][2] == '') {
                $error .= 'Currency For Value is required field for row ' . $i . '\n';
            } else if ($csv[$i][3] == '') {
                $error .= 'Value is required field for row ' . $i . '\n';
            } else if ($csv[$i][4] == '') {
                $error .= 'Card Cost (IQD) is required field for row ' . $i . '\n';
            } else if ($csv[$i][5] == '') {
                $error .= 'Card Cost For Agent (IQD) is required field for row ' . $i . '\n';
            } else if ($csv[$i][6] == '') {
                $error .= 'Instruction is required field for row ' . $i . '\n';
            }

            if (!in_array(strtoupper($csv[$i][2]), $currencyList)) {
                $error .= 'Currency For Value is invalid for row ' . $i . '\n';
            }

            if (!is_numeric($csv[$i][3])) {
                $error .= 'Value should be numeric value for row ' . $i . '\n';
            }
            if (!is_numeric($csv[$i][4])) {
                $error .= 'Card Cost (IQD) should be numeric value for row ' . $i . '\n';
            }
            if (!is_numeric($csv[$i][5])) {
                $error .= 'Card Cost For Agent (IQD) should be numeric value for row ' . $i . '\n';
            }

            if (!empty($error)) {
                $errorFinal .= $error;
            } else {

                $input['card_id'] = $excel_cardId;
                $input['serial_number'] = $csv[$i][0];
                $input['pin_number'] = $csv[$i][1];
                $input['currency'] = $csv[$i][2];
                $input['real_value'] = $csv[$i][3];
                $input['card_value'] = $csv[$i][4];
                $input['agent_card_value'] = $csv[$i][5];
                $input['instruction'] = $csv[$i][6];

                $serialisedData = array();
                $serialisedData = $input;
                $serialisedData['created_at'] = date('Y-m-d H:i:s');
                $serialisedData['updated_at'] = date('Y-m-d H:i:s');
                $serialisedData['status'] = 1;
                $serialisedData['last_updated_by'] = Session::get('adminid');
                
                $data[] = $serialisedData;
//                echo '<pre>';
//                print_r($serialisedData);
                
                
//                $trans = new Bulk([
//                        'card_id' => $excel_cardId,
//                        'serial_number' => $csv[$i][0],
//                        'pin_number' => $csv[$i][1],
//                        'currency' => $csv[$i][2],
//                        'real_value' => $csv[$i][3],
//                        'card_value' => $csv[$i][4],
//                        'agent_card_value' => $csv[$i][5],
//                        'instruction' => $csv[$i][6],
//                        'status' => 1,
//                        'last_updated_by' => Session::get('adminid'),
//                        'created_at' => date('Y-m-d H:i:s'),
//                        'updated_at' => date('Y-m-d H:i:s'),
//                    ]);
//                    $trans->save();
//                    echo $TransId = $trans->id;
//                
                Carddetail::insert($serialisedData);
//                echo DB::getPdo()->lastInsertId();
//                
//                $recordInfo1 = Carddetail::where('id', DB::getPdo()->lastInsertId())->first();
//                echo '<pre>';print_r($recordInfo1);
//                continue;
            }
        }

        if (!empty($errorFinal)) {
            $message =  $errorFinal;
            Session::put('excel_message', $message);
            // return Redirect::to('/admin/cards/importcards/'.$cslug)->withErrors($errorFinal);
        } else {
//            DB::table('carddetails') -> insert($data);
            $message = 'Card details saved successfully.';
            Session::forget('excel_message');
//            exit;

//             Session::flash('success_message', "Card details saved successfully.");
        }
        
        


        // return Redirect::to('/admin/cards/importcards/'.$cslug);
    }

}
