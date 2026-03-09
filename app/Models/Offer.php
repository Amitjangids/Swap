<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Offer extends Model
{
    use Sortable;
    
    public $sortable = ['id', 'type', 'offer', 'status', 'created_at'];
    
    public function Admin(){
        return $this->belongsTo('App\Models\Admin', 'last_updated_by');
    }
    
    protected $fillable = [
        'id', 'type', 'offer', 'status', 'created_at', 'updated_at',
    ];
}
