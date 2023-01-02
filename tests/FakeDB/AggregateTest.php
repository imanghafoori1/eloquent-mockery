<?php

namespace Imanghafoori\EloquentMockery\Tests\FakeDB;

use Imanghafoori\EloquentMockery\FakeConnection;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class AggregateTest extends TestCase
{
    public function tearDown(): void
    {
        FakeDB::truncate();
    }

    public function testQueryBuilder()
    {
        FakeDB::table('users')->addRow(['id' => 1, 'username' => 'Iman']);
        FakeDB::table('users')->addRow(['id' => 2, 'username' => 'Ghafoori']);

        $count = FakeConnection::resolve()->table('users')->count();
        $this->assertEquals(2, $count);

        $count = FakeConnection::resolve()->table('sdfvsd')->count();
        $this->assertEquals(0, $count);

        $count = FakeConnection::resolve()->table('sdfvsd')->get();
        $this->assertEquals(true, $count->isEmpty());
    }

    public function testAvg()
    {
        FakeDB::table('users')->addRow(['id' => 1, 'username' => 'Iman']);
        FakeDB::table('users')->addRow(['id' => 2, 'username' => 'Ghafoori']);
        FakeDB::table('users')->addRow(['id' => null, 'username' => 'Ghafoori']);

        $this->assertEquals(1.5, FakeConnection::resolve()->table('users')->avg('id'));
    }

    public function testMinMax()
    {
        FakeDB::table('users')->addRow(['id' => 1, 'username' => 'Iman']);
        FakeDB::table('users')->addRow(['id' => 2, 'username' => 'Ghafoori']);

        $this->assertEquals(2, FakeConnection::resolve()->table('users')->max('id'));
        $this->assertEquals(1, FakeConnection::resolve()->table('users')->min('id'));
    }
}