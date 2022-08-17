<?php

namespace Imanghafoori\EloquentMockery\Tests\FakeDB;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class FakeDbUser extends Model
{
    use MockableModel;

    public $table = 'users';
}

class FakeDbTest extends TestCase
{

    /**
     * @test_
     */
    public function fake_row()
    {
        FakeDB::table('users')->addRow(['id' => 1, 'username' => 'Iman']);
        FakeDB::table('users')->addRow(['id' => 2, 'username' => 'Ghafoori']);

        $this->assertEquals(2, $users->count());
    }
}