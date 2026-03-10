<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class WalletManager extends Model
{
    //use HasApiTokens, Notifiable, Sortable;
    
    public function User(){
        return $this->belongsTo('App\User', 'user_id');
    }
    
    public function Merchant(){
        return $this->belongsTo('App\User', 'merchant_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id', 'name'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
