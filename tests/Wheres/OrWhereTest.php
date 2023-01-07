<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class OrWhereUser extends Model
{
    protected $table = 'users';
}

class OrWhereTest extends TestCase
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
    public function orWhere()
    {
        FakeDB::mockQueryBuilder();

        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'name0',
            'email' => 'email0',
            'address' => 'address-0',
        ]);

        FakeDB::addRow('users', [
            'id' => 2,
            'name' => 'name1',
            'email' => 'email1',
            'address' => 'address-1',
        ]);

        FakeDB::addRow('users', [
            'id' => 3,
            'name' => 'name2',
            'email' => 'email2',
            'address' => 'address-2',
        ]);

        FakeDB::addRow('users', [
            'id' => 4,
            'name' => 'name4',
            'email' => 'email4',
            'address' => 'address4',
        ]);

        $users = OrWhereUser::query()
            ->where('id',1)
            ->orWhere('address', 'address-2')->get();

        $this->assertEquals(2, $users->count());
        $this->assertEquals(1, $users->first()->id);
        $this->assertEquals(3, $users->last()->id);

        $users = OrWhereUser::query()
            ->where('id',1)
            ->orWhere('email' , 'email1')
            ->orWhere('address', 'address-2')
            ->get();

        $this->assertEquals(3, $users->count());
        $this->assertEquals(1, $users->first()->id);
        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals(3, $users->last()->id);
    }
}
