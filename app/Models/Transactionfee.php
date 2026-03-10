<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Transactionfee extends Model
{
    use Sortable;
    protected $table = "transactionfees";
    public $sortable = ['id', 'name', 'transaction_type','user_charge','agent_charge','merchant_charge', 'payment_mode', 'refrence_id', 'created_at','user_id', 'receiver_id', 'amount', 'trans_type', 'payment_mode', 'refrence_id', 'status', 'created_at', 'updated_at','slabId','min_amount','max_amount','fee_amount','fee_type','transaction_type','slug'];
    
    public function User(){  
        return $this->belongsTo('App\User', 'user_id');
    }
    
    public function Receiver(){
        return $this->belongsTo('App\User', 'receiver_id');
    }
    
    public function Admin(){
        return $this->belongsTo('App\Models\Admin', 'last_updated_by');
    }
    
    protected $fillable = [
        'user_id', 'receiver_id', 'amount', 'trans_type', 'payment_mode', 'refrence_id', 'status', 'created_at', 'updated_at','slabId','min_amount','max_amount','fee_amount','fee_type','transaction_type','slug'
    ];
}
