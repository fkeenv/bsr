<?php

namespace App\Console\Commands;

use App\Actions\Charges\GenerateChargesForPeriod;
use App\Models\AssociationSetting;
use App\Support\BillingPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateChargesCommand extends Command
{
    protected $signature = 'charges:generate
                            {--year= : Billing Period year}
                            {--month= : Billing Period month}
                            {--scheduled : Use Asia/Manila today and only run on the levy day}';

    protected $description = 'Generate missing Charges for a Billing Period';

    public function handle(GenerateChargesForPeriod $generateChargesForPeriod): int
    {
        $period = $this->resolvePeriod();

        if ($period === null) {
            $this->info('Not the levy day; skipping Charge generation.');

            return self::SUCCESS;
        }

        $created = $generateChargesForPeriod->handle($period);

        $this->info("Generated {$created} Charge(s) for {$period->label()}.");

        return self::SUCCESS;
    }

    private function resolvePeriod(): ?BillingPeriod
    {
        $year = $this->option('year');
        $month = $this->option('month');

        if ($year !== null && $month !== null) {
            return new BillingPeriod((int) $year, (int) $month);
        }

        if (! $this->option('scheduled')) {
            $today = Carbon::now('Asia/Manila');

            return new BillingPeriod($today->year, $today->month);
        }

        $today = Carbon::now('Asia/Manila');
        $levyDay = AssociationSetting::current()->levy_day_of_month;
        $runDay = min($levyDay, $today->daysInMonth);

        if ($today->day !== $runDay) {
            return null;
        }

        return new BillingPeriod($today->year, $today->month);
    }
}
