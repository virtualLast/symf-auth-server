<?php

namespace App\Service;

use App\Model\Dto\MetadataDto;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MetadataService
{
    public const SETTINGS_KEY_IDP = 'idp';
    public const SETTINGS_KEY_CERT = 'x509cert';
    public const METADATA_URL = 'http://localhost:8081/realms/local-dev/protocol/saml/descriptor';

    private const CACHE_KEY = 'metadata';
    private const DAY_IN_SECONDS = 86400;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get the SAML metadata from the IdP.
     * Cache it for 24 hours.
     * @return MetadataDto
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getMetadata(): MetadataDto
    {
        return $this->cache->get(MetadataService::class . self::CACHE_KEY, function (ItemInterface $item) {
            $response = $this->client->request(
                'GET',
                self::METADATA_URL,
                [
                    'headers' => [
                        'Accept' => 'application/xml',
                    ],
                ]
            );

            $item->expiresAfter(self::DAY_IN_SECONDS);
            return $this->parseMetadata($response->getContent());
        });
    }

    /**
     * pull out the certificate, this can be extended to pull out other metadata if needed.
     */
    private function parseMetadata(string $metadata): MetadataDto
    {
        $certificate = $this->extractCertificate($metadata);

        return (new MetadataDto())->setCertificate($certificate);
    }

    /**
     * Extract the X509Certificate from the metadata.
     * @param string $metadata
     * @return string
     */
    private function extractCertificate(string $metadata): string
    {
        $xml = simplexml_load_string($metadata);

        if ($xml === false) {
            throw new \RuntimeException('Invalid XML metadata.');
        }

        // Register namespaces
        $xml->registerXPathNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        // XPath to the X509Certificate
        $nodes = $xml->xpath('//md:KeyDescriptor[@use="signing"]/ds:KeyInfo/ds:X509Data/ds:X509Certificate');

        if (!$nodes || count($nodes) === 0) {
            throw new \RuntimeException('Certificate not found in metadata.');
        }

        return trim((string) $nodes[0]);
    }

}
