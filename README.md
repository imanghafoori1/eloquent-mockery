# Eloquent Mockery

Mock your eloquent queries without the repository pattern.

### Why this package was invented?
- It solves the problem of "slow tests" by removing the interactions with a real database.
- It simplifies the process of writing and running tests since you will be "DB Independent".

## Usage:
You have to use the `MockableModel` in your model.

```php

use Imanghafoori\EloquentMockery\MockableModel;

class User extends Model
{
    use MockableModel;
    
    ...
}

```

Then you can:
```php
public function test_basic()
{
    // Arrange:
    User::addFakeRow(['id' => 1, 'username' => 'faky', 'password' => '...']);
    User::addFakeRow(['id' => 1, 'username' => 'maky', 'password' => '...']]);

    // Act (This query resides in your controller):
    $user = User::where('username', 'faky')->first();   # <=== This does NOT connect to DB.

    // assert:
    $this->assert($user->id === 1);
    $this->assert($user->username === 'faky');
}
```


### Mocking a `create` query:
```php
public function test_basic()
{
    User::fake();

    // in your controller:
    $user = User::create(['username' => 'iman', 'email' => 'iman@gmail.com']);   # <=== This does NOT connect to DB.

    // Assert:
    $user = User::getCreatedModel();
    $this->assert($user->id === 1);
    $this->assert($user->username === 'iman');

    User::stopFaking();
}
```
You can access the changed model instances by accessing the static properties below:
```php

$model = User::getDeletedModel();

$model = User::getSoftDeletedModel();

$model = User::getCreatedModel();

$model = User::getUpdatedModel();

```
The `User` can be any other model using the `Mockable` trait.


- For more examples take a look at the `tests` directory.

<a name="license"></a>
## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


<a name="contributing"></a>

### :raising_hand: Contributing
If you find an issue or have a better way to do something, feel free to open an issue, or a pull request.

<a name="security"></a>
### :exclamation: Security
If you discover any security-related issues, please email `imanghafoori1@gmail.com` instead of using the issue tracker.


