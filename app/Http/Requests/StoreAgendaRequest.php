<?php

namespace App\Http\Requests;

use App\Models\Agenda;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Agenda::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'agenda_type_id' => ['required', 'exists:agenda_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('start_time') && strcmp((string) $value, (string) $this->input('start_time')) <= 0) {
                        $fail('Jam selesai harus lebih besar dari jam mulai.');
                    }
                },
            ],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::in(array_keys(config('agpim.priorities')))],
            'leader_target' => ['nullable', Rule::in(array_keys(config('agpim.dispositions')))],
            'person_in_charge' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:30'],
            'invitation_letter_path' => ['required', 'string', 'max:255'],
            'speech_draft_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
