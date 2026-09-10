<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicStoreAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dispositionTargets = array_keys(array_filter(
            config('agpim.dispositions'),
            static fn (string $key): bool => $key !== 'rejected',
            ARRAY_FILTER_USE_KEY,
        ));

        return [
            'submitter_name' => ['required', 'string', 'max:255'],
            'submitter_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'opd_option' => ['required', 'string', 'max:255'],
            'opd_custom_name' => ['nullable', 'string', 'max:255', 'required_if:opd_option,lainnya'],
            'agenda_type_id' => ['required', 'exists:agenda_types,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'time_unknown' => ['nullable', 'boolean'],
            'start_time' => [
                Rule::requiredIf(fn () => ! $this->boolean('time_unknown')),
                'nullable',
                'date_format:H:i',
            ],
            'end_time' => [
                Rule::requiredIf(fn () => ! $this->boolean('time_unknown')),
                'nullable',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->boolean('time_unknown')) {
                        return;
                    }

                    if ($this->input('start_time') && strcmp((string) $value, (string) $this->input('start_time')) <= 0) {
                        $fail('Jam selesai harus lebih besar dari jam mulai.');
                    }
                },
            ],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', Rule::in(array_keys(config('agpim.priorities')))],
            'leader_target' => ['required', Rule::in($dispositionTargets)],
            'person_in_charge' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:30'],
            'invitation_letter_path' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'speech_draft_path' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'speech_draft_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
