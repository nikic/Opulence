<?php
/**
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2016 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */
namespace Opulence\Bootstrappers\Dispatchers;

use Opulence\Applications\Tasks\Dispatchers\ITaskDispatcher;
use Opulence\Applications\Tasks\TaskTypes;
use Opulence\Bootstrappers\Bootstrapper;
use Opulence\Bootstrappers\IBootstrapperRegistry;
use Opulence\Ioc\IContainer;
use RuntimeException;

/**
 * Defines the bootstrapper dispatcher
 *
 * @deprecated since 1.0.0-beta6
 */
class BootstrapperDispatcher implements IBootstrapperDispatcher
{
    /** @var ITaskDispatcher The task dispatcher */
    private $taskDispatcher = null;
    /** @var IContainer The IoC container */
    private $container = null;
    /** @var bool Whether or not we force eager loading for all bootstrappers */
    private $forceEagerLoading = false;
    /** @var array The list of bootstrapper classes that have been run */
    private $runBootstrappers = [];

    /**
     * @param ITaskDispatcher $taskDispatcher The task dispatcher
     * @param IContainer $container The IoC container
     */
    public function __construct(ITaskDispatcher $taskDispatcher, IContainer $container)
    {
        $this->taskDispatcher = $taskDispatcher;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function dispatch(IBootstrapperRegistry $registry)
    {
        if ($this->forceEagerLoading) {
            $eagerBootstrapperClasses = $registry->getEagerBootstrappers();
            $lazyBootstrapperClasses = [];

            foreach (array_values($registry->getLazyBootstrapperBindings()) as $bindingData) {
                $lazyBootstrapperClasses[] = $bindingData["bootstrapper"];
            }

            $lazyBootstrapperClasses = array_unique($lazyBootstrapperClasses);
            $bootstrapperClasses = array_merge($eagerBootstrapperClasses, $lazyBootstrapperClasses);
            $this->dispatchEagerly($registry, $bootstrapperClasses);
        } else {
            // We must dispatch lazy bootstrappers first in case their bindings are used by eager bootstrappers
            $this->dispatchLazily($registry, $registry->getLazyBootstrapperBindings());
            $this->dispatchEagerly($registry, $registry->getEagerBootstrappers());
        }
    }

    /**
     * @inheritdoc
     */
    public function forceEagerLoading(bool $doForce)
    {
        $this->forceEagerLoading = $doForce;
    }

    /**
     * Dispatches the registry eagerly
     *
     * @param IBootstrapperRegistry $registry The bootstrapper registry
     * @param array $bootstrapperClasses The list of bootstrapper classes to dispatch
     * @throws RuntimeException Thrown if there was a problem dispatching the bootstrappers
     */
    private function dispatchEagerly(IBootstrapperRegistry $registry, array $bootstrapperClasses)
    {
        /** @var Bootstrapper[] $bootstrapperObjects */
        $bootstrapperObjects = [];

        foreach ($bootstrapperClasses as $bootstrapperClass) {
            /** @var Bootstrapper $bootstrapper */
            $bootstrapper = $registry->resolve($bootstrapperClass);
            $bootstrapper->initialize();
            $bootstrapperObjects[] = $bootstrapper;
        }

        foreach ($bootstrapperObjects as $bootstrapper) {
            $bootstrapper->registerBindings($this->container);
        }

        foreach ($bootstrapperObjects as $bootstrapper) {
            $this->container->callMethod($bootstrapper, "run", [], true);
        }

        // Call the shutdown method
        $this->taskDispatcher->registerTask(TaskTypes::PRE_SHUTDOWN, function () use ($bootstrapperObjects) {
            foreach ($bootstrapperObjects as $bootstrapper) {
                $this->container->callMethod($bootstrapper, "shutdown", [], true);
            }
        });
    }

    /**
     * Dispatches the registry lazily
     *
     * @param IBootstrapperRegistry $registry The bootstrapper registry
     * @param array $boundClassesToBindingData The mapping of bound classes to their targets and bootstrappers
     * @throws RuntimeException Thrown if there was a problem dispatching the bootstrappers
     */
    private function dispatchLazily(IBootstrapperRegistry $registry, array $boundClassesToBindingData)
    {
        // This gets passed around by reference so that it'll have the latest objects come time to shut down
        $bootstrapperObjects = [];

        foreach ($boundClassesToBindingData as $boundClass => $bindingData) {
            $bootstrapperClass = $bindingData["bootstrapper"];
            $target = $bindingData["target"];

            $factory = function () use ($registry, &$bootstrapperObjects, $boundClass, $bootstrapperClass, $target) {
                // To make sure this factory isn't used anymore to resolve the bound class, unbind it
                // Otherwise, we'd get into an infinite loop every time we tried to resolve it
                if ($target === null) {
                    $this->container->unbind($boundClass);
                } else {
                    $this->container->for($target, function (IContainer $container) use ($boundClass) {
                        $container->unbind($boundClass);
                    });
                }

                $bootstrapper = $registry->resolve($bootstrapperClass);

                if (!in_array($bootstrapper, $bootstrapperObjects)) {
                    $bootstrapperObjects[] = $bootstrapper;
                }

                if (!isset($this->runBootstrappers[$bootstrapperClass])) {
                    $bootstrapper->initialize();
                    $bootstrapper->registerBindings($this->container);
                    $this->container->callMethod($bootstrapper, "run", [], true);
                    $this->runBootstrappers[$bootstrapperClass] = true;
                }

                if ($target === null) {
                    return $this->container->resolve($boundClass);
                } else {
                    return $this->container->for($target, function (IContainer $container) use ($boundClass) {
                        return $container->resolve($boundClass);
                    });
                }
            };

            if ($target === null) {
                $this->container->bindFactory($boundClass, $factory);
            } else {
                $this->container->for($target, function (IContainer $container) use ($boundClass, $factory) {
                    $container->bindFactory($boundClass, $factory);
                });
            }
        }

        // Call the shutdown method
        $this->taskDispatcher->registerTask(TaskTypes::PRE_SHUTDOWN, function () use (&$bootstrapperObjects) {
            foreach ($bootstrapperObjects as $bootstrapper) {
                $this->container->callMethod($bootstrapper, "shutdown", [], true);
            }
        });
    }
}