<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\Tests;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MockHttpClientTest extends HttpClientTestCase
{
    /**
     * @dataProvider mockingProvider
     */
    public function testMocking($factory, array $expectedResponses)
    {
        $client = new MockHttpClient($factory);
        $this->assertSame(0, $client->getRequestsCount());

        $urls = ['/foo', '/bar'];
        foreach ($urls as $i => $url) {
            $response = $client->request('POST', $url, ['body' => 'payload']);
            $this->assertEquals($expectedResponses[$i], $response->getContent());
        }

        $this->assertSame(2, $client->getRequestsCount());
    }

    public function mockingProvider(): iterable
    {
        yield 'callable' => [
            static function (string $method, string $url, array $options = []) {
                return new MockResponse($method.': '.$url.' (body='.$options['body'].')');
            },
            [
                'POST: https://example.com/foo (body=payload)',
                'POST: https://example.com/bar (body=payload)',
            ],
        ];

        yield 'array of callable' => [
            [
                static function (string $method, string $url, array $options = []) {
                    return new MockResponse($method.': '.$url.' (body='.$options['body'].') [1]');
                },
                static function (string $method, string $url, array $options = []) {
                    return new MockResponse($method.': '.$url.' (body='.$options['body'].') [2]');
                },
            ],
            [
                'POST: https://example.com/foo (body=payload) [1]',
                'POST: https://example.com/bar (body=payload) [2]',
            ],
        ];

        yield 'array of response objects' => [
            [
                new MockResponse('static response [1]'),
                new MockResponse('static response [2]'),
            ],
            [
                'static response [1]',
                'static response [2]',
            ],
        ];

        yield 'iterator' => [
            new \ArrayIterator(
                [
                    new MockResponse('static response [1]'),
                    new MockResponse('static response [2]'),
                ]
            ),
            [
                'static response [1]',
                'static response [2]',
            ],
        ];

        yield 'null' => [
            null,
            [
                '',
                '',
            ],
        ];
    }

    /**
     * @dataProvider validResponseFactoryProvider
     */
    public function testValidResponseFactory($responseFactory)
    {
        (new MockHttpClient($responseFactory))->request('GET', 'https://foo.bar');

        $this->addToAssertionCount(1);
    }

    public function validResponseFactoryProvider()
    {
        return [
            [static function (): MockResponse { return new MockResponse(); }],
            [new MockResponse()],
            [[new MockResponse()]],
            [new \ArrayIterator([new MockResponse()])],
            [null],
            [(static function (): \Generator { yield new MockResponse(); })()],
        ];
    }

    /**
     * @dataProvider transportExceptionProvider
     */
    public function testTransportExceptionThrowsIfPerformedMoreRequestsThanConfigured($factory)
    {
        $client = new MockHttpClient($factory);

        $client->request('POST', '/foo');
        $client->request('POST', '/foo');

        $this->expectException(TransportException::class);
        $client->request('POST', '/foo');
    }

    public function transportExceptionProvider(): iterable
    {
        yield 'array of callable' => [
            [
                static function (string $method, string $url, array $options = []) {
                    return new MockResponse();
                },
                static function (string $method, string $url, array $options = []) {
                    return new MockResponse();
                },
            ],
        ];

        yield 'array of response objects' => [
            [
                new MockResponse(),
                new MockResponse(),
            ],
        ];

        yield 'iterator' => [
            new \ArrayIterator(
                [
                    new MockResponse(),
                    new MockResponse(),
                ]
            ),
        ];
    }

    /**
     * @dataProvider invalidResponseFactoryProvider
     */
    public function testInvalidResponseFactory($responseFactory, string $expectedExceptionMessage)
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        (new MockHttpClient($responseFactory))->request('GET', 'https://foo.bar');
    }

    public function invalidResponseFactoryProvider()
    {
        return [
            [static function (): \Generator { yield new MockResponse(); }, 'The response factory passed to MockHttpClient must return/yield an instance of ResponseInterface, "Generator" given.'],
            [static function (): array { return [new MockResponse()]; }, 'The response factory passed to MockHttpClient must return/yield an instance of ResponseInterface, "array" given.'],
            [(static function (): \Generator { yield 'ccc'; })(), 'The response factory passed to MockHttpClient must return/yield an instance of ResponseInterface, "string" given.'],
        ];
    }

    protected function getHttpClient(string $testCase): HttpClientInterface
    {
        $responses = [];

        $headers = [
          'Host: localhost:8057',
          'Content-Type: application/json',
        ];

        $body = '{
    "SERVER_PROTOCOL": "HTTP/1.1",
    "SERVER_NAME": "127.0.0.1",
    "REQUEST_URI": "/",
    "REQUEST_METHOD": "GET",
    "HTTP_ACCEPT": "*/*",
    "HTTP_FOO": "baR",
    "HTTP_HOST": "localhost:8057"
}';

        $client = new NativeHttpClient();

        switch ($testCase) {
            default:
                return new MockHttpClient(function (string $method, string $url, array $options) use ($client) {
                    try {
                        // force the request to be completed so that we don't test side effects of the transport
                        $response = $client->request($method, $url, ['buffer' => false] + $options);
                        $content = $response->getContent(false);

                        return new MockResponse($content, $response->getInfo());
                    } catch (\Throwable $e) {
                        $this->fail($e->getMessage());
                    }
                });

            case 'testUnsupportedOption':
                $this->markTestSkipped('MockHttpClient accepts any options by default');
                break;

            case 'testChunkedEncoding':
                $this->markTestSkipped("MockHttpClient doesn't dechunk");
                break;

            case 'testGzipBroken':
                $this->markTestSkipped("MockHttpClient doesn't unzip");
                break;

            case 'testTimeoutWithActiveConcurrentStream':
                $this->markTestSkipped('Real transport required');
                break;

            case 'testTimeoutOnDestruct':
                $this->markTestSkipped('Real transport required');
                break;

            case 'testDestruct':
                $this->markTestSkipped("MockHttpClient doesn't timeout on destruct");
                break;

            case 'testHandleIsRemovedOnException':
                $this->markTestSkipped("MockHttpClient doesn't cache handles");
                break;

            case 'testPause':
            case 'testPauseReplace':
            case 'testPauseDuringBody':
                $this->markTestSkipped("MockHttpClient doesn't support pauses by default");
                break;

            case 'testDnsFailure':
                $this->markTestSkipped("MockHttpClient doesn't use a DNS");
                break;

            case 'testGetRequest':
                array_unshift($headers, 'HTTP/1.1 200 OK');
                $responses[] = new MockResponse($body, ['response_headers' => $headers]);

                $headers = [
                  'Host: localhost:8057',
                  'Content-Length: 1000',
                  'Content-Type: application/json',
                ];

                $responses[] = new MockResponse($body, ['response_headers' => $headers]);
                break;

            case 'testDnsError':
                $mock = $this->createMock(ResponseInterface::class);
                $mock->expects($this->any())
                    ->method('getStatusCode')
                    ->willThrowException(new TransportException('DSN error'));
                $mock->expects($this->any())
                    ->method('getInfo')
                    ->willReturn([]);

                $responses[] = $mock;
                $responses[] = $mock;
                break;

            case 'testToStream':
            case 'testBadRequestBody':
            case 'testOnProgressCancel':
            case 'testOnProgressError':
            case 'testReentrantBufferCallback':
            case 'testThrowingBufferCallback':
            case 'testInfoOnCanceledResponse':
            case 'testChangeResponseFactory':
                $responses[] = new MockResponse($body, ['response_headers' => $headers]);
                break;

            case 'testTimeoutOnAccess':
                $mock = $this->createMock(ResponseInterface::class);
                $mock->expects($this->any())
                    ->method('getHeaders')
                    ->willThrowException(new TransportException('Timeout'));

                $responses[] = $mock;
                break;

            case 'testAcceptHeader':
                $responses[] = new MockResponse($body, ['response_headers' => $headers]);
                $responses[] = new MockResponse(str_replace('*/*', 'foo/bar', $body), ['response_headers' => $headers]);
                $responses[] = new MockResponse(str_replace('"HTTP_ACCEPT": "*/*",', '', $body), ['response_headers' => $headers]);
                break;

            case 'testResolve':
                $responses[] = new MockResponse($body, ['response_headers' => $headers]);
                $responses[] = new MockResponse($body, ['response_headers' => $headers]);
                $responses[] = new MockResponse((function () { throw new \Exception('Fake connection timeout'); yield ''; })(), ['response_headers' => $headers]);
                break;

            case 'testTimeoutOnStream':
            case 'testUncheckedTimeoutThrows':
            case 'testTimeoutIsNotAFatalError':
                $body = ['<1>', '', '<2>'];
                $responses[] = new MockResponse($body, ['response_headers' => $headers]);
                break;

            case 'testInformationalResponseStream':
                $client = $this->createMock(HttpClientInterface::class);
                $response = new MockResponse('Here the body', ['response_headers' => [
                    'HTTP/1.1 103 ',
                    'Link: </style.css>; rel=preload; as=style',
                    'HTTP/1.1 200 ',
                    'Date: foo',
                    'Content-Length: 13',
                ]]);
                $client->method('request')->willReturn($response);
                $client->method('stream')->willReturn(new ResponseStream((function () use ($response) {
                    $chunk = $this->createMock(ChunkInterface::class);
                    $chunk->method('getInformationalStatus')
                        ->willReturn([103, ['link' => ['</style.css>; rel=preload; as=style', '</script.js>; rel=preload; as=script']]]);

                    yield $response => $chunk;

                    $chunk = $this->createMock(ChunkInterface::class);
                    $chunk->method('isFirst')->willReturn(true);

                    yield $response => $chunk;

                    $chunk = $this->createMock(ChunkInterface::class);
                    $chunk->method('getContent')->willReturn('Here the body');

                    yield $response => $chunk;

                    $chunk = $this->createMock(ChunkInterface::class);
                    $chunk->method('isLast')->willReturn(true);

                    yield $response => $chunk;
                })()));

                return $client;

            case 'testNonBlockingStream':
                $responses[] = new MockResponse((function () { yield '<1>'; yield ''; yield '<2>'; })(), ['response_headers' => $headers]);
                break;

            case 'testMaxDuration':
                $mock = $this->createMock(ResponseInterface::class);
                $mock->expects($this->any())
                    ->method('getContent')
                    ->willReturnCallback(static function (): void {
                        usleep(100000);

                        throw new TransportException('Max duration was reached.');
                    });

                $responses[] = $mock;
                break;
        }

        return new MockHttpClient($responses);
    }

    public function testHttp2PushVulcain()
    {
        $this->markTestSkipped('MockHttpClient doesn\'t support HTTP/2 PUSH.');
    }

    public function testHttp2PushVulcainWithUnusedResponse()
    {
        $this->markTestSkipped('MockHttpClient doesn\'t support HTTP/2 PUSH.');
    }

    public function testChangeResponseFactory()
    {
        /* @var MockHttpClient $client */
        $client = $this->getHttpClient(__METHOD__);
        $expectedBody = '{"foo": "bar"}';
        $client->setResponseFactory(new MockResponse($expectedBody));

        $response = $client->request('GET', 'http://localhost:8057');

        $this->assertSame($expectedBody, $response->getContent());
    }

    public function testStringableBodyParam()
    {
        $client = new MockHttpClient();

        $param = new class() {
            public function __toString()
            {
                return 'bar';
            }
        };

        $response = $client->request('GET', 'https://example.com', [
            'body' => ['foo' => $param],
        ]);

        $this->assertSame('foo=bar', $response->getRequestOptions()['body']);
    }
}
