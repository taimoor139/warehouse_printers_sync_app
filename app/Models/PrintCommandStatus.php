<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintCommandStatus extends Model
{
    use HasFactory;
    public $table = 'print_command_status';
    public $fillable = ['tenant_id', 'production_order_id', 'start_date', 'end_date', 'status', 'deleted_at'];

    const STARTED = 1;
    const PAUSED = 2;
    const ENDED  = 3;
}
