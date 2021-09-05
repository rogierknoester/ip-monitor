<?php
declare(strict_types=1);

namespace App\Infrastructure\DnsServices\DigitalOcean;

use App\Domain\Exception\InvalidDsn;
use Nyholm\Dsn\DsnParser;

class Config
{
    public string $domain;

    public string $subdomain;

    public string $token;

    public string $recordType;

    public function __construct(string $dsn)
    {
        $dsnConfig = DsnParser::parse($dsn);

        if($dsnConfig->getScheme() !== 'digitalocean') {
            throw new InvalidDsn(sprintf("Expected a DSN for DigitalOcean but received %s", $dsn));
        }

        $this->domain = $dsnConfig->getHost() ?? throw new InvalidDsn('Missing domain in dsn');
        $this->subdomain = $dsnConfig->getUser() ?? '@';
        /** @var string token */
        $this->token = $dsnConfig->getParameter('token') ?? throw new InvalidDsn('Missing token in dsn');
        /** @var string recordType */
        $this->recordType = $dsnConfig->getParameter('recordType', 'A');
    }
}
