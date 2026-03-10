<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class TransactionLimit extends Model
{
    use Sortable;

    protected $table = "transactions_limit";
    
    public $sortable = ['id', 'type', 'minDeposit', 'maxDeposit', 'minWithdraw', 'maxWithdraw','minSendMoney', 'maxSendMoney', 'gimacMin','gimacMax','moneyReceivingMin', 'moneyReceivingMax', 'unverifiedKycMin', 'unverifiedKycMax','bulkMin','bulkMax','onafriqa_min','onafriqa_max', 'created_at', 'updated_at'];
    
    protected $fillable = [
        'id', 'type', 'minDeposit', 'maxDeposit','minAirtel','maxAirtel', 'minWithdraw', 'maxWithdraw','minSendMoney', 'maxSendMoney', 'gimacMin','gimacMax','bdaMin','bdaMax','onafriqa_min','onafriqa_max','moneyReceivingMin', 'moneyReceivingMax', 'unverifiedKycMin', 'unverifiedKycMax','bulkMin','bulkMax', 'created_at', 'updated_at'
    ];
}
