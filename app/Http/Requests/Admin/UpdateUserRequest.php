<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $routeUser = $this->route('user');
        $targetUser = $routeUser instanceof User ? $routeUser : User::find($routeUser);
        $currentUser = $this->user();

        // Kendi profilini düzenleyen Super Admin'in rolü formda kilitli olduğundan her zaman korunur
        if (
            $currentUser !== null
            && $targetUser !== null
            && $currentUser->id === $targetUser->id
            && $currentUser->hasRole(RoleName::SuperAdmin->value)
        ) {
            $superAdminRoleId = Role::where('name', RoleName::SuperAdmin->value)->value('id');
            if ($superAdminRoleId !== null) {
                $roles = is_array($this->input('roles')) ? $this->input('roles') : [];
                $roles[] = (int) $superAdminRoleId;
                $this->merge([
                    'roles' => array_values(array_unique(array_map('intval', $roles))),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? $routeUser->id : $routeUser;

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
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
            'password.min'       => 'Şifre en az 8 karakter olmalıdır.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
            'roles.required'     => 'En az bir rol seçilmelidir.',
            'roles.*.exists'     => 'Seçilen rol geçersiz veya bu rolü atama yetkiniz bulunmuyor.',
        ];
    }
}
