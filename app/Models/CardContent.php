<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class CardContent extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;

    public $sortable = ['id', 'cardType', 'title','description','status', 'created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cardType',
        'title',
        'description',
        'status'
    ];
    protected $casts = [
        'description' => 'array',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
}
