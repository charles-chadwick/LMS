<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

abstract class Base extends Model
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime:m/d/Y h:i A',
        'updated_at' => 'datetime:m/d/Y h:i A',
        'deleted_at' => 'datetime:m/d/Y h:i A',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by_id = auth()->id();
                $model->updated_by_id = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by_id = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by_id = auth()->id();
                $model->save();
            }
        });

        // Activity logging
        static::created(function ($model) {
            if (auth()->check()) {
                activity()
                    ->performedOn($model)
                    ->causedBy(auth()->user())
                    ->log(class_basename($model).' created');
            }
        });

        static::updated(function ($model) {
            if (auth()->check()) {
                activity()
                    ->performedOn($model)
                    ->causedBy(auth()->user())
                    ->log(class_basename($model).' updated');
            }
        });

        static::deleted(function ($model) {
            if (auth()->check()) {
                activity()
                    ->performedOn($model)
                    ->causedBy(auth()->user())
                    ->log(class_basename($model).' deleted');
            }
        });

        static::restored(function ($model) {
            if (auth()->check()) {
                activity()
                    ->performedOn($model)
                    ->causedBy(auth()->user())
                    ->log(class_basename($model).' restored');
            }
        });

        static::forceDeleted(function ($model) {
            if (auth()->check()) {
                activity()
                    ->performedOn($model)
                    ->causedBy(auth()->user())
                    ->log(class_basename($model).' permanently deleted');
            }
        });
    }

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the fillable attributes.
     */
    public function getFillable(): array
    {
        return array_merge(
            $this->fillable,
            ['created_at', 'updated_at', 'deleted_at']
        );
    }

    /**
     * Get the user who created this record.
     */
    public function created_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updated_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Get the user who deleted this record.
     */
    public function deleteD_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }
}
