<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class Issuertrxref extends Model
{
    protected $fillable = [
        'issuertrxref', 'messages','created_at', 'updated_at'
    ];
}
