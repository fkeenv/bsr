<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StoreAnnouncementAttachments
{
    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, AnnouncementAttachment>
     */
    public function handle(Announcement $announcement, User $officer, array $data): Collection
    {
        $files = $data['attachments'] ?? null;

        if (! is_array($files) || $files === []) {
            throw ValidationException::withMessages([
                'attachments' => 'At least one attachment is required.',
            ]);
        }

        $stored = collect();

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                throw ValidationException::withMessages([
                    'attachments' => 'Each attachment must be a file.',
                ]);
            }

            $path = $file->store('announcement-attachments', 'local');

            if ($path === false) {
                throw ValidationException::withMessages([
                    'attachments' => 'An attachment could not be stored.',
                ]);
            }

            $stored->push($announcement->attachments()->create([
                'path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'disk' => 'local',
                'size' => $file->getSize() ?: 0,
            ]));
        }

        $announcement->update([
            'updated_by_user_id' => $officer->id,
        ]);

        return $stored;
    }
}
