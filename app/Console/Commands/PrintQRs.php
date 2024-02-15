<?php

namespace App\Console\Commands;

use App\Models\GeneratedQR;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PrintQRs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'print:qrs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print Qrs and update pack response';

    protected $token = '';
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '256M');
        

        // Login
        $login = $this->warehouseLogin();

        if (count($login) > 0) {
            $this->token = $login["token"]; 
            
            // Print QRs
            $this->printQRs();
        }
    }


    // Print QRs Aling with status update
    public function printQRs()
    {
        $production_numbers = [];
        $pack_numbers = [];
        GeneratedQR::chunk(50, function ($qrs) use(&$pack_numbers, &$production_numbers) {
            foreach ($qrs as $qr) {
                $printer_req_data = [];
                $printer_req_data['printer_ip'] = $qr->printer_ip;
                $printer_req_data['printer_port'] = $qr->printer_port;
                $printer_req_data['value'] = $qr->pack_number . 'B:' . $qr->batch_number . 'RS' . $qr->price . 'MFG:' . Carbon::parse($qr->mfg_date) . 'BB:' . Carbon::parse($qr->expiry_date);

                // Post Request for Python Printing Script
                $headers = [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ];
                $response = Http::withHeaders($headers)
                ->withBody(http_build_query($printer_req_data), 'application/x-www-form-urlencoded')->post(env('PYTHON_SCRIPT_URL'));
                $response_data = json_decode($response, true);
                if(!$response_data["connection_error"]){
                    $pack_numbers[] = $qr->pack_number;
                    $production_numbers[] = $qr->production_number;
                }
                
            }
        }); 
        // Update Pack Status
        if(count($pack_numbers) > 0)
        try {

            $headers = [
                'Accept' => 'application/json',
                'Accept-Language' => 'application/json',
                'tool_id' => env('WAREHOUSE_CREDS_TOOL_ID'),
                'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE'),
                'Authorization' => "Bearer ".$this->token
            ];
  
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders($headers)->post(env("WAREHOUSE_CREDS_URL") . '/warehouse-tool/production_orders/pack_status/update', ["pack_numbers" => $pack_numbers, 'production_numbers' => array_unique($production_numbers)]);
            
            $response_data = json_decode($response->body(), true);
            
         
            if($response_data['success']){
                GeneratedQR::query()->delete();
            }
            return $response_data['data'];
        } catch (\Exception $e) {
            return $e->getMessage();
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
}
