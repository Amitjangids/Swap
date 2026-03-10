<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class ReferralSetting extends Model
{
    use Sortable;
    protected $table = "referral_settings";
    public $sortable = ['id', 'type', 'fee_type','fee_value','is_active', 'created_at', 'updated_at'];
    
        protected $fillable = [
        'type', 'fee_type','fee_value','is_active','last_updated_by','created_at', 'updated_at'
    ];

    public function User(){  
        return $this->belongsTo('App\User', 'user_id');
    }
    
    public function Receiver(){
        return $this->belongsTo('App\User', 'receiver_id');
    }
    
    public function Admin(){
        return $this->belongsTo('App\Models\Admin', 'last_updated_by');
    }
    

}
