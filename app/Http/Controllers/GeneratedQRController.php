<?php

namespace App\Http\Controllers;

use App\Console\Commands\PrintQRs;
use App\Models\GeneratedQR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class GeneratedQRController extends Controller
{
    public function getRemainingPrintCounter(Request $request)
    {
        $remaining_prints = GeneratedQR::where('tenant_id', $request->tenant_id)->count();
        if ($remaining_prints) {
            $response = [
                "remining_count" => $remaining_prints,
                "message" =>  "current counter fetched successfully",
                "status" => "success"
            ];
        } else {
            $response = [
                "remining_count" => $remaining_prints,
                "message" =>  "current counter not fetched successfully",
                "status" => "false"
            ];
        }
        return response($response)->json();
    }

    public function getCommandStatus(Request $request)
    {
    }

    public function printQRs(Request $request)
    {
        try {
            if ($request->tenant_id) {
                return Artisan::call(PrintQRs::class);
            } else {
                return null;
            }

            // return response($response)->json();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
