<?php

namespace App\Actions\Settings;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateUserProfile
{
    public function handle(
        User $user,
        ?string $title = null,
        ?UploadedFile $avatar = null,
        bool $shouldUpdateTitle = false,
    ): ?UserProfile {
        $hasAvatar = $avatar instanceof UploadedFile;
        $profile = $user->profile;

        if (! $shouldUpdateTitle && ! $hasAvatar) {
            return $profile;
        }

        $normalizedTitle = filled($title) ? $title : null;

        if ($profile === null && ! $hasAvatar && $normalizedTitle === null) {
            return null;
        }

        $profile ??= $user->profile()->create([]);

        $attributes = [];

        if ($shouldUpdateTitle) {
            $attributes['title'] = $normalizedTitle;
        }

        if ($hasAvatar) {
            $path = $avatar->store('avatars', 'local');

            if ($path === false) {
                throw ValidationException::withMessages([
                    'avatar' => 'The avatar could not be stored.',
                ]);
            }

            if (filled($profile->avatar_path)) {
                Storage::disk('local')->delete($profile->avatar_path);
            }

            $attributes['avatar_path'] = $path;
        }

        $profile->update($attributes);

        return $profile->refresh();
    }
}
