<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class Country extends Model
{
    use Sortable;
    //
    public $sortable = ['id', 'name','code','currency','flag','created_at'];
    
    public static function getCountryList(){
       return Country::orderBy('name', 'ASC')->pluck('name','name')->all();
    }
}
