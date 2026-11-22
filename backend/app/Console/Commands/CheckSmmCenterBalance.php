<?php

namespace App\Console\Commands;

use App\Services\SmmCenterBalanceMonitor;
use App\Services\SmmCenterClient;
use Illuminate\Console\Command;

class CheckSmmCenterBalance extends Command
{
    protected $signature = 'smm-center:check-balance';

    protected $description = 'Check and record the SMM Center supplier balance';

    public function handle(SmmCenterClient $client, SmmCenterBalanceMonitor $monitor): int
    {
        $check = $monitor->check($client);

        if ($check->status !== 'success') {
            $this->error('SMM Center balance check failed; the previous successful balance remains available.');
            return self::FAILURE;
        }

        $this->info('SMM Center balance recorded: '.$check->currency.' '.$check->amount.'.');
        return self::SUCCESS;
    }
}
