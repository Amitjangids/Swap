<?php
// app/Imports/ExcelImport.php
namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Validator;

class ExcelImport implements ToModel, WithStartRow
{
    protected $totalAmount = 0;
    protected $rowCount = 0;
    public $data = [];
    public $headerRow = null;
    protected $highestColumn = null;
    protected $errors = [];

    public function model(array $row)
    {
        if ($this->rowCount == 0) {
            $this->headerRow = $row;
            $this->rowCount++;
            return null;
        }

        $this->rowCount++;

        if (empty(array_filter($row))) {
            return null;
        }

        if (empty($row[0]) || empty($row[5]) || empty($row[6])) {
            $this->errors[] = [
                'row' => $this->rowCount,
                'errors' => ['records not transfer ']
            ];
            // return null;
        }

        $amount = $row[6] ?? 0;

        if (is_numeric($amount)) {
            $this->totalAmount += $amount;
        }

        $dataRow = [
            'first_name' => $row[0] ?? null,
            'name' => $row[1] ?? null,
            'comment' => $row[2] ?? null,
            'country' => $row[3] ?? null,
            'wallet_manager' => $row[4] ?? null,
            'tel_number' => $row[5] ?? null,
            'amount' => $amount,
        ];

        $rules = [
            'first_name' => 'required|string',
            'tel_number' => 'required',
            // 'tel_number' => 'required|min:9|max:9',
            'amount' => 'required|numeric|min:1|max:1000000',
        ];

 
        $validator = Validator::make($dataRow, $rules);
      

        if ($validator->fails()) {
            // print_r($validator->errors()->all()); die;
            $this->errors[] = [
                'row' => $this->rowCount,
                'errors' => $validator->errors()->all()

            ];
            // return null;
        }

        $this->data[] = $dataRow;

        if ($this->highestColumn === null) {
            $columnNames = array_filter(array_keys($row));
            $this->highestColumn = end($columnNames);
        }
    }

    public function startRow(): int
    {
        return 1;
    }

    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getCollectedData()
    {
        return $this->data;
    }

    public function onFirstRecord()
    {
        return array_filter($this->headerRow);
    }

    public function getHighestColumnName()
    {
        return count(array_filter($this->headerRow));
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getErrorCount()
    {
        return count($this->errors);
    }
}
