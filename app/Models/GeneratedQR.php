<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedQR extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "generated_qrs";
    public $fillable = ['production_number', 'pack_number', 'product_id', 'printer_ip', 'batch_number', 'qr_number', 'mfg_date', 'expiry_date', 'price', 'status', 'deleted_at'];
}
