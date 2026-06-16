<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportCourseGradesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'semester' => ['required', 'string', 'max:50'], // mis. "Genap 2223"
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'], // maks 5MB
        ];
    }
}
