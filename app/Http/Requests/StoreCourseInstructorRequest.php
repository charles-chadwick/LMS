<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
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
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, callable $fail): void {
                    $user = User::find($value);

                    if ($user !== null && ! $user->hasAnyRole([UserRole::Admin, UserRole::Instructor])) {
                        $fail('Each selected user must be an instructor or admin.');
                    }
                },
            ],
        ];
    }
}
