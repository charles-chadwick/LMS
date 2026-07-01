<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Course extends Base implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'cover',
    ];

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
     * Register the course's media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile();
    }

    /**
     * Register the conversions generated for the course's cover image.
     *
     * The original upload is kept full sized while a 16:9 thumbnail
     * conversion is generated synchronously so it is available immediately.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 800, 450)
            ->nonQueued();
    }

    /**
     * Get the course's cover image URLs, or null when no cover has been uploaded.
     *
     * @return array{thumb: string, full: string}|null
     */
    public function getCoverAttribute(): ?array
    {
        $thumb = $this->getFirstMediaUrl('cover', 'thumb');

        if ($thumb === '') {
            return null;
        }

        return [
            'thumb' => $thumb,
            'full' => $this->getFirstMediaUrl('cover'),
        ];
    }

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
     * Admins see every course. Instructors see the courses they teach at any
     * status. Students see only published courses they are enrolled in. In all
     * cases the `courses_users` row must not be soft-deleted.
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
                ->whereNull('courses_users.deleted_at')
                ->where(function (\Illuminate\Database\Query\Builder $subquery): void {
                    // Instructors see their courses at any status; students only
                    // see courses that have been published.
                    $subquery->where('courses_users.is_instructor', true)
                        ->orWhere('courses.status', CourseStatus::Published->value);
                });
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
