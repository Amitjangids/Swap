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


class ReferralReportExport implements FromCollection,WithHeadings,WithEvents
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
            //  'id'=>$i,
             'Sender Name' =>$record->sender_name,
             'Sender Phone' => $record->sender_phone,
             "Receiver Name" => $record->receiver_name, 
             "Receiver Phone" => $record->receiver_phone, 
             "Transaction For"=>$record->payment_mode,
             "Amount"=>$record->amount,
             "Status" =>$status,
             "Transaction Date" =>$record->created_at,
          );
          $i++;
        } 
        // $result
        return collect($result);
      
    }

    public function headings(): array
    {
       return [
         'Referrer Name',
         'Referrer Phone Number',
         'Referred Name',
         'Referred Phone Number',
         'Transaction For',
         'Amount',
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
                $cellRange = 'A1:H1'; 
                $event->sheet->getDelegate()->setAutoFilter('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(10);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);
                $last_row=$event->sheet->getHighestRow();
                $heighest_row=$event->sheet->getHighestRow()+1;

                $cellRange = 'A'.$heighest_row.':H'.$heighest_row; 
                $event->sheet->setCellValue('F'. ($heighest_row), '=SUM(F2:F'.$last_row.')');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(10);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);

                $event->sheet->setCellValue('E'. ($heighest_row), 'Total');

                $event->sheet->getStyle('A'.$heighest_row.':H'.$heighest_row)->ApplyFromArray($styleArray1);
                $event->sheet->getStyle('A1:H'.$heighest_row)->ApplyFromArray($styleArray1);

                foreach (range('A',$event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->
                            getColumnDimension($col)
                            ->setAutoSize(true);
                } 
            },
        ];
    }
}
