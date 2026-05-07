<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ForceStepRequest — التحقق من بيانات تغيير خطوة العميل
 */
class ForceStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Defense-in-depth: even though the 'admin' middleware protects the route,
        // re-check at the request layer so this request cannot be misused if the
        // middleware is ever omitted by accident on a future route.
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'step'            => ['required', 'integer', 'min:0', 'max:6'],
            'note'            => ['nullable', 'string', 'max:500'],
            'notify_customer' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'step.required' => 'رقم الخطوة مطلوب',
            'step.integer'  => 'رقم الخطوة يجب أن يكون رقم صحيح',
            'step.min'      => 'رقم الخطوة يجب أن يكون 0 على الأقل',
            'step.max'      => 'رقم الخطوة يجب أن لا يتجاوز 6',
            'note.max'      => 'الملاحظة يجب أن لا تتجاوز 500 حرف',
        ];
    }
}
