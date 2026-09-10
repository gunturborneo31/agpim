<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWaBlastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin', 'admin_prokopim') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
            'backend' => ['required', Rule::in(['auto', 'cloud', 'web'])],
            'target' => ['required', Rule::in(['all_users', 'role'])],
            'target_role' => ['nullable', 'required_if:target,role', Rule::in(['opd', 'bupati', 'wakil_bupati', 'sekda', 'admin_prokopim'])],
        ];
    }
}
