<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Zenigata\Testing\Http\FakeUri;

/**
 * Unit test for {@see FakeUri}.
 *
 * Verifies the behavior of the fake PSR-7 URI implementation.
 * 
 * Covered cases:
 *
 * - Default values for scheme, host, path, port, user info, query, fragment, and string representation.
 * - Return constructor-provided values for all URI components.
 * - Correct formatting of the authority component, including user info and port.
 * - Build the full URI string from all components.
 * - Immutability of `with*` methods, which return modified clones with updated components.
 * - Handle of `withUserInfo()` when the password is omitted.
 */
#[CoversClass(FakeUri::class)]
final class FakeUriTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $uri = new FakeUri();

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('localhost', $uri->getHost());
        $this->assertSame('/', $uri->getPath());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        $this->assertSame('http://localhost/', (string) $uri);
    }

    #[Test]
    public function gettersReturnConstructorValues(): void
    {
        $uri = new FakeUri(
            scheme: 'https',
            host: 'example.com',
            path: '/foo/bar',
            port: 8080,
            userInfo: 'user:pass',
            query: 'a=1&b=2',
            fragment: 'section1'
        );

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('a=1&b=2', $uri->getQuery());
        $this->assertSame('section1', $uri->getFragment());
    }

    #[Test]
    public function getAuthorityWithUserInfoAndPort(): void
    {
        $uri = new FakeUri(
            host: 'example.com',
            userInfo: 'user:pass',
            port: 8080
        );

        $this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
    }

    #[Test]
    public function toStringBuildsFullUri(): void
    {
        $uri = new FakeUri(
            scheme: 'https',
            host: 'example.com',
            path: '/test',
            port: 443,
            userInfo: 'user:pass',
            query: 'foo=1',
            fragment: 'end'
        );

        $this->assertSame('https://user:pass@example.com:443/test?foo=1#end', (string) $uri);
    }

    #[Test]
    public function withMethodsReturnModifiedClones(): void
    {
        $original = new FakeUri();

        $modified = $original
            ->withScheme('ftp')
            ->withHost('ftp.example.com')
            ->withPort(21)
            ->withPath('/test')
            ->withUserInfo('anon', 'pass')
            ->withQuery('file=foo')
            ->withFragment('download');

        $this->assertNotSame($original, $modified);

        $this->assertSame('ftp', $modified->getScheme());
        $this->assertSame('ftp.example.com', $modified->getHost());
        $this->assertSame(21, $modified->getPort());
        $this->assertSame('/test', $modified->getPath());
        $this->assertSame('anon:pass', $modified->getUserInfo());
        $this->assertSame('file=foo', $modified->getQuery());
        $this->assertSame('download', $modified->getFragment());
    }

    #[Test]
    public function withUserInfoHandlesNullPassword(): void
    {
        $uri = new FakeUri()->withUserInfo('user');

        $this->assertSame('user', $uri->getUserInfo());
    }
}