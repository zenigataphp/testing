# Testing

A lightweight PHP library offering **in-memory fake implementations** for common [PSR](https://www.php-fig.org/psr/) interfaces. They behave according to the PSR contracts but keep everything **self-contained**, making your tests faster, isolated, and free from external infrastructure.

## Features

- In-memory implementations for multiple PSR specs
- Compliant with PSR contracts
- No external dependencies required
- Ideal for unit and integration tests

## Installation

```shell
composer require --dev zenigata/testing
```

This library is meant for **testing only**, so it’s recommended to install it as a dev dependency.

## Available Fake Implementations

### Cache

- `FakeCacheItem` (PSR-6)
- `FakeCacheItemPool` (PSR-6)
- `FakeCachePool` (PSR-6)
- `FakeSimpleCache` (PSR-16)

### HTTP

- `FakeHttpFactory` (PSR-17)
- `FakeMessage` (PSR-7)
- `FakeMiddleware` (PSR-15)
- `FakeRequest` (PSR-7)
- `FakeRequestHandler` (PSR-15)
- `FakeResponse` (PSR-7)
- `FakeServerRequest` (PSR-7)
- `FakeStream` (PSR-7)
- `FakeUploadedFile` (PSR-7)
- `FakeUri` (PSR-7)

### Infrastructure

- `FakeClock`(PSR-20)
- `FakeContainer` (PSR-11)
- `FakeLogger` (PSR-3)

## Usage

### `FakeSimpleCache` (PSR-16)

```php
use Zenigata\Testing\Cache\FakeSimpleCache;

$cache = new FakeSimpleCache();
$cache->set('foo', 'bar');

assert($cache->get('foo') === 'bar');
```

### `FakeResponse` & `FakeStream` (PSR-7)

```php
use Zenigata\Testing\Http\FakeResponse;
use Zenigata\Testing\Http\FakeStream;

$response = new FakeResponse(
    statusCode: 200,
    body:       new FakeStream('hello')
);

assert($response->getStatusCode() === 200);
assert((string) $response->getBody() === 'hello');
```

### `FakeRequestHandler` (PSR-15)

#### 1. Using Hooks to Inspect the Request

```php
use Psr\Http\Message\ServerRequestInterface;
use Zenigata\Testing\Http\FakeRequestHandler;
use Zenigata\Testing\Http\FakeServerRequest;
use Zenigata\Testing\Http\FakeUri;

// Extend FakeRequestHandler to capture the incoming request
$handler = new class extends FakeRequestHandler {
    public ?ServerRequestInterface $capturedRequest = null;

    protected function onHandle(ServerRequestInterface $request): void
    {
        // Save the request for inspection in the test
        $this->capturedRequest = $request;
    }
};

$request = new FakeServerRequest(
    method: 'POST',
    uri:    new FakeUri(path: '/submit')
);

$response = $handler->handle($request);

assert($handler->capturedRequest === $request);
assert($handler->capturedRequest->getMethod() === 'POST');
assert($response->getStatusCode() === 200);
```

You can use `onHandle` and `onResponse` hooks to **extend behavior** without writing a full request handler or middleware from scratch.

#### 2. Throwing Exceptions for Error Propagation

```php
use RuntimeException;
use Zenigata\Testing\Http\FakeRequestHandler;
use Zenigata\Testing\Http\FakeServerRequest;
use Zenigata\Testing\Http\FakeUri;

// Configure the handler to throw instead of returning a response
$handler = new FakeRequestHandler(
    throwable: new RuntimeException('Something failed')
);

$request = new FakeServerRequest(
    method: 'GET',
    uri:    new FakeUri(path: '/will-fail')
);

try {
    $handler->handle($request);
} catch (RuntimeException $e) {
    assert($e->getMessage() === 'Something failed');
}
```

### `FakeContainer` (PSR-11)

```php
use Psr\Container\NotFoundExceptionInterface;
use Zenigata\Testing\Infrastructure\FakeContainer;

$container = new FakeContainer([
    'config'  => ['env' => 'test'],
    'service' => new stdClass(),
]);

// Retrieve entries
$config = $container->get('config');
$service = $container->get('service');

assert($config['env'] === 'test');
assert($service instanceof stdClass);

// Accessing a missing entry throws a PSR-11 exception
try {
    $container->get('missing');
} catch (NotFoundExceptionInterface $e) {
    echo $e->getMessage(); // "Service 'missing' not found"
}
```

### `FakeLogger` (PSR-3)

```php
use Zenigata\Testing\Infrastructure\FakeLogger;

$logger = new FakeLogger();

$logger->info('User logged in', ['id' => 42]);
$logger->error('Something went wrong');

// Assertions in your test
assert(count($logger->output) === 2);
assert(str_contains($logger->output[0], '[INFO] User logged in'));
assert(str_contains($logger->output[1], '[ERROR] Something went wrong'));
```

Instead of sending logs to a backend, `FakeLogger` collects them in memory so you can inspect and assert logged output.

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

Keep the implementation minimal, focused, and well-documented, making sure to update tests accordingly.

See [CONTRIBUTING](./CONTRIBUTING.md) for more information.

## License

This library is licensed under the MIT license. See [LICENSE](./LICENSE) for more information.
