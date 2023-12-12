# Eloquent Mockery

Mock your eloquent queries without the repository pattern.


[![Required Laravel Version][ico-laravel]][link-packagist]
<a href="https://packagist.org/packages/imanghafoori/eloquent-mockery" rel="nofollow"><img src="https://camo.githubusercontent.com/ff110760ba1d6de9119c4599aee70aa0d65137c1a8fcffe539912f723da66b9d/68747470733a2f2f696d672e736869656c64732e696f2f7061636b61676973742f7068702d762f696d616e676861666f6f72692f6c61726176656c2d6d6963726f73636f70653f636f6c6f723d253233383839324246267374796c653d666c61742d737175617265266c6f676f3d706870" alt="Required PHP Version" data-canonical-src="https://img.shields.io/packagist/php-v/imanghafoori/eloquent-mockery?color=%238892BF&amp;style=flat-square&amp;logo=php" style="max-width: 100%;"></a>
[![tests](https://github.com/imanghafoori1/eloquent-mockery/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/imanghafoori1/eloquent-mockery/actions/workflows/tests.yml)
[![Imports](https://github.com/imanghafoori1/eloquent-mockery/actions/workflows/imports.yml/badge.svg?branch=main)](https://github.com/imanghafoori1/eloquent-mockery/actions/workflows/imports.yml)
<a href="https://github.com/imanghafoori1/eloquent-mockery/blob/main/LICENSE"><img src="https://camo.githubusercontent.com/d885b3999bb863974fb67118174bb0402d089a89/68747470733a2f2f696d672e736869656c64732e696f2f62616467652f6c6963656e73652d4d49542d626c75652e7376673f7374796c653d726f756e642d737175617265" alt="Software License" data-canonical-src="https://img.shields.io/badge/license-MIT-blue.svg?style=round-square" style="max-width:100%;"></a>

### Why this package was invented?
- It solves the problem of "slow tests" by removing the interactions with a real database.
- It simplifies the process of writing and running tests since your tests will be "DB Independent".

## Installation
You can **install** the package via Composer:
```bash
composer require imanghafoori/eloquent-mockery --dev dev-main
```

## Usage:
First, you have to define a new connection in your `config/database.php` and set the driver to 'arrayDB'.

```php
<?php

return [
  
   ...
  
  'connections' => [
     'my_test_connection' => [
         'driver' => 'arrayDB',
         'database' => '',
     ],
     
     ...
  ],
  ...
]
```

Then you can:

```php
public function test_basic()
{
    config()->set('database.default', 'my_test_connection');

    # ::Arrange:: (Setup Sample Data)
    FakeDB::addRow('users', ['id' => 1, 'username' => 'faky', 'password' => '...']);
    FakeDB::addRow('users', ['id' => 1, 'username' => 'maky', 'password' => '...']);

    # ::Act:: (This query resides in your controller)
    $user = User::where('username', 'faky')->first();   # <=== This does NOT connect to a real DB.

    # ::Assert::
    $this->assert($user->id === 1);
    $this->assert($user->username === 'faky');
}
```


### Mocking a `create` query:
```php
public function test_basic()
{
    # In setUp:
    FakeDB::mockEloquentBuilder();

    # ::Act::
    $this->post('/create-url', ['some' => 'data' ])

    # ::Assert::
    $user = User::first();
    $this->assertEquals('iman', $user->username);

    # In tearDown
    FakeDB::dontMockEloquentBuilder();
}
```

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

[ico-laravel]: https://img.shields.io/badge/Laravel-%E2%89%A5%206.0-ff2d20?style=flat-square&logo=laravel
[ico-php]: https://img.shields.io/packagist/php-v/imanghafoori/eloquent-mockery?color=%238892BF&style=flat-square&logo=php
[link-packagist]: https://packagist.org/packages/imanghafoori/eloquent-mockery
