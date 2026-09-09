<?php

namespace App\Actions\Suspends;

use App\Models\Suspend;

class UpdateSuspend
{
    use ResolvesSuspendPeriods;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Suspend $suspend, array $data): Suspend
    {
        $suspend->update($this->suspendPayload($data));

        return $suspend->refresh();
    }
}
