# Sefra Container

A lightweight **service container** for WordPress plugins and themes, inspired by Laravel's and other modern PHP frameworks’ dependency injection containers.  
It allows you to **bind classes, singletons, and closures**, and automatically resolves dependencies via constructor injection.

## Purpose

This container is a core part of the [Sefra WordPress Plugin Starter Kit](https://github.com/YoussefACHCHIRAJ/sefra-plugin-starter), providing dependency injection and service management for plugins.

## Installation

Use Composer to install:

```bash
composer require sefra/container
```

## Usage

```php
use Sefra\Core\Container;

// Get container instance
$container = Container::getInstance();

// Bind a class
$container->bind(App\Services\MyService::class);

// Bind a singleton
$container->singleton(App\Services\MySingletonService::class);

// Resolve an instance
$service = $container->get(App\Services\MyService::class);

```

## Constructor Injection

Dependencies are automatically resolved:

```php
class Logger {
    public function log($message) { /* ... */ }
}

class Mailer {
    public function __construct(Logger $logger) { $this->logger = $logger; }
}

$mailer = $container->resolve(Mailer::class); // Logger is injected automatically

```

## Restrictions

Injecting the container itself into services is forbidden to prevent tight coupling.

Circular dependencies are detected and will throw an exception.

## License

MIT

