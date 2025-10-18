<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Zenigata\Testing\Http\FakeHttpFactory;
use Zenigata\Testing\Http\FakeUri;
use Zenigata\Testing\Http\FakeStream;
use Zenigata\Testing\Http\FakeUploadedFile;
use Zenigata\Testing\Http\FakeRequest;
use Zenigata\Testing\Http\FakeResponse;
use Zenigata\Testing\Http\FakeServerRequest;

use const UPLOAD_ERR_OK;

use function fopen;

/**
 * Unit test for {@see FakeHttpFactory}.
 * 
 * Covered cases:
 *
 * - Fake request creation.
 * - Fake response creation.
 * - Fake server request creation.
 * - Fake stream creation.
 * - Error handling when creating streams from invalid or unreadable sources.
 * - Fake uploaded file creation.
 * - Fake URI creation from a string.
 * - Fake URI creation from an empty string.
 */
#[CoversClass(FakeHttpFactory::class)]
final class FakeHttpFactoryTest extends TestCase
{
    /**
     * Fake HTTP factory instance under test.
     *
     * @var FakeHttpFactory
     */
    private FakeHttpFactory $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->factory = new FakeHttpFactory();
    }

    public function testCreateRequestReturnsFakeRequest(): void
    {
        $request = $this->factory->createRequest('GET', 'http://example.com');

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertInstanceOf(FakeRequest::class, $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://example.com/', (string) $request->getUri());
    }

    public function testCreateResponseReturnsFakeResponse(): void
    {
        $response = $this->factory->createResponse(201, 'Created');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(FakeResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testCreateServerRequestReturnsFakeServerRequest(): void
    {
        $request = $this->factory->createServerRequest('POST', 'http://localhost', ['foo' => 'bar']);

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
        $this->assertInstanceOf(FakeServerRequest::class, $request);
        $this->assertSame('POST', $request->getMethod());
    }

    public function testCreateStreamReturnsFakeStream(): void
    {
        $stream = $this->factory->createStream('string content');

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(FakeStream::class, $stream);
        $this->assertSame('string content', (string) $stream);
    }

    public function testCreateStreamFromResourceReturnsFakeStream(): void
    {
        $root = vfsStream::setup('root');
        $file = vfsStream::newFile('test.txt')->at($root)->setContent('virtual content');

        $stream = $this->factory->createStreamFromFile($file->url());

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(FakeStream::class, $stream);
        $this->assertSame('virtual content', (string) $stream);
    }

    public function testCreateStreamFromResource(): void
    {
        $root = vfsStream::setup('root');
        $file = vfsStream::newFile('resource.txt')->at($root)->setContent('resource content');
        
        $stream = $this->factory->createStreamFromResource(fopen($file->url(), 'rb'));

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(FakeStream::class, $stream);
        $this->assertSame('resource content', (string) $stream);
    }

    public function testCreateStreamFromFileThrowsIfFileUnreadable(): void
    {
        $this->expectException(RuntimeException::class);

        $this->factory->createStreamFromFile('/non/existent/file.txt');
    }

    public function testCreateStreamFromResourceThrowsOnInvalidResource(): void
    {
        $this->expectException(RuntimeException::class);

        $this->factory->createStreamFromResource('not-a-resource');
    }

    public function testCreateStreamWithFileUri(): void
    {
        $root = vfsStream::setup('root');
        $file = vfsStream::newFile('file.txt')->at($root)->setContent('uri content');

        $stream = $this->factory->createStream('file://' . $file->url());

        $this->assertInstanceOf(FakeStream::class, $stream);
        $this->assertSame('uri content', (string) $stream);
    }

    public function testCreateUploadedFileReturnsFakeUploadedFile(): void
    {
        $file = $this->factory->createUploadedFile(
            stream: new FakeStream('foo'),
            size: 7,
            error: UPLOAD_ERR_OK,
            clientFilename: 'file.txt',
            clientMediaType: 'text/plain'
        );

        $this->assertInstanceOf(UploadedFileInterface::class, $file);
        $this->assertInstanceOf(FakeUploadedFile::class, $file);
        $this->assertSame(7, $file->getSize());
        $this->assertSame('file.txt', $file->getClientFilename());
        $this->assertSame('text/plain', $file->getClientMediaType());
    }

    public function testCreateUriReturnsFakeUriFromString(): void
    {
        $uri = $this->factory->createUri('https://user:pass@acme.com:8080/test?foo=bar#frag');

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertInstanceOf(FakeUri::class, $uri);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('acme.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/test', $uri->getPath());
        $this->assertSame('foo=bar', $uri->getQuery());
        $this->assertSame('frag', $uri->getFragment());
    }

    public function testCreateUriWithEmptyStringReturnsUriWithDefaults(): void
    {
        $uri = $this->factory->createUri('');

        $this->assertInstanceOf(FakeUri::class, $uri);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
    }
}