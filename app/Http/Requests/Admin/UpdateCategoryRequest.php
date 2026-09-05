<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name'             => 'required|string|max:255|unique:categories,name,' . $categoryId,
            'description'      => 'nullable|string',
            'main_category_id' => 'nullable|exists:main_categories,id',
            'image_id'         => 'nullable|exists:images,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Kategori adı zorunludur.',
            'name.unique'             => 'Bu kategori adı zaten kullanılmaktadır.',
            'main_category_id.exists' => 'Seçilen ana kategori geçerli değil.',
            'image_id.exists'         => 'Seçilen görsel geçerli değil.',
        ];
    }
}
