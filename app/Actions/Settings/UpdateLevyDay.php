<?php

namespace App\Actions\Settings;

use App\Models\AssociationSetting;
use InvalidArgumentException;

class UpdateLevyDay
{
    public function handle(int $levyDayOfMonth): AssociationSetting
    {
        if ($levyDayOfMonth < 1 || $levyDayOfMonth > 31) {
            throw new InvalidArgumentException('Levy day must be between 1 and 31.');
        }

        $settings = AssociationSetting::current();
        $settings->update([
            'levy_day_of_month' => $levyDayOfMonth,
        ]);

        return $settings->refresh();
    }
}
