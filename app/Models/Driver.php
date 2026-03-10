<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class Driver extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;

    public $sortable = ['id', 'name', 'email','phone','status', 'created_at', 'updated_at'];

    protected $fillable = [
        'name', 'email','phone','status'
    ];
}
