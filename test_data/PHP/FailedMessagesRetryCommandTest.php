<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Command\FailedMessagesRetryCommand;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Receiver\ListableReceiverInterface;

class FailedMessagesRetryCommandTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testBasicRun()
    {
        $receiver = $this->createMock(ListableReceiverInterface::class);
        $receiver->expects($this->exactly(2))->method('find')->withConsecutive([10], [12])->willReturn(new Envelope(new \stdClass()));
        // message will eventually be ack'ed in Worker
        $receiver->expects($this->exactly(2))->method('ack');

        $dispatcher = new EventDispatcher();
        $bus = $this->createMock(MessageBusInterface::class);
        // the bus should be called in the worker
        $bus->expects($this->exactly(2))->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $command = new FailedMessagesRetryCommand(
            'failure_receiver',
            $receiver,
            $bus,
            $dispatcher
        );

        $tester = new CommandTester($command);
        $tester->execute(['id' => [10, 12], '--force' => true]);

        $this->assertStringContainsString('[OK]', $tester->getDisplay());
    }

    public function testBasicRunWithServiceLocator()
    {
        $receiver = $this->createMock(ListableReceiverInterface::class);
        $receiver->expects($this->exactly(2))->method('find')->withConsecutive([10], [12])->willReturn(new Envelope(new \stdClass()));
        // message will eventually be ack'ed in Worker
        $receiver->expects($this->exactly(2))->method('ack');

        $dispatcher = new EventDispatcher();
        $bus = $this->createMock(MessageBusInterface::class);
        // the bus should be called in the worker
        $bus->expects($this->exactly(2))->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $failureTransportName = 'failure_receiver';
        $serviceLocator = $this->createMock(ServiceLocator::class);
        $serviceLocator->method('has')->with($failureTransportName)->willReturn(true);
        $serviceLocator->method('get')->with($failureTransportName)->willReturn($receiver);

        $command = new FailedMessagesRetryCommand(
            $failureTransportName,
            $serviceLocator,
            $bus,
            $dispatcher
        );

        $tester = new CommandTester($command);
        $tester->execute(['id' => [10, 12], '--force' => true]);

        $this->assertStringContainsString('[OK]', $tester->getDisplay());
        $this->assertStringNotContainsString('Available failure transports are:', $tester->getDisplay());
    }

    public function testBasicRunWithServiceLocatorMultipleFailedTransportsDefined()
    {
        $receiver = $this->createMock(ListableReceiverInterface::class);
        $receiver->method('all')->willReturn([]);

        $dispatcher = new EventDispatcher();
        $bus = $this->createMock(MessageBusInterface::class);

        $failureTransportName = 'failure_receiver';
        $serviceLocator = $this->createMock(ServiceLocator::class);
        $serviceLocator->method('has')->with($failureTransportName)->willReturn(true);
        $serviceLocator->method('get')->with($failureTransportName)->willReturn($receiver);
        $serviceLocator->method('getProvidedServices')->willReturn([
            'failure_receiver' => [],
            'failure_receiver_2' => [],
            'failure_receiver_3' => [],
        ]);

        $command = new FailedMessagesRetryCommand(
            $failureTransportName,
            $serviceLocator,
            $bus,
            $dispatcher
        );
        $tester = new CommandTester($command);
        $tester->setInputs([0]);
        $tester->execute(['--force' => true]);

        $expectedLadingMessage = <<<EOF
> Available failure transports are: failure_receiver, failure_receiver_2, failure_receiver_3
EOF;
        $this->assertStringContainsString($expectedLadingMessage, $tester->getDisplay());
    }

    public function testBasicRunWithServiceLocatorWithSpecificFailureTransport()
    {
        $receiver = $this->createMock(ListableReceiverInterface::class);
        $receiver->expects($this->exactly(2))->method('find')->withConsecutive([10], [12])->willReturn(new Envelope(new \stdClass()));
        // message will eventually be ack'ed in Worker
        $receiver->expects($this->exactly(2))->method('ack');

        $dispatcher = new EventDispatcher();
        $bus = $this->createMock(MessageBusInterface::class);
        // the bus should be called in the worker
        $bus->expects($this->exactly(2))->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $failureTransportName = 'failure_receiver';
        $serviceLocator = $this->createMock(ServiceLocator::class);
        $serviceLocator->method('has')->with($failureTransportName)->willReturn(true);
        $serviceLocator->method('get')->with($failureTransportName)->willReturn($receiver);

        $command = new FailedMessagesRetryCommand(
            $failureTransportName,
            $serviceLocator,
            $bus,
            $dispatcher
        );

        $tester = new CommandTester($command);
        $tester->execute(['id' => [10, 12], '--transport' => $failureTransportName, '--force' => true]);

        $this->assertStringContainsString('[OK]', $tester->getDisplay());
    }
}
