<?php
declare(strict_types=1);

namespace App\Domain;

interface IpAddressFetcher
{
    public function fetch(string $checkingService): IpAddress;

    public function supports(string $dsn): bool;
}
