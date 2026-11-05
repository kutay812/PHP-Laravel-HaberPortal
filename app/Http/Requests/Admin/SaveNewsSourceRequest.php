<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use App\Services\UrlSecurityService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SaveNewsSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(RoleName::adminRoles()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'    => $this->boolean('is_active'),
            'auto_publish' => $this->boolean('auto_publish'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $safeUrlRule = static function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && $value !== '' && !app(UrlSecurityService::class)->isSafePublicUrl($value)) {
                $fail('Belirtilen URL güvenli bir genel HTTP/HTTPS adresi olmalıdır.');
            }
        };

        return [
            'name'                   => ['required', 'string', 'max:150'],
            'url'                    => ['required', 'url', 'max:500', $safeUrlRule],
            'type'                   => ['required', 'in:rss,api,scraper'],
            'website_url'            => ['nullable', 'url', 'max:500'],
            'logo_url'               => ['nullable', 'url', 'max:500'],
            'reliability_score'      => ['required', 'integer', 'between:0,100'],
            'default_category_id'    => ['nullable', 'exists:categories,id'],
            'is_active'              => ['required', 'boolean'],
            'auto_publish'           => ['required', 'boolean'],
            'fetch_interval_minutes' => ['required', 'integer', 'min:5'],
            'notes'                  => ['nullable', 'string'],
        ];
    }
}
