<?php

namespace App\Models;

use App\Enums\GroupType;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Base
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => GroupType::class,
    ];

    /**
     * Get the users that belong to the group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_users')
            ->withPivot('is_leader')
            ->withTimestamps();
    }

    /**
     * Get the users that lead the group.
     */
    public function leaders(): BelongsToMany
    {
        return $this->users()->wherePivot('is_leader', true);
    }

    /**
     * Scope the query to groups visible to the given user.
     *
     * Admins see every group. Any other user sees only the groups they belong
     * to — a non-deleted `group_users` row, regardless of leadership status.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasRole(UserRole::Admin)) {
            return;
        }

        $query->whereExists(function (\Illuminate\Database\Query\Builder $subquery) use ($user): void {
            $subquery->from('group_users')
                ->whereColumn('group_users.group_id', 'groups.id')
                ->where('group_users.user_id', $user->id)
                ->whereNull('group_users.deleted_at');
        });
    }
}
