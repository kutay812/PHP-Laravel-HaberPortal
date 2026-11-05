<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

class SaveTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(RoleName::staffRoles()) ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $routeTag = $this->route('tag');
        $tagId = $routeTag instanceof Tag ? $routeTag->id : $routeTag;
        $uniqueRule = $tagId !== null ? 'unique:tags,name,' . $tagId : 'unique:tags,name';

        return [
            'name'  => 'required|string|max:100|' . $uniqueRule,
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }
}

