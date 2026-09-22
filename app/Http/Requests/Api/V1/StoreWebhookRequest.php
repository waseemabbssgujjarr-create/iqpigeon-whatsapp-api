<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\SafeWebhookUrl;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:2048', new SafeWebhookUrl],
            'events' => ['nullable', 'array'],
            'events.*' => ['string', 'max:128'],
            'secret' => ['nullable', 'string', 'min:16', 'max:255'],
        ];
    }
}
