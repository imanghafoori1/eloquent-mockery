<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class WhereArrayUser extends Model
{
    use MockableModel;
}

class ArrayAsConditionsTest extends TestCase
{
    public function tearDown(): void
    {
        WhereArrayUser::stopFaking();
    }

    /**
     * @test
     */
    public function whereArray()
    {
        WhereArrayUser::addFakeRow([
            'id' => 1,
            'name' => 'test-name',
            'email' => 'test-email1',
            'address' => 'test-address0',
        ]);

        WhereArrayUser::addFakeRow([
            'id' => 2,
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        WhereArrayUser::addFakeRow([
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
