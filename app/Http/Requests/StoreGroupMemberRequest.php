<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->route('group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, mixed>|string>
     */
    public function rules(): array
    {
        $group = $this->route('group');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, callable $fail) use ($group): void {
                    $user = User::find($value);

                    if ($user === null) {
                        return;
                    }

                    if (! $user->hasRole($group->type->toUserRole())) {
                        $fail("The selected user must be a {$group->type->value}.");
                    } elseif ($group->users()->whereKey($value)->exists()) {
                        $fail('This user is already a member of the group.');
                    }
                },
            ],
            'is_leader' => ['sometimes', 'boolean'],
        ];
    }
}
