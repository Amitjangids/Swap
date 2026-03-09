<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class GeneratedQrCode extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public function User(){
        return $this->belongsTo('App\User', 'user_id');
    }
    
    protected $fillable = [
        'user_id', 'phone','amount','unique_key','qr_code','status'
    ];

}
