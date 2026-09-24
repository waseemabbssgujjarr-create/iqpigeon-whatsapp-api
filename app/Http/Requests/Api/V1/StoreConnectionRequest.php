<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'external_ref' => ['nullable', 'string', 'max:255'],
            'return_url' => ['nullable', 'string', 'url', 'max:2048'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
