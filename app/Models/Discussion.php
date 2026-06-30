<?php

namespace App\Models;

use App\Enums\DiscussionStatus;
use App\Enums\DiscussionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Discussion extends Base
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'on',
        'on_type',
        'type',
        'title',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'on' => 'integer',
        'type' => DiscussionType::class,
        'status' => DiscussionStatus::class,
    ];

    /**
     * Get the parent discussable model.
     */
    public function discussable(): MorphTo
    {
        return $this->morphTo('on', 'on_type', 'on');
    }

    /**
     * Get the posts for the discussion.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class)->orderBy('created_at');
    }

    /**
     * Determine whether the discussion is open to new posts.
     */
    public function isOpen(): bool
    {
        return $this->status === DiscussionStatus::Open;
    }
}
