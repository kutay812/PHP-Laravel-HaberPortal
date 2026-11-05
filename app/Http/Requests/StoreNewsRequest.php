<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(RoleName::staffRoles()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];
        foreach (['is_featured', 'is_breaking', 'is_editor_pick'] as $flag) {
            if ($this->has($flag)) {
                $merges[$flag] = $this->boolean($flag);
            }
        }
        if ($this->has('is_active')) {
            $merges['is_active'] = $this->boolean('is_active');
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
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'new_image'      => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'tags'           => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required'       => 'Haber başlığı zorunludur.',
            'title.min'            => 'Haber başlığı en az 5 karakter olmalıdır.',
            'title.max'            => 'Haber başlığı en fazla 255 karakter olmalıdır.',
            'description.required' => 'Haber özeti (açıklama) zorunludur.',
            'description.min'      => 'Haber özeti en az 10 karakter olmalıdır.',
            'description.max'      => 'Haber özeti en fazla 1000 karakter olmalıdır.',
            'content.required'     => 'Haber içeriği zorunludur.',
            'content.min'          => 'Haber içeriği en az 30 karakter olmalıdır.',
            'category_id.required' => 'Lütfen geçerli bir kategori seçin.',
            'category_id.exists'   => 'Seçilen kategori sistemde bulunamadı.',
            'image.image'          => 'Lütfen geçerli bir görsel yükleyin.',
            'image.mimes'          => 'Sadece JPG, JPEG, PNG, GIF ve WEBP formatları desteklenir.',
            'image.max'            => 'Görsel boyutu en fazla 10MB olabilir.',
            'new_image.image'      => 'Lütfen geçerli bir görsel yükleyin.',
            'new_image.mimes'      => 'Sadece JPG, JPEG, PNG, GIF ve WEBP formatları desteklenir.',
            'new_image.max'        => 'Görsel boyutu en fazla 10MB olabilir.',
        ];
    }
}
