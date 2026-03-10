<?php

namespace App\Exports;


use Illuminate\Support\Facades\Session;
use App\Models\UploadedExcel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Auth;
use Carbon\Carbon;



class TransactionHistoryExport implements FromCollection, WithHeadings, WithEvents
{

    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $i = 1;
        return $this->records->map(function ($record) use (&$i) {
            $status = $record->status == 5 ? "approved by merchant" : ($record->status == 6 ? "rejected by merchant" : '');

            return  [
                'id' => $i++,
                'Txn. Reference number' => $record->reference_id,
                'Purpose of payment' => !empty($record->remarks) ? ucfirst($record->remarks) : 'Salary',
                'No. of transactions' => $record->no_of_records,
                'Initiation date' => $record->created_at->format('M d, Y h:i:s A'),
                'Amount' => CURR . ' ' . $record->totat_amount,
                'Fees' => CURR . ' ' . $record->total_fees,
                'Status' => $status,
            ];
           
        
    });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Reference ID',
            'Purpose of Payment',
            'No. of Transactions',
            'Initiation Date',
            'Amount',
            'Fees',
            'Status',
        ];
    }


    // public function headings(): array
    // {
    //     return [
    //         '#',
    //         'Txn. Reference number',
    //         'Purpose of payment',
    //         'No. of transactions',
    //         'Initiation date',
    //         'Amount',
    //         'Fees',
    //         'Status',
    //     ];
    // }

    /**
     * Register events to format the Excel sheet.
     * 
     * @return array
     */
    public function registerEvents(): array
    {
        $styleArray = [
            'font' => [
                'name' => 'Calibri',
                'size' => 13,
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'dff0d8'],
            ],
        ];

        $styleArray1 = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        return [
            AfterSheet::class => function (AfterSheet $event) use ($styleArray, $styleArray1) {
                $cellRange = 'A1:H1';
                $event->sheet->getDelegate()->setAutoFilter('A1:H1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(13);
                $event->sheet->getStyle($cellRange)->applyFromArray($styleArray);

                $lastRow = $event->sheet->getHighestRow();
                $totalRow = $lastRow + 1;

                // Sum totals for Amount and Fees columns
                $event->sheet->setCellValue('F' . $totalRow, '=SUM(F2:F' . $lastRow . ')');
                $event->sheet->setCellValue('G' . $totalRow, '=SUM(G2:G' . $lastRow . ')');
                $event->sheet->setCellValue('H' . $totalRow, 'Total');
                
                // Applying border styles
                $event->sheet->getStyle('A' . $totalRow . ':H' . $totalRow)->applyFromArray($styleArray1);
                $event->sheet->getStyle('A1:H' . $totalRow)->applyFromArray($styleArray1);

                // Auto-resize columns
                foreach (range('A', $event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
