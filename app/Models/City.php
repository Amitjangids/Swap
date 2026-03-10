<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class City extends Model
{
    use Sortable;
    //
    public $sortable = ['id', 'name_en','created_at'];
    
    public static function getCityList(){
       return City::orderBy('id', 'ASC')->pluck('name_en','id')->all();
    }

    public static function getCityListAr(){
       return City::orderBy('id', 'ASC')->pluck('name_ar','id')->all();
    }
}
