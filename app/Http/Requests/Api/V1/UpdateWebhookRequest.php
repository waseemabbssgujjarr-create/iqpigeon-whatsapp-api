<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\SafeWebhookUrl;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
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
            'url' => ['sometimes', 'url', 'max:2048', new SafeWebhookUrl],
            'events' => ['sometimes', 'nullable', 'array'],
            'events.*' => ['string', 'max:128'],
            'is_active' => ['sometimes', 'boolean'],
            'secret' => ['sometimes', 'nullable', 'string', 'min:16', 'max:255'],
        ];
    }
}
