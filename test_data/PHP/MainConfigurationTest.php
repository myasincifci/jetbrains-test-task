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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\SecurityBundle\DependencyInjection\MainConfiguration;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class MainConfigurationTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * The minimal, required config needed to not have any required validation
     * issues.
     */
    protected static $minimalConfig = [
        'providers' => [
            'stub' => [
                'id' => 'foo',
            ],
        ],
        'firewalls' => [
            'stub' => [],
        ],
    ];

    public function testNoConfigForProvider()
    {
        $this->expectException(InvalidConfigurationException::class);
        $config = [
            'providers' => [
                'stub' => [],
            ],
        ];

        $processor = new Processor();
        $configuration = new MainConfiguration([], []);
        $processor->processConfiguration($configuration, [$config]);
    }

    public function testManyConfigForProvider()
    {
        $this->expectException(InvalidConfigurationException::class);
        $config = [
            'providers' => [
                'stub' => [
                    'id' => 'foo',
                    'chain' => [],
                ],
            ],
        ];

        $processor = new Processor();
        $configuration = new MainConfiguration([], []);
        $processor->processConfiguration($configuration, [$config]);
    }

    public function testCsrfAliases()
    {
        $config = [
            'firewalls' => [
                'stub' => [
                    'logout' => [
                        'csrf_token_generator' => 'a_token_generator',
                        'csrf_token_id' => 'a_token_id',
                    ],
                ],
            ],
        ];
        $config = array_merge(static::$minimalConfig, $config);

        $processor = new Processor();
        $configuration = new MainConfiguration([], []);
        $processedConfig = $processor->processConfiguration($configuration, [$config]);
        $this->assertArrayHasKey('csrf_token_generator', $processedConfig['firewalls']['stub']['logout']);
        $this->assertEquals('a_token_generator', $processedConfig['firewalls']['stub']['logout']['csrf_token_generator']);
        $this->assertArrayHasKey('csrf_token_id', $processedConfig['firewalls']['stub']['logout']);
        $this->assertEquals('a_token_id', $processedConfig['firewalls']['stub']['logout']['csrf_token_id']);
    }

    public function testDefaultUserCheckers()
    {
        $processor = new Processor();
        $configuration = new MainConfiguration([], []);
        $processedConfig = $processor->processConfiguration($configuration, [static::$minimalConfig]);

        $this->assertEquals('security.user_checker', $processedConfig['firewalls']['stub']['user_checker']);
    }

    public function testUserCheckers()
    {
        $config = [
            'firewalls' => [
                'stub' => [
                    'user_checker' => 'app.henk_checker',
                ],
            ],
        ];
        $config = array_merge(static::$minimalConfig, $config);

        $processor = new Processor();
        $configuration = new MainConfiguration([], []);
        $processedConfig = $processor->processConfiguration($configuration, [$config]);

        $this->assertEquals('app.henk_checker', $processedConfig['firewalls']['stub']['user_checker']);
    }

    public function testFirewalls()
    {
        $factory = $this->createMock(AuthenticatorFactoryInterface::class);
        $factory->expects($this->once())->method('addConfiguration');
        $factory->method('getKey')->willReturn('key');

        $configuration = new MainConfiguration(['stub' => $factory], []);
        $configuration->getConfigTreeBuilder();
    }

    /**
     * @group legacy
     */
    public function testLegacyFirewalls()
    {
        $factory = $this->createMock(AuthenticatorFactoryInterface::class);
        $factory->expects($this->once())->method('addConfiguration');

        $this->expectDeprecation('Since symfony/security-bundle 5.4: Passing an array of arrays as 1st argument to "Symfony\Bundle\SecurityBundle\DependencyInjection\MainConfiguration::__construct" is deprecated, pass a sorted array of factories instead.');

        $configuration = new MainConfiguration(['http_basic' => ['stub' => $factory]], []);
        $configuration->getConfigTreeBuilder();
    }
}
