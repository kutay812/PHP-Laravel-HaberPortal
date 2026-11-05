<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // Form hem `password` hem `new_password` alan adını destekler
        if ($this->filled('password') && !$this->filled('new_password')) {
            $this->merge([
                'new_password'              => $this->input('password'),
                'new_password_confirmation' => $this->input('password_confirmation'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,email,' . $userId,
            'profile_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'new_password'     => ['nullable', 'string', 'min:8', 'confirmed'],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'                  => 'Ad Soyad alanı zorunludur.',
            'email.required'                 => 'E-mail alanı zorunludur.',
            'email.unique'                   => 'Bu e-mail adresi zaten kullanılıyor.',
            'profile_image.image'            => 'Profil resmi bir resim dosyası olmalıdır.',
            'profile_image.mimes'            => 'Profil resmi jpeg, png, jpg, gif veya webp formatında olmalıdır.',
            'profile_image.max'              => 'Profil resmi dosyası en fazla 10MB olabilir.',
            'current_password.required_with' => 'Şifre değiştirmek için mevcut şifreyi girmeniz gerekir.',
            'new_password.min'               => 'Şifre en az 8 karakter olmalıdır.',
            'new_password.confirmed'         => 'Yeni şifre tekrarı eşleşmiyor.',
        ];
    }
}
