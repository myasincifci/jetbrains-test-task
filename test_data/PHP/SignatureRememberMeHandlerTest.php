<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Tests\RememberMe;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Core\Signature\Exception\InvalidSignatureException;
use Symfony\Component\Security\Core\Signature\SignatureHasher;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Http\RememberMe\RememberMeDetails;
use Symfony\Component\Security\Http\RememberMe\ResponseListener;
use Symfony\Component\Security\Http\RememberMe\SignatureRememberMeHandler;

class SignatureRememberMeHandlerTest extends TestCase
{
    private $signatureHasher;
    private $userProvider;
    private $request;
    private $requestStack;
    private $handler;

    protected function setUp(): void
    {
        $this->signatureHasher = $this->createMock(SignatureHasher::class);
        $this->userProvider = new InMemoryUserProvider();
        $user = new InMemoryUser('wouter', null);
        $this->userProvider->createUser($user);
        $this->requestStack = new RequestStack();
        $this->request = Request::create('/login');
        $this->requestStack->push($this->request);
        $this->handler = new SignatureRememberMeHandler($this->signatureHasher, $this->userProvider, $this->requestStack, []);
    }

    /**
     * @group time-sensitive
     */
    public function testCreateRememberMeCookie()
    {
        ClockMock::register(SignatureRememberMeHandler::class);

        $user = new InMemoryUser('wouter', null);
        $this->signatureHasher->expects($this->once())->method('computeSignatureHash')->with($user, $expire = time() + 31536000)->willReturn('abc');

        $this->handler->createRememberMeCookie($user);

        $this->assertTrue($this->request->attributes->has(ResponseListener::COOKIE_ATTR_NAME));

        /** @var Cookie $cookie */
        $cookie = $this->request->attributes->get(ResponseListener::COOKIE_ATTR_NAME);
        $this->assertEquals(base64_encode(InMemoryUser::class.':d291dGVy:'.$expire.':abc'), $cookie->getValue());
    }

    public function testClearRememberMeCookie()
    {
        $this->handler->clearRememberMeCookie();

        $this->assertTrue($this->request->attributes->has(ResponseListener::COOKIE_ATTR_NAME));

        /** @var Cookie $cookie */
        $cookie = $this->request->attributes->get(ResponseListener::COOKIE_ATTR_NAME);
        $this->assertNull($cookie->getValue());
    }

    /**
     * @group time-sensitive
     */
    public function testConsumeRememberMeCookieValid()
    {
        $this->signatureHasher->expects($this->once())->method('verifySignatureHash')->with($user = new InMemoryUser('wouter', null), 360, 'signature');
        $this->signatureHasher->expects($this->any())
            ->method('computeSignatureHash')
            ->with($user, $expire = time() + 31536000)
            ->willReturn('newsignature');

        $rememberMeDetails = new RememberMeDetails(InMemoryUser::class, 'wouter', 360, 'signature');
        $this->handler->consumeRememberMeCookie($rememberMeDetails);

        $this->assertTrue($this->request->attributes->has(ResponseListener::COOKIE_ATTR_NAME));

        /** @var Cookie $cookie */
        $cookie = $this->request->attributes->get(ResponseListener::COOKIE_ATTR_NAME);
        $this->assertEquals((new RememberMeDetails(InMemoryUser::class, 'wouter', $expire, 'newsignature'))->toString(), $cookie->getValue());
    }

    public function testConsumeRememberMeCookieInvalidHash()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The cookie\'s hash is invalid.');

        $this->signatureHasher->expects($this->any())
            ->method('verifySignatureHash')
            ->with(new InMemoryUser('wouter', null), 360, 'badsignature')
            ->will($this->throwException(new InvalidSignatureException()));

        $this->handler->consumeRememberMeCookie(new RememberMeDetails(InMemoryUser::class, 'wouter', 360, 'badsignature'));
    }

    public function testConsumeRememberMeCookieExpired()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('The cookie has expired.');

        $this->signatureHasher->expects($this->any())
            ->method('verifySignatureHash')
            ->with(new InMemoryUser('wouter', null), 360, 'signature')
            ->will($this->throwException(new ExpiredSignatureException()));

        $this->handler->consumeRememberMeCookie(new RememberMeDetails(InMemoryUser::class, 'wouter', 360, 'signature'));
    }
}
