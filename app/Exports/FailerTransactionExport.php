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

class FailerTransactionExport implements FromCollection, WithHeadings, WithEvents
{
    use Exportable;

    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        $i = 1;
        return $this->records->map(function ($record) use (&$i) {
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
                "submitted_date" => $record->created_at ? date(ONLY_DATE, strtotime($record->created_at)) : '-',
                "approved_by" => $record->approver_name ? $record->approver_name : '-', 
                "approved_date" => $record->approved_date ? date(ONLY_DATE, strtotime($record->approved_date)) : '-',
                "merchant_by" => $record->merchant_by ? $record->merchant_by : '-',
                "approved_merchant_date" => $record->approved_merchant_date ? date(ONLY_DATE, strtotime($record->approved_merchant_date)) : '-',
                "gimac_status" => $record->remarks ? $record->remarks : '-',
            ];
        });
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
            'Reason For Rejection',
        ];
    }

    public function registerEvents(): array
    {
        $styleArray = [
            'font' => [
                'name'      =>  'Calibri',
                'size'      =>  13,
                'bold'      =>  true,
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
                ],
            ],
        ];  

        return [
            AfterSheet::class => function(AfterSheet $event) use($styleArray, $styleArray1) {
                $cellRange = 'A1:O1'; 
                $event->sheet->getDelegate()->setAutoFilter('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->applyFromArray($styleArray);

                foreach (range('A', $event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->getColumnDimension($col)->setAutoSize(true);
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
            
            default:
                return "-"; 
        }
    }
}
