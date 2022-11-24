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

        $users = FakeConnection::resolve()
            ->table('users')
            ->select('id')
            ->where('id', 2)
            ->get();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals([['id' => 2]], $users->all());

        $users = FakeConnection::resolve()
            ->table('users')
            ->where('id', 2)
            ->get('id');

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals([['id' => 2]], $users->all());

        $count = FakeConnection::resolve()->table('users')->count();
        $this->assertEquals(2, $count);
        $this->assertEquals(2, FakeConnection::resolve()->table('users')->max('id'));
        $this->assertEquals(1, FakeConnection::resolve()->table('users')->min('id'));
        $this->assertEquals(1.5, FakeConnection::resolve()->table('users')->avg('id'));

        $count = FakeConnection::resolve()->table('sdfvsd')->count();
        $this->assertEquals(0, $count);

        $count = FakeConnection::resolve()->table('sdfvsd')->get();
        $this->assertEquals(true, $count->isEmpty());
    }
}
