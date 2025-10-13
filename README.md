# Zenigata Testing

A lightweight PHP library offering **in-memory fake implementations** for common [PSR](https://www.php-fig.org/psr/) interfaces. They behave according to the PSR contracts but keep everything **self-contained**, making your tests faster, isolated, and free from external infrastructure.

## Features

- In-memory implementations for multiple PSR specs
- Compliant with PSR contracts
- Ideal for unit and integration tests
- Lightweight

## Requirements

- PHP >= 8.2
- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos)

## Installation

```shell
composer require --dev zenigata/testing
```

This library is meant for **testing only**, so it’s recommended to install it as a **dev dependency**.

## Available Fake Implementations

### Cache

- `FakeCacheItem`: fake implementation of [CacheItemInterface](https://www.php-fig.org/psr/psr-6/#cacheiteminterface) (PSR-6)
- `FakeCacheItemPool`: fake implementation of [CacheItemPoolInterface](https://www.php-fig.org/psr/psr-6/#cacheitempoolinterface) (PSR-6)
- `FakeSimpleCache`: fake implementation of [CacheInterface](https://www.php-fig.org/psr/psr-16/#21-cacheinterface) (PSR-16)

### HTTP

- `FakeHttpClient`: fake implementation of [ClientInterface](https://www.php-fig.org/psr/psr-18/#clientinterface) (PSR-18)
- `FakeHttpFactory`: fake implementation of all [HTTP Factories](https://www.php-fig.org/psr/psr-17/#2-interfaces) (PSR-17)
- `FakeMessage`: fake implementation of [MessageInterface](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface) (PSR-7)
- `FakeMiddleware`: fake implementation of [MiddlewareInterface](https://www.php-fig.org/psr/psr-15/#22-psrhttpservermiddlewareinterface) (PSR-15)
- `FakeRequest`: fake implementation of [RequestInterface](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface) (PSR-7)
- `FakeRequestHandler`: fake implementation of [RequestHandlerInterface](https://www.php-fig.org/psr/psr-15/#21-psrhttpserverrequesthandlerinterface) (PSR-15)
- `FakeResponse`: fake implementation of [ResponseInterface](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface) (PSR-7)
- `FakeServerRequest`: fake implementation of [ServerRequestInterface](https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface) (PSR-7)
- `FakeStream`: fake implementation of [StreamInterface](https://www.php-fig.org/psr/psr-7/#34-psrhttpmessagestreaminterface) (PSR-7)
- `FakeUploadedFile`: fake implementation of [UploadedFileInterface](https://www.php-fig.org/psr/psr-7/#36-psrhttpmessageuploadedfileinterface) (PSR-7)
- `FakeUri`: fake implementation of [UriInterface](https://www.php-fig.org/psr/psr-7/#35-psrhttpmessageuriinterface) (PSR-7)

### Infrastructure

- `FakeClock`: fake implementation of [ClockInterface](https://www.php-fig.org/psr/psr-20/#21-clockinterface) (PSR-20)
- `FakeContainer`: fake implementation of [ContainerInterface](https://www.php-fig.org/psr/psr-11/#31-psrcontainercontainerinterface) (PSR-11)
- `FakeLogger`: fake implementation of [LoggerInterface](https://www.php-fig.org/psr/psr-3/#3-psrlogloggerinterface) (PSR-3)

## Usage

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

`FakeLogger` stores all logs in the public `$output` property, making it easy to inspect and assert log content in your tests.

### `FakeCacheItem` & `FakeCacheItemPool` (PSR-6)

```php
use Zenigata\Testing\Cache\FakeCacheItem;
use Zenigata\Testing\Cache\FakeCacheItemPool;

$pool = new FakeCacheItemPool();
$item = new FakeCacheItem('foo', 'bar');

$pool->save($item);

// You can also inspect the in-memory store directly
assert($cache->items['foo'] === $item);
```

`FakeCacheItemPool` exposes two public properties:

- `$items`: the active in-memory cache store
- `$deferred`: items saved with `saveDeferred()` but not yet committed

Both can be used for **debugging** or **fine-grained assertions** in tests.

### `FakeResponse` & `FakeStream` (PSR-7)

```php
use Zenigata\Testing\Http\FakeResponse;
use Zenigata\Testing\Http\FakeStream;

$stream = new FakeStream('hello');
$response = new FakeResponse(statusCode: 200, body: $stream);

assert($response->getStatusCode() === 200);
assert((string) $response->getBody() === 'hello');

// Streams track read operations for inspection
$body = $response->getBody()->getContents();

assert($stream->reads[0] === 'hello');
```

`FakeStream` exposes `$reads` to let you verify how your code interacts with the stream.

### `FakeContainer` (PSR-11)

```php
use Psr\Container\NotFoundExceptionInterface;
use Zenigata\Testing\Infrastructure\FakeContainer;

$container = new FakeContainer([
    'foo'     => 'bar',
    'service' => new stdClass(),
]);

assert($container->get('config') === 'bar');
assert($container->get('service') instanceof stdClass);

// You can inspect the registered entries directly
assert(count($container->entries) === 2);

// Accessing a missing entry throws a PSR-11 exception
try {
    $container->get('missing');
} catch (NotFoundExceptionInterface $e) {
    echo $e->getMessage(); // "Service 'missing' not found."
}
```

`FakeContainer` exposes the `$entries` property contains the in-memory **map of services**.

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
    exception: new RuntimeException('Something failed')
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

### `FakeSimpleCache` (PSR-16)

```php
use Zenigata\Testing\Cache\FakeSimpleCache;

$cache = new FakeSimpleCache();
$cache->set('foo', 'bar');

assert($cache->get('foo') === 'bar');

// Internal storage can also be inspected
assert($cache->items['foo'] === 'bar');
```

Like `FakeCacheItemPool`, `FakeSimpleCache` exposes an `$items` property with all cached values.

### `FakeHttpFactory` (PSR-17)

```php
use Zenigata\Testing\Http\FakeHttpFactory;

$factory = new FakeHttpFactory();
$response = $factory->createResponse(201);
$request = $factory->createRequest('GET', 'http://example.com');

assert($response->getStatusCode() === 201);
assert($request->getMethod() === 'GET');
assert((string) $request->getUri() === 'http://example.com/');
```

### `FakeHttpClient` (PSR-18)

```php
use Psr\Http\Client\RequestExceptionInterface;
use Zenigata\Testing\Exception\RequestException;
use Zenigata\Testing\Http\FakeHttpClient;
use Zenigata\Testing\Http\FakeRequest;

// Create a fake request and client
$request = new FakeRequest();
$client  = new FakeHttpClient();

// Send a request — no real network I/O occurs
$response = $client->sendRequest($request);

assert($response->getStatusCode() === 200);
assert($client->calls[0] === $request);

// Simulate a client that always throws a request exception
$client = new FakeHttpClient(exception: new RequestException('custom error'));

try {
    $client->sendRequest(new FakeRequest());
} catch (RequestExceptionInterface $e) {
    assert($e->getMessage() === 'custom error');
}
```

`FakeHttpClient` stores all **“sent”** requests in the public `$calls` property, allowing you to inspect and assert request behavior in your tests.

This package incluedes **PSR-18 compliant exceptions** like `RequestException` or `NetworkException` for error-handling scenarios.

### `FakeClock` (PSR-20)

```php
use Zenigata\Testing\Infrastructure\FakeClock;

$datetime = new DateTimeImmutable('2023-01-01 00:00:00');
$clock = new FakeClock($datetime);

// Return the fixed time set at construction
assert($clock->now() === $datetime);
```

## Dependencies

- [php-http-enum](https://github.com/alexanderpas/php-http-enum): this package is used to provide standardized reason phrases in fake responses

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

Keep the implementation minimal, focused, and well-documented, making sure to update tests accordingly.

See [CONTRIBUTING](./CONTRIBUTING.md) for more information.

## License

This library is licensed under the MIT license. See [LICENSE](./LICENSE) for more information.
