<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class Orderdetail extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;
    
    public $sortable = ['id', 'order_id', 'status','created_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id','date','start_time','end_time', 'status'
    ];

}
