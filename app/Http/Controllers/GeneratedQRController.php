<?php

namespace App\Http\Controllers;

use App\Console\Commands\PrintQRs;
use App\Http\Requests\Print\CreateRequest;
use App\Http\Requests\Print\StopRequest;
use App\Models\GeneratedQR;
use App\Models\PrintCommandStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

// use Symfony\Component\Process\Process;

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

    /****** Print QRs *****/
    public function printQRs(CreateRequest $request)
    {
        try {
            $process = PrintCommandStatus::create([
                'production_order_id' => $request->production_order_id,
                'start_time' => Carbon::now()->toTimeString(),
                'status' => PrintCommandStatus::STARTED
            ]);
            return response()->json([
                'success' => true,
                'data' => $process->id,
                'message' => 'Printing has been started',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /******* Stop Printing *******/
    public function stopPrintingQRs(StopRequest $request)
    {
        try {
            $printing_print = PrintCommandStatus::find($request->process_id);
            $message = "";
            if ($printing_print && $printing_print->status == PrintCommandStatus::STARTED) {
                if ($request->process_type == 1) {
                    $printing_print->update([
                        'status' => PrintCommandStatus::PAUSED
                    ]);
                    $message = "Printing has been paused successfully";
                } else if ($request->process_type == 2) {
                    $printing_print->update([
                        'end_date' => Carbon::now()->toTimeString(),
                        'status' => PrintCommandStatus::ENDED
                    ]);

                    $pack_numbers = GeneratedQR::where('production_number', $printing_print->production_number)->pluck()->toArray();
                    $response_data = $this->warehouseConfiguration($pack_numbers, [$printing_print->production_number]);
                    if ($response_data['success']) {
                        GeneratedQR::where('production_number', $printing_print->production_number)->delete();
                    }
                    $message = "Printing has been stopped successfully";
                }
            }

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /******* Resume Printing *******/
    public function resumePrintingQRs(CreateRequest $request)
    {
        try {
            $printing_print = PrintCommandStatus::where('production_number', $request->production_number)->first();
            $message = "";

            if ($printing_print) {
                $printing_print->update([
                    'status' => PrintCommandStatus::STARTED
                ]);
                $message = 'Printing resumed successfully!';
            } else {
                $message = 'No data found!';
            }
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Login API for warehouse system
    public function warehouseLogin()
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'Accept-Language' => 'application/json',
                'tool_id' => env('WAREHOUSE_CREDS_TOOL_ID'),
                'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE')
            ];
            $data = [
                'email' => env('WAREHOUSE_CREDS_EMAIL'),
                'password' => env('WAREHOUSE_CREDS_PASSWORD'),
                'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE'),
            ];

            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders($headers)->post(env("WAREHOUSE_CREDS_URL") . '/main-tool/auth/login', $data);
            $response_data = json_decode($response->body(), true);
            return $response_data['data'];
        } catch (\Exception $e) {
            return [$e->getMessage()];
        }
    }

    // Warehouse call back api configuration
    public function warehouseConfiguration($pack_numbers, $production_numbers)
    {
        try {
            $login = $this->warehouseLogin();
            $headers = [
                'Accept' => 'application/json',
                'Accept-Language' => 'application/json',
                'tool_id' => env('WAREHOUSE_CREDS_TOOL_ID'),
                'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE'),
                'Authorization' => "Bearer " . $login["token"]
            ];

            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders($headers)->post(env("WAREHOUSE_CREDS_URL") . '/warehouse-tool/production_orders/pack_status/update', ["pack_numbers" => $pack_numbers, 'production_numbers' => array_unique($production_numbers)]);

            $response_data = json_decode($response->body(), true);

            return $response_data;
        } catch (\Exception $e) {
            return [$e->getMessage()];
        }
    }
}
