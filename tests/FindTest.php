<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class FindUser extends Model
{
    use MockableModel;
}

class FindTest extends TestCase
{
    public function tearDown(): void
    {
        FindUser::stopFaking();
    }

    /**
     * @test
     */
    public function find()
    {
        FindUser::addFakeRow([
            'id' => 1,
            'name' => 'Iman',
        ]);
        FindUser::addFakeRow([
            'id' => 2,
            'name' => 'Iman 2',
        ]);

        $user = FindUser::find(1);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman', $user->name);
        $this->assertTrue($user->exists);

        $user = FindUser::query()->find(2);
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);

        $users = FindUser::query()->find([1, 2]);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);

        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals('Iman 2', $users[1]->name);
        $this->assertEquals(2, $users->count());

        $user = FindUser::query()->find(10);
        $this->assertNull($user);

        FindUser::stopFaking();
    }

    /**
     * @test
     */
    public function findOrFail()
    {
        FindUser::addFakeRow([
            'id' => 1,
            'name' => 'Iman',
        ]);
        FindUser::addFakeRow([
            'id' => 2,
            'name' => 'Iman 2',
        ]);

        $user = FindUser::findOrFail(1);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman', $user->name);

        $user = FindUser::query()->findOrFail(2);
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);

        $users = FindUser::query()->findOrFail([1, 2]);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);
        $this->assertTrue($users[0]->exists);

        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals('Iman 2', $users[1]->name);
        $this->assertEquals(2, $users->count());

        $this->expectException(ModelNotFoundException::class);
        $user = FindUser::query()->findOrFail(10);

        $this->assertNull($user);

        FindUser::stopFaking();
    }

    /**
     * @test
     */
    public function findMany()
    {
        FindUser::addFakeRow([
            'id' => 1,
            'name' => 'Iman',
        ]);

        FindUser::addFakeRow([
            'id' => 2,
            'name' => 'Iman 2',
        ]);

        FindUser::addFakeRow([
            'id' => 3,
            'name' => 'Iman 3',
        ]);

        $users = FindUser::findMany(1);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);
        $this->assertEquals(1, $users->count());

        $users = FindUser::query()->findMany(2);
        $this->assertEquals(2, $users[0]->id);
        $this->assertEquals('Iman 2', $users[0]->name);
        $this->assertEquals(1, $users->count());

        $users = FindUser::query()->find([1, 2]);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);
        $this->assertTrue($users[0]->exists);

        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals('Iman 2', $users[1]->name);
        $this->assertTrue($users[1]->exists);

        $this->assertEquals(2, $users->count());

        FindUser::stopFaking();
    }
}
