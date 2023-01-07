<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentMockery
{
    public static function fakeModels(array $models)
    {
        foreach ($models as $model) {
            if (is_string($model)) {
                (new $model)->fake();
            } else {
                throw new ModelNotFoundException;
            }
        }
    }
}