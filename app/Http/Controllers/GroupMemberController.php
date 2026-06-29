<?php

namespace App\Http\Controllers;

use App\Actions\Groups\AssignMember;
use App\Actions\Groups\ListAssignableUsers;
use App\Actions\Groups\RemoveMember;
use App\Actions\Groups\UpdateMember;
use App\Http\Requests\StoreGroupMemberRequest;
use App\Http\Requests\UpdateGroupMemberRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupMemberController extends Controller
{
    /**
     * Search instructors and students who can still join the group (typeahead).
     */
    public function assignable(Request $request, Group $group, ListAssignableUsers $listAssignableUsers): JsonResponse
    {
        $this->authorize('manageMembers', $group);

        return response()->json(
            $listAssignableUsers->execute($group, $request->string('search')->toString() ?: null)
        );
    }

    /**
     * Add a member to the group.
     */
    public function store(StoreGroupMemberRequest $request, Group $group, AssignMember $assignMember): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::findOrFail($validated['user_id']);

        $assignMember->execute($group, $user, $validated['is_leader'] ?? false);

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Member added successfully.');
    }

    /**
     * Update a member's leadership status.
     */
    public function update(UpdateGroupMemberRequest $request, Group $group, User $user, UpdateMember $updateMember): RedirectResponse
    {
        $updateMember->execute($group, $user, $request->validated()['is_leader']);

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Member updated successfully.');
    }

    /**
     * Remove a member from the group.
     */
    public function destroy(Group $group, User $user, RemoveMember $removeMember): RedirectResponse
    {
        $this->authorize('manageMembers', $group);

        $removeMember->execute($group, $user);

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Member removed successfully.');
    }
}
