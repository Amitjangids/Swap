<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class Currency extends Model
{
    use Sortable;
    //
    public $sortable = ['id', 'currency','created_at'];
    
    public static function getCurrencyList(){
       return Currency::where('currency','!=',null)->orderBy('currency', 'ASC')->pluck('currency','currency')->all();
    }
}
