<?php

namespace App\Application\Command;

use App\Domain\DnsService;
use App\Domain\Exception\InvalidIpAddress;
use App\Domain\Exception\UnsupportedCheckingService;
use App\Domain\Exception\UnsupportedDnsService;
use App\Domain\IpAddressFetcher;
use App\Domain\Monitor;
use App\Domain\MonitorConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'monitor',
    description: 'St',
)]
class MonitorCommand extends Command
{

    /**
     * @psalm-param iterable<IpAddressFetcher> $ipAddressFetchers
     * @psalm-param iterable<DnsService>       $dnsServices
     */
    public function __construct(
        private iterable $ipAddressFetchers,
        private iterable $dnsServices,
        private Monitor $monitor,
        private LoggerInterface $logger,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('domain', InputArgument::REQUIRED, 'The domain you\'re checking')
            ->addArgument('checking-service-dsn', InputArgument::REQUIRED, 'A DSN for the service used to check the IP of this monitor',)
            ->addArgument('dns-service', InputArgument::REQUIRED, 'One of the supported DNS services, e.g. DigitalOcean')
            ->addArgument('token', InputArgument::REQUIRED, 'The token to use with the DNS service\'s API');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $domain = $input->getArgument('domain');
        $checkingServiceDsn = $input->getArgument('checking-service-dsn');
        $dnsService = $input->getArgument('dns-service');
        $token = $input->getArgument('token');

        $config = new MonitorConfig($domain, $checkingServiceDsn, $token);

        $violations = $this->validator->validate($config);
        if ($violations->count() > 0) {

            $io->error([
                'The provided config is invalid.',
                ...array_map(static fn(ConstraintViolationInterface $violation) => sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage()),
                    [...$violations]),
            ]);

            return Command::FAILURE;
        }

        $ipAddressFetcher = $this->resolveIpAddressFetcher($checkingServiceDsn);

        $dnsService = $this->resolveDnsService($dnsService);

        try {
            $this->monitor->process($ipAddressFetcher, $dnsService, $config);
        } catch (InvalidIpAddress $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return Command::FAILURE;
        }


        return Command::SUCCESS;
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

    private function resolveDnsService(string $type): DnsService
    {
        foreach ($this->dnsServices as $dnsService) {
            if ($dnsService->supports($type)) {
                return $dnsService;
            }
        }

        throw new UnsupportedDnsService(sprintf('The DNS service "%s" is not supported', $type));
    }
}
