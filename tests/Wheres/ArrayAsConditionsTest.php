<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class WhereArrayUser extends Model
{
    protected $table = 'users';
}

class ArrayAsConditionsTest extends TestCase
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
    public function whereArray()
    {
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'test-name',
            'email' => 'test-email1',
            'address' => 'test-address0',
        ]);

        FakeDB::addRow('users', [
            'id' => 2,
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        FakeDB::addRow('users', [
            'id' => 3,
            'name' => 'test-name1',
            'email' => 'test-email',
            'address' => 'test-address2',
        ]);

        $user = WhereArrayUser::where(['id' => 1])->first();
        $this->assertEquals($user->getKey(), 1);

        $user = WhereArrayUser::where(['id' => 2])->first();
        $this->assertEquals($user->getKey(), 2);

        $user = WhereArrayUser::where([
            'name' => 'test-name1',
            'email' => 'test-email1',
        ])->first();

        $this->assertEquals($user->getKey(), 2);
    }
}
