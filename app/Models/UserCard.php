<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class UserCard extends Authenticatable
{

    public $sortable = ['id', 'userId', 'accountId','last4Digits','passCode','cardType','cardImage','cardStatus', 'created_at', 'updated_at'];

    protected $fillable = [
        'userId', 'accountId','last4Digits','passCode','cardType','cardImage','cardStatus', 'created_at', 'updated_at'
    ];


    public function User(){
        return $this->belongsTo('App\User', 'userId');
    }
}
