<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class DriverActivationCard extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;

    public $sortable = ['id', 'userId', 'driverId', 'created_at', 'updated_at'];

    protected $fillable = [
        'driverId',
        'userId',
        'accountId',
        'otp'
    ];

  /*   public function Driver()
    {
        return $this->belongsTo(Driver::class, 'driverId');
    }
    public function User()
    {
        return $this->belongsTo('App\Models\User', 'userId');
    } */
}
