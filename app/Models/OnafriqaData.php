<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class OnafriqaData extends Model
{
    use Sortable;
    
    public $sortable = [];
    protected $table = "onafriqa_data";

    protected $fillable = ['userId','senderName', 'senderIdNumber', 'senderCountry', 'senderIdExpiry', 'amount', 'recipientMsisdn','recipientCountry','walletManager', 'recipientSurname', 'recipientName', 'transactionId','paymentStatus','trans_app_id','partnerCode','fromMSISDN'];
}

