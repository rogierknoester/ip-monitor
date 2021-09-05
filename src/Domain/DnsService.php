<?php
declare(strict_types=1);

namespace App\Domain;

/**
 * @psalm-template TConfig
 */
interface DnsService
{

    /**
     * @psalm-param TConfig $config
     * @throws \App\Domain\Exception\DomainNameException
     * @throws \App\Domain\Exception\InvalidIpAddress
     */
    public function getCurrentIpAddress(mixed $config): ?IpAddress;

    /**
     * @psalm-param TConfig $config
     * @throws \App\Domain\Exception\UpdateError
     * @throws \App\Domain\Exception\DomainNameException
     */
    public function update(IpAddress $toIpAddress, mixed $config): void;

    public function supports(string $dsn): bool;

    /** @psalm-return TConfig */
    public function buildConfig(string $dsn): mixed;
}
