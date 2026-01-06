<?php

namespace Sefra;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Sefra\Exceptions\BindingResolutionException;
use TypeError;

class Container
{
    protected $bindings = [];
    protected $instances = [];
    protected array $buildStack = [];

    public static function getInstance(): Container
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new Container();
        }

        return $instance;
    }

    public function bind($abstract, $concrete = null, bool $shared = false)
    {

        if ($concrete instanceof Closure) {
            $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => $shared];
            return;
        }

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (! $concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => $shared];
    }

    public function singleton($abstract, $concrete = null)
    {
        return $this->bind($abstract, $concrete, true);
    }

    public function getClosure($abstract, $concrete)
    {
        return function ($container) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete);
        };
    }

    public function get($abstract)
    {
        return $this->resolve($abstract);
    }

    public function resolve($abstract)
    {

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (in_array($abstract, $this->buildStack, true)) {
            throw new BindingResolutionException("Circular dependency [$abstract]");
        }



        if (!$this->isBound($abstract)) {
            $this->singleton($abstract);
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

        $this->buildStack[] = $abstract;

        $object = $this->build($concrete);

        array_pop($this->buildStack);

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }


    public function isShared($abstract)
    {
        return isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared'] === true;
    }

    public function isBound($abstract)
    {
        return isset($this->bindings[$abstract]);
    }

    public function build($concrete)
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {

            $reflection = new ReflectionFunction($concrete);

            foreach ($reflection->getParameters() as $param) {
                if ($param->getType()?->getName() === self::class) {
                    throw new BindingResolutionException("Injecting the container into services is forbidden.");
                }
            }
            return $concrete($this);
        }


        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }

        if ($reflector->isAbstract() && !$this->isBound($concrete)) {
            throw new BindingResolutionException(
                "Abstract or interface [$concrete] is not bound."
            );
        }

        if (! $reflector->isInstantiable()) {
            throw new BindingResolutionException("Target class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {

            $instance = new $concrete;

            return $instance;
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            if (!$type || $type->isBuiltin()) {
                throw new BindingResolutionException(
                    "Cannot resolve parameter \${$dependency->getName()}"
                );
            }

            if ($type->getName() === self::class) {
                throw new BindingResolutionException(
                    "Injecting the container into services is forbidden."
                );
            }

            $results[] = $this->get($type->getName());
        }

        return $results;
    }
}
