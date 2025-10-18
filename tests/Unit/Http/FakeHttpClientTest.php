<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Client\ClientExceptionInterface;
use Zenigata\Testing\Exception\RequestException;
use Zenigata\Testing\Http\FakeHttpClient;
use Zenigata\Testing\Http\FakeRequest;
use Zenigata\Testing\Http\FakeResponse;

/**
 * Unit test for {@see FakeHttpClient}.
 * 
 * Covered cases:
 *
 * - Default state with empty call history.
 * - Return a default fake response when no custom response is provided.
 * - Return a custom response when injected via the constructor.
 * - Throw a preconfigured client exception instead of returning a response.
 * - Verifies the client exception may hold a different request.
 * - Recording of sent requests without changes.
 */
#[CoversClass(FakeHttpClient::class)]
final class FakeHttpClientTest extends TestCase
{
    public function testDefaults(): void
    {
        $client = new FakeHttpClient();

        $this->assertEmpty($client->calls);
    }

    public function testReturnsPredefinedResponse(): void
    {
        $response = new FakeResponse();
        $client = new FakeHttpClient(response: $response);
        $request = new FakeRequest();

        $result = $client->sendRequest($request);

        $this->assertSame($response, $result);
        $this->assertCount(1, $client->calls);
        $this->assertSame($request, $client->calls[0]);
    }

    public function testThrowsConfiguredException(): void
    {
        $this->expectException(ClientExceptionInterface::class);

        $exception = new RequestException();
        $client = new FakeHttpClient(exception: $exception);

        $client->sendRequest(new FakeRequest());
    }

    public function testNoCallsRecordedIfExceptionThrown(): void
    {
        $exception = new RequestException();
        $client = new FakeHttpClient(exception: $exception);

        try {
            $client->sendRequest(new FakeRequest());
        } catch (ClientExceptionInterface) {
            // Ignore
        }

        $this->assertEmpty($client->calls);
    }

    public function testRequestExceptionReturnsDistinctRequestInstance(): void
    {
        $request = new FakeRequest();
        $exception = new RequestException();

        $client = new FakeHttpClient(exception: $exception);

        try {
            $client->sendRequest($request);
        } catch (RequestException $e) {
            $this->assertNotSame($request, $e->getRequest());
        }
    }

    public function testMultipleRequestsAreRecordedInOrder(): void
    {
        $request1 = new FakeRequest(method: 'GET');
        $request2 = new FakeRequest(method: 'POST');

        $client = new FakeHttpClient();

        $client->sendRequest($request1);
        $client->sendRequest($request2);

        $this->assertCount(2, $client->calls);
        $this->assertSame([$request1, $request2], $client->calls);
    }

    public function testRecordedRequestsAreUnmodifiedInstances(): void
    {
        $request = new FakeRequest(headers: ['X-Test' => '123']);
        $client = new FakeHttpClient();

        $client->sendRequest($request);

        $this->assertSame($request, $client->calls[0]);
        $this->assertSame(['X-Test' => ['123']], $client->calls[0]->getHeaders());
    }
}