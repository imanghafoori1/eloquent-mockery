<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class User extends Model
{
    use MockableModel;
}

class GetFirstWhereTest extends TestCase
{
    public function tearDown(): void
    {
        User::stopFaking();
    }

    /**
     * @test
     */
    public function first()
    {
        User::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        User::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        User::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

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

        User::stopFaking();
    }

    /**
     * @test
     */
    public function where__whereIn()
    {
        User::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        User::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        User::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

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

        // ################ where In / first ################
        $user = User::whereIn('id', [2])->first();
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertInstanceOf(User::class, $user);

        // ################  where In / get  ################
        $users = User::whereIn('id', [1, 2])->get();
        $this->assertInstanceOf(Collection::class, $users);

        $user = $users[0];
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman 1', $user->name);
        $this->assertEquals(20, $user->age);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(User::class, $user);

        $user = $users[1];
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);
        $this->assertEquals(30, $user->age);
        $this->assertEquals(true, $user->exists);
        $this->assertInstanceOf(User::class, $user);

        $this->assertEquals(2, $users->count());

        // ################  where In / get  ################
        $users = User::where('id', '<', 2)->get();
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(1, $users->count());

        User::stopFaking();
    }
}
