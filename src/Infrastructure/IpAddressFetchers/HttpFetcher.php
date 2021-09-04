<?php
declare(strict_types=1);

namespace App\Infrastructure\IpAddressFetchers;

use App\Domain\IpAddress;
use App\Domain\IpAddressFetcher;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpFetcher implements IpAddressFetcher
{

    public function __construct(private HttpClientInterface $ipAddressFetcherClient, private LoggerInterface $logger)
    {

    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \App\Domain\Exception\InvalidIpAddress
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    public function fetch(string $checkingService): IpAddress
    {
        $this->logger->debug('Checking with service: {{service}}', ['service' => $checkingService]);
        $response = $this->ipAddressFetcherClient->request('GET', $checkingService)->getContent(true);
        return new IpAddress($response);
    }

    public function supports(string $dsn): bool
    {
        return str_starts_with($dsn, 'http://') || str_starts_with($dsn, 'https://');
    }
}
