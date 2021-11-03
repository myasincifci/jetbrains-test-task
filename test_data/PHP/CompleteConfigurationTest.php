<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\Pbkdf2PasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\SodiumPasswordHasher;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Strategy\AffirmativeStrategy;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\SodiumPasswordEncoder;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

abstract class CompleteConfigurationTest extends TestCase
{
    abstract protected function getLoader(ContainerBuilder $container);

    abstract protected function getFileExtension();

    public function testAuthenticatorManager()
    {
        $container = $this->getContainer('authenticator_manager');

        $authenticatorManager = $container->getDefinition('security.authenticator.manager.main');
        $this->assertEquals(AuthenticatorManager::class, $authenticatorManager->getClass());

        // required badges
        $this->assertEquals([CsrfTokenBadge::class, RememberMeBadge::class], $authenticatorManager->getArgument(7));

        // login link
        $expiredStorage = $container->getDefinition($expiredStorageId = 'security.authenticator.expired_login_link_storage.main');
        $this->assertEquals('cache.redis', (string) $expiredStorage->getArgument(0));
        $this->assertEquals(3600, (string) $expiredStorage->getArgument(1));

        $linker = $container->getDefinition($linkerId = 'security.authenticator.login_link_handler.main');
        $this->assertEquals([
            'route_name' => 'login_check',
            'lifetime' => 3600,
        ], $linker->getArgument(3));

        $hasher = $container->getDefinition((string) $linker->getArgument(2));
        $this->assertEquals(['id', 'email'], $hasher->getArgument(1));
        $this->assertEquals($expiredStorageId, (string) $hasher->getArgument(3));
        $this->assertEquals(1, $hasher->getArgument(4));

        $authenticator = $container->getDefinition('security.authenticator.login_link.main');
        $this->assertEquals($linkerId, (string) $authenticator->getArgument(0));
        $this->assertEquals([
            'check_route' => 'login_check',
            'check_post_only' => true,
        ], $authenticator->getArgument(4));

        // login throttling
        $listener = $container->getDefinition('security.listener.login_throttling.main');
        $this->assertEquals('app.rate_limiter', (string) $listener->getArgument(1));
    }

    public function testRolesHierarchy()
    {
        $container = $this->getContainer('container1');
        $this->assertEquals([
            'ROLE_ADMIN' => ['ROLE_USER'],
            'ROLE_SUPER_ADMIN' => ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
            'ROLE_REMOTE' => ['ROLE_USER', 'ROLE_ADMIN'],
        ], $container->getParameter('security.role_hierarchy.roles'));
    }

    public function testUserProviders()
    {
        $container = $this->getContainer('container1');

        $providers = array_values(array_filter($container->getServiceIds(), function ($key) { return str_starts_with($key, 'security.user.provider.concrete'); }));

        $expectedProviders = [
            'security.user.provider.concrete.default',
            'security.user.provider.concrete.digest',
            'security.user.provider.concrete.basic',
            'security.user.provider.concrete.service',
            'security.user.provider.concrete.chain',
        ];

        $this->assertEquals([], array_diff($expectedProviders, $providers));
        $this->assertEquals([], array_diff($providers, $expectedProviders));

        // chain provider
        $this->assertEquals([new IteratorArgument([
            new Reference('user.manager'),
            new Reference('security.user.provider.concrete.basic'),
        ])], $container->getDefinition('security.user.provider.concrete.chain')->getArguments());
    }

