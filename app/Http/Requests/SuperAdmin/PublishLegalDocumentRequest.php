<?php

namespace App\Http\Requests\SuperAdmin;

use App\Support\SanitizeHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublishLegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $body = $this->input('body');

            if (! is_string($body) || SanitizeHtml::isBlank($body)) {
                $validator->errors()->add('body', 'The body field is required.');
            }
        });
    }
}
