<?php

namespace App\Models;

use Illuminate\Support\Str;

trait AddUUIDTrait
{
    /**
     * Boot the trait.
     */
    protected static function bootAddUUIDTrait(): void
    {
        static::creating(static function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