    public function testFirewalls()
    {
        $container = $this->getContainer('container1');
        $arguments = $container->getDefinition('security.firewall.map')->getArguments();
        $listeners = [];
        $configs = [];
        foreach (array_keys($arguments[1]->getValues()) as $contextId) {
            $contextDef = $container->getDefinition($contextId);
            $arguments = $contextDef->getArguments();
            $listeners[] = array_map('strval', $arguments[0]->getValues());

            $configDef = $container->getDefinition((string) $arguments[3]);
            $configs[] = array_values($configDef->getArguments());
        }

        // the IDs of the services are case sensitive or insensitive depending on
        // the Symfony version. Transform them to lowercase to simplify tests.
        $configs[0][2] = strtolower($configs[0][2]);
        $configs[2][2] = strtolower($configs[2][2]);

        $this->assertEquals([
            [
                'simple',
                'security.user_checker',
                '.security.request_matcher.xmi9dcw',
                false,
                false,
                '',
                '',
                '',
                '',
                '',
                [],
                null,
            ],
            [
                'secure',
                'security.user_checker',
                null,
                true,
                true,
                'security.user.provider.concrete.default',
                null,
                'security.authenticator.form_login.secure',
                null,
                null,
                [
                    'switch_user',
                    'x509',
                    'remote_user',
                    'form_login',
                    'http_basic',
                    'remember_me',
                ],
                [
                    'parameter' => '_switch_user',
                    'role' => 'ROLE_ALLOWED_TO_SWITCH',
                ],
            ],
            [
                'host',
                'security.user_checker',
                '.security.request_matcher.iw4hyjb',
                true,
                false,
                'security.user.provider.concrete.default',
                'host',
                'security.authenticator.http_basic.host',
                null,
                null,
                [
                    'http_basic',
                ],
                null,
            ],
            [
                'with_user_checker',
                'app.user_checker',
                null,
                true,
                false,
                'security.user.provider.concrete.default',
                'with_user_checker',
                'security.authenticator.http_basic.with_user_checker',
                null,
                null,
                [
                    'http_basic',
                ],
                null,
            ],
        ], $configs);

        $this->assertEquals([
            [],
            [
                'security.channel_listener',
                'security.firewall.authenticator.secure',
                'security.authentication.switchuser_listener.secure',
                'security.access_listener',
            ],
            [
                'security.channel_listener',
                'security.context_listener.0',
                'security.firewall.authenticator.host',
                'security.access_listener',
            ],
            [
                'security.channel_listener',
                'security.context_listener.1',
                'security.firewall.authenticator.with_user_checker',
                'security.access_listener',
            ],
        ], $listeners);

        $this->assertFalse($container->hasAlias('Symfony\Component\Security\Core\User\UserCheckerInterface', 'No user checker alias is registered when custom user checker services are registered'));
    }

    /**
     * @group legacy
     */
    public function testLegacyFirewalls()
    {
        $container = $this->getContainer('legacy_container1');
        $arguments = $container->getDefinition('security.firewall.map')->getArguments();
        $listeners = [];
        $configs = [];
        foreach (array_keys($arguments[1]->getValues()) as $contextId) {
            $contextDef = $container->getDefinition($contextId);
            $arguments = $contextDef->getArguments();
            $listeners[] = array_map('strval', $arguments[0]->getValues());

            $configDef = $container->getDefinition((string) $arguments[3]);
            $configs[] = array_values($configDef->getArguments());
        }

        // the IDs of the services are case sensitive or insensitive depending on
        // the Symfony version. Transform them to lowercase to simplify tests.
        $configs[0][2] = strtolower($configs[0][2]);
        $configs[2][2] = strtolower($configs[2][2]);

        $this->assertEquals([
            [
                'simple',
                'security.user_checker',
                '.security.request_matcher.xmi9dcw',
                false,
                false,
                '',
                '',
                '',
                '',
                '',
                [],
                null,
            ],
            [
                'secure',
                'security.user_checker',
                null,
                true,
                true,
                'security.user.provider.concrete.default',
                null,
                'security.authentication.form_entry_point.secure',
                null,
                null,
                [
                    'switch_user',
                    'x509',
                    'remote_user',
                    'form_login',
                    'http_basic',
                    'remember_me',
                    'anonymous',
                ],
                [
                    'parameter' => '_switch_user',
                    'role' => 'ROLE_ALLOWED_TO_SWITCH',
                ],
            ],
            [
                'host',
                'security.user_checker',
                '.security.request_matcher.iw4hyjb',
                true,
                false,
                'security.user.provider.concrete.default',
                'host',
                'security.authentication.basic_entry_point.host',
                null,
                null,
                [
                    'http_basic',
                    'anonymous',
                ],
                null,
            ],
            [
                'with_user_checker',
                'app.user_checker',
                null,
                true,
                false,
                'security.user.provider.concrete.default',
                'with_user_checker',
                'security.authentication.basic_entry_point.with_user_checker',
                null,
                null,
                [
                    'http_basic',
                    'anonymous',
                ],
                null,
            ],
        ], $configs);

        $this->assertEquals([
            [],
            [
                'security.channel_listener',
                'security.authentication.listener.x509.secure',
                'security.authentication.listener.remote_user.secure',
                'security.authentication.listener.form.secure',
                'security.authentication.listener.basic.secure',
                'security.authentication.listener.rememberme.secure',
                'security.authentication.listener.anonymous.secure',
                'security.authentication.switchuser_listener.secure',
                'security.access_listener',
            ],
            [
                'security.channel_listener',
                'security.context_listener.0',
                'security.authentication.listener.basic.host',
                'security.authentication.listener.anonymous.host',
                'security.access_listener',
            ],
            [
                'security.channel_listener',
                'security.context_listener.1',
                'security.authentication.listener.basic.with_user_checker',
                'security.authentication.listener.anonymous.with_user_checker',
                'security.access_listener',
            ],
        ], $listeners);

        $this->assertFalse($container->hasAlias('Symfony\Component\Security\Core\User\UserCheckerInterface', 'No user checker alias is registered when custom user checker services are registered'));
    }

