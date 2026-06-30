<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'can' => [
                    'create_courses' => $request->user()?->can('create', Course::class) ?? false,
                    'create_users' => $request->user()?->can('create', User::class) ?? false,
                    'view_users' => $request->user()?->can('viewAny', User::class) ?? false,
                    'create_groups' => $request->user()?->can('create', Group::class) ?? false,
                    'view_groups' => $request->user()?->can('viewAny', Group::class) ?? false,
                    'manage_groups' => $request->user()?->canManageGroups() ?? false,
                ],
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
