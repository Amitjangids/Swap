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


class ReportExport implements FromCollection, WithHeadings, WithEvents
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

        $query = new Transaction();

        if (!empty($daterange)) {
            $dt = explode(' - ', $daterange);
            $start_date = Carbon::createFromFormat('d/m/Y', $dt[0])->format('Y-m-d');
            $end_date = Carbon::createFromFormat('d/m/Y', $dt[1])->format('Y-m-d');
            $query = $query->whereDate('transactions.created_at', '>=', $start_date)->whereDate('transactions.created_at', '<=', $end_date);
        }

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
        $total_record = $query->orderBy($columnName, $columnSortOrder)->get();

        $result = array();
        $i = 1;
        $admin = DB::table('admins')->where('id', 1)->first();
        foreach ($this->records as $record) {

            if ($record->user_id == 1 && $record->trans_for == 'Admin') {
                if ($record->trans_to != "") {
                    $admin = DB::table('admins')->where('id', $record->trans_to)->first();
                } else {
                    $admin = DB::table('admins')->where('id', 1)->first();
                }

                $name = isset($admin->username) ? ucfirst($admin->username) : 'N/A';
                $phone = 'NA';
            } else {
                if (isset($record->senderName) && !empty($record->senderName)) {
                    $name = ucfirst($record->senderName . ' ' . $record->senderSurname);
                } else {
                    $name = $record->User->name ?? 'N/A';
                }

                if (isset($record->senderMsisdn) && !empty($record->senderMsisdn)) {
                    $phone = $record->senderMsisdn;
                } else {
                    $phone = $record->User->phone ?? 'N/A';
                }
            }


            if ($record->receiver_id == 0 && $record->receiver_mobile != '') {
                if (isset($record->recipientName) && !empty($record->recipientName)) {
                    $receiverName = ucfirst($record->recipientName . ' ' . $record->recipientSurname);
                    $receiverPhone = $record->recipientMsisdn;
                } else {
                    if (isset($record->ExcelTransaction->first_name)) {
                        $receiverName = ucfirst($record->ExcelTransaction->first_name) ?? 'N/A';
                    }
                    $receiverPhone = $record->receiver_mobile;
                }
            } elseif ($record->receiver_id == 0 && $record->receiver_mobile == '') {
                if (isset($record->recipientName) && !empty($record->recipientName)) {
                    $receiverName = ucfirst($record->recipientName . ' ' . $record->recipientSurname);
                    $receiverPhone = $record->recipientMsisdn;
                } else {
                    $receiverName = ucfirst($admin->username) ?? 'N/AQ';
                    $receiverPhone = 'N/A';
                }
            } elseif ($record->receiver_id == 1 && $record->tomember == 'Admin') {
                if ($record->trans_to != "") {
                    $admin = DB::table('admins')->where('id', $record->trans_to)->first();
                } else {
                    $admin = DB::table('admins')->where('id', 1)->first();
                }

                if (isset($record->recipientName) && !empty($record->recipientName)) {
                    $receiverName = ucfirst($record->recipientName . ' ' . $record->recipientSurname);
                    $receiverPhone = 'N/A';
                } else {
                    $receiverName = ucfirst($admin->username) ?? 'N/AW';
                    $receiverPhone = 'N/A';
                }
            } else {
                if (isset($record->recipientName) && !empty($record->recipientName)) {
                    $receiverName = ucfirst($record->recipientName . ' ' . $record->recipientSurname);
                    $receiverPhone = $record->recipientMsisdn;
                } else {
                    if (isset($record->Receiver->name)) {
                        $receiverName = ucfirst($record->Receiver->name);
                        $receiverPhone = ucfirst($record->Receiver->phone);
                    } elseif (isset($record->User->name)) {
                        $receiverName = ucfirst($record->User->name);
                        $receiverPhone = ucfirst($record->User->phone);
                    } else {
                        $receiverName = 'N/A';
                        $receiverPhone = ucfirst($record->User->phone);
                    }
                }
            }



            /* if ($record->receiver_id == 1 && $record->trans_for == 'Admin') {
                if ($record->trans_to != "") {
                    $admin = DB::table('admins')->where('id', $record->trans_to)->first();
                } else {
                    $admin = DB::table('admins')->where('id', 1)->first();
                }
                $receiverName = isset($admin->username) ? ucfirst($admin->username) : 'N/A';
                $receiverPhone = 'N/A';
            } else {
                $receiverName = $receiverPhone = 'N/A';
                if (isset($record->recipientMsisdn) && !empty($record->recipientMsisdn) && !empty($record->recipientName)) {
                    $receiverPhone = $record->recipientMsisdn;
                    $receiverName = ucfirst($record->recipientName . ' ' . $record->recipientSurname);
                } elseif (isset($record->Receiver)) {
                    $receiverName = isset($record->Receiver->name) ? $record->Receiver->name : 'N/A';
                    $receiverPhone = isset($record->Receiver->phone) ? $record->Receiver->phone : 'N/A';
                } elseif (isset($record->User)) {
                    $receiverName = isset($record->User->name) ? $record->User->name : 'N/A';
                    $receiverPhone = isset($record->User->phone) ? $record->User->phone : 'N/A';
                }
            } */




            if ($record->payment_mode == 'Withdraw' && $record->trans_for == 'Admin' && $record->trans_type == 2) {
                $transactionFor = 'Withdraw';
            } elseif ($record->payment_mode == 'Withdraw') {
                $transactionFor = 'Buy Balance';
            } elseif ($record->payment_mode == 'Agent Deposit') {
                $transactionFor = 'Sell Balance';
            } elseif (isset($record->receiver_mobile)) {

                if ($record->transactionType == 'SWAPTOGIMAC') {
                    $transactionFor = 'GIMAC Transfer';
                } elseif ($record->transactionType == 'SWAPTOONAFRIQ') {
                    $transactionFor = 'ONAFRIQ Transfer';
                } elseif ($record->transactionType == 'SWAPTOBDA') {
                    $transactionFor = 'BDA Transfer';
                } else {
                    $transactionFor = 'wallet2wallet';
                }
            } else {
                $transactionFor = $record->payment_mode;
            }



            if ($record->status == '1') {
                $status = "Completed";
            } elseif ($record->status == '2') {
                $status = "Pending";
            } elseif ($record->status == '3') {
                $status = "Failed";
            } elseif ($record->status == '4') {
                $status = "Cancelled";
            }


            if (isset($record->transactionId) && !empty($record->transactionId)) {
                $trasactionId = $record->transactionId ?? "";
            }else if (isset($record->remitanceTransactionId) && !empty($record->remitanceTransactionId) && $record->transactionType=='SWAPTOBDA') {
                $trasactionId = $record->remitanceTransactionId ? $record->remitanceTransactionId: "N/A";
            } else {
                $trasactionId = $record->refrence_id;
            }



            $result[] = array(
                'id' => $i,
                'Sender Name' => $name,
                'Sender Phone' => $phone,
                "Receiver Name" => $receiverName ?? 'N/A',
                "Receiver Phone" => $receiverPhone ?? 'N/A',
                "Transaction For" => $transactionFor,
                "Amount" => $record->amount ? $record->amount : '0',
                "Transaction Fee" => $record->transaction_amount ? $record->transaction_amount : '0',
                "Total Amount" => $record->total_amount,
                "Transaction ID" => $trasactionId,
                "Status" => $status,
                "Transaction Request Date" => $record->created_at->format('M d, Y h:i:s A'),
                "Transaction Process Date" => $record->updated_at->format('M d, Y h:i:s A'),
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
            'Transaction For',
            'Amount',
            'Transaction Fee',
            'Total Amount',
            'Transaction ID',
            'Status',
            'Transaction Request Date',
            'Transaction Process Date'

        ];
    }

    public function registerEvents(): array
    {

        $styleArray = [
            'font' => [
                'name' => 'Calibri',
                'size' => 13,
                'bold' => true,
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
            AfterSheet::class => function (AfterSheet $event) use ($styleArray, $styleArray1) {
                $cellRange = 'A1:M1';
                $event->sheet->getDelegate()->setAutoFilter('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);
                $last_row = $event->sheet->getHighestRow();
                $heighest_row = $event->sheet->getHighestRow() + 1;

                $cellRange = 'A' . $heighest_row . ':M' . $heighest_row;
                $event->sheet->setCellValue('G' . ($heighest_row), '=SUM(G2:G' . $last_row . ')');
                $event->sheet->setCellValue('H' . ($heighest_row), '=SUM(H2:H' . $last_row . ')');
                $event->sheet->setCellValue('I' . ($heighest_row), '=SUM(I2:I' . $last_row . ')');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);

                $event->sheet->setCellValue('F' . ($heighest_row), 'Total');

                $event->sheet->getStyle('A' . $heighest_row . ':M' . $heighest_row)->ApplyFromArray($styleArray1);
                $event->sheet->getStyle('A1:M' . $heighest_row)->ApplyFromArray($styleArray1);

                foreach (range('A', $event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->
                        getColumnDimension($col)
                        ->setAutoSize(true);
                }
            },
        ];
    }
}
