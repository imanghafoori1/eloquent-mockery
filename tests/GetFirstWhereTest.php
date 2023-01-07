<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class User extends Model
{
    protected $table = 'users';
}

class GetFirstWhereTest extends TestCase
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
    public function first()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $user = User::first();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertInstanceOf(User::class, $user);

        $user = User::query()->first();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals(1, User::query()->value('id'));
        $this->assertEquals('Iman 1', User::query()->value('name'));
        $this->assertEquals(null, User::query()->value('sdfvsdb'));

        $user = User::query()->first(['id']);
        $attrs = $user->getAttributes();
        $this->assertEquals(['id' => 1], $attrs);
        $this->assertInstanceOf(User::class, $user);

        $user = User::query()->latest('id')->first(['id']);
        $attrs = $user->getAttributes();
        $this->assertEquals(['id' => 3], $attrs);
        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * @test
     */
    public function where()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = User::where('id', '<', 2)->get();
        $this->assertEquals('Iman 1', ($users[0])->name);
        $this->assertEquals(true, ($users[0])->exists);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(1, $users->count());

        $user = User::where('id', 2)->first();
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(User::class, $user);

        // Previous wheres are not applied here in this query.
        $user = User::first();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(User::class, $user);

        // ################  where / get  ################
        $users = User::where('id', '<', 2)->get();
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(1, $users->count());
    }
}
