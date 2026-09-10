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
        $dispositionTargets = array_keys(array_filter(
            config('agpim.dispositions'),
            static fn (string $key): bool => $key !== 'rejected',
            ARRAY_FILTER_USE_KEY,
        ));

        return [
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
            'priority' => ['required', Rule::in(array_keys(config('agpim.priorities')))],
            'direct_approve' => ['nullable', 'boolean'],
            'opd_id' => ['required_if:direct_approve,1', 'nullable', 'integer', 'exists:opds,id'],
            'leader_target' => ['required', Rule::in($dispositionTargets)],
            'person_in_charge' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:30'],
            'visual_content' => ['nullable', 'array'],
            'visual_content.*' => ['string', 'max:100'],
            'visual_other_note' => ['nullable', 'string', 'max:255'],
            'visual_status_ak' => ['nullable', 'string', 'in:Belum,menunggu,sedang dikerjakan,selesai'],
            'visual_status_prokopim' => ['nullable', 'string', 'in:belum ada bahan,sudah ada bahan dokumentasi,belum diperiksa,perlu revisi,ok tayang'],
            'video_content' => ['nullable', 'string', 'max:255'],
            'video_status_ak' => ['nullable', 'string', 'in:Belum,menunggu,sedang dikerjakan,selesai'],
            'video_status_prokopim' => ['nullable', 'string', 'in:belum ada bahan,sudah ada bahan dokumentasi,belum diperiksa,perlu revisi,ok tayang'],
            'invitation_letter_path' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'speech_draft_path' => ['nullable', 'file', 'mimes:pdf,docx', 'max:5120'],
            'speech_draft_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
