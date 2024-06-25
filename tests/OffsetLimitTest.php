<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class OffsetUser extends Model
{
    protected $table = 'users';
}

class OffsetLimitTest extends TestCase
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
    public function offset_limit()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1']);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2']);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3']);
        FakeDB::addRow('users', ['id' => 4, 'name' => 'Iman 4']);

        $users = OffsetUser::skip(1)->limit(2)->get();
        $this->assertEquals(2, $users->count());
        $this->assertEquals(2, $users->first()->id);
        $this->assertEquals(3, $users[1]->id);
        $this->assertEquals(OffsetUser::class, get_class($users[1]));

        $users = OffsetUser::query()->offset(1)->limit(2)->get();
        $this->assertEquals(2, $users->count());
        $this->assertEquals(2, $users->first()->id);
        $this->assertEquals(3, $users[1]->id);

        $users = OffsetUser::skip(1)->first();
        $this->assertEquals(2, $users->id);

        $users = OffsetUser::offset(2)->first();
        $this->assertEquals(3, $users->id);
    }
}
