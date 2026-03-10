<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Kyslik\ColumnSortable\Sortable;

class HelpTicket extends Authenticatable
{
    use HasApiTokens, Notifiable, Sortable;

    public $sortable = ['id', 'userId', 'ticketId','categoryId','description','imagePath','status', 'created_at', 'updated_at'];

    protected $fillable = [
        'userId', 'ticketId','categoryId','description','imagePath','comment','status','created_at','updated_at'
    ];


    public function User(){
        return $this->belongsTo('App\User', 'userId');
    }
    public function HelpCat(){
        return $this->belongsTo('App\Models\HelpCategory', 'categoryId');
    }
}
