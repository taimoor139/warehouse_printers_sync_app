<?php

namespace App\Http\Controllers;

use App\Models\GeneratedQR;
use Illuminate\Http\Request;

class GeneratedQRController extends Controller
{
    public function printQRs(){
        $generated_qrs = GeneratedQR::chunk(50, function($qrs){
            foreach($qrs as $qr){
                
            }
        });
    }
}
