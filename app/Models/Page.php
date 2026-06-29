<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Base implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'order',
        'status',
        'title',
        'content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order' => 'integer',
        'status' => CourseStatus::class,
    ];

    /**
     * Scope a query to only published pages.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }

    /**
     * Get the course that owns the page.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user progress records for the page.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * Get all discussions for the page.
     */
    public function discussions(): MorphMany
    {
        return $this->morphMany(Discussion::class, 'on', 'on_type', 'on');
    }
}
