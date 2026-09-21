<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileAvatarController extends Controller
{
    /**
     * Stream the authenticated user's avatar image.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $profile = $request->user()?->profile;

        abort_unless(filled($profile?->avatar_path), 404);

        return Storage::disk('local')->response($profile->avatar_path);
    }
}
