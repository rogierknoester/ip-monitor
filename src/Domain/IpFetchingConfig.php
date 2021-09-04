<?php
declare(strict_types=1);

namespace App\Domain;

class IpFetchingConfig
{
    public function __construct(public string $type, public ?string $endpoint = null, public ?string $responseType = null)
    {

    }
}
