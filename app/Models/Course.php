<?php

namespace App\Models;

use App\Enums\CourseStatus;
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
}
