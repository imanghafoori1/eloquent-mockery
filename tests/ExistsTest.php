<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class ExistsUser extends Model
{
    protected $table = 'users';
}

class ExistsTest extends TestCase
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
    public function basic_exists()
    {
        FakeDB::addRow('users',['id' => 1, 'name' => null, 'age' => 20,]);
        FakeDB::addRow('users',['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users',['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
        FakeDB::addRow('users',['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);

        $this->assertTrue(ExistsUser::query()->exists());
        $this->assertTrue(ExistsUser::query()->exists());
        $this->assertTrue(ExistsUser::whereNull('name')->exists());
        $this->assertFalse(ExistsUser::whereNull('id')->exists());
        $this->assertTrue(ExistsUser::query()->where('id', '<', 4)->where('age', '>', 20)->exists());
        $this->assertFalse(ExistsUser::query()->where('id', '>', 4)->where('age', '>', 44)->exists());
        $this->assertTrue(ExistsUser::exists('name'));
    }
}
