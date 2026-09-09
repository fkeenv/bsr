<?php

namespace App\Actions\Suspends;

use App\Models\Suspend;

class CreateSuspend
{
    use ResolvesSuspendPeriods;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Suspend
    {
        return Suspend::query()->create($this->suspendPayload($data));
    }
}
