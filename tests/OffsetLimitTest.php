<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class OffsetUser extends Model
{
    use MockableModel;

    public function comments()
    {
        return $this->hasMany(HasManyComment::class, 'user_id');
    }
}

class OffsetLimitTest extends TestCase
{
    /**
     * @test
     */
    public function offset_limit()
    {
        OffsetUser::addFakeRow(['id' => 1, 'name' => 'Iman 1']);
        OffsetUser::addFakeRow(['id' => 2, 'name' => 'Iman 2']);
        OffsetUser::addFakeRow(['id' => 3, 'name' => 'Iman 3']);
        OffsetUser::addFakeRow(['id' => 4, 'name' => 'Iman 4']);

        $users = OffsetUser::skip(1)->limit(2)->get();
        $this->assertEquals(2, $users->count());
        $this->assertEquals(2, $users->first()->id);
        $this->assertEquals(3, $users[1]->id);
        $this->assertEquals(OffsetUser::class, get_class($users[1]));

        $users = OffsetUser::query()->offset(1)->limit(2)->get();
        $this->assertEquals(2, $users->count());
        $this->assertEquals(2, $users->first()->id);
        $this->assertEquals(3, $users[1]->id);

        $users = OffsetUser::skip(1)->first();
        $this->assertEquals(2, $users->id);

        $users = OffsetUser::offset(2)->first();
        $this->assertEquals(3, $users->id);
    }
}
