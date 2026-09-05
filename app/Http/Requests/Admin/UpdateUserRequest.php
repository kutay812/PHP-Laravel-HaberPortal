<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:8|confirmed',
            'roles'    => 'required|array',
            'roles.*'  => 'exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Ad Soyad alanı zorunludur.',
            'email.required'     => 'E-posta alanı zorunludur.',
            'email.email'        => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique'       => 'Bu e-posta adresi zaten kullanılmaktadır.',
            'password.min'       => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
            'roles.required'     => 'En az bir rol seçilmelidir.',
        ];
    }
}
