<?php

namespace App\Application\Command;

use App\Domain\DnsService;
use App\Domain\Exception\UnsupportedCheckingService;
use App\Domain\Exception\UnsupportedDnsService;
use App\Domain\IpAddressFetcher;
use App\Domain\Monitor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'monitor',
    description: 'Checks the IP address for a given domain at a given DNS service',
)]
class MonitorCommand extends Command
{

    /**
     * @psalm-param iterable<IpAddressFetcher> $ipAddressFetchers
     * @psalm-param iterable<DnsService> $dnsServices
     */
    public function __construct(
        private iterable $ipAddressFetchers,
        private iterable $dnsServices,
        private Monitor $monitor,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('checking-service-dsn', InputArgument::REQUIRED, 'A DSN for the service used to check the IP of this monitor')
            ->addArgument('dns-service', InputArgument::REQUIRED, 'A DSN specifying the service used to update the IP of the DNS record');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $checkingServiceDsn */
        $checkingServiceDsn = $input->getArgument('checking-service-dsn');

        /** @var string $dnsDsn */
        $dnsDsn = $input->getArgument('dns-service');

        // The service that will fetch us our current IP
        // Especially necessary when behind NAT
        $ipAddressFetcher = $this->resolveIpAddressFetcher($checkingServiceDsn);

        // The service that will talk to the DNS service, e.g. DigitalOcean
        $dnsService = $this->resolveDnsService($dnsDsn);
        $dnsConfig = $dnsService->buildConfig($dnsDsn);

        try {
            $this->monitor->process($ipAddressFetcher, $dnsService, $dnsConfig, $checkingServiceDsn);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return Command::FAILURE;
        }
    }

    private function resolveIpAddressFetcher(string $checkingServiceDsn): IpAddressFetcher
    {
        foreach ($this->ipAddressFetchers as $fetcher) {
            if ($fetcher->supports($checkingServiceDsn)) {
                return $fetcher;
            }
        }

        throw new UnsupportedCheckingService(sprintf('The checking service "%s" is not supported', $checkingServiceDsn));
    }

    /**
     * @template TDns
     * @psalm-return DnsService<TDns>
     */
    private function resolveDnsService(string $dsn): DnsService
    {
        foreach ($this->dnsServices as $dnsService) {
            if ($dnsService->supports($dsn)) {
                return $dnsService;
            }
        }

        throw new UnsupportedDnsService(sprintf('The DSN "%s" is not supported', $dsn));
    }
}
