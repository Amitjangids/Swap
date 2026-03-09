<?php 
namespace App\Exports;

use Illuminate\Support\Facades;
use App\Models\UploadedExcel; 
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet; 
use Auth;
use Carbon\Carbon;

class OperationOfMonthExport implements FromCollection,WithHeadings,WithEvents
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
        // $currentMonth = Carbon::now()->format('Y-m');
        
        // $records = UploadedExcel::orderBy('created_at', 'desc')
        //     ->join('users', 'uploaded_excels.user_id', '=', 'users.id')
        //     ->whereRaw("DATE_FORMAT(uploaded_excels.created_at, '%Y-%m') = ?", [$currentMonth])
        //     ->where('uploaded_excels.parent_id', $parent_id)
        //     ->whereIn('uploaded_excels.status', [1, 3, 4, 5, 6])
        //     ->select('uploaded_excels.*', 'uploaded_excels.id as id', 'users.name as name', 'users.email as email', 'users.user_type as user_type')
        //     ->get();

        $i=1;
        return $this->records->map(function ($record)  use (&$i){ 
            $amount= number_format($record->totat_amount, 0, '.', ',');

            $status = $record->remarks != "" ? 'Rejected' : $this->getStatusText($record->transaction_status);
            return [
                "id" => $i++,
                "reference_id" => $record->reference_id,
                "remarks" => $record->remarks != "" ?  ucfirst($record->remarks) : 'Salary', 
                "no_of_records" => $record->no_of_records,
                "created_at" => date('d M,y', strtotime($record->created_at)),
                "totat_amount" => CURR . ' ' . $amount,
                "total_fees" => CURR . ' ' . $record->total_fees,
                "status" => $this->getStatus($record->status), 
            ];
        }); 
        return $records;
    }

    public function headings(): array
    {
        return [
            'Id',
            'Txn. Reference number',
            'Purpose of payment' ,
            'No. of transactions',
            'Initiation date',
            'Amount',
            'Fees',
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
                $cellRange = 'A1:M1'; 
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
    public function getStatus($status)
    {
        $status_list = array(0 => 'Pending', 1 => 'Approved by submitter', 2 => 'Rejected by submitter', 3 => 'Approved by approver', 4 => 'Rejected by approver', 5 => 'Approved by merchant', 6 => 'Rejected by merchant');
        return $status_list[$status];
    }
}
