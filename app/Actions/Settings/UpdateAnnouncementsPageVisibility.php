<?php

namespace App\Actions\Settings;

use App\Enums\AnnouncementsPageVisibility;
use App\Models\AssociationSetting;

class UpdateAnnouncementsPageVisibility
{
    public function handle(AnnouncementsPageVisibility $visibility): AssociationSetting
    {
        $settings = AssociationSetting::current();
        $settings->update([
            'announcements_page_visibility' => $visibility,
        ]);

        return $settings->refresh();
    }
}
