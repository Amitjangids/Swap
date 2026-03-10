<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class Errorrecords extends Model
{
    use Sortable;
    //
    public $sortable = ['id', 'title','message','created_at'];
}
