<?php
declare(strict_types=1);

namespace App\Infrastructure\DnsServices\DigitalOcean;

use App\Domain\Exception\DomainNameException;
use App\Domain\Exception\UpdateError;
use App\Domain\IpAddress;
use App\Infrastructure\DnsServices\AbstractDnsService;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\DomainRecord;
use DigitalOceanV2\Exception\ExceptionInterface;

/**
 * @extends AbstractDnsService<\App\Infrastructure\DnsServices\DigitalOcean\Config>
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DigitalOceanDnsService extends AbstractDnsService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @throws \App\Domain\Exception\UpdateError
     * @throws \App\Domain\Exception\DomainNameException
     */
    public function update(IpAddress $toIpAddress, mixed $config): void
    {
        $this->client->authenticate($config->token);
        $this->logger->info('Authenticated with DigitalOcean API');

        try {
            $records = $this->client->domainRecord()->getAll($config->domain);
        } catch (ExceptionInterface $e) {
            throw new DomainNameException('Unable to find records for domain', previous: $e);
        }

        $subdomainRecord = $this->resolveSubdomainRecord($records, $config->subdomain, $config->recordType);

        if (!$subdomainRecord) {
            $this->logger->error('Could not find record ("{sub}" in "{domain}") in DigitalOcean', ['sub' => $config->subdomain, 'domain' => $config->domain]);

            return;
        }

        $this->logger->info('Found "{sub}" in "{domain}" in "{recordType}" DigitalOcean; will update', [
            'sub'        => $config->subdomain,
            'domain'     =>
                $config->domain,
            'recordType' => $config->recordType,
        ]);

        try {
            $this->client->domainRecord()->updateData($config->domain, $subdomainRecord->id, (string) $toIpAddress);
        } catch (ExceptionInterface $exception) {
            throw new UpdateError('Unable to update record at DigitalOcean', previous: $exception);
        }
    }

    /**
     * @psalm-param array<DomainRecord> $records
     */
    private function resolveSubdomainRecord(array $records, string $subdomain, string $recordType): ?DomainRecord
    {
        foreach ($records as $record) {
            if ($record->name === $subdomain && strtolower($record->type) === strtolower($recordType)) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @throws \App\Domain\Exception\InvalidIpAddress
     * @throws \App\Domain\Exception\DomainNameException
     */
    public function getCurrentIpAddress(mixed $config): ?IpAddress
    {
        $this->client->authenticate($config->token);

        try {
            $records = $this->client->domainRecord()->getAll($config->domain);
        } catch (ExceptionInterface $e) {
            $this->logger->debug('DigitalOcean API has no records for domain');
            throw new DomainNameException('Unable to find records for domain', previous: $e);
        }

        $subdomainRecord = $this->resolveSubdomainRecord($records, $config->subdomain, $config->recordType);

        if ($subdomainRecord) {
            return new IpAddress($subdomainRecord->data);
        }

        return null;
    }

    /**
     * DigitalOcean expects something like digitalocean://subdomain@domain?token={token}
     * where the subdomain is optional
     */
    public function buildConfig(string $dsn): Config
    {
        return new Config($dsn);
    }
}
