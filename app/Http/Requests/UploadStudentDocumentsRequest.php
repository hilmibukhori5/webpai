<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UploadStudentDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->user()?->student;

        return $student && $student->submissions()->where('status', 'approved')->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'formulir_terisi' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->hasFile('bukti_pembayaran') && ! $this->hasFile('formulir_terisi')) {
                $validator->errors()->add('bukti_pembayaran', 'Pilih minimal salah satu file untuk diupload.');
            }
        });
    }
}
