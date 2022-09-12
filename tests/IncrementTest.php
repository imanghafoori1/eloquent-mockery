<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class IncrementUser extends Model
{
    use MockableModel;
}

class IncrementTest extends TestCase
{
    public function test_increment()
    {
        IncrementUser::addFakeRow([
            'id' => 1,
            'age' => 19,
        ]);
        IncrementUser::addFakeRow([
            'id' => 2,
            'age' => 19,
        ]);

        IncrementUser::query()->where('id', 1)->increment('age');
        $user = IncrementUser::query()->find(1);
        $this->assertEquals(20, $user->age);

        IncrementUser::query()->where('id', 1)->increment('age', 10);
        $user = IncrementUser::query()->find(1);
        $this->assertEquals(30, $user->age);

        $user = IncrementUser::query()->find(2);
        $this->assertEquals(19, $user->age);

        FakeDB::truncate();
    }

    public function test_decrement()
    {
        IncrementUser::addFakeRow([
            'id' => 1,
            'age' => 19,
        ]);
        IncrementUser::addFakeRow([
            'id' => 2,
            'age' => 19,
        ]);

        IncrementUser::query()->where('id', 1)->decrement('age');
        $user = IncrementUser::query()->find(1);
        $this->assertEquals(18, $user->age);

        IncrementUser::query()->where('id', 1)->decrement('age', 10);
        $user = IncrementUser::query()->find(1);
        $this->assertEquals(8, $user->age);

        $user = IncrementUser::query()->find(2);
        $this->assertEquals(19, $user->age);

        FakeDB::truncate();
    }

}
