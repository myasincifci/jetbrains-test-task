<?php

namespace Symfony\Component\HttpClient\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RetryableHttpClientTest extends TestCase
{
    public function testRetryOnError()
    {
        $client = new RetryableHttpClient(
            new MockHttpClient([
                new MockResponse('', ['http_code' => 500]),
                new MockResponse('', ['http_code' => 200]),
            ]),
            new GenericRetryStrategy([500], 0),
            1
        );

        $response = $client->request('GET', 'http://example.com/foo-bar');

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRetryRespectStrategy()
    {
        $client = new RetryableHttpClient(
            new MockHttpClient([
                new MockResponse('', ['http_code' => 500]),
                new MockResponse('', ['http_code' => 500]),
                new MockResponse('', ['http_code' => 200]),
            ]),
            new GenericRetryStrategy([500], 0),
            1
        );

        $response = $client->request('GET', 'http://example.com/foo-bar');

        $this->expectException(ServerException::class);
        $response->getHeaders();
    }

    public function testRetryWithBody()
    {
        $client = new RetryableHttpClient(
            new MockHttpClient([
                new MockResponse('', ['http_code' => 500]),
                new MockResponse('', ['http_code' => 200]),
            ]),
            new class(GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 0) extends GenericRetryStrategy {
                public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): ?bool
                {
                    return null === $responseContent ? null : 200 !== $context->getStatusCode();
                }
            },
            1
        );

        $response = $client->request('GET', 'http://example.com/foo-bar');

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRetryWithBodyKeepContent()
    {
        $client = new RetryableHttpClient(
            new MockHttpClient([
                new MockResponse('my bad', ['http_code' => 400]),
            ]),
            new class([400], 0) extends GenericRetryStrategy {
                public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): ?bool
                {
                    if (null === $responseContent) {
                        return null;
                    }

                    return 'my bad' !== $responseContent;
                }
            },
            1
        );

        $response = $client->request('GET', 'http://example.com/foo-bar');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('my bad', $response->getContent(false));
    }

    public function testRetryWithBodyInvalid()
    {
        $client = new RetryableHttpClient(
            new MockHttpClient([
                new MockResponse('', ['http_code' => 500]),
                new MockResponse('', ['http_code' => 200]),
            ]),
            new class(GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 0) extends GenericRetryStrategy {
                public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): ?bool
                {
                    return null;
                }
            },
            1
        );

        $response = $client->request('GET', 'http://example.com/foo-bar');

        $this->expectExceptionMessageMatches('/must not return null when called with a body/');
        $response->getHeaders();
    }

    public function testStreamNoRetry()
    {
        $client = new RetryableHttpClient(
            new MockHttpClient([
                new MockResponse('', ['http_code' => 500]),
            ]),
            new GenericRetryStrategy([500], 0),
            0
        );

        $response = $client->request('GET', 'http://example.com/foo-bar');

        foreach ($client->stream($response) as $chunk) {
            if ($chunk->isFirst()) {
                self::assertSame(500, $response->getStatusCode());
            }
        }
    }

    public function testRetryWithDnsIssue()
    {
        $client = new RetryableHttpClient(
            new NativeHttpClient(),
            new class(GenericRetryStrategy::DEFAULT_RETRY_STATUS_CODES, 0) extends GenericRetryStrategy {
                public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): ?bool
                {
                    $this->fail('should not be called');
                }
            },
            2,
            $logger = new TestLogger()
        );

        $response = $client->request('GET', 'http://does.not.exists/foo-bar');

        try {
            $response->getHeaders();
        } catch (TransportExceptionInterface $e) {
            $this->assertSame('Could not resolve host "does.not.exists".', $e->getMessage());
        }
        $this->assertCount(2, $logger->logs);
        $this->assertSame('Try #{count} after {delay}ms: Could not resolve host "does.not.exists".', $logger->logs[0]);
    }

    public function testCancelOnTimeout()
    {
        $client = HttpClient::create();

        if ($client instanceof NativeHttpClient) {
            $this->markTestSkipped('NativeHttpClient cannot timeout before receiving headers');
        }

        $client = new RetryableHttpClient($client);

        $response = $client->request('GET', 'https://example.com/');

        foreach ($client->stream($response, 0) as $chunk) {
            $this->assertTrue($chunk->isTimeout());
            $response->cancel();
        }
    }
}
