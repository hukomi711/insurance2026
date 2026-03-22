<?php

namespace App\Http\Requests;

use App\Models\FunnelEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFunnelEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_name'      => ['required', 'string', Rule::in(FunnelEvent::ALLOWED_EVENTS)],
            'step_name'       => ['nullable', 'string', 'max:40'],
            'previous_step'   => ['nullable', 'string', 'max:40'],
            'quote_uuid'      => ['nullable', 'uuid'],
            'device_type'     => ['nullable', 'string', Rule::in(['mobile', 'desktop'])],
            'source'          => ['nullable', 'string', 'max:60'],
            'campaign'        => ['nullable', 'string', 'max:100'],
            'elapsed_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'metadata'        => ['nullable', 'array'],
            'metadata.*'      => ['nullable'],
        ];
    }
}
