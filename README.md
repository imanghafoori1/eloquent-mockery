# eloquent-mockery
Mock your eloquent queries without the repository pattern.

## Usage:
You have to use the `MockableModel` in your model.
```php

use Imanghafoori\EloquentMockery\MockableModel;


User extends Model
{
    use MockableModel;
}
```

Then you can:
```php

        // arrange:
        User::addFakeRow(['id' => 1, 'username' => 'faky', 'password' => '...']);
        User::addFakeRow(['id' => 1, 'username' => 'maky', 'password' => '...']]);

        // act:
        $user = User::where('username', 'faky')->first();   # <=== does NOT connect to DB

        // assert:
        assert($user->id === 1);

```

It uses laravel collections behind the scenes.