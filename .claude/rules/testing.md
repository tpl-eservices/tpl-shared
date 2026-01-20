---
paths:
  - "tests/**"
---

# Testing with Pest

All tests use Pest. Create tests with `php artisan make:test --pest {name}`.

## Running Tests

- Run minimal tests: `php artisan test --filter=testName`
- Run file: `php artisan test tests/Feature/ExampleTest.php`
- Run all: `php artisan test`
- Parallel: `php artisan test -p` (Use this for frequently running tests after changes and before commits)
- Code coverage: `herd coverage ./vendor/bin/pest --coverage` (allows code coverage without xdebug et al configured all the time)

After changes pass, ask user if they want to run the full suite.

## Pest Syntax

```php
it('does something', function () {
    expect(true)->toBeTrue();
});
```

## Assertions

Use specific status methods: `assertForbidden()`, `assertNotFound()` — not `assertStatus(403)`.

```php
$response->assertSuccessful();
$response->assertForbidden();
```

## Mocking

```php
use function Pest\Laravel\mock;

mock(SomeService::class)->shouldReceive('method')->andReturn('value');
// or: $this->mock(SomeService::class)
```

## Datasets

Use for repetitive test cases (especially validation):

```php
it('validates emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james@example.com',
    'taylor@example.com',
]);
```

## Pest 4 Browser Testing

Browser tests live in `tests/Browser/`. Features include:
- Real browser interaction (click, type, scroll, drag-and-drop)
- Multiple browsers/viewports
- Screenshots for debugging
- `assertNoJavascriptErrors()`, `assertNoConsoleLogs()`

```php
$page = visit('/login');
$page->assertSee('Sign In')
    ->fill('email', 'user@example.com')
    ->click('Submit');
```
