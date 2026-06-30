<?php

namespace App\Actions\Groups;

use App\Enums\UserRole;
use App\Models\Group;
use App\Traits\HasSearchFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ListGroups
{
    use AsAction;
    use HasSearchFilter;

    /**
     * Build the filtered, paginated group listing for the index page.
     */
    public function execute(Request $request): LengthAwarePaginator
    {
        $user = $request->user();

        $query = Group::query()
            ->visibleTo($user)
            ->select([
                'id',
                'type',
                'name',
                'description',
                'created_at',
            ])
            ->withCount('users');

        $query = $this->applyCommonFilters($query, $request, [
            'name',
            'description',
        ], [
            'status_field' => 'type',
            'status_param' => 'type',
        ]);

        $is_admin = $user->hasRole(UserRole::Admin);
        $led_group_ids = $user->hasRole(UserRole::Instructor)
            ? DB::table('group_users')
                ->where('user_id', $user->id)
                ->where('is_leader', true)
                ->whereNull('deleted_at')
                ->pluck('group_id')
            : collect();

        return $query->paginate($request->input('perPage', 15))
            ->withQueryString()
            ->through(function (Group $group) use ($is_admin, $led_group_ids): Group {
                $group->can_update = $is_admin || $led_group_ids->contains($group->id);
                $group->can_delete = $is_admin;

                return $group;
            });
    }
}
