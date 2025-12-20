<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionPost extends Base
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'discussion_id',
        'status',
        'content',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
    ];

    /**
     * Get the discussion that owns the post.
     *
     * @return BelongsTo
     */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }
}
