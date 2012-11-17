<?php
/**
 * 
 * This file is part of the Aura Project for PHP.
 * 
 * @package Aura.Router
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Router;

/**
 * 
 * A factory to create Definition objects.
 * 
 * @package Aura.Router
 * 
 */
class DefinitionFactory
{
    /**
     * 
     * Returns a new Definition instance.
     * 
     * @param string $type The type of definition, 'single' or 'attach'.
     * 
     * @param array|callable $spec The definition spec: either an array, or a
     * callable that returns an array.
     * 
     * @param string $path_prefix For 'attach' definitions, use this as the 
     * prefix for attached paths.
     * 
     * @return Route
     * 
     */
    public function newInstance($type, $spec, $path_prefix = null)
    {
        return new Definition($type, $spec, $path_prefix);
    }
}
