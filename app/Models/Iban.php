<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iban extends Model
{


    protected $fillable = ['id','iban','userId', 'bankCode', 'agencyCode', 'accountNumber', 'ribKey','countryId','walletManagerId','created_at','updated_at'];
}