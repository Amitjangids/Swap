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
    public $duplicateNumbers = [];
    protected $errors = [];

    protected $option;

    // Update the constructor to accept a parameter
    public function __construct($option)
    {
        $this->option = $option;
    }

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
        // App::setLocale(Session::get('locale', 'en'));

        if (!empty($this->option) && $this->option == 'swap_to_swap') {
            $amount = $row[2] ?? 0;

            if (is_numeric($amount)) {
                $this->totalAmount += $amount;
            }

            $dataRow = [
                'comment' => $row[0] ?? null,
                'tel_number' => $row[1] ?? null,
                'amount' => $amount,
            ];

            $rules = [
                'tel_number' => 'required',
                'amount' => 'required|numeric|min:1|max:1000000',
            ];

            $customMessages = [
                'tel_number.required' => __('message.Tel number field can\'t be left blank'),
                'amount.required' => __('message.Amount field can\'t be left blank'),
            ];

        } else if (!empty($this->option) && $this->option == 'swap_to_gimac') {
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
                'name' => 'required|string',
                'country' => 'required',
                'wallet_manager' => 'required',
                'tel_number' => 'required|digits:9',
                'amount' => 'required|numeric|min:1|max:1000000',
            ];

            $customMessages = [
                'first_name.required' => __('message.First name is required.'),
                'first_name.string' => __('message.First name must be a valid string.'),
                'name.required' => __('message.Name field can\'t be left blank'),
                'name.string' => __('message.Name must be a valid string.'),
                'country_id.required' => __('message.Country field can\'t be left blank'),
                'wallet_manager_id.required' => __('message.Wallet manager field can\'t be left blank'),
                'tel_number.required' => __('message.Tel number field can\'t be left blank'),
                'amount.required' => __('message.Amount field can\'t be left blank'),
            ];

        } else if (!empty($this->option) && $this->option == 'swap_to_bda') {
            $amount = $row[3] ?? 0;

            if (is_numeric($amount)) {
                $this->totalAmount += $amount;
            }

            $dataRow = [
                'beneficiary' => $row[0] ?? null,
                'iban' => $row[1] ?? null,
                'reason' => $row[2] ?? null,
                'amount' => $amount,
            ];

            $rules = [
                'beneficiary' => 'required',
                'iban' => 'required|min:24|max:30',
                'reason' => 'required',
                'amount' => 'required|numeric|min:1|max:99999999',
            ];

            $customMessages = [
                'newBeneficiary.required' => __('message.newBeneficiary can\'t be left blank'),
                'iban.required' => __('message.Iban field can\'t be left blank'),
                'iban.min' => __('message.Iban min length is 24'),
                'iban.max' => __('message.Iban max length is 30'),
                'reason.required' => __('message.Reason field can\'t be left blank'),
                'amount.required' => __('message.Amount field can\'t be left blank'),
                'amount.min' => __('message.Amount must be at least 1'),
                'amount.max' => __('message.Amount maximum 99999999'),
            ];

        } else if (!empty($this->option) && $this->option == 'swap_to_onafriq') {
            $amount = $row[5] ?? 0;

            if (is_numeric($amount)) {
                $this->totalAmount += $amount;
            }

            $dataRow = [
                'recipientCountry' => $row[0] ?? null,
                'walletManager' => $row[1] ?? null,
                'recipientMsisdn' => $row[2] ?? null,
                'recipientName' => $row[3] ?? null, 
                'recipientSurname' => $row[4] ?? null,
                'amount' => $amount,
                'senderCountry' => $row[6] ?? null,
                'senderMsisdn' => $row[7] ?? null,
                'senderName' => $row[8] ?? null,
                'senderSurname' => $row[9] ?? null,
                'senderAddress' => $row[10] ?? null,
                'senderDob' => $row[11] ?? null,
                'senderIdType' => $row[12] ?? null,
                'senderIdNumber' => $row[13] ?? null,
            ];
            
            $rules = [
                'recipientCountry' => 'required',
                'walletManager' => 'required',
                'recipientMsisdn' => 'required',
                'recipientName' => 'required',
                'recipientSurname' => 'required',
                'amount' => 'required|numeric|min:500|max:1500000',
                'senderCountry' => 'required',
                'senderMsisdn' => 'required',
                'senderName' => 'required',
                'senderSurname' => 'required',
                'senderAddress' => 'required_if:senderCountry,Senegal,Mali',
                'senderIdType' => 'required_if:senderCountry,Senegal,Mali',
                'senderIdNumber' => 'required_if:senderCountry,Senegal,Mali',
                'senderDob' => 'required_if:senderCountry,Senegal,Mali,Burkina Faso',
            ];

            $customMessages = [
                    'recipientCountry.required' => __('message.Recipient Country field can\'t be left blank'),
                    'recipientSurname.required' => __('message.Recipient Last Name field can\'t be left blank'),
                    'recipientName.required' => __('message.Recipient First Name field can\'t be left blank'),
                    'africamount.required' => __('message.Amount field can\'t be left blank'),
                    'africamount.min' => __('message.The amount must be at least 500'),
                    'africamount.max' => __('message.The amount maximum 1500000'),
                    'recipientMsisdn.required' => __('message.Recipient Mobile Number field can\'t be left blank'),
                    'onafriqCountryCode.required' => __('message.Country code field can\'t be left blank'),
                    'walletManager.required' => __('message.Wallet Manager field can\'t be left blank'),
                    'senderCountry.required' => __('message.Sender Country field can\'t be left blank'),
                    'senderMsisdn.required' => __('message.Sender Phone Number field can\'t be left blank'),
                    'senderName.required' => __('message.Sender Name field can\'t be left blank'),
                    'senderSurname.required' => __('message.Sender Surname field can\'t be left blank'),
                    'senderAddress.required' => __('message.Sender Address field can\'t be left blank'),
                    'senderIdType.required' => __('message.Sender Id Type field can\'t be left blank'),
                    'senderIdNumber.required' => __('message.Sender Id Number field can\'t be left blank'),
                    'senderDob.required' => __('message.Sender Dob field can\'t be left blank')
                ];
        }

        $validator = Validator::make($dataRow, $rules,$customMessages);


        if ($validator->fails()) {
            $this->errors[] = [
                'row' => $this->rowCount,
                'errors' => $validator->errors()->all()

            ];
            return null;
        }

        if (!empty($this->option) && $this->option == 'swap_to_swap') {
            $telNumber = $row[1] ?? null;
            if ($telNumber && in_array($telNumber, array_column($this->data, 'tel_number'))) {
                $this->duplicateNumbers[] = $telNumber;
            }
        } else if (!empty($this->option) && $this->option == 'swap_to_gimac') {
            $telNumber = $row[5] ?? null;
            if ($telNumber && in_array($telNumber, array_column($this->data, 'tel_number'))) {
                $this->duplicateNumbers[] = $telNumber;
            }
        } else if (!empty($this->option) && $this->option == 'swap_to_bda') {
            $iban = $row[1] ?? null;
            if ($iban && in_array($iban, array_column($this->data, 'iban'))) {
                $this->duplicateNumbers[] = $iban;
            }
        } else if (!empty($this->option) && $this->option == 'swap_to_onafriq') {
            $recipientMsisdn = $row[2] ?? null;
            if ($recipientMsisdn && in_array($recipientMsisdn, array_column($this->data, 'recipientMsisdn'))) {
                $this->duplicateNumbers[] = $recipientMsisdn;
            }
        }

        $this->data[] = $dataRow;
        if ($this->highestColumn === null) {
            $columnNames = array_filter(array_keys($row));
            $this->highestColumn = end($columnNames);
        }
    }

    public function getDuplicateNumbers()
    {
        return $this->duplicateNumbers;
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
