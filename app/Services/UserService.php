<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @return Collection<int, User>
     */
    public function getAllUsers(): Collection
    {
        return User::all();
    }

    public function getUserById(int|string $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): User
    {
        return User::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateUser(int|string $id, array $data): User
    {
        $user = User::findOrFail($id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return $user;
    }

    public function deleteUser(int|string $id): ?bool
    {
        if ((int) $id === (int) Auth::id()) {
            throw ValidationException::withMessages([
                'user' => 'Kendi hesabınızı silemezsiniz.',
            ]);
        }

        return User::findOrFail($id)->delete();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function profileEdit(User $user, array $data): User
    {
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

        return $user;
    }
}
