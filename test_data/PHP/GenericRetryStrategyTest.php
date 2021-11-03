<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Tests\Retry;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GenericRetryStrategyTest extends TestCase
{
    /**
     * @dataProvider provideRetryable
     */
    public function testShouldRetry(string $method, int $code, ?TransportExceptionInterface $exception)
    {
        $strategy = new GenericRetryStrategy();

        self::assertTrue($strategy->shouldRetry($this->getContext(0, $method, 'http://example.com/', $code), null, $exception));
    }

    /**
     * @dataProvider provideNotRetryable
     */
    public function testShouldNotRetry(string $method, int $code, ?TransportExceptionInterface $exception)
    {
        $strategy = new GenericRetryStrategy();

        self::assertFalse($strategy->shouldRetry($this->getContext(0, $method, 'http://example.com/', $code), null, $exception));
    }

    public function provideRetryable(): iterable
    {
        yield ['GET', 200, new TransportException()];
        yield ['GET', 500, null];
        yield ['POST', 429, null];
    }

    public function provideNotRetryable(): iterable
    {
        yield ['POST', 200, null];
        yield ['POST', 200, new TransportException()];
        yield ['POST', 500, null];
    }

    /**
     * @dataProvider provideDelay
     */
    public function testGetDelay(int $delay, int $multiplier, int $maxDelay, int $previousRetries, int $expectedDelay)
    {
        $strategy = new GenericRetryStrategy([], $delay, $multiplier, $maxDelay, 0);

        self::assertSame($expectedDelay, $strategy->getDelay($this->getContext($previousRetries, 'GET', 'http://example.com/', 200), null, null));
    }

    public function provideDelay(): iterable
    {
        // delay, multiplier, maxDelay, retries, expectedDelay
        yield [1000, 1, 5000, 0, 1000];
        yield [1000, 1, 5000, 1, 1000];
        yield [1000, 1, 5000, 2, 1000];

        yield [1000, 2, 10000, 0, 1000];
        yield [1000, 2, 10000, 1, 2000];
        yield [1000, 2, 10000, 2, 4000];
        yield [1000, 2, 10000, 3, 8000];
        yield [1000, 2, 10000, 4, 10000]; // max hit
        yield [1000, 2, 0, 4, 16000]; // no max

        yield [1000, 3, 10000, 0, 1000];
        yield [1000, 3, 10000, 1, 3000];
        yield [1000, 3, 10000, 2, 9000];

        yield [1000, 1, 500, 0, 500]; // max hit immediately

        // never a delay
        yield [0, 2, 10000, 0, 0];
        yield [0, 2, 10000, 1, 0];
    }

    public function testJitter()
    {
        $strategy = new GenericRetryStrategy([], 1000, 1, 0, 1);
        $min = 2000;
        $max = 0;
        for ($i = 0; $i < 50; ++$i) {
            $delay = $strategy->getDelay($this->getContext(0, 'GET', 'http://example.com/', 200), null, null);
            $min = min($min, $delay);
            $max = max($max, $delay);
        }
        $this->assertGreaterThanOrEqual(1000, $max - $min);
        $this->assertGreaterThanOrEqual(1000, $max);
        $this->assertLessThanOrEqual(1000, $min);
    }

    private function getContext($retryCount, $method, $url, $statusCode): AsyncContext
    {
        $passthru = null;
        $info = [
            'retry_count' => $retryCount,
            'http_method' => $method,
            'url' => $url,
            'http_code' => $statusCode,
        ];
        $response = new MockResponse('', $info);

        return new AsyncContext($passthru, new MockHttpClient(), $response, $info, null, 0);
    }
}
