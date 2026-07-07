<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreWorkflowRequest — Validates incoming workflow step creation payloads.
 *
 * Used for: leave requests, promotions, transfers, and any other
 * workflow events submitted through the platform's request system.
 */
class StoreWorkflowRequest extends FormRequest
{
    /**
     * Any authenticated user may submit a workflow request.
     */
    public function authorize(): bool
    {
        return session('authenticated', false) === true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            // Workflow type: must be one of the defined types
            'type'         => ['required', 'string', 'in:leave,promotion,transfer,training,complaint,other'],
            // Free-text reason (XSS stripped at display layer)
            'reason'       => ['required', 'string', 'min:10', 'max:1000'],
            // Target employee (optional — for HR-initiated flows)
            'employee_id'  => ['nullable', 'integer', 'min:1'],
            // Start date for leaves / transfers
            'date_start'   => ['nullable', 'date', 'after_or_equal:today'],
            // End date must be after start
            'date_end'     => ['nullable', 'date', 'after_or_equal:date_start'],
            // Optional supporting document
            'attachment'   => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            // Priority flag
            'priority'     => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ];
    }

    /**
     * Custom human-readable error messages (Arabic).
     */
    public function messages(): array
    {
        return [
            'type.required'        => 'نوع الطلب مطلوب.',
            'type.in'              => 'نوع الطلب غير صالح.',
            'reason.required'      => 'سبب الطلب مطلوب.',
            'reason.min'           => 'يجب أن يكون السبب 10 أحرف على الأقل.',
            'reason.max'           => 'السبب لا يمكن أن يتجاوز 1000 حرف.',
            'date_start.date'      => 'تاريخ البداية غير صالح.',
            'date_start.after_or_equal' => 'لا يمكن أن يكون تاريخ البداية في الماضي.',
            'date_end.after_or_equal'   => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.',
            'attachment.max'       => 'حجم الملف المرفق يتجاوز 10 ميغابايت.',
            'attachment.mimes'     => 'نوع الملف المرفق غير مسموح به.',
            'priority.in'          => 'مستوى الأولوية غير صالح.',
        ];
    }

    /**
     * Sanitize inputs before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type'     => strtolower(trim($this->input('type', ''))),
            'reason'   => strip_tags(trim($this->input('reason', ''))),
            'priority' => strtolower(trim($this->input('priority', 'normal'))),
        ]);
    }
}
