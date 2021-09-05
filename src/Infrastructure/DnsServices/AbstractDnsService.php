<?php
declare(strict_types=1);

namespace App\Infrastructure\DnsServices;

use App\Domain\DnsService;
use App\Domain\Exception\InvalidDsn;
use App\Infrastructure\DnsServices\DigitalOcean\Config;
use Nyholm\Dsn\Exception\FunctionsNotAllowedException;
use Nyholm\Dsn\Exception\SyntaxException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @template T
 * @implements DnsService<T>
 */
abstract class AbstractDnsService implements DnsService
{
    protected LoggerInterface $logger;

    #[Required]
    public function setLogger(LoggerInterface $dnsLogger): void
    {
        $this->logger = $dnsLogger;
    }

    public function supports(string $dsn): bool
    {
        try {
            $this->buildConfig($dsn);

            return true;
        } catch (SyntaxException | FunctionsNotAllowedException | InvalidDsn) {
            return false;
        }
    }

}
