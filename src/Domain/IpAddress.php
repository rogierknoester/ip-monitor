<?php
declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\InvalidIpAddress;

class IpAddress implements \Stringable
{
    /**
     * @throws \App\Domain\Exception\InvalidIpAddress
     */
    public function __construct(private string $value)
    {
        !filter_var($this->value, FILTER_VALIDATE_IP) && throw new InvalidIpAddress(sprintf('The value %s is not a valid IP address ', $this->value));
    }


    public function __toString(): string
    {
        return $this->value;
    }

    public function sameAs(?IpAddress $ipAddress): bool
    {
        return $ipAddress?->value === $this->value;
    }
}
