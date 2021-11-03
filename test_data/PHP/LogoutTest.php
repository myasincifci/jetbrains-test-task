<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LogoutTest extends AbstractWebTestCase
{
    public function testCsrfTokensAreClearedOnLogout()
    {
        $client = $this->createClient(['enable_authenticator_manager' => true, 'test_case' => 'LogoutWithoutSessionInvalidation', 'root_config' => 'config.yml']);
        $client->disableReboot();
        $this->callInRequestContext($client, function () {
            static::getContainer()->get('security.csrf.token_storage')->setToken('foo', 'bar');
        });

        $client->request('POST', '/login', [
            '_username' => 'johannes',
            '_password' => 'test',
        ]);

        $this->callInRequestContext($client, function () {
            $this->assertTrue(static::getContainer()->get('security.csrf.token_storage')->hasToken('foo'));
            $this->assertSame('bar', static::getContainer()->get('security.csrf.token_storage')->getToken('foo'));
        });

        $client->request('GET', '/logout');

        $this->callInRequestContext($client, function () {
            $this->assertFalse(static::getContainer()->get('security.csrf.token_storage')->hasToken('foo'));
        });
    }

    /**
     * @group legacy
     */
    public function testLegacyCsrfTokensAreClearedOnLogout()
    {
        $client = $this->createClient(['enable_authenticator_manager' => false, 'test_case' => 'LogoutWithoutSessionInvalidation', 'root_config' => 'config.yml']);
        $client->disableReboot();
        $this->callInRequestContext($client, function () {
            static::getContainer()->get('security.csrf.token_storage')->setToken('foo', 'bar');
        });

        $client->request('POST', '/login', [
            '_username' => 'johannes',
            '_password' => 'test',
        ]);

        $this->callInRequestContext($client, function () {
            $this->assertTrue(static::getContainer()->get('security.csrf.token_storage')->hasToken('foo'));
            $this->assertSame('bar', static::getContainer()->get('security.csrf.token_storage')->getToken('foo'));
        });

        $client->request('GET', '/logout');

        $this->callInRequestContext($client, function () {
            $this->assertFalse(static::getContainer()->get('security.csrf.token_storage')->hasToken('foo'));
        });
    }

    public function testAccessControlDoesNotApplyOnLogout()
    {
        $client = $this->createClient(['enable_authenticator_manager' => true, 'test_case' => 'Logout', 'root_config' => 'config_access.yml']);

        $client->request('POST', '/login', ['_username' => 'johannes', '_password' => 'test']);
        $client->request('GET', '/logout');

        $this->assertRedirect($client->getResponse(), '/');
    }

    /**
     * @group legacy
     */
    public function testLegacyAccessControlDoesNotApplyOnLogout()
    {
        $client = $this->createClient(['enable_authenticator_manager' => false, 'test_case' => 'Logout', 'root_config' => 'config_access.yml']);

        $client->request('POST', '/login', ['_username' => 'johannes', '_password' => 'test']);
        $client->request('GET', '/logout');

        $this->assertRedirect($client->getResponse(), '/');
    }

    public function testCookieClearingOnLogout()
    {
        $client = $this->createClient(['test_case' => 'Logout', 'root_config' => 'config_cookie_clearing.yml']);

        $cookieJar = $client->getCookieJar();
        $cookieJar->set(new Cookie('flavor', 'chocolate', strtotime('+1 day'), null, 'somedomain'));

        $client->request('POST', '/login', ['_username' => 'johannes', '_password' => 'test']);
        $client->request('GET', '/logout');

        $this->assertRedirect($client->getResponse(), '/');
        $this->assertNull($cookieJar->get('flavor'));
    }

    private function callInRequestContext(KernelBrowser $client, callable $callable): void
    {
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        $wrappedCallable = function (RequestEvent $event) use (&$callable) {
            $callable();
            $event->setResponse(new Response(''));
            $event->stopPropagation();
        };

        $eventDispatcher->addListener(KernelEvents::REQUEST, $wrappedCallable);
        try {
            $client->request('GET', '/'.uniqid('', true));
        } finally {
            $eventDispatcher->removeListener(KernelEvents::REQUEST, $wrappedCallable);
        }
    }
}
