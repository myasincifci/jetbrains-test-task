<?php

namespace Symfony\Component\Notifier\Tests\Event;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Event\SentMessageEvent;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;

final class SentMessageEventTest extends TestCase
{
    /**
     * @dataProvider messagesProvider
     */
    public function testConstruct(SentMessage $message, SentMessageEvent $event)
    {
        $this->assertEquals($event, new SentMessageEvent($message));
    }

    /**
     * @dataProvider messagesProvider
     */
    public function testGetMessage(SentMessage $message, SentMessageEvent $event)
    {
        $this->assertSame($message, $event->getMessage());
    }

    public function messagesProvider(): iterable
    {
        yield [$message = new SentMessage(new ChatMessage('subject'), 'null_transport'), new SentMessageEvent($message)];
        yield [$message = new SentMessage(new SmsMessage('+3312345678', 'subject'), 'null_transport'), new SentMessageEvent($message)];
    }
}
