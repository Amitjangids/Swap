<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class CompanyTransaction extends Model
{
    use Sortable;
    
    public $sortable = ['user_id', 'receiver_id','amount','billing_description','status','refrence_id','trans_type','payment_mode','created_at', 'updated_at'];
    
    public function User(){
        return $this->belongsTo('App\Models\Admin', 'user_id');
    }
    
    public function Receiver(){
        return $this->belongsTo('App\Models\Admin', 'receiver_id');
    }

    public function ExcelTransaction(){
        return $this->belongsTo(ExcelTransaction::Class, 'excel_trans_id');
    }
    
    protected $fillable = [
        'user_id', 'receiver_id','amount','billing_description','status','refrence_id','trans_type','payment_mode','trans_id','created_at', 'updated_at'
    ];
}
