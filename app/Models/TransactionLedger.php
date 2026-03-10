<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class TransactionLedger extends Model
{
    use Sortable;
    
    public $sortable = ['user_id','opening_balance', 'amount','fees', 'actual_amount', 'type', 'trans_id','payment_mode','closing_balance','created_at', 'updated_at'];
    
    
    
    protected $fillable = ['user_id','opening_balance', 'amount', 'fees', 'actual_amount', 'type', 'trans_id','payment_mode','closing_balance','created_at', 'updated_at','excelTransId'];
    
    public function User(){
        return $this->belongsTo('App\User', 'user_id');
    }
}
