<?php

namespace Symfony\Component\Translation\Bridge\Crowdin\Tests;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Translation\Bridge\Crowdin\CrowdinProvider;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\Test\ProviderTestCase;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CrowdinProviderTest extends ProviderTestCase
{
    public function createProvider(HttpClientInterface $client, LoaderInterface $loader, LoggerInterface $logger, string $defaultLocale, string $endpoint): ProviderInterface
    {
        return new CrowdinProvider($client, $loader, $logger, $this->getXliffFileDumper(), $defaultLocale, $endpoint);
    }

    public function toStringProvider(): iterable
    {
        yield [
            $this->createProvider($this->getClient()->withOptions([
                'base_uri' => 'https://api.crowdin.com/api/v2/projects/1/',
                'auth_bearer' => 'API_TOKEN',
            ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com'),
            'crowdin://api.crowdin.com',
        ];

        yield [
            $this->createProvider($this->getClient()->withOptions([
                'base_uri' => 'https://domain.api.crowdin.com/api/v2/projects/1/',
                'auth_bearer' => 'API_TOKEN',
            ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'domain.api.crowdin.com'),
            'crowdin://domain.api.crowdin.com',
        ];

        yield [
            $this->createProvider($this->getClient()->withOptions([
                'base_uri' => 'https://api.crowdin.com:99/api/v2/projects/1/',
                'auth_bearer' => 'API_TOKEN',
            ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com:99'),
            'crowdin://api.crowdin.com:99',
        ];
    }

    public function testCompleteWriteProcessAddFiles()
    {
        $this->xliffFileDumper = new XliffFileDumper();

        $expectedMessagesFileContent = <<<'XLIFF'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="en" datatype="plaintext" original="file.ext">
    <header>
      <tool tool-id="symfony" tool-name="Symfony"/>
    </header>
    <body>
      <trans-unit id="ypeBEso" resname="a">
        <source>a</source>
        <target>trans_en_a</target>
      </trans-unit>
    </body>
  </file>
</xliff>

XLIFF;

        $expectedValidatorsFileContent = <<<'XLIFF'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="en" datatype="plaintext" original="file.ext">
    <header>
      <tool tool-id="symfony" tool-name="Symfony"/>
    </header>
    <body>
      <trans-unit id="is7pld7" resname="post.num_comments">
        <source>post.num_comments</source>
        <target>{count, plural, one {# comment} other {# comments}}</target>
      </trans-unit>
    </body>
  </file>
</xliff>

XLIFF;

        $responses = [
            'listFiles' => function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);
                $this->assertSame('Authorization: Bearer API_TOKEN', $options['normalized_headers']['authorization'][0]);

                return new MockResponse(json_encode(['data' => []]));
            },
            'addStorage' => function (string $method, string $url, array $options = []) use ($expectedMessagesFileContent): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/storages', $url);
                $this->assertSame('Content-Type: application/octet-stream', $options['normalized_headers']['content-type'][0]);
                $this->assertSame('Crowdin-API-FileName: messages.xlf', $options['normalized_headers']['crowdin-api-filename'][0]);
                $this->assertSame($expectedMessagesFileContent, $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 19]]), ['http_code' => 201]);
            },
            'addFile' => function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);
                $this->assertSame('{"storageId":19,"name":"messages.xlf"}', $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 199, 'name' => 'messages.xlf']]));
            },
            'addStorage2' => function (string $method, string $url, array $options = []) use ($expectedValidatorsFileContent): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/storages', $url);
                $this->assertSame('Content-Type: application/octet-stream', $options['normalized_headers']['content-type'][0]);
                $this->assertSame('Crowdin-API-FileName: validators.xlf', $options['normalized_headers']['crowdin-api-filename'][0]);
                $this->assertSame($expectedValidatorsFileContent, $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 19]]), ['http_code' => 201]);
            },
            'addFile2' => function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);
                $this->assertSame('{"storageId":19,"name":"validators.xlf"}', $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 200, 'name' => 'validators.xlf']]));
            },
        ];

        $translatorBag = new TranslatorBag();
        $translatorBag->addCatalogue(new MessageCatalogue('en', [
            'messages' => ['a' => 'trans_en_a'],
            'validators' => ['post.num_comments' => '{count, plural, one {# comment} other {# comments}}'],
        ]));

        $provider = $this->createProvider((new MockHttpClient($responses))->withOptions([
            'base_uri' => 'https://api.crowdin.com/api/v2/projects/1/',
            'auth_bearer' => 'API_TOKEN',
        ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com/api/v2/projects/1/');

        $provider->write($translatorBag);
    }

    public function testCompleteWriteProcessUpdateFiles()
    {
        $this->xliffFileDumper = new XliffFileDumper();

        $expectedMessagesFileContent = <<<'XLIFF'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="en" datatype="plaintext" original="file.ext">
    <header>
      <tool tool-id="symfony" tool-name="Symfony"/>
    </header>
    <body>
      <trans-unit id="ypeBEso" resname="a">
        <source>a</source>
        <target>trans_en_a</target>
      </trans-unit>
      <trans-unit id="PiPoFgA" resname="b">
        <source>b</source>
        <target>trans_en_b</target>
      </trans-unit>
    </body>
  </file>
</xliff>

XLIFF;

        $responses = [
            'listFiles' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);

                return new MockResponse(json_encode([
                    'data' => [
                        ['data' => [
                            'id' => 12,
                            'name' => 'messages.xlf',
                        ]],
                    ],
                ]));
            },
            'addStorage' => function (string $method, string $url, array $options = []) use ($expectedMessagesFileContent): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/storages', $url);
                $this->assertSame('Content-Type: application/octet-stream', $options['normalized_headers']['content-type'][0]);
                $this->assertSame('Crowdin-API-FileName: messages.xlf', $options['normalized_headers']['crowdin-api-filename'][0]);
                $this->assertSame($expectedMessagesFileContent, $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 19]]), ['http_code' => 201]);
            },
            'UpdateFile' => function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame('PUT', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files/12', $url);
                $this->assertSame('{"storageId":19}', $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 199, 'name' => 'messages.xlf']]));
            },
        ];

        $translatorBag = new TranslatorBag();
        $translatorBag->addCatalogue(new MessageCatalogue('en', [
            'messages' => ['a' => 'trans_en_a', 'b' => 'trans_en_b'],
        ]));

        $provider = $this->createProvider((new MockHttpClient($responses))->withOptions([
            'base_uri' => 'https://api.crowdin.com/api/v2/projects/1/',
            'auth_bearer' => 'API_TOKEN',
        ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com/api/v2/projects/1/');

        $provider->write($translatorBag);
    }

    public function testCompleteWriteProcessAddFileAndUploadTranslations()
    {
        $this->xliffFileDumper = new XliffFileDumper();

        $expectedMessagesFileContent = <<<'XLIFF'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="en" datatype="plaintext" original="file.ext">
    <header>
      <tool tool-id="symfony" tool-name="Symfony"/>
    </header>
    <body>
      <trans-unit id="ypeBEso" resname="a">
        <source>a</source>
        <target>trans_en_a</target>
      </trans-unit>
    </body>
  </file>
</xliff>

XLIFF;

        $expectedMessagesTranslationsContent = <<<'XLIFF'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="fr" datatype="plaintext" original="file.ext">
    <header>
      <tool tool-id="symfony" tool-name="Symfony"/>
    </header>
    <body>
      <trans-unit id="ypeBEso" resname="a">
        <source>a</source>
        <target>trans_fr_a</target>
      </trans-unit>
    </body>
  </file>
</xliff>

XLIFF;

        $responses = [
            'listFiles' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);

                return new MockResponse(json_encode([
                    'data' => [
                        ['data' => [
                            'id' => 12,
                            'name' => 'messages.xlf',
                        ]],
                    ],
                ]));
            },
            'addStorage' => function (string $method, string $url, array $options = []) use ($expectedMessagesFileContent): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/storages', $url);
                $this->assertSame('Content-Type: application/octet-stream', $options['normalized_headers']['content-type'][0]);
                $this->assertSame('Crowdin-API-FileName: messages.xlf', $options['normalized_headers']['crowdin-api-filename'][0]);
                $this->assertSame($expectedMessagesFileContent, $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 19]]), ['http_code' => 201]);
            },
            'updateFile' => function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame('PUT', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files/12', $url);
                $this->assertSame('{"storageId":19}', $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 12, 'name' => 'messages.xlf']]));
            },
            'addStorage2' => function (string $method, string $url, array $options = []) use ($expectedMessagesTranslationsContent): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/storages', $url);
                $this->assertSame('Content-Type: application/octet-stream', $options['normalized_headers']['content-type'][0]);
                $this->assertSame('Crowdin-API-FileName: messages.xlf', $options['normalized_headers']['crowdin-api-filename'][0]);
                $this->assertSame($expectedMessagesTranslationsContent, $options['body']);

                return new MockResponse(json_encode(['data' => ['id' => 19]]), ['http_code' => 201]);
            },
            'UploadTranslations' => function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/translations/fr', $url);
                $this->assertSame('{"storageId":19,"fileId":12}', $options['body']);

                return new MockResponse();
            },
        ];

        $translatorBag = new TranslatorBag();
        $translatorBag->addCatalogue(new MessageCatalogue('en', [
            'messages' => ['a' => 'trans_en_a'],
        ]));
        $translatorBag->addCatalogue(new MessageCatalogue('fr', [
            'messages' => ['a' => 'trans_fr_a'],
        ]));

        $provider = $this->createProvider((new MockHttpClient($responses))->withOptions([
            'base_uri' => 'https://api.crowdin.com/api/v2/projects/1/',
            'auth_bearer' => 'API_TOKEN',
        ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com/api/v2/projects/1/');

        $provider->write($translatorBag);
    }

    /**
     * @dataProvider getResponsesForOneLocaleAndOneDomain
     */
    public function testReadForOneLocaleAndOneDomain(string $locale, string $domain, string $responseContent, TranslatorBag $expectedTranslatorBag)
    {
        $responses = [
            'listFiles' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);

                return new MockResponse(json_encode([
                    'data' => [
                        ['data' => [
                            'id' => 12,
                            'name' => 'messages.xlf',
                        ]],
                    ],
                ]));
            },
            'exportProjectTranslations' => function (string $method, string $url, array $options = []): ResponseInterface {
                $this->assertSame('POST', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/translations/exports', $url);
                $this->assertSame('{"targetLanguageId":"fr","fileIds":[12]}', $options['body']);

                return new MockResponse(json_encode(['data' => ['url' => 'https://file.url']]));
            },
            'downloadFile' => function (string $method, string $url) use ($responseContent): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://file.url/', $url);

                return new MockResponse($responseContent);
            },
        ];

        $loader = $this->getLoader();
        $loader->expects($this->once())
            ->method('load')
            ->willReturn($expectedTranslatorBag->getCatalogue($locale));

        $crowdinProvider = $this->createProvider((new MockHttpClient($responses))->withOptions([
            'base_uri' => 'https://api.crowdin.com/api/v2/projects/1/',
            'auth_bearer' => 'API_TOKEN',
        ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com/api/v2');

        $translatorBag = $crowdinProvider->read([$domain], [$locale]);

        $this->assertEquals($expectedTranslatorBag->getCatalogues(), $translatorBag->getCatalogues());
    }

    public function getResponsesForOneLocaleAndOneDomain(): \Generator
    {
        $arrayLoader = new ArrayLoader();

        $expectedTranslatorBagFr = new TranslatorBag();
        $expectedTranslatorBagFr->addCatalogue($arrayLoader->load([
            'index.hello' => 'Bonjour',
            'index.greetings' => 'Bienvenue, {firstname} !',
        ], 'fr'));

        yield ['fr', 'messages', <<<'XLIFF'
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="fr" datatype="database" tool-id="crowdin">
    <header>
      <tool tool-id="crowdin" tool-name="Crowdin" tool-version="1.0.25 20201211-1" tool-company="Crowdin"/>
    </header>
    <body>
      <trans-unit id="crowdin:5fd89b853ee27904dd6c5f67" resname="index.hello" datatype="plaintext">
        <source>index.hello</source>
        <target state="translated">Bonjour</target>
      </trans-unit>
      <trans-unit id="crowdin:5fd89b8542e5aa5cc27457e2" resname="index.greetings" datatype="plaintext" extradata="crowdin:format=icu">
        <source>index.greetings</source>
        <target state="translated">Bienvenue, {firstname} !</target>
      </trans-unit>
    </body>
  </file>
</xliff>
XLIFF
            ,
            $expectedTranslatorBagFr,
        ];
    }

    /**
     * @dataProvider getResponsesForDefaultLocaleAndOneDomain
     */
    public function testReadForDefaultLocaleAndOneDomain(string $locale, string $domain, string $responseContent, TranslatorBag $expectedTranslatorBag)
    {
        $responses = [
            'listFiles' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);

                return new MockResponse(json_encode([
                    'data' => [
                        ['data' => [
                            'id' => 12,
                            'name' => 'messages.xlf',
                        ]],
                    ],
                ]));
            },
            'downloadSource' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files/12/download', $url);

                return new MockResponse(json_encode(['data' => ['url' => 'https://file.url']]));
            },
            'downloadFile' => function (string $method, string $url) use ($responseContent): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://file.url/', $url);

                return new MockResponse($responseContent);
            },
        ];

        $loader = $this->getLoader();
        $loader->expects($this->once())
            ->method('load')
            ->willReturn($expectedTranslatorBag->getCatalogue($locale));

        $crowdinProvider = $this->createProvider((new MockHttpClient($responses))->withOptions([
            'base_uri' => 'https://api.crowdin.com/api/v2/projects/1/',
            'auth_bearer' => 'API_TOKEN',
        ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com/api/v2');

        $translatorBag = $crowdinProvider->read([$domain], [$locale]);

        $this->assertEquals($expectedTranslatorBag->getCatalogues(), $translatorBag->getCatalogues());
    }

    public function getResponsesForDefaultLocaleAndOneDomain(): \Generator
    {
        $arrayLoader = new ArrayLoader();

        $expectedTranslatorBagEn = new TranslatorBag();
        $expectedTranslatorBagEn->addCatalogue($arrayLoader->load([
            'index.hello' => 'Hello',
            'index.greetings' => 'Welcome, {firstname} !',
        ], 'en', 'messages'));

        yield ['en', 'messages', <<<'XLIFF'
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="fr" datatype="plaintext" tool-id="crowdin">
    <header>
      <tool tool-id="symfony" tool-name="Symfony"/>
    </header>
    <body>
      <trans-unit id="crowdin:5fd89b853ee27904dd6c5f67" resname="index.hello" datatype="plaintext">
        <source>index.hello</source>
        <target state="translated">Hello</target>
      </trans-unit>
      <trans-unit id="crowdin:5fd89b8542e5aa5cc27457e2" resname="index.greetings" datatype="plaintext" extradata="crowdin:format=icu">
        <source>index.greetings</source>
        <target state="translated">Welcome, {firstname} !</target>
      </trans-unit>
    </body>
  </file>
</xliff>
XLIFF
            ,
            $expectedTranslatorBagEn,
        ];
    }

    public function testDelete()
    {
        $responses = [
            'listFiles' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/files', $url);

                return new MockResponse(json_encode([
                    'data' => [
                        ['data' => [
                            'id' => 12,
                            'name' => 'messages.xlf',
                        ]],
                    ],
                ]));
            },
            'listStrings1' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/strings?fileId=12&limit=500&offset=0', $url);

                return new MockResponse(json_encode([
                    'data' => [
                        ['data' => ['id' => 1, 'text' => 'en a']],
                        ['data' => ['id' => 2, 'text' => 'en b']],
                    ],
                ]));
            },
            'listStrings2' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/strings?fileId=12&limit=500&offset=500', $url);

                $response = $this->createMock(ResponseInterface::class);
                $response->expects($this->any())
                    ->method('getContent')
                    ->with(false)
                    ->willReturn(json_encode(['data' => []]));

                return $response;
            },
            'deleteString1' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('DELETE', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/strings/1', $url);

                return new MockResponse('', ['http_code' => 204]);
            },
            'deleteString2' => function (string $method, string $url): ResponseInterface {
                $this->assertSame('DELETE', $method);
                $this->assertSame('https://api.crowdin.com/api/v2/projects/1/strings/2', $url);

                return new MockResponse('', ['http_code' => 204]);
            },
        ];

        $translatorBag = new TranslatorBag();
        $translatorBag->addCatalogue(new MessageCatalogue('en', [
            'messages' => [
                'en a' => 'en a',
                'en b' => 'en b',
            ],
        ]));

        $provider = $this->createProvider((new MockHttpClient($responses))->withOptions([
            'base_uri' => 'https://api.crowdin.com/api/v2/projects/1/',
            'auth_bearer' => 'API_TOKEN',
        ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'api.crowdin.com/api/v2/projects/1/');

        $provider->delete($translatorBag);
    }
}
