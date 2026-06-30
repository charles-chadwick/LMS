<?php

namespace App\Http\Requests;

use App\Enums\DiscussionType;
use App\Models\Discussion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscussionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $type = DiscussionType::tryFrom($this->input('type', '')) ?? DiscussionType::General;

        return $this->user()->can('create', [Discussion::class, $this->route('course'), $type]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DiscussionType::class)],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
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
            'type' => 'discussion type',
            'title' => 'discussion title',
            'body' => 'opening post',
        ];
    }
}
