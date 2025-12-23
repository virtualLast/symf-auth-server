<?php

namespace App\Tests\Service;

use App\Model\Dto\MetadataDto;
use App\Service\MetadataService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MetadataServiceTest extends TestCase
{
    private HttpClientInterface $client;
    private CacheInterface $cache;
    private ItemInterface $cacheItem;
    private MetadataService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheItem = $this->createMock(ItemInterface::class);

        $this->service = new MetadataService($this->client, $this->cache);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_get_metadata_fetches_parses_and_caches_for_24_hours(): void
    {
        // arrange
        $expectedCert = 'MIIDTESTCERTBASE64==';

        // Http client mock returning valid XML
        $response = $this->createResponseWithContent($this->buildValidMetadataXml($expectedCert));

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                MetadataService::METADATA_URL,
                $this->callback(function ($opts) {
                    return isset($opts['headers']['Accept']) && $opts['headers']['Accept'] === 'application/xml';
                })
            )
            ->willReturn($response);

        // Cache mock: assert key and TTL via ItemInterface::expiresAfter
        $this->cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with($this->equalTo(86400));

        $capturedKey = null;
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($this->callback(function ($key) use (&$capturedKey) {
                $capturedKey = $key;
                return true;
            }), $this->isCallable())
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->cacheItem);
            });

        // act
        $dto = $this->service->getMetadata();

        // assert
        $this->assertSame($expectedCert, $dto->getCertificate());
        $this->assertSame(urlencode(MetadataService::class . 'metadata'), $capturedKey);
    }

    /**
     * @throws InvalidArgumentException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_invalid_xml_throws_runtime_exception(): void
    {
        // arrange
        $response = $this->createResponseWithContent('not-xml');

        $this->client
            ->method('request')
            ->willReturn($response);

        // Simulate cache miss so callback runs
        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->cacheItem);
            });

        // act + assert
        $this->expectException(\RuntimeException::class);
        $this->service->getMetadata();
    }

    /**
     * @throws InvalidArgumentException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_missing_certificate_throws_runtime_exception(): void
    {
        // arrange
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="test-entity">
  <md:IDPSSODescriptor>
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <!-- Intentionally missing X509Certificate -->
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $response = $this->createResponseWithContent($xml);

        $this->client
            ->method('request')
            ->willReturn($response);

        // Simulate cache miss so callback runs
        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->cacheItem);
            });

        // act + assert
        $this->expectException(\RuntimeException::class);
        $this->service->getMetadata();
    }

    /**
     * @throws InvalidArgumentException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_cache_hit_returns_cached_value_without_http_request(): void
    {
        // arrange
        $this->client
            ->expects($this->never())
            ->method('request');

        $cached = (new MetadataDto())->setCertificate('CACHEDCERT');
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturn($cached);

        // act
        $dto = $this->service->getMetadata();

        // assert
        $this->assertSame('CACHEDCERT', $dto->getCertificate());
    }

    /**
     * @throws ServerExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function test_uses_correct_url_and_accept_header(): void
    {
        // arrange
        $response = $this->createResponseWithContent($this->buildValidMetadataXml('CERT'));

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                $this->equalTo(MetadataService::METADATA_URL),
                $this->callback(function ($opts) {
                    return isset($opts['headers']) && ($opts['headers']['Accept'] ?? null) === 'application/xml';
                })
            )
            ->willReturn($response);

        // Simulate cache miss so callback runs
        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->cacheItem);
            });

        // act
        $dto = $this->service->getMetadata();

        // assert
        $this->assertSame('CERT', $dto->getCertificate());
    }

    private function buildValidMetadataXml(string $certificate): string
    {
        $cert = htmlspecialchars($certificate, ENT_NOQUOTES);
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="test-entity">
  <md:IDPSSODescriptor>
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>$cert</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
  </md:IDPSSODescriptor>
  </md:EntityDescriptor>
XML;
    }

    private function createResponseWithContent(string $content): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($content);
        return $response;
    }
}
