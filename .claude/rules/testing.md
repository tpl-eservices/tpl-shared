---
paths:
  - "tests/**"
---

# Testing with Pest

All tests use Pest. Create tests with `php artisan make:test --pest {name}`.

## Running Tests

- Run minimal tests: `php artisan test --filter=testName`
- Run file: `php artisan test tests/Feature/ExampleTest.php`
- Run all: `php artisan test` or `composer test`
- Parallel: `php artisan test -p` (recommended before commits)
- Static analysis: `composer analyse` (PHPStan level 8)

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
