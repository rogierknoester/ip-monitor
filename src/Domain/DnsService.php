<?php
declare(strict_types=1);

namespace App\Domain;

interface DnsService
{

    public function update(IpAddress $toIpAddress, MonitorConfig $config): void;

    public function supports(string $type): bool;
}
