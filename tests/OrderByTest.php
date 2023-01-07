<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class OrderUser extends Model
{
    protected $table = 'users';
}

class OrderByTest extends TestCase
{
    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();
    }

    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
    }

    /**
     * @test
     */
    public function orderBy()
    {
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = OrderUser::query()->orderBy('id')->get();
        $user = $users[0];
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Hello', $user->name);

        $users = OrderUser::query()->orderBy('id', 'desc')->get();
        $user = $users[0];
        $this->assertEquals(3, $user->id);
        $this->assertEquals('Iman 3', $user->name);
    }

    /**
     * @test
     */
    public function multiOrderBy()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'a', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'a', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'b', 'age' => 31,]);
        FakeDB::addRow('users', ['id' => 4, 'name' => 'b', 'age' => 21,]);

        $users = OrderUser::query()
            ->orderBy('name', 'desc')
            ->orderBy('age', 'asc')
            ->get();
        $user = $users[0];
        $this->assertEquals(4, $user->id);
        $this->assertEquals('b', $user->name);
        $user = $users[1];
        $this->assertEquals(3, $user->id);
        $this->assertEquals('b', $user->name);
        $user = $users[2];
        $this->assertEquals(2, $user->id);
        $this->assertEquals('a', $user->name);
        $user = $users[3];
        $this->assertEquals(1, $user->id);
        $this->assertEquals('a', $user->name);
    }

    /**
     * @test
     */
    public function reorder()
    {
        if (! method_exists(OrderUser::query()->getQuery(), 'reorder')) {
            $this->markTestSkipped('reorder does not exist in this laravel version.');
        }
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Hello', 'age' => 40,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'a Iman 3', 'age' => 34,]);

        $users = OrderUser::query()->orderBy('id')->reorder('age')->get();
        $user = $users[0];
        $this->assertEquals(2, $user->id);
    }

    /**
     * @test
     */
    public function latest_oldest()
    {
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'Hello',
            'age' => 20,
            'created_at' => '2022-08-14 16:59:29',
        ]);
        FakeDB::addRow('users', [
            'id' => 2,
            'name' => 'Iman 2',
            'age' => 30,
            'created_at' => '2022-08-22 16:59:29',
        ]);
        FakeDB::addRow('users', [
            'id' => 3,
            'name' => 'Iman 3',
            'age' => 34,
            'created_at' => '2020-01-10 16:59:29',
        ]);
        FakeDB::addRow('users', [
            'id' => 4,
            'name' => 'Iman 3',
            'age' => 34,
            'created_at' => '2022-08-10 16:59:29',
        ]);

        $user = OrderUser::query()->latest()->first();
        $this->assertEquals(2, $user->id);

        $user = OrderUser::query()->oldest()->first();
        $this->assertEquals(3, $user->id);

        $users1 = OrderUser::query()->inRandomOrder()->get();
        $users2 = OrderUser::query()->inRandomOrder()->get();
        $users3 = OrderUser::query()->inRandomOrder()->get();

        $this->assertTrue($users1[0]->id !== 1 || $users2[0]->id !== 1 || $users3[0]->id !== 1 || $users1[1]->id !== 2 || $users2[1]->id !== 2 || $users3[1]->id !== 2);
    }
}
