<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class Scratchcard extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public $sortable = ['id', 'card_number', 'real_value','purchase_by_id', 'card_value','expiry_date','status','used_status','created_at', 'updated_at'];

    
    public function User(){
        return $this->belongsTo('App\User', 'used_by_id');
    }
    
    public function Admin(){
        return $this->belongsTo('App\Models\Admin', 'last_updated_by');
    }
    
    public function Purchased(){
        return $this->belongsTo('App\User', 'purchase_by_id');
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'card_number', 'card_value', 'expiry_date'
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
