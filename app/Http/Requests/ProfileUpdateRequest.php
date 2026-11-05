<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|max:255|unique:users,email,' . $userId,
            'profile_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'current_password' => 'nullable|required_with:password|string',
            'password'         => 'nullable|string|min:8|confirmed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'                  => 'Ad Soyad alanı zorunludur.',
            'email.required'                 => 'E-posta alanı zorunludur.',
            'email.email'                    => 'Geçerli bir e-posta adresi girin.',
            'email.unique'                   => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.',
            'profile_image.image'            => 'Profil fotoğrafı geçerli bir görsel olmalıdır.',
            'profile_image.mimes'            => 'Profil fotoğrafı formatı JPG, JPEG, PNG veya WEBP olmalıdır.',
            'profile_image.max'              => 'Profil fotoğrafı boyutu en fazla 2MB olabilir.',
            'current_password.required_with' => 'Şifrenizi değiştirmek için mevcut şifrenizi girmelisiniz.',
            'password.min'                   => 'Yeni şifre en az 8 karakter olmalıdır.',
            'password.confirmed'             => 'Yeni şifre onayı eşleşmiyor.',
        ];
    }
}
