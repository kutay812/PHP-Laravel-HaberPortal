<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();

        return view('admin.profile.index', compact('user'));
    }

    public function update(AdminProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $validated = $request->validated();

        if (!empty($validated['new_password'])) {
            if (!Hash::check((string) ($validated['current_password'] ?? ''), $user->password)) {
                return back()->withErrors(['current_password' => 'Mevcut şifre yanlış.'])->withInput();
            }
            $user->password = (string) $validated['new_password'];
        }

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $path = (string) $request->file('profile_image')->store('profile-image', 'public');
            $user->profile_image = $path;
        }

        $user->name = (string) $validated['name'];
        $user->email = (string) $validated['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('admin.profile')
            ->with('success', 'Profil başarıyla güncellendi.');
    }

    public function deleteProfileImage(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $user->profile_image = null;
        $user->save();

        return redirect()->route('admin.profile')
            ->with('success', 'Profil resmi başarıyla silindi.');
    }

    /**
     * Profil resmini dizin geçişine (path traversal) karşı güvenli şekilde göster.
     */
    public function showProfileImage(string $filename): BinaryFileResponse
    {
        $safeFilename = basename($filename);
        if ($safeFilename === '' || $safeFilename === '.' || $safeFilename === '..' || $safeFilename !== $filename) {
            abort(404);
        }

        $path = 'profile-image/' . $safeFilename;
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}
