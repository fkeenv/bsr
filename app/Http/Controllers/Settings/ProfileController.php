<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Support\FlashToast;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(
        ProfileUpdateRequest $request,
        UpdateUserProfile $updateUserProfile,
    ): RedirectResponse {
        $user = $request->user();
        $validated = $request->safe()->only(['name', 'email', 'mobile_number']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        /** @var UploadedFile|null $avatar */
        $avatar = $request->file('avatar');
        $shouldUpdateTitle = $request->exists('title');

        if ($shouldUpdateTitle || $avatar instanceof UploadedFile) {
            $updateUserProfile->handle(
                $user,
                $shouldUpdateTitle ? $request->validated('title') : null,
                $avatar,
                $shouldUpdateTitle,
            );
        }

        FlashToast::success(__('Profile updated.'));

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
