<?php
declare(strict_types=1);

namespace App\Infrastructure\DnsServices;

use App\Domain\DnsService;
use App\Domain\Exception\DomainNameException;
use App\Domain\IpAddress;
use App\Domain\MonitorConfig;
use DigitalOceanV2\Client;
use Psr\Log\LoggerInterface;

class DigitalOceanDnsService implements DnsService
{
    private Client $client;

    public function __construct(private LoggerInterface $logger,)
    {
        $this->client = new Client();
    }

    public function update(IpAddress $toIpAddress, MonitorConfig $config): void
    {

        $this->client->authenticate($config->token);

        $this->logger->info('Authenticated with DigitalOcean API');

        $api = $this->client->domainRecord();

        $domainName = $this->resolveDomain($config->domain);

        $domainRecords = $api->getAll($domainName);


//        dump($api->getAll($config->domain));

    }

    /**
     * User might pass a subdomain, but we need a base domain to work with DO's API
     * So loop over all entries and find the entry that exists in the provided domain
     * @throws \App\Domain\Exception\DomainNameException
     * @throws \DigitalOceanV2\Exception\ExceptionInterface
     */
    private function resolveDomain(string $providedDomain): string
    {
        $domains = $this->client->domain()->getAll();

        foreach ($domains as $domain) {
            if (str_contains($providedDomain, $domain->name)) {
                return $domain->name;
            }
        }

        throw new DomainNameException(sprintf('Unable to find a matching domain in DigitalOcean for "%s"', $providedDomain));
    }

    public function supports(string $type): bool
    {
        return $type === 'digitalocean';
    }
}
