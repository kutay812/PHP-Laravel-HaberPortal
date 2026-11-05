<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isSuperAdmin = $this->user()?->hasRole(RoleName::SuperAdmin->value) ?? false;
        $allowedRoleRule = Rule::exists('roles', 'id');

        if (!$isSuperAdmin) {
            $superAdminRoleId = Role::where('name', RoleName::SuperAdmin->value)->value('id');
            if ($superAdminRoleId !== null) {
                $allowedRoleRule = Rule::exists('roles', 'id')->whereNot('id', $superAdminRoleId);
            }
        }

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['integer', $allowedRoleRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'Ad Soyad alanı zorunludur.',
            'email.required'     => 'E-posta alanı zorunludur.',
            'email.email'        => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique'       => 'Bu e-posta adresi zaten kullanılmaktadır.',
            'password.required'  => 'Şifre alanı zorunludur.',
            'password.min'       => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
            'roles.required'     => 'En az bir rol seçilmelidir.',
            'roles.*.exists'     => 'Seçilen rol geçersiz veya bu rolü atama yetkiniz bulunmuyor.',
        ];
    }
}
