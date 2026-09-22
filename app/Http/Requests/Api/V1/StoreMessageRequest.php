<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
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
            'connection_id' => ['required', 'uuid'],
            'to' => ['required', 'string', 'max:32'],
            'type' => ['required', 'string', 'in:text,template,image,document,audio,video'],
            'body' => ['required_if:type,text', 'nullable', 'string', 'max:4096'],
            'template' => ['required_if:type,template', 'nullable', 'array'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
