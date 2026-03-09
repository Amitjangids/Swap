<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'notif_title', 'notif_body','notif_title_fr','notif_body_fr','transPaymentType','transactionId','created_at', 'updated_at',
    ];
}
