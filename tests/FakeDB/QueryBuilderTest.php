<?php

namespace Imanghafoori\EloquentMockery\Tests\FakeDB;

use Illuminate\Support\Collection;
use Imanghafoori\EloquentMockery\FakeConnection;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    public function tearDown(): void
    {
        FakeDB::truncate();
    }

    public function testQueryBuilder()
    {
        FakeDB::table('users')->addRow(['id' => 1, 'username' => 'Iman']);
        FakeDB::table('users')->addRow(['id' => 2, 'username' => 'Ghafoori']);

        $users = (new FakeConnection())
            ->table('users')
            ->select('id')
            ->where('id', 2)
            ->get();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals([['id' => 2]], $users->all());

        $users = (new FakeConnection())
            ->table('users')
            ->where('id', 2)
            ->get('id');

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals([['id' => 2]], $users->all());

        $count = (new FakeConnection())->table('users')->count();
        $this->assertEquals(2, $count);
        $this->assertEquals(2, (new FakeConnection())->table('users')->max('id'));
        $this->assertEquals(1, (new FakeConnection())->table('users')->min('id'));
        $this->assertEquals(1.5, (new FakeConnection())->table('users')->avg('id'));

        $count = (new FakeConnection())->table('sdfvsd')->count();
        $this->assertEquals(0, $count);

        $count = (new FakeConnection())->table('sdfvsd')->get();
        $this->assertEquals(true, $count->isEmpty());
    }
}