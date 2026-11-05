<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.edit') ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $routeCategory = $this->route('category');
        $categoryId = $routeCategory instanceof Category ? $routeCategory->id : $routeCategory;

        return [
            'name'             => 'required|string|max:190|unique:categories,name,' . $categoryId,
            'title'            => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'main_category_id' => 'nullable|exists:main_categories,id',
            'image_id'         => 'nullable|exists:images,id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'           => 'Kategori adı zorunludur.',
            'name.max'                => 'Kategori adı en fazla 190 karakter olabilir.',
            'name.unique'             => 'Bu kategori adı zaten kullanılmaktadır.',
            'main_category_id.exists' => 'Seçilen ana kategori geçerli değil.',
            'image_id.exists'         => 'Seçilen görsel geçerli değil.',
        ];
    }
}
