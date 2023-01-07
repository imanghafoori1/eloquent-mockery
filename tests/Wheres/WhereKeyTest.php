<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class KeyUser extends Model
{
    protected $table = 'users';
}

class WhereKeyTest extends TestCase
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
    public function whereKey()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = KeyUser::query()->whereKey(1)->get();
        $this->assertEquals('Hello', ($users[0])->name);
        $this->assertEquals(1, ($users->count()));

        $users = KeyUser::query()->whereKey(KeyUser::find(1))->get();
        $this->assertEquals('Hello', ($users[0])->name);
        $this->assertEquals(1, ($users->count()));

        $users = KeyUser::query()->whereKey([1, 2])->get();
        $this->assertEquals(2, ($users->count()));
    }

    /**
     * @test
     */
    public function whereKeyNot()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = KeyUser::query()->whereKeyNot(1)->get();
        $this->assertEquals('Iman 2', ($users[0])->name);
        $this->assertEquals('Iman 3', ($users[1])->name);
        $this->assertEquals(2, $users->count());

        $users = KeyUser::query()->whereKeyNot([1, 2])->get();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(1, $users->count());
        $this->assertEquals(3, $users[0]->id);
    }
}
