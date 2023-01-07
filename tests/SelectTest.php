<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class SelectyUser extends Model
{
    protected $table = 'users';
}

class SelectTest extends TestCase
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
    public function test_get()
    {
        FakeDB::addRow('users',['id' => 1, 'name' => null, 'age' => 20,]);
        FakeDB::addRow('users',['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users',['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
        FakeDB::addRow('users',['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);

        $users = SelectyUser::whereNull('name')->get(['age']);
        $this->assertEquals(null, ($users[0])->id);
        $this->assertEquals(20, ($users[0])->age);
        $this->assertEquals(1, $users->count());

        $users = SelectyUser::whereNull('name')->select('age')->first();
        $this->assertEquals(null, $users->id);
        $this->assertEquals(20, $users->age);

        $users = SelectyUser::whereNull('name')->select('age as mag')->first();
        $this->assertEquals(null, $users->id);
        $this->assertEquals(20, $users->mag);
        $this->assertEquals(null, $users->age);

        $users = SelectyUser::whereNull('name')->select(['age as mag', 'id as uid'])->first();
        $this->assertEquals(null, $users->id);
        $this->assertEquals(1, $users->uid);
        $this->assertEquals(20, $users->mag);
        $this->assertEquals(null, $users->age);

        $users = SelectyUser::whereNull('name')->first('age');
        $this->assertEquals(null, $users->id);
        $this->assertEquals(20, $users->age);

        $users = SelectyUser::whereNull('name')->first('age as mage');
        $this->assertEquals(null, $users->id);
        $this->assertEquals(null, $users->age);
        $this->assertEquals(20, $users->mage);

        $users = SelectyUser::whereNull('name')->select('age')->addSelect('id')->first();
        $this->assertEquals(1, $users->id);
        $this->assertEquals(20, $users->age);

        $users = SelectyUser::query()->whereNull('name')->select('age')->first('id');
        $this->assertEquals(null, $users->id);
        $this->assertEquals(20, $users->age);
    }
}
