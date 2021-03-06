<?php
/**
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2016 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */
namespace Opulence\Bootstrappers\Dispatchers;

use Opulence\Bootstrappers\IBootstrapperRegistry;
use RuntimeException;

/**
 * Defines the interface for bootstrapper dispatchers to implement
 *
 * @deprecated since 1.0.0-beta6
 */
interface IBootstrapperDispatcher
{
    /**
     * Dispatches a registry
     *
     * @param IBootstrapperRegistry $registry The registry to dispatch
     * @throws RuntimeException Thrown if there was a problem dispatching the bootstrappers
     */
    public function dispatch(IBootstrapperRegistry $registry);

    /**
     * Sets whether or not we force eager loading for all bootstrappers
     *
     * @param bool $doForce Whether or not to force eager loading
     */
    public function forceEagerLoading(bool $doForce);
}