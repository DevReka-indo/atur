<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.index', [
            'user' => $request->user(),
        ]);
    }

    // Update the user's profile information.
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Hanya save & tampilkan success jika ada perubahan
        if ($request->user()->isDirty()) {
            $request->user()->save();
            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        // Tidak ada perubahan, redirect tanpa flash message
        return Redirect::route('profile.edit');
    }

    // Update the user's profile photo.
    public function updatePhoto(Request $request): RedirectResponse
{
    $user = $request->user();

    // Jika user minta hapus foto
    if ($request->remove_photo == '1') {
        if ($user->profile_photo) {
            Storage::delete('public/' . $user->profile_photo);
            $user->update(['profile_photo' => null]);
        }
        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    // Jika upload foto baru
    $request->validate([
        'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);

    if ($user->profile_photo) {
        Storage::delete('public/' . $user->profile_photo);
    }

    $path = $request->file('profile_photo')->store('profile-photos', 'public');

    $user->update(['profile_photo' => $path]);

    return Redirect::route('profile.edit')->with('status', 'photo-updated');
}

    // Delete the user's profile photo.
    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo) {
            Storage::delete('public/' . $user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        return Redirect::route('profile.edit')->with('status', 'photo-deleted');
    }

    // Delete the user's account.
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // validasi password kalau bukan google
        if (!$user->google_id) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        $user->workspaces()->detach();
        $user->projects()->detach();

        $user->activityLogs()->delete();
        $user->comments()->delete();
        $user->attachments()->delete();
        $user->assignedTasks()->delete();
        $user->createdTasks()->delete();
        $user->createdBaselines()->delete();
        $user->recordedProgress()->delete();
        $user->createdProjects()->delete();
        $user->createdWorkspaces()->delete();

        \App\Models\Notification::where('user_id', $user->id)->delete();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
