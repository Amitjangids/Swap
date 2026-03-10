<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IbanGeneratedList extends Model
{


    protected $fillable = ['id','iban', 'bankCode', 'agencyCode', 'accountNumber', 'ribKey','isUsed','created_at','updated_at'];
}