<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class ExistsUser extends Model
{
    use MockableModel;
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
        ExistsUser::addFakeRow(['id' => 1, 'name' => null, 'age' => 20,]);
        ExistsUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        ExistsUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
        ExistsUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);

        $this->assertTrue(ExistsUser::query()->exists());
        $this->assertTrue(ExistsUser::query()->exists());
        $this->assertTrue(ExistsUser::whereNull('name')->exists());
        $this->assertFalse(ExistsUser::whereNull('id')->exists());
        $this->assertTrue(ExistsUser::query()->where('id', '<', 4)->where('age', '>', 20)->exists());
        $this->assertFalse(ExistsUser::query()->where('id', '>', 4)->where('age', '>', 44)->exists());
        $this->assertTrue(ExistsUser::exists('name'));
    }
}
