<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'token'    => 'required|string',
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required'     => 'E-posta adresi gereklidir.',
            'email.email'        => 'Geçerli bir e-posta adresi giriniz.',
            'email.exists'       => 'Bu e-posta adresi sistemde kayıtlı değil.',
            'password.required'  => 'Şifre gereklidir.',
            'password.min'       => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre tekrarı eşleşmiyor.',
            'token.required'     => 'Geçersiz token.',
        ];
    }
}

