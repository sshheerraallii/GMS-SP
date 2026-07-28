<?php

namespace App\Models\Concerns;

use App\Support\Audit;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            Audit::log(
                'created',
                $model::class,
                (int) $model->getKey(),
                null,
                $model->toArray()
            );
        });

        static::updating(function ($model) {
            // old values = original attributes
            $old = $model->getOriginal();

            // new values = only changed fields (keeps logs small)
            $new = $model->getDirty();

            Audit::log(
                'updated',
                $model::class,
                (int) $model->getKey(),
                $old,
                $new
            );
        });

        static::deleted(function ($model) {
            Audit::log(
                'deleted',
                $model::class,
                (int) $model->getKey(),
                $model->toArray(),
                null
            );
        });
    }
}
