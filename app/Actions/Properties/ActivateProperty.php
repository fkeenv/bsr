<?php

namespace App\Actions\Properties;

use App\Models\Property;

class ActivateProperty
{
    public function handle(Property $property): Property
    {
        $property->update(['is_active' => true]);

        return $property->refresh();
    }
}
