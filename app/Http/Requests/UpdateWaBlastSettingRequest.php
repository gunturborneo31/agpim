<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWaBlastSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin', 'admin_prokopim') ?? false;
    }

    public function rules(): array
    {
        return [
            'cloud_enabled' => ['nullable', 'boolean'],
            'web_enabled' => ['nullable', 'boolean'],
            'default_backend' => ['required', Rule::in(['auto', 'cloud', 'web'])],
            'web_session_name' => ['required', 'string', 'max:80'],
            'send_delay_seconds' => ['required', 'integer', 'min:0', 'max:120'],
            'message_footer' => ['nullable', 'string', 'max:255'],
        ];
    }
}
