<?php

namespace Symfony\Bundle\SecurityBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class RememberMeCookieTest extends AbstractWebTestCase
{
    /** @dataProvider getSessionRememberMeSecureCookieFlagAutoHttpsMap */
    public function testSessionRememberMeSecureCookieFlagAuto($https, $expectedSecureFlag)
    {
        $client = $this->createClient(['test_case' => 'RememberMeCookie', 'root_config' => 'config.yml']);

        $client->request('POST', '/login', [
            '_username' => 'test',
            '_password' => 'test',
        ], [], [
             'HTTPS' => (int) $https,
        ]);

        $cookies = $client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        $this->assertSame($expectedSecureFlag, $cookies['']['/']['REMEMBERME']->isSecure());
    }

    /**
     * @dataProvider getSessionRememberMeSecureCookieFlagAutoHttpsMap
     * @group legacy
     */
    public function testLegacySessionRememberMeSecureCookieFlagAuto($https, $expectedSecureFlag)
    {
        $client = $this->createClient(['test_case' => 'RememberMeCookie', 'root_config' => 'legacy_config.yml']);

        $client->request('POST', '/login', [
            '_username' => 'test',
            '_password' => 'test',
        ], [], [
            'HTTPS' => (int) $https,
        ]);

        $cookies = $client->getResponse()->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        $this->assertSame($expectedSecureFlag, $cookies['']['/']['REMEMBERME']->isSecure());
    }

    public function getSessionRememberMeSecureCookieFlagAutoHttpsMap()
    {
        return [
            [true, true],
            [false, false],
        ];
    }
}
