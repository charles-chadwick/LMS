<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Course extends Base
{
    use HasFactory;

    /**
     * The attributes that are mass-assignable.
     *
     * @var array<int, string>
     */
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => CourseStatus::class,
    ];

    protected $fillable = [
        'status',
        'title',
        'code',
        'description',
    ];

    /**
     * Get the pages for the course.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('order');
    }

    /**
     * Get the users enrolled in the course.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'courses_users')
            ->withPivot('is_instructor', 'completed_at')
            ->withTimestamps();
    }

    /**
     * Get the instructors for the course.
     */
    public function instructors(): BelongsToMany
    {
        return $this->users()->wherePivot('is_instructor', true);
    }

    /**
     * Get the students enrolled in the course.
     */
    public function students(): BelongsToMany
    {
        return $this->users()->wherePivot('is_instructor', false);
    }

    /**
     * Get the user progress records for the course.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * Get all discussions for the course.
     */
    public function discussions(): MorphMany
    {
        return $this->morphMany(Discussion::class, 'on', 'on_type', 'on');
    }

    /**
     * Scope the query to courses visible to the given user.
     *
     * Admins see every course. Any other user sees only the courses they are
     * assigned to — a non-deleted `courses_users` row, whether as student or
     * instructor.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasRole(UserRole::Admin)) {
            return;
        }

        $query->whereExists(function (\Illuminate\Database\Query\Builder $subquery) use ($user): void {
            $subquery->from('courses_users')
                ->whereColumn('courses_users.course_id', 'courses.id')
                ->where('courses_users.user_id', $user->id)
                ->whereNull('courses_users.deleted_at');
        });
    }

    /**
     * Determine whether the user may manage the course — an admin, or an
     * instructor assigned to teach it.
     */
    public function isManagedBy(User $user): bool
    {
        return $user->hasRole(UserRole::Admin)
            || ($user->hasRole(UserRole::Instructor) && $this->instructors()->whereKey($user->id)->exists());
    }

    /**
     * Determine whether the user takes part in the course — a manager or an
     * enrolled student.
     */
    public function hasParticipant(User $user): bool
    {
        return $this->isManagedBy($user)
            || $this->students()->whereKey($user->id)->exists();
    }
}
