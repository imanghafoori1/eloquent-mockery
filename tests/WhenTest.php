<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class WhenUser extends Model
{
    use MockableModel;
}

class WhenTest extends TestCase
{
    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
    }

    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();
    }

    /**
     * @test
     */
    public function when_test()
    {
        WhenUser::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        WhenUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        WhenUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = WhenUser::query()->when(true, function ($q) {
            $q->where('id', '<', 2);
        })->get();
        $this->assertEquals('Iman 1', ($users[0])->name);
        $this->assertEquals(1, $users->count());

        $user = WhenUser::query()->when(true, function ($q) {
            return $q->where('id', 2);
        })->first();
        $this->assertEquals(2, $user->id);
        $this->assertEquals(1, $users->count());

        $count = WhenUser::query()->when(false, function ($q) {
            return $q->where('id', 2);
        })->count();
        $this->assertEquals(3, $count);
    }
}
