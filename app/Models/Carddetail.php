<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class Carddetail extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public $sortable = ['id', 'serial_number', 'agent_card_value', 'status', 'used_status', 'pin_number','real_value','card_value','used_date','created_at'];

    public function Card(){
        return $this->belongsTo('App\Models\Card', 'card_id');
    }    
    
    public function Admin(){
        return $this->belongsTo('App\Models\Admin', 'last_updated_by');
    }
    
    public function User(){
        return $this->belongsTo('App\User', 'used_by');
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'serial_number', 'agent_card_value', 'status', 'used_status', 'pin_number','real_value','card_value','used_date','created_at'
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
        'email_verified_at' => 'datetime',
    ];
}
