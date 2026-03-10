<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Location extends Model
{ 

    public $sortable = ['id', 'name', 'address', 'telephone', 'created_at', 'updated_at'];

    protected $table = "ecobank_location";
    protected $fillable = [
        'name',
        'address',
        'telephone',
    ];

}
