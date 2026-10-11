<?php

namespace App\Enums;

enum AnnouncementPermission: string
{
    case Read = 'announcements.read';
    case Create = 'announcements.create';
    case Edit = 'announcements.edit';
    case Publish = 'announcements.publish';
    case Pin = 'announcements.pin';
    case Attachments = 'announcements.attachments';

    public function label(): string
    {
        return match ($this) {
            self::Read => 'Read staff Announcements',
            self::Create => 'Create drafts',
            self::Edit => 'Edit Announcements',
            self::Publish => 'Publish and unpublish',
            self::Pin => 'Pin and unpin',
            self::Attachments => 'Upload attachments',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Read => 'Read shared drafts and Public, Private, and Hidden Announcements.',
            self::Create => 'Write new Announcement drafts.',
            self::Edit => 'Change Announcement content and visibility.',
            self::Publish => 'Publish Announcements or return them to drafts.',
            self::Pin => 'Pin or unpin published Announcements.',
            self::Attachments => 'Add images and PDFs to Announcements.',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
