<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class AggregateUser extends Model
{
    use MockableModel;
}

class AggregateTest extends TestCase
{
    public function tearDown(): void
    {
        AggregateUser::stopFaking();
    }

    /**
     * @test
     */
    public function basic_exists()
    {
        AggregateUser::addFakeRow(['id' => 1, 'name' => null, 'age' => 20,]);
        AggregateUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        AggregateUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
        AggregateUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);

        $this->assertEquals(90, AggregateUser::query()->sum('age'));
        $this->assertEquals(20, AggregateUser::query()->min('age'));
        $this->assertEquals(40, AggregateUser::query()->max('age'));
        $this->assertEquals(30, AggregateUser::query()->avg('age'));
        $this->assertEquals(30, AggregateUser::query()->average('age'));
    }
}
