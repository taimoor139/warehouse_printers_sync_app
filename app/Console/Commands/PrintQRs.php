<?php

namespace App\Console\Commands;

use App\Models\GeneratedQR;
use App\Models\PrintCommandStatus;
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
    protected $signature = 'print:qrs
                            
                            {--printer_ip= :Printer IP on which command should run}';

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
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', '4621353235231');
        ini_set('xdebug.max_nesting_level', 4621353235231);


        // Login
        // $login = $this->warehouseLogin();

        // if (count($login) > 0) {
        //     $this->token = $login["token"];

        // Print QRs
        $this->printQRs();
        // }
    }


    // Print QRs Aling with status update
    public function printQRs()
    {
        $printer_ip = $this->option('printer_ip');
        $printer_count = 0;
        while (true) {
            $process_Status = PrintCommandStatus::where('status', PrintCommandStatus::STARTED)->first();
            if ($process_Status) {
                $print_qr = GeneratedQR::where('printer_ip', $printer_ip)->orWhere([
                    'new_printer_ip' => $printer_ip,
                    'is_replaced' => 1
                ])->first();

                if ($print_qr) {
                    $this->line($printer_count);
                    $printer_ip = $print_qr->is_replaced ? $print_qr->new_printer_ip : $print_qr->printer_ip;
                    $value = $this->genenrateValueString($print_qr->pack_number, $print_qr->batch_number, $print_qr->price, $print_qr->mfg_date, $print_qr->expiry_date);
                    $this->line($value);
                    $response = $this->printerValues($printer_ip, $print_qr->printer_port, $value, $printer_count);

                    if ($response['status']) {
                        $printer_count = $response['current_counter'];
                        $pack_numbers[] = $print_qr->pack_number;
                        $production_numbers[] = $print_qr->production_number;

                        $print_qr->delete();
                    }
                } else {
                    continue;
                }
            } else {
                continue;
            }
        }
        // $production_numbers = [];
        // $pack_numbers = [];
        // $printer_count = 0;
        // GeneratedQR::chunk(50, function ($qrs) use (&$pack_numbers, &$production_numbers, &$printer_count) {
        //     foreach ($qrs as $qr) {
        //         dump($qr->pack_number);
        //         // $this->line($printer_count);
        //         // $value = $this->genenrateValueString($qr->pack_number, $qr->batch_number, $qr->price, $qr->mfg_date, $qr->expiry_date);
        //         // $response = $this->printerValues($qr->printer_ip, $qr->printer_port, $value, $printer_count);
        //         // $this->line($value);
        //         // dump($response);
        //         // if ($response['status']) {
        //         //     $printer_count = $response['current_counter'];
        //         //     $pack_numbers[] = $qr->pack_number;
        //         //     $production_numbers[] = $qr->production_number;

        //         //     $qr->delete();
        //         // }
        //     }
        // });

        // Update Pack Status
        // if (count($pack_numbers) > 0) {
        //     try {
        //         $response_data = $this->warehouseConfiguration($pack_numbers, $production_numbers);

        //         if ($response_data['success']) {
        //             GeneratedQR::query()->delete();
        //         }
        //         $this->line($response_data['message']);
        //         return 'Data printed successfully';
        //     } catch (\Exception $e) {
        //         $this->line($e->getMessage());
        //         return $e->getMessage();
        //     }
        // }
    }

    // Login API for warehouse system
    // public function warehouseLogin()
    // {
    //     try {
    //         $headers = [
    //             'Accept' => 'application/json',
    //             'Accept-Language' => 'application/json',
    //             'tool_id' => env('WAREHOUSE_CREDS_TOOL_ID'),
    //             'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE')
    //         ];
    //         $data = [
    //             'email' => env('WAREHOUSE_CREDS_EMAIL'),
    //             'password' => env('WAREHOUSE_CREDS_PASSWORD'),
    //             'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE'),
    //         ];

    //         $response = Http::withOptions([
    //             'verify' => false,
    //         ])->withHeaders($headers)->post(env("WAREHOUSE_CREDS_URL") . '/main-tool/auth/login', $data);
    //         $response_data = json_decode($response->body(), true);
    //         return $response_data['data'];
    //     } catch (\Exception $e) {
    //         return [$e->getMessage()];
    //     }
    // }

    // Warehouse call back api configuration
    // public function warehouseConfiguration($pack_numbers, $production_numbers)
    // {
    //     $headers = [
    //         'Accept' => 'application/json',
    //         'Accept-Language' => 'application/json',
    //         'tool_id' => env('WAREHOUSE_CREDS_TOOL_ID'),
    //         'tenant-code' => env('WAREHOUSE_CREDS_TENANT_CODE'),
    //         'Authorization' => "Bearer " . $this->token
    //     ];

    //     $response = Http::withOptions([
    //         'verify' => false,
    //     ])->withHeaders($headers)->post(env("WAREHOUSE_CREDS_URL") . '/warehouse-tool/production_orders/pack_status/update', ["pack_numbers" => $pack_numbers, 'production_numbers' => array_unique($production_numbers)]);

    //     $response_data = json_decode($response->body(), true);

    //     return $response_data;
    // }

    // Printer Configurations 
    public function printerConfiguration($payload)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $response = Http::withHeaders($headers)
            ->post(env('PYTHON_SCRIPT_URL'),  $payload);

        $response_data = json_decode($response->body(), true);

        return $response_data;
    }

    // Printer Counter Check
    public function printerCounterCheck($printer_ip, $printer_port, $printer_count)
    {
        $printer_req_data = [];
        $printer_req_data['printer_ip'] = $printer_ip;
        $printer_req_data['printer_port'] = $printer_port;
        $printer_req_data['product_count'] = "";

        // Post Request for Python Printing Script
        $response_data = $this->printerConfiguration($printer_req_data);

        dump($response_data);
        if (is_array($response_data) && array_key_exists('additional_info', $response_data)) {
            if ($response_data['additional_info'] > $printer_count) {
                return ['current_counter' => $response_data['additional_info'], 'status' => true];
            } else {
                return ['current_counter' => $printer_count, 'status' => false];
            }
        } else {
            return ['current_counter' => $printer_count, 'status' => false];
        }
    }

    // Print value + addwait if print count is not changed
    public function printerValues($printer_ip, $printer_port, $value, $printer_count)
    {
        $printer_counter = $this->printerCounterCheck($printer_ip, $printer_port, $printer_count);
        dump($printer_counter);
        if ($printer_counter['status']) {
            $printer_req_data['printer_ip'] = $printer_ip;
            $printer_req_data['printer_port'] = $printer_port;
            $printer_req_data['value'] = $value;
            $printer_req_data['add_value'] = "";

            dump($printer_req_data);
            $response_data = $this->printerConfiguration($printer_req_data);

            if ($response_data["success_message"]) {
                return ['current_counter' => $printer_counter['current_counter'], 'status' => true];
            } else {
                return ['current_counter' => $printer_counter['current_counter'], 'status' => false];
            }
        } else {
            sleep(1);
            return $this->printerValues($printer_ip, $printer_port, $value, $printer_count);
        }
    }


    function genenrateValueString($pack_number, $batch_number, $price, $mfg_date, $expiry_date)
    {
        $price =  $price > 0 ? 'RS:' . $price : '*******';
        $batch_number = str_pad($batch_number, 6, '0', STR_PAD_LEFT);
        $this->line($batch_number);

        return $pack_number . '' . $batch_number . Carbon::now()->toTimeString() .  $price . 'MFG:' . Carbon::parse($mfg_date)->format('Y-m-d') . 'BB:' . Carbon::parse($expiry_date)->format('Y-m-d');
    }
}
