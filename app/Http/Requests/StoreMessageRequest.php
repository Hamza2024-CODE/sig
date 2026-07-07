<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreMessageRequest — Validates internal messaging payloads.
 *
 * Used for: EmployeeMessage creation via the internal messaging module.
 * Prevents message flooding, XSS injection, and invalid recipients.
 */
class StoreMessageRequest extends FormRequest
{
    /**
     * Any authenticated user may send a message.
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
            // Recipient — must be a positive integer employee ID
            'recipient_id'   => ['required', 'integer', 'min:1'],
            // Subject line — concise, required
            'subject'        => ['required', 'string', 'min:3', 'max:200'],
            // Message body — substantial, stripped of dangerous HTML
            'body'           => ['required', 'string', 'min:5', 'max:5000'],
            // Optional thread ID for replies
            'thread_id'      => ['nullable', 'integer', 'min:1'],
            // Optional file attachment
            'attachment'     => ['nullable', 'file', 'max:5120', 'mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls'],
            // Priority indicator
            'priority'       => ['nullable', 'string', 'in:normal,urgent,confidential'],
            // Message type
            'message_type'   => ['nullable', 'string', 'in:general,administrative,pedagogical,hr'],
        ];
    }

    /**
     * Custom human-readable Arabic validation messages.
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => 'يجب تحديد المستلم.',
            'recipient_id.integer'  => 'معرّف المستلم غير صالح.',
            'subject.required'      => 'عنوان الرسالة مطلوب.',
            'subject.min'           => 'عنوان الرسالة قصير جداً (3 أحرف على الأقل).',
            'subject.max'           => 'عنوان الرسالة لا يمكن أن يتجاوز 200 حرف.',
            'body.required'         => 'محتوى الرسالة مطلوب.',
            'body.min'              => 'محتوى الرسالة قصير جداً.',
            'body.max'              => 'محتوى الرسالة لا يمكن أن يتجاوز 5000 حرف.',
            'attachment.max'        => 'حجم المرفق يتجاوز 5 ميغابايت.',
            'attachment.mimes'      => 'نوع الملف المرفق غير مدعوم.',
            'priority.in'           => 'مستوى الأولوية المحدد غير صالح.',
            'message_type.in'       => 'نوع الرسالة غير صالح.',
        ];
    }

    /**
     * Sanitize all text inputs before validation to strip dangerous HTML.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject'      => strip_tags(trim($this->input('subject', ''))),
            'body'         => strip_tags(trim($this->input('body', '')), '<b><i><u><br><p><ul><li><ol>'),
            'priority'     => strtolower(trim($this->input('priority', 'normal'))),
            'message_type' => strtolower(trim($this->input('message_type', 'general'))),
        ]);
    }
}
