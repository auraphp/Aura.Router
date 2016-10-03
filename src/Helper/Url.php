<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router\Helper;

use Aura\Router\Exception\RouteNotFound;
use Aura\Router\Generator;

/**
 *
 * Generic Url Helper class
 *
 * @package Aura.Router
 *
 */
class Url
{
    /**
     *
     * The Generator object used by the RouteContainer
     *
     * @var Generator
     *
     */
    protected $generator;

    /**
     *
     * Constructor.
     *
     * @param Generator $generator The generator object to use
     *
     */
    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     *
     * Returns the Generator
     *
     * @param string|null $name The name of the route to lookup.
     *
     * @param array $data The data to pass into the route
     *
     * @return Generator|string|false The generator object, or the results of {Aura\Router\Generator::generate()}
     *                                when $name is null
     *
     * @throws RouteNotFound When the route cannot be found, thrown by {Aura\Router\Generator::generate()}
     *
     */
    public function __invoke($name = null, array $data = [])
    {
        return $name === null
            ? $this->generator
            : $this->generator->generate($name, $data);
    }
}