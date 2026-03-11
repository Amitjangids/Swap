<?php 
namespace App\Exports;

use Illuminate\Support\Facades;
use App\Models\ExcelTransaction; 
use App\Models\OnafriqaData; 
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet; 
use Auth;

class SuccessTransactionExport implements FromCollection,WithHeadings,WithEvents
{
    use Exportable;

    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        // Fetch the data based on user_id and search
        // $parent_id = Auth::user()->user_type == "Merchant" ? Auth::user()->id : Auth::user()->parent_id; 
        
        // $records = ExcelTransaction::where('excel_transactions.parent_id', $parent_id)->where('transactions.status', 1)
        //     ->leftJoin('users as submitter', 'excel_transactions.submitted_by', '=', 'submitter.id')
        //     ->leftJoin('users as approver', 'excel_transactions.approved_by', '=', 'approver.id')
        //     ->leftJoin('countries', 'excel_transactions.country_id', '=', 'countries.id')
        //     ->leftJoin('wallet_managers', 'excel_transactions.wallet_manager_id', '=', 'wallet_managers.id')
        //     ->leftJoin('transactions', 'excel_transactions.id', '=', 'transactions.excel_trans_id')
        //     ->select('excel_transactions.*', 'submitter.name as submitted_by', 'approver.name as approved_by', 'countries.name as country_name', 'wallet_managers.name as wallet_name', 'transactions.status as transaction_status')
        //     ->get();

            $i=1;
            return $this->records->map(function ($record)  use (&$i){ 
                $OnafriqaData = OnafriqaData::where('excelTransId', $record->id)->first();
                
            $amount = number_format($record->amount, 0, '.', ',');

            $status = $record->remarks != "" ? 'Rejected' : $this->getStatusText($record->transaction_status);
            if (!empty($record->bdastatus) && $record->bdastatus == "ONAFRIQ") {
                $firstName = $OnafriqaData->recipientName;
                $lastName = $OnafriqaData->recipientSurname;
            } else {
                $firstName = $record->first_name;
                $lastName = $record->name;
            }
            $dateA = "d M, Y";
                $dateB = "d M, Y";
                $dateC = "d M, Y";
            
            return [
                "id" => $i++,
                "first_name" => $firstName != '' ? $firstName : '-',
                "name" => $lastName != '' ? $lastName : '-',
                "comment" => $record->comment != '' ? $record->comment : '-',
                /* "country_name" => $record->country_name != '' ?  $record->country_name : '-',
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : '-',
                "tel_number" => $record->tel_number, */
                "country_name" => $record->country_name != '' ? $record->country_name : (isset($OnafriqaData->recipientCountry) && $OnafriqaData->recipientCountry != '' ? $this->getCountryStatus($OnafriqaData->recipientCountry) : '-'),
                "wallet_name" => $record->wallet_name != '' ? $record->wallet_name : (isset($OnafriqaData->walletManager) && $OnafriqaData->walletManager != '' ? $OnafriqaData->walletManager : '-'),
                "tel_number" => $record->tel_number != '' ? $record->tel_number : (isset($OnafriqaData->recipientMsisdn) && $OnafriqaData->recipientMsisdn != '' ? $OnafriqaData->recipientMsisdn : '-') ,
                "amount" => CURR . ' ' .$amount ,
                "submitted_by" => $record->submitted_by,
                "submitted_date" => $record->created_at ? date($dateA, strtotime($record->created_at)) : '-',
                "approved_by" => $record->approver_name ? $record->approver_name : '-', 
                "approved_date" => $record->approved_date ? date($dateB, strtotime($record->approved_date)) : '-',
                "merchant_by" => $record->merchant_by ? $record->merchant_by : '-',
                "approved_merchant_date" => $record->approved_merchant_date ? date($dateC, strtotime($record->approved_merchant_date)) : '-',
                "gimac_status" => $status,
            ];
        }); 
        return $records;
    }

    public function headings(): array
    {
        return [
            'Id',
            'First Name',
            'Last Name',
            'Comment',
            'Country',
            'Wallet Manager',
            'Phone Number',
            'Amount',
            'Submitted By',
            'Submitted Date',
            'Approved/Rejected By',
            'Approved/Rejected Date',
            'Merchant Approval/Rejected By',
            'Merchant Approval/Rejected Date',
            'Status',
        ];
    

    }
    public function registerEvents(): array
    {

        $styleArray = [
            'font' => [
                'name'      =>  'Calibri',
                'size'      =>  13,
                'bold'      =>  true,
             //   'color' => ['argb' => 'EB2B02'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'dff0d8',
                ]           
            ],
        ];

        $styleArray1 = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                //'color' => ['argb' => 'FFFF0000'],
                ],
            ],
        ];  

        return [
            AfterSheet::class    => function(AfterSheet $event) use($styleArray,$styleArray1) {
                $cellRange = 'A1:O1'; 
                $event->sheet->getDelegate()->setAutoFilter('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);
                /*$last_row=$event->sheet->getHighestRow();
                $heighest_row=$event->sheet->getHighestRow()+1;

                $cellRange = 'A'.$heighest_row.':L'.$heighest_row; 
                $event->sheet->setCellValue('J'. ($heighest_row), '=SUM(J2:J'.$last_row.')');
                $event->sheet->setCellValue('K'. ($heighest_row), '=SUM(K2:K'.$last_row.')');
                $event->sheet->setCellValue('L'. ($heighest_row), '=SUM(L2:L'.$last_row.')');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);

                $event->sheet->setCellValue('I'. ($heighest_row), 'Total');

                $event->sheet->getStyle('A'.$heighest_row.':L'.$heighest_row)->ApplyFromArray($styleArray1);
                $event->sheet->getStyle('A1:L'.$heighest_row)->ApplyFromArray($styleArray1);*/

                foreach (range('A',$event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->
                    getColumnDimension($col)
                    ->setAutoSize(true);
                } 
            },
        ];
    }
    private function getStatusText($status)
    {

        if ($status != "") {
            $statusArr = array('1' => 'Completed', '2' => 'Pending', '3' => 'Failed', '4' => 'Reject', '5' => 'Refund', '6' => 'Refund Completed');
            return $statusArr[$status];
        }

        return '-';
    }
    public function getCountryStatus($countryShortName)
    {
        switch ($countryShortName) {
            case 'BJ':
                return "Benin";
            case 'BF':
                return "Burkina Faso";
            case 'GW':
                return "Guinea Bissau";
            case 'ML':
                return "Mali"; 
            case 'NE':
                return "Niger"; 
            case 'SN':
                return "Senegal"; 
            case 'TG':
                return "Togo"; 
            case 'CI':
                return "Ivoiry Coast"; 
            
            default:
                return "-"; 
        }
    }
}
