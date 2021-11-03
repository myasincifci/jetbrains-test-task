<?php

namespace Symfony\Component\Mailer\Tests\Transport\Smtp;

use Symfony\Component\Mailer\Test\TransportFactoryTestCase;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;

class EsmtpTransportFactoryTest extends TransportFactoryTestCase
{
    public function getFactory(): TransportFactoryInterface
    {
        return new EsmtpTransportFactory($this->getDispatcher(), $this->getClient(), $this->getLogger());
    }

    public function supportsProvider(): iterable
    {
        yield [
            new Dsn('smtp', 'example.com'),
            true,
        ];

        yield [
            new Dsn('smtps', 'example.com'),
            true,
        ];

        yield [
            new Dsn('api', 'example.com'),
            false,
        ];
    }

    public function createProvider(): iterable
    {
        $eventDispatcher = $this->getDispatcher();
        $logger = $this->getLogger();

        $transport = new EsmtpTransport('localhost', 25, false, $eventDispatcher, $logger);

        yield [
            new Dsn('smtp', 'localhost'),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 99, true, $eventDispatcher, $logger);
        $transport->setUsername(self::USER);
        $transport->setPassword(self::PASSWORD);

        yield [
            new Dsn('smtps', 'example.com', self::USER, self::PASSWORD, 99),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 465, true, $eventDispatcher, $logger);

        yield [
            new Dsn('smtps', 'example.com'),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 465, true, $eventDispatcher, $logger);

        yield [
            new Dsn('smtps', 'example.com', '', '', 465),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 465, true, $eventDispatcher, $logger);
        /** @var SocketStream $stream */
        $stream = $transport->getStream();
        $streamOptions = $stream->getStreamOptions();
        $streamOptions['ssl']['verify_peer'] = false;
        $streamOptions['ssl']['verify_peer_name'] = false;
        $stream->setStreamOptions($streamOptions);

        yield [
            new Dsn('smtps', 'example.com', '', '', 465, ['verify_peer' => false]),
            $transport,
        ];

        yield [
            new Dsn('smtps', 'example.com', '', '', 465, ['verify_peer' => 'false']),
            $transport,
        ];

        yield [
            Dsn::fromString('smtps://:@example.com?verify_peer=0'),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 465, true, $eventDispatcher, $logger);

        yield [
            Dsn::fromString('smtps://:@example.com?verify_peer='),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 465, true, $eventDispatcher, $logger);
        $transport->setLocalDomain('example.com');

        yield [
            new Dsn('smtps', 'example.com', '', '', 465, ['local_domain' => 'example.com']),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 465, true, $eventDispatcher, $logger);
        $transport->setRestartThreshold(10, 1);

        yield [
            new Dsn('smtps', 'example.com', '', '', 465, ['restart_threshold' => '10', 'restart_threshold_sleep' => '1']),
            $transport,
        ];

        $transport = new EsmtpTransport('example.com', 465, true, $eventDispatcher, $logger);
        $transport->setPingThreshold(10);

        yield [
            new Dsn('smtps', 'example.com', '', '', 465, ['ping_threshold' => '10']),
            $transport,
        ];
    }
}
