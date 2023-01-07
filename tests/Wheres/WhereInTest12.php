<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class WhereInUser extends Model
{
    protected $table = 'users';
}

class WhereInTest extends TestCase
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
    public function where_in()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        // ################ where In / first ################
        $user = WhereInUser::whereIn('id', [2])->first();
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertInstanceOf(WhereInUser::class, $user);

        // ################  where In / get  ################
        $users = WhereInUser::whereIn('id', [1, 2])->get();
        $this->assertInstanceOf(Collection::class, $users);

        $user = $users[0];
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertEquals(20, $user->age);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(WhereInUser::class, $user);

        $user = $users[1];
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertEquals(30, $user->age);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(WhereInUser::class, $user);
        $this->assertEquals(2, $users->count());
    }

    /**
     * @test
     */
    public function where_not_in()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        // ################ where not In / first ################
        $user = WhereInUser::whereNotIn('id', [1, 3])->first();
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertInstanceOf(WhereInUser::class, $user);

        // ################  where not In / get  ################
        $users = WhereInUser::whereNotIn('id', [3])->get();
        $this->assertInstanceOf(Collection::class, $users);

        $user = $users[0];
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertEquals(20, $user->age);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(WhereInUser::class, $user);

        $user = $users[1];
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertEquals(30, $user->age);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(WhereInUser::class, $user);
        $this->assertEquals(2, $users->count());
    }

    /**
     * @test
     */
    public function where_in_can_accept_arrayable()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        // ################ where In / first ################
        $count = WhereInUser::whereIn('id', collect([1, 2]))->count();
        $this->assertEquals(2, $count);

        $users = WhereInUser::whereIn('id', collect([1, 2]))->get();

        $user = $users[0];
        $this->assertEquals(1, $user->id);

        $user = $users[1];
        $this->assertEquals(2, $user->id);

        $this->assertCount(2, $users);
    }
}
