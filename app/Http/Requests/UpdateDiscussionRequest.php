<?php

namespace App\Http\Requests;

use App\Enums\DiscussionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscussionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('discussion'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::enum(DiscussionType::class),
                function (string $attribute, mixed $value, callable $fail): void {
                    $course = $this->route('discussion')->discussable;

                    if ($value === DiscussionType::Announcement->value && ! $course->isManagedBy($this->user())) {
                        $fail('Only course managers may mark a discussion as an announcement.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
