<?php

namespace Imanghafoori\EloquentMockery\Tests\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class HasManyUser extends Model
{
    public $fillable = ['name'];

    use MockableModel;

    public function comments()
    {
        return $this->hasMany(HasManyComment::class, 'user_id');
    }
}

class HasManyComment extends Model
{
    use MockableModel;

    public $fillable = ['comment'];

    public function user()
    {
        return $this->belongsTo(HasManyUser::class);
    }
}

class HasManyTest extends TestCase
{
    /**
     * @test
     */
    public function has_many()
    {
        HasManyUser::addFakeRow(['id' => 1, 'name' => 'Iman 1']);
        HasManyUser::addFakeRow(['id' => 2, 'name' => 'Iman 2']);
        HasManyUser::addFakeRow(['id' => 3, 'name' => 'Iman 3']);
        HasManyUser::addFakeRow(['id' => 4, 'name' => 'Iman 4']);

        HasManyComment::addFakeRow(['id' => 1, 'user_id' => 1, 'comment' => 'sss']);
        HasManyComment::addFakeRow(['id' => 2, 'user_id' => 1, 'comment' => 'aaa']);
        HasManyComment::addFakeRow(['id' => 3, 'user_id' => 2, 'comment' => 'bbb']);
        HasManyComment::addFakeRow(['id' => 4, 'user_id' => 2, 'comment' => 'ccc']);
        HasManyComment::addFakeRow(['id' => 5, 'user_id' => 3, 'comment' => 'ddd']);

        $user = HasManyUser::with('comments')->where('id', 1)->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(HasManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(HasManyComment::class, $user->comments[1]);
        $this->assertEquals(2, $user->comments[1]->id);
        $this->assertEquals(1, $user->comments[0]->id);
        $this->assertEquals(1, $user->comments[0]->user_id);
        $this->assertEquals('sss', $user->comments[0]->comment);

        $user = HasManyUser::with('comments:user_id')->where('id', 1)->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(HasManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(HasManyComment::class, $user->comments[1]);
        $this->assertEquals(null, $user->comments[1]->id);
        $this->assertEquals(1, $user->comments[1]->user_id);
        $this->assertEquals(null, $user->comments[1]->comment);

        $user = HasManyUser::with('comments')->where('id', 1)->get()->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(HasManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(HasManyComment::class, $user->comments[1]);
        $this->assertEquals(2, $user->comments[1]->id);

        $comments = HasManyUser::find(1)->load('comments');
        $this->assertInstanceOf(Collection::class, $comments->comments);
        $this->assertEquals(2, $comments->comments->count());
        $this->assertInstanceOf(HasManyComment::class, $comments->comments[0]);
        $this->assertInstanceOf(HasManyComment::class, $comments->comments[1]);
        $this->assertEquals(2, $comments->comments[1]->id);

        $this->assertEquals(2, HasManyUser::find(1)->comments()->count());
        $this->assertEquals(1, HasManyUser::find(1)->comments()->where('comment', 'aaa')->count());
        $this->assertEquals(1, HasManyUser::find(1)->comments()->where('comment', 'aaa')->get()->count());
        $this->assertEquals(2, HasManyUser::find(2)->comments()->count());
        $this->assertEquals(1, HasManyUser::find(3)->comments()->count());
        $this->assertEquals(0, HasManyUser::find(4)->comments()->count());

        $this->assertEquals(2, HasManyUser::find(1)->comments->count());
        $this->assertEquals(2, HasManyUser::find(2)->comments->count());
        $this->assertEquals(1, HasManyUser::find(3)->comments->count());
        $this->assertEquals(0, HasManyUser::find(4)->comments->count());

        $comments = HasManyUser::find(3)->comments;
        $this->assertEquals('ddd', $comments[0]->comment);

        $comments = HasManyUser::find(1)->comments;
        $this->assertEquals('sss', $comments[0]->comment);

        $this->assertEquals('aaa', HasManyUser::find(1)->comments()->where('comment', 'aaa')->first()->comment);

        $this->assertEquals(1, HasManyComment::query()->find(1)->user->id);
        $this->assertEquals(1, HasManyComment::query()->find(1)->user()->count());

        $this->assertEquals(1, HasManyComment::query()->find(2)->user->id);
        $this->assertEquals(1, HasManyComment::query()->find(2)->user()->count());

        $this->assertEquals(2, HasManyComment::query()->find(3)->user->id);
        $this->assertEquals(1, HasManyComment::query()->find(3)->user()->count());

        $newUser = HasManyComment::query()->find(3)->user()->create([
            'name' => 'created',
        ]);

        $this->assertNotNull($newUser->created_at);
        $this->assertNotNull($newUser->updated_at);
        $this->assertNotNull(HasManyUser::find(5));
        $this->assertEquals(5, $newUser->id);
        $this->assertEquals('created', $newUser->name);

        $this->assertEquals('created', $newUser->name);
        $this->assertSame(HasManyUser::getCreatedModel(), $newUser);
        $this->assertEquals(5, HasManyUser::count());

        $comment = HasManyUser::find(4)->comments()->create([
            'comment' => 'created!',
        ]);
        $this->assertEquals('created!', $comment->comment);
        $this->assertEquals(6, $comment->id);
        $this->assertEquals(4, $comment->user_id);
        $this->assertNotNull($comment->created_at);
        $this->assertNotNull($comment->updated_at);
        $this->assertSame(HasManyComment::getCreatedModel(), $comment);
        $this->assertNull(HasManyComment::getCreatedModel(1));
    }
}
