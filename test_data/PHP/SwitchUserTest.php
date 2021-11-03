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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;

class SwitchUserTest extends AbstractWebTestCase
{
    /**
     * @dataProvider getTestParameters
     */
    public function testSwitchUser($originalUser, $targetUser, $expectedUser, $expectedStatus)
    {
        $client = $this->createAuthenticatedClient($originalUser, ['root_config' => 'switchuser.yml']);

        $client->request('GET', '/profile?_switch_user='.$targetUser);

        $this->assertEquals($expectedStatus, $client->getResponse()->getStatusCode());
        $this->assertEquals($expectedUser, $client->getProfile()->getCollector('security')->getUser());
    }

    /**
     * @dataProvider getLegacyTestParameters
     */
    public function testLegacySwitchUser($originalUser, $targetUser, $expectedUser, $expectedStatus)
    {
        $client = $this->createAuthenticatedClient($originalUser, ['root_config' => 'legacy_switchuser.yml']);

        $client->request('GET', '/profile?_switch_user='.$targetUser);

        $this->assertEquals($expectedStatus, $client->getResponse()->getStatusCode());
        $this->assertEquals($expectedUser, $client->getProfile()->getCollector('security')->getUser());
    }

    /**
     * @dataProvider provideSecuritySystems
     */
    public function testSwitchedUserCanSwitchToOther(array $options)
    {
        $client = $this->createAuthenticatedClient('user_can_switch', $options);

        $client->request('GET', '/profile?_switch_user=user_cannot_switch_1');
        $client->request('GET', '/profile?_switch_user=user_cannot_switch_2');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('user_cannot_switch_2', $client->getProfile()->getCollector('security')->getUser());
    }

    /**
     * @dataProvider provideSecuritySystems
     */
    public function testSwitchedUserExit(array $options)
    {
        $client = $this->createAuthenticatedClient('user_can_switch', $options);

        $client->request('GET', '/profile?_switch_user=user_cannot_switch_1');
        $client->request('GET', '/profile?_switch_user='.SwitchUserListener::EXIT_VALUE);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('user_can_switch', $client->getProfile()->getCollector('security')->getUser());
    }

    /**
     * @dataProvider provideSecuritySystems
     */
    public function testSwitchUserStateless(array $options)
    {
        $client = $this->createClient(['test_case' => 'JsonLogin', 'root_config' => 'switchuser_stateless.yml'] + $options);
        $client->request('POST', '/chk', [], [], ['HTTP_X_SWITCH_USER' => 'dunglas', 'CONTENT_TYPE' => 'application/json'], '{"user": {"login": "user_can_switch", "password": "test"}}');
        $response = $client->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Welcome @dunglas!'], json_decode($response->getContent(), true));
        $this->assertSame('dunglas', $client->getProfile()->getCollector('security')->getUser());
    }

    public function getTestParameters()
    {
        return [
            'unauthorized_user_cannot_switch' => ['user_cannot_switch_1', 'user_cannot_switch_1', 'user_cannot_switch_1', 403],
            'authorized_user_can_switch' => ['user_can_switch', 'user_cannot_switch_1', 'user_cannot_switch_1', 200],
            'authorized_user_cannot_switch_to_non_existent' => ['user_can_switch', 'user_does_not_exist', 'user_can_switch', 403],
            'authorized_user_can_switch_to_himself' => ['user_can_switch', 'user_can_switch', 'user_can_switch', 200],
        ];
    }

    public function getLegacyTestParameters()
    {
        return [
            'legacy_unauthorized_user_cannot_switch' => ['user_cannot_switch_1', 'user_cannot_switch_1', 'user_cannot_switch_1', 403],
            'legacy_authorized_user_can_switch' => ['user_can_switch', 'user_cannot_switch_1', 'user_cannot_switch_1', 200],
            'legacy_authorized_user_cannot_switch_to_non_existent' => ['user_can_switch', 'user_does_not_exist', 'user_can_switch', 403],
            'legacy_authorized_user_can_switch_to_himself' => ['user_can_switch', 'user_can_switch', 'user_can_switch', 200],
        ];
    }

    protected function createAuthenticatedClient($username, array $options = [])
    {
        $client = $this->createClient(['test_case' => 'StandardFormLogin', 'root_config' => 'switchuser.yml'] + $options);
        $client->followRedirects(true);

        $form = $client->request('GET', '/login')->selectButton('login')->form();
        $form['_username'] = $username;
        $form['_password'] = 'test';
        $client->submit($form);

        return $client;
    }
}
