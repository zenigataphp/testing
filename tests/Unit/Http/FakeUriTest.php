<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\UriInterface;
use Zenigata\Testing\Http\FakeUri;

/**
 * Unit test for {@see FakeUri}.
 * 
 * Covered cases:
 *
 * - Default state and string representation.
 * - Correct formatting of the authority component, including user info and port.
 * - Build the full URI string from all components.
 * - Immutability of `with*` methods, which return modified clones with updated components.
 * - Handle of `withUserInfo()` when the password is omitted.
 * - Instance created from parsed URI string.
 */
#[CoversClass(FakeUri::class)]
final class FakeUriTest extends TestCase
{
    public function testDefaults(): void
    {
        $uri = new FakeUri();

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('/', $uri->getPath());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        $this->assertSame('/', (string) $uri);
    }

    public function testGettersReturnConstructorValues(): void
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

    public function testGetAuthorityWithUserInfoAndPort(): void
    {
        $uri = new FakeUri(
            host: 'example.com',
            userInfo: 'user:pass',
            port: 8080
        );

        $this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
    }

    public function testToStringBuildsFullUri(): void
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

    public function testWithMethodsReturnModifiedClones(): void
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

    public function testWithUserInfoHandlesNullPassword(): void
    {
        $uri = (new FakeUri())->withUserInfo('user');

        $this->assertSame('user', $uri->getUserInfo());
    }

    public function testConstructorAndFromStringProduceEquivalentInstances(): void
    {
        $fromContructor = new FakeUri(
            scheme: 'https',
            host: 'example.com',
            path: '/foo/bar',
            port: 8080,
            userInfo: 'user:pass',
            query: 'x=1',
            fragment: 'frag',
        );

        $fromString = FakeUri::fromString('https://user:pass@example.com:8080/foo/bar?x=1#frag');

        $this->assertSame($fromContructor->getScheme(),   $fromString->getScheme());
        $this->assertSame($fromContructor->getHost(),     $fromString->getHost());
        $this->assertSame($fromContructor->getPort(),     $fromString->getPort());
        $this->assertSame($fromContructor->getUserInfo(), $fromString->getUserInfo());
        $this->assertSame($fromContructor->getPath(),     $fromString->getPath());
        $this->assertSame($fromContructor->getQuery(),    $fromString->getQuery());
        $this->assertSame($fromContructor->getFragment(), $fromString->getFragment());

        $this->assertSame((string) $fromContructor, (string) $fromString);
    }

    public function testFromStringBuildsCompleteUri(): void
    {
        $original = 'https://user:pass@example.com:8080/foo/bar?x=1#frag';

        $uri = FakeUri::fromString($original);

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('/foo/bar', $uri->getPath());
        $this->assertSame('x=1', $uri->getQuery());
        $this->assertSame('frag', $uri->getFragment());

        $this->assertSame($original, (string) $uri);
    }

    public function testFromStringWithMinimalUri(): void
    {
        $uri = FakeUri::fromString('http://localhost');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('localhost', $uri->getHost());
        $this->assertSame('/', $uri->getPath());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
    }

    public function testFromStringWithoutSchemeUsesDefaults(): void
    {
        $uri = FakeUri::fromString('example.com/foo');

        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('example.com/foo', $uri->getPath());
    }

    public function testFromStringHandlesQueryAndFragmentOnly(): void
    {
        $uri = FakeUri::fromString('/path?key=value#frag');

        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('key=value', $uri->getQuery());
        $this->assertSame('frag', $uri->getFragment());
    }

    public function testFromStringWithUserWithoutPassword(): void
    {
        $uri = FakeUri::fromString('https://user@example.org/foo');

        $this->assertSame('user', $uri->getUserInfo());
        $this->assertSame('example.org', $uri->getHost());
    }

    public function testFromStringWithPortOnly(): void
    {
        $uri = FakeUri::fromString('http://example.org:9090/');

        $this->assertSame(9090, $uri->getPort());
        $this->assertSame('example.org', $uri->getHost());
    }

    public function testFromStringWithNoPathDefaultsToSlash(): void
    {
        $uri = FakeUri::fromString('https://example.org');

        $this->assertSame('/', $uri->getPath());
    }

    public function testFromStringThrowsOnInvalidUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to parse URI string');

        FakeUri::fromString('http:///bad//uri:://');
    }
}