<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Tests\Firewall;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordJsonAuthenticationListener;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 */
class UsernamePasswordJsonAuthenticationListenerTest extends TestCase
{
    /**
     * @var UsernamePasswordJsonAuthenticationListener
     */
    private $listener;

    private function createListener(array $options = [], $success = true, $matchCheckPath = true, $withMockedHandler = true)
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->willReturn($matchCheckPath)
        ;
        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);

        $authenticatedToken = $this->createMock(TokenInterface::class);

        if ($success) {
            $authenticationManager->method('authenticate')->willReturn($authenticatedToken);
        } else {
            $authenticationManager->method('authenticate')->willThrowException(new AuthenticationException());
        }

        $authenticationSuccessHandler = null;
        $authenticationFailureHandler = null;

        if ($withMockedHandler) {
            $authenticationSuccessHandler = $this->createMock(AuthenticationSuccessHandlerInterface::class);
            $authenticationSuccessHandler->method('onAuthenticationSuccess')->willReturn(new Response('ok'));
            $authenticationFailureHandler = $this->createMock(AuthenticationFailureHandlerInterface::class);
            $authenticationFailureHandler->method('onAuthenticationFailure')->willReturn(new Response('ko'));
        }

        $this->listener = new UsernamePasswordJsonAuthenticationListener($tokenStorage, $authenticationManager, $httpUtils, 'providerKey', $authenticationSuccessHandler, $authenticationFailureHandler, $options);
    }

    public function testHandleSuccessIfRequestContentTypeIsJson()
    {
        $this->createListener();
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "password": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertEquals('ok', $event->getResponse()->getContent());
    }

    public function testSuccessIfRequestFormatIsJsonLD()
    {
        $this->createListener();
        $request = new Request([], [], [], [], [], [], '{"username": "dunglas", "password": "foo"}');
        $request->setRequestFormat('json-ld');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertEquals('ok', $event->getResponse()->getContent());
    }

    public function testHandleFailure()
    {
        $this->createListener([], false, true, false);
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "password": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertSame(['error' => 'An authentication exception occurred.'], json_decode($event->getResponse()->getContent(), true));
    }

    public function testTranslatedHandleFailure()
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['An authentication exception occurred.' => 'foo'], 'en', 'security');

        $this->createListener([], false, true, false);
        $this->listener->setTranslator($translator);

        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "password": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertSame(['error' => 'foo'], json_decode($event->getResponse()->getContent(), true));
    }

    public function testUsePath()
    {
        $this->createListener(['username_path' => 'user.login', 'password_path' => 'user.pwd']);
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"user": {"login": "dunglas", "pwd": "foo"}}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertEquals('ok', $event->getResponse()->getContent());
    }

    public function testAttemptAuthenticationNoJson()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid JSON');
        $this->createListener();
        $request = new Request();
        $request->setRequestFormat('json');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
    }

    public function testAttemptAuthenticationNoUsername()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The key "username" must be provided');
        $this->createListener();
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"usr": "dunglas", "password": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
    }

    public function testAttemptAuthenticationNoPassword()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The key "password" must be provided');
        $this->createListener();
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "pass": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
    }

    public function testAttemptAuthenticationUsernameNotAString()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The key "username" must be a string.');
        $this->createListener();
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": 1, "password": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
    }

    public function testAttemptAuthenticationPasswordNotAString()
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('The key "password" must be a string.');
        $this->createListener();
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "password": 1}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
    }

    public function testAttemptAuthenticationUsernameTooLong()
    {
        $this->createListener();
        $username = str_repeat('x', Security::MAX_USERNAME_LENGTH + 1);
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], sprintf('{"username": "%s", "password": 1}', $username));
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertSame('ko', $event->getResponse()->getContent());
    }

    public function testDoesNotAttemptAuthenticationIfRequestPathDoesNotMatchCheckPath()
    {
        $this->createListener(['check_path' => '/'], true, false);
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json']);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);
        $event->setResponse(new Response('original'));

        ($this->listener)($event);
        $this->assertSame('original', $event->getResponse()->getContent());
    }

    public function testDoesNotAttemptAuthenticationIfRequestContentTypeIsNotJson()
    {
        $this->createListener();
        $request = new Request([], [], [], [], [], [], '{"username": "dunglas", "password": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);
        $event->setResponse(new Response('original'));

        ($this->listener)($event);
        $this->assertSame('original', $event->getResponse()->getContent());
    }

    public function testAttemptAuthenticationIfRequestPathMatchesCheckPath()
    {
        $this->createListener(['check_path' => '/']);
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "password": "foo"}');
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertSame('ok', $event->getResponse()->getContent());
    }

    public function testNoErrorOnMissingSessionStrategy()
    {
        $this->createListener();
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "password": "foo"}');
        $this->configurePreviousSession($request);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        ($this->listener)($event);
        $this->assertEquals('ok', $event->getResponse()->getContent());
    }

    public function testMigratesViaSessionStrategy()
    {
        $this->createListener();
        $request = new Request([], [], [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], '{"username": "dunglas", "password": "foo"}');
        $this->configurePreviousSession($request);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, KernelInterface::MAIN_REQUEST);

        $sessionStrategy = $this->createMock(SessionAuthenticationStrategyInterface::class);
        $sessionStrategy->expects($this->once())
            ->method('onAuthentication')
            ->with($request, $this->isInstanceOf(TokenInterface::class));
        $this->listener->setSessionAuthenticationStrategy($sessionStrategy);

        ($this->listener)($event);
        $this->assertEquals('ok', $event->getResponse()->getContent());
    }

    private function configurePreviousSession(Request $request)
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->any())
            ->method('getName')
            ->willReturn('test_session_name');
        $request->setSession($session);
        $request->cookies->set('test_session_name', 'session_cookie_val');
    }
}
