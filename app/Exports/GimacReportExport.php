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


class GimacReportExport implements FromCollection, WithHeadings, WithEvents
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
        $i = 1;
        foreach ($this->records as $record) {

            if ($record->is_verified_by_gimac == '0')
                $status = 'Not Verified';
            if ($record->is_verified_by_gimac == '1')
                $status = 'Verified';
            
            $result[] = array(
                'id' => $i,
                'First Name' => $record->ExcelTransaction->first_name ?? 0,
                'Name' => $record->ExcelTransaction->name ?? 0,
                'Country' => $record->ExcelTransaction->country->name ?? 0,
                "Wallet Manager" => $record->ExcelTransaction->walletManager->name ?? 0,
                "Tel Number" => $record->ExcelTransaction->tel_number ?? 0,
                "Amount" => $record->amount_value,
                "Fee" => $record->transaction_amount,
                "Issuertrxref No" => $record->issuertrxref,
                "Status" => $status,
                "Transaction Date" => $record->created_at->format('M d, Y h:i:s A'),
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
            'First Name',
            'Name',
            'Country',
            'Wallet Manager',
            'Tel Number',
            'Amount',
            'Transaction Fee',
            'Issuertrxref No',
            'Status',
            'Transaction Date',
        ];
    }

    public function registerEvents(): array
    {

        $styleArray = [
            'font' => [
                'name' => 'Calibri',
                'size' => 11,
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
                $cellRange = 'A1:K1';
                $event->sheet->getDelegate()->setAutoFilter('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(11);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);
                $last_row = $event->sheet->getHighestRow();
                $heighest_row = $event->sheet->getHighestRow() + 1;

                $cellRange = 'A' . $heighest_row . ':K' . $heighest_row;
                $event->sheet->setCellValue('G' . ($heighest_row), '=SUM(G2:G' . $last_row . ')');
                $event->sheet->setCellValue('H' . ($heighest_row), '=SUM(H2:H' . $last_row . ')');
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(11);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);

                $event->sheet->setCellValue('F' . ($heighest_row), 'Total');

                $event->sheet->getStyle('A' . $heighest_row . ':K' . $heighest_row)->ApplyFromArray($styleArray1);
                $event->sheet->getStyle('A1:K' . $heighest_row)->ApplyFromArray($styleArray1);

                foreach (range('A', $event->sheet->getHighestDataColumn()) as $col) {
                    $event->sheet->
                        getColumnDimension($col)
                        ->setAutoSize(true);
                }
            },
        ];
    }
}
