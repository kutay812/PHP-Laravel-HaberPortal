<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function getAllUsers()
    {
        return User::all();
    }

    public function getUserById($id)
    {
        return User::findOrFail($id);
    }

    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function updateUser($id, array $data)
    {
        $user = User::findOrFail($id);
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return $user;
    }

    public function deleteUser($id)
    {
        if ($id == Auth::id()) {
            throw new \Exception('Kendi hesabınızı silemezsiniz.');
        }
        return User::findOrFail($id)->delete();
    }

    public function profileEdit($user, $data)
    {
        // Only verify current password if user is trying to change password
        if (!empty($data['password']) || !empty($data['current_password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                throw new \Exception('Mevcut şifreniz yanlış veya girilmedi.');
            }
            $user->password = Hash::make($data['password']);
        }

        $user->name = $data['name'] ?? $user->name;
        $user->email = $data['email'] ?? $user->email;

        if (isset($data['profile_image']) && $data['profile_image']) {
            // Profil resmi güncelle
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $user->profile_image = $data['profile_image']->store('profile-image', 'public');
        }

        $user->save();
        return $user;
    }
}
