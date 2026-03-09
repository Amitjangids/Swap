<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class ExcelTransaction extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public $sortable = ['first_name', 'name','comment', 'country_id','wallet_manager_id', 'tel_number', 'amount', 'created_at'];

    public function country(){
        return $this->belongsTo(Country::Class, 'country_id');
    }

    public function walletManager(){
        return $this->belongsTo(WalletManager::Class, 'wallet_manager_id');
    }

    protected $fillable = [
        'excel_id','submitted_by','approved_by', 'first_name', 'name','comment', 'country_id','wallet_manager_id', 'tel_number', 'amount','fees','remarks','bdastatus','parent_id'
    ];

}
