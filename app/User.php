<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;
use DB;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public $sortable = ['id', 'name','lastName','device_type','login_time', 'business_name', 'email','phone','is_verify','user_type','wallet_balance','is_kyc_done','created_at'];

    public function City(){
        return $this->belongsTo('App\Models\City', 'city');
    }
    
    public function Area(){
        return $this->belongsTo('App\Models\Area', 'area');
    }

    public function Company(){
        return $this->belongsTo('App\Models\Admin', 'company_code','company_code');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','lastName', 'phone', 'email','user_type', 'country', 'state', 'city','address1','address2','postCode', 'addr_line1','business_name', 'addr_line2', 'zip','otp_verify','is_verify','kyc_status', 'password','slug','verify_code','national_identity_type','national_identity_number','dob','id_expiry_date','identity_back_image','identity_front_image','device_token','device_type','device_id','is_account_deleted','company_code', 'is_email_verified', 'referralCode', 'referralBy','ibanNumber','alreadyReplace','requestReplaceCard','gabonStampImg','gabonStampStatus'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'profile_image','password', 'remember_token', 'is_kyc_done', 'is_verify',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
 
    public static function getCityName($id){
       return DB::table('cities')->where('id', $id)->select('name_en','name_ar')->first();
    }
    
    public static function getAreaName($id){
       return DB::table('areas')
  ->where('id', '=', $id)
  ->select('name')->first();
    }
}
