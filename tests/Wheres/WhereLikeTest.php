<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class LikeUser extends Model
{
    protected $table = 'users';
}

class WhereLikeTest extends TestCase
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
    public function whereLike()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = LikeUser::where('name', 'like', '%man 3')->get();
        $this->assertEquals('Iman 3', ($users[0])->name);
        $this->assertEquals(1, ($users->count()));

        $users = LikeUser::where('name', 'like', 'Iman%')->get();
        $this->assertEquals('Iman 2', ($users[0])->name);
        $this->assertEquals('Iman 3', ($users[1])->name);
    }
}
