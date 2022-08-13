<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class PluckUser extends Model
{
    use MockableModel;
}

class PluckTest extends TestCase
{
    /**
     * @test
     */
    public function pluck_test()
    {
        PluckUser::addFakeRow(['id' => 1, 'name' => null, 'age' => 20,]);
        PluckUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        PluckUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => null,]);

        $this->assertEquals([1, 2, 3], PluckUser::pluck('id')->all());
        $this->assertEquals([3, 2, 1], PluckUser::orderBy('id', 'desc')->pluck('id')->all());
        $this->assertEquals([20, 30, null], PluckUser::pluck('age')->all());
        $this->assertEquals([1, 2, 3], PluckUser::query()->pluck('id')->all());
        $this->assertEquals([1], PluckUser::query()->where('id', 1)->pluck('id')->all());
    }
}
