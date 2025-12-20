<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Course extends Base
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'title',
        'code'
    ];

    /**
     * Get the pages for the course.
     *
     * @return HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('order');
    }

    /**
     * Get the users enrolled in the course.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'courses_users')
            ->withPivot('is_instructor')
            ->withTimestamps();
    }

    /**
     * Get the instructors for the course.
     *
     * @return BelongsToMany
     */
    public function instructors(): BelongsToMany
    {
        return $this->users()->wherePivot('is_instructor', true);
    }

    /**
     * Get the students enrolled in the course.
     *
     * @return BelongsToMany
     */
    public function students(): BelongsToMany
    {
        return $this->users()->wherePivot('is_instructor', false);
    }

    /**
     * Get the user progress records for the course.
     *
     * @return HasMany
     */
    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * Get all discussions for the course.
     *
     * @return MorphMany
     */
    public function discussions(): MorphMany
    {
        return $this->morphMany(Discussion::class, 'on', 'on_type', 'on');
    }
}
