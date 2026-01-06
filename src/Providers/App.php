<?php

namespace Sefra\Providers;

use RuntimeException;
use Sefra\Container;

final class App
{

    public static function registerProviders(array $providers): void
    {
        $container = Container::getInstance();
        
        foreach ($providers as $providerClass) {
            $provider = new $providerClass();

            if (!$provider instanceof ServiceProvider) {
                throw new RuntimeException("[$providerClass] must implement ServiceProvider");
            }

            $provider->register($container);
            $provider->boot($container);
        }
    }
}
