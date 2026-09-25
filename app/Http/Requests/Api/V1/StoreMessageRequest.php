<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $template = $this->input('template');

        if (! is_array($template)) {
            return;
        }

        if (isset($template['name']) && is_string($template['name'])) {
            $template['name'] = trim($template['name']);
        }

        $this->merge(['template' => $template]);
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
            'template.name' => ['required_if:type,template', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/'],
            'template.language' => ['required_if:type,template', 'array'],
            'template.language.code' => ['required_if:type,template', 'string', 'max:32'],
            'template.components' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
