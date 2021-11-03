<?php

namespace Symfony\Component\Notifier\Bridge\GatewayApi\Tests;

use Symfony\Component\Notifier\Bridge\GatewayApi\GatewayApiTransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

/**
 * @author Piergiuseppe Longo <piergiuseppe.longo@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class GatewayApiTransportFactoryTest extends TransportFactoryTestCase
{
    /**
     * @return GatewayApiTransportFactory
     */
    public function createFactory(): TransportFactoryInterface
    {
        return new GatewayApiTransportFactory();
    }

    public function createProvider(): iterable
    {
        yield [
            'gatewayapi://gatewayapi.com?from=Symfony',
            'gatewayapi://token@default?from=Symfony',
        ];
    }

    public function supportsProvider(): iterable
    {
        yield [true, 'gatewayapi://token@host.test?from=Symfony'];
        yield [false, 'somethingElse://token@default?from=Symfony'];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield 'missing token' => ['gatewayapi://host.test?from=Symfony'];
    }

    public function missingRequiredOptionProvider(): iterable
    {
        yield 'missing option: from' => ['gatewayapi://token@host.test'];
    }
}
