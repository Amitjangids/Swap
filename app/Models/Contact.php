<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class Contact extends Model
{
    use Sortable;
    //
    public $sortable = ['id', 'email','subject','message','created_at'];
    
    public function User(){
        return $this->belongsTo('App\User', 'user_id');
    }
}
