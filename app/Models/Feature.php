<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
class Feature extends Model
{
    use Sortable;
    //
    public $sortable = ['id', 'name','status','last_updated_by','created_at'];
    
    public function Admin(){
        return $this->belongsTo('App\Models\Admin', 'last_updated_by');
    }
}
