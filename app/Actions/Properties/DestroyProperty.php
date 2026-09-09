<?php

namespace App\Actions\Properties;

use App\Models\Property;
use InvalidArgumentException;

class DestroyProperty
{
    public function handle(Property $property): void
    {
        if ($property->hasBeenCharged()) {
            throw new InvalidArgumentException(
                'A Property that has been charged can only be made inactive.',
            );
        }

        $property->delete();
    }
}
