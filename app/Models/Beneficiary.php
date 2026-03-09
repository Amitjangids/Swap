<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class Beneficiary extends Model
{
    protected $table = 'beneficiary';

    protected $fillable = [
        'userId',
        'parentId',
        'type',
        'first_name',
        'name',
        'country',
        'country_code',
        'telephone',
        'walletManagerId',
        'status',
    ];

    public function walletManager()
    {
        return $this->belongsTo(WalletManager::class, 'walletManagerId', 'id');
    }

    public function countryDetail()
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

}
