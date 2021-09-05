<?php
declare(strict_types=1);


use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;

/**
 * @psalm-suppress UndefinedClass
 */
return static function (FrameworkConfig $config, ContainerConfigurator $containerConfigurator) {

    $config
        ->httpMethodOverride(false)
        ->session([
            'handler_id'         => null,
            'cookie_secure'      => true,
            'cookie_samesite'    => 'lax',
            'storage_factory_id' => 'session.storage.factory.native',
        ]);

    $config->phpErrors(['log' => true]);

    $config->httpClient([
        'scoped_clients' => [
            'ip_address_fetcher_client' => [
                'headers' => [
                    'User-Agent' => 'Buzzed Bird',
                ],
            ],
        ],
    ]);

    if ($containerConfigurator->env() === 'test') {
        $config
            ->test(true)
            ->session(['storage_factory_id' => 'session.storage.factory.mock_file']);
    }

};
