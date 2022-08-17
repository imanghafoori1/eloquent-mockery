<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class ManyToManyUser extends Model
{
    public $fillable = ['username'];

    use MockableModel;

    public function city()
    {
        return $this->belongsToMany(ManyToManyCity::class);
    }
}

class ManyToManyCity extends Model
{
    use MockableModel;

    public $fillable = ['name'];

    public function user()
    {
        return $this->belongsToMany(ManyTosManyUser::class);
    }
}

class ManyToManyTest extends TestCase
{
    /**
     * @test_
     */
    public function belongs_to_many()
    {
        ManyToManyUser::addFakeRow(['id' => 1, 'name' => 'Iman 1']);
        ManyToManyUser::addFakeRow(['id' => 2, 'name' => 'Iman 2']);
        ManyToManyUser::addFakeRow(['id' => 3, 'name' => 'Iman 3']);
        ManyToManyUser::addFakeRow(['id' => 4, 'name' => 'Iman 4']);

        ManyToManyCity::addFakeRow(['id' => 1, 'user_id' => 1, 'comment' => 'sss']);
        ManyToManyCity::addFakeRow(['id' => 2, 'user_id' => 1, 'comment' => 'aaa']);
        ManyToManyCity::addFakeRow(['id' => 3, 'user_id' => 2, 'comment' => 'bbb']);
        ManyToManyCity::addFakeRow(['id' => 4, 'user_id' => 2, 'comment' => 'ccc']);
        ManyToManyCity::addFakeRow(['id' => 5, 'user_id' => 3, 'comment' => 'ddd']);

        $user = ManyToManyUser::where('id', 1)->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[1]);
        $this->assertEquals(2, $user->comments[1]->id);
        $this->assertEquals(1, $user->comments[0]->id);
        $this->assertEquals(1, $user->comments[0]->user_id);
        $this->assertEquals('sss', $user->comments[0]->comment);

        $user = ManyToManyUser::with('comments:user_id')->where('id', 1)->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[1]);
        $this->assertEquals(null, $user->comments[1]->id);
        $this->assertEquals(1, $user->comments[1]->user_id);
        $this->assertEquals(null, $user->comments[1]->comment);

        $user = ManyToManyUser::with('comments')->where('id', 1)->get()->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[1]);
        $this->assertEquals(2, $user->comments[1]->id);

        $comments = ManyToManyUser::find(1)->load('comments');
        $this->assertInstanceOf(Collection::class, $comments->comments);
        $this->assertEquals(2, $comments->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $comments->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $comments->comments[1]);
        $this->assertEquals(2, $comments->comments[1]->id);

        $this->assertEquals(2, ManyToManyUser::find(1)->comments()->count());
        $this->assertEquals(1, ManyToManyUser::find(1)->comments()->where('comment', 'aaa')->count());
        $this->assertEquals(1, ManyToManyUser::find(1)->comments()->where('comment', 'aaa')->get()->count());
        $this->assertEquals(2, ManyToManyUser::find(2)->comments()->count());
        $this->assertEquals(1, ManyToManyUser::find(3)->comments()->count());
        $this->assertEquals(0, ManyToManyUser::find(4)->comments()->count());

        $this->assertEquals(2, ManyToManyUser::find(1)->comments->count());
        $this->assertEquals(2, ManyToManyUser::find(2)->comments->count());
        $this->assertEquals(1, ManyToManyUser::find(3)->comments->count());
        $this->assertEquals(0, ManyToManyUser::find(4)->comments->count());

        $comments = ManyToManyUser::find(3)->comments;
        $this->assertEquals('ddd', $comments[0]->comment);

        $comments = ManyToManyUser::find(1)->comments;
        $this->assertEquals('sss', $comments[0]->comment);

        $this->assertEquals('aaa', ManyToManyUser::find(1)->comments()->where('comment', 'aaa')->first()->comment);

        $this->assertEquals(1, ManyToManyCity::query()->find(1)->user->id);
        $this->assertEquals(1, ManyToManyCity::query()->find(1)->user()->count());

        $this->assertEquals(1, ManyToManyCity::query()->find(2)->user->id);
        $this->assertEquals(1, ManyToManyCity::query()->find(2)->user()->count());

        $this->assertEquals(2, ManyToManyCity::query()->find(3)->user->id);
        $this->assertEquals(1, ManyToManyCity::query()->find(3)->user()->count());

        $newUser = ManyToManyCity::query()->find(3)->user()->create([
            'name' => 'created',
        ]);

        $this->assertNotNull($newUser->created_at);
        $this->assertNotNull($newUser->updated_at);
        $this->assertNotNull(ManyToManyUser::find(5));
        $this->assertEquals(5, $newUser->id);
        $this->assertEquals('created', $newUser->name);

        $this->assertEquals('created', $newUser->name);
        $this->assertSame(ManyToManyUser::getCreatedModel(), $newUser);
        $this->assertEquals(5, ManyToManyUser::count());

        $comment = ManyToManyUser::find(4)->comments()->create([
            'comment' => 'created!',
        ]);
        $this->assertEquals('created!', $comment->comment);
        $this->assertEquals(6, $comment->id);
        $this->assertEquals(4, $comment->user_id);
        $this->assertNotNull($comment->created_at);
        $this->assertNotNull($comment->updated_at);
        $this->assertSame(ManyToManyCity::getCreatedModel(), $comment);
        $this->assertNull(ManyToManyCity::getCreatedModel(1));
    }
}
