<?php

namespace App\Http\Controllers;

use App\Console\Commands\PrintQRs;
use Illuminate\Support\Facades\Artisan;

class GeneratedQRController extends Controller
{
    public function printQRs(){
        try {
            return Artisan::call(PrintQRs::class);
        } catch(\Exception $e){
            return $e->getMessage();
        }
    }
}
