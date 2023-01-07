<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class IgnoreWheresUser extends Model
{
    protected $table = 'users';
}

class IgnoreWheresTest_ extends TestCase
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
    public function where__whereIn()
    {
FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        IgnoreWheresUser::ignoreWheres();

        $users = IgnoreWheresUser::where('id', '<', 2)->get();
        $this->assertEquals('Iman 1', ($users[0])->name);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(3, $users->count());

        $user = IgnoreWheresUser::where('id', 2)->first();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertInstanceOf(IgnoreWheresUser::class, $user);

        // Previous wheres are not applied here in this query.
        $user = IgnoreWheresUser::first();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertInstanceOf(IgnoreWheresUser::class, $user);

        // ################ where In / first ################
        $user = IgnoreWheresUser::whereIn('id', [2])->first();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertInstanceOf(IgnoreWheresUser::class, $user);

        // ################  where In / get  ################
        $users = IgnoreWheresUser::whereIn('id', [1, 2])->get();
        $this->assertInstanceOf(Collection::class, $users);

        $user = $users[0];
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertEquals(20, $user->age);
        $this->assertInstanceOf(IgnoreWheresUser::class, $user);

        $user = $users[1];
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertEquals(30, $user->age);
        $this->assertInstanceOf(IgnoreWheresUser::class, $user);

        $this->assertEquals(3, $users->count());

        // ################  where In / get  ################
        $users = IgnoreWheresUser::where('id', '<', 2)->get();
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(3, $users->count());
    }

    /**
     * @test
     */
    public function test_get()
    {
FakeDB::addRow('users', ['id' => 1, 'name' => null, 'age' => 20,]);
FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
FakeDB::addRow('users', ['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);

        IgnoreWheresUser::ignoreWheres();

        $users = IgnoreWheresUser::whereNull('name')->get(['age']);
        $this->assertEquals(null, ($users[0])->id);
        $this->assertEquals(20, ($users[0])->age);
        $this->assertEquals(4, $users->count());

        $users = IgnoreWheresUser::query()->whereNull('name')->where('id', 1)->get(['age']);
        $this->assertEquals(null, ($users[0])->id);
        $this->assertEquals(20, ($users[0])->age);
        $this->assertEquals(4, $users->count());
    }
}
