<?php

namespace App\Exports;


use Illuminate\Support\Facades\Session;
use App\Models\UploadedExcel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Auth;
use Carbon\Carbon;
use App\Models\Transaction;
use DB;



class OnafriqTransaction implements FromCollection,WithHeadings, WithMapping
{

    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $today = Carbon::today()->toDateString();
        // Fetch transaction data from your database
        return Transaction::join('onafriqa_data as o', 'o.id', '=', 'transactions.onafriq_bda_ids')
            ->select(
                DB::raw("DATE_FORMAT(transactions.created_at, '%Y-%m-%d %H:%i:%s') as created_at"),
                'transactions.id',
                'o.transactionId',
                'o.thirdPartyTransactionId',
                DB::raw("'Transfer' AS TransactionType"),
                'o.status',
                'o.fromMSISDN',
                'o.recipientMsisdn',
                'o.recipientCurrency',
                'o.amount',
                DB::raw("'0' AS Fee_Amount"),
                DB::raw("'' AS Balance_before"),
                DB::raw("'' AS Balance_after"),
                DB::raw("'' AS Related_Transaction_ID"),
                DB::raw("'' AS Wallet_Identifier"),
                'o.partnerCode'
            )
            ->whereDate('transactions.created_at', $today)
            ->where('transactions.transactionType', 'SWAPTOONAFRIQ')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Datestamp',
            'Transaction ID',
            'Onafriq Transaction ID',
            'Third_PartyID',
            'Transaction_Type',
            'Transaction_Status',
            'From_MSISDN',
            'To_MSISDN',
            'Currency',
            'Transaction_Amount',
            'Fee_Amount',
            'Balance_before',
            'Balance_after',
            'Related_Transaction_ID',
            'Wallet_Identifier',
            'Partner_name',
        ];
    }

    public function map($transaction): array
    {
        return [
            Carbon::parse($transaction->created_at)->format('Y-m-d H:i'), // Format the date
            $transaction->id,
            $transaction->transactionId,
            $transaction->thirdPartyTransactionId,
            'Transfer', // Static value for Transaction Type
            ucfirst($transaction->status),
            $transaction->fromMSISDN,
            $transaction->recipientMsisdn,
            $transaction->recipientCurrency,
            $transaction->amount,
            '0', // Static fee value
            '', // Blank values
            '',
            '',
            '',
            $transaction->partnerCode,
        ];
    }

}
