<?php
declare(strict_types=1);

namespace App\Domain;

use Symfony\Component\Validator\Constraints as Assert;

class MonitorConfig
{
    public function __construct(
        #[Assert\Hostname]
        public string $domain,
        public string $checkingServiceDsn,
        public ?string $token,
    )
    {

    }
}
