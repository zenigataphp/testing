<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use const UPLOAD_ERR_OK;

use function fclose;
use function fopen;
use function is_readable;
use function is_resource;
use function parse_url;
use function rewind;
use function str_starts_with;
use function stream_get_contents;
use function substr;

use RuntimeException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Zenigata\Testing\Http\FakeUri;
use Zenigata\Testing\Http\FakeStream;
use Zenigata\Testing\Http\FakeUploadedFile;
use Zenigata\Testing\Http\FakeRequest;
use Zenigata\Testing\Http\FakeResponse;
use Zenigata\Testing\Http\FakeServerRequest;

/**
 * Fake implementation of all PSR-17 HTTP factories:
 * {@see RequestFactoryInterface}, {@see ResponseFactoryInterface},
 * {@see ServerRequestFactoryInterface}, {@see StreamFactoryInterface},
 * {@see UploadedFileFactoryInterface}, and {@see UriFactoryInterface}.
 *
 * This HTTP factory implementation creates lightweight fake PSR-7 objects for testing purposes,
 * without relying on real HTTP transport or network operations.
 *
 * Methods creating streams from files or `file://` URIs will read the provided file contents,
 * these can be real files or virtual files used in tests.
 */
class FakeHttpFactory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new FakeRequest($method, $uri instanceof UriInterface ? $uri : new FakeUri());
    }

    /**
     * {@inheritDoc}
     */
    public function createResponse(int $statusCode = 200, string $reasonPhrase = 'OK'): ResponseInterface
    {
        return new FakeResponse($statusCode, $reasonPhrase);
    }

    /**
     * {@inheritDoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new FakeServerRequest(
            serverParams: $serverParams,
            method:       $method,
            uri:          $uri instanceof UriInterface ? $uri : new FakeUri()
        );
    }

    /**
     * {@inheritDoc}
     * 
     * Creating streams from `file://` URIs will read the provided file contents,
     * these can be real files or virtual files used in tests.
     */
    public function createStream(string $content = ''): StreamInterface
    {
        if (str_starts_with($content, 'file://')) {
            $path = substr($content, 7);

            if (!is_readable($path)) {
                throw new RuntimeException("Cannot read file: {$path}");
            }

            $resource = fopen($path, 'rb');
            $content = stream_get_contents($resource);
            fclose($resource);
        }

        return new FakeStream($content);
    }

    /**
     * {@inheritDoc}
     * 
     * Creating streams from file will read the provided file contents,
     * these can be real files or virtual files used in tests.
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!is_readable($filename)) {
            throw new RuntimeException("Cannot read file: {$filename}");
        }

        $resource = fopen($filename, $mode);

        if ($resource === false) {
            throw new RuntimeException("Unable to open file: {$filename}");
        }

        $content = stream_get_contents($resource);
        fclose($resource);

        return new FakeStream($content);
    }

    /**
     * {@inheritDoc}
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new RuntimeException('Expected resource');
        }

        $content = stream_get_contents($resource);
        rewind($resource);

        return new FakeStream($content);
    }

    /**
     * {@inheritDoc}
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        return new FakeUploadedFile(
            stream:          $stream,
            size:            $size ?? $stream->getSize() ?? 0,
            error:           $error,
            clientFilename:  $clientFilename,
            clientMediaType: $clientMediaType
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createUri(string $uri = ''): UriInterface
    {
        $parsed = parse_url($uri);

        $user = $parsed['user'] ?? '';
        $pass = $parsed['pass'] ?? '';

        return new FakeUri(
            scheme:   $parsed['scheme'] ?? '',
            userInfo: $pass ? "$user:$pass" : $user,
            host:     $parsed['host'] ?? '',
            port:     $parsed['port'] ?? null,
            path:     $parsed['path'] ?? '',
            query:    $parsed['query'] ?? '',
            fragment: $parsed['fragment'] ?? ''
        );
    }
}
