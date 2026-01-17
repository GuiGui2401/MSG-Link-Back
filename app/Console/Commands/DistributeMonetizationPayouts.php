<?php

namespace App\Console\Commands;

use App\Models\MonetizationPayout;
use App\Services\MonetizationService;
use Illuminate\Console\Command;

class DistributeMonetizationPayouts extends Command
{
    protected $signature = 'monetization:distribute {type?}';
    protected $description = 'Distribute creator fund and ad revenue payouts';

    public function handle(MonetizationService $monetizationService): int
    {
        $type = $this->argument('type');
        $types = $type ? [$type] : [MonetizationPayout::TYPE_CREATOR_FUND, MonetizationPayout::TYPE_AD_REVENUE];

        foreach ($types as $payoutType) {
            $result = $monetizationService->distribute($payoutType);
            if ($result['already_processed']) {
                $this->info("{$payoutType}: already processed for current period.");
            } else {
                $this->info("{$payoutType}: created {$result['created']} payouts, skipped {$result['skipped']}.");
            }
        }

        return Command::SUCCESS;
    }
}
