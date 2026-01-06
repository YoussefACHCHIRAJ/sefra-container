<?php

namespace Sefra\Providers;

use Sefra\Container;

interface ServiceProvider
{
    public function register(Container $container): void;

    public function boot(Container $container): void;
}
