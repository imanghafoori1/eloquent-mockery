<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class AggregateUser extends Model
{
    use MockableModel;
}

class AggregateTest extends TestCase
{
    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();

        AggregateUser::addFakeRow(['id' => 1, 'name' => null, 'age' => 20,]);
        AggregateUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        AggregateUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
        AggregateUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);
    }

    public function tearDown(): void
    {
        FakeDB::truncate();
    }

    /**
     * @test_
     */
    public function sum()
    {
        //AggregateUser::query()->selectRaw('count(*) as r')->get();
        //$this->assertEquals(90, AggregateUser::query()->sum('age'));
        //$this->assertTrue(0 === AggregateUser::query()->where('id', 0)->sum('age'));
    }

    /**
     * @test
     */
    public function avg()
    {
        $this->assertEquals(30, AggregateUser::query()->avg('age'));
        $this->assertEquals(30, AggregateUser::query()->average('age'));
        $this->assertNull(AggregateUser::query()->where('id', 0)->avg('age'));
    }

    /**
     * @test
     */
    public function min()
    {
        $this->assertEquals(20, AggregateUser::query()->min('age'));
        $this->assertEquals(40, AggregateUser::query()->where('id', '>', 2)->min('age'));
        $this->assertNull(AggregateUser::query()->where('id', 0)->min('age'));
    }

    /**
     * @test
     */
    public function max()
    {
        $this->assertEquals(40, AggregateUser::query()->max('age'));
        $this->assertEquals(30, AggregateUser::query()->where('id', '<', 3)->max('age'));
        $this->assertNull(AggregateUser::query()->where('id', 0)->max('age'));
    }
}
