<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Traits\HasSearchFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
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
        $query = Group::query()
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

        return $query->paginate($request->input('perPage', 15))
            ->withQueryString()
            ->through(function (Group $group) use ($request): Group {
                $current_user = $request->user();

                $group->can_update = $current_user->can('update', $group);
                $group->can_delete = $current_user->can('delete', $group);

                return $group;
            });
    }
}
