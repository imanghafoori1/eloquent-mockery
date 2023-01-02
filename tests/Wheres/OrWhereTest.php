<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class OrWhereUser extends Model
{
    use MockableModel;
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

        OrWhereUser::addFakeRow([
            'id' => 1,
            'name' => 'name0',
            'email' => 'email0',
            'address' => 'address-0',
        ]);

        OrWhereUser::addFakeRow([
            'id' => 2,
            'name' => 'name1',
            'email' => 'email1',
            'address' => 'address-1',
        ]);

        OrWhereUser::addFakeRow([
            'id' => 3,
            'name' => 'name2',
            'email' => 'email2',
            'address' => 'address-2',
        ]);

        OrWhereUser::addFakeRow([
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
