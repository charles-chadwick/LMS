<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Traits\HasSearchFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListUsers
{
    use HasSearchFilter;

    /**
     * Build the filtered, paginated user listing for the index page.
     */
    public function execute(Request $request): LengthAwarePaginator
    {
        $query = User::query()
            ->select([
                'id',
                'role',
                'first_name',
                'last_name',
                'email',
                'email_verified_at',
                'created_at',
            ])
            ->with('media')
            ->withCount('courses');

        $query = $this->applyCommonFilters($query, $request, [
            'first_name',
            'last_name',
            'email',
        ], [
            'status_field' => 'role',
            'status_param' => 'role',
        ]);

        return $query->paginate($request->input('perPage', 15))
            ->withQueryString()
            ->through(function (User $user) use ($request): User {
                $current_user = $request->user();

                $user->can_update = $current_user->can('update', $user);
                $user->can_delete = $current_user->can('delete', $user);

                return $user;
            });
    }
}
