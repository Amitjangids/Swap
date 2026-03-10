<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class UploadedExcel extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public $sortable = ['id', 'order_id', 'status','created_at'];
    
    public function User(){
        return $this->belongsTo('App\User', 'user_id');
    }
  
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','parent_id', 'excel','reference_id','no_of_records','total_fees','totat_amount','remarks','status','approved_by','type','created_at','updated_at'
    ];

}
