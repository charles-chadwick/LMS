<?php

namespace App\Http\Controllers;

use App\Actions\Groups\CreateGroup;
use App\Actions\Groups\DeleteGroup;
use App\Actions\Groups\ForceDeleteGroup;
use App\Actions\Groups\ListGroups;
use App\Actions\Groups\LoadGroupDetails;
use App\Actions\Groups\RestoreGroup;
use App\Actions\Groups\UpdateGroup;
use App\Enums\GroupType;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    /**
     * Display a listing of the groups.
     */
    public function index(Request $request, ListGroups $listGroups): Response
    {
        return Inertia::render('Groups/Index', [
            'groups' => $listGroups->execute($request),
            'filters' => $request->only([
                'search',
                'type',
                'sortBy',
                'sortDirection',
            ]),
            'type_options' => GroupType::options(),
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create(): Response
    {
        $this->authorize('create', Group::class);

        return Inertia::render('Groups/Form', [
            'type_options' => GroupType::options(),
        ]);
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(StoreGroupRequest $request, CreateGroup $createGroup): RedirectResponse
    {
        $this->authorize('create', Group::class);

        $group = $createGroup->execute($request->validated());

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Group created successfully.');
    }

    /**
     * Display the specified group.
     */
    public function show(Request $request, Group $group, LoadGroupDetails $loadGroupDetails): Response
    {
        $this->authorize('view', $group);

        $loadGroupDetails->execute($group);

        return Inertia::render('Groups/Show', [
            'group' => $group,
            'can' => [
                'update' => $request->user()->can('update', $group),
                'delete' => $request->user()->can('delete', $group),
                'manage_members' => $request->user()->can('manageMembers', $group),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified group.
     */
    public function edit(Group $group): Response
    {
        $this->authorize('update', $group);

        return Inertia::render('Groups/Form', [
            'group' => $group,
            'type_options' => GroupType::options(),
        ]);
    }

    /**
     * Update the specified group in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group, UpdateGroup $updateGroup): RedirectResponse
    {
        $this->authorize('update', $group);

        $updateGroup->execute($group, $request->validated());

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Group updated successfully.');
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Group $group, DeleteGroup $deleteGroup): RedirectResponse
    {
        $this->authorize('delete', $group);

        $name = $deleteGroup->execute($group);

        return redirect()
            ->route('groups.index')
            ->with('success', "Group '{$name}' deleted successfully.");
    }

    /**
     * Restore the specified group from soft deletion.
     */
    public function restore(int $id, RestoreGroup $restoreGroup): RedirectResponse
    {
        $this->authorize('restore', Group::class);

        $group = $restoreGroup->execute($id);

        return redirect()
            ->route('groups.show', $group)
            ->with('success', 'Group restored successfully.');
    }

    /**
     * Permanently delete the specified group.
     */
    public function forceDestroy(int $id, ForceDeleteGroup $forceDeleteGroup): RedirectResponse
    {
        $this->authorize('forceDelete', Group::class);

        $name = $forceDeleteGroup->execute($id);

        return redirect()
            ->route('groups.index')
            ->with('success', "Group '{$name}' permanently deleted.");
    }
}
