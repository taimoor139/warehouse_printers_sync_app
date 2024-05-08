<?php

namespace App\Console\Commands;

use App\Models\GeneratedQR;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear daatbase data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dd(Carbon::now()->toString());
        GeneratedQR::delete();
        $this->line('QRs deleted successfully!');
    }
}
