<?php

namespace App\Exports;
use Illuminate\Support\Facades;
use Session;
use App\Models\BarUser;
use App\Models\BarOwner;
use App\Models\User;
use App\Models\Transaction;
use App\Models\UploadedExcel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Sheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;
use Auth;


class ReportExport1 implements FromCollection,WithHeadings,WithEvents
{

    public function __construct() 
    {
        Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
            $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
        });
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
      //   $searchValue=Session::get('keyword');
      //   $status=Session::get('status');
      //   $daterange=Session::get('daterange');
      $columnName = Session::get('columnName');
      $columnSortOrder = Session::get('columnSortOrder', 'desc');
  
      // Validate that $columnName is not empty before using it in orderBy
      if (empty($columnName)) {
          // Set a default column name or handle the error as needed
          // For example, you could set it to a default column like 'id'
          $columnName = 'id';
      }
      $user_id = Auth::user()->id;
 
        $query=new UploadedExcel();

      //   if(!empty($daterange))
      //   {   
      //    $dt=explode(' - ',$daterange);  
      //    $start_date= Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
      //    $end_date= Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
      //    $query=$query->whereDate('transactions.created_at','>=',$start_date)->whereDate('transactions.created_at','<=',$end_date); 
      //   }

      //   if(isset($status))
      //   {
      //   $query=$query->where('status',$status);   
      //   }
        
      //   $query=$query->Join('barusers','barusers.id','=','orders.userId');
      //   $query=$query->Join('barowners','barowners.id','=','orders.barId');
      //   $query=$query->where(function ($q) use($searchValue) {
      //      $q->orWhere('orderId', 'like', '%' . $searchValue . '%');
      //      $q->orWhere('barowners.fullName', 'like', '%' . $searchValue . '%');
      //      $q->orWhere('barusers.fullName', 'like', '%' . $searchValue . '%');
      //      });
        //  ->select('*','orders.id','orders.createdAt','orders.barId','orders.userId')
        $total_record = $query->where('parent_id',$user_id)->orderBy($columnName, $columnSortOrder)->get();
     
       $result = array();
       $i=1;
    //    $admin= DB::table('admins')->where('id',1)->first();
       foreach($total_record as $record){

        // echo"<pre>";print_r( $record);
 
    //           if ($record->user_id == 1 && $record->trans_for == 'Admin') {
    //             $name = isset($admin->username) ? ucfirst($admin->username) : 'N/A';
    //             $phone = 'NA';
    //         } else {
    //             $name = isset($record->User->name) ? ucfirst($record->User->name) : 'N/A';
    //             $phone = isset($record->User->phone) ? ucfirst($record->User->phone) : 'N/A';
    //         }
      
                  
              


    //         if ($record->receiver_id == 1 && $record->trans_for == 'Admin') {
    //           $receiverName = isset($admin->username) ? ucfirst($admin->username) : 'N/A';
    //           $receiverPhone = 'N/A';
    //       } else {
    //           $receiverName = $receiverPhone = 'N/A';
    //           if (isset($record->Receiver)) {
    //               $receiverName = isset($record->Receiver->name) ? $record->Receiver->name : 'N/A';
    //               $receiverPhone = isset($record->Receiver->phone) ? $record->Receiver->phone : 'N/A';
    //           } elseif (isset($record->User)) {
    //               $receiverName = isset($record->User->name) ? $record->User->name : 'N/A';
    //               $receiverPhone = isset($record->User->phone) ? $record->User->phone : 'N/A';
    //           }
    //       }
          
           
            

    //       if ($record->payment_mode == 'Withdraw' && $record->trans_for == 'Admin' && $record->trans_type == 2) {
    //         $transactionFor = 'Withdraw';
    //       } elseif ($record->payment_mode == 'Withdraw') {
    //           $transactionFor = 'Buy Balance';
    //       } elseif ($record->payment_mode == 'Agent Deposit') {
    //           $transactionFor = 'Sell Balance';
    //       } elseif (isset($record->receiver_mobile)) {
    //           $transactionFor = 'GIMAC Transfer';
    //       } else {
    //           $transactionFor = $record->payment_mode;
    //       }


         


    // $data_arr = array();
    // foreach ($records as $record) {
    //     $action = '
    //     <a href="'.$record->id.'" data-bs-toggle="modal" data-bs-target="#transList" class=""><i class="fa fa-eye" aria-hidden="true" title="View"></i></a>' .
    //     ($record->type == 0 ? '<a href="'.PUBLIC_PATH.'/assets/front/excel/'.$record->excel.'" class="" download="'.$record->excel.'"><i class="fa fa-download" aria-hidden="true" title="Download Excel"></i></a>' : '');
    //     $data_arr[] = array(
    //         "reference_id" =>$record->reference_id,
    //         "remarks" => $record->remarks!="" ?  ucfirst($record->remarks) : 'Salary',
    //         "excel"=>$record->excel,
    //         "no_of_records" => $record->no_of_records,
    //         "updated_at" => date('d M,y',strtotime($record->created_at)),
    //         "totat_amount" => CURR.' '.$record->totat_amount,
    //         "total_fees" => CURR.' '.$record->total_fees,
    //         "status" =>$this->getStatus($record->status),
    //         "action" =>$action,
      
    $status = ""; // Initialize $status variable with a default value

    if ($record->status == 5) {
        $status = "approved by merchant";
    } elseif ($record->status == 6) {
        $status = "rejected by merchant";
    }
    
    $result[] = array(
        'id' => $i,
        'Txn. Reference number' => $record->reference_id,
        'Purpose of payment' => $record->remarks != "" ? ucfirst($record->remarks) : 'Salary',
        "No. of transactions" => $record->no_of_records,
        "Initiation date" => $record->created_at->format('M d, Y h:i:s A'),
        "Amount" => CURR . ' ' . $record->totat_amount,
        "Fees" => CURR . ' ' . $record->total_fees,
        "Status" => $status,
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
         'Txn. Reference number',
         'Purpose of payment',
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
                $cellRange = 'A1:L1'; 
                $event->sheet->getDelegate()->setAutoFilter('A1:'.$event->sheet->getDelegate()->getHighestColumn().'1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);
                $last_row=$event->sheet->getHighestRow();
                $heighest_row=$event->sheet->getHighestRow()+1;

                $cellRange = 'A'.$heighest_row.':L'.$heighest_row; 
                $event->sheet->setCellValue('J'. ($heighest_row), '=SUM(J2:J'.$last_row.')');
                $event->sheet->setCellValue('K'. ($heighest_row), '=SUM(K2:K'.$last_row.')');
                $event->sheet->setCellValue('L'. ($heighest_row), '=SUM(L2:L'.$last_row.')');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);

                $event->sheet->setCellValue('I'. ($heighest_row), 'Total');

                $event->sheet->getStyle('A'.$heighest_row.':L'.$heighest_row)->ApplyFromArray($styleArray1);
                $event->sheet->getStyle('A1:L'.$heighest_row)->ApplyFromArray($styleArray1);

                foreach (range('A',$event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->
                            getColumnDimension($col)
                            ->setAutoSize(true);
                } 
            },
        ];
    }
}