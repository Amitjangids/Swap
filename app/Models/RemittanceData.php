<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class RemittanceData extends Model
{
    use Sortable;
    
    public $sortable = ['id','senderType','transactionId','paymentTransId', 'firstName', 'lastName', 'businessName', 'idType', 'idNumber','excel_id',
    'senderPhoneNumber', 'senderAddress', 'receiverType', 'receiverFirstName',
    'receiverLastName', 'deliveryMethod', 'bankName', 'bankCountryCode',
    'codeBank', 'codeAgence', 'numeroDeCompte', 'cleRib', 'transactionDescription',
    'transactionSourceAmount', 'sourceCurrency', 'transactionTargetAmount', 'targetCurrency','receiverBusinessName', 'walletSource', 'walletDestination', 'walletManager','type', 'created_at','updated_at'];

    protected $fillable = [
        'product','iban','excel_id','	titleAccount','amount','paymentTransId','partnerreference','reason','senderType','transactionId', 'firstName', 'lastName', 'businessName', 'idType', 'idNumber',
        'senderPhoneNumber', 'senderAddress', 'receiverType', 'receiverFirstName',
        'receiverLastName', 'deliveryMethod', 'bankName', 'bankCountryCode',
        'codeBank', 'codeAgence', 'numeroDeCompte', 'cleRib', 'transactionDescription',
        'transactionSourceAmount', 'sourceCurrency', 'transactionTargetAmount', 'targetCurrency','receiverBusinessName', 'walletSource', 'walletDestination', 'walletManager','type','trans_app_id'
    ];
}

