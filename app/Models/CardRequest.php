<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class CardRequest extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;

    public $sortable = ['id', 'user_id', 'status', 'created_at', 'updated_at'];

    protected $fillable = [
        'user_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function userCard()
{
    return $this->hasOne(UserCard::class, 'userId', 'user_id');
}


}
