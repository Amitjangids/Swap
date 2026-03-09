<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class WithdrawRequest extends Model
{
    use Sortable;
    
    public $sortable = ['id', 'req_type', 'user_id', 'user_name', 'agent_id', 'amount', 'paypal_email', 'status', 'created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function User(){
        return $this->belongsTo('App\User', 'user_id');
    }
    protected $fillable = [
        'id', 'req_type', 'user_id', 'user_name', 'agent_id','remark', 'amount', 'paypal_email', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
      
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      
    ];
}
