<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait IsPerson
{


    public function getFullNameAttribute() : string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullNameWithSalutationsAttribute() : string
    {
        return trim($this?->prefix.' '.$this->first_name.' '.$this->last_name.' '.$this?->suffix);
    }

    public function getInitialsAttribute() : string
    {
        return $this->first_name[0].$this->last_name[0];
    }

}