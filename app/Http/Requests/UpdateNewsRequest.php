<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(RoleName::staffRoles()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];
        foreach (['is_featured', 'is_breaking', 'is_editor_pick', 'is_active'] as $flag) {
            if ($this->has($flag)) {
                $merges[$flag] = $this->boolean($flag);
            }
        }
        if ($merges !== []) {
            $this->merge($merges);
        }
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'title'          => 'required|string|min:5|max:255',
            'description'    => 'required|string|min:10|max:1000',
            'content'        => 'required|string|min:30',
            'category_id'    => 'required|exists:categories,id',
            'is_active'      => 'nullable|boolean',
            'is_featured'    => 'nullable|boolean',
            'is_breaking'    => 'nullable|boolean',
            'is_editor_pick' => 'nullable|boolean',
            'new_image'      => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'tags'           => 'nullable|string|max:500',
        ];
    }
}
