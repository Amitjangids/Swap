<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public $sortable = ['id', 'username', 'email','status','created_at','company_name','phone','company_code','parent_id','edited_by'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'password','status', 'role_id','wallet_balance','activation_status','slug','created_at','updated_at','company_name','phone','company_address','website','profile','parent_id','company_code','referralBonusSender','referralBonusReceiver','edited_by'
    ];

    public function createdBy(){
        return $this->belongsTo(Admin::Class, 'parent_id');
    }

    public function editedBy(){
        return $this->belongsTo(Admin::Class, 'edited_by');
    }
  
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];
}
