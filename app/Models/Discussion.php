<?php

namespace App\Models;

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
        'notes',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'on' => 'integer',
    ];

    /**
     * Get the parent discussable model.
     *
     * @return MorphTo
     */
    public function discussable(): MorphTo
    {
        return $this->morphTo('on', 'on_type', 'on');
    }

    /**
     * Get the posts for the discussion.
     *
     * @return HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class)->orderBy('created_at');
    }
}
