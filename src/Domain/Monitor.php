<?php
declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\InvalidIpAddress;
use Psr\Log\LoggerInterface;

class Monitor
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function process(IpAddressFetcher $fetcher, DnsService $dnsService, mixed $dnsConfig, string $checkingServiceDsn): void
    {
        // find out what our current IP address is
        $currentIpAddress = $fetcher->fetch($checkingServiceDsn);

        $this->logger->info('The current IP address of the server this application runs on is {address}', ['address' => (string) $currentIpAddress]);

        $this->logger->info('Finding out what IP address is set in the DNS records');

        // use the DNS service to see what the current registered IP address is
        try {
            $ipAddressOfExternalHost = $dnsService->getCurrentIpAddress($dnsConfig);

            if(!$ipAddressOfExternalHost) {
                throw new InvalidIpAddress();
            }
        } catch (InvalidIpAddress | Exception\DomainNameException) {
            $this->logger->error('The IP address could not be found for the provided DNS config. Does the domain exist in the DNS service?');

            return;
        }


        $this->logger->info('The IP address in the DNS records is {address}', ['address' => (string) $ipAddressOfExternalHost]);

        if ($currentIpAddress->sameAs($ipAddressOfExternalHost)) {
            $this->logger->info('The IP addresses are the same. No further action required.');

            return;
        }

        $this->logger->info('The IP addresses are different. {current} & {expected}', [
            'current'  => (string) $currentIpAddress,
            'expected' =>
                (string) $ipAddressOfExternalHost,
        ]);
        $this->logger->info('Requesting DNS service to update DNS records');

        try {
            $dnsService->update($currentIpAddress, $dnsConfig);
        } catch (Exception\UpdateError $exception) {
            $this->logger->error('Unable to update DNS', ['exception' => $exception]);
        }
    }

}
