<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                'unique:courses,code',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'cover' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'course status',
            'title' => 'course title',
            'code' => 'course code',
            'cover' => 'course cover image',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The :attribute must be either Draft, Published, or Archived.',
            'code.unique' => 'A course with this code already exists.',
        ];
    }
}
