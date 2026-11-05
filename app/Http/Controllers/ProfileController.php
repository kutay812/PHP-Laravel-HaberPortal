<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $roles = $user->roles->pluck('name')->all();

        return view('profile', compact('user', 'roles'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $data = $request->validated();

        if (!empty($data['password']) || !empty($data['current_password'])) {
            if (empty($data['current_password']) || !Hash::check((string) $data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Mevcut şifreniz yanlış veya girilmedi.',
                ]);
            }
            $user->password = (string) $data['password'];
        }

        $user->name = isset($data['name']) ? (string) $data['name'] : $user->name;
        $user->email = isset($data['email']) ? (string) $data['email'] : $user->email;

        if (isset($data['profile_image']) && $data['profile_image'] instanceof UploadedFile) {
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $user->profile_image = (string) $data['profile_image']->store('profile-image', 'public');
        }

        $user->save();

        return redirect('/')->with('success', 'Profiliniz başarıyla güncellendi.');
    }
}
