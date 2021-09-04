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

    /**
     * @throws \App\Domain\Exception\InvalidIpAddress
     */
    public function process(IpAddressFetcher $fetcher, DnsService $dnsService, MonitorConfig $config): void
    {

        // find out what our current IP address is
        $currentIpAddress = $fetcher->fetch($config->checkingServiceDsn);

        // check what the IP address in the DNS records is for the given domain
        try {
            $ipAddressOfExternalHost = new IpAddress(gethostbyname($config->domain));
        } catch (InvalidIpAddress) {
            $ipAddressOfExternalHost = null;
        }



        if($currentIpAddress->sameAs($ipAddressOfExternalHost)) {
            $this->logger->info('IP address of the application is the same as in the DNS records ({address})', ['address' => (string) $currentIpAddress]);
            return;
        }

        $this->logger->info('IP of application is ({app_ip}) different from domain ({domain_ip})', ['app_ip' => $currentIpAddress, 'domain_ip' =>
            $ipAddressOfExternalHost ?? '-']);


        $this->logger->info('Request DNS service to update DNS records');
        $dnsService->update($currentIpAddress, $config);

    }

}
