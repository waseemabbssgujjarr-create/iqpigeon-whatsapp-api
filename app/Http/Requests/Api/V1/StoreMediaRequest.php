<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:image,document,audio,video'],
            'media' => ['required', 'array'],
            'caption' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
