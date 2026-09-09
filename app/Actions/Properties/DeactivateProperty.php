<?php

namespace App\Actions\Properties;

use App\Models\Property;

class DeactivateProperty
{
    public function handle(Property $property): Property
    {
        $property->update(['is_active' => false]);

        return $property->refresh();
    }
}
