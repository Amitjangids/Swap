<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class Area extends Model
{
    use Sortable;
    //
    public $sortable = ['id', 'name','created_at'];
    
    public static function getAreaList($id) {
        return Area::where(['city_id' => $id])->orderBy('id', 'ASC')->pluck('name', 'id')->all();
    }
}
