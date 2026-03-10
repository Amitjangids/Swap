<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Bulk extends Model
{
    protected $table = 'carddetails';
    protected $fillable = [
        'id', 'serial_number', 'agent_card_value', 'status', 'used_status', 'pin_number','real_value','card_value','used_date','created_at'
    ];
}