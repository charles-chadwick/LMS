<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\ForceDeleteUser;
use App\Actions\Users\ListUsers;
use App\Actions\Users\RestoreUser;
use App\Actions\Users\UpdateUser;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request, ListUsers $listUsers): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Users/Index', [
            'users' => $listUsers->execute($request),
            'filters' => $request->only([
                'search',
                'role',
                'sortBy',
                'sortDirection',
            ]),
            'role_options' => UserRole::options(),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Form', [
            'role_options' => UserRole::options(),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = $createUser->execute($request->validated());

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user): Response
    {
        $this->authorize('view', $user);

        $user->loadCount('courses');

        return Inertia::render('Users/Show', [
            'user' => $user,
            'can' => [
                'update' => $request->user()->can('update', $user),
                'delete' => $request->user()->can('delete', $user),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Form', [
            'user' => $user,
            'role_options' => UserRole::options(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $this->authorize('update', $user);

        $updateUser->execute($user, $request->validated());

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user, DeleteUser $deleteUser): RedirectResponse
    {
        $this->authorize('delete', $user);

        $full_name = $deleteUser->execute($user);

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$full_name}' deleted successfully.");
    }

    /**
     * Restore the specified user from soft deletion.
     */
    public function restore(int $id, RestoreUser $restoreUser): RedirectResponse
    {
        $this->authorize('restore', User::class);

        $user = $restoreUser->execute($id);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User restored successfully.');
    }

    /**
     * Permanently delete the specified user.
     */
    public function forceDestroy(int $id, ForceDeleteUser $forceDeleteUser): RedirectResponse
    {
        $this->authorize('forceDelete', User::class);

        $full_name = $forceDeleteUser->execute($id);

        return redirect()
            ->route('users.index')
            ->with('success', "User '{$full_name}' permanently deleted.");
    }
}
