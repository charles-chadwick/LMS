<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseInstructorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageInstructors', $this->route('course'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, callable $fail) use ($course): void {
                    $user = User::find($value);

                    if ($user === null) {
                        return;
                    }

                    if (! $user->hasAnyRole(['Admin', 'Instructor'])) {
                        $fail('The selected user must be an instructor or admin.');
                    } elseif ($course->instructors()->whereKey($value)->exists()) {
                        $fail('This user is already an instructor of the course.');
                    }
                },
            ],
        ];
    }
}
