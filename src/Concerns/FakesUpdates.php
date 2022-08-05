<?php

namespace Imanghafoori\EloquentMockery\Concerns;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Assert as PHPUnit;

trait FakesUpdates
{
    public static $updatedModels = [];

    public static function assertModelIsUpdated($times = 1)
    {
        $actual = count(self::$updatedModels);

        PHPUnit::assertEquals($times, $actual, 'Model is not saved as expected.');
    }
}
