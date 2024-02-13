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
    public $headers = [];
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Warehosue APIs headers
        $this->headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'application/json',
            'tool_id' => env('WAREHOUSE_CREDS_TOOL_ID'),
            'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE')
        ];

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
        $pack_numbers = [];
        GeneratedQR::chunk(50, function ($qrs) use(&$pack_numbers) {
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
                $response = Http::withHeaders($headers)->post(env('PYTHON_SCRIPT_URL'), $printer_req_data);

                dd($response->body());
                $pack_numbers[] = $qr->pack_number;
            }
        });

        // Update Pack Status
        try {
            $response = Http::withHeaders($this->headers)->post(env("WAREHOUSE_CREDS_LOGIN_URL") . '/api/v1/warehouse-tool/production_orders/pack_status/update', $pack_numbers);
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
            $data = [
                'email' => env('WAREHOUSE_CREDS_EMAIL'),
                'password' => env('WAREHOUSE_CREDS_PASSWORD'),
                'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE'),
            ];
            
            $response = Http::withHeaders($this->headers)->post(env("WAREHOUSE_CREDS_LOGIN_URL") . '/api/v1/main-tool/auth/login', $data);
            $response_data = json_decode($response->body(), true);
            return $response_data['data'];
        } catch (\Exception $e) {
            return [];
        }
    }
}
