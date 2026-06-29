<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupUser extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'group_users';

    /**
     * The attributes that are mass-assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'is_leader',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_leader' => 'boolean',
    ];

    /**
     * Get the group.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