    public function testFirewallRequestMatchers()
    {
        $container = $this->getContainer('container1');

        $arguments = $container->getDefinition('security.firewall.map')->getArguments();
        $matchers = [];

        foreach ($arguments[1]->getValues() as $reference) {
            if ($reference instanceof Reference) {
                $definition = $container->getDefinition((string) $reference);
                $matchers[] = $definition->getArguments();
            }
        }

        $this->assertEquals([
            [
                '/login',
            ],
            [
                '/test',
                'foo\\.example\\.org',
                ['GET', 'POST'],
            ],
        ], $matchers);
    }

    public function testUserCheckerAliasIsRegistered()
    {
        $container = $this->getContainer('no_custom_user_checker');

        $this->assertTrue($container->hasAlias('Symfony\Component\Security\Core\User\UserCheckerInterface', 'Alias for user checker is registered when no custom user checker service is registered'));
        $this->assertFalse($container->getAlias('Symfony\Component\Security\Core\User\UserCheckerInterface')->isPublic());
    }

    public function testAccess()
    {
        $container = $this->getContainer('container1');

        $rules = [];
        foreach ($container->getDefinition('security.access_map')->getMethodCalls() as $call) {
            if ('add' == $call[0]) {
                $rules[] = [(string) $call[1][0], $call[1][1], $call[1][2]];
            }
        }

        $matcherIds = [];
        foreach ($rules as [$matcherId, $attributes, $channel]) {
            $requestMatcher = $container->getDefinition($matcherId);

            $this->assertArrayNotHasKey($matcherId, $matcherIds);
            $matcherIds[$matcherId] = true;

            $i = \count($matcherIds);
            if (1 === $i) {
                $this->assertEquals(['ROLE_USER'], $attributes);
                $this->assertEquals('https', $channel);
                $this->assertEquals(
                    ['/blog/524', null, ['GET', 'POST'], [], [], null, 8000],
                    $requestMatcher->getArguments()
                );
            } elseif (2 === $i) {
                $this->assertEquals(['IS_AUTHENTICATED_ANONYMOUSLY'], $attributes);
                $this->assertNull($channel);
                $this->assertEquals(
                    ['/blog/.*'],
                    $requestMatcher->getArguments()
                );
            } elseif (3 === $i) {
                $this->assertEquals('IS_AUTHENTICATED_ANONYMOUSLY', $attributes[0]);
                $expression = $container->getDefinition((string) $attributes[1])->getArgument(0);
                $this->assertEquals("token.getUserIdentifier() matches '/^admin/'", $expression);
            }
        }
    }

    public function testMerge()
    {
        $container = $this->getContainer('merge');

        $this->assertEquals([
            'FOO' => ['MOO'],
            'ADMIN' => ['USER'],
        ], $container->getParameter('security.role_hierarchy.roles'));
    }

