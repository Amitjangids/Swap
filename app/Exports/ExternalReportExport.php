<?php

namespace App\Exports;
use Illuminate\Support\Facades;
use Session;
use App\Models\BarUser;
use App\Models\BarOwner;
use App\Models\User;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;


class ExternalReportExport implements FromCollection,WithHeadings,WithEvents
{
    protected $records;
    public function __construct($records)
    {
        $this->records = $records;
        Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
            $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
        });
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
      $columnName = Session::get('columnName');
      if (empty($columnName)) {
          $columnName = 'id';
      }
     
       $result = array();
       $i=1;
       foreach($this->records as $record){

        if($record->status == '1'){
            $status="Completed" ;
          }elseif($record->status == '2'){
            $status="Pending";
          }elseif($record->status == '3'){
            $status="Failed";
          }elseif($record->status == '4'){
            $status="Cancelled";	
          }

          $result[] = array(
             'id'=>$i,
             'Sender Name' =>"Airtel Money",
             'Sender Phone' => $record->receiver_mobile,
             "Receiver Name" => $record->name, 
             "Receiver Phone" => $record->phone, 
             "Amount"=>$record->amount,
             "Transaction Fee"=>$record->transaction_amount,
             "Total Amount"=>$record->total_amount,
             "Transaction ID" => $record->refrence_id,
             "Status" =>$status,
             "Transaction Date" =>$record->created_at->format('M d, Y h:i:s A'),
          );
          $i++;
        }
        // $result
        return collect($result);
      
    }

    public function headings(): array
    {
       return [
         '#',
         'Sender Name',
         'Sender Phone',
         'Receiver Name',
         'Receiver Phone',
         'Amount',
         'Transaction Fee',
         'Total Amount',
         'Transaction ID',
         'Status',
         'Transaction Date',
       ];
    }

    public function registerEvents(): array
    {

        $styleArray = [
            'font' => [
                'name'      =>  'Calibri',
                'size'      =>  10,
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
                $cellRange = 'A1:K1'; 
                $event->sheet->getDelegate()->setAutoFilter('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(10);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);
                $last_row=$event->sheet->getHighestRow();
                $heighest_row=$event->sheet->getHighestRow()+1;

                $cellRange = 'A'.$heighest_row.':K'.$heighest_row; 
                $event->sheet->setCellValue('F'. ($heighest_row), '=SUM(F2:F'.$last_row.')');
                $event->sheet->setCellValue('G'. ($heighest_row), '=SUM(G2:G'.$last_row.')');
                $event->sheet->setCellValue('H'. ($heighest_row), '=SUM(H2:H'.$last_row.')');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(10);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);

                $event->sheet->setCellValue('E'. ($heighest_row), 'Total');

                $event->sheet->getStyle('A'.$heighest_row.':K'.$heighest_row)->ApplyFromArray($styleArray1);
                $event->sheet->getStyle('A1:K'.$heighest_row)->ApplyFromArray($styleArray1);

                foreach (range('A',$event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->
                            getColumnDimension($col)
                            ->setAutoSize(true);
                } 
            },
        ];
    }
}