    /**
     * @group legacy
     */
    public function testEncoders()
    {
        $container = $this->getContainer('legacy_encoders');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder',
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.encoder.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder',
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\NativePasswordEncoder',
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'algorithm' => 'auto',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
        ]], $container->getDefinition('security.encoder_factory.generic')->getArguments());
    }

    /**
     * @group legacy
     */
    public function testEncodersWithLibsodium()
    {
        if (!SodiumPasswordEncoder::isSupported()) {
            $this->markTestSkipped('Libsodium is not available.');
        }

        $container = $this->getContainer('sodium_encoder');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder',
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.encoder.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder',
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\NativePasswordEncoder',
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\SodiumPasswordEncoder',
                'arguments' => [8, 128 * 1024 * 1024],
            ],
        ]], $container->getDefinition('security.encoder_factory.generic')->getArguments());
    }

    /**
     * @group legacy
     */
    public function testEncodersWithArgon2i()
    {
        if (!($sodium = SodiumPasswordEncoder::isSupported() && !\defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) && !\defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('Argon2i algorithm is not supported.');
        }

        $container = $this->getContainer('argon2i_encoder');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder',
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.encoder.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder',
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\NativePasswordEncoder',
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'class' => $sodium ? SodiumPasswordEncoder::class : NativePasswordEncoder::class,
                'arguments' => $sodium ? [256, 1] : [1, 262144, null, \PASSWORD_ARGON2I],
            ],
        ]], $container->getDefinition('security.encoder_factory.generic')->getArguments());
    }

    /**
     * @group legacy
     */
    public function testMigratingEncoder()
    {
        if (!($sodium = SodiumPasswordEncoder::isSupported() && !\defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) && !\defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('Argon2i algorithm is not supported.');
        }

        $container = $this->getContainer('migrating_encoder');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder',
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.encoder.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder',
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\NativePasswordEncoder',
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'algorithm' => 'argon2i',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => 256,
                'time_cost' => 1,
                'migrate_from' => ['bcrypt'],
            ],
        ]], $container->getDefinition('security.encoder_factory.generic')->getArguments());
    }

    /**
     * @group legacy
     */
    public function testEncodersWithBCrypt()
    {
        $container = $this->getContainer('bcrypt_encoder');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder',
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.encoder.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder',
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => 'Symfony\Component\Security\Core\Encoder\NativePasswordEncoder',
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'class' => NativePasswordEncoder::class,
                'arguments' => [null, null, 15, \PASSWORD_BCRYPT],
            ],
        ]], $container->getDefinition('security.encoder_factory.generic')->getArguments());
    }

    public function testHashers()
    {
        $container = $this->getContainer('container1');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => PlaintextPasswordHasher::class,
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.hasher.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => Pbkdf2PasswordHasher::class,
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => NativePasswordHasher::class,
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'algorithm' => 'auto',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
        ]], $container->getDefinition('security.password_hasher_factory')->getArguments());
    }

    public function testHashersWithLibsodium()
    {
        if (!SodiumPasswordHasher::isSupported()) {
            $this->markTestSkipped('Libsodium is not available.');
        }

        $container = $this->getContainer('sodium_hasher');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => PlaintextPasswordHasher::class,
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.hasher.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => Pbkdf2PasswordHasher::class,
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => NativePasswordHasher::class,
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'class' => SodiumPasswordHasher::class,
                'arguments' => [8, 128 * 1024 * 1024],
            ],
        ]], $container->getDefinition('security.password_hasher_factory')->getArguments());
    }

    public function testHashersWithArgon2i()
    {
        if (!($sodium = SodiumPasswordHasher::isSupported() && !\defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) && !\defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('Argon2i algorithm is not supported.');
        }

        $container = $this->getContainer('argon2i_hasher');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => PlaintextPasswordHasher::class,
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.hasher.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => Pbkdf2PasswordHasher::class,
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => NativePasswordHasher::class,
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'class' => $sodium ? SodiumPasswordHasher::class : NativePasswordHasher::class,
                'arguments' => $sodium ? [256, 1] : [1, 262144, null, \PASSWORD_ARGON2I],
            ],
        ]], $container->getDefinition('security.password_hasher_factory')->getArguments());
    }

    public function testMigratingHasher()
    {
        if (!($sodium = SodiumPasswordHasher::isSupported() && !\defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) && !\defined('PASSWORD_ARGON2I')) {
            $this->markTestSkipped('Argon2i algorithm is not supported.');
        }

        $container = $this->getContainer('migrating_hasher');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => PlaintextPasswordHasher::class,
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.hasher.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => Pbkdf2PasswordHasher::class,
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => NativePasswordHasher::class,
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'algorithm' => 'argon2i',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => 256,
                'time_cost' => 1,
                'migrate_from' => ['bcrypt'],
            ],
        ]], $container->getDefinition('security.password_hasher_factory')->getArguments());
    }

    public function testHashersWithBCrypt()
    {
        $container = $this->getContainer('bcrypt_hasher');

        $this->assertEquals([[
            'JMS\FooBundle\Entity\User1' => [
                'class' => PlaintextPasswordHasher::class,
                'arguments' => [false],
            ],
            'JMS\FooBundle\Entity\User2' => [
                'algorithm' => 'sha1',
                'encode_as_base64' => false,
                'iterations' => 5,
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User3' => [
                'algorithm' => 'md5',
                'hash_algorithm' => 'sha512',
                'key_length' => 40,
                'ignore_case' => false,
                'encode_as_base64' => true,
                'iterations' => 5000,
                'cost' => null,
                'memory_cost' => null,
                'time_cost' => null,
                'migrate_from' => [],
            ],
            'JMS\FooBundle\Entity\User4' => new Reference('security.hasher.foo'),
            'JMS\FooBundle\Entity\User5' => [
                'class' => Pbkdf2PasswordHasher::class,
                'arguments' => ['sha1', false, 5, 30],
            ],
            'JMS\FooBundle\Entity\User6' => [
                'class' => NativePasswordHasher::class,
                'arguments' => [8, 102400, 15],
            ],
            'JMS\FooBundle\Entity\User7' => [
                'class' => NativePasswordHasher::class,
                'arguments' => [null, null, 15, \PASSWORD_BCRYPT],
            ],
        ]], $container->getDefinition('security.password_hasher_factory')->getArguments());
    }

    /**
     * @group legacy
     */
    public function testLegacyRememberMeThrowExceptionsDefault()
    {
        $container = $this->getContainer('legacy_container1');
        $this->assertTrue($container->getDefinition('security.authentication.listener.rememberme.secure')->getArgument(5));
    }

    /**
     * @group legacy
     */
    public function testLegacyRememberMeThrowExceptions()
    {
        $container = $this->getContainer('legacy_remember_me_options');
        $service = $container->getDefinition('security.authentication.listener.rememberme.main');
        $this->assertEquals('security.authentication.rememberme.services.persistent.main', $service->getArgument(1));
        $this->assertFalse($service->getArgument(5));
    }

    public function testUserCheckerConfig()
    {
        $this->assertEquals('app.user_checker', $this->getContainer('container1')->getAlias('security.user_checker.with_user_checker'));
    }

    public function testUserCheckerConfigWithDefaultChecker()
    {
        $this->assertEquals('security.user_checker', $this->getContainer('container1')->getAlias('security.user_checker.host'));
    }

    public function testUserCheckerConfigWithNoCheckers()
    {
        $this->assertEquals('security.user_checker', $this->getContainer('container1')->getAlias('security.user_checker.secure'));
    }

    public function testUserPasswordHasherCommandIsRegistered()
    {
        $this->assertTrue($this->getContainer('remember_me_options')->has('security.command.user_password_hash'));
    }

    public function testDefaultAccessDecisionManagerStrategyIsAffirmative()
    {
        $container = $this->getContainer('access_decision_manager_default_strategy');

        $this->assertEquals((new Definition(AffirmativeStrategy::class, [false])), $container->getDefinition('security.access.decision_manager')->getArgument(1), 'Default vote strategy is affirmative');
    }

    public function testCustomAccessDecisionManagerService()
    {
        $container = $this->getContainer('access_decision_manager_service');

        $this->assertSame('app.access_decision_manager', (string) $container->getAlias('security.access.decision_manager'), 'The custom access decision manager service is aliased');
    }

    public function testAccessDecisionManagerServiceAndStrategyCannotBeUsedAtTheSameTime()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "security.access_decision_manager": "strategy" and "service" cannot be used together.');
        $this->getContainer('access_decision_manager_service_and_strategy');
    }

    public function testAccessDecisionManagerOptionsAreNotOverriddenByImplicitStrategy()
    {
        $container = $this->getContainer('access_decision_manager_customized_config');

        $accessDecisionManagerDefinition = $container->getDefinition('security.access.decision_manager');

        $this->assertEquals((new Definition(AffirmativeStrategy::class, [true])), $accessDecisionManagerDefinition->getArgument(1));
    }

    public function testAccessDecisionManagerWithStrategyService()
    {
        $container = $this->getContainer('access_decision_manager_strategy_service');

        $accessDecisionManagerDefinition = $container->getDefinition('security.access.decision_manager');

        $this->assertEquals(AccessDecisionManager::class, $accessDecisionManagerDefinition->getClass());
        $this->assertEquals(new Reference('app.custom_access_decision_strategy'), $accessDecisionManagerDefinition->getArgument(1));
    }

    public function testFirewallUndefinedUserProvider()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid firewall "main": user provider "undefined" not found.');
        $this->getContainer('firewall_undefined_provider');
    }

    public function testFirewallListenerUndefinedProvider()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid firewall "main": user provider "undefined" not found.');
        $this->getContainer('listener_undefined_provider');
    }

    public function testFirewallWithUserProvider()
    {
        $this->getContainer('firewall_provider');
        $this->addToAssertionCount(1);
    }

    public function testFirewallListenerWithProvider()
    {
        $this->getContainer('listener_provider');
        $this->addToAssertionCount(1);
    }

    protected function getContainer($file)
    {
        $file .= '.'.$this->getFileExtension();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('request_listener.http_port', 80);
        $container->setParameter('request_listener.https_port', 443);
        $container->register('cache.app', \stdClass::class);

        $security = new SecurityExtension();
        $container->registerExtension($security);

        $bundle = new SecurityBundle();
        $bundle->build($container); // Attach all default factories
        $this->getLoader($container)->load($file);

        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
